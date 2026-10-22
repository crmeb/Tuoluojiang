# 五、API 接口调用规范

## 5.1 request 封装说明

项目在 `src/api/request.js` 中封装了 Axios，提供以下特性：

- **请求去重**: 自动拦截短时间内重复请求
- **自动认证**: 自动携带 Token
- **统一响应处理**: 自动处理 Token 过期、错误提示
- **快捷方法**: `request.get/post/put/delete`

## 5.2 API 文件编写规范

```javascript
// src/api/asset.js
import request from './request'

/**
 * @description 资产列表
 * @param {Object} params { page, limit, keyword }
 */
export function assetListApi(params) {
  return request.get('asset', params)
}

/**
 * @description 资产详情
 * @param {Number} id 资产ID
 */
export function assetInfoApi(id) {
  return request.get(`asset/${id}`)
}

/**
 * @description 新增资产
 * @param {Object} data 资产信息
 */
export function assetCreateApi(data) {
  return request.post('asset', data)
}

/**
 * @description 修改资产
 * @param {Number} id 资产ID
 * @param {Object} data 修改内容
 */
export function assetUpdateApi(id, data) {
  return request.put(`asset/${id}`, data)
}

/**
 * @description 删除资产
 * @param {Number} id 资产ID
 */
export function assetDeleteApi(id) {
  return request.delete(`asset/${id}`)
}
```

## 5.3 页面中调用 API

```javascript
import { assetListApi, assetCreateApi } from '@/api/asset'

export default {
  methods: {
    // ✅ 正确示例
    async getList() {
      this.loading = true
      try {
        const res = await assetListApi(this.pagination)
        if (res.status === 200) {
          this.list = res.data.list
          this.total = res.data.count
        }
      } catch (error) {
        console.error('获取列表失败', error)
      } finally {
        this.loading = false
      }
    },

    // ✅ 允许重复请求的场景
    async polling() {
      const res = await assetListApi({}, { allowRepeat: true })
    }
  }
}
```

## 5.4 响应状态码处理

| 状态码 | 说明 | 处理方式 |
|--------|------|----------|
| 200 | 成功 | 正常处理数据 |
| 400 | 业务错误 | 框架自动提示，需判断 status |
| 410000/410001/410002/40000/410003 | Token 失效 | 框架自动跳转登录页 |

```javascript
// 处理业务错误
const res = await assetCreateApi(this.form)
if (res.status === 200) {
  this.$message.success('创建成功')
  this.getList()
} else if (res.status === 400) {
  // 业务错误，框架已自动提示
  console.log('业务错误:', res.message)
}
```

## 5.5 使用 $modalForm 动态表单

```javascript
// 后端驱动的动态表单
import { assetFormCreateApi, assetFormEditApi } from '@/api/asset'

export default {
  methods: {
    // 新增
    handleAdd() {
      this.$modalForm(assetFormCreateApi()).then(() => {
        this.$message.success('创建成功')
        this.getList()
      })
    },

    // 编辑
    handleEdit(row) {
      this.$modalForm(assetFormEditApi(row.id)).then(() => {
        this.$message.success('修改成功')
        this.getList()
      })
    }
  }
}
```

## 5.6 API 函数命名模板

| 操作 | 命名格式 | 示例 |
|------|----------|------|
| 列表查询 | xxxListApi | `userListApi` |
| 详情查询 | xxxInfoApi | `userInfoApi` |
| 新增 | xxxCreateApi | `userCreateApi` |
| 修改 | xxxUpdateApi | `userUpdateApi` |
| 删除 | xxxDeleteApi | `userDeleteApi` |
| 批量删除 | xxBatchDeleteApi | `userBatchDeleteApi` |
| 导出 | xxxExportApi | `userExportApi` |
| 导入 | xxxImportApi | `userImportApi` |

---

[上一章：Vue 组件开发规范](./04-Vue组件开发规范.md) | [返回目录](./README.md) | [下一章：Vuex 状态管理规范](./06-Vuex状态管理规范.md)
