<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
      <oaFromBox
        :search="search"
        :title="$route.meta.title"
        :total="total"
        :dropdownList="dropdownList"
        :treeData="treeData"
        :viewSearch="viewSearch"
        :whereData="where"
        :timeSearchObj="timeSearchObj"
        :category="keyword"
        ref="fromBox"
        btnText="添加联系人"
        :isAddBtn="false"
        @dropdownFn="dropdownFn"
        @addDataFn="addDataFn"
        @confirmData="confirmData"
      ></oaFromBox>

      <customizeTable
        flexLayout
        ref="tableData"
        :keyword="keyword"
        :where="where"
        :tableData="tableData"
        :total="total"
        :loading="loading"
        :isChecked="false"
        @getSearch="getSearch"
        @getTableData="getTableData"
      >
        <template #options="{ data }">
          <el-button type="text" @click="handleCheck(data)">查看</el-button>
          <el-button type="text" @click="handleDel(data)">删除</el-button>
        </template>
      </customizeTable>
    </el-card>
    <!-- 详情 -->
    <detailsDrawer ref="details" :formData="detailsFromData" @getTableData="getTableData"></detailsDrawer>
    <!-- 导入联系人 -->
    <dragUpload ref="dragUpload" @getTableData="getTableData()"></dragUpload>
    <!-- 导入/导出记录 -->
    <importRecords ref="importRecords"></importRecords>

    <!-- 通用弹窗表单   -->
    <liaison-dialog ref="liaisonDialog" :formData="liaisonConfig" @isLiaison="getTableData"></liaison-dialog>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import { clientExportApi, clientLiaisonDeleteApi, clientLiaisonListApi as liaisonViewApi } from '@/api/client'
import { DRAWER_SIZE } from '@/constants/popupSize'
import { roterPre } from '@/settings'

export default {
  name: 'Liaison',
  components: {
    customizeTable: () => import('../components/customizeTable'),
    detailsDrawer: () => import('../components/details'),
    dragUpload: () => import('../components/dragUpload'),
    importRecords: () => import('@/views/customer/list/components/importRecords'),
    liaisonDialog: () => import('@/views/customer/list/components/liaisonDialog'),
    oaFromBox: () => import('@/components/common/oaFromBox')
  },

  data() {
    return {
      treeData: [
        {
          label: '我负责的',
          id: 1
        },
        {
          label: '下属负责的',
          id: 2,
          default: true
        }
      ],
      liaisonConfig: {},
      detailsFromData: {},
      tableData: [],
      where: {
        page: 1,
        limit: 15,
        view_search: 1
      },
      dropdownList: [
        { label: '筛选条件设置', value: 1 },
        { label: '表头显示设置', value: 2 },
        { label: '导出', value: 3 },
        { label: '导入', value: 4 },
        { label: '导入导出记录', value: 5 },
        { label: '字段选项设置', value: 6 }
      ],
      keyword: 'liaison',
      total: 0,
      loading: false,
      search: [],
      timeSearchObj: {},
      customInfo: {},
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

    //添加联系人
    addDataFn() {
      this.liaisonConfig = {
        title: '新增联系人',
        width: '570px'
      }
      this.$refs.liaisonDialog.openBox('', this.customInfo, '')
    },

    async getTableData() {
      if (this.loading) return
      this.loading = true
      const res = await liaisonViewApi(this.where)
      this.tableData = res.data.list
      this.total = res.data.count
      this.loading = false
    },

    handleCheck(item) {
      this.detailsFromData = {
        title: '联系人查看',
        width: DRAWER_SIZE.LG,
        data: item,
        types: 'liaison',
        link_type: 'liaison'
      }

      this.$refs.details.openBox(item.id, 'liaison')
    },

    // 导出列表数据
    async exportList() {
      await clientExportApi(this.keyword, { ...this.where, page: 0, limit: 0, types: this.keyword })
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
          // 导出
          this.exportList()
          break
        case 4:
          // 导入
          this.$refs.dragUpload.openBox(this.keyword)
          break
        case 5:
          // 导入导出记录
          this.$refs.importRecords.openBox(this.keyword)
          break
        case 6:
          // 字典选项设置
          this.$router.push({ path: `${roterPre}/customer/liaison/dictSetting` })
          break
      }
    },

    confirmData(data, resetState) {
      if (data == 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          view_search: this.where.view_search,
          // 排序等由搜索栏维护的默认参数，重置后需一并带回
          ...resetState
        }
        this.labelText = ''
        this.getTableData('')
      } else {
        this.where = {
          page: 1,
          limit: 15
        }

        for (let key in data) {
          this.where[key] = data[key] || ''
        }
        setTimeout(() => {
          this.getTableData()
        }, 100)
      }
    },
    // 删除
    async handleDel(item) {
      await modalSure('确定删除当前联系人')
      await clientLiaisonDeleteApi(item.id)
      if (this.where.page > 1 && this.tableData.length <= 1) {
        this.where.page--
      }
      await this.getTableData()
    }
  }
}
</script>

<style lang="scss" scoped></style>
