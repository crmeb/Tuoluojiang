---
name: register-permission
description: 为按钮/区块/路由配置权限控制，统一权限码命名（scope:feature:action）与 v-hasPermi / v-permission 指令用法，并产出需在后台菜单登记的权限码清单。当用户说"加权限/这个按钮要按权限显示/配菜单权限/某功能只给某角色看"时触发。
---

# register-permission

为本项目（Vue 2.7 + Element UI）的按钮、区块、路由配置统一的权限控制。本项目权限是**三层体系**，详见 `docs/二次开发手册/08-权限控制规范.md`，本 skill 是其落地脚手架。

## 权限三层（必须先分清用哪一层）

| 层级 | 实现 | 数据源 | 控制粒度 | 何时用 |
|---|---|---|---|---|
| 路由级 | 后端动态菜单 | 接口下发菜单 | 整个页面 | 控制页面能否进入 → 走 `create-route` + 后台菜单 |
| 按钮级 | `v-hasPermi` 指令 | `store.getters.permissions` | DOM 元素 | **最常用**，按"功能权限码"控制按钮/操作 |
| 角色级 | `v-permission` 指令 | `store.getters.roles` | DOM 元素 | 按"角色"控制（如仅 admin/finance 可见） |

> 指令均已在 `src/directive/index.js` 全局注册（`src/directive/permission/hasPermi.js`、`permission.js`），页面无需 import。

## 一、按钮级权限 v-hasPermi（默认首选）

```vue
<!-- 指令值必须是数组；命中其一即显示，无权限会从 DOM 中【移除】（不是 v-show 隐藏） -->
<el-button v-hasPermi="['asset:create']" type="primary" @click="handleAdd">新增</el-button>
<el-button v-hasPermi="['asset:edit', 'asset:admin']" @click="handleEdit">编辑</el-button>
<el-button v-hasPermi="['asset:delete']" type="danger" @click="handleDelete">删除</el-button>
```

机制（见 `hasPermi.js`）：超管标识 `'*'` 命中所有权限；`permissions` 数组用 `includes` 判定；不通过则 `el.parentNode.removeChild(el)`。

## 二、角色级权限 v-permission

```vue
<div v-permission="['admin']">管理员专属</div>
<div v-permission="['admin', 'finance']">管理员或财务可见</div>
```

仅在"按角色"而非"按功能码"控制时才用；业务功能优先用 `v-hasPermi`。

## 三、权限码命名规约（强约束）

格式固定 `<scope>:<feature>:<action>`，全小写英文，禁止中文/拼音；与 `create-crud-page`、`create-route` 的 scope/feature 保持同一套命名。

- 标准 action：`view` `add`/`create` `edit` `delete` `export` `import` `audit`（审批）`admin`
- 同一功能页的一组码必须成套出现，便于后台菜单批量登记
- 列表页操作列与"新增"按钮逐个挂码，不要整页只挂一个码

示例（售后工单 `aftersale/ticket`）：
```
aftersale:ticket:view
aftersale:ticket:add
aftersale:ticket:edit
aftersale:ticket:delete
aftersale:ticket:export
```

## 四、非模板场景的编程式判断

项目**没有** `checkPermi` 工具函数。需要在 `<script>` 里（如 `v-if` 表达式、方法内分支）判断权限时，直接读 store：

```js
import store from '@/store'
const has = (codes) => {
  const perms = store.getters.permissions || []
  return perms.includes('*') || codes.some((c) => perms.includes(c))
}
```
若此类判断在多页复用，再考虑抽到 `src/utils/`，不要每页重复造轮子。

## 工作流程

1. **确认层级**：功能码（按钮级，默认）还是角色（角色级）。
2. **确认 scope/feature**：与所属业务模块/路由一致。
3. **给元素挂指令**：列表操作列、表单提交按钮、敏感区块逐个挂 `v-hasPermi`。
4. **产出权限码清单**：把本次新增的全部权限码列成清单交给用户，并**明确提示**：这些码需由开发者在后台"权限菜单"中登记，前端挂码不会自动创建后台权限。
5. **路由级**：若涉及"页面能否进入"，提示走 `create-route` 注册路由 + 后台菜单绑定 `views/<scope>/<feature>/index`。

## 不要做的事

- 不要用 `v-hasPermi="'asset:edit'"`（字符串）——指令值**必须是数组**，否则 `hasPermi.js` 抛错。
- 不要用 `v-show`/`v-if` 手写 `store.getters.permissions.includes(...)` 替代指令做按钮显隐（除非确需保留占位）。
- 不要臆造后台不存在的权限码当作"已生效"，必须把清单交回用户登记。
- 不要把功能权限误用成角色权限（`v-permission` 走的是 `roles` 不是 `permissions`）。
