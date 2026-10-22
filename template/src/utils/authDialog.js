import Vue from 'vue'
import AuthDialog from '@/components/common/authDialog'

let instance = null

// 全局共用一个企微绑定弹窗实例，首次调用时才创建并挂载到 body
function getInstance() {
  if (instance) return instance
  const DialogConstructor = Vue.extend(AuthDialog)
  instance = new DialogConstructor()
  instance.$mount(document.createElement('div'))
  document.body.appendChild(instance.$el)
  return instance
}

/**
 * 打开企微绑定弹窗
 * @param {string} options 弹窗类型，透传给组件的 open 方法
 * @returns {Promise<void>} 弹窗关闭后 resolve
 */
export function openAuthDialog(options) {
  const vm = getInstance()
  return new Promise((resolve) => {
    vm.open(options)
    vm.handleClosed = () => {
      vm.visible = false
      resolve()
    }
  })
}
