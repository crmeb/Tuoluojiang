import Vue from 'vue';
import Router from 'vue-router';
import { roterPre, WORKBENCH_PATH } from '@/settings';
Vue.use(Router);
/* Layout */
import Layout from '@/layout';
import { generateUniqueString } from '@/libs/helper';

// 获取views中以.vue结尾的文件；非 eager 模式下每个值都是 () => import()，
// 与 vue-router 的异步组件签名天然吻合，且各页面独立分包按需加载。
const contextInfo = import.meta.glob('../views/**/*.vue');

/**
 * 过滤/components中的vue文件
 * @returns {{}}
 */
const filteredFileNames = () => {
  const files = {};
  Object.keys(contextInfo).forEach((fileName) => {
    if (!fileName.includes('/components')) {
      // '../views/user/index.vue' => '/user/index'
      const path = fileName.slice('../views'.length, -'.vue'.length);
      files[path] = contextInfo[fileName]
    }
  });
  return files;
};
const files = filteredFileNames();

/**
 * 删除不需要配置路由
 * @param items
 * @param paths
 */
const removeItems = (items, paths) => {
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    for (let j = 0; j < paths.length; j++) {
      const path = paths[j];
      if (item.menu_path === path) {
        items.splice(i, 1);
        i--;
        break;
      } else if (item.children) {
        removeItems(item.children, paths);
        if (item.children.length === 0) {
          delete item.children;
        }
      }
    }
  }
};

/**
 * 处理平台菜单
 * @param menus
 * @param parentPath
 */
const replaceMenuPath = (menus, parentPath) => {
  menus.forEach((menu, index) => {
    if (menu.children) {
      replaceMenuPath(menu.children, menu.menu_path);
    }
    if (parentPath !== '/' &&menu.menu_path&& menu.menu_path.indexOf(parentPath) > -1) {
      menu.new_path = menu.menu_path.replace(parentPath + '/', '');
    }
    // menu.new_path = '/' + menu.component
    menu.component = '/' + menu.component;
  });
};

/**
 * 过滤路由所需要的数据
 * @param routes
 * @param firstRoute
 * @returns {*}
 */
const filterAsyncRoutes = (routes, firstRoute) => {
  return routes.map((route) => {
    const routeRecord = createRouteRecord(route, firstRoute);
    if (route.children != null && route.children && route.children.length) {
      routeRecord.children = filterAsyncRoutes(route.children, false);
    }

    return routeRecord;
  });
};

/**
 * 从其他模块进入这些 views 子目录下的页面时，自动收起二级菜单。
 * 用组件路径而不是 menu_path 判断：menu_path 由后端下发、随时可能改，
 * 组件路径则与 views/ 下的物理位置一一对应，改了前端必然白屏，不会悄悄漂移。
 * 收起动作在 @/permission 的 afterEach 里按 meta.autoCollapse 统一执行。
 */
const AUTO_COLLAPSE_VIEW_DIRS = ['/chat/'];

/**
 * 该菜单对应的页面是否需要跨模块进入时收起二级菜单
 * 注意 component 已在 replaceMenuPath 里补过前导斜杠，形如 '/chat/knowledge/index'
 * @param component
 * @returns {boolean}
 */
const shouldAutoCollapseSidebar = (component) =>
  typeof component === 'string' && AUTO_COLLAPSE_VIEW_DIRS.some((dir) => component.startsWith(dir));

/**
 * 创建一条路由记录
 * @param route
 * @param firstRoute
 * @returns {{path: (*|string), meta: {icon, title: *}, name: *}}
 */
const createRouteRecord = (route, firstRoute) => {
  const routeRecord = {
    // path: route.pid === 0 ? route.menu_path : route.new_path ? route.new_path : '/',
    path: firstRoute ? `/${route.menu_path}` : route.menu_path,
    name: route.unique_auth + '_' + generateUniqueString(),
    meta: {
      title: route.menu_name,
      icon: route.icon,
      autoCollapse: shouldAutoCollapseSidebar(route.component)
    }
  };
  if (route.pid === 0) {
    routeRecord.component = Layout;
    // 解决父级路由不能定义name属性
    delete routeRecord.name;
  } else if (route.pid > 0 && route.children && route.children.length > 0) {
    // 解决父级不写component 属性,子集的component也不会显示问题
    routeRecord.component = { render: (e) => e('router-view') };
  } else {
    routeRecord.component = files[route.component];
  }
  return routeRecord;
};

import defaultRoutes from '@/router/routes';
// 按业务模块拆分的固定路由，见 @/router/modules
import moduleRoutes from '@/router/modules';
const baseConstantRoutes = [...defaultRoutes, ...moduleRoutes];
export const constantRoutes = [...baseConstantRoutes];

// 解决强制刷新后404找不到页面逻辑
const route404 = { path: '*', redirect: roterPre + '/404', hidden: true };

function resetConstantRoutes() {
  constantRoutes.splice(0, constantRoutes.length, ...baseConstantRoutes);
}

/**
 * 根据后台菜单动态生成路由
 */
export const getRouterMenus = (entMenuList) => {
  let entRouter = [];
  resetConstantRoutes();
  if (entMenuList && entMenuList.length > 0) {
    // 工作台已由 routes.js 的静态路由承载（路由名固定为 dashboard、带固定标签），
    // 这里把后端菜单里的同名条目剔除，避免同一个页面生成第二条路由
    removeItems(entMenuList, [WORKBENCH_PATH]);
    // 处理后台返回的路由结构
    replaceMenuPath(entMenuList, '/');

    entRouter = filterAsyncRoutes(entMenuList);
    constantRoutes.push(...entRouter, route404);

    // 路由重置加载
  }
  resetRouter();
};

const createRouter = () =>
  new Router({
    mode: 'history', // require service support
    scrollBehavior: () => ({ y: 0 }),
    routes: constantRoutes
  });

const router = createRouter();

export function resetRouter() {
  const newRouter = createRouter();
  router.matcher = newRouter.matcher; // reset router
}

export default router;
