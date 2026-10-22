<template>
  <div>
    <el-table
      v-if="type == 5"
      ref="oddsTable"
      :key="type"
      :data="list"
      style="width: 100%"
      class="mt20"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="55" />
      <el-table-column prop="odds_no" label="商机编号" width="180" />
      <el-table-column label="商机类型" width="120">
        <template #default="scope">
          <div
            class="dictionaries-tag over-text"
            :style="{
              color: scope.row.types.color || '#1890ff',
              background: scope.row.types.color
                ? getColorFn(scope.row.types.color, '0.1')
                : getColorFn('#1890ff', '0.1')
            }"
          >
            {{ scope.row.types.name }}
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="odds_customer" label="客户名称" width="120" />
      <!-- <el-table-column prop="total_amount" label="商机金额（元）" /> -->
      <el-table-column prop="status" label="商机状态" width="120">
        <template #default="scope">
          <div
            class="dictionaries-tag over-text"
            :style="{
              color: scope.row.status.color || '#1890ff',
              background: scope.row.status.color
                ? getColorFn(scope.row.status.color, '0.1')
                : getColorFn('#1890ff', '0.1')
            }"
          >
            {{ scope.row.status.name }}
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="salesman.name" label="业务员" width="120" />
      <el-table-column prop="created_at" label="创建日期" />
    </el-table>

    <el-table
      v-else
      :data="list"
      ref="contractTable"
      :key="type"
      style="width: 100%"
      class="mt20"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="55" />
      <el-table-column prop="contract_no" label="订单编号" width="180" />
      <el-table-column prop="contract_price" label="订单金额（元）" />
      <el-table-column prop="price" label="付款状态" width="120">
        <template #default="scope">
          <span class="pointer color-success" v-if="parseFloat(scope.row.surplus) === 0">已结清</span>
          <span class="pointer color-warning" v-else>未结清</span>
          <!-- <div class="dictionaries-tag over-text" :style="{
                    color: scope.row.contract_status.color || '#1890ff',
                    background: scope.row.contract_status.color
                        ? getColorFn(scope.row.contract_status.color, '0.1')
                        : getColorFn('#1890ff', '0.1')
                }">
                    {{ scope.row.contract_status.name }}
                </div> -->
        </template>
      </el-table-column>
      <el-table-column prop="contract_customer" label="客户名称" width="120" />

      <el-table-column prop="salesman.name" label="创建人" width="120">
        <template #default="scope">
          {{ scope.row.salesman ? scope.row.salesman.name : '--' }}
        </template>
      </el-table-column>

      <el-table-column prop="created_at" label="创建日期">
        <template #default="scope">
          {{ scope.row.created_at || '--' }}
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>
<script>
import { getColor } from '@/utils/format'
export default {
  name: 'SignProduct',
  props: {
    list: {
      type: Array,
      default: () => []
    },
    type: {
      type: String,
      default: '5`'
    }
  },
  data() {
    return {
      selectedRows: []
    }
  },
  methods: {
    setChecked(val) {
      val = val.map((item) => Number(item))

      if (this.type == 5) {
        if (this.$refs.oddsTable) {
          this.list.forEach((row) => {
            if (val.includes(row.id)) {
              this.$refs.oddsTable.toggleRowSelection(row, true)
            }
          })
        }
      } else {
        if (this.$refs.contractTable) {
          this.list.forEach((row) => {
            if (val.includes(row.contract_id)) {
              this.$refs.contractTable.toggleRowSelection(row, true)
            }
          })
        }
      }
    },
    handleSelectionChange(val) {
      this.$emit('selectionChange', val)
    },
    getColorFn(color, opacity) {
      return getColor(color, opacity)
    }
  }
}
</script>
<style scoped>
.sign-product {
  padding: 20px;
}

.dictionaries-tag {
  max-width: 100px;
  display: inline-block;
  margin: 0;
  box-sizing: border-box;
  height: 24px;
  padding: 0 8px;
  text-align: center;
  font-size: 12px;
  margin-top: 8px;
  border-radius: 3px;
}
</style>
