import { Message } from 'element-ui';
import store from '@/store';
import router, { getRouterMenus } from '@/router';
import { userMenusApi } from '@/api/public';
import { CUSTOMER_MODULE_COMPONENTS } from '@/constants/customerModules';
import { redirectIfCurrentRouteForbidden } from '@/utils/route-access';
import { roterPre } from '@/settings';

/**
 * 菜单加载与应用
 * 拉取后端菜单 → 按客户模块开关过滤 → 应用到 store 与动态路由。
 * 菜单与按钮权限不做本地持久化缓存，每次页面加载都全量拉取，
 * 保证权限与模块开关变更即时生效。
 */

const EMPTY_MENU_MESSAGE = '当前账号未分配任何菜单权限，请联系管理员';

/**
 * 后端返回空菜单（账号无任何权限）时抛出的错误
 * 调用方可通过 isEmptyMenuError 区分它与普通的接口异常
 */
class EmptyMenuError extends Error {
  constructor(message = EMPTY_MENU_MESSAGE) {
    super(message);
    this.name = 'EmptyMenuError';
  }
}

/**
 * 判断错误是否为空菜单错误
 * @param {*} error
 * @returns {boolean}
 */
export const isEmptyMenuError = (error) => Boolean(error) && error.name === 'EmptyMenuError';

function clone(data) {
  return JSON.parse(JSON.stringify(data || null));
}

/**
 * 根据组件路径获取对应的模块 key
 * @param {string} component 组件路径
 * @returns {string|null} 模块 key 或 null
 */
const getModuleKeyByComponent = (component) => {
  if (!component) return null;
  for (const [moduleKey, components] of Object.entries(CUSTOMER_MODULE_COMPONENTS)) {
    if (components.includes(component)) {
      return moduleKey;
    }
  }
  return null;
};

/**
 * 过滤菜单，移除未开启模块的菜单项
 * 采用后序遍历：先递归过滤 children，再判断当前节点
 * @param {Array} menus 菜单列表
 * @returns {Array} 过滤后的菜单列表
 */
const filterMenusByModule = (menus) => {
  // 配置缺失时（如 common/config 异常）不做过滤，避免误删全部客户模块菜单
  if (!store.getters['appConfig/configData']) {
    return menus;
  }

  const isCustomerModuleEnabled = store.getters['appConfig/isCustomerModuleEnabled'];

  const filterMenu = (menuList) => {
    return menuList
      .map((menu) => {
        // 先递归过滤 children
        if (menu.children && menu.children.length > 0) {
          const filteredChildren = filterMenu(menu.children);
          return { ...menu, children: filteredChildren };
        }
        return { ...menu, children: [] };
      })
      .filter((menu) => {
        // 判断条件一：若 menu.component 非空，检查是否属于某个模块且该模块未开启
        if (menu.component) {
          const moduleKey = getModuleKeyByComponent(menu.component);
          if (moduleKey && !isCustomerModuleEnabled(moduleKey)) {
            return false;
          }
        }
        // 判断条件二：若 menu.component 为空（纯菜单节点）且过滤后 children.length === 0，则移除
        if (!menu.component && (!menu.children || menu.children.length === 0)) {
          return false;
        }
        return true;
      });
  };

  return filterMenu(clone(menus));
};

/**
 * 将菜单与按钮权限应用到 store 和动态路由
 * @param {Array} menu 过滤后的菜单
 * @param {Array} permissions 按钮权限
 */
const applyMenuState = (menu, permissions = []) => {
  store.commit('user/SET_MENU_LIST', clone(menu) || []);
  store.commit('user/SET_PERMISSIONS', permissions || []);
  getRouterMenus(clone(menu) || []);
};

function fetchAppConfig() {
  // 强制刷新，保证菜单过滤基于最新的模块开关
  return store.dispatch('appConfig/fetchConfig', true)
    .catch(() => {
      console.warn('[appConfig] 获取应用配置失败，沿用本地已有配置');
      return null;
    });
}

let emptyMenuHandling = false;

/**
 * 账号无任何菜单权限时的统一善后处理
 * 清除登录态并提示；登录成功后与刷新页面时都可能走到这里，
 * 所以处理放在此处而非某个页面，避免各调用方重复实现。
 * @param {boolean} redirect 是否主动跳转登录页（路由守卫内部应传 false，由守卫自行 next）
 */
async function handleEmptyMenu(redirect, message = EMPTY_MENU_MESSAGE) {
  // 并发调用（守卫 + 跨标签页同步）时只提示一次
  if (emptyMenuHandling) {
    return;
  }
  emptyMenuHandling = true;
  try {
    await store.dispatch('user/resetToken');
    Message.closeAll();
    Message.error(message);
    const loginPath = `${roterPre}/login`;
    if (redirect && router.currentRoute.path !== loginPath) {
      router.replace(loginPath).catch(() => {});
    }
  } finally {
    emptyMenuHandling = false;
  }
}

/**
 * 获取菜单与按钮权限
 * 配置与菜单并行请求，避免串行等待
 * @param {boolean} checkCurrentRoute 拉取后是否校验当前路由仍可访问
 * @param {boolean} redirectOnEmpty 菜单为空时是否由本方法跳转登录页
 * @throws {EmptyMenuError} 后端返回空菜单时抛出，登录态已在抛出前清除
 */
export const getMenus = ({ checkCurrentRoute = false, redirectOnEmpty = true } = {}) => {
  return Promise.all([fetchAppConfig(), userMenusApi()])
    .then(async ([, response]) => {
      const { menu: rawMenu, roles } = response.data;
      // 后端菜单为空视为该账号无任何权限，需清除登录态
      // 注意：以后端原始菜单判空，模块开关过滤后为空属于配置问题，不应踢出登录
      if (!Array.isArray(rawMenu) || rawMenu.length === 0) {
        await handleEmptyMenu(redirectOnEmpty, response.message);
        throw new EmptyMenuError(response.message);
      }
      // 根据模块配置过滤菜单，roles 为按钮权限
      applyMenuState(filterMenusByModule(rawMenu), roles);
      if (checkCurrentRoute) {
        redirectIfCurrentRouteForbidden();
      }
    });
};
