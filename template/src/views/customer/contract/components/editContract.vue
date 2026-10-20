<!-- 查看订单信息页面 -->
<template>
  <div class="contract">
    <el-drawer
      class="customer-contract-edit-contract-drawer"
      :append-to-body="true"
      :before-close="handleClose"
      :direction="direction"
      :modal="true"
     
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
              <div class="txt1 over-text">{{ dataInfo.data ? dataInfo.data.contract_no : '--' }}</div>
              <div class="txt2">
                <span class="title">订单状态：</span
                ><span
                  :style="{
                    color:
                      dataInfo.data && dataInfo.data.contract_status ? dataInfo.data.contract_status.color : '#1890ff'
                  }"
                  >{{ dataInfo.data && dataInfo.data.contract_status ? dataInfo.data.contract_status.name : '--' }}</span
                >
                <span class="title">订单金额(元)：</span
                ><span class="info1">{{ dataInfo.data ? dataInfo.data.price : '--' }}</span>
                <span class="title">客户：</span
                ><span class="weight">{{ dataInfo.data ? dataInfo.data.customer_name : '--' }}</span>
                <span class="title"> 业务员：</span
                ><span class="weight">{{ dataInfo.data ? dataInfo.data.salesman : '--' }}</span>
                <span class="title">创建时间：</span
                ><span>{{ (dataInfo.data && dataInfo.data.created_at) || '--' }}</span>
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
      <div class="contract-body table-box mt14">
        <!--基本信息-->
        <div v-show="tabNumber === 1" class="contract-info">
          <oaForm
            :form-info="dataInfo.form"
            :id="cid"
            v-if="drawer"
            :keyWord="`contract`"
            ref="oaForm"
            :viewMode="true"
            :btnShow="false"
            :isShowFooter="false"
          >
            <!-- 自定义产品分组标题：在 oaForm 默认标题基础上追加编辑入口 -->
            <template #header-product="{ group, type }">
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
            <template #product="slotProps">
              <productList
                ref="productList"
                :type="slotProps.type"
                :product="dataInfo.product"
                show-stock
                limit-by-stock
                :warehouse-id="warehouseId"
              />

              <div v-if="slotProps.type == 'add'" class="mb10 flex-end">
                <el-button @click="productFn(1)">取消</el-button>
                <el-button type="primary" @click="productFn(2)">确定</el-button>
              </div>
            </template>
          </oaForm>
        </div>

        <!--合同信息-->
        <div v-show="tabNumber === 8" class="contract-list">
          <infoItem v-if="signDataInfo.length != 0" :dataInfo="signDataInfo"></infoItem>
          <!-- 审批流程 -->
          <process-from v-if="signDataInfo.length != 0" :examine-data="examineData"></process-from>
          <template v-else>
            <default-page :textShow="false" imgWidth="128px" :index="17" :min-height="400" :top="`40px`">
              <el-button class="ml10" type="primary" link @click="openContract"
                >生成合同<span class="el-icon-arrow-right"
              /></el-button>
            </default-page>
          </template>
        </div>

        <!--订单付款-->
        <div v-show="tabNumber === 2" class="contract-list">
          <contract-payment
            ref="contractPayment"
            :form-info="formData"
            @refresh-detail="refreshContractDetail"
          ></contract-payment>
        </div>
        <!--订单续费-->
        <div v-show="tabNumber === 7" class="contract-list">
          <contract-renew ref="contractRenew" :form-info="formData"></contract-renew>
        </div>
        <!--付款提醒-->
        <div v-show="tabNumber === 3" class="contract-list">
          <contract-remind
            ref="contractRemind"
            :form-info="formData"
            @refresh-detail="refreshContractDetail"
          ></contract-remind>
        </div>
        <!--出入库记录-->
        <div v-show="tabNumber === 10" class="contract-list">
          <order-stock-movements
            ref="orderStockMovements"
            :form-info="formData"
            :warehouse-id="warehouseId"
          ></order-stock-movements>
        </div>
        <!--发票-->
        <div v-show="tabNumber === 4" class="contract-list">
          <contract-invoice
            ref="contractInvoice"
            :contractInvoice="contractInvoice"
            :form-info="formData"
            @handleInvoice="handleInvoice"
            @refresh-detail="refreshContractDetail"
          ></contract-invoice>
        </div>
        <!--附件相关-->
        <div v-if="tabNumber === 5" class="contract-list">
          <file
            ref="file"
            :form-info="formData"
            @refresh-detail="refreshContractDetail"
            @vue:mounted="handleFileMounted"
          ></file>
        </div>
        <div v-if="tabNumber === 6" class="contract-list">
          <dynamic-record ref="dynamicRecord" :form-info="formData"></dynamic-record>
        </div>
      </div>
    </el-drawer>
    <edit-customer ref="editCustomer" :form-data="newForm" @isOkEdit="getTableData(true)"></edit-customer>
    <!-- 生成合同 -->
    <addContractSign ref="addContractSign" @isOk="getDetailsSign"></addContractSign>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { contractDocDetailApi } from '@/api/contractSign.ts'
