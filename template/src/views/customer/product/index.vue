<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
      <oaFromBox
        :search="search"
        :title="$route.meta.title"
        :total="total"
        :treeData="treeData"
        :dropdownList="dropdownList"
        :viewSearch="viewSearch"
        :category="keyword"
        :timeSearchObj="timeSearchObj"
        btnText="添加产品"
        ref="fromBox"
        @addDataFn="addDataFn"
        @dropdownFn="dropdownFn"
        @confirmData="confirmData"
      ></oaFromBox>

      <customizeTable
          flexLayout
          ref="tableData"
          :keyword="keyword"
          :tableData="tableData"
          :where="where"
          :total="total"
          :loading="loading"
          @handleCheck="openDetails"
          @getSearch="getSearch"
          @handleSelectionChange="handleSelectionChange"
          @getTableData="getTableData"
        >
          <template #options="{ data }">
            <el-button type="primary" link @click="openDetails(data)">查看</el-button>
            <el-button type="primary" link @click="handleEdit(data)">编辑</el-button>
            <el-button type="primary" link @click="handleDelete(data)">删除</el-button>
          </template>
      </customizeTable>
    </el-card>
    <!-- 详情 -->
    <detailsDrawer ref="details" :formData="detailsFromData"></detailsDrawer>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { roterPre } from '@/settings.ts'
import { productListApi, productDelApi } from '@/api/client.ts'
import { DRAWER_SIZE } from '@/constants/popupSize.ts'
import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'product',
  components: {
    customizeTable: defineAsyncComponent(() => import('../components/customizeTable')),
    detailsDrawer: defineAsyncComponent(() => import('./details')),
    oaFromBox
  },

  data() {
    return {
      ids: [],
      tableData: [],
      where: {
        page: 1,
        limit: 15,
        types: 'product'
      },
      dropdownList: [
        { label: '筛选条件设置', value: 1 },
        { label: '表头显示设置', value: 2 },
        { label: '字典选项设置', value: 3 },
      ],
      timeSearchObj: {},
      keyword: 'product',
      total: 0,
      loading: false,
      detailsFromData: {},
      search: [],
      treeData: [
        {
          label: '全部',
          id: ''
        }
      ],
      viewSearch: []
    }
  },

  mounted() {
    // this.getTableData()
  },
  methods: {
    getSearch(val) {
      this.search = val.search
      this.viewSearch = val.viewSearch
      this.timeSearchObj = val.timeSearchObj
    },
    dropdownFn(item) {
      switch (item.value) {
        case 1:
          // 筛选条件设置
          this.$refs.tableData.customSearchEvt(1)
          break
        case 2:
          // 表头显示设置
          this.$refs.tableData.customSearchEvt(2)
          break
        case 3:
          // 字典选项设置
          this.$router.push({ path: `${roterPre}/customer/product/dictSetting` })
          break
      }
    },

    //添加商品
    addDataFn() {
      this.$router.push({
        path: `${roterPre}/customer/product/addProduct`
      })
    },
    // 编辑商品
    handleEdit(item) {
      this.$router.push({
        path: `${roterPre}/customer/product/addProduct`,
        query: { id: item.id }
      })
    },

    async getTableData() {
      if (this.loading) return
      this.loading = true
      const res = await productListApi(this.where)
      this.tableData = res.data.list
      this.total = res.data.count
      this.loading = false
    },

    // 查看
    async openDetails(item) {
      this.detailsFromData = {
        title: '产品查看',
        width: DRAWER_SIZE.LG,
        data: item,
        types: this.types
      }

      this.$refs.details.openBox(item.id, this.types)
    },
    handleSelectionChange(ids) {
      this.ids = ids
    },
    confirmData(data, resetState) {
      if (data == 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          view_search: this.where.view_search,
          types: this.keyword,
          // 排序等由搜索栏维护的默认参数，重置后需一并带回
          ...resetState
        }
        this.labelText = ''
        this.getTableData('')
      } else {
        this.where = {
          page: 1,
          limit: 15,
          types: 'product'
        }

        for (let key in data) {
          this.where[key] = data[key]
        }

        setTimeout(() => {
          this.getTableData()
        }, 100)
      }
    },
    // 删除
    async handleDelete(item) {
      await modalSure('确定删除当前产品')
      await productDelApi(item.id)
      if (this.where.page > 1 && this.tableData.length <= 1) {
        this.where.page--
      }
      await this.getTableData()
    },

    handleClose() {
      this.dialogVisible = false
    }
  }
}
</script>

<style lang="scss" scoped></style>
