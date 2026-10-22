/**
 * 业务模块路由自动聚合
 *
 * 约定：本目录下每个 .js 文件即一个业务模块的固定路由，
 * default 导出一条路由记录或一个路由记录数组。
 * 新增模块只需在本目录新建文件，无需改动 router/index.js。
 */
const context = import.meta.glob('./*.js', { eager: true })

const moduleRoutes = Object.keys(context).reduce((routes, key) => {
  if (key === './index.js') return routes
  const module = context[key]
  const route = module.default || module
  return routes.concat(route)
}, [])

export default moduleRoutes
