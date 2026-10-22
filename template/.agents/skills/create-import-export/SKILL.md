---
name: create-import-export
description: 为 CRUD 列表页接入 Excel 导入、导出、模板下载与导入记录，复用 dragUpload 与 oaFromBox 下拉约定。当用户说"加导入导出/Excel 导入/批量导入/导出列表"时触发。
---

# create-import-export

在已有或新建 CRUD 列表页上接入**导入 / 导出 / 模板下载**，复用项目内成熟组件与 `oaFromBox` 下拉 `value` 约定。

## 项目现成能力

| 能力 | 位置 | 用途 |
|---|---|---|
| 导入弹窗 | `src/views/customer/components/dragUpload.vue` | 拖拽上传 xls/xlsx，调 `xxxImportApi` |
| 导入记录 | `src/views/customer/list/components/importRecords.vue` | 查看导入/导出历史 |
| 列表导出 | 各业务 `index.vue` 的 `exportList` + `xxxExportApi` | 按当前筛选条件导出 |
| API 命名 | `xxxImportTemplateApi` / `xxxImportApi` / `xxxExportApi` | 见 [`create-api-module`](../create-api-module/SKILL.md) |

参考实现：`src/views/customer/list/index.vue`（`dropdownFn` case 4/8/12）。

## oaFromBox 下拉 value 约定（客户列表通用）

在 `dropdownList` 中追加项（value 勿与现有页面冲突，先 `rg "value:\s*[0-9]+" src/views/<scope>` 查重）：

| value | 含义 | 处理 |
|---|---|---|
| 4 | 导出 | 调 `exportList()` |
| 8 | 导入 | `this.$refs.dragUpload.openBox(keyword)` |
| 12 | 导入/导出记录 | `this.$refs.importRecords.openBox(keyword)` |

其他页面 value 可能不同（如合同页 4=筛选设置），**以当前文件已有 dropdownList 为准**，不可硬套。

## 工作流程

### 1. 补齐 API（`create-api-module`）

```js
/** <模块>--导入模板下载 */
export function xxxImportTemplateApi(data) {
  return request.get('<scope>/import/template', data)
}

/** <模块>--导入 */
export function xxxImportApi(keyword, data) {
  return request.post(`<scope>/import/${keyword}`, data)
}

/** <模块>--导出 */
export function xxxExportApi(data) {
  return request.get('<scope>/export', data)
}
```

具体 URL 以后端文档为准；导出若为文件流，确认 `request.js` 是否需 `{ responseType: 'blob' }`。

### 2. 列表页接入组件

```vue
<template>
  <!-- oaFromBox 已有 @dropdownFn -->
  <drag-upload ref="dragUpload" @ok="getTableData" />
  <import-records ref="importRecords" />
</template>

<script>
import dragUpload from '@/views/customer/components/dragUpload.vue'
import importRecords from '@/views/customer/list/components/importRecords.vue'
import { xxxExportApi } from '@/api/<scope>'

export default {
  components: { dragUpload, importRecords },
  methods: {
  dropdownFn(item) {
    switch (item.value) {
      case 4:
        this.exportList()
        break
      case 8:
        this.$refs.dragUpload.openBox(this.keyword)
        break
      case 12:
        this.$refs.importRecords.openBox(this.keyword)
        break
    }
  },
  async exportList() {
    const params = { ...this.where, ids: this.ids }
    const res = await xxxExportApi(params)
    // 按项目现有方式触发下载（blob 或返回 url）
  }
  }
}
</script>
```

`keyword` 必须与 `customizeTable`、后台导入配置的业务标识一致（如 `customer`、`clue`）。

### 3. 更新 dropdownList

在 `dropdownList` 增加「导出」「导入」「导入/导出记录」项，并挂 `v-hasPermi`：

```js
{ label: '导出', value: 4, permi: ['<scope>:<feature>:export'] }
```

若 `oaFromBox` 不支持 per-item permi，在 `dropdownFn` 入口做权限判断。

### 4. 权限码

```
<scope>:<feature>:import
<scope>:<feature>:export
```

登记清单交给 [`register-permission`](../register-permission/SKILL.md)。

## 验收要点

- 模板下载文件名、格式正确
- 导入成功刷新列表；失败展示后端 `msg`
- 导出携带当前筛选条件与勾选 ids
- 超量导出是否有前端拦截（参考低代码模块 1000 条限制提示）

## 不要做的事

- 不要新建第二套上传组件，优先复用 `dragUpload.vue`
- 不要在导入成功后再 `this.$message.success`（`request.js` 已统一提示）
- 不要假设所有模块 value 4/8/12 含义相同，必须读当前页 `dropdownList`
