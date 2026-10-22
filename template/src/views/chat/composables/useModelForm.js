import { ref, computed } from 'vue'
import { fetchModelsApi, getModelsInfoApi, saveModelsApi, editModelsApi, testModelsApi } from '@/api/chatAi'
import {
  CONFIG_TYPE,
  CUSTOM_DRIVER,
  MODEL_CATEGORY,
  MODEL_GROUP,
  MODEL_TYPE,
  YIHAOTONG_DRIVER,
  THINKING_MODE
} from '../utils/model'

// 业务成功状态码。request 封装对 status=400 走 resolve（并已统一弹出错误提示），
// 因此凡是要按结果继续往下走的调用，都必须显式判定 status
const SUCCESS_STATUS = 200

// 下拉项统一形如 { label, value }。已填的值若不在拉取结果里则补一项，
// 保证「手动输入的模型名」与「未重新拉取时的详情值」都能正常回显
const withSelected = (options, selected) => {
  if (!selected || options.some((item) => item.value === selected)) return options
  return [{ label: selected, value: selected }, ...options]
}

const createAdvancedJson = () => ({
  input_tokens: null,
  output_tokens: null,
  max_tool_rounds: null,
  temperature: null,
  top_p: null,
  top_k: null
})

// 表单字段与后端 ChatModel 保存请求一一对应，详情回填也只按这些 key 取值
const createForm = () => ({
  config_type: CONFIG_TYPE.PRESET,
  name: '',
  provider: '',
  base_url: '',
  // 「完整URL」：开启表示 base_url 已含接口路径，仅自定义配置下发
  is_full_url: 0,
  model_id: '',
  api_key: '',
  status: 1,
  enable_thinking: THINKING_MODE.DEFAULT,
  json: createAdvancedJson()
})

/**
 * 把详情里的 json 收敛成高级配置表单结构（空值保持 null，调用默认由后端兜底）.
 * @param {Object|null|undefined} raw
 */
const normalizeAdvancedJson = (raw) => {
  const source = raw && typeof raw === 'object' ? raw : {}
  const pickNumber = (value) => {
    if (value === null || value === undefined || value === '') return null
    const num = Number(value)
    return Number.isFinite(num) ? num : null
  }
  return {
    input_tokens: pickNumber(source.input_tokens),
    output_tokens: pickNumber(source.output_tokens),
    max_tool_rounds: pickNumber(source.max_tool_rounds),
    temperature: pickNumber(source.temperature),
    top_p: pickNumber(source.top_p),
    top_k: pickNumber(source.top_k)
  }
}

/**
 * 是否配置过任意高级参数（用于编辑态自动展开）.
 * @param {Object} json
 * @param {Number} enableThinking
 */
const hasCustomAdvanced = (json, enableThinking) => {
  const values = Object.values(json || {})
  if (values.some((value) => value !== null && value !== undefined && value !== '')) return true
  return enableThinking !== THINKING_MODE.DEFAULT
}

/**
 * 提交前清理高级配置：空值不下发，避免把 null 写成库内脏数据.
 * @param {Object} json
 */
const compactAdvancedJson = (json) => {
  const payload = {}
  Object.keys(json || {}).forEach((key) => {
    const value = json[key]
    if (value === null || value === undefined || value === '') return
    payload[key] = value
  })
  return payload
}

/**
 * 模型新增 / 编辑弹窗表单逻辑
 *
 * @param {Object}   options
 * @param {Function} options.getProviderOptions 返回供应商选项数组（getModelsOptionsApi 的结果）
 */
