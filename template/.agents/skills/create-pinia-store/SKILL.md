---
name: create-pinia-store
description: 在 src/stores/ 下生成 Pinia setup store（TypeScript），并说明 Options API / <script setup> / 组件外代码的调用方式。当用户说"加一个 pinia store / 全局状态 / 跨页共享状态"时触发。
---

# create-pinia-store

为本项目（Pinia + TypeScript setup store）生成一个新的全局状态，遵循 `src/stores/*.ts` 现有风格。

## 先判断要不要进全局 store

全局 store 只放**跨页面 / 跨子树**共享、或需要在路由守卫 / 请求层等组件外读取的状态。以下情况不要建 store：

- 只在单个页面或组件内使用 → 组件内 `ref` / `reactive` / `data()`
- 只在一棵组件子树内共享 → 祖先 `provide`、后代 `inject`（事件用 `@/utils/eventHub`）
- 只是某个页面的请求缓存 → 由页面持有缓存实例（参考 `@/utils/dictTreeCache`）

## 项目约定

1. **目录**：`src/stores/<name>.ts`，导出 `use<Name>Store`，store id 用小驼峰 `<name>`。不存在自动注册，按需 import。
2. **写法**：setup store（`defineStore('<name>', () => { ... })`），**所有 state 都要在 return 里暴露**（devtools / `$reset` 依赖）。
3. **类型**：state 用 `ref<T>()` 标注；接口数据按 `@/types/api` 的 `ApiResult<T>` 标注 await 结果，只列前端实际读取的字段，其余用索引签名兜底，不写 `any`。
4. **持久化**：
   - 用户态、企业信息等跨刷新保留 → `localStorage`（读取用 `@/utils/storage` 的 `getStorageJson` 兜底解析）
   - UI 偏好（侧边栏开合等）→ `js-cookie`
   - 临时状态（loading、弹窗开关）→ 仅内存
5. **修改状态**：简单赋值直接写 `store.xxx = value`；带副作用（落盘、联动其他 store）的才封装成函数。
6. **登录态相关**：退出登录时需要清空的 store 提供 `reset()`，并在 `stores/user.ts` 的 `resetUserState` 里调用。

## 模板

```ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { xxxListApi } from '@/api/<scope>'
import type { ApiResult } from '@/types/api'

export interface XxxItem {
  id: number
  name: string
  [key: string]: unknown
}

/** 一句话说明这份状态为什么需要全局共享 */
export const useXxxStore = defineStore('xxx', () => {
  const list = ref<XxxItem[]>([])
  const loaded = ref(false)
  // 进行中的请求，用于并发去重
  let pending: Promise<XxxItem[]> | null = null

  function load({ force = false } = {}): Promise<XxxItem[]> {
    if (loaded.value && !force) return Promise.resolve(list.value)
    if (pending && !force) return pending
    const task: Promise<XxxItem[]> = xxxListApi({}).then((res: ApiResult<XxxItem[]>) => {
      list.value = res.data || []
      loaded.value = true
      return list.value
    })
    pending = task
    task.finally(() => {
      if (pending === task) pending = null
    })
    return task
  }

  function reset() {
    list.value = []
    loaded.value = false
    pending = null
  }

  return { list, loaded, load, reset }
})
```

## 调用方式

```js
// Options API：整个 store 挂到 this.xxxStore（mapStores 生成的名字是 id + Store）
import { mapStores } from 'pinia'
import { useXxxStore } from '@/stores/xxx'
export default {
  computed: {
    ...mapStores(useXxxStore)
  },
  methods: {
    refresh() {
      this.xxxStore.load({ force: true })
    }
  }
}
// 只读少量字段可用 mapState(useXxxStore, ['list'])；方法用 mapActions
// 注意：computed 在 data() 之后才建立，data() 里要取 store 值请直接调用 useXxxStore()
```

```ts
// <script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useXxxStore } from '@/stores/xxx'
const xxxStore = useXxxStore()
const { list } = storeToRefs(xxxStore) // 解构 state 需 storeToRefs 保持响应式
xxxStore.load()
```

```js
// 组件外（路由守卫、请求层、指令、工具函数）：显式传入 pinia 实例
import pinia from '@/stores'
import { useXxxStore } from '@/stores/xxx'
useXxxStore(pinia).load()
```

## 校验

- `pnpm typecheck`
- `rg "defineStore\('<name>'" src` 确认 id 未被占用

## 不要做的事

- 不要把多个无关业务合并到一个 store；按业务拆开（参考 `user.ts / appConfig.ts / warehouse.ts`）
- 不要在模块顶层调用 `useXxxStore()`（此时 pinia 可能尚未安装）；在函数内调用
- 不要解构 store 的 state 而不用 `storeToRefs`，会丢失响应式
- 不要为组件局部状态新建 store
