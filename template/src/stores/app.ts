import { defineStore } from 'pinia'
import { reactive, ref } from 'vue'
import Cookies from 'js-cookie'
import { getStorageJson } from '@/utils/storage'

export type DeviceType = 'desktop' | 'mobile'

/** 当前选中的一级菜单下标；历史数据可能是带 13 位前缀的字符串，读取方自行截取 */
export type SidebarParentCur = number | string | null

const SIDEBAR_STATUS_KEY = 'sidebarStatus'
const PARENT_CUR_KEY = 'sidebarParentCur'

/** 布局状态：二级侧边栏开合、设备类型、当前一级菜单 */
export const useAppStore = defineStore('app', () => {
  const sidebarStatus = Cookies.get(SIDEBAR_STATUS_KEY)
  const sidebar = reactive({
    opened: sidebarStatus ? !!+sidebarStatus : true,
    withoutAnimation: false
  })
  const device = ref<DeviceType>('desktop')
  const sidebarParentCur = ref<SidebarParentCur>(getStorageJson(PARENT_CUR_KEY, null))
  // 顶部一级菜单点击切换时置为 true，二级侧边栏据此跳到该模块的第一个菜单
  const isClickTab = ref(false)

  function openSideBar() {
    Cookies.set(SIDEBAR_STATUS_KEY, '1')
    sidebar.opened = true
  }

  function closeSideBar(withoutAnimation = false) {
    Cookies.set(SIDEBAR_STATUS_KEY, '0')
    sidebar.opened = false
    sidebar.withoutAnimation = withoutAnimation
  }

  function toggleDevice(value: DeviceType) {
    device.value = value
  }

  function setSidebarParentCur(index: SidebarParentCur) {
    sidebarParentCur.value = index
    localStorage.setItem(PARENT_CUR_KEY, JSON.stringify(index))
  }

  function setClickTab(status: boolean) {
    isClickTab.value = status
  }

  return {
    sidebar,
    device,
    sidebarParentCur,
    isClickTab,
    openSideBar,
    closeSideBar,
    toggleDevice,
    setSidebarParentCur,
    setClickTab
  }
})
