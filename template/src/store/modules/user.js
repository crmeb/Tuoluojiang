import { login, phoneLogin, logout, userScanStatusApi, refreshTokenApi, loginInfo } from '@/api/user'
import { frameUserApi,frameTreeApi } from '@/api/public'
import { getDictTreeListApi } from '@/api/form'
import router, { resetRouter } from '@/router'
import { AiEmbeddedManager } from '@/libs/ai'
import { processResourceData } from '@/utils/resourceUtil'
import { EventBus } from '@/libs/bus'

function getStorageJson(key, fallback) {
  try {
    var raw = localStorage.getItem(key)
    return raw ? processResourceData(JSON.parse(raw)) : fallback
  } catch (e) {
    return fallback
  }
}

const state = {
  token: localStorage.getItem('token') || '',
  refreshToken: localStorage.getItem('refresh_token') || '',
  name: '',
  avatar: '',
  introduction: '',
  unique:'', // 调查问卷判断是否登录
  roles: [],
  menuList: [],
  menuAuthor: [],
  activeField:{}, // 低代码详情编辑，当前编辑的字段
  formDicList: [], // 字典列表-就不用重复请求接口
  departmentList: [], // 部门列表-就不用重复请求接口
  memberList: [], // 人员列表-就不用重复请求接口
  isLogin: false,
  userInfo: getStorageJson('userInfo', {}),
  enterprise: getStorageJson('enterprise', {}),
  messageCount: 0,
  permissions: []
}
const mutations = {
  SET_MENU_LIST: (state, menuList) => {
    state.menuList = menuList
  },
  SET_PERMISSIONS: (state, permissions) => {
    state.permissions = permissions
  },
  SET_MENU_AUTHOR: (state, menuAuthor) => {
    state.menuAuthor = menuAuthor
  },
  SET_FIELD: (state, activeField) => {
    state.activeField = activeField
  },
  SET_TOKEN: (state, token) => {
    state.token = token
    localStorage.setItem('token', token)
  },
  SET_REFRESH_TOKEN: (state, refreshToken) => {
    state.refreshToken = refreshToken
    localStorage.setItem('refresh_token', refreshToken)
  },
  SET_UNIQUE: (state, unique) => {
    state.unique = unique
    localStorage.setItem('unique', unique)
  },
  SET_ISLOGIN: (state, isLogin) => {
    state.isLogin = isLogin
  },
  SET_INTRODUCTION: (state, introduction) => {
    state.introduction = introduction
  },
  SET_NAME: (state, name) => {
    state.name = name
  },
  // 修改字典列表数据
  SET_FORMDIC: (state, data) => {
    state.formDicList.push(data)
  },
  // 修改部门列表数据
  SET_DEPARTMENT: (state, data) => {
    state.departmentList=data
  },
  // 修改人员列表数据
  SET_MEMBER: (state, data) => {
    state.memberList = data
  },
  // 重置字典列表数据
  REMOVE_FORMDIC: (state, data) => {
    state.formDicList = data
  },
  SET_AVATAR: (state, avatar) => {
    state.avatar = avatar
  },
  SET_ROLES: (state, roles) => {
    state.roles = roles
  },
  SET_USERINFO: (state, data) => {
    state.userInfo = processResourceData(data)
    if (data) {
      localStorage.setItem('userInfo', JSON.stringify(state.userInfo))
    } else {
      localStorage.removeItem('userInfo')
    }
  },
  SET_ENTINFO: (state, data) => {
    state.enterprise = processResourceData(data)
    if (data) {
      localStorage.setItem('enterprise', JSON.stringify(state.enterprise))
    } else {
      localStorage.removeItem('enterprise')
    }
  },
  SET_MESSAGE: (state, count) => {
    state.messageCount = count
  }
}

function clearAuthStorage(enterpriseCache) {
  localStorage.clear()
  if (enterpriseCache) {
    localStorage.setItem('enterprise', enterpriseCache)
  }
  window.sessionStorage.clear()
}

function resetUserState(commit) {
  const enterpriseCache = localStorage.getItem('enterprise')
  commit('SET_TOKEN', '')
  commit('SET_REFRESH_TOKEN', '')
  commit('SET_ROLES', [])
  commit('SET_MENU_LIST', [])
  commit('SET_MENU_AUTHOR', [])
  commit('SET_PERMISSIONS', [])
  commit('SET_USERINFO', {})
  commit('SET_UNIQUE', '')
  commit('SET_ISLOGIN', false)
  commit('SET_MESSAGE', 0)
  commit('SET_FIELD', {})
  commit('REMOVE_FORMDIC', [])
  commit('SET_DEPARTMENT', [])
  commit('SET_MEMBER', [])
  clearAuthStorage(enterpriseCache)
}

