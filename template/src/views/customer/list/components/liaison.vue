<!-- 客户-联系人页面组件 -->
<template>
  <div class="station">
    <div class="btn-box1 mb10">
      <div class="title-16">联系人列表</div>
      <el-button @click="addLiaison()" type="primary">添加联系人</el-button>
    </div>
    <customizeTable
      keyword="liaison"
      :total="total"
      :where="where"
      :tableData="tableData"
      :isChecked="false"
      @getTableData="getTableData"
    >
      <template #options="{ data }">
        <el-button type="primary" link @click="handleCheck( data)">查看</el-button>
        <el-button type="primary" link @click="deleteLiaison(data)">删除</el-button>
      </template>
    </customizeTable>
     <!-- 详情 -->
    <detailsDrawer ref="details" :formData="detailsFromData" @getTableData="getTableData"></detailsDrawer>
    <liaison-dialog ref="liaisonDialog" :formData="liaisonConfig" @isLiaison="handleLiaisonChange"></liaison-dialog>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import liaisonDialog from '@/views/customer/list/components/liaisonDialog'
import detailsDrawer from '@/views/customer/components/details'
import { clientLiaisonDeleteApi, clientLiaisonListApi as liaisonViewApi } from '@/api/client.ts'
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

    customInfo: {
      // 客户信息 客户id 和类型
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    liaisonDialog,
    detailsDrawer,
    customizeTable: defineAsyncComponent(() => import('@/views/customer/components/customizeTable'))
  },
  data() {
    return {
      total: 0,
      liaisonData: [],
      liaisonConfig: {},
 detailsFromData: {},
      where: {
        page: 1,
        limit: 15,
        eid: 0
      },
      tableData: [], // 表格的数据
      tableHeaders: [] // 表格的表头
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

      this.where.eid = this.customInfo.id
      liaisonViewApi(this.where)
        .then((res) => {
          this.tableData = res.data.list
          this.total = res.data.count
          this.total_price = res.data.total_price || 0
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },
     // 添加编辑联系人
    addLiaison(edit, row) {
      this.liaisonConfig = {
        title: edit !== 'edit' ? '添加联系人' : '编辑联系人',
        width: '570px'
      }
      this.$refs.liaisonDialog.openBox(row, this.customInfo, edit)
    },

    handleLiaisonChange() {
      this.getTableData()
      this.$emit('refresh-detail')
    },

    // 添加编辑联系人
    handleCheck(item) {
          this.detailsFromData = {
        title: '联系人查看',
        width: '1000px',
        data: item,
        types: 'liaison',
        link_type: 'liaison'
      }

      this.$refs.details.openBox(item.id, 'liaison')
      // this.liaisonConfig = {
      //   title: edit !== 'edit' ? '添加联系人' : '编辑联系人',
      //   width: '570px'
      // }
      // this.$refs.liaisonDialog.openBox(row, this.customInfo, edit)
    },

    async deleteLiaison(row) {
      await modalSure('你确定要删除该联系人吗')
      await clientLiaisonDeleteApi(row.id)
      await this.getTableData()
      this.$emit('refresh-detail')
    }
  }
}
</script>

<style lang="scss" scoped>
.station {
  height: 100%;
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
