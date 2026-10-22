# 六、Vuex 状态管理规范

## 6.1 模块自动注册

Store 模块通过 `require.context` 自动注册，新建模块只需：

1. 在 `src/store/modules/` 下创建 `.js` 文件
2. 导出标准 Vuex 模块对象
3. **必须设置 `namespaced: true`**

## 6.2 Store 模块结构

```javascript
// src/store/modules/asset.js
const state = {
  assetList: [],
  currentAsset: null
}

const mutations = {
  // 使用大写下划线命名
  SET_ASSET_LIST: (state, list) => {
    state.assetList = list
  },
  SET_CURRENT_ASSET: (state, asset) => {
    state.currentAsset = asset
  }
}

const actions = {
  // 获取资产列表
  async fetchAssetList({ commit }, params) {
    const { assetListApi } = await import('@/api/asset')
    const res = await assetListApi(params)
    if (res.status === 200) {
      commit('SET_ASSET_LIST', res.data.list)
    }
    return res
  }
}

// 必须设置命名空间
export default {
  namespaced: true,
  state,
  mutations,
  actions
}
```

## 6.3 在组件中使用 Store

```javascript
import { mapState, mapActions, mapMutations } from 'vuex'

export default {
  computed: {
    // 使用 mapState
    ...mapState('asset', ['assetList', 'currentAsset']),
    
    // 或通过 getters
    assetList() {
      return this.$store.getters.assetList
    }
  },
  
  methods: {
    // 使用 mapActions
    ...mapActions('asset', ['fetchAssetList']),
    
    // 使用 mapMutations
    ...mapMutations('asset', ['SET_CURRENT_ASSET']),
    
    // 直接调用
    async getList() {
      await this.fetchAssetList({ page: 1 })
    }
  }
}
```

## 6.4 添加全局 Getters

```javascript
// src/store/getters.js
const getters = {
  // 已有的 getters
  token: (state) => state.user.token,
  userInfo: (state) => state.user.userInfo,
  
  // 新增 getter
  assetList: (state) => state.asset.assetList
}

export default getters
```

## 6.5 命名规范

### State 命名

```javascript
// 使用名词，小驼峰
const state = {
  userInfo: null,
  userList: [],
  loading: false
}
```

### Mutation 命名

```javascript
// 使用大写下划线
const mutations = {
  SET_USER_INFO: (state, info) => { state.userInfo = info },
  SET_USER_LIST: (state, list) => { state.userList = list },
  RESET_STATE: (state) => { state.userInfo = null }
}
```

### Action 命名

```javascript
// 使用小驼峰，动词开头
const actions = {
  async fetchUserInfo({ commit }, id) { /* ... */ },
  async updateUser({ commit }, data) { /* ... */ }
}
```

---

[上一章：API 接口调用规范](./05-API接口调用规范.md) | [返回目录](./README.md) | [下一章：路由配置规范](./07-路由配置规范.md)
