<!-- 查看客户信息侧滑页面 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-signing-check-contract-drawer" :append-to-body="true" :before-close="handleClose" :direction="direction" :show-close="true"
      size="1000px" :title="formData.title" v-model="drawer">
      <template #header>
        <div class="invoice-title">
          <div class="invoice-header">
            <div class="invoice-left">
              <div class="invoice-logo"><i class="icon iconfont iconhetong"></i></div>
            </div>
            <div v-if="drawer" class="invoice-right">
              <div class="txt1 over-text">
                {{ dataInfo.doc_name || '--' }}
              </div>

              <div class="txt2">
                <span class="title"> 签约状态：</span>
                <div v-if="statusList[dataInfo.status]" class="dictionaries-tag" :style="{
                  color: statusList[dataInfo.status].color || '#1890ff',
                  background:
                    getColorFn(statusList[dataInfo.status].color, '0.1')

                }" >{{ statusList[dataInfo.status].name }}</div>


                <span class="title ml20">业务员：</span><span class="weight mr20">{{ dataInfo.admin ? dataInfo.admin.name : '--'
                }}</span>
                <span class="title">合同编号：</span><span class="weight mr20">{{ dataInfo.doc_no || '--' }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <el-tabs v-if="tabData.length" v-model="tabIndex" :tab-position="tabPosition" type="border-card"
        @tab-click="handleClick">
        <el-tab-pane v-for="item in tabData" :key="item.value" :label="item.label" :name="item.value" />
      </el-tabs>

      <div class="contract-body">
        <div v-show="tabIndex === '1'">
          <infoItem :dataInfo="dataInfo"></infoItem>
          <!-- 审批流程 -->
          <process-from :examine-data="examineData" v-if="dataInfo.approve_id !== 0"></process-from>
        </div>
        <div v-show="tabIndex === '2'">
          <div class="title-box">
            <div class="title-16">关联订单</div>
            <el-button type="primary" class="mb10 mt10 pointer" @click="openOrderList">关联订单</el-button>
          </div>
          <paymentTable ref="paymentTable" :list="list" :type="`check`" :selectionIsShow="false" @deleteFn="deleteFn">
          </paymentTable>
        </div>
        <div class="mt20">
          <dynamic-record v-show="tabIndex === '3'" ref="dynamicRecord" :formInfo="formInfo"></dynamic-record>
        </div>
      </div>

    </el-drawer>
    <!-- 关联订单 -->
    <paymentTableDialog ref="paymentTableDialog" @getTableData="getOrderList"></paymentTableDialog>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { getColor } from '@/utils/format.ts'
import { contractDocDetailApi, contractDocOrdersApi,contractLinkOrderApi } from '@/api/contractSign.ts'
export default {
  name: 'detailsDrawer',
  components: {
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
    record: defineAsyncComponent(() => import('@/views/customer/list/components/record')),
    contract: defineAsyncComponent(() => import('@/views/customer/list/components/contract')),
    paymentTable: defineAsyncComponent(() => import('./paymentTable.vue')),
    processFrom: defineAsyncComponent(() => import('@/views/user/examine/components/detailProcecss')),
    paymentTableDialog: defineAsyncComponent(() => import('./paymentTableDialog.vue')),
    dynamicRecord: defineAsyncComponent(() => import('@/views/customer/list/components/dynamicRecord')),
    infoItem: defineAsyncComponent(() => import('@/views/customer/signing/components/infoItem'))
  },
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dataInfo: {},
      rowData: {},
      examineData: {
      },
      id: 0,
      types: '',
      drawer: false,
      direction: 'rtl',
      tabPosition: 'top',
      tabIndex: '1',
      tabNumber: 1,
      tabData: [
        { value: '1', label: '基本信息' },
        { value: '2', label: '订单记录' },
        { value: '3', label: '动态记录' }
      ],
      statusList: {
        '-1': {
          name: '审批驳回',
          color: '#ED4014',
        },
        '0': {
          name: '待处理',
          color: '#FFC107',
        },
        '1': {
          name: '待审核',
          color: '#409EFF',
        },
        '2': {
          name: '待签约',
          color: '#67C23A',
        },
        '3': {
          name: '已签约',
          color: '#409EFF',
        },
        '4': {
          name: '已拒绝',
          color: '#909399',
        },
        '5': {
          name: '已过期',
          color: '#909399',
        },
        '6': {
          name: '已撤销',
          color: '#909399',
        },
      },
      remindConfig: {},
      formInfo: {},
      list: []
    }
  },

  methods: {
    async getDetails(id) {
      const result = await contractDocDetailApi(id)
      this.dataInfo = result.data

      // 确保 sign_file 是数组格式
      this.dataInfo.sign_file = Array.isArray(this.dataInfo.sign_file) ? this.dataInfo.sign_file : [this.dataInfo.sign_file]
      // 确保 attach 也是数组格式
      this.dataInfo.attach = Array.isArray(this.dataInfo.attach) ? this.dataInfo.attach : (this.dataInfo.attach ? [this.dataInfo.attach] : [])
      this.dataInfo.link_type = 'contract_doc'
      this.formInfo = {
        data: this.dataInfo,
        link_type: this.dataInfo.link_type
      }
      this.examineData.users = result.data.approve
      // this.examineData.rules = result.data.rules


    },

    getColorFn(color, opacity) {
      return getColor(color, opacity)
    },



    deleteFn(ids) {
      contractLinkOrderApi(this.id, {cid:ids}).then(res => {
     
        this.getOrderList()

      })
    },
    handleClose() {
      this.drawer = false
      this.list = []
      this.formInfo = {}
      this.tabIndex = '1'
    },

    openBox(row) {
      this.id = row.id
      this.rowData = row
      this.getDetails(row.id)
      this.drawer = true
    },

    // 关联订单
    getOrderList() {
      contractDocOrdersApi(this.id).then(res => {
        this.list = res.data.list
      })
    },

    openOrderList() {
      this.$refs.paymentTableDialog.openBox(this.dataInfo)
    },

    // 点击tab切换 
    handleClick(event) {
      this.tabNumber = Number(event.paneName)
      if (this.tabNumber == 2) {
        // 获取关联订单
        this.getOrderList()

      } else if (this.tabNumber == 3) {
        setTimeout(() => {
          this.$refs.dynamicRecord.getTableData()
        }, 100)
      }
      if (this.$refs.oaForm) {
        this.$refs.oaForm.clearClick()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-tabs__item.is-active) {
  border-right-color: transparent !important;
  border-left-color: transparent !important;

  &::after {
    content: '';
    height: 2px;
    width: 100%;
    background-color: #1890ff;
    position: absolute;
    left: 0;
    top: 0;
  }
}

.dictionaries-tag {
  display: inline-block;
  margin: 0;
  box-sizing: border-box;
  height: 24px;
  line-height: 24px;
  padding: 0 8px;
  text-align: center;
  font-size: 12px;
  margin-top: 8px;
  border-radius: 3px;
}

.title-box {
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.weight {
  font-weight: 400;
  color: #303133;
}

:deep(.el-tabs__item) {
  line-height: 40px !important;
}

:deep(.el-tabs__header) {
  background-color: #f7fbff;
  border-bottom: none;
}

:deep(.el-tabs__nav-wrap::after) {
  height: 0;
}

:deep(.el-tabs__active-bar) {
  top: 0;
}

.el-tabs--border-card {
  height: 39px;
  position: fixed;
  top: 80px;
  width: 100%;
  z-index: 4;
  background-color: transparent;
  border: none;
  box-shadow: none;
}

.invoice-title {
  .invoice-header {
    display: flex;
    align-items: center;

    .invoice-left {
      width: 48px;
      margin-right: 10px;

      .invoice-logo {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #1890ff;
        border-radius: 4px;

        i {
          color: #ffffff;
          font-size: 30px;
        }
      }
    }

    .invoice-right {
      width: calc(100% - 55px);
    }

    .txt1 {
      font-size: 16px;
      font-weight: bold;
      color: rgba(0, 0, 0, 0.85);
    }

    .title {
      font-size: 14px;
      color: #999999;
      font-weight: 400;
    }

  }
}

.contract-body {
  margin-top: 60px;
  height: 100%;
  padding: 0 20px;
  // padding-top: 20px;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-signing-check-contract-drawer .el-drawer__body {
  padding-bottom: 50px;
}
.customer-signing-check-contract-drawer .el-drawer__header {
  height: 80px;
  padding: 14px 18px;
}
</style>
