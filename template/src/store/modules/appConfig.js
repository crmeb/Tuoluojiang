import { appConfigApi } from '@/api/public';
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules';
import { processResourceData } from '@/utils/resourceUtil';
import { getStorageJson, setStorageJson } from '@/utils/storage';

/**
 * 全局应用配置 Vuex 模块
 * 统一管理 appConfigApi (common/config) 的数据，避免多处重复请求
 * 同时提供客户模块开关的派生 getter（原 customerModule 的职责）
 *
 * 使用方式：
 *   获取数据：store.getters['appConfig/configData']
 *   判断模块开关：store.getters['appConfig/isCustomerModuleEnabled']('leads')
 *   主动拉取：store.dispatch('appConfig/fetchConfig')
 *   强制刷新：store.dispatch('appConfig/fetchConfig', true)
 */

let fetchTask = null; // 进行中的请求 Promise，用于去重
const CACHE_KEY = 'webConfigCache';
const CACHE_TTL = 10 * 60 * 1000;

function persistConfig(data) {
  localStorage.setItem('webConfig', JSON.stringify(data));
  localStorage.setItem('isWebConfig', data.global_watermark_status);
}

// API 返回的 key 映射到 CUSTOMER_MODULE_KEYS
const API_KEY_TO_MODULE_KEY = {
  lead_module_switch: CUSTOMER_MODULE_KEYS.LEADS,
  customer_module_switch: CUSTOMER_MODULE_KEYS.CUSTOMER,
  opportunity_module_switch: CUSTOMER_MODULE_KEYS.OPPORTUNITY,
  contract_module_switch: CUSTOMER_MODULE_KEYS.CONTRACT,
  order_module_switch: CUSTOMER_MODULE_KEYS.ORDER,
  invoice_module_switch: CUSTOMER_MODULE_KEYS.INVOICE,
  liaison_module_switch: CUSTOMER_MODULE_KEYS.LIAISON,
};

const state = {
  /** @type {Object|null} appConfigApi 返回的完整 data */
  configData: getStorageJson('webConfig', null),
};

const getters = {
  /** 完整配置对象 */
  configData: (state) => state.configData,

  /** 客户相关模块启用信息 map */
  customerModuleEnableMap: state => {
    const customerSwitch = (state.configData && state.configData.customer_switch) || {};

    return Object.entries(API_KEY_TO_MODULE_KEY)
      .reduce((map, [apiKey, mappedKey]) => {
        map[mappedKey] = Boolean(customerSwitch[apiKey]);
        return map;
      }, {});
  },

  /**
   * 判断客户模块是否启用（从 configData.customer_switch 实时派生）
   * @returns {function(string): boolean}
   */
  isCustomerModuleEnabled: (state, getters) => (moduleKey) => {
    const map = getters.customerModuleEnableMap;
    return moduleKey in map ? map[moduleKey] : false;
  },
};

const mutations = {
  SET_CONFIG_DATA(state, data) {
    state.configData = processResourceData(data);
  },
  RESET_CONFIG_DATA(state) {
    state.configData = null;
  },
};

const actions = {
  /**
   * 获取应用配置（带缓存 & 请求去重）
   * @param {boolean} force - 是否强制刷新（忽略缓存）
   * @returns {Promise<Object>} 配置数据
   */
  fetchConfig({ commit, state }, force = false) {
    // 已有缓存且非强制刷新 → 直接返回
    if (!force && state.configData) {
      return Promise.resolve(state.configData);
    }

    const cache = getStorageJson(CACHE_KEY);
    if (!force && cache && cache.cachedAt && Date.now() - cache.cachedAt <= CACHE_TTL && cache.data) {
      const data = processResourceData(cache.data);
      commit('SET_CONFIG_DATA', data);
      persistConfig(data);
      return Promise.resolve(data);
    }

    // 非强制刷新且有正在进行中的请求 → 复用，避免并发重复
    // 强制刷新时不复用旧请求，确保拿到最新数据
    if (!force && fetchTask) {
      return fetchTask;
    }

    fetchTask = appConfigApi()
      .then((res) => {
        const data = processResourceData(res.data || {});
        commit('SET_CONFIG_DATA', data);

        // 同步写入 localStorage，兼容现有读取 localStorage 的逻辑
        persistConfig(data);
        setStorageJson(CACHE_KEY, {
          data,
          cachedAt: Date.now(),
        });

        return data;
      })
      .finally(() => {
        fetchTask = null;
      });

    return fetchTask;
  },
  resetConfig({ commit }) {
    fetchTask = null;
    commit('RESET_CONFIG_DATA');
  },
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions,
};
