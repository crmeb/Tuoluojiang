<template>
  <div class="station">
    <div class="btn-box1 mb10">
      <div class="title-16">订单列表</div>
      <el-button type="primary" @click="addContract">添加订单</el-button>
    </div>
    <el-table :data="contractData" fit style="width: 100%">
      <el-table-column prop="contract_no" label="订单编号"> </el-table-column>
      <el-table-column prop="contract_price" min-width="100px" label="订单金额(元)"> </el-table-column>
      <el-table-column prop="surplus" label="付款状态">
        <template #default="scope">
          <span class="pointer color-success" v-if="parseFloat(scope.row.surplus) === 0">已结清</span>
          <span class="pointer color-warning" v-else>未结清</span>
        </template>
      </el-table-column>
      <el-table-column prop="contract_status" label="订单状态">
        <template #default="scope">
          <div :style="{ color: scope.row.contract_status.color || '#1890ff' }">
            {{ scope.row.contract_status.name }}
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" min-width="150px" label="创建时间">
        <template #default="scope">
          {{ scope.row.created_at || '--' }}
        </template>
      </el-table-column>
      <el-table-column label="创建人">
        <template #default="scope">
          {{ getCreatorName(scope.row) }}
        </template>
      </el-table-column>
      <el-table-column prop="address" label="操作">
        <template #default="scope">
          <el-button @click="handleCheck(scope.row)" type="primary" link>查看</el-button>
        </template>
      </el-table-column>
    </el-table>
    <div class="pagination">
      <el-pagination
        :page-size="where.limit"
        :current-page="where.page"
        layout="total, prev, pager, next, jumper"
        :total="contractTotal"
        @current-change="pageChange"
      />
    </div>

    <!-- 弹窗 -->
    <editContract ref="editContract" :form-data="fromData" @isOk="getTableData"></editContract>
    <add-contract
      ref="addContract"
      @getTableData="handleContractChange"
      :form-data="contractFromData"
      :products="product"
    ></add-contract>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { getStorageJson } from '@/utils/storage.ts'
import { clientContractListApi } from '@/api/enterprise.ts'
import { getContractTagType, getContractText } from '@/libs/customer.ts'
export default {
  emits: ['refresh-detail'],
  name: 'Contract',
  components: {
    addContract: defineAsyncComponent(() => import('@/views/customer/contract/components/addContract')),
    editContract: defineAsyncComponent(() => import('@/views/customer/contract/components/editContract'))
  },
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    },
    product: {
      type: Array,
      default: () => {
        return []
      }
    }
  },

  data() {
    return {
      where: {
        eid: 0,
        page: 1,
        limit: 15,
        types: 'contract'
      },
      userId: getStorageJson('userInfo', {}).id,
      contractTotal: 0,
      contractData: [],
      fromData: {},
      contractFromData: {}
    }
  },
  computed: {
    showAddButton() {
      return this.formInfo.types == 2 || (this.formInfo.types == 1 && this.userId == this.formInfo.data.salesman.id)
    }
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    handleContractChange() {
      this.getTableData()
      this.$emit('refresh-detail')
    },
    getCreatorName(row) {
      return (
        row.creator?.name ||
        row.card?.name ||
        row.admin?.name ||
        row.creator_name ||
        row.salesman?.name ||
        '--'
      )
    },

    getTableData() {
      if (this.formInfo.odds_id){
        this.where.oid = this.formInfo.odds_id
        this.where.eid = ''
      }else{
        this.where.eid = this.formInfo.types === 'odds' ? '' : this.formInfo.data.eid
        this.where.oid = this.formInfo.types === 'odds' ? this.formInfo.data.id : ''
      }
      clientContractListApi(this.where).then((res) => {
        this.contractData = res.data.list
        this.contractTotal = res.data.count
      })
    },

    // 添加订单
    addContract() {
      this.contractFromData = {
        title: '添加订单',
        id: this.formInfo.data.eid,
        name: this.formInfo.data.name,
        edit: false,
        eid: this.formInfo.data.eid || '',
        odds_id: this.formInfo.types == 'odds' ? this.formInfo.odds_id : '',
        width: '1129px'
      }
      setTimeout(() => {
        this.$refs.addContract.openBox()
      }, 300)
    },

    async handleCheck(item) {
      // 解构赋值获取id，避免直接修改传入的item对象
      const { id } = item
      this.fromData = {
        title: '查看客户',
        width: '1000px',
        data: { ...item, cid: id }, // 使用展开运算符创建新对象并添加cid属性
        isClient: false,
        edit: true
      }

      // 等待DOM更新
      await this.$nextTick()
      // 可以考虑封装成一个方法，避免重复设置属性
      const editContractRef = this.$refs.editContract
      editContractRef.tabIndex = '1'
      editContractRef.tabNumber = 1
      editContractRef.openBox(this.fromData.data) // 传递新对象
    },
    getAbnormalTag(row) {
      return getContractTagType(row)
    },
    getAbnormalTexts(row) {
      return getContractText(row)
    },
    pageChange(val) {
      this.where.page = val
      this.getTableData()
    }
  }
}
</script>

<style lang="scss" scoped>
.color1 {
  color: #ff9900;
}
.color2 {
  color: #19be6b;
}
.color3 {
  color: #ed4014;
}
.btn-box1 {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 32px;
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
:deep(.el-tag) {
  background-color: transparent;
}
</style>
