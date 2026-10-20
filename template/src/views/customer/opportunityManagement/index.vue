<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
      <oaFromBox
        :dropdownList="dropdownList"
        :search="search"
        :title="$route.meta.title"
        :total="total"
        :treeData="treeData"
        :viewSearch="viewSearch"
        :whereData="where"
        :category="keyword"
        :timeSearchObj="timeSearchObj"
        ref="fromBox"
        btnText="添加商机"
        @addDataFn="addDataFn"
        @confirmData="confirmData"
        @dropdownFn="dropdownFn"
      ></oaFromBox>

      <customizeTable
        flexLayout
        ref="tableData"
        :keyword="keyword"
        :where="where"
        :total="total"
        :loading="loading"
        @handleCheck="openDetails"
        :tableData="tableData"
        @getSearch="getSearch"
        @handleSelectionChange="handleSelectionChange"
        @getTableData="getTableData"
      >
        <template #options="{ data }">
          <el-button type="primary" link @click="openDetails(data)">查看</el-button>
          <el-button
            type="primary" link
            v-if="!data.is_sign && isModuleEnabled(CUSTOMER_MODULE_KEYS.CONTRACT)"
            @click="addSigning(data)"
            >生成合同</el-button
          >

          <el-dropdown>
            <el-button type="primary" link class="el-dropdown-link more ml10">
              更多
              <i class="el-icon-arrow-down" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu style="text-align: center">
                <el-dropdown-item v-if="isModuleEnabled(CUSTOMER_MODULE_KEYS.ORDER)" @click="addContract(data)">
                  生成订单
                </el-dropdown-item>
                <el-dropdown-item style="border-bottom: 1px solid #f5f5f5" @click="handleFollowUp(data)">
                  填写跟进
                </el-dropdown-item>
                <el-dropdown-item @click="handleTransfer(2, data)"> 移交同事 </el-dropdown-item>
                <el-dropdown-item v-if="data.status && data.status.value == 1" @click="handleInvalid(data, 4)">
                  失效
                </el-dropdown-item>
                <el-dropdown-item v-if="data.status && data.status.value == 4" @click="handleInvalid(data, 1)">
                  取消失效
                </el-dropdown-item>
                <el-dropdown-item @click="handleDelete(data)"> 删除 </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </customizeTable>
    </el-card>

    <!-- 添加商机 -->
    <addForm ref="addForm" :form-data="formBoxConfig" :keyword="keyword" @getTableData="getTableData"></addForm>
    <!-- 移交其他同事 -->
    <transfer-dialog ref="transferDialog" :from-data="transferData" @handleTransfer="getTableData"></transfer-dialog>
    <!-- 详情 -->
    <detailsDrawer ref="details" :formData="detailsFromData" @getTableData="getTableData"></detailsDrawer>
    <!-- 跟进弹窗 -->
    <el-dialog v-model="dialogShow" class="record" title="添加跟进记录" width="40%">
      <recordUpload :form-info="formInfo" @change="recordChange"></recordUpload>
    </el-dialog>
    <add-contract ref="addContract" :form-data="contractFromData"></add-contract>
    <!-- 导入商机 -->
    <dragUpload ref="dragUpload" @getTableData="getTableData()"></dragUpload>

    <!-- 导入/导出记录 -->
    <importRecords ref="importRecords"></importRecords>
    <!-- 生成合同 -->
    <addContractSign ref="addContractSign"></addContractSign>
  </div>
