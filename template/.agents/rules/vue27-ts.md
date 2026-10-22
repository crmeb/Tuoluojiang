# Vue 2.7 + TypeScript 组件写法

> 适用：新建任意脚本文件、新建页面 / 组件、重构组件、改写 `<script setup>`、写 TS 类型、跑 typecheck。

## 文件类型选择（TS 优先）

- **新建文件一律 `.ts`**：api、utils、composables、store 模块、常量、类型、构建脚本等，新增时都写 `.ts`，**不再新增 `.js`**（`vite.config.ts` / `eslint.config.ts` 等已有配置同理）。
- **完全重构一个存量 `.js` 时顺手改 `.ts`**：既然整体重写，就把后缀一并换掉并补上类型；`resolve.extensions` 中 `.ts` 排在 `.js` 前，删掉旧 `foo.js` 新增 `foo.ts` 后**调用方的 import 路径不用改**。
- **小改动、bug 修复不改后缀**：存量 `.js` 就地改，不要顺手转 TS（撑爆 diff，且 `checkJs: false` 下转换后新暴露的类型错误会连累无关代码）。
- **新建 Vue 组件的 `<script>` 一律带 `lang="ts"`**，包括只有几行逻辑的展示型组件。
- 拿不准某个文件该不该转：**看改动幅度**——重写 ≥ 半个文件才算「完全重构」，否则按小改动处理。

## 组件写法约定

- **新建页面或完全重构组件**：一律 `<script setup lang="ts">` + Composables。不写 Options + `setup()`，也不在新代码里写 `data / methods / computed` 选项。
- **小改动、bug 修复不迁移语法**：存量 Options API 组件就地改，不要顺手重写成 `<script setup>`（撑爆 diff、且容易漏掉 `this` 语义）。

## Vue 2.7 `<script setup>` 硬约束

Vue 2.7 的 `<script setup>` 停在 Vue 3.2 时代的编译器实现，以下写错不是风格问题，而是直接坏功能：

- **没有 `defineOptions` 宏。** 需要组件 `name` 时（递归自引用、局部 `keep-alive :include`、devtools 可读名）并列一个普通 script 块导出 `defineComponent({ name: 'Xxx' })`，两块由编译器合并：

  ```vue
  <script lang="ts">
  import { defineComponent } from 'vue'

  export default defineComponent({ name: 'CustomerList' })
  </script>

  <script setup lang="ts">
  // 组合式逻辑写这里
  </script>
  ```

- **路由页不需要 `name`。** `src/layout/components/AppMain.vue` 是 `<router-view :key>` 直出（key 取 `$route.path`），没有全局 keep-alive，**路由页每次进入都是全新实例**。因此：不要依赖 `activated / onActivated`，「从子页返回后刷新列表」写在 `onMounted` 即可；确需跨路由保活的页面自行在内部包 `<keep-alive>`（例：`src/views/hr/archives/index.vue`），此时被包住的子组件才需要 `name`。
- **没有 `defineModel`。** 双向绑定用 `defineProps` + `emit('input', v)` / `emit('update:xxx', v)`。
- **`defineProps<T>()` 的类型参数只能是本文件内定义的 interface / 类型字面量**，`import` 进来的类型解析不了。要复用外部类型就在组件内重新声明一份，或退回运行时声明 `defineProps({ label: { type: String, default: '' } })`。
- **`defineExpose` 必写。** `<script setup>` 组件默认对外封闭，父组件通过 `ref` 调子组件方法（弹窗的 `openBox` 等）时不 expose 就取不到。
- **块内没有 `this`。** 模板引用用 `const tableRef = ref(null)` + `ref="tableRef"`；`$router / $store` 从单例导入（`import router from '@/router'`、`import store from '@/store'`，路由信息取 `router.currentRoute`）——**本项目不存在 `useRoute` / `useStore`**，不要凭 Vue 3 印象写；element-ui 的 `$message / $confirm` 同理，从 `element-ui` 具名导入 `Message` / `MessageBox`。
- **不要用 `Vue.extend`**，需要显式定义组件对象时一律 `defineComponent`——Vue 2.7 下只有它能让 Volar 在模板里正确解包 `ref` / `computed`；用 `Vue.extend` 时模板里访问 `rows.length` 会误报 `Property 'length' does not exist on type 'Ref<...>'`。
- 可用：`withDefaults`、`defineExpose`、`useSlots()` / `useAttrs()` / `useCssModule()`。

标准骨架：

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'

interface Row {
  id: number
  name: string
}

const props = withDefaults(defineProps<{ label?: string; disabled?: boolean }>(), {
  label: '',
  disabled: false
})
const emit = defineEmits<{
  (e: 'change', id: number): void
}>()

const rows = ref<Row[]>([])
const total = computed(() => rows.value.length)

function openBox() {
  // ...
}

defineExpose({ openBox })
</script>
```

## 样式

- **新建页面 / 完全重构组件的 `<style scoped>` 一律用 `:deep()`，禁止 `::v-deep` 和 `/deep/`**（两者已废弃，Volar 报警告）。存量文件的小改动**保持原写法，不顺手改**。
- 写法：`:deep(.el-input__inner) { ... }`，不要写成后代选择器前缀 `::v-deep .el-input__inner`。

## TypeScript（js + ts 混编）

- 存量 `.js` / `.vue` **不需要成批迁移**（只在完全重构该文件时才换 `.ts`）：`checkJs: false`，存量 js 只被「允许引用」，不做类型检查；ts 可直接 import 存量 js，类型由 TS 从源码推断。
- **`vueCompilerOptions.target` 必须是 `2.7`**，否则 Volar 按 Vue 3 语义编译模板。
- **不要往 `tsconfig.json` 加 `baseUrl`**（TS 6 已弃用，`TS5101`）。`paths` 不需要它，写 `"@/*": ["./src/*"]` 即可（相对 tsconfig 所在目录解析）。
- **`resolve.extensions` 中 `.ts` 排在 `.js` 前**：存量 `foo.js` 可直接新增 `foo.ts` 顶替，调用方的 import 路径不用改（新增 `.ts` 后记得删掉同名 `.js`，避免两份同时存在）。
- 全局变量（第三方 SDK 挂 window 那类）统一加到 `src/types/global.d.ts`，不要在业务代码写 `(window as any)`。
- 模板插值里**不要写 HTML 实体**（如 `&lt;`）：Vue 运行时会解码，Volar 不会，会报解析错误。
- 类型检查：`pnpm typecheck`（`vue-tsc --noEmit`，全量约 10s）。**不接入 dev / build**，类型错误不阻塞打包。
