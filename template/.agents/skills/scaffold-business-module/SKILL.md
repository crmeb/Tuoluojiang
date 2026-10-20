---
name: scaffold-business-module
description: 一键生成一个完整的业务模块脚手架（API 文件 + 路由 + 列表页 + 新增/编辑弹窗 + 详情抽屉 + 可选 Pinia store），自动串联其他 skills。当用户说"新建一个业务模块/做一个完整功能模块/起一个新模块的架子"时触发。
---

# scaffold-business-module

把"新增一个业务模块"这件 OA 二开里最高频的事变成一次连贯的操作：依次调用同目录下的 `create-api-module` → `create-route` → `create-crud-page`（必要时加 `create-pinia-store`），确保最终产物符合项目所有约定。

## 收集信息（一次性问清，避免反复打断）

在动手前，**必须**先通过 `AskUserQuestion` 收集以下信息（如果用户提示词里已经给了对应内容，则跳过该项）：

1. **模块名**：中文显示名（如"售后工单"）+ 英文 scope（如 `aftersale/ticket`），用作目录、命名空间、URL、权限码前缀
2. **接口清单**：列表 / 详情 / 新增 / 修改 / 删除是默认必须，额外还有什么？（导入、导出、批量操作、状态变更）
3. **字段清单**：表格列（含字段类型：文本/字典/人员/标签/时间/Tag）、搜索字段、表单字段
4. **路由路径**：相对 `roterPre` 写，例如 `aftersale/ticket`
5. **是否需要 Pinia store**：单页面无跨页共享一般不需要，跨页/全局缓存才加
6. **权限码前缀**：默认 `<scope>:<feature>:`（如 `aftersale:ticket:view/edit/add/delete/export`）

## 执行步骤（严格顺序）

> 每一步完成后，告诉用户"已完成 X，下一步将做 Y"，方便用户随时打断/调整方向。

### 1. 生成 API 文件

调用同仓库 skill 的逻辑：在 `src/api/<scope>.ts`（若 scope 是 `aftersale/ticket` 则文件名为 `src/api/aftersale.ts` 或 `src/api/ticket.ts`，按业务领域决定）追加 / 新建：

```js
import request from '@/api/request'
//todo <模块中文名>相关接口

/** <模块>--列表 */
export function <feature>ListApi(data) { return request.get(`<scope>`, data) }
/** <模块>--详情 */
export function <feature>DetailApi(id) { return request.get(`<scope>/${id}`) }
/** <模块>--新增 */
export function <feature>CreateApi(data) { return request.post(`<scope>`, data) }
/** <模块>--更新 */
export function <feature>UpdateApi(id, data) { return request.put(`<scope>/${id}`, data) }
/** <模块>--删除 */
export function <feature>DeleteApi(id) { return request.delete(`<scope>/${id}`) }
```

参考 `.agents/skills/create-api-module/SKILL.md` 的更详细模板。

### 2. 生成视图文件

在 `src/views/<scope>/<feature>/` 下生成：

- `index.vue` 列表主页面（`<script setup lang="ts">` + Composables）
- `components/editDialog.vue` 新增/编辑弹窗
- `components/details.vue` 详情抽屉

完全套用 `.agents/skills/create-crud-page/SKILL.md` 给出的模板。**记得**：
- 顶层包 `<div class="divBox"><el-card class="normal-page el-card-flex">`
- 必接 `oaFromBox` + `customizeTable`，并把 `flexLayout` 传上
- 操作按钮挂 `v-hasPermi="['<scope>:<feature>:<action>']"`
- 调用步骤 1 生成的 `xxxApi`

### 3. 注册路由（按需）

如果用户使用**动态后台菜单**（默认情况），仅需保证 `src/views/<scope>/<feature>/index.vue` 存在并提醒用户在后台菜单里绑定 `component = views/<scope>/<feature>/index`。

如果用户明确要静态注册，在 `src/router/routes.ts` 追加：

```js
{
  path: `${roterPre}/<scope>/<feature>`,
  name: '<scope><Feature>',
  component: () => import('@/views/<scope>/<feature>/index'),
  meta: { title: '<模块中文名>' }
}
```

参考 `.agents/skills/create-route/SKILL.md`。

### 4. （可选）生成 Pinia store

仅在用户明确"需要全局/跨页缓存列表数据 / 字典 / 当前选中项"时执行。模板见 [`../create-pinia-store/SKILL.md`](../create-pinia-store/SKILL.md)，文件落到 `src/stores/<feature>.ts`。

### 5. 汇总并指引验证

最后输出一段简短总结，告诉用户：

```
已生成：
  - src/api/<scope>.ts（新建/扩展，共 N 个接口）
  - src/views/<scope>/<feature>/index.vue
  - src/views/<scope>/<feature>/components/editDialog.vue
  - src/views/<scope>/<feature>/components/details.vue
  - （可选）src/router/routes.ts 追加 1 条路由
  - （可选）src/stores/<feature>.ts

下一步：
  1. 后台菜单绑定 component: views/<scope>/<feature>/index
  2. 配置权限码：<scope>:<feature>:view/add/edit/delete/export
  3. 本地启动 pnpm dev 验证
```

## 复用现有 skill

本 skill 不复刻其他 skill 的代码模板，**生成内容时直接套用**同目录下 `create-api-module / create-route / create-crud-page / create-pinia-store` 的规则。当遇到不在那些 skill 覆盖范围的边界场景（比如要做"标签页式 CRUD"、"树形列表 + 详情"、"流程审批"等），先按最贴近的标准 CRUD 生成，再向用户提示需要进一步定制的位置。

## 不要做的事

- 不要一次性把所有文件用一个 `Write` 拼成超长输出，**按步骤拆分**，每一步独立生成、独立汇报
- 不要在没收集字段清单前就把表格列写死成假字段
- 不要直接 `pnpm dev` 启动验证（让用户决定何时启动）
- 不要把模块写进 `customer/` 目录除非确实属于客户管理；新模块就开新 scope 目录
- 不要默认追加 Pinia store，按用户回答决定
