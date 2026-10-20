# Vue 3 + TypeScript 组件写法

> 适用：新建脚本、页面或组件，重构组件，写 TS 类型，运行 typecheck。

## 文件与组件

- 新建脚本一律使用 `.ts`，新建页面和完全重构的组件使用 `<script setup lang="ts">` 与 Composables。已有 `.js` / Options API 的小改动就地完成，不为迁移语法扩大改动。
- 完全重构存量 `.js` 时改成 `.ts` 并补类型；同名 `.js` 必须删除，避免两个实现并存。
- `<script setup>` 支持 `defineOptions`、`defineModel`、`defineProps`、`defineEmits` 和 `defineExpose`。仅当父组件需要通过组件 ref 调用方法时才使用 `defineExpose`。
- `<script setup>` 中没有 `this`。路由和状态可用 `useRouter` / `useRoute`（`vue-router`）、`@/stores` 下的 `useXxxStore()`（Options API 用 pinia 的 `mapStores` / `mapState`），也可复用项目现有的单例；按所在模块既有写法保持一致。
- 新增的组件自定义事件应声明在 `defineEmits` 或 `emits` 中，尤其是 `click`、`input`、`change` 等与原生事件同名的事件。
- Element Plus 弹窗和抽屉使用 `v-model` 控制显隐；组件库属性按 Element Plus 的类型传值。

## 样式与类型

- 新写 scoped 样式使用 `:deep()`，不新增 `::v-deep` 或 `/deep/`。存量文件的小改动保持原有样式组织。
- 存量 `.js` 可继续引用，`allowJs: true`、`checkJs: false`；新写的 `.ts` 和 `<script lang="ts">` 走 strict 检查。
- 路径别名由 `tsconfig.json` 的 `paths` 提供，不增加已弃用的 `baseUrl`。
- 全局属性类型放在 `src/types/global.d.ts`；业务代码不要随意写 `any` 来规避类型错误。
- 类型检查运行 `pnpm typecheck`；打包运行 `pnpm build`。两者是独立命令。
