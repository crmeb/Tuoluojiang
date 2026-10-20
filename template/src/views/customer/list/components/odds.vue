<!-- 客户-商机页面组件 -->
<template>
  <div class="station odds-station">
    <div class="btn-box1 mb10">
      <div class="title-16">商机列表</div>
      <el-button @click="addLiaison()" type="primary">添加商机</el-button>
    </div>
    <customizeTable
      flexLayout
      keyword="odds"
      :where="where"
      :isChecked="false"
      :tableData="tableData"
      :total="liaisonTotal"
      @getTableData="getTableData"
    >
      <template #options="{ data }">
        <el-button type="primary" link @click="openDetails(data)">查看</el-button>
        <el-button type="primary" link v-if="!data.is_sign" @click="createContract(data)">生成合同</el-button>
        <el-button type="primary" link @click="createOrder(data)">生成订单</el-button>
      </template>
    </customizeTable>
    <div>
    <!-- 添加商机 -->
    <addForm ref="addForm" :form-data="formBoxConfig" keyword="odds" @getTableData="handleOddsChange"></addForm>
    <!-- 详情 -->
    <detailsDrawer ref="details" :formData="detailsFromData"></detailsDrawer>
    <!-- 生成订单 -->
      <add-contract
      ref="addContract"
      :form-data="orderFromData"
      :products="product"
      @getTableData="handleOrderChange"
    ></add-contract>
    <!-- 生成合同 -->
     <addContractSign ref="addContractSign"
      @getTableData="handleSignChange"
     ></addContractSign>
     </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { oddsCreateApi, oddsCreateEditApi, oddsListApi } from '@/api/client.ts'
export default {
  emits: ['refresh-detail'],
  name: 'Liaison',
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    },
    custom_type: {
      type: Number,
      default: 1
    },
    customInfo: {
      // 客户信息 客户id 和类型
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    addForm: defineAsyncComponent(() => import('@/views/customer/components/addForm')),
     addContract: defineAsyncComponent(() => import('@/views/customer/contract/components/addContract')),
     addContractSign: defineAsyncComponent(() => import('@/views/customer/signing/components/addContractSign')),
    detailsDrawer: defineAsyncComponent(() => import('@/views/customer/components/details')),
    customizeTable: defineAsyncComponent(() => import('@/views/customer/components/customizeTable'))
  },
  data() {
    return {
      liaisonTotal: 0,
      orderFromData: {},
      formBoxConfig: {},
      product: [],
      detailsFromData: {},
      where: {
        page: 1,
        limit: 15,
        eid: 0
      },
      tableData: [] // 表格的数据
    }
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    getTableData(condition = false) {
      if (!condition) {
        if (this.loading) return
        this.loading = true
      }

      this.where.eid = this.formInfo.data.eid

      oddsListApi(this.where)
        .then((res) => {
          this.tableData = res.data.list
          this.liaisonTotal = res.data.count
          this.total_price = res.data.total_price || 0
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },

    handleOddsChange() {
      this.getTableData()
      this.$emit('refresh-detail')
    },

    handleOrderChange() {
      this.getTableData()
      this.$emit('refresh-detail')
    },

    handleSignChange() {
      this.getTableData()
      this.$emit('refresh-detail')
    },

    // 生成合同
    createContract(item) {
      item.link_type = '5'
    
     this.$refs.addContractSign.openBox('','add',this.where.eid,item)
    },
    // 生成订单
    createOrder(item) {
      // 与商机列表页保持一致：调用 client/odds/${id}/edit 获取并回显商机关联的产品清单
      oddsCreateEditApi(item.id, { eid: this.where.eid, odds_id: item.id }).then((res) => {
        this.product = res.data.product || []

        this.orderFromData = {
          title: '添加订单',
          id: this.where.eid,
          name: this.formInfo.data.name,
          edit: false,
          eid: this.where.eid,
          odds_id: item.id,
          width: '1129px'
        }
        setTimeout(() => {
          this.$refs.addContract.openBox()
        }, 100)
      })
    },

    // 查看
    async openDetails(item) {
      this.detailsFromData = {
        title: '商机查看',
        width: '1000px',
        data: item,
        eid: item.id,
        types: 'odds',
        link_type: 'odds'
      }

      this.$refs.details.openBox(item.id, 'odds')
    },

    pageChange(val) {
      this.where.page = val
      this.getTableData()
    },
    // 添加商机
    addLiaison(edit, row) {
      this.formBoxConfig = {
        title: '新增商机',
        width: '1000px',
        types: 'odds'
      }
      oddsCreateApi({ eid: this.formInfo.data.eid }).then((res) => {
        res.data.forEach((item) => {
          item.data.forEach((el) => {
            if (el.key == 'eid') {
              el.value = this.formInfo.data.eid
            }
          })
        })
        this.$refs.addForm.openBox(res.data)
      })
    }
  }
}
</script>
<style></style>

<style lang="scss" scoped>
.odds-station {
  height: 82vh;
  display: flex;
  flex-flow: column;
}
.btn-box1 {
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.el-icon-male {
  color: #1890ff;
  font-size: 13px;
}
.el-icon-female {
  color: #f95c96;
  font-size: 13px;
}
.hand {
  cursor: pointer;
}
:deep(.el-input__inner) {
  text-align: left;
}
.from-item-title {
  border-left: 5px solid #1890ff;
  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}
</style>
