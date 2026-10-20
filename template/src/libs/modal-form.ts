import request from '@/api/request'
import type { ApiResult } from '@/types/api'
import { h, reactive } from 'vue'
import { ElMessage, ElMessageBox, type ElMessageBoxOptions } from 'element-plus'
import { getAppContext } from '@/app-context'
import { loadApprovalForm } from './approval-form'

let unique = 1
const uniqueId = () => ++unique

/** form-create 暴露的表单 api，这里只用到提交 */
interface FormCreateApi {
  submit(onSuccess: (formData: object) => void, onFail: () => void): void
}

/** 后端下发的 form-create 表单配置 */
interface ModalFormData {
  title?: string
  rule: unknown[]
  method: string
  action: string
  config?: {
    submitBtn?: boolean
    resetBtn?: boolean
    form?: Record<string, unknown>
    formData?: Record<string, unknown>
    global?: Record<string, unknown>
    [key: string]: unknown
  }
}

export interface ModalFormConfig {
  /** 额外合并进表单的初始值 */
  formData?: Record<string, unknown>
  /** 弹窗自定义类名 */
  class?: string
}

type BoxInstance = { confirmButtonLoading: boolean }
type RequestName = keyof typeof request

export default function modalForm(
  formRequestPromise: Promise<ApiResult<ModalFormData>>,
  config: ModalFormConfig = {}
): Promise<ApiResult<any>> {
  return new Promise((resolve, reject) => {
    let formApi: FormCreateApi | null = null
    formRequestPromise
      .then(async ({ data }) => {
        const FormCreate = await loadApprovalForm()
        if (!data.config) data.config = {}
        data.config.submitBtn = false
        data.config.resetBtn = false
        if (!data.config.form) data.config.form = {}
        data.config.form.labelSuffix = '：'
        data.config.form.labelWidth = '90px'
        if (!data.config.formData) data.config.formData = {}
        data.config.formData = { ...data.config.formData, ...config.formData }
        data.config.global = {
          upload: {
            props: {
              onSuccess(rep: ApiResult<{ src: string }>, file: { url?: string }) {
                if (rep.status === 200) {
                  file.url = rep.data.src
                }
              }
            }
          },
          frame: {
            props: {
              onLoad(e: { fApi?: FormCreateApi | null }) {
                e.fApi = formApi
              }
            }
          },
          inputNumber: {
            props: {
              controls: false
            }
          }
        }
        data = reactive(data)
        // mask 为历史遗留选项，Element Plus 未声明但保留原样透传
        ElMessageBox({
          title: data.title,
          showCancelButton: true,
          customClass: config.class || 'modal-form',
          mask: false,
          // 打开动态表单时才加载运行时，不依赖启动阶段的全局注册
          message: () =>
            h('div', { class: 'common-form-create', key: uniqueId() }, [
              h(FormCreate, {
                rule: data.rule,
                option: data.config,
                'onUpdate:api': ($f: FormCreateApi) => {
                  formApi = $f
                }
              })
            ]),
          beforeClose: (action: string, instance: BoxInstance, done: () => void) => {
            if (action === 'confirm') {
              if (!formApi) {
                instance.confirmButtonLoading = false
                return
              }
              instance.confirmButtonLoading = true
              formApi.submit(
                (formData) => {
                  request[data.method.toLowerCase() as RequestName](data.action.slice(4), formData)
                    .then((res) => {
                      if (res.status === 200) {
                        instance.confirmButtonLoading = false
                        formApi = null
                        done()
                        resolve(res)
                      } else {
                        instance.confirmButtonLoading = false
                        reject(res)
                      }
                    })
                    .catch((err) => {
                      instance.confirmButtonLoading = false
                      reject(err)
                    })
                },
                () => {
                  instance.confirmButtonLoading = false
                }
              )
            } else {
              instance.confirmButtonLoading = false
              formApi = null
              done()
            }
          }
        } as ElMessageBoxOptions, getAppContext())
      })
      .catch((e) => {
        ElMessage.error(e.message || '获取表单配置失败')
      })
  })
}
