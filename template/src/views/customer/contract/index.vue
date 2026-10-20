<template>
  <div class="divBox">
    <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
      <oaFromBox
        :title="$route.meta.title"
        btnText="添加订单"
        :treeData="treeDataGroup"
        :search="search"
        :viewSearch="viewSearch"
        :dropdownList="dropdownList"
        :total="total"
        :category="keyword"
        :timeSearchObj="timeSearchObj"
        class="from-box"
        ref="fromBox"
        :whereData="where"
        @treeChange="handleNodeClick"
        @addDataFn="addContract"
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
        @handleCheck="handleCheck"
        @handleCustomerCheck="handleClient"
        @getSearch="getSearch"
        @handleSelectionChange="handleSelectionChange"
        @getTableData="getTableData"
      >
        <template #options="{ data }">
          <el-button type="primary" link @click="handleCheck(data)">
            查看
          </el-button>
          <el-button type="primary" link @click="handleBuild(data, buildData.contract_refund_switch, 'contract_refund_switch')">
            添加回款
          </el-button>
          <el-dropdown>
            <el-button type="primary" link class="el-dropdown-link more">
              更多
              <i class="el-icon-arrow-down" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu class="dropdown-menu-left" placement="top-start">
                <el-dropdown-item
                  @click="handleBuild(data, buildData.contract_renew_switch, 'contract_renew_switch')"
                >
                  添加续费
                </el-dropdown-item>
                <el-dropdown-item
                  @click="handleBuild(data, buildData.contract_disburse_switch, 'contract_disburse_switch')"
                >
                  添加支出
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="isModuleEnabled(CUSTOMER_MODULE_KEYS.INVOICE)"
                  style="border-bottom: 1px solid #f5f5f5"
                  @click="handleBuild(data, buildData.invoicing_switch, 'invoicing_switch')"
                >
                  申请发票
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="hasWarehousePermission({ id: data.outbound_warehouse_id, permission: WAREHOUSE_PERMISSION.INBOUND })"
                  @click="handleAddInventoryBill('inbound', data)"
                >
                  添加入库单
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="hasWarehousePermission({ id: data.outbound_warehouse_id, permission: WAREHOUSE_PERMISSION.OUTBOUND })"
                  @click="handleAddInventoryBill('outbound', data)"
                >
                  添加出库单
                </el-dropdown-item>
                <el-dropdown-item @click="handleTransfer(2, data)"> 移交同事 </el-dropdown-item>
                <el-dropdown-item @click="markedAbnormal(data)">
                  {{ data.contract_status.value == 3 ? '标为正常订单' : '标为异常订单' }}
                </el-dropdown-item>
                <el-dropdown-item @click="handleDelete(data)"> 删除 </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </customizeTable>
    </el-card>
    <add-contract ref="addContract" :form-data="contractFromData" @getTableData="getTableData()"></add-contract>
    <edit-contract ref="editContract" :form-data="fromData" @isOk="getTableData(true)"></edit-contract>
    <edit-customer ref="editCustomer" :form-data="fromData" @isOkEdit="getTableData(true)"></edit-customer>
    <transfer-dialog ref="transferDialog" :from-data="transferData" @handleTransfer="getTableData"></transfer-dialog>

    <edit-examine
      ref="editExamine"
      :ids="formInfo.id"
      :parameterData="parameterData"
      @isOk="getTableData()"
    ></edit-examine>

    <!-- 导入合同 -->
    <dragUpload ref="dragUpload" @getTableData="getTableData()"></dragUpload>
    <!-- 导入/导出记录 -->
    <importRecords ref="importRecords"></importRecords>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useAppConfigStore } from '@/stores/appConfig.ts'