const actions = {
  // 获取自定义表单关联字典数据
  getDictList({ commit, state }, val) {
    return new Promise(async (resolve, reject) => {
      let is_next = state.formDicList.some((item) => item.dict_ident == val.types)
      if (!state.formDicList || !is_next) {
        const response = await getDictTreeListApi(val)
        await commit('SET_FORMDIC', { dict_ident: val.types, list: response.data })
        await resolve(state.formDicList)
      }
      resolve(state.formDicList)
    })
  },

  // 获取人员数据
  async getMember({ commit, state }, options = {}) {
    if (!options.force && state.memberList && state.memberList.length) {
      return state.memberList
    }

    const data = {
      role: 0,
      leave: 0
    }
    const response = await frameUserApi(data)
    const list = response.data && response.data[0] && response.data[0].children ? response.data[0].children : response.data || []
    commit('SET_MEMBER', list)
    return state.memberList
  },
  // 获取部门数据
  async getDepartment({ commit, state }, options = {}) {
    if (!options.force && state.departmentList && state.departmentList.length) {
      return state.departmentList
    }

    const response = await frameTreeApi()
    commit('SET_DEPARTMENT', response.data || [])
    return state.departmentList
  },

  async login({ commit }, data) {
    const loginApi = data.activeName === 'passwordLogin' ? login : phoneLogin
    const response = await loginApi(data.userInfo)
    // 业务校验失败（如验证码错误）时请求层已弹出提示且不会返回 token，此处不能写入登录态
    if (!response.data || !response.data.token) {
      throw new Error(response.message || '登录失败')
    }
    commit('SET_TOKEN', response.data.token)
    commit('SET_REFRESH_TOKEN', response.data.refresh_token || '')
    commit('SET_ISLOGIN', true)
    return response.data
  },
  scanLogin({ commit }, data) {
    return new Promise(async (resolve, reject) => {
      const response = await userScanStatusApi(data)
      if (response.data.status === undefined) {
        commit('SET_TOKEN', response.data.token)
        commit('SET_REFRESH_TOKEN', response.data.refresh_token || '')
        commit('SET_ISLOGIN', true)
      }
      resolve(response.data)
    })
  },
  async refreshToken({ commit, state, dispatch }) {
    if (!state.refreshToken) {
      throw new Error('缺少刷新TOKEN')
    }
    const response = await refreshTokenApi({ refresh_token: state.refreshToken })
    if (!response.data || !response.data.token || !response.data.refresh_token) {
      throw new Error(response.message || '登录状态已失效')
    }
    commit('SET_TOKEN', response.data.token)
    commit('SET_REFRESH_TOKEN', response.data.refresh_token || '')
    commit('SET_ISLOGIN', true)
    EventBus.$emit('auth-token-updated', response.data.token)
    // 续期后顺带刷新用户信息，不阻塞原请求重试；失败不影响登录态
    dispatch('fetchUserInfo').catch(() => {})
    return response.data
  },

  /**
   * 把用户与企业信息写入 store 与本地缓存
   * @param {Object} data 接口返回的 data，含 userInfo、enterprise
   */
  setUserSession({ commit }, data = {}) {
    if (data.userInfo) {
      commit('SET_USERINFO', data.userInfo)
    }
    if (data.enterprise) {
      commit('SET_ENTINFO', data.enterprise)
    }
  },

  /**
   * 拉取用户与企业信息
   * @param {boolean} [apply=true] 是否立即写入 store；登录流程需等菜单校验通过后再写入，可传 false 自行接管写入时机
   * @returns {Promise<Object>} 接口返回的 data，含 userInfo、enterprise
   */
  async fetchUserInfo({ dispatch }, { apply = true } = {}) {
    const response = await loginInfo()
    const data = (response && response.data) || {}
    if (apply) {
      dispatch('setUserSession', data)
    }
    return data
  },
  // get user info
  getInfo({ commit, state }) {
    return new Promise((resolve, reject) => {
      getInfo(state.token)
        .then((response) => {
          const { data } = response

          if (!data) {
            reject('Verification failed, please Login again.')
          }

          const { roles, name, avatar, introduction } = data

          // roles must be a non-empty array
          if (!roles || roles.length <= 0) {
            reject('getInfo: roles must be a non-null array!')
          }

          commit('SET_ROLES', roles)
          commit('SET_NAME', name)
          commit('SET_AVATAR', avatar)
          commit('SET_INTRODUCTION', introduction)
          resolve(data)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
  // user logout
  logout({ commit, state, dispatch }) {
    return new Promise((resolve) => {
      logout(state.token)
        .catch(() => {})
        .then(() => {
          resetUserState(commit)
          dispatch('appConfig/resetConfig', null, { root: true })
          resetRouter()
          AiEmbeddedManager.destroyAll()
          resolve()
        })
    })
  },
  // remove token
  resetToken({ commit, dispatch }) {
    return new Promise((resolve) => {
      resetUserState(commit)
      dispatch('appConfig/resetConfig', null, { root: true })
      AiEmbeddedManager.destroyAll()
      resolve()
    })
  },

  // dynamically modify permissions
  changeRoles({ commit, dispatch }, role) {
    return new Promise(async (resolve) => {
      const token = role + '-token'
      commit('SET_TOKEN', token)
      const { roles } = await dispatch('getInfo')
      resetRouter()
      const accessRoutes = await dispatch('permission/generateRoutes', roles, { root: true })
      router.addRoutes(accessRoutes)

      resolve()
    })
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions
}
