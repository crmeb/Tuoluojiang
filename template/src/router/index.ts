import { h } from 'vue';
import {
  createRouter as createVueRouter,
  createWebHistory,
  RouterView,
  type RouteComponent,
  type RouteRecordRaw
} from 'vue-router';
import type { MenuItem } from '@/stores/user';
import { WORKBENCH_PATH } from '@/settings';
/* Layout */
import Layout from '@/layout/index.vue';
import { generateUniqueString } from '@/libs/helper';

// 获取views中以.vue结尾的文件；非 eager 模式下每个值都是 () => import()，
// 与 vue-router 的异步组件签名天然吻合，且各页面独立分包按需加载。
const contextInfo = import.meta.glob<RouteComponent>('../views/**/*.vue');

type LazyView = () => Promise<RouteComponent>;

/** 后端菜单项，处理过程中会补上 new_path 等字段 */
type BackendMenu = MenuItem & {
  pid?: number;
  new_path?: string;
  unique_auth?: string;
  icon?: string;
  children?: BackendMenu[];
};

/**
 * 过滤/components中的vue文件
 * @returns {{}}
 */
const filteredFileNames = () => {
  const files: Record<string, LazyView> = {};
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
const removeItems = (items: BackendMenu[], paths: string[]) => {
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
const replaceMenuPath = (menus: BackendMenu[], parentPath: string | undefined) => {
  menus.forEach((menu) => {
    if (menu.children) {
      replaceMenuPath(menu.children, menu.menu_path);
    }
    if (parentPath !== '/' &&menu.menu_path&& menu.menu_path.indexOf(parentPath as string) > -1) {
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
const filterAsyncRoutes = (routes: BackendMenu[], firstRoute?: boolean): RouteRecordRaw[] => {
  return routes.map((route) => {
    const routeRecord = createRouteRecord(route, firstRoute);
    if (route.children != null && route.children && route.children.length) {
      routeRecord.children = filterAsyncRoutes(route.children, false);
    }

    return routeRecord;
  });
};

/**
 * 创建一条路由记录
 * @param route
 * @param firstRoute
 * @returns {{path: (*|string), meta: {icon, title: *}, name: *}}
 */
const createRouteRecord = (route: BackendMenu, firstRoute?: boolean) => {
  // 先按宽松结构组装，component / children 在下方按菜单层级补齐
  const routeRecord: Partial<RouteRecordRaw> & { path: string; name?: string; children?: RouteRecordRaw[] } = {
    // path: route.pid === 0 ? route.menu_path : route.new_path ? route.new_path : '/',
    path: (firstRoute ? `/${route.menu_path}` : route.menu_path) as string,
    name: route.unique_auth + '_' + generateUniqueString(),
    meta: {
      title: route.menu_name,
      icon: route.icon
    }
  };
  if (route.pid === 0) {
    routeRecord.component = Layout;
    // 解决父级路由不能定义name属性
    delete routeRecord.name;
  } else if ((route.pid as number) > 0 && route.children && route.children.length > 0) {
    // 解决父级不写component 属性,子集的component也不会显示问题
    routeRecord.component = { render: () => h(RouterView) };
  } else {
    routeRecord.component = files[route.component as string];
  }
  return routeRecord as RouteRecordRaw;
};

import defaultRoutes from '@/router/routes';
// 按业务模块拆分的固定路由，见 @/router/modules
import moduleRoutes from '@/router/modules';
// 首屏解析发生在菜单请求之前：兜底路由必须预先存在，避免动态路由直达时出现无匹配警告。
// 不使用 redirect，否则初始地址会在权限守卫加载菜单前被改写成 404。
const route404 = { path: '/:pathMatch(.*)*', component: () => import('@/views/error-page/404.vue'), hidden: true };
const baseConstantRoutes: RouteRecordRaw[] = [...defaultRoutes, ...moduleRoutes, route404];
export const constantRoutes = [...baseConstantRoutes];

function resetConstantRoutes() {
  constantRoutes.splice(0, constantRoutes.length, ...baseConstantRoutes);
}

/**
 * 根据后台菜单动态生成路由
 */
export const getRouterMenus = (entMenuList: BackendMenu[]) => {
  let entRouter: RouteRecordRaw[] = [];
  resetConstantRoutes();
  if (entMenuList && entMenuList.length > 0) {
    // 工作台已由 routes.js 的静态路由承载（路由名固定为 dashboard、带固定标签），
    // 这里把后端菜单里的同名条目剔除，避免同一个页面生成第二条路由
    removeItems(entMenuList, [WORKBENCH_PATH]);
    // 处理后台返回的路由结构
    replaceMenuPath(entMenuList, '/');

    entRouter = filterAsyncRoutes(entMenuList);
    constantRoutes.push(...entRouter);

    // 路由重置加载
  }
  resetRouter();
};

const router = createVueRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: baseConstantRoutes
});

// 动态追加的路由各自的移除函数，重置时统一撤销
let dynamicRouteRemovers: Array<() => void> = [];

/**
 * 按 constantRoutes 的当前内容重建路由表：撤销上一轮动态路由，再追加本轮新增部分。
 * 替代 vue-router 3 的 `router.matcher = newRouter.matcher` 写法。
 */
export function resetRouter() {
  dynamicRouteRemovers.forEach((remove) => remove());
  dynamicRouteRemovers = constantRoutes.slice(baseConstantRoutes.length).map((route) => router.addRoute(route));
}

export default router;
