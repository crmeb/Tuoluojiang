<!-- 客户管理 -->
<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
      <oaFromBox
        :dropdownList="dropdownList"
        :search="search"
        :title="$route.meta.title"
        :total="total"
        :loading="loading"
        :treeData="treeData"
        :viewSearch="viewSearch"
        :category="keyword"
        :whereData="where"
        :timeSearchObj="timeSearchObj"
        ref="fromBox"
        btnText="添加线索"
        @addDataFn="addDataFn"
        @confirmData="confirmData"
        @dropdownFn="dropdownFn"
      ></oaFromBox>

      <customizeTable
        flexLayout
        :keyword="keyword"
        :tableData="tableData"
        :where="where"
        :total="total"
        :loading="loading"
        ref="tableData"
        @getSearch="getSearch"
        @handleCheck="openDetails"
        @handleSelectionChange="handleSelectionChange"
        @getTableData="getTableData"
      >
        <template #options="{ data }">
          <el-button type="text" @click="openDetails(data)">查看</el-button>
          <el-button type="text" v-if="keyword == 'clue'" @click="transferCustomers(data)">转客户</el-button>
          <el-button type="text" v-else @click="handleCollection(2, data)">领取</el-button>
          <el-dropdown>
            <span class="el-dropdown-link el-button--text el-button more">
              更多
              <i class="el-icon-arrow-down" />
            </span>
            <el-dropdown-menu class="dropdown-menu-left" placement="top-start">
              <el-dropdown-item v-if="keyword === 'clue_seas'" @click.native="handleTransfer(2, data)">
                分配</el-dropdown-item
              >
              <el-dropdown-item v-if="keyword === 'clue_seas'" @click.native="transferCustomers(data)">
                转客户</el-dropdown-item
              >
              <el-dropdown-item style="border-bottom: 1px solid #f5f5f5" @click.native="handleFollowUp(data)">
                填写跟进</el-dropdown-item
              >

              <el-dropdown-item v-if="keyword === 'clue'" @click.native="handleTransfer(2, data)">
                移交同事</el-dropdown-item
              >
              <el-dropdown-item v-if="keyword === 'clue'" @click.native="handleReturn(2, data)">
                退回线索池</el-dropdown-item
              >

              <el-dropdown-item @click.native="handleDelete(data)"> 删除</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
        </template>
      </customizeTable>
    </el-card>

    <!-- 详情 -->
    <detailsDrawer ref="details" :formData="detailsFromData" @getTableData="getTableData"></detailsDrawer>
    <addForm ref="addForm" :form-data="formBoxConfig" :keyword="keyword" @getTableData="getTableData"></addForm>
    <dialog-form ref="dialogForm" :form-data="config" @isOkEdit="getTableData()" />
    <!-- 跟进弹窗 -->
    <el-dialog :visible.sync="dialogShow" class="record" title="添加跟进记录" width="40%">
      <recordUpload :form-info="formInfo" @change="recordChange"></recordUpload>
    </el-dialog>
    <!-- 导入线索 -->
    <dragUpload ref="dragUpload" @getTableData="getTableData()"></dragUpload>
    <!-- 导入/导出记录 -->
    <importRecords ref="importRecords"></importRecords>

    <!-- 移交其他同事 -->
    <transfer-dialog ref="transferDialog" :from-data="transferData" @handleTransfer="getTableData"></transfer-dialog>
    <!-- 退回线索池 -->
    <oa-dialog
      ref="oaDialog"
      :fromData="fromData"
      :formDataInit="formDataInit"
      :formConfig="formConfig"
      :formRules="formRules"
      @submit="submit"
    />
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import { getStorageJson } from '@/utils/storage'
import imageViewer from '@/components/common/imageViewer'
import { getWorkCorpConfigApi } from '@/api/setting'
import { cluesViewApi, cluesClaimApi, cluesReturnApi } from '@/api/enterprise'
import {
  delcluesApi as cluesdelApi,
  batchDelCluesApi,
  getCluesCeartepApi,
  getCluesEditApi,
  clientWorkSyncApi,
  clientToCustomerApi,
  clientExportApi
} from '@/api/client'
import { DRAWER_SIZE } from '@/constants/popupSize'
import { roterPre } from '@/settings'
export default {
  name: 'ClueList',
  components: {
    imageViewer,
    customizeTable: () => import('../components/customizeTable'),
    dragUpload: () => import('../components/dragUpload'),
    importRecords: () => import('@/views/customer/list/components/importRecords'),
    addForm: () => import('../components/addForm'),
    detailsDrawer: () => import('../components/details'),
    dialogForm: () => import('@/views/customer/list/components/index'),
    recordUpload: () => import('@/views/customer/list/components/recordUpload'),
    transferDialog: () => import('@/views/customer/list/components/transferDialog'),
    oaDialog: () => import('@/components/form-common/dialog-form'),
    oaFromBox: () => import('@/components/common/oaFromBox')
  },
  props: {
    types: {
      type: String,
      default: 'clue'
    }
  },
  data() {
    return {
      returnForm: {
        reason: ''
      },
      fromData: {
        title: '退回线索池',
        width: '540px'
      },
      formDataInit: { reason: '' },
      formRules: {
        reason: [{ required: true, message: '请输入退回原因', trigger: 'blur' }]
      },
      client_switch: null,
      formConfig: [
        {
          type: 'textarea',
          label: '说明原因：',
          placeholder: '请输入说明原因',
          key: 'reason'
        }
      ],
      config: {},
      dialogVisible: false,
      dialogShow: false,
      returnShow: false,
      resource: '',
      transferData: {},
      formBoxConfig: {},
      formInfo: {
        avatar: '',
        type: 'add',
        show: 1,
        data: {},
        link_type: 'clue',
        follow_id: 0
      },
      userId: getStorageJson('userInfo', {}).id,
      ids: [],
      tableData: [],
      where: {
        page: 1,
        limit: 15,
        view_search: this.types == 'clue' ? 2 : 5,
        types: this.types
      },
      total: 0,
      loading: false,
      rule: {
        reason: [{ required: true, message: '请输入备注信息', trigger: 'blur' }]
      },
      checkedId: [],
      detailsFromData: {},
      timeSearchObj: {},
      search: [],
      viewSearch: []
    }
  },

  mounted() {
    this.getWorkData()
  },
  computed: {
    keyword() {
      return this.types == 'clue' ? 'clue' : 'clue_seas'
    },
    dropdownList() {
      let clue = [
        { label: '移交同事', value: 2 },
        { label: '退回线索池', value: 3 },
        { label: '筛选条件设置', value: 6 },
        { label: '表头显示设置', value: 7 },
        { label: '导出', value: 8 },
        { label: '导入', value: 9 },
        { label: '导入导出记录', value: 10 },
        { label: '字段选项设置', value: 11 }
      ]
      if (this.client_switch) {
        clue.unshift({ label: '同步企业微信客户', value: 5 })
      }

      let clue_seas = [
        { label: '分配', value: 2 },
        { label: '领取', value: 4 },
        { label: '批量删除', value: 12 },
        { label: '筛选条件设置', value: 6 },
        { label: '表头显示设置', value: 7 },
        { label: '导出', value: 8 },
        { label: '导入', value: 9 },
        { label: '导入导出记录', value: 10 },
        { label: '字段选项设置', value: 11 }
      ]
      return this.types == 'clue' ? clue : clue_seas
    },

    treeData() {
      let list = [
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
      ]
      return this.types == 'clue'
        ? list
        : [
            {
              label: '全部',
              id: '5'
            }
          ]
    }
  },
  methods: {
    getSearch(val) {
      this.search = val.search
      this.viewSearch = val.viewSearch
      this.timeSearchObj = val.timeSearchObj
    },
    // 添加跟进记录
    handleFollowUp(item) {
      this.formInfo.data.id = item.id
      this.formInfo.link_type = 'clue'
      this.dialogShow = true
    },
    getWorkData() {
      getWorkCorpConfigApi().then((res) => {
        this.client_switch = res.data.client_switch
      })
    }, // 编辑线索
    handleEdit(item) {
      this.formBoxConfig = {
        title: '编辑线索',
        width: '570px'
      }
      getCluesEditApi(item.id).then((res) => {
        this.$refs.addForm.openBox(res.data, item.id)
      })
    },
    //转客户
    async transferCustomers(item) {
      if (item.customer) {
        await modalSure('存在关联的企微客户,是否转客户')
        await clientToCustomerApi(item.id)
        await this.getTableData()
      } else {
        this.config = {
          title: '新增客户',
          width: '570px',
          linkId: item.id
        }

        setTimeout(() => {
          this.$refs.dialogForm.openBox('customer', item)
        }, 300)
      }
    },
    recordChange() {
      this.dialogShow = false
    },

    async getTableData() {
      if (this.loading) return
      this.loading = true
      const res = await cluesViewApi(this.where)
      this.tableData = res.data.list
      this.total = res.data.count
      this.loading = false
    },

    // 查看
    async openDetails(item) {
      this.detailsFromData = {
        title: '线索查看',
        width: DRAWER_SIZE.LG,
        data: item,
        types: this.types,
        link_type: 'clue'
      }

      this.$refs.details.openBox(item.id, this.types)
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
          view_search: this.where.view_search
        }

        for (let key in data) {
          this.where[key] = data[key] || ''
        }
        this.where.types = this.types

        setTimeout(() => {
          this.getTableData()
        }, 100)
      }
    },
    // 删除
    async handleDelete(item) {
      await modalSure('确定删除当前线索吗')
      await cluesdelApi(item.id)
      if (this.where.page > 1 && this.tableData.length <= 1) {
        this.where.page--
      }
      await this.getTableData()
    },
    async clientworkSync(item) {
      await modalSure('确定同步企业微信客户吗')
      await clientWorkSyncApi()

      await this.getTableData()
    },

    followFn() {
      const data = {
        status: this.resource,
        types: 0
      }
      this.handleStatus(this.eid, data)
      this.dialogVisible = false
    },

    handleClose() {
      this.dialogVisible = false
    },

    // 退回公海
    handleReturn(type, row) {
      if (type === 1) {
        if (this.ids.length <= 0) {
          return this.$message.error(this.$t('customer.placeholder22'))
        }
      } else {
        this.ids = [row.id]
      }
      this.$refs.oaDialog.openBox()
    },
    cancel() {
      this.returnForm.reason = ''
      this.returnShow = false
    },
    // 确定退回线索池
    submit(data) {
      cluesReturnApi({ data: this.ids, reason: data.reason }).then((res) => {
        this.$refs.oaDialog.handleClose()
        this.getTableData()
      })
    },
    // 导出列表数据
    async exportList() {
      await clientExportApi(this.keyword, { ...this.where, page: 0, limit: 0, types: this.keyword })
    },

    // 添加线索
    addDataFn(str) {
      this.formBoxConfig = {
        title: str === 'edit' ? '编辑线索' : '新增线索',
        width: '570px'
      }
      getCluesCeartepApi().then((res) => {
        this.$refs.addForm.openBox(res.data)
      })
    },
    dropdownFn(item, val) {
      switch (item.value) {
        case 2:
          // 移交同事
          this.handleTransfer(1)
          break
        case 3:
          // 退回线索池
          this.handleReturn(1)
          break
        case 4:
          // 领取
          this.handleCollection(1)
          break
        case 5:
          // 同步企业客户
          this.clientworkSync(1)
          break
        case 6:
          // 筛选条件设置
          this.$refs.tableData.customSearchEvt(1)
          break
        case 7:
          // 表头显示设置
          this.$refs.tableData.customSearchEvt(2)
          break
        case 8:
          // 导出
          this.exportList()
          break
        case 9:
          // 导入
          this.$refs.dragUpload.openBox(this.keyword)
          break
        case 10:
          // 导入导出记录
          this.$refs.importRecords.openBox(this.keyword)
          break
        case 11:
          // 字典选项设置
          this.$router.push({ path: `${roterPre}/customer/clue/dictSetting` })
          break
        case 12:
          // 批量删除
          this.handleBatchDelete()
          break
      }
    },

    // 批量删除
    async handleBatchDelete() {
      if (this.ids.length <= 0) {
        return this.$message.error(this.$t('customer.placeholder22'))
      }
      await modalSure('确定删除选中的线索吗')
      await batchDelCluesApi({ data: this.ids })
      if (this.where.page > 1 && this.tableData.length <= this.ids.length) {
        this.where.page--
      }
      this.ids = []
      await this.getTableData()
    },

    // 转移
    handleTransfer(type, row = []) {
      if (this.ids.length <= 0 && type === 1) {
        this.$message.error(this.$t('customer.placeholder22'))
      } else {
        const ids = type === 1 ? this.ids : [row.id]
        this.transferData = {
          title: '移交其他同事',
          width: '520px',
          type: 5,
          ids
        }
        this.$refs.transferDialog.handleOpen(this.keyword)
      }
    },
    // 领取
    handleCollection(type, row = []) {
      if (this.ids.length <= 0 && type === 1) {
        this.$message.error(this.$t('customer.placeholder22'))
      } else {
        const ids = type === 1 ? this.ids : [row.id]
        modalSure(this.$t('您确定要领取此线索吗')).then(async () => {
          await cluesClaimApi({ data: ids })
          await this.getTableData()
        })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.m14 {
  padding: 14px;
}

.el-icon-info {
  margin-top: 4px;
  color: #1890ff;
  position: absolute;

  right: 15px;
}

.tooltip-wrap {
  max-width: 250px !important;
}

.right {
  border-left: 1px solid #eeeeee;
  padding-top: 14px;
}

.left {
  padding: 0;

  .title {
    padding-left: 25px;
    font-size: 14px;
    font-family: PingFangSC-Medium, PingFang SC;
    font-weight: 500;
    color: #303133;
  }
}

.tree .smallHand {
  cursor: pointer;
}

.boder {
  border: none;
}

::v-deep .el-card__body {
  padding: 0;
}

.upload {
  display: inline-block;
  margin-left: 10px;
}

.icon-star {
  i {
    font-size: 18px;
  }

  .icon-star-on {
    font-size: 24px;
    margin-left: -3px;
  }
}

::v-deep .el-button--primary.is-plain:hover {
  color: #1890ff;
  background: #e8f4ff;
  border-color: #a3d3ff;
}

.icon-star {
  i {
    font-size: 18px;
  }

  .icon-star-on {
    font-size: 24px;
    margin-left: -3px;
  }
}

.customer-tag {
  margin-right: 6px;
  background-color: transparent;
}

.fileItem {
  margin: 0;
  cursor: pointer;
  width: var(--width);
  display: inline-block;
  height: 48px;
  line-height: 1;
  align-items: center;
  position: relative;

  .file-close {
    font-size: 18px;
    position: absolute;
    top: 14px;
    right: 10px;
    color: #c0c4cc;
    cursor: pointer;
  }

  .el-image {
    margin-right: 10px;
  }
}

.left .title {
  padding-left: 25px;
  padding-top: 20px;
  font-size: 14px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: #303133;
}

.el-table .cell {
  height: 24px;
}

.ml14 {
  margin-left: 14px !important;
}

.mr14 {
  margin-right: 14px !important;
}

.el-tooltip.pointer.line1.item {
  display: flex;
}

.more {
  margin-left: 10px;
}

.flex_box {
  display: flex;

  .tips {
    span {
      margin-right: 10px;
    }
  }
}

::v-deep .divBox .el-tag {
  max-width: 91px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.customer-label .el-tag {
  border: 0;
}

.point {
  cursor: pointer;
  color: #1890ff;
}
.record {
  ::v-deep .el-dialog__body {
    padding: 20px 20px 30px 20px;
  }
}
</style>
