import { reactive, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { roterPre } from '@/settings'
import { getGroupChatyDetails, saveChannelCodeApi, putChannelCodeApi, groupInvoteConfigDetailApi } from '@/api/weCom'
import { useLoading } from '@/composables/useLoading'
import { useChannelCodeGroup } from './useChannelCodeGroup'
import type { FormInstance } from 'element-plus'
import type { ArrayValidator, GroupInvoteConfig, IdItem, WelcomeAttach } from './types'

/**
 * 渠道活码新增 / 编辑表单逻辑
 *
 * 编辑态由路由 query.id 驱动（独立路由页面，不再依赖 props）；
 * 弹窗 ref、素材组件交互等强耦合 UI 的逻辑留在页面组件内。
 */
export const useChannelCodeForm = () => {
  const route = useRoute()
  const router = useRouter()

  // 编辑态：路由携带 id 时拉取详情回填
  const editId = Number(route.query.id) || 0
  const isEdit = computed(() => !!editId)
  const pageTitle = computed(() => (isEdit.value ? '编辑渠道活码页面' : '新建渠道活码页面'))

  // 活码分组下拉数据（与加群配置 groupConfig 区分命名）
  const { groupList: codeGroupList, fetchGroupList } = useChannelCodeGroup()

  // 新增态支持由列表页带入当前选中分组（列表选「全部」时不携带）；编辑态一律以详情为准
  const presetGroupId: number | string = isEdit.value ? '' : Number(route.query.group_id) || ''

  const formData = reactive({
    name: '',
    group_id: presetGroupId, // 活码分组 ID；空表示未选择，由必填校验兜底
    userids: [] as Array<number | string>,
    tag_ids: [] as Array<number | string>,
    skip_verify: 1,
    status: 1,
    remark: '',
    invite_group: 1,
    welcome: {
      content: '',
      attach: [] as WelcomeAttach[]
    },
    // 加群配置 ID：邀请加群的群聊与自动建群规则已拆分为独立的「加群配置」模块，此处只引用；
    // 空串表示未选择，提交时转为 0（接口要求 integer）
    code_id: '' as number | string
  })

  const rules = {
    name: [{ required: true, message: '请输入扫码名称', trigger: 'change' }],
    group_id: [{ required: true, message: '请选择分组', trigger: 'change' }],
    skip_verify: [{ required: true, message: '请选择验证方式', trigger: 'change' }],
    userids: [
      {
        required: true,
        validator: ((rule, value, callback) => {
          if (!value || !value.length) {
            callback(new Error('请选择使用员工'))
          } else {
            callback()
          }
        }) as ArrayValidator,
        trigger: 'change'
      }
    ],
    // 欢迎语内容由 materialContent 维护，变更后由页面手动 validateField
    'welcome.content': [{ required: true, message: '请输入新客户欢迎语', trigger: 'change' }],
    // 加群配置由选择弹窗维护，仅在开启邀请加群（表单项渲染）时参与校验
    code_id: [{ required: true, message: '请选择加群配置', trigger: 'change' }]
  }

  // 关联集合：客户标签 / 使用员工
  const labelList = ref<IdItem[]>([])
  const userList = ref<IdItem[]>([])

  // 选中的加群配置，表单项后展示其群聊名称
  const groupConfig = ref<GroupInvoteConfig | null>(null)
  // 加群配置展示名：该配置下所有群聊名称的顿号拼接
  const groupConfigName = ref('')

  const formatGroupNames = (names?: Array<string | undefined> | null) => (names || []).filter(Boolean).join('、')

  // 加群配置与 formData 保持同步：接口只收 code_id，群聊与建群规则由后端按该配置派生，
  // 完整配置对象仅留在 groupConfig 供页面展示名称
  // 详情接口的 invite_group_config 用 code_id 承载配置 ID，列表行则是 id
  // names：编辑态由活码详情的 group_names 直接给出；不传则从配置的群聊明细 group_chat 派生
  const setGroupConfig = (config: GroupInvoteConfig | null | undefined, names?: string[]) => {
    groupConfig.value = config || null
    formData.code_id = config?.code_id || config?.id || ''
    groupConfigName.value = formatGroupNames(names || (config?.group_chat || []).map((item) => item.name))
  }

  // 编辑态回填加群配置：详情已带配置明细时直接用，只有 ID 时再拉一次详情补齐展示名称
  const loadGroupConfig = async (data: {
    code_id?: number
    invite_group_config?: GroupInvoteConfig
    group_names?: string[]
  }) => {
    // 先按详情回填关联 ID，配置明细取不到时也不至于丢失已选关联
    formData.code_id = data.code_id || ''
    // 内嵌的 invite_group_config 不含群聊明细，群名统一取详情顶层的 group_names
    if (data.invite_group_config) {
      setGroupConfig(data.invite_group_config, data.group_names)
      return
    }
    if (!data.code_id) return
    try {
      const res = await groupInvoteConfigDetailApi(data.code_id)
      if (res.status === 200 && res.data) setGroupConfig(res.data, data.group_names)
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    }
  }

  // 编辑回填详情；返回后端详情数据，欢迎语素材由页面组件回填到 materialContent
  const { isLoading: detailLoading, startLoading: startDetail, stopLoading: stopDetail } = useLoading()
  const loadDetail = async () => {
    if (!editId) return null
    startDetail()
    try {
      const res = await getGroupChatyDetails(editId)
      // http 库业务失败（status 400 等）仍会 resolve，需显式判定
      if (res.status !== 200) return null
      const data = res.data || {}
      formData.name = data.name || ''
      formData.group_id = data.group_id || '' // 0 表示未分组，回显为空由必填校验兜底
      formData.userids = data.userids || []
      formData.tag_ids = data.tag_ids || []
      // 后端可能返回字符串，统一转 number 防止单选 / 开关取值匹配失败
      formData.skip_verify = Number(data.skip_verify)
      formData.invite_group = Number(data.invite_group)
      formData.status = Number(data.status ?? 1)
      formData.remark = data.remark || ''
      formData.welcome = data.welcome || { content: '', attach: [] }

      labelList.value = data.tags || []
      userList.value = data.users || []
      // 加群相关配置整体由加群配置回填，不再逐字段读取详情
      await loadGroupConfig(data)
      return data
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
      return null
    } finally {
      stopDetail()
    }
  }

  const goList = () => {
    router.push({ path: roterPre + '/customer/weChatMass/createGroupList' })
  }

  // 提交：attach 为 materialContent 维护的欢迎语附件列表（由页面组件传入）
  const { isLoading: submitting, startLoading: startSubmit, stopLoading: stopSubmit } = useLoading()
  const save = async (formRef: FormInstance, attach?: WelcomeAttach[]) => {
    try {
      await formRef.validate()
    } catch (e) {
      return
    }
    const payload = structuredClone(formData);
    payload.tag_ids = labelList.value.map((item) => item.id)
    // 接口要求 integer；未选择或关闭邀请加群时传 0
    payload.code_id = payload.invite_group === 1 ? Number(payload.code_id) || 0 : 0
    payload.welcome.attach = structuredClone(attach || [])
    payload.welcome.attach.forEach((obj) => {
      delete obj.file
    })

    startSubmit()
    try {
      const res = editId
        ? await putChannelCodeApi(editId, payload)
        : await saveChannelCodeApi(payload)
      if (res.status === 200) goList()
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopSubmit()
    }
  }

  return {
    isEdit,
    pageTitle,
    codeGroupList,
    fetchGroupList,

    formData,
    rules,
    labelList,
    userList,
    groupConfig,
    groupConfigName,
    setGroupConfig,

    detailLoading,
    submitting,
    loadDetail,
    save
  }
}
