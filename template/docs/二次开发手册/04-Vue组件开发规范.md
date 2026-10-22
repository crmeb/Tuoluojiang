# 四、Vue 组件开发规范

## 4.1 组件结构顺序

```vue
<template>
  <!-- 模板内容 -->
</template>

<script>
// 1. 导入语句
import { mapState, mapActions } from 'vuex'
import { userListApi } from '@/api/user'

export default {
  // 2. 组件名称（必须）
  name: 'UserList',

  // 3. 组件注册
  components: { UserForm },

  // 4. Props 定义
  props: {
    userId: { type: Number, default: 0 }
  },

  // 5. 数据定义
  data() {
    return { list: [], loading: false }
  },

  // 6. 计算属性
  computed: {
    ...mapState('user', ['userInfo']),
    isEmpty() { return this.list.length === 0 }
  },

  // 7. 监听器
  watch: {
    userId(val) { this.getList() }
  },

  // 8. 生命周期钩子
  created() { this.getList() },
  mounted() {},
  beforeDestroy() {},

  // 9. 方法
  methods: {
    ...mapActions('user', ['fetchUserInfo']),
    async getList() {
      this.loading = true
      const res = await userListApi()
      if (res.status === 200) {
        this.list = res.data.list
      }
      this.loading = false
    }
  }
}
</script>

<style lang="scss" scoped>
// 样式内容
</style>
```

## 4.2 Props 定义规范

```javascript
// ✅ 正确示例
props: {
  status: { type: String, required: true },
  value: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },      // 对象/数组必须用工厂函数
  config: { type: Object, default: () => ({}) },
  visible: { type: Boolean, default: false },
  type: {
    type: String,
    validator: (val) => ['primary', 'success', 'warning', 'danger'].includes(val)
  }
}

// ❌ 错误示例
props: ['status', 'value']  // 不要使用数组形式
props: {
  options: { type: Array, default: [] }  // 对象/数组不能直接使用字面量
}
```

## 4.3 事件处理规范

```javascript
methods: {
  // 事件处理方法以 handle 开头
  handleClick(row) {
    this.currentRow = row
    this.dialogVisible = true
  },
  handleDelete(row) {
    this.$modalSure(`确定删除"${row.name}"吗？`).then(() => {
      this.deleteRow(row.id)
    })
  },
  // 内部方法使用下划线前缀（可选）
  _validateForm() {
    return this.$refs.form.validate()
  }
}
```

## 4.4 组件通信规范

```javascript
// 父子组件通信
// 父组件
<user-form :user="currentUser" @update="handleUpdate" @close="handleClose" />

// 子组件
this.$emit('update', data)
this.$emit('close')

// 跨组件通信（EventBus）
// 发送
this.$bus.$emit('refresh-list', data)

// 接收（在 created 中注册）
this.$bus.$on('refresh-list', this.handleRefresh)

// 销毁（在 beforeDestroy 中移除）
this.$bus.$off('refresh-list', this.handleRefresh)
```

## 4.5 组件注释规范

```javascript
/**
 * 用户列表组件
 * @description 用于展示和管理用户列表，支持搜索、新增、编辑、删除
 * @author 开发者姓名
 */
export default {
  name: 'UserList',
  methods: {
    /**
     * 获取用户列表
     * @param {Object} params 查询参数
     * @returns {Promise}
     */
    async getList(params = {}) {}
  }
}
```

---

[上一章：目录结构规范](./03-目录结构规范.md) | [返回目录](./README.md) | [下一章：API 接口调用规范](./05-API接口调用规范.md)