import { useWarehouseStore } from '@/stores/warehouse.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { clientContractDeleteApi, contractViewApi, contractAbnormalApi, contractImport } from '@/api/enterprise.ts'
import { clientExportApi } from '@/api/client.ts'
import { configRuleApproveApi } from '@/api/config.ts'
import { DRAWER_SIZE } from '@/constants/popupSize.ts'
import { roterPre } from '@/settings.ts'
import { CUSTOMER_MODULE_KEYS } from '@/constants/customerModules.ts'
import { WAREHOUSE_PERMISSION } from '@/views/inventory/constants.ts'
import { hasWarehousePermission } from '@/directive/warehousePermi.ts'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'ContractList',
  components: {
    oaFromBox,
    editContract: defineAsyncComponent(() => import('./components/editContract')),
    addContract: defineAsyncComponent(() => import('./components/addContract')),
    customizeTable: defineAsyncComponent(() => import('../components/customizeTable')),
    dragUpload: defineAsyncComponent(() => import('../components/dragUpload')),
    importRecords: defineAsyncComponent(() => import('@/views/customer/list/components/importRecords')),
    transferDialog: defineAsyncComponent(() => import('@/views/customer/list/components/transferDialog')),
    editCustomer: defineAsyncComponent(() => import('@/views/customer/list/components/editCustomer')),
    editExamine: defineAsyncComponent(() => import('@/views/user/examine/components/editExamine'))
  },
  props: {
    types: {
      type: Number,
      default: 1
    }
  },
  computed: {
    ...mapStores(useAppConfigStore, useWarehouseStore)
  },
  data() {
    return {
      CUSTOMER_MODULE_KEYS,
      WAREHOUSE_PERMISSION,
      transferDataList: [],

      tableData: [], // 表格的数据
      total_price: 0,

      fromData: {},
      labelText: '',
      contractFromData: {},
      where: {
        page: 1,
        limit: 15,
        view_search: 2,
        types: 'contract'
      },
      formInfo: {
        id: ''
      },
      parameterData: {
        contract_id: '',
        customer_id: '',
        invoice_id: '',
        bill_id: ''
      },
      total: 0,
      transferData: {},
      ids: [],
      loading: false,

      searchForm: {},

      keyword: 'contract',
      buildData: [],

      search: [],
      timeSearchObj: {},
      viewSearch: [],
      dropdownList: [
        { label: '移交同事', value: 1 },
        { label: '筛选条件设置', value: 4 },
        { label: '表头显示设置', value: 5 },
        { label: '导出', value: 2 },
        { label: '导入', value: 3 },
        { label: '导入导出记录', value: 6 },
        { label: '字段选项设置', value: 7 }
      ],
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
          id: 3,
          label: '我关注的'
        },
        {
          id: 4,
          label: '已签约'
        },
        {
          id: 5,
          label: '未签约'
        },
        {
          id: 6,
          label: '签约作废'
        },
        {
          id: 7,
          label: '过期订单'
        },
        {
          id: 8,
          label: '急需续费'
        },
        {
          id: 9,
          label: '费用过期',
          line: true
        }
      ]
    }
  },

  created() {
    this.warehouseStore.loadAccessible().catch(() => {})
    this.getConfigApprove()
  },

  methods: {
    // 客户模块开关用 v-if 判断：原自定义指令直接删 DOM，挂在 el-dropdown-item（非元素根节点）上不生效，
    // 与 v-if 同用时还会让 Vue 更新到已被移除的节点而报错
    isModuleEnabled(key) {
      return this.appConfigStore.isCustomerModuleEnabled(key)
    },
    // 依赖 warehouse 模块的可访问仓库列表（created 中加载），加载完成前返回 false，菜单项保持隐藏
    hasWarehousePermission,
    getSearch(val) {
      this.search = val.search
      this.viewSearch = val.viewSearch
      this.timeSearchObj = val.timeSearchObj
      this.transferDataList = val.tableHeaders
    },
    // 导出列表数据
    async exportList() {
      await clientExportApi(this.keyword, { ...this.where, page: 0, limit: 0, types: this.keyword })
    },

    // 标为异常
    markedAbnormal(row) {
      let text, status
      text = row.contract_status.value == 3 ? '正常' : '异常'
      status = row.contract_status.value == 3 ? 0 : 1
      modalSure(`您确定要将此订单标为${text}吗`).then(() => {
        contractAbnormalApi(row.id, status)
        this.getTableData()
      })
    },
    handleBuild(item, val, type) {
      this.parameterData.customer_id = item.eid
      this.parameterData.contract_id = item.id
      this.$refs.editExamine.openBox(val, item.id, type)
    },

    // 添加入库单 / 出库单：跳转新建单据页，携带订单仓库与订单信息用于回显仓库、订单及关联商品
    handleAddInventoryBill(type, row) {
      this.$router.push({
        path: `${roterPre}/inventory/${type}/create`,
        query: {
          warehouse_id: row.outbound_warehouse_id,
          order_id: row.id,
          contract_no: row.contract_no
        }
      })
    },

    isOk() {
      this.getTableData()
    },
    async getConfigApprove() {
      const result = await configRuleApproveApi(0)
      this.buildData = result.data
    },

    // 添加订单
    addContract(row) {
      this.contractFromData = {
        title: row ? '编辑订单' : '添加订单',
        edit: false,
        eid: row ? row.eid : '',
        width: '1129px'
      }
      this.$refs.addContract.openBox(row)
    },

    handleNodeClick(data) {
      this.where.page = 1
      this.where.statistics_type = data.value
      this.getTableData()
    },

    getTableData() {
      if (this.loading) return
      this.loading = true
      contractViewApi(this.where)
        .then((res) => {
          this.tableData = res.data.list
          this.total = res.data.count
          this.total_price = res.data.total_price || 0
          this.loading = false
          // setTimeout(() => {
          //   this.loading = false
          //   this.$refs.multipleTable?.doLayout()
          // }, 300)
        })
        .catch((error) => {
          this.loading = false
        })
    },

    // 转移
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
            this.$nextTick(() => {
              for (let i = 0; i < this.$refs.multipleTable.length; i++) {
                this.$refs.multipleTable[i].clearSelection()
              }
            })
          }
          ids.push(row.id)
        }
        this.transferData = {
          title: type === 1 ? '移交其他同事' : '转移设置',
          width: '520px',
          type: 2,
          ids
        }
        this.$refs.transferDialog.handleOpen(this.keyword)
      }
    },

    // 删除
    handleDelete(item) {
      modalSure('确定要删除该订单吗').then(() => {
        clientContractDeleteApi(item.id).then((res) => {
          if (this.where.page > 1 && this.tableData.length <= 1) {
            this.where.page--
          }
          this.getTableData()
        })
      })
    },

    // 打开客户
    handleClient(item) {
      item.eid = item.eid
      item.cid = item.id
      this.fromData = {
        title: '编辑客户',
        width: '1100px',
        link_type: 'customer',
        data: item
      }
      this.$refs.editCustomer.tabIndex = '1'
      this.$refs.editCustomer.tabNumber = 1
      this.$refs.editCustomer.openBox(item.eid, 'customer')
    },

    // 查看
    async handleCheck(item) {
      item.cid = item.id
      this.fromData = {
        title: '查看订单',
        width: DRAWER_SIZE.LG,
        data: item,
        isClient: false,
        name: item.client ? item.client.name : '',
        id: item.client ? item.client.id : '',
        edit: true,
        link_type: 'contract'
      }

      this.$refs.editContract.tabIndex = '1'
      this.$refs.editContract.tabNumber = 1
      this.$refs.editContract.openBox(item)
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
        this.getTableData()
      } else {
        this.where = {
          page: 1,
          limit: 15,
          view_search: this.where.view_search
        }

        for (let key in data) {
          this.where[key] = data[key] || ''
        }

        setTimeout(() => {
          this.getTableData()
        }, 100)
      }
    },

    handleSelectionChange(val) {
      this.ids = val
    },
    dropdownFn(item) {
      switch (item.value) {
        case 1:
          this.handleTransfer(1)
          break
        case 2:
          this.exportList(item)
          break
        case 3:
          this.$refs.dragUpload.openBox(this.keyword)
          break
        case 4:
          // 筛选条件设置
          this.$refs.tableData.customSearchEvt(1)
          break
        case 5:
          // 表头显示设置
          this.$refs.tableData.customSearchEvt(2)
          break
        case 6:
          // 导入导出记录
          this.$refs.importRecords.openBox(this.keyword)
          break
        case 7:
          // 字典选项设置
          this.$router.push({ path: `${roterPre}/customer/contract/dictSetting` })
          break
      }
    },
    importExcelData(arrRes) {
      // 提取表头
      const [thead, ...rows] = arrRes
      let data = []

      // 过滤掉全为空字符串的行，并转换为对象格式
      rows.forEach((row) => {
        const isAllEmpty = row.every((cell) => cell.trim() === '')
        if (!isAllEmpty) {
          const rowData = {}
          row.forEach((cell, index) => {
            this.transferDataList.map((item) => {
              if (item.name == thead[index]) {
                rowData[item.field] = cell
              }
            })
          })
          data.push(rowData)
        }
      })
      contractImport('', data).then((res) => {
        this.getTableData(this.labelText, this.searchForm)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.p14 {
  padding: 0 14px;
}

:deep(.el-card__body) {
  padding: 0;
}

.point {
  cursor: pointer;
  color: #1890ff;
}

.dropdown-menu-left {
  position: relative;
}
.dropdown-menu-right {
  width: 100px;
  position: absolute;
  top: -50px;
  right: 0;
}
.more {
  margin-left: 10px;
}
</style>
