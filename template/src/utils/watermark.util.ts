import type { Router } from 'vue-router'
import warterMark from '@/libs/waterMark'
import pinia from '@/stores'
import { useUserStore } from '@/stores/user'

let isWatermarkRegistered = false

const getWaterMarkText = () => {
  const userInfo = useUserStore(pinia).userInfo
  let name = ''
  if (userInfo && userInfo.name) {
    if (userInfo.job) {
      name = userInfo.name + '' + '(' + userInfo.job.name + ')'
    } else {
      name = userInfo.name
    }
  }

  return name
}

export function setupWatermark(router: Router) {
  if (isWatermarkRegistered) {
    return
  }
  isWatermarkRegistered = true

  router.afterEach((item) => {
    const name = getWaterMarkText()
    if (item.path !== '/admin/login' && localStorage.getItem('isWebConfig') === '1') {
      warterMark.set(name, name)
    } else {
      warterMark.set(name, name, 'close')
    }
  })
}
