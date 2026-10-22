import { MessageBox } from 'element-ui'

/**
 * 知识库模块的删除二次确认
 *
 * 设计稿的确认框没有警告图标，用不了带 type: 'warning' 的 modalSure，
 * 样式统一走 styles/confirm.scss 里的 knowledge-delete-confirm。
 *
 * @returns 用户是否点了确认
 */
export const confirmDelete = async (title: string, message: string, confirmText = '删除') => {
  try {
    await MessageBox.confirm(message, title, {
      confirmButtonText: confirmText,
      cancelButtonText: '取消',
      customClass: 'knowledge-delete-confirm'
    })
    return true
  } catch {
    return false
  }
}
