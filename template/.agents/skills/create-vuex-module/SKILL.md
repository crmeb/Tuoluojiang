---
name: create-vuex-module
description: 在 src/store/modules/ 下生成 namespaced Vuex module，包含 state/mutations/actions/getters，并自动在 store/index.js 自动注册 require.context 模式下生效。当用户说"加一个 vuex 模块/全局状态/store"时触发。
---

# create-vuex-module

为本项目（Vuex 3 + namespaced module）生成一个新的状态模块，遵循 `src/store/modules/*.js` 现有风格（小驼峰 mutations 命名为大写下划线、actions 调 commit、必要时落 localStorage / Cookies）。

## 项目约定

1. **目录**：`src/store/modules/<name>.js`。
2. **命名空间**：每个 module 必须 `namespaced: true`，组件里通过 `this.$store.state.<name>.xxx` / `this.$store.dispatch('<name>/xxx')` 调用。
3. **state 持久化**：
   - 用户态、菜单、企业等跨刷新需保留的状态 → 落到 `localStorage`（用 `@/utils/storage` 的 `getStorageJson` 兜底解析）
   - 侧边栏开合、size 等 UI 偏好 → 落到 `js-cookie`
   - 临时状态（loading、当前编辑字段）→ 仅内存
4. **mutations 命名**：全大写 + 下划线（如 `SET_TOKEN`、`SET_MENU_LIST`）。
5. **actions**：薄一层，只负责 `commit` + 调用 API；不要在 actions 里写复杂业务逻辑。
6. **getters**：跨 module 派生时写到 `src/store/getters.js`，单 module 自身派生写在 module 内。
7. **注册**：`src/store/index.js` 一般通过 `require.context('./modules', ...)` 自动注册（具体看现有写法）；若未自动注册需手动在 `index.js` 引入。**先用 Read 确认**。

## 工作流程

1. **收集信息**：
   - 模块名（英文小驼峰，作为命名空间）
   - 字段清单：每个字段说明类型、初始值、是否需要持久化（localStorage / Cookies / 无）
   - 是否需要异步 action（接 `@/api/...`）
2. **检查 `src/store/index.js`** 是否自动加载 `modules/` 下文件；若不是，则需要手动 import + register。
3. **生成 module 文件**，模板：

   ```js
   import { xxxApi } from '@/api/<scope>'
   import { getStorageJson } from '@/utils/storage'

   const state = {
     list: [],
     detail: getStorageJson('<name>Detail', null),
     loading: false
   }

   const mutations = {
     SET_LIST: (state, list) => {
       state.list = list
     },
     SET_DETAIL: (state, detail) => {
       state.detail = detail
       localStorage.setItem('<name>Detail', JSON.stringify(detail))
     },
     SET_LOADING: (state, loading) => {
       state.loading = loading
     }
   }

   const actions = {
     async fetchList({ commit }, params) {
       commit('SET_LOADING', true)
       try {
         const res = await xxxApi(params)
         commit('SET_LIST', res.data.list || [])
       } finally {
         commit('SET_LOADING', false)
       }
     },
     setDetail({ commit }, detail) {
       commit('SET_DETAIL', detail)
     }
   }

   const getters = {
     count: (state) => state.list.length
   }

   export default {
     namespaced: true,
     state,
     mutations,
     actions,
     getters
   }
   ```

4. **如需暴露顶层 getter**，再到 `src/store/getters.js` 加一行：
   ```js
   <name>List: (state) => state.<name>.list,
   ```

5. **在组件中使用示例**（提示用户，不修改业务文件）：
   ```js
   // Options API
   computed: {
     list() { return this.$store.state.<name>.list }
   },
   methods: {
     load() { this.$store.dispatch('<name>/fetchList', { page: 1 }) }
   }

   // <script setup lang="ts"> 内
   // 注意：本项目是 vuex 3，没有 useStore，直接导入单例
   import { computed } from 'vue'
   import store from '@/store'
   const list = computed(() => store.state.<name>.list)
   store.dispatch('<name>/fetchList', { page: 1 })
   ```

## 校验

- `rg "modules/<name>" src/store` 看是否需要手动注册
- `rg "'<name>/" src` 看命名空间是否已被占用，避免冲突

## 不要做的事

- 不要把多个无关业务合并到一个 module；按业务拆开（参考 `business.js / user.js / appConfig.js`）
- 不要在 mutation 里写异步逻辑，异步只能在 action
- 不要为简单组件局部状态新建 store module；用 `ref/reactive` 即可
- 不要给 module 内字段挂 `Vue.set/this.$set`，新版 Vue 2.7 + Vuex 已不需要
