<!-- 订单-添加订单页面 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-contract-add-contract-drawer"
      :title="formData.title"
      v-model="drawer"
      :direction="direction"
      :modal="true"
      :append-to-body="true"
      :before-close="handleClose"
      :size="formData.width"
      :close-on-click-modal="false"
    >
      <oaForm
        :form-info="fromInfo"
        ref="oaForm"
        :showContractBtn="true"
        :type="`contract`"
        @handleClose="handleClose"
        @submitOk="submitOk"
        @changeValue="changeValue"
        @addContinueOk="addContinueOk"
      >
        <template v-slot:product>
          <productList
            ref="productList"
            :product="product"
            show-stock
            limit-by-stock
            :warehouse-id="warehouseId"
            @productChange="handleProductChange"
            :loading="productLoading"
          ></productList>
        </template>
      </oaForm>
    </el-drawer>
    <edit-contract ref="editContract" :form-data="fromData"></edit-contract>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { oddsCreateEditApi } from '@/api/client.ts'
import { contractCreateApi, contractEditCreateApi } from '@/api/enterprise.ts'
import { clientContractSaveApi as contractAddApi, clientContractEditApi } from '@/api/client.ts'
import { generateEmptyProduct } from "@/views/customer/components/helper.ts";

export default {
  emits: ['getTableData'],
  name: 'AddContract',
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    products: {
      type: Array,
      default: () => {
        return []
      }
    }
  },
  components: {
    oaForm: defineAsyncComponent(() => import('@/components/customer/oaForm')),
    productList: defineAsyncComponent(() => import('@/views/customer/components/productList')),
    editContract: defineAsyncComponent(() => import('@/views/customer/contract/components/editContract'))
  },
  data() {
    return {
      row: {},
      drawer: false,
      direction: 'rtl',
      fromInfo: [],
      addText: '',
      fromData: {},
      product: [
        generateEmptyProduct()
      ],
      itemData: null,
      productLoading: false,
      // 出库仓库 id，联动产品清单的剩余良品查询
      warehouseId: ''
    }
  },
  watch: {
    product: function (val) {
      if (val.length == 0) {
        this.product = [
          {
            unique: '',
            image: '',
            name: '',
            sku: '',
            price: 0,
            count: 0,
            discount: 100,
            total_price: 0,
            ot_price: 0.0,
            remark: ''
          }
        ]
      }
    }
  },
  methods: {
    handleClose() {
      this.drawer = false
      this.row = {}
      this.product = []
    },
    // 产品总价变化时，自动同步到订单金额
    handleProductChange(total) {
      if (this.$refs.oaForm && this.$refs.oaForm.ruleForm) {
        // 遍历 formInfo 找到订单金额字段的 key
        const moneyKey = this.getMoneyKey()
        if (moneyKey) {
          this.$refs.oaForm.ruleForm[moneyKey] = total
        }
      }
    },
    // 获取订单金额字段的 key
    getMoneyKey() {
      if (!this.fromInfo || this.fromInfo.length === 0) return null
      for (const group of this.fromInfo) {
        if (group.data && group.data.length > 0) {
          for (const field of group.data) {
            // 匹配订单金额字段（可能是 contract_money、order_money 等）
            if (field.key_name && field.key_name.includes('订单金额')) {
              return field.key
            }
          }
        }
      }
      return null
    },
    // 选择商机或客户时，自动同步相关信息
    async changeValue(key, val) {
      // 选择商机时，根据商机同步客户信息和产品信息；选择客户时，根据客户同步商机信息
      if (key === 'oid') {
        if (!val) {
          this.product = []
          return
        }
        this.handleSyncCustomerByOid(val);
        try {
          this.productLoading = true;
          const res = await oddsCreateEditApi(val);
          this.product = res.data.product;
        } catch (error) {
          this.$message.error('获取商机产品信息失败');
        } finally {
          this.productLoading = false;
        }
      } else if (key === 'contract_customer') {
        this.handleSyncOidByCustomer(val);
      } else if (key === 'outbound_warehouse_id') {
        // 出库仓库变化，同步给产品清单以重新查询剩余良品
        this.warehouseId = val || '';
      }
    },
    // 根据选择商机同步客户信息
    handleSyncCustomerByOid(oid) {
      const oaFormRef = this.$refs.oaForm;
      if (!oaFormRef) return;
      const formCustomerId = oaFormRef.ruleForm.contract_customer;
      const oidConfig = this.findField('oid');
      const newCustomerId = oidConfig?.options.find(option => String(option.value) === String(oid))?.eid;
      if (!newCustomerId || newCustomerId === formCustomerId) return;
      oaFormRef.ruleForm.contract_customer = newCustomerId;
    },
    // 根据选择客户同步商机信息
    handleSyncOidByCustomer(customerId) {
      const oaFormRef = this.$refs.oaForm;
      if (!oaFormRef) return;
      const oidConfig = this.findField('oid');
      if (!oidConfig) return;

      // 备份原始选项列表，避免多次过滤导致数据丢失
      if (!this._rawOidOptions) {
        this._rawOidOptions = structuredClone(oidConfig.options);
      }

      delete oaFormRef.ruleForm.oid;
      this.product = [];
      if (customerId) {
        const filteredOptions = this._rawOidOptions.filter(option => String(option.eid) === String(customerId));
        oidConfig.options = filteredOptions;
      } else {
        oidConfig.options = structuredClone(this._rawOidOptions);
      }
    },
    // 根据 key 查找表单字段配置
    findField(key) {
      const configList = this.fromInfo.reduce((acc, item) => {
        return acc.concat(item.data || []);
      }, []);

      return configList.find(field => field.key === key);
    },
    // 获取新增表单
    getData() {
      let obj = {
        
        odds_id: this.formData.odds_id || '',
        eid: this.formData.eid || ''
      }

      // 必须返回 Promise：openBox 需等表单配置就绪后才能读取出库仓库默认值
      return contractCreateApi(obj).then((res) => {
        if (this.formData && this.formData.id) {
          res.data.forEach((item) => {
            item.data.forEach((val) => {
              if (val.key == 'contract_customer') {
                val.value = this.formData.id
              }
              if (val.key == 'oid') {
                val.value = this.formData.odds_id
              }
            })
          })
        }
        if (this.formData.product) {
          this.product = this.formData.product
        }

        this.fromInfo = res.data
      })
    },
    // 获取编辑表单
    getEditData() {
      // 必须返回 Promise：openBox 需等表单配置就绪后才能读取出库仓库默认值
      return contractEditCreateApi(this.row.id).then((res) => {
        this.fromInfo = res.data.list
        if (res.data.product.length > 0) {
          this.product = res.data.product
        }
      })
    },
    async openBox(data) {
      this._rawOidOptions = null
      this.warehouseId = ''
      if (data) {
        this.row = data
        await this.getEditData()
      } else {
        await this.getData()
      }
      if (this.products.length > 0) {
        this.product = this.products
      }
      // 初始化出库仓库（新增时后端表单可能带默认选中仓库，编辑订单时可能已有值），
      // 用户未手动改动出库仓库、拿不到 changeValue 回调，需在此主动联动产品清单的剩余良品查询
      const defaultWarehouseId = this.findField('outbound_warehouse_id')?.value ?? ''
      // 未选仓库时后端可能返回 0 / '0'，统一按未选择处理，避免误触发库存查询与提交时的库存校验
      this.warehouseId = Number(defaultWarehouseId) === 0 ? '' : defaultWarehouseId
      this.drawer = true
    },

    // 校验产品剩余良品：存在剩余良品为 0 的产品则返回其名称列表（未查询到库存的 undefined 不拦截）
    // 未选择出库仓库时不存在库存概念，不做库存校验
    getOutOfStockNames(products) {
      if (!this.warehouseId) return []
      return products
        .filter((item) => item.unique && Number(item.good_stock) === 0)
        .map((item) => item.name || item.product_name)
        .filter(Boolean)
    },
    getEmptyCountNames(products) {
      return products
        .filter((item) => item.unique && Number(item.count) === 0)
        .map((item) => item.name || item.product_name)
        .filter(Boolean)
    },
    // 提交成功
    submitOk(data, type) {
      data.products = this.$refs.productList.tableData
      data.products = data.products.filter((item) => item.unique)
      const clear = () => {
        if (this.$refs.oaForm) {
          this.$refs.oaForm.saveLoading = false
          this.$refs.oaForm.addContractLoading = false
        }
      }
      // 库存校验：存在剩余良品为 0 的产品时禁止提交
      const outOfStockNames = this.getOutOfStockNames(data.products)
      if (outOfStockNames.length) {
        this.$message.error(`产品「${outOfStockNames.join('、')}」库存不足，无法提交`)
        clear();
        return
      }
      const emptyCountNames = this.getEmptyCountNames(data.products);
      if (emptyCountNames.length) {
        this.$message.error(`请输入产品「${emptyCountNames.join('、')}」成交数量`)
        clear();
        return
      }
      if (this.row.id) {
        clientContractEditApi(this.row.id, data)
          .then((res) => {
            if (res.status == 200) {
              if (type == 1) {
                data.eid = data.contract_customer
                data.cid = this.row.id
                this.fromData = {
                  title: '查看订单',
                  width: '1000px',
                  data: data,
                  isClient: false,
                  name: this.formData.name,
                  id: this.row.id,
                  edit: true
                }
                this.$nextTick()
                this.$refs.editContract.tabIndex = '2'
                this.$refs.editContract.tabNumber = 2
                this.$refs.editContract.openBox(data)
              } else {
                this.drawer = false
                this.$emit('getTableData')
                this.$refs.oaForm.resetForm()
              }
              this.$refs.oaForm.saveLoading = false
              this.$refs.oaForm.addContractLoading = false
            } else {
              this.$refs.oaForm.saveLoading = false
              this.$refs.oaForm.addContractLoading = false
            }
          })
          .catch((err) => {
            this.$refs.oaForm.resetForm()
          })
      } else {
        contractAddApi(data)
          .then((res) => {
            if (res.status == 200) {
              if (type == 1) {
                this.drawer = false
                data.eid = data.contract_customer
                data.cid = res.data.id
                this.fromData = {
                  title: '查看订单',
                  width: '1000px',
                  data: data,
                  isClient: false,
                  name: this.formData.name,
                  id: res.data.id,
                  cid: res.data.id,
                  edit: true
                }
                this.$refs.editContract.tabIndex = '2'
                this.$refs.editContract.tabNumber = 2
                this.$refs.editContract.openBox(this.fromData)
              } else {
                this.drawer = false
                this.$refs.oaForm.resetForm()
                this.$emit('getTableData')
              }
              this.$refs.oaForm.saveLoading = false
              this.$refs.oaForm.addContractLoading = false
            } else {
              this.$refs.oaForm.saveLoading = false
              this.$refs.oaForm.addContractLoading = false
            }
          })
          .catch((err) => {
            this.$message.error(err)
            // this.$refs.oaForm.resetForm()
          })
      }
    },
    addContinueOk(data) {
      this.submitOk(data, 1)
    },
    handlePayment() {
      this.addText = 'on'
      this.$refs.contractInfo.handleConfirm().then(() => {
        setTimeout(() => {
          this.fromData = {
            title: '查看客户',
            width: '1000px',
            data: this.itemData,
            isClient: false,
            name: this.formData.name,
            id: this.itemData.id,
            edit: true
          }

          this.$refs.editContract.tabIndex = '2'
          this.$refs.editContract.tabNumber = 2
          this.drawer = false
          setTimeout(() => {
            this.$refs.editContract.openBox()
            this.addText = ''
            this.$refs.contractInfo.reset()
            this.$refs.contractInfo.contractList()
          }, 500)
        }, 300)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-form--inline .el-form-item) {
  display: flex;
}
:deep(.el-input-number) {
  width: 100%;
}
:deep(.el-input__inner) {
  text-align: left;
}
:deep(.el-date-editor) {
  width: 100%;
}
.from-item-title {
  border-left: 5px solid #1890ff;
  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}
.form-box {
  display: flex;
  flex-wrap: wrap;
  margin: 0 20px;
  justify-content: space-between;
  .form-item {
    width: 48%;
    :deep(.el-form-item__content) {
      width: calc(100% - 90px);
    }
    :deep(.el-select) {
      width: 100%;
    }
    :deep(.el-form-item) {
      margin-bottom: 20px;
    }
    :deep(.el-textarea__inner) {
      resize: none;
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
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-contract-add-contract-drawer .el-drawer__body {
  padding: 20px;
  padding-bottom: 40px;
}
</style>
