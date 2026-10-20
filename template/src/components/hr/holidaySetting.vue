<!-- @FileDescription: 人事-审批设置-请假控件组对应的配置组件 -->
<template>
  <div class="composite-selector">
    <el-table :data="tableData">
      <el-table-column prop="name" label="假期类型" min-width="180">
        <template #default="scope">
          <div class="flex">
            <div class="ml10">{{ scope.row.name }}</div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="refuse" label="请假单位" min-width="100">
        <template #default="scope">
          <span>{{ scope.row.duration_type == 1 ? '按小时' : '按天' }}</span>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { approveHolidayTypeApi } from '@/api/business.ts'
export default {
  emits: ['update:value', 'update:modelValue'],
  name: 'HolidaySetting',
  props: {
    modelValue: {
      default: undefined,
      type: String
    }
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
  },
  data() {
    return {
      tableData: [],
      title: ''
    }
  },
  created() {
    this.getTableData()
  },
  methods: {
    handleSelectChange(value) {
      this.$emit('update:value', value)
    },
    handleInputChange(value) {
      this.$emit('update:modelValue', value)
    },
    // 获取假期类型列表
    async getTableData() {
      const result = await approveHolidayTypeApi()
      this.tableData = result.data.list
    }
  }
}
</script>
