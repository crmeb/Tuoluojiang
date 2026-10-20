<!-- 查看客户信息侧滑页面 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-details-drawer"
      :append-to-body="true"
      :before-close="handleClose"
      :direction="direction"
      :show-close="true"
      :size="formData.width"
      :title="formData.title"
      v-model="drawer"
    >
      <template #header>
        <div class="invoice-title">
          <div class="invoice-header">
            <div class="invoice-left">
              <div class="invoice-logo"><i class="icon iconfont iconhetong"></i></div>
            </div>
            <div v-if="drawer" class="invoice-right">
              <div class="txt1 over-text" v-if="types === 'odds'">
                {{ dataInfo.data ? dataInfo.data.odds_no : '--' }}
              </div>
              <div class="txt1 over-text" v-else-if="types !== 'liaison'">
                {{ dataInfo.data ? dataInfo.data.name : '--' }}
              </div>
              <div class="txt1 over-text" v-else>
                {{ dataInfo.data ? dataInfo.data.liaison_name : '--' }}
              </div>
              <div class="txt2" v-if="types !== 'liaison'">
                <span class="title"> {{ types === 'odds' ? '商机状态：' : '线索状态：' }}</span>

                <span
                  class="info3"
                  :style="{ color: dataInfo.data && dataInfo.data.status ? dataInfo.data.status.color : '#1890ff' }"
                  >{{ dataInfo.data && dataInfo.data.status ? dataInfo.data.status.name : '--' }}</span
                >

                <span class="title" v-if="types === 'odds'"
                  >客户名称：<span class="weight">{{
                    dataInfo.data && dataInfo.data.customer_name ? dataInfo.data.customer_name : '--'
                  }}</span></span
                >
                <span class="title">业务员：</span
                ><span class="weight">{{ dataInfo.data ? dataInfo.data.salesman : '--' }}</span>
              </div>
            </div>
          </div>
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
      <div class="contract-body table-box">
        <!--基本信息-->
        <div v-if="tabNumber == 1" class="contract-baseinfo">
          <oaForm
            v-if="drawer && tabNumber == 1"
            :form-info="dataInfo.form"
            :id="id"
            :keyWord="types"
            ref="oaForm"
            :viewMode="true"
            :btnShow="false"
            :isShowFooter="false"
            @getDetails="getDetails"
            @fieldSaved="handleFieldSaved"
          >
            <!-- 自定义产品分组标题：在 oaForm 默认标题基础上追加编辑入口 -->
            <template v-slot:header-product="{ group, type }">
              <span>{{ group.title }}</span>
              <div
                v-if="type == '' || type == 'edit'"
                class="addColor iconfont iconbianji3"
                title="编辑产品清单"
                @click="editProduct"
              >
                编辑
              </div>
            </template>
            <template v-slot:product="slotProps">
              <productList
                ref="productList"
                class="mt10"
                :type="slotProps.type"
                :product="dataInfo.product"
              ></productList>
              <div v-if="slotProps.type == 'add'" class="flex-end">
                <el-button @click="productFn(1)">取消</el-button>
                <el-button type="primary" @click="productFn(2)">确定</el-button>
              </div>
            </template>
          </oaForm>
        </div>
        <!--跟进记录-->
        <div v-if="tabNumber === 2" class="contract-record">
          <record ref="record" :form-info="formData" @refresh-detail="refreshCustomerDetail"></record>
        </div>
        <!--合同信息-->
        <div v-if="tabNumber === 5" class="contract-list">
          <infoItem v-if="signDataInfo.length != 0" :dataInfo="signDataInfo"></infoItem>
          <!-- 审批流程 -->
          <process-from v-if="signDataInfo.length != 0" :examine-data="examineData"></process-from>
          <template v-else>
            <default-page :textShow="false" :index="17" imgWidth="128px">
              <el-button class="btn-primary" type="primary" link @click="openContract"
                >生成合同 <span class="el-icon-arrow-right"
              /></el-button>
            </default-page>
          </template>
        </div>
        <!-- 订单 -->
        <div v-if="tabNumber === 4" class="contract-list">
          <contract
            ref="contract"
            :form-info="formData"
            :product="dataInfo.product"
            @refresh-detail="refreshCustomerDetail"
          ></contract>
        </div>
        <!--动态记录-->
        <div v-if="tabNumber === 3" class="contract-list">
          <dynamic-record ref="dynamicRecord" :form-info="formData"></dynamic-record>
        </div>
      </div>
    </el-drawer>
    <!-- 生成合同 -->
    <addContractSign ref="addContractSign" @isOk="getDetailsSign"></addContractSign>
  </div>
