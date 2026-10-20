<!-- 订单发票-关联付款单表格组件 -->
<template>
  <div class="paymentTable">
    <el-table
      ref="paymentTable"
      :data="table"
      :key="editLocal"
      row-key="id"
      style="width: 100%"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="55" v-if="editLocal === ''"> </el-table-column>
      <el-table-column prop="bill_no" label="付款单号" min-width="100"> </el-table-column>
      <el-table-column prop="treaty.contract_name" label="订单名称" min-width="100"> </el-table-column>

      <el-table-column prop="types" label="业务类型" min-width="100">
        <template #default="scope">
          <span v-if="scope.row.types === 0">回款记录</span>
          <span v-if="scope.row.types === 1"
            >续费记录 <span v-if="scope.row.renew.title">-</span>{{ scope.row.renew.title }}</span
          >
        </template>
      </el-table-column>
      <el-table-column prop="num" label="付款金额(元)" min-width="100"> </el-table-column>
      <el-table-column prop="status" label="发票状态" min-width="100">
        <template #default="scope">
        
          <div v-if="scope.row.status != undefined">
            <span 
              :class="`table-btn ${getStatusClass(scope.row.status)}`"
            >
              {{ getStatusText(scope.row.status) }}
            </span>
          </div>
          <div v-else>--</div>
        </template>
      </el-table-column>

      <el-table-column prop="card.name" label="创建人" min-width="90"> </el-table-column>
      <el-table-column prop="created_at" label="创建时间" min-width="150"> </el-table-column>

      <el-table-column fixed="right" label="操作" width="80" v-if="editLocal !== ''">
        <template #default="scope">
          <el-button @click="deleteFn(scope.row)" type="primary" link>移除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-image-viewer v-if="isImage" @close="closeImageViewer" :url-list="srcList" />
  </div>
</template>
<script>
import { modalSure } from '@/libs/public.ts'
import { ElImageViewer } from 'element-plus'
export default {
  emits: ['handleSelectionFn', 'totalFn'],
  name: '',
  components: { ElImageViewer },
  props: ['tableData', 'edit', 'ids'],
  data() {
    return {
      // Vue 3 的 prop 只读，组件内部需要改写时用本地副本
      editLocal: this.edit,
      table: this.tableData,
      isImage: false,
      srcList: [],
      multipleSelection: [],
      total: 0,
      isRestoringSelection: false
    }
  },

  watch: {
    // prop 由父组件更新时同步到本地副本（组件内部改写的是 editLocal）
    edit(val) {
      this.editLocal = val
    },
    tableData: {
      handler(nVal) {
        this.table = nVal || []
        this.restoreSelection()
      }
    },
    ids: {
      handler(nVal) {
        this.multipleSelection = Array.isArray(nVal) ? nVal : []
        this.restoreSelection()
      },
      deep: true
    }
  },
  mounted() {
    this.restoreSelection()
  },

  methods: {
    handleSelectionChange(val) {
      if (this.isRestoringSelection) return
      this.$emit('handleSelectionFn', val)
    },
    restoreSelection() {
      const selectedIds = Array.isArray(this.ids) ? this.ids : []

      this.$nextTick(() => {
        const tableRef = this.$refs.paymentTable
        if (!tableRef || this.editLocal !== '') return

        this.isRestoringSelection = true
        tableRef.clearSelection()

        if (selectedIds.length) {
          const selectedIdSet = new Set(selectedIds.map((id) => String(id)))
          this.table.forEach((row) => {
            if (selectedIdSet.has(String(row.id))) {
              tableRef.toggleRowSelection(row, true)
            }
          })
        }

        this.$nextTick(() => {
          this.isRestoringSelection = false
        })
      })
    },

    closeImageViewer() {
      this.isImage = false
      this.srcList = []
    },
    getStatusClass(status) {
      const statusMap = {
        '-1': 'blue',
        '0': 'gray',
        '1': 'yellow',
        '2': 'red',
        '3': 'green',
        '4': 'red',
        '5': 'gray',
        '6': 'yellow'
      }
      return statusMap[status] || ''
    },
    getStatusText(status) {
      const textMap = {
        '-1': '开票撤回',
        '0': '待开票',
        '1': '已开票',
        '2': '已拒绝',
        '3': '申请冲红',
        '4': '同意冲红',
        '5': '拒绝冲红',
        '6': '冲红撤回'
      }
      return textMap[status] || ''
    },
    handlePictureCardPreview(val) {
      this.srcList.push(val)
      this.isImage = true
    },
    deleteFn(row) {
      modalSure('确认移除此付款订单吗').then(() => {
        this.table = this.table.filter((item) => {
          return item.id != row.id
        })

        this.$emit('totalFn', this.total, this.table)
      })
    }
  }
}
</script>
<style scoped lang="scss">
.img {
  width: 40px;
  height: 40px;
}
.table-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 22px;
  border-radius: 3px;
  font-size: 13px;

  &.blue {
    background: rgba(24, 144, 255, 0.05);
    color: #1890ff;
  }

  &.yellow {
    background: rgba(255, 153, 0, 0.05);
    color: #ff9900;
  }
  &.red {
    background: rgba(255, 153, 0, 0.05);
    color: #ed4014;
  }

  &.green {
    background: rgba(0, 192, 80, 0.05);
    color: #00c050;
  }

  &.gray {
    background: rgba(153, 153, 153, 0.05);
    color: #999999;
  }
}
</style>
