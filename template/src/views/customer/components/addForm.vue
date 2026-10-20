<!-- 订单-添加订单页面 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-add-form-drawer"
      :title="formData.title"
      v-model="drawer"
      :direction="direction"
      :modal="true"
      :append-to-body="true"
      :before-close="handleClose"
     
      :size="formData.width"
      :close-on-click-modal="false"
    >
      <div class="p20">
        <oaForm
          :form-info="fromInfo"
          ref="oaForm"
          :keyWord="keyword"
          :btnShow="false"
          @handleClose="handleClose"
          @submitOk="submitOk"
        >
          <template v-slot:product>
            <productList ref="productList" :product="product" remarkFixed></productList>
          </template>
        </oaForm>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { savecluesApi, oddsSaveApi, oddsEditApi, savecluesEditApi } from '@/api/client.ts'
import productList from './productList'
import { generateEmptyProduct } from "./helper.ts";

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
    keyword: {
      type: String,
      default: ''
    }
  },
  components: {
    productList,
    oaForm: defineAsyncComponent(() => import('@/components/customer/oaForm'))
  },
  data() {
    return {
      row: {},
      id: 0,
      drawer: false,
      direction: 'rtl',
      fromInfo: [],
      product: [
        generateEmptyProduct()
      ],
      addText: '',
      fromData: {},
      itemData: null
    }
  },
  watch: {},
  methods: {
    handleClose() {
      this.drawer = false
      this.row = {}
      this.fromInfo = []

      this.id = 0
      this.product = [
        {
          image: '',
          name: '',
          sku: '',
          price: 0,
          count: 1,
          discount: 100,
          total_price: 0,
          ot_price: 0.0,
          remark: '',
          unique: ''
        }
      ]
    },

    async openBox(data, id) {
    
      
      if (id) {
        this.id = id
        if (this.keyword == 'odds') {
          this.fromInfo = data.list
          if (data.product.length > 0) this.product = data.product
        } else {
          this.fromInfo = data
        }
      } else {
        this.fromInfo = data
      }

      this.drawer = true
    },

    // 提交成功
    submitOk(val) {
      // 商机
      if (this.keyword == 'odds') {
        val.products = this.$refs.productList.tableData
        val.products = val.products.filter((item) => item.unique)
        this.setOdds(val)
      } else {
        // 线索
        val.types = this.keyword
        this.setClue(val)
      }
    },
    // 保存线索表单
    setClue(val) {
      if (this.id) {
        savecluesEditApi(this.id, val)
          .then((res) => {
            if (res.status == 200) {
              this.drawer = false
              this.$emit('getTableData')
              this.$refs.oaForm.resetForm()
              this.$refs.oaForm.saveLoading = false
            } else {
              this.$refs.oaForm.saveLoading = false
            }
          })
          .catch((err) => {
            this.$message.error(err)
            this.$refs.oaForm.resetForm()
            this.$refs.oaForm.saveLoading = false
          })
      } else {
        savecluesApi(val)
          .then((res) => {
            if (res.status == 200) {
              this.drawer = false
              this.$emit('getTableData')
              this.$refs.oaForm.resetForm()
            } else {
              this.$refs.oaForm.saveLoading = false
            }
          })
          .catch((err) => {
            this.$message.error(err)
            this.$refs.oaForm.resetForm()
          })
      }
    },
    // 保存商机表单
    setOdds(val) {
      if (this.id) {
        oddsEditApi(this.id, val)
          .then((res) => {
            if (res.status == 200) {
              this.drawer = false
              this.$emit('getTableData')
              this.$refs.oaForm.resetForm()
              this.$refs.productList.tableData = []
              this.$refs.oaForm.saveLoading = false
            } else {
              this.$refs.oaForm.saveLoading = false
            }
          })
          .catch((err) => {
            this.$message.error(err)
            this.$refs.oaForm.saveLoading = false
          })
      } else {
        oddsSaveApi(val)
          .then((res) => {
            if (res.status == 200) {
              this.drawer = false
              this.$emit('getTableData')
              this.$refs.oaForm.resetForm()
              this.$refs.productList.tableData = []
            } else {
              this.$refs.oaForm.saveLoading = false
            }
          })
          .catch((err) => {
            this.$message.error(err)
            this.$refs.oaForm.saveLoading = false
          })
      }
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
.customer-add-form-drawer .el-drawer__body {
  padding: 20px;
  padding-bottom: 40px;
}
.customer-add-form-drawer .el-drawer__header {
  font-size: 15px;
}
</style>
