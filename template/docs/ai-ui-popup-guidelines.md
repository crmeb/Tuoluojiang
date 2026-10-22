# AI UI 弹窗与侧滑规范

本文档用于约束 AI 和二次开发时新增、重构弹窗/侧滑的尺寸选择。存量页面不做全量批量替换，优先在常用模块和后续新页面中落地。

## 适用范围

- 技术栈：Vue 2.7 + Element UI。
- PC 端 `el-dialog` 模态弹窗与 `el-drawer` 侧滑弹窗。
- 全新页面、完全重构组件、常用模块的侧滑统一改造必须遵守本文档。
- 历史页面的小改动只在触及弹窗/侧滑本身时顺手规范，不为统一尺寸扩大改动范围。

## 尺寸常量

统一从 `src/constants/popupSize.js` 引入：

```js
import { DIALOG_SIZE, DRAWER_SIZE } from '@/constants/popupSize'
```

`DIALOG_SIZE`：

| 名称 | 尺寸 | 典型场景 |
| --- | --- | --- |
| `XS` | `470px` | 简单确认、少量字段的小表单 |
| `SM` | `540px` | 常规小表单、原因填写、单组基础信息 |
| `MD` | `650px` | 常规新增/编辑表单 |
| `LG` | `800px` | 字段较多、带分组、带小表格的弹窗 |
| `XL` | `1000px` | 大表格、复杂选择器、预览类弹窗 |

`DRAWER_SIZE`：

| 名称 | 尺寸 | 典型场景 |
| --- | --- | --- |
| `SM` | `600px` | 简单详情、简单表单 |
| `MD` | `800px` | 常规新增/编辑、信息较多的详情 |
| `LG` | `1000px` | 复杂详情、明细表格、左右信息区 |
| `XL` | `1200px` | 多栏复杂编辑，尽量少用 |

## 使用方式

`<script setup>` 中直接在模板使用：

```vue
<template>
  <el-dialog :width="DIALOG_SIZE.SM" />
  <el-drawer :size="DRAWER_SIZE.MD" />
</template>

<script setup>
import { DIALOG_SIZE, DRAWER_SIZE } from '@/constants/popupSize'
</script>
```

Options API 组件需要把常量暴露给模板：

```js
import { DIALOG_SIZE, DRAWER_SIZE } from '@/constants/popupSize'

export default {
  data() {
    return {
      DIALOG_SIZE,
      DRAWER_SIZE
    }
  }
}
```

配置对象里优先写常量，不再新增散落的魔法尺寸：

```js
const formData = {
  title: '新增',
  width: DRAWER_SIZE.MD
}
```

## 选型规则

- 新增侧滑优先从 `600px`、`800px`、`1000px` 中选择，`1200px` 只用于确实复杂的多栏内容。
- 新增模态弹窗只能从 `470px`、`540px`、`650px`、`800px`、`1000px` 中选择。
- 不新增 `520px`、`550px`、`700px`、`760px`、`850px`、`920px`、`1120px` 等散值。
- 不新增百分比宽度，如 `40%`、`60%`、`85%`。设计器、打印预览、文件预览、全屏预览等工具型页面确需例外时，保留局部说明。
- 不通过全局 CSS 强制覆盖 `.el-dialog` 或 `.el-drawer` 宽度。宽度应由组件属性或配置对象显式传入。

## 存量改造策略

- 不做全仓批量替换，避免误改表格列宽、输入框宽度、图片宽度和工具型页面。
- 优先改常用模块的主流程侧滑，例如新增、编辑、详情、审批、付款、合同、客户等高频入口。
- 每次改造只覆盖当前模块，测试当前模块的打开、关闭、提交、滚动、底部按钮和内容溢出。
- 特殊页面如流程设计器、表单设计器、文件预览、打印预览、XMind 编辑器等先标记例外，不纳入普通业务尺寸。

## AI 生成要求

- 新建 CRUD 页、管理页、列表页时，弹窗/侧滑必须引入 `src/constants/popupSize.js`。
- 优先在 `components/editDialog.vue`、`components/details.vue` 等子组件里维护尺寸，不把尺寸散落在列表页。
- 如果用户只要求小改动，不为了尺寸规范迁移 Options API 到 `<script setup>`。
- 如果必须使用非标准尺寸，先说明原因，并尽量局部封闭，避免扩散为新规范。