</template>
<script>
import { mapStores } from 'pinia'
import { useAppConfigStore } from '@/stores/appConfig.ts'
import { defineAsyncComponent } from 'vue'
import { contractDocDetailApi } from '@/api/contractSign.ts'
import { getCluesEditApi, oddsCreateEditApi } from '@/api/client.ts'
import { liaisonEditCreateApi } from '@/api/enterprise.ts'
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules.ts'
import dynamicRecord from '@/views/customer/list/components/dynamicRecord'

export default {
  emits: ['getTableData'],
  name: 'detailsDrawer',
  components: {
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    addContractSign: defineAsyncComponent(() => import('@/views/customer/signing/components/addContractSign')),
    infoItem: defineAsyncComponent(() => import('@/views/customer/signing/components/infoItem')),
    processFrom: defineAsyncComponent(() => import('@/views/user/examine/components/detailProcecss')),
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
    record: defineAsyncComponent(() => import('@/views/customer/list/components/record')),
    contract: defineAsyncComponent(() => import('@/views/customer/list/components/contract')),
    productList: defineAsyncComponent(() => import('./productList')),
    oaForm: defineAsyncComponent(() => import('@/components/customer/oaForm')),
    dynamicRecord
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
      rowInfo: {},
      signDataInfo: {},
      examineData: {},
      id: 0,
      types: '',
      drawer: false,
      direction: 'rtl',
      tabPosition: 'top',
      tabIndex: '1',
      tabNumber: 1,
      remindConfig: {}
    }
  },
  computed: {
    ...mapStores(useAppConfigStore),
    tabData() {
      const TAB_DATA = [
        {
          value: '1',
          label: '基本信息'
        },
        {
          value: '5',
          label: '合同信息',
          includeType: ['odds'],
          moduleKey: CUSTOMER_MODULE_KEYS.CONTRACT
        },
        {
          value: '2',
          label: '跟进记录',
          badgeKey: 'follow_count',
          excludeType: ['liaison']
        },
        {
          value: '4',
          label: '订单',
          badgeKey: 'contract_count',
          excludeType: ['liaison', 'clue', 'clue_seas'],
          moduleKey: CUSTOMER_MODULE_KEYS.ORDER
        },
        {
          value: '3',
          label: '动态记录',
          badgeKey: 'record_count'
        }
      ]

      const isCustomerModuleEnabled = this.appConfigStore.isCustomerModuleEnabled
      const currentType = this.types
      return TAB_DATA.filter((item) => {
        if ('moduleKey' in item && !isCustomerModuleEnabled(item.moduleKey)) {
          return false
        }
        if (item.includeType && !item.includeType.includes(currentType)) {
          return false
        }
        if (item.excludeType && item.excludeType.includes(currentType)) {
          return false
        }
        return true
      })
    }
  },
  methods: {
    getTabBadge(tabItem) {
      if (!tabItem.badgeKey || !this.dataInfo || !this.dataInfo.count) return null
      const count = this.dataInfo.count[tabItem.badgeKey]
      return count > 0 ? count : null
    },
    async getDetails(id) {
      const api = {
        odds: oddsCreateEditApi,
        clue: getCluesEditApi,
        clue_seas: getCluesEditApi,
        liaison: liaisonEditCreateApi
      }
      const result =
        this.types === 'clue' || this.types === 'clue_seas'
          ? await getCluesEditApi(this.id, { types: this.types })
          : await api[this.types](this.id)
      this.dataInfo = result.data
      if (this.dataInfo.product && this.dataInfo.product.length == 0) {
        this.dataInfo.product = [{}]
      }
      if (this.types === 'odds') {
        this.formData.odds_id = this.id
      }
    },
    refreshCustomerDetail() {
      if (!this.drawer) return
      if (!this.id) return
      this.getDetails(this.id)
    },
    handleFieldSaved() {
      this.$emit('getTableData')
    },
    openContract() {
      if (!this.formData.data.eid) {
        this.$message.error('无效的客户')
        return false
      }
      this.formData.data.link_type = '5'
      this.$refs.addContractSign.openBox('', 'add', this.formData.data.eid, this.formData.data)
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

    editProduct() {
      this.$refs.oaForm.productType = 'add'
    },

    productFn(type) {
      if (type == 1) {
        this.$refs.oaForm.editKey = ''
        this.$refs.oaForm.productType = 'edit'
        this.getDetails()
      } else {
        this.$refs.oaForm.editKey = 'products'
        this.$refs.oaForm.handlePopoverHide(this.$refs.productList.tableData)
        setTimeout(() => {
          this.getDetails()
        }, 200)
      }
    },
    // 获取合同签约详情
    getDetailsSign() {
      contractDocDetailApi(this.id, { link_type: 'odds' }).then((res) => {
        this.signDataInfo = res.data
        // 确保 sign_file 是数组格式
        this.signDataInfo.sign_file = Array.isArray(this.signDataInfo.sign_file)
          ? this.signDataInfo.sign_file
          : [this.signDataInfo.sign_file]
        // 确保 attach 也是数组格式
        this.signDataInfo.attach = Array.isArray(this.signDataInfo.attach)
          ? this.signDataInfo.attach
          : this.signDataInfo.attach
          ? [this.signDataInfo.attach]
          : []
        this.examineData.users = res.data.approve
      })
    },

    change() {
      this.$refs.record.getTableData()
      this.refreshCustomerDetail()
    },
    handleClose() {
      if (this.$refs.oaForm) {
        this.$refs.oaForm.editKey = ''
        this.$refs.oaForm.productType = 'edit'
        this.$refs.oaForm.removeEvent()
      }
      this.$emit('getTableData')
      this.drawer = false
    },

    openBox(id, type) {
      this.tabIndex = '1'
      this.tabNumber = 1
      this.id = id
      this.types = type
      this.getDetails(id)
      this.drawer = true
    },

    // 点击tab切换
    handleClick(event) {
      this.tabNumber = Number(event.paneName)
      if (this.tabNumber == 2) {
        setTimeout(() => {
          this.$refs.record.getTableData()
        }, 200)
      }
      if (this.tabNumber == 5) {
        this.getDetailsSign()
      }
      if (this.$refs.oaForm) {
        this.$refs.oaForm.clearClick()
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

.flex-end {
  display: flex;
  justify-content: flex-end;
}

.weight {
  font-weight: 400;
  color: #303133;
}
.addColor {
  font-weight: 400;
  font-family: PingFang SC-常规体, PingFang SC;
  color: #303133;
  font-size: 13px;
  margin-left: 10px;
}
:deep(.el-date-editor.el-input) {
  width: 100%;
}

.addBox :deep(.el-dialog__body) {
  padding: 0;
}

.addBox :deep(.el-dialog) {
  border-radius: 6px;
  height: 300px;
}

:deep(.el-form--inline .el-form-item) {
  display: flex;
}

:deep(.el-tabs__item) {
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
      flex: 0 0 48px;
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
      flex: 1;
      min-width: 0;
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

      .title {
        font-size: 14px;
        color: #999999;
        padding-left: 20px;
        font-weight: 400;
      }

      .title:first-of-type {
        padding-left: 0;
      }

      .info1 {
        color: #19be6b;
      }

      .info2 {
        color: rgba(245, 34, 45, 1);
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
  height: 100%;
  // justify-content: center;

  .contract-baseinfo {
    width: 100%;
  }

  .contract-record {
    width: 100%;
  }

  .contract-list {
    width: 100%;
    height: calc(100% - 44px);

    :deep(.el-button) {
      font-size: 13px;
    }
  }
}
.btn-primary {
  // margin-top: 10px;
  margin-left: 20px;
}

.oneline {
  width: 100% !important;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-details-drawer .el-drawer__body {
  padding-bottom: 50px;
}
.customer-details-drawer .el-drawer__header {
  height: 80px;
  border: none;
  padding: 14px 18px;
}
</style>