export const useModelForm = ({ getProviderOptions }) => {
  /* ------------------------------------------------------------------ 状态 */

  const form = ref(createForm())
  // 模型类别：仅前端使用，不参与提交，只决定「模型」下拉展示聊天还是向量选项
  const modelCategory = ref(MODEL_CATEGORY.CHAT)
  // 编辑态的记录 id，为空表示新增
  const editId = ref('')
  const isDetailLoading = ref(false)
  const isModelLoading = ref(false)
  // 「获取模型」从供应商拉回的模型，按能力分组存放，切换配置类型 / 服务商后失效
  const remoteChatModels = ref([])
  const remoteEmbeddingModels = ref([])
  // 上一次成功拉取模型时的配置指纹，用于密钥失焦自动拉取时跳过重复请求
  const lastFetchSignature = ref('')

  /* -------------------------------------------------------------- 派生状态 */

  const isEdit = computed(() => !!editId.value)
  const isPreset = computed(() => form.value.config_type === CONFIG_TYPE.PRESET)
  const isCustom = computed(() => form.value.config_type === CONFIG_TYPE.CUSTOM)
  const isYihaotong = computed(() => form.value.config_type === CONFIG_TYPE.YIHAOTONG)
  const isEmbeddingCategory = computed(() => modelCategory.value === MODEL_CATEGORY.EMBEDDING)
  const title = computed(() => (isEdit.value ? '编辑模型' : '添加模型'))

  // 供应商选项接口把「自定义 / 一号通」也作为项返回，这里按 config_type 拆开使用
  const providerOptions = computed(() => getProviderOptions() || [])
  const presetOptions = computed(() =>
    providerOptions.value.filter((item) => item.config_type === CONFIG_TYPE.PRESET)
  )
  const customDriver = computed(() => {
    const custom = providerOptions.value.find((item) => item.config_type === CONFIG_TYPE.CUSTOM)
    return (custom && custom.value) || CUSTOM_DRIVER
  })
  const yihaotongOption = computed(() =>
    providerOptions.value.find((entry) => entry.config_type === CONFIG_TYPE.YIHAOTONG) || null
  )
  const yihaotongDriver = computed(() => (yihaotongOption.value && yihaotongOption.value.value) || YIHAOTONG_DRIVER)
  // 后端 options.enabled：已填 AppId/AppSecret 且连通性校验通过（换取 API Key 成功），不回传密钥
  const yihaotongEnabled = computed(() => !!(yihaotongOption.value && yihaotongOption.value.enabled))
  // 选中一号通但不可用：选项可选，下方表单锁定
  const isYihaotongLocked = computed(() => isYihaotong.value && !yihaotongEnabled.value)
  const defaultConfigType = computed(() =>
    yihaotongEnabled.value ? CONFIG_TYPE.YIHAOTONG : CONFIG_TYPE.PRESET
  )
  // 一号通固定排在首位：未配置时仍可选，选中后表单锁定并引导去配置
  const configTypeOptions = computed(() => [
    { label: '一号通', value: CONFIG_TYPE.YIHAOTONG, disabled: false },
    { label: '模型服务商', value: CONFIG_TYPE.PRESET, disabled: false },
    { label: '自定义配置', value: CONFIG_TYPE.CUSTOM, disabled: false }
  ])

  // 取模型与提交时真正使用的 provider
  const currentDriver = computed(() => {
    if (isYihaotong.value) return yihaotongDriver.value
    if (isCustom.value) return customDriver.value
    return form.value.provider
  })
  const currentProvider = computed(
    () => providerOptions.value.find((item) => item.value === currentDriver.value) || null
  )
  // API Key 申请入口由 options 接口下发（api_key_url），未下发的供应商不展示入口
  const apiKeyDocUrl = computed(() => (currentProvider.value && currentProvider.value.api_key_url) || '')

  // 聊天 / 向量模型都是「可下拉选择、也可手动输入」；按当前类别暴露同一份选项
  const chatModelOptions = computed(() => withSelected(remoteChatModels.value, form.value.model_id))
  const embeddingModelOptions = computed(() =>
    withSelected(remoteEmbeddingModels.value, form.value.model_id)
  )
  const modelOptions = computed(() =>
    isEmbeddingCategory.value ? embeddingModelOptions.value : chatModelOptions.value
  )

  /* -------------------------------------------------------------- 表单联动 */

  // 换配置类型 / 换服务商后，已拉取的模型不再适用，连同已选值一并清空
  const resetModels = () => {
    remoteChatModels.value = []
    remoteEmbeddingModels.value = []
    lastFetchSignature.value = ''
    form.value.model_id = ''
  }

  const handleConfigTypeChange = () => {
    form.value.provider = isYihaotong.value ? yihaotongDriver.value : ''
    form.value.base_url = ''
    form.value.api_key = ''
    form.value.is_full_url = 0
    resetModels()
  }

  const applyDefaultConfigType = () => {
    form.value.config_type = defaultConfigType.value
    if (isYihaotong.value) {
      form.value.provider = yihaotongDriver.value
    }
  }

  const handleDriverChange = () => {
    resetModels()
  }

  // 模型变化时按配置类型生成名称：预设模型带服务商前缀，其余只用模型值
  const handleModelChange = (model) => {
    if (!model) return
    const providerName = currentProvider.value && currentProvider.value.label
    form.value.name = isPreset.value && providerName ? `${providerName}-${model}` : model
  }

  /* -------------------------------------------------------------- 获取模型 */

  // 拉完模型后自动选中首项：下拉不展开时用户看不出取到了没有，空着容易被当成拉取失败。
  // 已经选过的值不覆盖
  const autoSelectModels = () => {
    if (form.value.model_id) return
    const options = isEmbeddingCategory.value ? remoteEmbeddingModels.value : remoteChatModels.value
    if (!options.length) return
    form.value.model_id = options[0].value
    handleModelChange(form.value.model_id)
  }

  // 切换类别时清空已选模型，再按新类别补默认值
  const handleModelCategoryChange = () => {
    form.value.model_id = ''
    autoSelectModels()
    handleModelChange(form.value.model_id)
  }

  /**
   * 拉取当前服务商可选的模型
   *
   * 预设/自定义会带着 api_key 真实调用供应商的 /models；
   * 一号通只传 config_type，由后端自行补全密钥与地址。
   *
   * @return {Promise<{ success: Boolean, message?: String }>} message 为空表示错误已由 request 统一提示
   */
  const fetchModels = async () => {
    if (isYihaotong.value) {
      // 一号通无需服务商 / 密钥 / 地址
    } else if (!currentDriver.value) {
      return { success: false, message: '请先选择服务商' }
    } else if (!form.value.api_key) {
      return { success: false, message: '请先输入API密钥' }
    } else if (isCustom.value && !form.value.base_url) {
      return { success: false, message: '请先输入自定义请求地址' }
    }

    isModelLoading.value = true
    try {
      const payload = {
        config_type: form.value.config_type,
        provider: currentDriver.value
      }
      if (!isYihaotong.value) {
        payload.api_key = form.value.api_key
        if (form.value.base_url) payload.base_url = form.value.base_url
      }

      // 请求发出即记指纹：同一份配置（含密钥错误的那份）不再被失焦自动拉取重复触发，
      // 需要重试时走手动「获取模型」，它不看指纹
      lastFetchSignature.value = buildFetchSignature()

      const res = await fetchModelsApi(payload)
      if (res.status !== SUCCESS_STATUS) return { success: false }

      const groups = res.data || []
      const pickGroup = (value) => {
        const group = groups.find((item) => item.value === value)
        return (group && group.children) || []
      }
      remoteChatModels.value = pickGroup(MODEL_GROUP.CHAT)
      remoteEmbeddingModels.value = pickGroup(MODEL_GROUP.EMBEDDING)
      autoSelectModels()

      const models = isEmbeddingCategory.value ? remoteEmbeddingModels.value : remoteChatModels.value
      return models.length ? { success: true } : { success: false, message: '未获取到可用模型' }
    } catch (e) {
      return {
        success: false,
        message: isYihaotong.value
          ? '获取模型失败，请检查一号通配置'
          : '获取模型失败，请检查密钥与请求地址'
      }
    } finally {
      isModelLoading.value = false
    }
  }

  // 同一份配置（类型 + 驱动 + 密钥 + 地址）只自动拉取一次
  const buildFetchSignature = () =>
    [form.value.config_type, currentDriver.value, form.value.api_key, form.value.base_url].join('|')

  /**
   * 密钥失焦后自动拉取模型：条件不满足（缺服务商 / 密钥 / 地址，或配置未变）时静默跳过，
   * 不打扰还在填表的用户；真正发起请求时结果与手动「获取模型」一致
   *
   * @return {Promise<{ success: Boolean, skipped?: Boolean, message?: String }>}
   */
  const autoFetchModels = async () => {
    const canFetch =
      !isModelLoading.value &&
      // 一号通不展示密钥输入框，没有失焦自动拉取的场景
      !isYihaotong.value &&
      !!form.value.api_key &&
      !!currentDriver.value &&
      (!isCustom.value || !!form.value.base_url) &&
      buildFetchSignature() !== lastFetchSignature.value
    if (!canFetch) return { success: false, skipped: true }
    return fetchModels()
  }

  /* ---------------------------------------------------------- 打开与回填 */

  const resetForm = () => {
    form.value = createForm()
    modelCategory.value = MODEL_CATEGORY.CHAT
    editId.value = ''
    remoteChatModels.value = []
    remoteEmbeddingModels.value = []
    lastFetchSignature.value = ''
  }

  // 详情字段多于表单（含 user / created_at 等），按表单 key 逐项取值，避免原样提交回去
  const fillForm = (detail) => {
    Object.keys(form.value).forEach((key) => {
      if (key === 'json') return
      if (detail[key] !== undefined && detail[key] !== null) form.value[key] = detail[key]
    })
    form.value.json = normalizeAdvancedJson(detail.json)
    if (detail.enable_thinking === undefined || detail.enable_thinking === null) {
      form.value.enable_thinking = THINKING_MODE.DEFAULT
    }
    // 一号通详情不回传密钥与地址，本地保持空即可
    if (form.value.config_type === CONFIG_TYPE.YIHAOTONG) {
      form.value.api_key = ''
      form.value.base_url = ''
      form.value.provider = yihaotongDriver.value
    }
    // 模型类别不是表单字段，单独按详情的 model_type 回填（2=向量，其余按对话）
    modelCategory.value =
      Number(detail.model_type) === MODEL_TYPE.EMBEDDING
        ? MODEL_CATEGORY.EMBEDDING
        : MODEL_CATEGORY.CHAT
  }

  const loadDetail = async (id) => {
    isDetailLoading.value = true
    try {
      const res = await getModelsInfoApi(id)
      if (res.status === SUCCESS_STATUS) fillForm(res.data || {})
    } finally {
      isDetailLoading.value = false
    }
  }

  /**
   * 打开弹窗
   *
   * @param {Object} [options]
   * @param {Number|String} [options.id] 模型 id，传入即为编辑态，字段一律取详情接口
   */
  const openForm = ({ id } = {}) => {
    resetForm()

    if (id) {
      editId.value = id
      return loadDetail(id)
    }

    applyDefaultConfigType()
    return Promise.resolve()
  }

  /* ------------------------------------------------------------------ 提交 */

  const buildPayload = () => {
    const { json, ...rest } = form.value
    const payload = {
      ...rest,
      provider: currentDriver.value,
      model_id: form.value.model_id,
      model_type: isEmbeddingCategory.value ? MODEL_TYPE.EMBEDDING : MODEL_TYPE.CHAT,
      config_type: form.value.config_type,
      // 向量模型不需要对话高级配置
      json: isEmbeddingCategory.value ? {} : compactAdvancedJson(json)
    }

    if (isYihaotong.value) {
      // 一号通密钥与地址由后端按 config_type 补全，前端绝不下发
      delete payload.api_key
      delete payload.base_url
      delete payload.is_full_url
      payload.provider = yihaotongDriver.value
      return payload
    }

    // 「完整URL」只属于自定义配置
    if (!isCustom.value) delete payload.is_full_url
    // 预设服务商新增时不下发地址，由后端按 provider 取默认值；
    // 编辑时原样回传详情值，避免覆盖掉已配置的自定义地址（如预设 deepseek 指向第三方中转）
    if (isPreset.value && !isEdit.value) delete payload.base_url
    return payload
  }

  /**
   * 连通性测试
   *
   * 提交前用当前表单配置向供应商发起一次真实请求，失败信息回传给弹窗内联展示，
   * 因此这里用 silent 关掉 request 的统一错误弹窗，避免和内联提示重复。
   *
   * @return {Promise<{ success: Boolean, message?: String }>}
   */
  const testConnection = async () => {
    try {
      const res = await testModelsApi(buildPayload(), { silent: true })
      if (res.status === SUCCESS_STATUS) return { success: true }
      return { success: false, message: res.message || '连通性测试未通过' }
    } catch (e) {
      const message = (e && e.data && e.data.message) || (typeof (e && e.message) === 'string' ? e.message : '')
      return { success: false, message: message || '连通性测试未通过，请检查密钥、请求地址与模型' }
    }
  }

  /**
   * 提交表单
   * @return {Promise<Boolean>} 是否保存成功，失败时错误提示已由 request 统一弹出
   */
  const submitForm = async () => {
    const payload = buildPayload()
    const res = isEdit.value
      ? await editModelsApi(editId.value, payload)
      : await saveModelsApi(payload)
    return res.status === SUCCESS_STATUS
  }

  /* ------------------------------------------------------------------ 校验 */

  const rules = {
    config_type: [{ required: true, message: '请选择模型类型', trigger: 'change' }],
    name: [{ required: true, message: '请输入模型名称', trigger: 'blur' }],
    provider: [
      {
        required: true,
        validator: (_rule, value, callback) => {
          // 一号通 / 自定义不展示服务商，跳过；预设必须选
          if (isYihaotong.value || isCustom.value || value) callback()
          else callback(new Error('请选择服务商'))
        },
        trigger: 'change'
      }
    ],
    base_url: [
      {
        required: true,
        validator: (_rule, value, callback) => {
          // 仅自定义配置必填请求地址
          if (!isCustom.value || value) callback()
          else callback(new Error('请输入请求地址'))
        },
        trigger: 'blur'
      }
    ],
    api_key: [
      {
        required: true,
        validator: (_rule, value, callback) => {
          // 一号通不展示密钥，跳过；其余必填
          if (isYihaotong.value || value) callback()
          else callback(new Error('请输入API密钥'))
        },
        trigger: 'blur'
      }
    ],
    model_id: [{ required: true, message: '请选择或输入模型', trigger: ['change', 'blur'] }]
  }

  return {
    form,
    rules,
    title,
    isEdit,
    isPreset,
    isCustom,
    isYihaotong,
    yihaotongEnabled,
    isYihaotongLocked,
    configTypeOptions,
    modelCategory,
    isEmbeddingCategory,
    isDetailLoading,
    isModelLoading,
    presetOptions,
    chatModelOptions,
    embeddingModelOptions,
    modelOptions,
    apiKeyDocUrl,
    hasCustomAdvanced,

    openForm,
    fetchModels,
    autoFetchModels,
    handleConfigTypeChange,
    handleDriverChange,
    handleModelChange,
    handleModelCategoryChange,
    testConnection,
    submitForm
  }
}
