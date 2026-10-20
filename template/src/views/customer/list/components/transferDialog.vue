<!-- 客户-订单转移弹窗组件 -->
<template>
  <div>
    <el-dialog
      :title="fromData.title"
      v-model="dialogVisible"
      :width="fromData.width"
      :before-close="handleClose"
      :close-on-click-modal="false"
    >
      <div class="body">
        <div class="mt14 el-input--small flex">
          <span class="label">接手人员：</span>
          <select-member
            :only-one="true"
            :value="userList || []"
            :placeholder="`请选择企业成员`"
            @getSelectList="getSelectList"
            style="width: 100%"
          ></select-member>
        </div>
        <div class="mt20">
          <el-checkbox-group v-model="checkList">
            <el-checkbox
              v-for="item in transfer"
              v-show="fromData.type <= item.value"
              :disabled="fromData.type === item.value"
              :key="item.value"
              :label="item.value" :value="item.value"
              >{{ item.label }}</el-checkbox
            >
          </el-checkbox-group>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose">取消</el-button>
          <el-button :loading="loading" type="primary" @click="handleAdd">确定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { customerShiftApi, clientInvoiceShiftApi, cluesShiftApi } from '@/api/enterprise.ts'
import { clientContractShiftApi, oddsShiftApi } from '@/api/client.ts'
export default {
  emits: ['handleTransfer'],
  name: 'TransferDialog',
  components: {
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member'))
  },
  props: {
    fromData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dialogVisible: false,
      userList: [],
      checkList: [],
      keyword: '',
      transfer: [
        { value: 1, label: '客户转移' },
        { value: 2, label: '订单转移' },
        { value: 3, label: '发票转移' }
      ],
      loading: false
    }
  },
  watch: {
    fromData: {
      handler(nVal) {
        this.checkList.push(nVal.type)
      },
      deep: true
    }
  },
  methods: {
    handleClose() {
      this.userList = []
      this.checkList = []
      this.dialogVisible = false
    },
    handleOpen(keyword) {
      this.keyword = keyword
      if (keyword == 'clue' || keyword === 'clue_seas') {
        this.transfer.push({ value: 5, label: '线索移交' })
      } else if (keyword === 'odds') {
        this.transfer[0].label = '商机移交'
      }

      this.dialogVisible = true
    },
    // 选择成员完成回调
    getSelectList(data) {
      this.userList = data
    },

    handleAdd() {
      if (this.userList.length <= 0) {
        this.$message.error('请选择要转移的成员')
      } else {
        const data = {
          to_uid: this.userList[0].value,
          data: this.fromData.ids
        }
        if (this.keyword === 'customer' || this.keyword == 'customer_seas') {
          data.contract = this.checkList.includes(2) ? 1 : 0
          data.invoice = this.checkList.includes(3) ? 1 : 0
          this.clientDataShift(data)
        } else if (this.keyword === 'contract') {
          data.invoice = this.checkList.includes(3) ? 1 : 0
          this.clientContractShift(data)
        } else if (this.fromData.type === 3) {
          this.clientInvoiceShift(data)
        } else if (this.keyword === 'clue' || this.keyword == 'clue_seas') {
          // 线索移交
          this.cluesShift(data)
        } else if (this.keyword === 'odds') {
          data.contract = this.checkList.includes(2) ? 1 : 0
          data.invoice = this.checkList.includes(3) ? 1 : 0
          this.oddsShift(data)
        }
      }
    },
    // 客户管理--批量转移
    clientDataShift(data) {
      this.loading = true
      customerShiftApi(data)
        .then((res) => {
          this.loading = false
          this.userList = []
          this.handleClose()
          this.$emit('handleTransfer')
          this.$refs.department.selectList = {}
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 订单管理--批量转移
    clientContractShift(data) {
      this.loading = true
      clientContractShiftApi(data)
        .then((res) => {
          this.loading = false
          this.userList = []
          this.handleClose()
          this.$emit('handleTransfer')
          this.$refs.department.selectList = {}
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 发票管理--批量转移
    clientInvoiceShift(data) {
      this.loading = true
      clientInvoiceShiftApi(data)
        .then((res) => {
          this.loading = false
          this.userList = []
          this.handleClose()
          this.$emit('handleTransfer')
          this.$refs.department.selectList = {}
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 线索管理-转移
    cluesShift(data) {
      this.loading = true
      cluesShiftApi(data)
        .then((res) => {
          this.loading = false
          this.userList = []
          this.handleClose()
          this.$emit('handleTransfer')
          this.$refs.department.selectList = {}
        })
        .catch((error) => {
          this.loading = false
        })
    },

    // 商机管理-转移
    oddsShift(data) {
      this.loading = true
      oddsShiftApi(data)
        .then((res) => {
          this.loading = false
          this.userList = []
          this.handleClose()
          this.$emit('handleTransfer')
          this.$refs.department.selectList = {}
        })
        .catch((error) => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped lang="scss">
.label {
  width: 80px;
}
.flex {
  align-items: center;
}
.el-tag {
  margin-top: 3px;
}
</style>
