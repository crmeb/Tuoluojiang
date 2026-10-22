---
name: create-route
description: 在 src/router/ 添加前端路由（通用页进 routes.js，按业务模块拆分的进 src/router/modules/），自动套用 roterPre 前缀、Layout 包裹、懒加载 component 与 meta 配置。当用户说"加一个路由/页面入口"、"注册路由"、"新增菜单页"时触发。
---

# create-route

为本项目（Vue Router 3）追加路由配置，确保与现有静态路由一致：使用 `roterPre` 前缀、懒加载、必要时包裹 Layout、设置 `meta.title`。

## 项目路由架构（必须理解）

1. **两类路由**：
   - **静态路由**：`src/router/routes.js` + `src/router/modules/*.js`，编译期注册。
   - **动态路由**：后端菜单接口返回，由 `src/router/index.js` 的 `getRouterMenus()` 自动把 `src/views/**` 下的 `.vue` 映射成组件（通过 `require.context('../views', true, /.vue$/, "lazy")`，不含 `/components`）。
   - **这条 skill 只处理静态路由**。动态路由由后台菜单驱动，前端只需保证 `src/views/<path>.vue` 存在即可被自动注入。
2. **路径前缀**：所有可访问页面必须以 `roterPre`（`/admin`）开头，引入方式 `import { roterPre } from '@/settings'`。
3. **Layout 包裹**：需要显示侧边栏/顶部栏的业务页，必须挂在父级 `{ path: roterPre, component: Layout, children: [...] }` 节点下；登录、分享、错误页等独立页面不挂 Layout，并设 `hidden: true`。
4. **组件懒加载**：`component: () => import('@/views/.../index')`，**不要**静态 import。
5. **meta 字段**：
   - `title`: 中文标题，会显示在面包屑
   - `icon`: 可选 iconfont 名
6. **name 唯一**：name 用英文小驼峰，全局唯一；动态路由由 `unique_auth + generateUniqueString()` 生成，静态路由请避开冲突。
7. **业务模块目录 `src/router/modules/`**：`modules/index.js` 通过 `require.context('./', false, /\.js$/)` 自动聚合本目录下所有 `.js`（跳过自身），在 `src/router/index.js` 中并入 `baseConstantRoutes`。**新增业务模块只需新建文件，禁止改动 `router/index.js`**。每个文件 default 导出一条路由记录或路由记录数组。
8. **二级页的菜单归属**：侧边栏与面包屑都以后端 `menuList` 为数据源（`Sidebar/index.vue`、`Header/breadcrumb.vue`）。新增/编辑/详情等不在后台菜单里的二级页，**必须**设 `meta.activeMenu` 指向所属列表页的完整路径，否则菜单不高亮、面包屑为空。

## 文件选择（先判定）

- **通用/零散页**（登录、错误页、预览、工具页等与业务域无关的页面）→ `src/router/routes.js`
- **业务模块路由**（能归到某个业务域，如客户、库存、项目、人事、开发者中心）→ `src/router/modules/<module>.js`，用 Layout 父节点包住 children
- 已有同名模块文件时（当前已有 `customer` / `develop` / `hr` / `inventory` / `program` / `setting` / `user`），直接往该文件的 `children` 里追加，不要另起文件
- 模块文件可 default 导出单条路由记录，也可导出数组（如 `setting.js`、`develop.js` 中不包 Layout 的全屏页与 Layout 组并列）

## 工作流程

1. **询问/收集信息**：
   - 路径（相对 `roterPre` 写，例如 `setting/role`）
   - 对应组件文件（`@/views/...` 路径；若文件未存在，提示用户是否先生成）
   - 标题、是否进 Layout、是否 hidden、是否需要 icon
   - 是否需要路径参数（如 `:id`）
   - 是否属于某个业务域的二级页（决定放 `routes.js` 还是 `modules/`，以及 `activeMenu` 指向）
2. **确定插入位置**：
   - 业务模块组：`src/router/modules/<module>.js`（新建或追加，模板见下）
   - 业务子页：追加到 `routes.js` 中 `path: '/'` 或 `path: roterPre` 的 `children` 数组里
   - 独立页：追加到顶层 `defaultRoutes` 数组的 `404 fallback` 之前
3. **追加路由片段**，模板如下：

   - **业务页（进 Layout 的 children）**：
     ```js
     {
       path: `${roterPre}/setting/role`,
       name: 'settingRole',
       component: () => import('@/views/setting/role/index'),
       meta: { title: '角色管理', icon: 'role' }
     }
     ```

   - **独立全屏页**：
     ```js
     {
       path: roterPre + '/setting/icons',
       component: () => import('@/components/form-common/select-icon.vue'),
       name: 'icons',
       hidden: true
     }
     ```

   - **带参数页**：
     ```js
     {
       path: `${roterPre}/share/:id`,
       name: 'share',
       component: () => import('@/views/share'),
       hidden: true
     }
     ```

   - **业务模块文件（`src/router/modules/<module>.js`）**：父节点用 Layout 包裹，children 用相对路径，二级页统一挂 `meta.activeMenu`：
     ```js
     import Layout from '@/layout'
     import { roterPre } from '@/settings'

     const inventoryRouter = {
       path: `${roterPre}/inventory`,
       component: Layout,
       name: 'inventory',
       hidden: true,
       meta: { title: '库存' },
       children: [
         {
           path: 'inbound/create',
           component: () => import('@/views/inventory/inbound/create'),
           name: 'inventoryInboundCreate',
           meta: {
             title: '新建入库',
             activeMenu: `${roterPre}/inventory/inbound/index`
           }
         }
       ]
     }

     export default inventoryRouter
     ```
     父节点 `hidden: true` 表示本组只承载路由、不参与 `permission_routes` 菜单渲染；若列表页本身也要固定到前端并出现在菜单里，则去掉 `hidden` 并补 `icon`。

4. **校验**：
   - `rg "name:\s*'<新 name>'" src/router` 确认 name 不重复
   - `rg "path:.*<新 path>" src/router` 确认路径不冲突
   - 若 `component` 指向的 `.vue` 不存在，主动提示用户先用 `create-crud-page` 或手动生成
5. **若用户实际需要的是"动态菜单注入"**（即菜单由后台管理），告诉用户：
   - 只需在 `src/views/<path>.vue` 创建对应文件，无需改 `routes.js`
   - 通过后台菜单管理界面绑定 `component` 字段为 `views/<path>` 即可

## 跳转辅助

视图内跳转必须带 `roterPre` 前缀：

```js
import { roterPre } from '@/settings'
this.$router.push({ path: `${roterPre}/customer/list` })
```

如果用户在新增页面同时需要写跳转代码，提醒一次即可。

## 不要做的事

- 不要把业务模块的二级页塞回 `routes.js`；`routes.js` 只保留与业务域无关的通用页
- 不要为了接入 `modules/` 去改 `src/router/index.js`，聚合是自动的
- 不要给进侧边栏的菜单页设置 `hidden: true`，那是为登录/分享/工具页以及 `modules/` 里只承载二级页的父节点保留的
- 不要漏写二级页的 `meta.activeMenu`，否则菜单不高亮、面包屑为空
- 不要漏写 `roterPre`，否则刷新会 404（由 `route404` 兜底重定向）
- 不要修改 `src/router/index.js` 的核心逻辑（filterAsyncRoutes / createRouteRecord 等）
