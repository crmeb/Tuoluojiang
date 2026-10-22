// 路由前缀
export const roterPre = '/admin';

/**
 * 工作台路径，必须与后端菜单里工作台的 menu_path 保持一致。
 * 后端菜单同样会为工作台生成一条动态路由，@/router 的 getRouterMenus 按这个值把它
 * 剔除，否则同一个页面会有两条路由、两个标签。
 * 放在这里而非 @/router/routes：本文件零依赖，路由兜底逻辑引用它不会新增依赖边。
 */
export const WORKBENCH_PATH = `${roterPre}/user/workbench/index`;

// 路由标题
export const title = '陀螺匠OA系统';

/**
 * @type {boolean} true | false
 * @description Whether fix the header
 */
export const fixedHeader = true;

/**
 * @type {boolean} true | false
 * @description Whether show the logo in sidebar
 */
export const sidebarLogo = true;

/** 显示右侧帮助
 * @type {boolean} true | false
 * @description Whether show the logo in sidebar
 */
export const helpShow = true;

/** 显示论坛
 * @type {boolean} true | false
 * @description Whether show the logo in sidebar
 */
export const bbsShow = true;

/** 显示知识社区
 * @type {boolean} true | false
 * @description Whether show the logo in sidebar
 */
export const forumShow = true;

const settings = {
  roterPre,
  WORKBENCH_PATH,
  title,
  fixedHeader,
  sidebarLogo,
  helpShow,
  bbsShow,
  forumShow,
};

export default settings;
