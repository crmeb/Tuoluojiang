<!-- 查看客户信息侧滑页面 -->
<template>
  <div>
    <el-drawer
      :append-to-body="true"
      :before-close="handleClose"
      :direction="direction"
      :show-close="true"
      :size="DRAWER_SIZE.XL"
      :title="formData.title"
      :visible.sync="drawer"
    >
      <template #title>
        <div class="invoice-title">
          <el-row class="invoice-header">
            <el-col class="invoice-left">
              <div class="invoice-logo"><i class="icon iconfont iconhetong"></i></div>
            </el-col>
            <el-col v-if="drawer" class="invoice-right">
              <div class="txt1 over-text">
                {{ dataInfo.data ? dataInfo.data.customer_name : '--' }}
                <i class="el-icon-message-solid default-color pointer" @click="addRecord"></i>
                <span class="default-color pointer txt3" @click="addRecord">添加提醒</span>
              </div>
              <div class="txt2">
                <span v-if="workCustomer" class="wechat-info">
                  <img
                    v-default-avatar="workCustomer"
                    :src="getAvatarSrc(workCustomer)"
                    alt=""
                    class="wechat-avatar"
                  />
                  <span class="wechat-name">{{ workCustomer.name || '--' }}</span>
                  <span v-if="workCustomer.type == 2" class="color-ppt wechat-tag">{{
                    workCustomer.corp_name || '@企业微信'
                  }}</span>
                  <span v-else class="color-excel wechat-tag">@微信</span>
                </span>
                <span class="title">客户状态：</span>

                <span
                  v-if="dataInfo.data && dataInfo.data.customer_status"
                  :style="{ color: dataInfo.data.customer_status.color || '#1890ff' }"
                >
                  {{ dataInfo.data ? dataInfo.data.customer_status.name : '--' }}
                </span>
                <span class="title">{{ $t('customer.salesman') }}：</span
                ><span class="weight">{{ dataInfo.data ? dataInfo.data.salesman : '--' }}</span>
                <span class="title">添加时间：</span
                ><span class="weight">{{ dataInfo.data ? dataInfo.data.created_at : '--' }}</span>
              </div>
            </el-col>
          </el-row>
        </div>
      </template>

      <el-tabs
        v-if="tabData.length"
        v-model="tabIndex"
        :tab-position="tabPosition"
        type="border-card"
        @tab-click="handleClick"
      >
        <el-tab-pane v-for="item in tabData" :key="item.value" :name="item.value">
          <template #label>
            <span class="tab-label-wrap">
              {{ item.label }}
              <span class="tab-badge" v-if="getTabBadge(item)">({{ getTabBadge(item) }})</span>
            </span>
          </template>
        </el-tab-pane>
      </el-tabs>
      <div class="contract-body table-box" v-if="drawer">
        <!--基本信息-->
        <div v-if="tabNumber === 1" class="contract-info">
          <oaForm
            :form-info="dataInfo.form"
            :id="customInfo.id"
            :keyWord="customInfo.types"
            ref="oaForm"
            :viewMode="true"
            :btnShow="false"
            @getDetails="getDetails"
            :isShowFooter="false"
          >
          </oaForm>
        </div>
        <!--跟进记录-->
        <div v-if="tabNumber === 2" class="contract-record">
          <record ref="record" :form-info="formData" @refresh-detail="refreshCustomerDetail"></record>
        </div>
        <!--联系人-->
        <div v-if="tabNumber === 8" class="contract-list">
          <liaison
            ref="liaison"
            :customInfo="customInfo"
            :form-info="formData"
            @refresh-detail="refreshCustomerDetail"
          ></liaison>
        </div>
        <!--合同签约-->
        <div v-if="tabNumber === 11" class="contract-list">
          <sign ref="sign" :form-info="formData" @refresh-detail="refreshCustomerDetail"></sign>
        </div>
        <!--订单-->
        <div v-if="tabNumber === 3" class="contract-list">
          <contract ref="contract" :form-info="formData" @refresh-detail="refreshCustomerDetail"></contract>
        </div>
        <!--付款记录-->
        <div v-if="tabNumber === 4" class="contract-list">
          <contract-record-all
            ref="contractRecord"
            :form-info="formData"
            @refresh-detail="refreshCustomerDetail"
          ></contract-record-all>
        </div>
        <!--付款提醒-->
        <div v-if="tabNumber === 5" class="contract-remind">
          <contract-remind
            ref="contractRemind"
            :form-info="formData"
            :type="1"
            @refresh-detail="refreshCustomerDetail"
          ></contract-remind>
        </div>
        <!--发票-->
        <div v-if="tabNumber === 6" class="contract-list">
          <contract-invoice
            ref="contractInvoice"
            :form-info="formData"
            @refresh-detail="refreshCustomerDetail"
          ></contract-invoice>
        </div>
        <!-- 商机 -->
        <div v-if="tabNumber === 10" class="contract-list">
          <odds ref="odds" :form-info="formData" @refresh-detail="refreshCustomerDetail"></odds>
        </div>
        <!--附件相关-->
        <div v-if="tabNumber === 7" class="contract-list">
          <file ref="file" :form-info="formData" @refresh-detail="refreshCustomerDetail"></file>
        </div>
        <!--动态记录-->
        <div v-if="tabNumber === 9" class="contract-list">
          <dynamic-record ref="dynamicRecord" :form-info="formData"></dynamic-record>
        </div>
      </div>
    </el-drawer>
    <!-- 跟进提醒弹窗 -->
    <remind-dialog ref="remindDialog" :config="remindConfig" @change="change"></remind-dialog>
  </div>
