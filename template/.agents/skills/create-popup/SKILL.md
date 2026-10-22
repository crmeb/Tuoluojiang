---
name: create-popup
description: 新建独立的 PC 端模态弹窗（el-dialog）或侧滑抽屉（el-drawer）组件，统一从 constants/popupSize 取尺寸 token，套用 openBox + defineExpose 父子通信模式。当用户说"加个弹窗/加侧滑/加详情抽屉/弹个表单/二次确认框"且不是整套 CRUD 时触发。
---

# create-popup

为本项目（Vue 2.7 + Element UI）生成一个**独立**的弹窗/侧滑组件。整套 CRUD 列表页请用 `create-crud-page`；本 skill 用于单独新增一个弹窗/抽屉（详情、表单、选择器、二次确认等）。

## 动手前必读

**必须先读 `docs/ai-ui-popup-guidelines.md`**（AGENTS.md 强制要求），它规定了尺寸 token 选型与禁用的散值/百分比宽度。

## 必须遵循的项目约定

1. **写法**：全新组件统一 `<script setup lang="ts">` + Composables；改既有 Options 弹窗的小改动不强行迁移。**不需要**伴生 `name` 块。
2. **尺寸只能用 token**：从 `@/constants/popupSize` 引入 `DIALOG_SIZE` / `DRAWER_SIZE`，模板里 `:width="DIALOG_SIZE.SM"` / `:size="DRAWER_SIZE.MD"`。
   - DIALOG：`XS 470 / SM 540 / MD 650 / LG 800 / XL 1000`
   - DRAWER：`SM 600 / MD 800 / LG 1000 / XL 1200`
   - 禁止 `520/550/700/760/850/920px` 等散值与 `40%/60%` 等百分比宽度，禁止用全局 CSS 覆盖 `.el-dialog`/`.el-drawer` 宽度。
3. **父子通信模式**：子组件用 `defineExpose({ openBox })` 暴露打开方法，父组件 `ref` 调用；提交成功 `emit('ok')` 由父组件刷新。**`defineExpose` 必写**——`<script setup>` 组件默认对外封闭，漏了父组件 `ref` 只能拿到空壳。
4. **关闭即重置**：`@close="reset"`，reset 里清空表单并 `clearValidate()`，避免下次打开残留。
5. **轻提示**：成功提示由 `request.js` 拦截器统一弹出，业务里**不要** `$message.success`。
6. **选型**：表单/确认类用 `el-dialog`；详情/信息较多用 `el-drawer`。简单确认优先 `proxy.$modalSure('文案')` 而不是新建弹窗。

## 模板骨架

### A. 表单弹窗（el-dialog）

```vue
<template>
  <el-dialog
    :visible.sync="visible"
    :title="form.id ? '编辑' : '新增'"
    :width="DIALOG_SIZE.SM"
    :close-on-click-modal="false"
    @close="reset"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
      <el-form-item label="名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入" maxlength="50" show-word-limit />
      </el-form-item>
    </el-form>
    <template #footer>
      <span>
        <el-button size="small" @click="visible = false">取 消</el-button>
        <el-button size="small" type="primary" :loading="loading" @click="submit">确 定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize'
import { xxxCreateApi, xxxUpdateApi } from '@/api/<scope>'

interface FormState {
  id: number
  name: string
}

const emit = defineEmits<{ (e: 'ok'): void }>()

const visible = ref(false)
const loading = ref(false)
const formRef = ref<any>(null)
const form = reactive<FormState>({ id: 0, name: '' })
const rules = { name: [{ required: true, message: '请输入名称', trigger: 'blur' }] }

function reset() {
  form.id = 0
  form.name = ''
  formRef.value && formRef.value.clearValidate()
}

function openBox(row?: Partial<FormState>) {
  reset()
  if (row) Object.assign(form, row)
  visible.value = true
}

function submit() {
  formRef.value.validate(async (ok: boolean) => {
    if (!ok) return
    loading.value = true
    try {
      if (form.id) await xxxUpdateApi(form.id, form)
      else await xxxCreateApi(form)
      visible.value = false
      emit('ok')
    } finally {
      loading.value = false
    }
  })
}

defineExpose({ openBox })
</script>
```

### B. 详情抽屉（el-drawer）

```vue
<template>
  <el-drawer :visible.sync="visible" title="详情" :size="DRAWER_SIZE.SM" :wrapper-closable="false">
    <div v-loading="loading" class="p20">
      <el-descriptions :column="1" border>
        <el-descriptions-item label="名称">{{ data.name || '--' }}</el-descriptions-item>
      </el-descriptions>
    </div>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { DRAWER_SIZE } from '@/constants/popupSize'
import { xxxDetailApi } from '@/api/<scope>'

const visible = ref(false)
const loading = ref(false)
const data = ref<Record<string, any>>({})

async function open(row: { id: number }) {
  visible.value = true
  loading.value = true
  try {
    const res = await xxxDetailApi(row.id)
    data.value = res.data
  } finally {
    loading.value = false
  }
}

defineExpose({ open })
</script>
```

### 父组件接线

父组件若是 `<script setup>`，模板 ref 名与变量名对齐即可：

```vue
<template>
  <edit-dialog ref="editDialogRef" @ok="getTableData" />
  <details-drawer ref="detailsDrawerRef" />
</template>

<script setup lang="ts">
import { ref } from 'vue'
import editDialog from './components/editDialog.vue'
import detailsDrawer from './components/details.vue'

const editDialogRef = ref<any>(null)
const detailsDrawerRef = ref<any>(null)

// 打开：editDialogRef.value.openBox(row) / detailsDrawerRef.value.open(row)
</script>
```

父组件仍是存量 Options 页面时不用改，照旧 `this.$refs.editDialog.openBox(row)`——`defineExpose` 出来的方法两种写法都取得到。

## 不要做的事

- 不要新增散值尺寸或百分比宽度，不要用全局 CSS 覆盖弹窗宽度。
- 不要把弹窗逻辑塞进列表页 `index.vue`，独立组件放 `components/`。
- 不要在成功后再写 `$message.success`。
- 简单二次确认不要新建弹窗组件，用 `proxy.$modalSure('文案')`（`proxy` 取自 `getCurrentInstance()`，不能脱离实例直接调 `modalSure`，它内部依赖 `this.$t`）。
- 不要漏 `defineExpose`；也不要在 Vue 2.7 里写 `defineOptions` / `defineModel`（都不存在）。