</template>
<script>
import { mapStores } from 'pinia'
import { useAppConfigStore } from '@/stores/appConfig.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import {
  oddsCreateApi,
  oddsCreateEditApi,
  oddsListApi,
  oddsEditApi,
  oddsStatusApi,
  oddsDelApi,
  clientExportApi
} from '@/api/client.ts'
import { DRAWER_SIZE } from '@/constants/popupSize.ts'
import { roterPre } from '@/settings.ts'
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules.ts'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'opportunityManagement',
  components: {
    customizeTable: defineAsyncComponent(() => import('../components/customizeTable')),
    detailsDrawer: defineAsyncComponent(() => import('../components/details')),
    addContractSign: defineAsyncComponent(() => import('@/views/customer/signing/components/addContractSign')),
    dragUpload: defineAsyncComponent(() => import('../components/dragUpload')),
    addForm: defineAsyncComponent(() => import('../components/addForm')),
    importRecords: defineAsyncComponent(() => import('@/views/customer/list/components/importRecords')),
    recordUpload: defineAsyncComponent(() => import('@/views/customer/list/components/recordUpload')),
    transferDialog: defineAsyncComponent(() => import('@/views/customer/list/components/transferDialog')),
    addContract: defineAsyncComponent(() => import('@/views/customer/contract/components/addContract')),
    oaFromBox
  },

  computed: {
    ...mapStores(useAppConfigStore)
  },
  data() {
    return {
      CUSTOMER_MODULE_KEYS,
      returnForm: {
        reason: ''
      },
      contractFromData: {},
      dropdownList: [
        { label: '移交同事', value: 1 },
        { label: '筛选条件设置', value: 2 },
        { label: '表头显示设置', value: 3 },
        { label: '导出', value: 4 },
        { label: '导入', value: 5 },
        { label: '导入导出记录', value: 6 },
        { label: '字段选项设置', value: 7 }
      ],
      treeData: [
        {
          label: '我负责的',
          id: 1
        },
        {
          label: '下属负责的',
          id: 2,
          default: true
        },
        {
          label: '我关注的',
          id: 3
        },
        {
          label: '急需跟进',
          id: 4,
          line: true
        }
      ],
      transferData: {},
      dialogVisible: false,
      dialogShow: false,
      returnShow: false,
      resource: '',
      timeSearchObj: {},
      transferData: {},
      formBoxConfig: {},
      formInfo: {
        avatar: '',
        type: 'add',
        show: 1,
        data: {},
        link_type: 'odds',
        follow_id: 0
      },
      ids: [],
      tableData: [],

      where: {
        page: 1,
        limit: 15,
        types: '',
        view_search: 2
      },
      keyword: 'odds',
      total: 0,
      loading: false,
      detailsFromData: {},
      search: [],
      viewSearch: []
    }
  },

  mounted() {
    // this.getTableData()
  },
  methods: {
    // 客户模块开关用 v-if 判断：原自定义指令直接删 DOM，挂在 el-dropdown-item（非元素根节点）上不生效，
    // 与 v-if 同用时还会让 Vue 更新到已被移除的节点而报错
    isModuleEnabled(key) {
      return this.appConfigStore.isCustomerModuleEnabled(key)
    },
    getSearch(val) {
      this.search = val.search
      this.viewSearch = val.viewSearch
      this.timeSearchObj = val.timeSearchObj
    },

    //添加商机
    addDataFn(str) {
      this.formBoxConfig = {
        title: str === 'edit' ? '编辑商机' : '新增商机',
        width: '1129px',
        types: this.keyword
      }
      oddsCreateApi().then((res) => {
        this.$refs.addForm.openBox(res.data)
      })
    },
    // 生成合同
    addSigning(data) {
      if (!data.eid) {
        this.$message.error('无效的客户')
        return false
      }
      data.link_type = '5'

      this.$refs.addContractSign.openBox('', 'add', data.eid, data)
    },
    // 添加订单
    addContract(data) {
      oddsCreateEditApi(data.id, { eid: data.eid, odds_id: data.id }).then((res) => {
        this.contractFromData = {
          title: '添加订单',
          width: '1129px',
          product: res.data.product,
          odds_id: data.id,
          eid: data.eid
        }
        setTimeout(() => {
          this.$refs.addContract.openBox()
        }, 300)
      })
    },

    // 编辑商机
    handleEdit(data) {
      this.formBoxConfig = {
        title: '编辑商机',
        width: '1000px',
        types: this.keyword
      }
      oddsCreateEditApi(data.id).then((res) => {
        this.$refs.addForm.openBox(res.data, data.id)
      })
    },

    async getTableData() {
      if (this.loading) return
      this.loading = true
      const res = await oddsListApi(this.where)
      this.tableData = res.data.list
      this.total = res.data.count
      this.loading = false
    },
    // 添加跟进记录
    handleFollowUp(item) {
      this.formInfo.data.eid = item.id
      this.formInfo.data.id = item.id
      this.dialogShow = true
    },
    recordChange() {
      this.dialogShow = false
    }, // 转移
    handleTransfer(type, row = []) {
      if (this.ids.length <= 0 && type === 1) {
        this.$message.error('至少选择一项')
      } else {
        const ids = type === 1 ? this.ids : [row.id]
        this.transferData = {
          title: '移交其他同事',
          width: '520px',
          type: 1,
          ids
        }
        this.$refs.transferDialog.handleOpen(this.keyword)
      }
    },
    // 查看
    async openDetails(item) {
      this.detailsFromData = {
        title: '商机查看',
        width: DRAWER_SIZE.LG,
        data: item,
        eid: item.id,
        types: this.keyword,
        link_type: 'odds',
        odds_id: item.id
      }

      this.$refs.details.openBox(item.id, this.keyword)
    },
    handleSelectionChange(list, ids) {
      this.ids = ids
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
          this.where[key] = data[key]
        }

        setTimeout(() => {
          this.getTableData()
        }, 100)
      }
    },
    // 失效
    async handleInvalid(item, type) {
      const tip = type === 4 ? '确定失效当前商机' : '确定取消失效当前商机'
      await modalSure(tip)
      await oddsStatusApi(item.id, type)
      await this.getTableData()
    },

    // 删除
    async handleDelete(item) {
      await modalSure('确定删除当前商机')
      await oddsDelApi(item.id)
      if (this.where.page > 1 && this.tableData.length <= 1) {
        this.where.page--
      }
      await this.getTableData()
    },

    handleClose() {
      this.dialogVisible = false
    },

    // 导出列表数据
    async exportList() {
      await clientExportApi(this.keyword, { ...this.where, page: 0, limit: 0, types: this.keyword })
    },

    dropdownFn(item, val) {
      switch (item.value) {
        case 1:
          // 移交同事
          this.handleTransfer(1)

          break
        case 2:
          // 筛选条件设置
          this.$refs.tableData.customSearchEvt(1)
          break
        case 3:
          // 表头显示设置
          this.$refs.tableData.customSearchEvt(2)
          break
        case 4:
          // 导出
          this.exportList()
          break
        case 5:
          // 导入
          this.$refs.dragUpload.openBox(this.keyword)
          break
        case 6:
          // 导入导出记录
          this.$refs.importRecords.openBox(this.keyword)
          break
        case 7:
          // 字典选项设置
          this.$router.push({ path: `${roterPre}/customer/opportunityManagement/dictSetting` })
          break
      }
    }
  }
}
</script>

<style lang="scss" scoped>
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.record {
  .el-dialog__body {
    padding: 20px 20px 30px 20px;
  }
}
</style>