</template>
<script>
import { chargeEditApi } from '@/api/enterprise';
import { getAvatarSrc } from '@/utils/avatar';
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules';
import { DRAWER_SIZE } from '@/constants/popupSize';

export default {
  name: 'EditCustomer',
  components: {
    uploadFile: () => import('@/components/form-common/oa-upload'),
    contractRecordAll: () => import('./contractRecordAll'),
    sign: () => import('./sign'),
    contractInvoice: () => import('@/views/customer/contract/components/contractInvoice'),
    liaison: () => import('@/views/customer/list/components/liaison'),
    odds: () => import('@/views/customer/list/components/odds'),
    record: () => import('@/views/customer/list/components/record'),
    file: () => import('@/views/customer/list/components/file'),
    contract: () => import('@/views/customer/list/components/contract'),
    contractRemind: () => import('@/views/customer/contract/components/contractRemind'),
    remindDialog: () => import('./remindDialog'),
    oaForm: () => import('@/components/customer/oaForm'),
    dynamicRecord: () => import('./dynamicRecord')
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
      DRAWER_SIZE,
      dataInfo: {},
      drawer: false,
      direction: 'rtl',
      tabPosition: 'top',
      tabIndex: '1',
      tabNumber: 1,
      customInfo: { id: 0, types: 0 },
      remindConfig: {}
    }
  },
  computed: {
    // 企微/微信客户信息，无则不展示
    workCustomer() {
      const info = this.dataInfo.data && this.dataInfo.data.work_customer
      if (!info || (!info.name && !info.avatar)) return null
      return info
    },
    tabData() {
      const isCustomerModuleEnabled = this.$store.getters['appConfig/isCustomerModuleEnabled'];

      const config = [
        { value: '1', label: this.$t('setting.info.essentialinformation') },
        { value: '2', label: this.$t('customer.followrecord'), badgeKey: 'follow_count' },
        { value: '8', label: this.$t('customer.contacts'), badgeKey: 'liaisons_count', moduleKey: CUSTOMER_MODULE_KEYS.LIAISON },
        { value: '10', label: '商机', badgeKey: 'odds_count', moduleKey: CUSTOMER_MODULE_KEYS.OPPORTUNITY },
        { value: '11', label: '合同', badgeKey: 'contract_doc_count', moduleKey: CUSTOMER_MODULE_KEYS.CONTRACT },
        { value: '3', label: '订单', badgeKey: 'contract_count', moduleKey: CUSTOMER_MODULE_KEYS.ORDER },
        { value: '4', label: '账目记录', badgeKey: 'bill_count' },
        { value: '5', label: this.$t('customer.paymentreminder'), badgeKey: 'remind_count' },
        { value: '6', label: this.$t('customer.invoice'), badgeKey: 'invoice_count', moduleKey: CUSTOMER_MODULE_KEYS.INVOICE },
        { value: '7', label: this.$t('customer.annexrelated'), badgeKey: 'file_count' },
        { value: '9', label: '动态记录', badgeKey: 'record_count' }
      ];

      return config.filter(item => {
        if ('moduleKey' in item) {
          return isCustomerModuleEnabled(item.moduleKey);
        }
        return true;
      });
    }
  },
  methods: {
    getAvatarSrc,
    getTabBadge(tabItem) {
      if (!tabItem.badgeKey || !this.dataInfo || !this.dataInfo.count) return null
      const count = this.dataInfo.count[tabItem.badgeKey]
      return count > 0 ? count : null
    },
    async getDetails(id) {
      const result = await chargeEditApi(id || this.customInfo.id)

      setTimeout(() => {
        this.dataInfo = { ...result.data }
        this.$forceUpdate()
      }, 300) // 可根据实际场景调整延迟（50-300ms）
    },
    // 数组转成字符串
    getValue(val) {
      let str = ''
      if (val == '') {
        str = '--'
      } else if (Array.isArray(val)) {
        str = val.toString()
      } else {
        str = val
      }
      return str || '--'
    },

    refreshCustomerDetail() {
      if (!this.drawer) return
      const currentId = this.customInfo.id || this.formData.data?.eid || this.formData.data?.id
      if (!currentId) return
      this.getDetails(currentId)
    },

    change() {
      if (this.$refs.record && this.$refs.record.getTableData) {
        this.$refs.record.getTableData()
      }
      this.refreshCustomerDetail()
    },
    handleClose() {
      if (this.$refs.oaForm) {
        this.$refs.oaForm.removeEvent()
      }
      this.$emit('isOkEdit')
      this.tabIndex = '1'
      this.tabNumber = 1
      this.drawer = false
    },

    openBox(id, type) {
      if (id) {
        this.getDetails(id)
        this.customInfo.id = id
      }
      if (type) {
        this.customInfo.types = type
      }
      this.drawer = true
    },

    // 点击tab切换
    handleClick(event) {
      this.tabNumber = Number(event.name)

      const actions = {
        2: () => this.$refs.record.getTableData(),
        3: () => this.$refs.contract.getTableData(),
        4: () => {
          this.$refs.contractRecord.getTableData()
          this.$refs.contractRecord.getConfigApprove()
        },
        5: () => this.$refs.contractRemind.getTableData(),
        6: () => {
          this.$refs.contractInvoice.getTableData()
          this.$refs.contractInvoice.getConfigApprove()
        },
        7: () => this.$refs.file.getTableData(),
        8: () => {
          this.$refs.liaison.getTableData()
        },
        // 9: () => this.$refs.dynamicRecord.getTableData(),
        10: () => this.$refs.odds.getTableData(),
        11: () => this.$refs.sign.getTableData(),
        1: () => this.refreshCustomerDetail()
      }
      const action = actions[this.tabNumber]
      if (action) {
        setTimeout(() => {
          action()
        }, 100)
      }
    },

    // 添加跟进提醒
    addRecord() {
      this.remindConfig = {
        eid: this.formData.data.eid,
        isEdit: false,
        link_type: this.formData.link_type
      }
      this.$refs.remindDialog.handleOpen(false)
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  padding: 0 14px;
  width: 100%;
  height: 400px;
  border: 1px solid #d7dbe0;
}
::v-deep .el-tabs__item.is-active {
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
::v-deep .el-date-editor.el-input {
  width: 100%;
}

.addBox ::v-deep .el-dialog__body {
  padding: 0;
}
.addBox ::v-deep .el-dialog {
  border-radius: 6px;
  height: 300px;
}
::v-deep .el-form--inline .el-form-item {
  display: flex;
}
::v-deep .el-drawer__body {
  // padding-bottom: 50px;
}
::v-deep .el-drawer__header {
  height: 80px !important;
  border: none;
  padding: 14px 18px;
}
::v-deep .el-tabs__item {
  line-height: 40px !important;
  .tab-label-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
  }
  .tab-badge {
    display: inline-block;
    margin-left: 4px;
    font-size: 14px;
    font-weight: 400;
  }
}
::v-deep .el-tabs__header {
  background-color: #f7fbff;
  border-bottom: none;
}
::v-deep .el-tabs__nav-wrap::after {
  height: 0;
}
::v-deep .el-tabs__active-bar {
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
.weight {
  font-weight: 400;
  color: #303133;
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
          // margin-top: 12px;
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
    .txt3 {
      font-size: 14px;
    }
    .txt2 {
      margin-top: 10px;
      font-size: 13px;
      color: #000;
      .wechat-info {
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        .wechat-avatar {
          width: 20px;
          height: 20px;
          border-radius: 50%;
          margin-right: 6px;
          flex-shrink: 0;
        }
        .wechat-tag {
          margin-left: 4px;
        }
      }
      .title {
        font-size: 14px;
        color: #999999;
        padding-left: 20px;
        font-weight: 400;
      }
      .title:first-of-type {
        padding-left: 0;
      }

      .info3 {
        color: #1890ff;
      }
    }
  }
}

.contract-body {
  margin-top: 39px;
  padding: 20px;
  display: flex;
  justify-content: center;
  .contract-info {
    width: 100%;
  }
  .contract-record {
    width: 100%;
  }
  .contract-remind {
    height: calc(100% - 120px);
  }
  .contract-list {
    width: 100%;
    height: calc(100% - 44px);
    ::v-deep .el-button--medium {
      font-size: 13px;
    }
  }
}
</style>
