<template>
  <div>
    <el-table :data="tableDataLocal" tooltip-effect="light" :header-cell-style="{ background: 'rgba(245, 245, 245, .3)' }">
      <el-table-column v-for="(item, index) in tableLabel" show-overflow-tooltip :key="index" :label="item.name">
        <template #default="scope">
          <template v-if="!item.slot">
            {{ scope.row[item.prop] }}
          </template>
          <template v-else>
            <span v-if="item.name == '任职状态'">{{ scope.row.status ? '在职' : '离职' }}</span>
          </template>
        </template>
      </el-table-column>
      <el-table-column prop="address" label="操作">
        <template #default="scope">
          <el-button type="primary" link @click="handlePut(scope.row, scope.$index)">编辑</el-button>
          <el-button type="primary" link @click="handleDelete(scope.row, scope.$index)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import modalForm from '@/libs/modal-form.ts'
import {
  enterpriseWorkEditApi,
  getWorkList,
  enterpriseWorkDeleteApi,
  enterpriseEducationApi,
  educationDeleteApi,
  getEducationList
} from '@/api/enterprise.ts'
import {
  userDeleteWork,
  userDeleteEducation,
  userPutWork,
  userPutEducation,
  userWorkList,
  userEducationList
} from '@/api/user.ts'
export default {
  emits: ['deleteWork', 'putWork'],
  name: 'Table',
  props: {
    tableData: {
      type: Array,
      default: () => {
        return []
      }
    },
    tableLabel: {
      type: Array,
      default: () => {
        return []
      }
    },
    tableName: {
      type: String,
      default: ''
    },
    personId: {
      type: [String, Number],
      default: ''
    },
    id: {
      type: [String, Number, Boolean],
      default: () => false
    },
    user: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'edit'
    }
  },
  watch: {
    // prop 由父组件更新时同步到本地副本（组件内部改写的是 tableDataLocal）
    tableData(val) {
      this.tableDataLocal = val
    }
  },
  data() {
    return {
      // Vue 3 的 prop 只读，组件内部需要改写时用本地副本
      tableDataLocal: this.tableData,
    }
  },

  methods: {
    // 删除
    handleDelete(row, index) {
      modalSure('删除该经历吗').then(() => {
        if (this.user == '个人简历') {
          if (this.tableName === 'works') {
            userDeleteWork(row.id).then((res) => {
              this.tableDataLocal.splice(index, 1)
            })
          }
          if (this.tableName === 'education') {
            userDeleteEducation(row.id).then((res) => {
              this.tableDataLocal.splice(index, 1)
            })
          }
        } else if (this.type === 'edit') {
          if (this.tableName === 'works') {
            let data = {
              card_id: this.id
            }
            enterpriseWorkDeleteApi(row.id, data).then((res) => {
              this.tableDataLocal.splice(index, 1)
            })
          }

          if (this.tableName === 'education') {
            educationDeleteApi(row.id).then((res) => {
              this.tableDataLocal.splice(index, 1)
            })
          }
        } else if (this.type === 'add') {
          if (this.tableName === 'works') {
            this.$emit('deleteWork', row, index, this.tableName)
          }
          if (this.tableName === 'education') {
            this.$emit('deleteWork', row, index, this.tableName)
          }
        }
      })
    },
    // 编辑
    handlePut(row, index) {
      if (this.user == '个人简历') {
        if (this.tableName === 'works') {
          modalForm(userPutWork(row.id)).then((res) => {
            this.userWorkList()
          })
        }
        if (this.tableName === 'education') {
          modalForm(userPutEducation(row.id)).then((res) => {
            this.userEducationList()
          })
        }
      } else if (this.type === 'edit') {
        if (this.tableName === 'works') {
          modalForm(enterpriseWorkEditApi(row.id)).then((res) => {
            this.getEditWorkList()
          })
        }
        if (this.tableName === 'education') {
          modalForm(enterpriseEducationApi(row.id)).then((res) => {
            this.getEducationList()
          })
        }
      } else if (this.type === 'add') {
        if (this.tableName === 'works') {
          this.$emit('putWork', row, index, this.tableName)
        }
        if (this.tableName === 'education') {
          this.$emit('putWork', row, index, this.tableName)
        }
      }
    },

    // 刷新工作经历列表
    async userWorkList() {
      const result = await userWorkList()
      this.tableDataLocal = result.data.list
    },

    // 刷新个人档案工作经历列表
    async getEditWorkList() {
      let data = {
        user_id: this.id
      }
      const result = await getWorkList(data)
      this.tableDataLocal = result.data.list
    },

    // 刷新个人档案教育经历列表
    async getEducationList() {
      let data = {
        user_id: this.id
      }
      const result = await getEducationList(data)
      this.tableDataLocal = result.data.list
    },

    // 刷新教育经历列表
    async userEducationList() {
      const result = await userEducationList()
      this.tableDataLocal = result.data.list
    }
  }
}
</script>

<style>
.el-tooltip:where(.el-popper:not(.is-pure, .el-popover)) {
  border: none !important;
  font-size: 13px !important;
  line-height: 22px;
  font-family: Helvetica Neue, Helvetica, PingFang SC, Hiragino Sans GB, Microsoft YaHei, Arial, sans-serif;
  box-shadow: 0px 2px 14px 0px rgba(0, 0, 0, 0.1) !important;
}
</style>
<style scoped lang="scss">
:deep(.el-table .cell) {
  padding-right: 0;
}
:deep(.el-textarea__inner) {
  width: 92%;
}
</style>
