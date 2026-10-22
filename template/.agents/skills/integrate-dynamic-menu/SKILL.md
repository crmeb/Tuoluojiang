---
name: integrate-dynamic-menu
description: 将二开页面接入后台动态菜单体系（views 路径约定、菜单字段、权限绑定、侧边栏展示）。当用户说"加菜单/后台配菜单/动态路由/菜单不显示/页面做了但进不去"时触发。
---

# integrate-dynamic-menu

本项目**绝大多数业务页**走**后台动态菜单**，前端只需保证 `src/views/` 下文件存在，由 `src/router/index.js` 的 `require.context` 自动映射组件。**不要**默认改 `routes.js`。

## 动态 vs 静态路由（决策）

| 场景 | 做法 | 参考 skill |
|---|---|---|
| 常规业务模块（客户、合同、设置等） | 动态菜单 | **本 skill** |
| 登录页、分享页、隐藏工具页 | 静态 `routes.js` | [`create-route`](../create-route/SKILL.md) |

## 前端文件约定

1. 页面文件路径：`src/views/<scope>/<feature>/index.vue`
2. 后台菜单 `component` 字段填写：`views/<scope>/<feature>/index`（**不带** `@/`、**不带** `.vue`）
3. 路由实际 path 由后台菜单 `menu_path` 决定，须以 `/admin`（`roterPre`）为前缀
4. 页面内跳转统一：`import { roterPre } from '@/settings'` + `` `${roterPre}/...` ``

## 工作流程

### 1. 确认视图已存在

```bash
# 示例：客户域售后工单
src/views/customer/aftersale/ticket/index.vue
```

若不存在，先执行 [`create-crud-page`](../create-crud-page/SKILL.md) 或 [`scaffold-business-module`](../scaffold-business-module/SKILL.md)。

### 2. 产出「菜单配置清单」交给实施人员

生成 Markdown 表格，用户复制到后台「菜单管理」：

| 字段 | 填写说明 | 示例 |
|---|---|---|
| 菜单名称 | 中文，显示在侧边栏 | 售后工单 |
| 父级菜单 | 挂在哪个一级/二级下 | 客户管理 |
| menu_path | 浏览器地址路径 | `/admin/customer/aftersale/ticket` |
| component | 组件映射路径 | `views/customer/aftersale/ticket/index` |
| 图标 | iconfont 类名（可选） | `iconkehu` |
| 排序 | 数字越小越靠前 | 10 |
| 是否显示 | `is_show = 1` 显示 | 1 |

### 3. 权限码登记

与 [`register-permission`](../register-permission/SKILL.md) 联动，列出需在后台登记的权限码：

```
customer:aftersale:view
customer:aftersale:add
customer:aftersale:edit
customer:aftersale:delete
customer:aftersale:export
```

页面按钮已用 `v-hasPermi` 时，权限码必须与后台一致。

### 4. 验收

- 重新登录或刷新权限缓存后，侧边栏应出现新菜单
- 点击菜单 URL 与 `menu_path` 一致
- 二级侧边栏宽度、折叠行为正常（见 `layout/components/Sidebar`）
- 无权限账号看不到对应按钮

## 常见问题

| 现象 | 排查 |
|---|---|
| 菜单有了但 404 | `component` 路径与 `src/views` 实际路径不一致 |
| 白屏 + Console 组件加载失败 | 文件名/目录大小写、缺少 `index.vue` |
| 菜单不显示 | 父级 `is_show`、角色未分配菜单 |
| 按钮全消失 | 权限码未登记或拼写与 `v-hasPermi` 不一致 |

## 不要做的事

- 不要为常规模块同时改 `routes.js` 和配动态菜单（二选一）
- 不要写 `component: '@/views/...'`（动态路由解析不支持 `@/`）
- 不要修改 `src/router/index.js` 的 `filterAsyncRoutes` 核心逻辑
