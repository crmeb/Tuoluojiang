import { ref } from 'vue'

export const routeLoading = ref(false)

let loadingTimer: ReturnType<typeof setTimeout> | undefined

export function startRouteLoading() {
  clearTimeout(loadingTimer)
  routeLoading.value = false
  // 快速切页不闪烁；慢网络下给仍留在原页面的内容区明确反馈。
  loadingTimer = setTimeout(() => {
    routeLoading.value = true
    loadingTimer = undefined
  }, 200)
}

export function finishRouteLoading() {
  clearTimeout(loadingTimer)
  loadingTimer = undefined
  routeLoading.value = false
}
