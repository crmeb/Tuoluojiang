import { warehouseAccessibleApi, warehouseListApi } from '@/api/inventory'

/**
 * 仓储模块：集中维护仓库列表缓存，分「可查看仓库」与「全量仓库」两份数据源。
 *
 * - accessibleList：当前登录人可查看的仓库及其业务动作权限，由 accessible 接口一次性
 *   拉取全量（后端不分页）。单据类场景（入库 / 出库 / 调拨 / 盘点）用它。
 * - allList：全量仓库（含已关闭、当前登录人无权限的仓库），走分页列表接口取一页。
 *   仅用于「给仓库配权限」的场景（如库管配置）：accessible 只返回已有权限的仓库，
 *   用它做选项会导致无权限的仓库既选不到、编辑回显也匹配不到 option。
 *   两份数据不可互相混用——权限过滤只对 accessibleList 有意义。
 * - 组件里优先通过 composables/useWarehouse 使用，不直接读 state。
 * - 权限标识统一取 @/views/inventory/constants 的 WAREHOUSE_PERMISSION。
 */

// 全量仓库走的是分页列表接口，仓库总量有限，取足够大的一页即可覆盖
const ALL_LIST_LIMIT = 9999

// 后端权限值可能为 1 / '1' / true，统一归一化为布尔
const toBool = (val) => val === true || val === 1 || val === '1'

// 归一化权限入参：字符串 / 数组均可，过滤空值
const normalizePermissions = (permissions) =>
  (Array.isArray(permissions) ? permissions : [permissions]).filter(Boolean)

const state = {
  accessibleList: [], // 可查看仓库原始列表
  loaded: false, // 是否已成功加载过（用于缓存，避免重复请求）
  loading: false, // 加载中
  allList: [], // 全量仓库原始列表（不含权限信息，含已关闭仓库）
  allLoaded: false,
  allLoading: false
}

const mutations = {
  SET_ACCESSIBLE_LIST: (state, list) => {
    state.accessibleList = Array.isArray(list) ? list : []
    state.loaded = true
  },
  SET_LOADING: (state, loading) => {
    state.loading = loading
  },
  SET_ALL_LIST: (state, list) => {
    state.allList = Array.isArray(list) ? list : []
    state.allLoaded = true
  },
  SET_ALL_LOADING: (state, loading) => {
    state.allLoading = loading
  },
  RESET: (state) => {
    state.accessibleList = []
    state.loaded = false
    state.loading = false
    state.allList = []
    state.allLoaded = false
    state.allLoading = false
  }
}

// 进行中的加载 Promise：并发调用（如列表页每行的仓库权限指令各自触发）复用同一请求，避免重复打接口
let pendingLoad = null
// 全量列表的进行中 Promise：作用同上（如库管配置里多行仓库下拉同时初始化）
let pendingAllLoad = null

const actions = {
  /**
   * 加载可查看仓库列表；默认带缓存，force=true 时强制刷新
   * @returns {Promise<Array>} 最新的可查看仓库列表
   */
  async loadAccessible({ commit, state }, { force = false } = {}) {
    if (state.loaded && !force) return state.accessibleList
    if (pendingLoad && !force) return pendingLoad
    commit('SET_LOADING', true)
    const load = warehouseAccessibleApi()
      .then((res) => {
        commit('SET_ACCESSIBLE_LIST', res?.data || [])
        return state.accessibleList
      })
      .finally(() => {
        if (pendingLoad === load) pendingLoad = null
        commit('SET_LOADING', false)
      })
    pendingLoad = load
    return load
  },
  // 强制刷新（如新增 / 编辑仓库、变更库管权限后调用）
  refreshAccessible({ dispatch }) {
    return dispatch('loadAccessible', { force: true })
  },
  /**
   * 加载全量仓库列表；默认带缓存，force=true 时强制刷新
   * @returns {Promise<Array>} 最新的全量仓库列表
   */
  loadAll({ commit, state }, { force = false } = {}) {
    if (state.allLoaded && !force) return Promise.resolve(state.allList)
    if (pendingAllLoad && !force) return pendingAllLoad
    commit('SET_ALL_LOADING', true)
    const load = warehouseListApi({ page: 1, limit: ALL_LIST_LIMIT })
      .then((res) => {
        commit('SET_ALL_LIST', res?.data?.list || [])
        return state.allList
      })
      // 失败静默（错误提示由 request 统一处理）：allLoaded 保持 false，下次调用可重试。
      // 消费方多为「下拉展开时加载」，不吞掉 reject 会产生 unhandled rejection
      .catch(() => state.allList)
      .finally(() => {
        if (pendingAllLoad === load) pendingAllLoad = null
        commit('SET_ALL_LOADING', false)
      })
    pendingAllLoad = load
    return load
  },
  // 强制刷新全量仓库（新增 / 编辑 / 删除仓库后调用）
  refreshAll({ dispatch }) {
    return dispatch('loadAll', { force: true })
  },
  // 重置（退出登录 / 切换企业时调用）
  reset({ commit }) {
    commit('RESET')
  }
}

const getters = {
  // 按权限过滤仓库：传单个权限或权限列表（数组），仅返回「同时具备列表内所有权限」的仓库；
  // 为空则不校验，返回全部可查看仓库。
  warehousesByPermission: (state) => (permissions) => {
    const keys = normalizePermissions(permissions)
    if (!keys.length) return state.accessibleList;
    return state.accessibleList.filter((item) => keys.every((key) => toBool(item.permissions[key])))
  },
  // 全量仓库列表（不做任何权限 / 状态过滤）
  allWarehouses: (state) => state.allList,
  // 全量仓库 id -> 仓库信息映射：按 ID 补全仓库名称等展示信息时 O(1) 查询
  warehouseMap: (state) =>
    state.allList.reduce((map, item) => {
      map[item.id] = item
      return map
    }, {})
}

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}
