import { ref, reactive, computed, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router/composables'
import { roterPre } from '@/settings'
import { useLoading } from '@/composables/useLoading'
import { groupInvoteConfigDetailApi, groupInvoteConfigCreateApi, groupInvoteConfigUpdateApi } from '@/api/weCom'

// 邀请加入的群聊最多可选数量（与渠道活码原「邀请加群设置」保持一致）
export const MAX_GROUP_CHAT = 5

/**
 * 自动建群配置表单逻辑：同时支持新增与编辑
 * - 编辑态由 URL query.id 判定，进入页面时拉取详情回填
 * - 群聊列表由 groupChat 弹窗维护，与 form.chat_ids 双向同步供必填校验读取
 */
export const useGroupInvoteConfigForm = () => {
  const route = useRoute()
  const router = useRouter()

  const formRef = ref(null)

  const configId = computed(() => route.query.id || null)
  const isEdit = computed(() => !!configId.value)
  const title = computed(() => (isEdit.value ? '编辑自动建群配置' : '新增自动建群配置'))

  const form = reactive({
    room_base_name: '', // 群聊名称，同时作为配置在列表与活码表单中的展示名
    chat_ids: [], // 邀请加入的群聊 ID
    auto_create_room: 1, // 群人数达上限时自动创建新群
    room_base_id: 1 // 群序号起始值，0 表示不启用序号
  })

  const rules = {
    room_base_name: [{ required: true, message: '请输入群聊名称', trigger: 'blur' }],
    chat_ids: [
      {
        required: true,
        validator(rule, value, callback) {
          if (!value || !value.length) {
            callback(new Error('请选择邀请加入的群聊'))
          } else if (value.length > MAX_GROUP_CHAT) {
            // 兜底历史数据超限的情况，弹窗侧已限制新增选择
            callback(new Error(`最多只能选择${MAX_GROUP_CHAT}个群聊`))
          } else {
            callback()
          }
        },
        trigger: 'change'
      }
    ]
  }

  // 群聊明细（含群主、人数等展示字段），提交时一并回传，便于列表 / 活码表单直接展示
  const groupList = ref([])

  const setGroupList = (list) => {
    groupList.value = list || []
    form.chat_ids = groupList.value.map((item) => item.chat_id).filter(Boolean)
  }

  // 群聊列表由弹窗维护，变更后手动触发对应表单项校验
  const validateGroupChat = () => {
    nextTick(() => {
      formRef.value && formRef.value.validateField('chat_ids')
    })
  }

  const handleSelectGroups = (list) => {
    setGroupList(list)
    validateGroupChat()
  }

  const removeGroup = (index) => {
    groupList.value.splice(index, 1)
    setGroupList(groupList.value)
    validateGroupChat()
  }

  // 详情已返回 group_chat 群聊明细（群名 / 群主 / 人数等），直接回显，无需再拉企微客户群列表匹配；
  // 以 chat_ids 为准保持原有顺序，group_chat 未覆盖到的保留 ID 占位，避免提交时丢群
  const resolveGroupList = (data) => {
    const chatMap = new Map((data.group_chat || []).map((item) => [item.chat_id, item]))
    return (data.chat_ids || []).map((chatId) => chatMap.get(chatId) || { chat_id: chatId })
  }

  const { isLoading: detailLoading, startLoading: startDetail, stopLoading: stopDetail } = useLoading()
  const loadDetail = async () => {
    if (!isEdit.value) return
    startDetail()
    try {
      const res = await groupInvoteConfigDetailApi(configId.value)
      // http 库业务失败（status 400 等）仍会 resolve，需显式判定
      if (res.status !== 200 || !res.data) return
      const data = res.data
      form.room_base_name = data.room_base_name || ''
      // 后端可能返回字符串，统一转 number 防止勾选 / 序号取值匹配失败
      form.auto_create_room = Number(data.auto_create_room ?? 0)
      form.room_base_id = Number(data.room_base_id ?? 0)
      setGroupList(resolveGroupList(data))
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopDetail()
    }
  }

  const goList = () => {
    router.push({ path: roterPre + '/customer/weChatMass/groupInvoteConfig' })
  }

  const { isLoading: submitting, startLoading: startSubmit, stopLoading: stopSubmit } = useLoading()
  const submit = () => {
    formRef.value.validate(async (valid) => {
      if (!valid) return
      const payload = { ...form }

      startSubmit()
      try {
        const res = isEdit.value
          ? await groupInvoteConfigUpdateApi(configId.value, payload)
          : await groupInvoteConfigCreateApi(payload)
        if (res.status === 200) goList()
      } catch (e) {
        // 错误提示由 request 统一处理，这里静默
      } finally {
        stopSubmit()
      }
    })
  }

  return {
    formRef,
    form,
    rules,
    title,
    isEdit,
    groupList,
    detailLoading,
    submitting,

    setGroupList,
    handleSelectGroups,
    removeGroup,
    loadDetail,
    submit
  }
}
