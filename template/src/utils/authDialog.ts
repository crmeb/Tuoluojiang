import AuthDialog from '@/components/common/authDialog.vue'
import { mountComponent } from '@/app-context'

interface AuthDialogInstance {
  visible: boolean
  open: (options?: string) => void
  handleClosed: () => void
}

let instance: AuthDialogInstance | null = null

// 全局共用一个企微绑定弹窗实例，首次调用时才创建并挂载到 body
function getInstance() {
  if (instance) return instance
  instance = mountComponent<AuthDialogInstance>(AuthDialog).vm
  return instance
}

/**
 * 打开企微绑定弹窗
 * @param {string} options 弹窗类型，透传给组件的 open 方法
 * @returns {Promise<void>} 弹窗关闭后 resolve
 */
export function openAuthDialog(options?: string) {
  const vm = getInstance()
  return new Promise<void>((resolve) => {
    vm.open(options)
    vm.handleClosed = () => {
      vm.visible = false
      resolve()
    }
  })
}
