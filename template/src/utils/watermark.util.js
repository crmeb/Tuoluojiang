import warterMark from '@/libs/waterMark'

let isWatermarkRegistered = false

const getWaterMarkText = (store) => {
  const userInfo = store.getters.userInfo
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

export function setupWatermark(router, store) {
  if (isWatermarkRegistered) {
    return
  }
  isWatermarkRegistered = true

  router.afterEach((item) => {
    const name = getWaterMarkText(store)
    if (item.path !== '/admin/login' && localStorage.getItem('isWebConfig') === '1') {
      warterMark.set(name, name)
    } else {
      warterMark.set(name, name, 'close')
    }
  })
}
