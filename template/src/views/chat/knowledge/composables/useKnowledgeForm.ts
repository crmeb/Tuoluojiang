import { ref, reactive, computed } from 'vue'
import { knowledgeBaseCreateApi, knowledgeBaseUpdateApi } from '@/api/knowledge'
import { DEFAULT_ICON_COLOR } from '../constants'

interface FormState {
  id: number
  name: string
  description: string
  icon: string
  color: string
  embedding_model_id: number | ''
}

/**
 * 知识库基础表单逻辑
 *
 * 创建弹窗与详情页「设置」页签用的是同一份字段与校验，
 * 差异只在于详情从哪来（弹窗自己请求 / 详情页父级下发），
 * 因此这里只暴露 applyDetail，由调用方决定详情的获取时机。
 *
 * 向量模型的选项加载与回显由 components/embeddingModelSelect.vue 自理，本文件只管字段值。
 */
export const useKnowledgeForm = () => {
  const formRef = ref<any>(null)
  const isSubmitting = ref(false)

  const generateForm = (): FormState => ({
    id: 0,
    name: '',
    description: '',
    icon: '',
    color: DEFAULT_ICON_COLOR,
    embedding_model_id: ''
  })

  const form = reactive<FormState>(generateForm())

  const isEdit = computed(() => !!form.id)

  const rules = {
    icon: [{ required: true, message: '请选择知识库图标', trigger: 'change' }],
    name: [{ required: true, message: '请输入知识库名称', trigger: 'blur' }],
    embedding_model_id: [{ required: true, message: '请选择向量模型', trigger: 'change' }]
  }

  const resetForm = () => {
    Object.assign(form, generateForm())
    if (formRef.value) formRef.value.clearValidate()
  }

  /** 用详情回填表单 */
  const applyDetail = (detail: any) => {
    if (!detail) return
    form.id = detail.id
    form.name = detail.name || ''
    form.description = detail.description || ''
    form.icon = detail.icon || ''
    form.color = detail.color || DEFAULT_ICON_COLOR
    form.embedding_model_id = detail.embedding_model_id || ''
  }

  const handleSelectIcon = (icon: string) => {
    form.icon = icon
    if (formRef.value) formRef.value.clearValidate('icon')
  }

  /** 校验并提交，返回是否保存成功 */
  const submit = (): Promise<boolean> =>
    new Promise((resolve) => {
      formRef.value.validate(async (valid: boolean) => {
        if (!valid) return resolve(false)
        isSubmitting.value = true
        try {
          const params = {
            name: form.name,
            description: form.description,
            icon: form.icon,
            color: form.color,
            embedding_model_id: form.embedding_model_id
          }
          const res = isEdit.value
            ? await knowledgeBaseUpdateApi(form.id, params)
            : await knowledgeBaseCreateApi(params)
          // request.js 对业务失败（status 400）是 resolve 而非 reject，
          // 不判一次就会「保存失败却当成功处理」，把用户填的内容一并清掉
          resolve(res.status === 200)
        } catch {
          resolve(false)
        } finally {
          isSubmitting.value = false
        }
      })
    })

  return {
    form,
    rules,
    formRef,
    isEdit,
    isSubmitting,

    resetForm,
    applyDetail,
    handleSelectIcon,
    submit
  }
}
