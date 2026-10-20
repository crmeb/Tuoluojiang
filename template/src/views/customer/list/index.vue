<!-- 客户管理 -->
<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
      <oaFromBox
        :dropdownList="dropdownList"
        :search="search"
        :ids="ids"
        :title="$route.meta.title"
        :total="total"
        :treeData="types == 'customer' ? treeDataGroup : [{ label: '全部', id: '' }]"
        :treeDefault="defaultFrame"
        :viewSearch="viewSearch"
        :timeSearchObj="timeSearchObj"
        :category="keyword"
        btnText="添加客户"
        :whereData="where"
        ref="fromBox"
        @addDataFn="addDataFn"
        @confirmData="confirmData"
        @dropdownFn="dropdownFn"
        @treeChange="handleNodeClick"
      >
      </oaFromBox>

      <customizeTable
        flexLayout
        ref="tableData"
        :keyword="keyword"
        :tableData="tableData"
        :where="where"
        :total="total"
        :loading="loading"
        :selectedCount="crossPageSelectionMap.size"
        labelClickable
        @handleCustomerCheck="handleCheck"
        @handleLabelClick="handleLabel"
        @getSearch="getSearch"
        @handleSelectionChange="handleSelectionChange"
        @getTableData="getTableData"
      >
        <template #options="{ data }">
          <el-button type="primary" link @click="handleCheck(data)">查看</el-button>
          <el-button v-if="types == 'customer'" type="primary" link @click="handleLabel(data)">设置标签</el-button>

          <el-button v-else type="primary" link @click="receive(2, data)">领取</el-button>
          <el-dropdown>
            <el-button type="primary" link class="el-dropdown-link more">
              更多
              <i class="el-icon-arrow-down" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu style="text-align: center">
                <!-- 客户：添加商机、添加订单、添加合同、填写跟进 -->
                <el-dropdown-item
                  v-if="types == 'customer' && customerModule.opportunity"
                  @click="addOdds(data)"
                >
                  添加商机
                </el-dropdown-item>
                <el-dropdown-item v-if="types == 'customer' && customerModule.order" @click="addContract(data)">
                  添加订单
                </el-dropdown-item>
                <el-dropdown-item v-if="types == 'customer' && customerModule.contract" @click="addSign(data)">
                  添加合同
                </el-dropdown-item>
                <el-dropdown-item
                  style="border-bottom: 1px solid #f5f5f5"
                  v-if="types == 'customer'"
                  @click="handleFollowUp(data)"
                >
                  填写跟进
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="types == 'customer' && userId == data.creator_uid"
                  @click="handleTransfer(2, data)"
                >
                  移交同事
                </el-dropdown-item>
                <el-dropdown-item v-if="types !== 'customer_seas'" @click="handleReturn(0, data)">
                  退回公海
                </el-dropdown-item>
  
                <!-- 客户公海：分配、设置标签、标为流失/取消流失 -->
                <el-dropdown-item v-if="types == 'customer_seas'" @click="handleTransfer(2, data)">
                  分配
                </el-dropdown-item>
                <el-dropdown-item v-if="types == 'customer_seas'" @click="handleLabel(data)">
                  设置标签
                </el-dropdown-item>
                <el-dropdown-item
                  style="border-top: 1px solid #f5f5f5"
                  v-if="types == 'customer_seas'"
                  @click="markedLoss(data, 1)"
                >
                  {{ data.customer_status.value == 2 ? '取消流失' : '标为流失' }}
                </el-dropdown-item>
  
                <el-dropdown-item @click="handleDelete(data)">删除 </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </customizeTable>
    </el-card>

    <!-- 修改客户状态 -->
    <el-dialog
      :before-close="handleClose"
      :close-on-click-modal="false"
      v-model="dialogVisible"
      title="修改客户状态"
      width="30%"
    >
      <el-form class="mt20" label-width="80px">
        <el-form-item label="客户状态：" prop="resource">
          <el-radio-group v-model="resource">
            <el-radio label="0" value="0">跟进中</el-radio>
            <el-radio label="1" value="1">已成交</el-radio>
            <el-radio label="2" value="2">已放弃</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取 消</el-button>
          <el-button type="primary" @click="followFn">确 定</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 通用弹窗表单   -->
    <dialog-form ref="dialogForm" :form-data="formBoxConfig" @isOkEdit="getTableData()" />
    <edit-customer ref="editCustomer" :form-data="fromData" @isOkEdit="getTableData()"></edit-customer>
    <!-- 添加订单 -->
    <add-contract ref="addContract" :form-data="contractFromData" @getTableData="getTableData()"></add-contract>
    <!-- 添加合同 -->
    <add-contract-sign ref="addContractSign" @getTableData="getTableData()"></add-contract-sign>
    <transfer-dialog ref="transferDialog" :from-data="transferData" @handleTransfer="getTable"></transfer-dialog>
    <!-- 跟进弹窗 -->
    <el-dialog v-model="dialogShow" class="record" title="添加跟进记录" width="40%">
      <recordUpload :form-info="formInfo" @change="recordChange"></recordUpload>
    </el-dialog>
    <!-- 退回公海 -->
    <el-dialog :append-to-body="true" v-model="returnShow" title="退回客户公海" width="540px">
      <el-form ref="returnForm" :model="returnForm" :rules="rule">
        <el-form-item label="说明原因：" label-width="90px" prop="reason">
          <el-input
            v-model="returnForm.reason"
            :autosize="{ minRows: 4, maxRows: 10 }"
            :maxlength="100"
            placeholder="请输入备注信息，最多可输入100字"
            type="textarea"
          ></el-input>
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="cancel">取 消</el-button>
          <el-button type="primary" @click="submit()">确 定</el-button>
        </div>
      </template>
    </el-dialog>
    <!-- 客户标签弹窗 -->
    <label-dialog ref="labelDialog" :config="labelData" @handleLabelConf="handleLabelConf"></label-dialog>

    <!-- 数据共享列表 -->
    <share ref="share"></share>
    <!-- 添加商机 -->
    <addForm ref="addForm" :form-data="formBoxConfig" :keyword="`odds`" @getTableData="getTableData()"></addForm>

    <!-- 导出组件 -->
    <!-- <export-excel ref="exportExcel"  :template="false" /> -->

    <!-- 导入/导出记录 -->
    <importRecords ref="importRecords"></importRecords>
    <!-- 导入客户 -->
    <dragUpload ref="dragUpload" @getTableData="getTableData()"></dragUpload>
    <!-- 合并客户 -->
    <el-dialog :append-to-body="true" v-model="mergeCustomerShow" title="合并客户数据" width="30%">
      <el-form ref="returnForm" :model="returnForm" :rules="rule">
        <el-form-item label="主客户：" prop="reason" label-width="80px">
          <el-select v-model="mergeCustomerId" placeholder="请选择主客户" style="width: 80%">
            <el-option
              v-for="item in mergeCustomerList"
              :key="item.id"
              :label="item.label"
              :value="item.id"
              class="flex lh-center"
            >
              <span style="float: left; width: 80%" class="mr10">{{ item.name }}</span>
              <img :src="item.img" style="width: 20px; height: 20px; border-radius: 50%" alt="" />
              <span class="ml10" v-if="item.salesman">{{ item.salesman }}</span>
            </el-option>
          </el-select>
          <div class="tips">客户一旦合并，不可回退，请谨慎操作！</div>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="cancel">取 消</el-button>
          <el-button type="primary" @click="mergeCustomerSubmit">确 定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>