import { contractEditCreateApi } from '@/api/enterprise.ts'
import { clientDataInfoApi, clientContractDetailApi as contractDetailApi } from '@/api/client.ts'

export default {
  emits: ['isOk'],
  name: 'EditContract',
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    addContractSign: defineAsyncComponent(() => import('@/views/customer/signing/components/addContractSign')),
    infoItem: defineAsyncComponent(() => import('@/views/customer/signing/components/infoItem')),
    processFrom: defineAsyncComponent(() => import('@/views/user/examine/components/detailProcecss')),
    contractInfo: defineAsyncComponent(() => import('./contractInfo')),
    contractRenew: defineAsyncComponent(() => import('./contractRenew')),
    contractInvoice: defineAsyncComponent(() => import('./contractInvoice')),
    contractRemind: defineAsyncComponent(() => import('./contractRemind')),
    contractPayment: defineAsyncComponent(() => import('./contractPayment')),
    orderStockMovements: defineAsyncComponent(() => import('./orderStockMovements')),
    file: defineAsyncComponent(() => import('./file')),
    dynamicRecord: defineAsyncComponent(() => import('@/views/customer/list/components/dynamicRecord')),
    oaForm: defineAsyncComponent(() => import('@/components/customer/oaForm')),
    productList: defineAsyncComponent(() => import('@/views/customer/components/productList')),
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
    ueditorFrom: defineAsyncComponent(() => import('@/components/form-common/oa-wangeditor')),
    editCustomer: defineAsyncComponent(() => import('@/views/customer/list/components/editCustomer'))
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      record: {
        info: ''
      },
      signDataInfo: {},
      examineData: {},
      cid: 0,
      activeColor: '',
      newForm: {},
      contractInvoice: 'is_contract',
      autosize: {
        minRows: 5,
        maxRows: 10
      },
      remindAutosize: {
        minRows: 2,
        maxRows: 4
      },
      dataInfo: {},
      tabPosition: 'top',
      tabIndex: '1',
      tabNumber: 1,
      tabData: [
        { value: '1', label: '基本信息' },
        { value: '8', label: '合同信息' },
        { value: '2', label: '账目记录', badgeKey: 'bill_count' },
        // { value: '6', label: '订单续费' },
        { value: '3', label: '付款提醒', badgeKey: 'remind_count' },
        { value: '10', label: '出入库记录', badgeKey: 'stock_record_count' },
        { value: '4', label: '发票', badgeKey: 'invoice_count' },
        { value: '5', label: '记录', badgeKey: 'file_count' },
        { value: '6', label: '动态记录', badgeKey: 'record_count' }
      ],
      configContract: {},
      formBoxConfig: {},
      loading: false
    }
  },
  computed: {
    warehouseId() {
      if (this.dataInfo?.form?.length) {
        const warehouseInfo = this.dataInfo.form[0]?.data?.find(item => item.key === "outbound_warehouse_id");
        if (warehouseInfo?.value) return warehouseInfo.value;
      }

      return null;
    }
  },
  methods: {
    getTabBadge(tabItem) {
      if (!tabItem.badgeKey || !this.dataInfo || !this.dataInfo.count) return null
      const count = this.dataInfo.count[tabItem.badgeKey]
      return count > 0 ? count : null
    },
    refreshContractDetail() {
      if (!this.drawer) return
      if (!this.cid) return
      this.getDetails(this.cid)
    },
    handleClose() {
      if (this.$refs.oaForm) {
        this.$refs.oaForm.removeEvent()
      }

      this.$emit('isOk')
      this.drawer = false
      this.dataInfo = {}
      this.dataInfo.product = []
    },
    // 获取订单详情
    getDetails(id) {
      contractEditCreateApi(this.cid, { edit: 1 }).then((res) => {
        this.dataInfo = res.data
        if (this.dataInfo.product && this.dataInfo.product.length == 0) {
          this.dataInfo.product = [{}]
        }
      })
    },
    openContract() {
      if (!this.formData.data.eid) {
        this.$message.error('无效的客户')
        return false
      }
      this.formData.data.link_type = '2'
      this.$refs.addContractSign.openBox('', 'add', this.formData.data.eid, this.formData.data)
    },

    openBox(row) {
      this.cid = row.cid
      this.drawer = true
      if (this.tabNumber == 2) {
        setTimeout(() => {
          this.$refs.contractPayment.getTableData(row.id)
          this.$refs.contractPayment.getConfigApprove(6)
          this.$refs.contractPayment.getConfigApprove(7)
          this.$refs.contractPayment.getConfigApprove(8)
        }, 300)
      }
      if (row.cid) {
        this.getDetails(row.cid)
      }
    },
    handleClick(event) {
      this.tabNumber = Number(event.paneName)
      if (this.tabNumber === 2) {
        this.$refs.contractPayment.getTableData(this.cid)
        this.$refs.contractPayment.getConfigApprove(6)
        this.$refs.contractPayment.getConfigApprove(7)
        this.$refs.contractPayment.getConfigApprove(8)
      } else if (this.tabNumber === 3) {
        this.$refs.contractRemind.getTableData()
      } else if (this.tabNumber === 10) {
        this.$refs.orderStockMovements.getTableData()
      } else if (this.tabNumber === 4) {
        this.$refs.contractInvoice.getTableData()
      } else if (this.tabNumber === 8) {
        // tab 5(记录)、tab 6(动态记录) 均为 v-if + 异步组件：切换时重建，挂载后通过 @hook:mounted / mounted 自加载 getTableData，
        // 若在此同步/延时取 $refs，组件尚未挂载会报 getTableData undefined，故不在此处理
        this.getDetailsSign()
      }
    },
    handleFileMounted() {
      this.$refs.file.getTableData()
    },
    // 获取合同签约详情
    getDetailsSign() {
      contractDocDetailApi(this.cid, { link_type: 'contract' }).then((res) => {
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
    editProduct() {
      this.$refs.oaForm.productType = 'add'
    },
    productFn(type) {
      if (type == 1) {
        this.$refs.oaForm.editKey = ''
        this.$refs.oaForm.productType = 'edit'
        this.getDetails()
      } else {
        // 库存校验：存在剩余良品为 0 的产品时禁止保存
        const products = this.$refs.productList.tableData || []
        const outOfStockNames = products
          .filter((item) => item.unique && Number(item.good_stock) === 0)
          .map((item) => item.name || item.product_name)
          .filter(Boolean)
        if (outOfStockNames.length) {
          this.$message.error(`产品「${outOfStockNames.join('、')}」库存不足，无法保存`)
          return
        }

        const emptyCountNames = products
          .filter((item) => item.unique && Number(item.count) === 0)
          .map((item) => item.name || item.product_name)
          .filter(Boolean)
        if (emptyCountNames.length) {
          this.$message.error(`请输入产品「${emptyCountNames.join('、')}」成交数量`)
          return
        }

        this.$refs.oaForm.editKey = 'products'
        this.$refs.oaForm.handlePopoverHide(this.$refs.productList.tableData)
        setTimeout(() => {
          this.getDetails()
        }, 200)
      }
    },

    // 打开客户
    handleClient() {
      clientDataInfoApi(this.formData.data.contract_customer.id).then((res) => {
        res.data.source = res.data.source.id
        let newAddress = []
        if (res.data.address) {
          res.data.address.map((item) => {
            newAddress.push(item.label)
          })
          res.data.address = newAddress
        }

        this.newForm = {
          title: '编辑客户',
          width: '1100px',
          data: res.data,
          isClient: true,
          edit: true,
          link_type: 'customer'
        }

        this.$refs.editCustomer.tabIndex = '1'
        this.$refs.editCustomer.tabNumber = 1
        this.$refs.editCustomer.openBox()
      })
    },

    handleInvoice() {
      this.$emit('isOk')
    },
    handleConfirm() {
      this.$refs.contractInfo.handleConfirm()
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

.add-color {
  cursor: pointer !important;
  color: #1890ff !important;
}

.contract {
}

.addColor {
  font-weight: 400;
  font-family: PingFang SC-常规体, PingFang SC;
  color: #303133;
  font-size: 13px;
  margin-left: 10px;
}

:deep(.el-tabs__content) {
  padding: 0;
}

:deep(.el-form--inline .el-form-item) {
  display: flex;
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

:deep(.el-input__inner) {
  text-align: left;
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
.flex-end {
  display: flex;
  justify-content: flex-end;
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
      font-size: 14px;
      font-weight: bold;
      color: rgba(0, 0, 0, 0.85);
    }

    .txt2 {
      margin-top: 10px;
      font-size: 13px;
      color: #000;

      .title {
        color: #999999;
        padding-left: 20px;
      }

      .title:first-of-type {
        padding-left: 0;
      }

      .info1 {
        color: rgba(245, 34, 45, 1);
      }

      .info2 {
        color: #1890ff;
      }
    }
  }
}
:deep(.form-box) {
  padding-bottom: 20px;
}

.contract-body {
  margin-top: 39px;
  padding: 20px;
  height: calc(100% - 39px);
  display: flex;
  justify-content: center;

  .contract-info {
    width: 100%;
  }

  .contract-list {
    width: 100%;
    height: 100%;

    :deep(.el-button) {
      font-size: 13px;
    }

    .icon-cover {
      font-size: 28px;
    }
  }
}

.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}

.from-foot-btn {
  button {
    height: auto;
  }
}

:deep(.el-tabs__header .el-tabs__item) {
  line-height: 40px;
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
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-contract-edit-contract-drawer .el-drawer__header {
  height: 80px !important;
  padding: 14px 18px;
}
</style>
