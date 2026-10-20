import uploadFromComponent from './index.vue'
import { mountComponent } from '@/app-context'

/** 图片管理弹窗对外可写的状态 */
interface UploadFromInstance {
  title: string
  visible: boolean
  callback: (data: any) => void
  isMore: boolean
}

let instance: UploadFromInstance | null = null

// 全局共用一个图片管理弹窗实例，首次调用时才创建并挂载到 body
function getInstance() {
  if (instance) return instance
  instance = mountComponent<UploadFromInstance>(uploadFromComponent).vm
  return instance
}

/**
 * 打开图片管理弹窗
 * @param {Function} callback 选中图片后的回调，入参为图片数据
 * @param {boolean} isMore 是否多选
 * @param {object} options 额外配置，支持 title 自定义弹窗标题
 */
export function modalUpload(callback: (data: any) => void, isMore: boolean, options: { title?: string } = {}) {
  const vm = getInstance()
  vm.title = options.title || '图片管理'
  vm.visible = true
  vm.callback = callback
  vm.isMore = isMore
}

export default modalUpload
