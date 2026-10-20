---
name: extract-composable
description: 从 Vue 3 页面中抽取可复用组合式逻辑到 composables/（useTable、usePage、useLoading 模式），供 script setup 组件复用。当用户说"抽 composable/组合式函数/逻辑复用/提取列表逻辑"时触发。
---

# extract-composable

将页面内重复的列表、分页、loading、抽屉状态等逻辑抽到 `composables/`，供 **`<script setup lang="ts">` 组件**复用（AGENTS.md 约定：新建 / 重构页统一 `<script setup>`）。存量 Options 页面同样能用——composable 是普通函数，只要在 `setup()` 里调用即可，两种写法共存不冲突。

## 项目参考

| 文件 | 职责 |
|---|---|
| `src/composables/usePage.ts` | 分页 state |
| `src/composables/useLoading.ts` | loading 开关 |
| `src/composables/useTable.ts` | 列表 + 分页 + 搜索组合 |
| `src/composables/useDetailDrawer.ts` | 详情抽屉开关与当前行 |
| `src/views/inventory/composables/useWarehouseMap.ts` | 全量仓库 id -> 信息映射（懒加载，展示用，与权限无关） |
| `src/views/user/calendar/composables/useCustomer.ts` | 业务域专用逻辑 |

**优先扩展已有 composable**，再新建文件。

## 何时抽取

| 信号 | 动作 |
|---|---|
| 两个以上列表页重复 `where.page/limit`、`getTableData`、`loading` | 用或扩展 `useTable` |
| 单页超过 80 行纯数据逻辑 | 按职责拆 `useXxx` |
| 仅一处使用 | **不抽**，保持内联 |

## 文件约定

```
src/views/<scope>/<feature>/composables/useXxx.ts   # 功能专用
src/views/<scope>/composables/useXxx.ts             # 域内共享
src/composables/useXxx.ts                           # 跨业务域全局共享（如 useTable/usePage/useLoading）
```

- 导出：`export const useXxx = (deps, options) => { ... }`
- 新建 composable 可直接写 `.ts`（`src/composables/useXxx.ts`），存量 `.js` 不必迁移；`resolve.extensions` 里 `.ts` 排在 `.js` 前，同名顶替时调用方 import 路径不用改
- 使用 Vue 3 的 `ref` / `reactive` / `watch` / `computed` from `'vue'`
- 返回对象属性用**顶层 ref**（模板自动解包）；在 `<script setup>` 里解构出来的 ref 模板直接可用，**不需要 `return`**

## 模板：列表逻辑

```js
import { ref, watch } from 'vue'
import { usePage } from '@/composables/usePage'
import { useLoading } from '@/composables/useLoading'

/**
 * <模块>--<功能>列表逻辑
 * @param {Function} fetchApi 列表接口
 */
export const useFeatureList = (fetchApi) => {
  const { page, pageSize, total, resetPage } = usePage()
  const { isLoading, startLoading, stopLoading } = useLoading()
  const tableData = ref([])
  const where = ref({ page: 1, limit: 15 })

  const fetchData = async () => {
    startLoading()
    try {
      const res = await fetchApi({ ...where.value, page: page.value, limit: pageSize.value })
      tableData.value = res.data.list || []
      total.value = res.data.count || 0
    } finally {
      stopLoading()
    }
  }

  watch([page, pageSize], fetchData)

  return { tableData, where, isLoading, page, pageSize, total, resetPage, fetchData }
}
```

## 在页面中使用（`<script setup lang="ts">`）

```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { xxxListApi } from '@/api/<scope>'
import { useFeatureList } from './composables/useFeatureList'

const { tableData, where, isLoading, fetchData } = useFeatureList(xxxListApi)

onMounted(() => fetchData())
</script>
```

解构出来的 `tableData` / `isLoading` 等顶层 ref 模板里自动解包，无需 `return` 给模板。

弹窗 `ref`、路由跳转等强耦合 UI 的逻辑**留在组件内**，不要塞进 composable。

## 工作流程

1. 标出重复块（分页、请求、选中行、抽屉状态）
2. 查 `src/views/**/composables/` 是否已有可扩展项
3. 新建或扩展 composable，补中文 JSDoc
4. 原页面改为调用 composable 并删除重复代码；页面本身是存量 Options 写法时**不要顺手迁成 `<script setup>`**，只替换重复逻辑
5. 若含纯函数分支，按业务风险补充针对分支行为的测试

## 不要做的事

- 不要在 composable 内直接 `import router` / `import store`（通过参数注入）；本项目用 `useRouter` 与 `@/stores` 下的 `useXxxStore()`
- 不要在 composable 里调 `getCurrentInstance()` 取 `proxy`——那样它就只能在组件 `setup` 同步阶段用，失去可测试性
- 不要把 API URL 硬编码在 composable（通过 `fetchApi` 参数传入）
- 不要为一次性脚本创建 composable

## 与其他 skill 的配合

- 新建 CRUD 页时若预判多页复用，可在 [`create-crud-page`](../create-crud-page/SKILL.md) 完成后调用本 skill 重构
-  inventory 模块的 `useTable` 可作为新模块默认基线