<script>
import { mapStores } from 'pinia'
import { useAppConfigStore } from '@/stores/appConfig.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { getStorageJson } from '@/utils/storage.ts'
import {
  customerViewApi,
  customerReturnApi,
  customerLostApi,
  customerCancelLostApi,
  customerClaimApi
} from '@/api/enterprise.ts'
import {
  oddsCreateApi,
  clientCluesMergeApi,
  clientDataDeleteApi,
  clientDataLabelApi,
  clientExportApi
} from '@/api/client.ts'
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules.ts'
import { DRAWER_SIZE } from '@/constants/popupSize.ts'
import { roterPre } from '@/settings.ts'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'FinanceList',
  components: {
    customizeTable: defineAsyncComponent(() => import('../components/customizeTable')),
    dragUpload: defineAsyncComponent(() => import('../components/dragUpload')),
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    recordUpload: defineAsyncComponent(() => import('@/views/customer/list/components/recordUpload')),
    share: defineAsyncComponent(() => import('@/views/develop/module/components/share')),
    addForm: defineAsyncComponent(() => import('@/views/customer/components/addForm')),
    dialogForm: defineAsyncComponent(() => import('./components/index')),
    editCustomer: defineAsyncComponent(() => import('./components/editCustomer')),
    labelDialog: defineAsyncComponent(() => import('./components/labelDialog')),
    selectLabel: defineAsyncComponent(() => import('@/components/form-common/select-label')),
    addContract: defineAsyncComponent(() => import('@/views/customer/contract/components/addContract')),
    addContractSign: defineAsyncComponent(() => import('@/views/customer/signing/components/addContractSign')),
    transferDialog: defineAsyncComponent(() => import('@/views/customer/list/components/transferDialog')),
    oaFromBox,
    importRecords: defineAsyncComponent(() => import('@/views/customer/list/components/importRecords'))
  },
  props: {
    types: {
      type: String,
      default: 'customer'
    }
  },
  data() {
    return {
      crossPageSelectionMap: new Map(),
      isRestoringSelection: false,
      returnForm: {
        reason: ''
      },
      dialogVisible: false,
      mergeCustomerShow: false,
      mergeCustomerList: [],
      mergeCustomerId: '',
      dialogShow: false,
      returnShow: false,
      resource: '',
      fromData: {},
      formBoxConfig: {},
      gettime: '',
      userId: getStorageJson('userInfo', {}).id,
      formInfo: {
        avatar: '',
        type: 'add',
        link_type: 'customer',
        show: 1,
        data: {},
        follow_id: 0
      },
      rowData: {},
      tableData: [],
      tab: '',
      labelText: '',
      salesmanList: [],
      defaultFrame: '',
      where: {
        page: 1,
        limit: 15,
        view_search: this.types === 'customer' ? 2 : 7
      },
      total: 0,
      type: '1',
      labelData: {},
      id: null,
      ids: [],
      eid: null,
      contractFromData: {},
      transferData: {},
      loading: false,
      transferDataList: [],
      timeSearchObj: {},
      rule: {
        reason: [{ required: true, message: '请输入备注信息', trigger: 'blur' }]
      },
      checkedId: [],
      treeDataGroup: [
        {
          id: 1,
          label: '我负责的'
        },
        {
          id: 2,
          label: '下属负责的',
          default: true
        },
        {
          id: 9,
          label: '我协作的'
        },
        {
          id: 3,
          label: '我关注的'
        },

        {
          id: 6,
          label: '急需跟进',
          line: true
        }
      ],

      search: [],
      dropdownList: [
        { label: '设置标签', value: 1 },
        { label: '移交同事', value: 2 },
        { label: '分配', value: 5 },
        { label: '领取', value: 6 },
        { label: '标为流失', value: 7 },
        { label: '退回公海', value: 3 },
        { label: '合并客户', value: 9 },
        { label: '筛选条件设置', value: 11 },
        { label: '表头显示设置', value: 10 },
        { label: '导出', value: 4 },
        { label: '导入', value: 8 },
        { label: '导入导出记录', value: 12 },
        { label: '字段选项设置', value: 13 }
      ],
      viewSearch: []
    }
  },

  created() {
    let dropdownValueList = [1, 2, 3, 10, 11, 4, 8, 9, 12, 13]
    if (this.types == 'customer_seas') {
      dropdownValueList = [1, 5, 6, 7, 10, 11, 4, 8, 9, 12, 13]
    }
    for (let i = 0; i < this.dropdownList.length; i++) {
      if (!dropdownValueList.includes(this.dropdownList[i].value)) {
        this.dropdownList.splice(i, 1)
        i--
      }
    }
    const query = this.$route.query
    if (query.id && query.name) {
      this.where.name = query.name
    }

    this.defaultFrame = ''
  },

  computed: {
    ...mapStores(useAppConfigStore),
    keyword() {
      return this.types == 'customer' ? 'customer' : 'customer_seas'
    },
    // 商机/订单/合同模块开关，控制操作栏对应的添加入口
    customerModule() {
      const isCustomerModuleEnabled = this.appConfigStore.isCustomerModuleEnabled
      return {
        opportunity: isCustomerModuleEnabled(CUSTOMER_MODULE_KEYS.OPPORTUNITY),
        order: isCustomerModuleEnabled(CUSTOMER_MODULE_KEYS.ORDER),
        contract: isCustomerModuleEnabled(CUSTOMER_MODULE_KEYS.CONTRACT)
      }
    }
  },
  methods: {
    getSearch(val) {
      this.search = val.search
      this.viewSearch = val.viewSearch
      this.timeSearchObj = val.timeSearchObj
      this.transferDataList = val.tableHeaders
    },
    // 添加商机
    addOdds(data) {
      this.formBoxConfig = {
        title: '新增商机',
        width: '1000px',
        types: 'odds'
      }
      oddsCreateApi({ eid: data.id }).then((res) => {
        res.data.forEach((item) => {
          item.data.forEach((el) => {
            if (el.key == 'eid') {
              el.value = data.id
            }
          })
        })
        this.$refs.addForm.openBox(res.data)
      })
    },

    // 添加合同
    addSign(row) {
      this.$refs.addContractSign.openBox('', 'add', row.id)
    },

    getTable() {
      this.clearCrossPageSelection()
      this.getTableData()
    },
    // 添加跟进记录
    handleFollowUp(item) {
      this.formInfo.data.id = item.id
      this.formInfo.link_type = this.keyword
      this.dialogShow = true
    },
    recordChange() {
      this.dialogShow = false
    },

    async getTableData() {
      if (this.loading) return
      this.loading = true
      this.where.types = this.keyword
      const res = await customerViewApi(this.where)
      this.tableData = res.data.list
      this.total = res.data.count
      this.loading = false
      // 翻页后回填选中状态
      this.$nextTick(() => {
        this.restoreSelection()
      })
    },

    handleNodeClick(data) {
      // types=1 我查看的    types=2 我负责的
      this.labelText = data.value
      this.clearCrossPageSelection()

      this.where.page = 1
      if (data) {
        this.where.follows = data.value
      } else {
        this.where.follows = ''
      }
      this.getTableData(this.labelText)
    },

    // 导出列表数据
    async exportList() {
      await clientExportApi(this.keyword, { ...this.where, page: 0, limit: 0, types: this.keyword })
    },
    getFieldValue(field, val) {
      if (Object.prototype.toString.call(val) === '[object Object]') {
        return val ? val.name : '--'
      } else if (Array.isArray(val)) {
        return val.length > 0 ? val.map((item) => item.name).join(',') : '--'
      } else {
        return val || '--'
      }
    },
    // 添加客户
    async addFinance(str, row) {
      this.formBoxConfig = {
        title: str === 'edit' ? '编辑客户' : '新增客户',
        width: '570px'
      }
      this.$refs.dialogForm.openBox(this.keyword, row, str)
    },

    // 查看
    async handleCheck(item) {
      item.eid = item.id
      item.cid = 0
      this.fromData = {
        title: '编辑客户',
        width: DRAWER_SIZE.XL,
        data: item,
        link_type: 'customer',
        types: this.types,
        type: 'add'
      }

      this.$refs.editCustomer.tabIndex = '1'
      this.$refs.editCustomer.tabNumber = 1
      this.$refs.editCustomer.openBox(item.id, this.types)
    },

    handleTransfer(type, row = []) {
      if (this.ids.length <= 0 && type === 1) {
        this.$message.error('至少选择一项')
      } else {
        let ids = []
        if (type === 1) {
          // 批量
          this.ids.map((value) => {
            ids.push(value.id)
          })
        } else {
          if (this.ids.length > 0) {
            this.$refs.tableData.$refs.table.clearSelection()
          }
          ids.push(row.id)
        }
        this.transferData = {
          title: type === 1 ? '移交其他同事' : '转移设置',
          width: '520px',
          type: 1,
          ids
        }
        this.$refs.transferDialog.handleOpen(this.types)
      }
    },

    confirmData(data, resetState) {
      if (data == 'reset') {
        // 仅重置搜索条件时清空全部选中
        this.clearCrossPageSelection()
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
        // 修改筛选条件后保留已选中数据，由 getTableData 后的 restoreSelection 回填
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
    async handleDelete(item) {
      await modalSure('你确定要删除该客户吗')
      await clientDataDeleteApi(item.id)
      if (this.where.page > 1 && this.tableData.length <= 1) {
        this.where.page--
      }
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

    handleLabelConf(res) {
      const data = this.rowData.id ? this.rowData.id : this.ids.map((value) => value.id)
      const label = res.data.map((value) => value.id)
      this.batchSetLabel({ data, label })
    },
    handleLabel(row) {
      this.rowData = row
      this.labelData = {
        title: '客户标签',
        width: '540px',
        label: row.customer_label,
        edit: 1
      }
      this.$refs.labelDialog.handleOpen()
    },
    // 退回公海
    handleReturn(type, row) {
      if (this.ids.length <= 0 && type === 1) {
        this.$message.error('至少选择一项')
      } else {
        if (row) {
          this.id = [row.id]
        }
        this.returnShow = true
      }
    },

    // 合并客户
    mergeCustomerFn() {
      if (this.ids.length < 2) {
        this.$message.error('最少需要选择两个客户')
        return
      } else {
        this.mergeCustomerShow = true
        this.mergeCustomerList = []

        this.ids.map((item) => {
          let obj = {
            label: item.customer_name,
            name: item.customer_name,
            salesman: item.salesman ? item.salesman.name : '--',
            id: item.id,
            img: item.salesman ? item.salesman.avatar : ''
          }
          if (item.salesman && item.salesman.name) {
            obj.label = item.customer_name + '' + '(' + item.salesman.name + ')'
          } else {
            obj.label = item.customer_name
          }
          this.mergeCustomerList.push(obj)
        })
      }
    },

    mergeCustomerSubmit() {
      if (this.mergeCustomerId == '') {
        this.$message.error('请选择主客户')
        return
      }
      let data = {
        main_id: this.mergeCustomerId,
        ids: this.checkedId
      }
      clientCluesMergeApi(data).then((res) => {
        this.cancel()
        setTimeout(() => {
          this.getTableData()
        }, 300)
      })
    },

    cancel() {
      this.mergeCustomerList = []
      this.returnForm.reason = ''
      this.mergeCustomerId = ''
      this.mergeCustomerShow = false
      this.returnShow = false
      this.clearCrossPageSelection()
    },
    // 确定退回公海
    submit() {
      let checkedId = Array.from(this.checkedId)
      let data = {
        data: checkedId.length ? checkedId : this.id,
        reason: this.returnForm.reason
      }
      this.$refs.returnForm.validate((valid) => {
        if (valid) {
          customerReturnApi(data).then((res) => {
            this.cancel()
            this.getTableData()
          })
        }
      })
    },
    // 标为流失
    markedLoss(row, val) {
      if (this.checkedId.length == 0 && val !== 1) {
        return this.$message.error('至少选择一项')
      }

      let checkedId = Array.from(this.checkedId)
      let id = checkedId.length && val !== 1 ? checkedId : [row.id]
      if (row && row.customer_status.value == 2) {
        modalSure('您确定要将此客户取消流失吗').then(() => {
          customerCancelLostApi(row.id)
          setTimeout(() => {
            this.getTableData()
          }, 300)
        })
      } else {
        modalSure('您确定要将此客户标为流失吗').then(() => {
          customerLostApi({ data: id })
          setTimeout(() => {
            this.getTableData()
          }, 300)
        })
      }
    },
    //领取客户
    receive(type, row) {
      if (this.ids.length <= 0 && type === 1) {
        this.$message.error('至少选择一项')
      } else {
        let checkedId = Array.from(this.checkedId)
        let id = checkedId.length ? checkedId : [row.id]
        modalSure('您确定要领取此客户吗').then(async () => {
          await customerClaimApi({ data: id })
          await this.getTableData()
        })
      }
    },
    // 批量设置标签
    async batchSetLabel(data) {
      await clientDataLabelApi(data)
      this.id = null
      this.ids = []
      this.getTableData()
      this.rowData = {}
      this.tab = 1
    },

    // 批量设置标签
    labelGroup(val) {
      if (this.ids.length <= 0) {
        return this.$message.error('至少选择一项')
      }

      this.labelData = {
        title: '客户标签',
        width: '540px',
        label: [],
        edit: 1
      }
      this.$refs.labelDialog.handleOpen()
    },
    handleSelectionChange(ids, checkedId) {
      // 回填选中状态时不更新跨页 Map，避免死循环
      if (this.isRestoringSelection) return

      // 添加当前页被选中的项
      ids.forEach((row) => {
        this.crossPageSelectionMap.set(row.id, row)
      })

      // 从 Map 中派生最终的 ids 和 checkedId
      this.ids = Array.from(this.crossPageSelectionMap.values())
      this.checkedId = Array.from(this.crossPageSelectionMap.keys())
    },

    // 翻页后回填选中状态
    restoreSelection() {
      const table = this.$refs.tableData?.$refs?.table
      if (!table) return
      this.isRestoringSelection = true
      this.tableData.forEach((row) => {
        if (this.crossPageSelectionMap.has(row.id)) {
          table.toggleRowSelection(row, true)
        }
      })
      this.isRestoringSelection = false
    },

    // 清空跨页选中状态
    clearCrossPageSelection() {
      this.crossPageSelectionMap.clear()
      this.ids = []
      this.checkedId = []
      const table = this.$refs.tableData?.$refs?.table
      if (table) {
        this.isRestoringSelection = true
        table.clearSelection()
        this.isRestoringSelection = false
      }
    },
    // 添加订单
    addContract(row) {
      this.contractFromData = {
        title: '添加订单',
        id: row.id,
        name: row.customer_name,
        eid: row.id,
        edit: false,
        width: '1129px'
      }
      this.$nextTick(() => {
        this.$refs.addContract.openBox()
      })
    },

    addDataFn() {
      this.addFinance()
    },
    dropdownFn(item, val) {
      switch (item.value) {
        case 1:
          this.labelGroup(val)
          break
        case 2:
        case 5:
          this.handleTransfer(1)
          break
        case 3:
          this.handleReturn(1)
          break
        case 4:
          this.exportList()
          break
        case 6:
          this.receive(1)
          break
        case 7:
          this.markedLoss()
          break
        case 8:
          this.$refs.dragUpload.openBox(this.types)
          break
        case 9:
          // 合并客户
          this.mergeCustomerFn()
          break
        case 10:
          // 表头设置
          this.$refs.tableData.customSearchEvt(2)
          break
        case 11:
          // 筛选条件
          this.$refs.tableData.customSearchEvt(1)
          break
        case 12:
          // 导入导出记录
          this.$refs.importRecords.openBox(this.keyword)
          break
        case 13:
          // 字典选项设置
          this.$router.push({ path: `${roterPre}/customer/list/dictSetting` })
          break
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.m14 {
  padding: 14px;
}

.avatar {
  margin-left: 4px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 4px;
  vertical-align: center;
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

:deep(.el-card__body) {
  padding: 0;
}

:deep(.el-button--primary.is-plain:hover) {
  color: #1890ff;
  background: #e8f4ff;
  border-color: #a3d3ff;
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

.tips {
  color: red !important;
}

:deep(.divBox .el-tag) {
  max-width: 91px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.point {
  cursor: pointer;
  color: #1890ff;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.record {
  .el-dialog__body {
    padding: 20px 20px 30px 20px;
  }
}
</style>
