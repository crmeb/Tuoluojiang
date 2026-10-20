import type { Component } from 'vue'

let pending: Promise<Component> | undefined

/** 首次打开表单时加载已配置的运行时，不引入设计器的编辑面板。 */
export function loadApprovalForm(): Promise<Component> {
  if (!pending) {
    pending = import('@/views/business/components/formSetting/components/form-create-designer/src/config/viewForm')
      .then(({ default: formCreate }) => formCreate.$form())
      .catch((error: unknown) => {
        pending = undefined
        throw error
      })
  }
  return pending
}
