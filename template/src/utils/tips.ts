import {
  ElMessage as Message,
  ElMessageBox as MessageBox,
  ElNotification as Notification,
  type MessageType,
  type NotificationParams
} from 'element-plus'

export interface ConfirmOptions {
  message: string
  title?: string
  confirmButtonText?: string
  confirmButtonClass?: string
  cancelButtonText?: string
  cancelButtonClass?: string
  type?: MessageType
}

class msgTips {
  static getInstance() {
    return new msgTips()
  }
  // 消息提示
  msg(msg: string) {
    Message.info({
      message: msg,
      duration: 5000
    })
  }

  // 错误消息
  msgError(msg: string) {
    Message.error({
      message: msg,
      duration: 5000
    })
  }

  // 成功消息（duration 可选：保存等高频轻提示可传短时长，不传保持全局默认）
  msgSuccess(msg: string, duration = 5000) {
    Message.success({
      message: msg,
      duration
    })
  }

  // 警告消息
  msgWarning(msg: string) {
    Message.warning({
      message: msg,
      duration: 5000
    })
  }

  // 弹出提示
  alert(msg: string) {
    MessageBox.alert(msg, '系统提示')
  }

  // 通知提示
  notify(msg: NotificationParams) {
    Notification.info(msg)
  }

  // 错误通知
  notifyError(msg: NotificationParams) {
    Notification.error(msg)
  }

  // 成功通知
  notifySuccess(msg: NotificationParams) {
    Notification.success(msg)
  }

  // 警告通知
  notifyWarning(msg: NotificationParams) {
    Notification.warning(msg)
  }

  // 确认窗体
  confirm(options: ConfirmOptions) {
    return new Promise<void>((resolve) => {
      MessageBox.confirm(options.message, options.title || '温馨提示', {
        confirmButtonText: options.confirmButtonText || '确定',
        confirmButtonClass: options.confirmButtonClass || '',
        cancelButtonText: options.cancelButtonText || '取消',
        cancelButtonClass: options.cancelButtonClass || '',
        type: options.type || 'warning'
      })
        .then(() => {
          resolve()
        })
        .catch(() => undefined)
    })
  }

  // 提交内容
  // prompt(content: string, title: string, options?: ElMessageBoxOptions) {
  //   return this.$prompt(content, title, {
  //     confirmButtonText: '确定',
  //     cancelButtonText: '取消',
  //     ...options
  //   })
  // }
}

const Tips = msgTips.getInstance()
export default Tips
