<!-- 财务-账目记录-收支记账页面 -->
<template>
  <div class="divBox">
    <el-card class="normal-page">
      <formBox
        ref="formBox"
        :total="total"
        @addFinance="addFinance"
        @confirmData="confirmData"
        @exportTemplate="exportTemplate"
        @importExcelDFn="importExcelDFn"
        @getExportData="getExportData"
      />
      <div v-loading="loading" class="table-box mt10">
        <el-table
          ref="table"
          :data="tableData"
          :height="TABLE_HEIGHT"
          :span-method="arraySpanMethod"
          :tree-props="{ children: 'children' }"
          row-key="id"
          style="width: 100%"
          @sort-change="sortChange"
        >
          <el-table-column label="收支时间" min-width="160" prop="edit_time" sortable />
          <el-table-column label="记账类型" min-width="100" prop="types">
            <template #default="scope">
              <el-tag v-if="scope.row.types == 0" plain type="warning">支出</el-tag>
              <el-tag v-else plain type="success">收入</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="收支金额(元)" min-width="120" prop="num" show-overflow-tooltip>
            <template #default="scope">
              <div v-if="scope.row.types !== 10">
                {{ scope.row.num }}
              </div>
            </template>
          </el-table-column>
          <el-table-column label="支付方式" min-width="120" prop="pay_type" />
          <el-table-column label="账目分类" min-width="120" prop="cate.name" />
          <el-table-column label="操作时间" min-width="180" prop="updated_at" sortable />
          <el-table-column label="备注" min-width="180" prop="mark" show-overflow-tooltip>
            <template #default="scope">{{
              scope.row.client_bill ? scope.row.client_bill.mark||'--' : scope.row.mark||'--'
            }}</template>
          </el-table-column>
          <el-table-column label="操作" prop="types" width="200">
            <template #default="scope">
              <div v-if="scope.row.types !== 10">
                <el-button v-hasPermi="['fd:enterprise:list:check']" type="primary" link @click="handleCheck(scope.row)"
                  >查看</el-button
                >
                <el-button v-hasPermi="['fd:enterprise:list:edit']" type="primary" link @click="handleEdit(scope.row)">编辑</el-button>
                <el-button
                  v-hasPermi="['fd:enterprise:list:delete']"
                  type="primary" link
                  @click="handleDelete(scope.row, scope.$index)"
                  >删除</el-button
                >
              </div>
            </template>
          </el-table-column>
        </el-table>
        <div class="footer flex">
          <div v-if="where.types === ''" class="expend ml14">
            <span
              >流入: <span class="income">{{ totalIncome }} </span>
            </span>

            <span
              >流出: <span class="expend-color">{{ totalExpend }} </span>
            </span>

            <span>
              净额: <span :class="getNum() > 0 ? 'positive' : 'positiveF'">{{ getNum() }}</span></span
            >
          </div>
          <div v-else class="expend ml14">
            <span v-if="where.types == 1"
              >总收入: <span class="income">{{ totalIncome }}</span></span
            >
            <span v-if="where.types == 0"
              >总支出: <span class="expend-color">{{ totalExpend }}</span></span
            >
          </div>
          <div class="page-fixed">
            <el-pagination
              :current-page="where.page"
              :page-size="where.limit"
              :page-sizes="[15, 20, 30]"
              :total="total"
              layout="total, sizes,prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="pageChange"
            />
          </div>
        </div>
      </div>
    </el-card>

    <!-- 通用弹窗表单 -->
    <dialogForm ref="dialogForm" :form-data="formBoxConfig" :roles-config="rolesConfig" @isOk="getTableData()" />
    <!-- 导入组件 -->
    <import-excel ref="importExcel" v-show="false" @importExcelData="importExcelData"></import-excel>
    <export-excel ref="exportExcel" :export-data="exportData" :save-name="saveName" :template="false" />
    <viewDetails ref="viewDetails" :form-data="detailsData"></viewDetails>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import {
  billListApi,
  billListEditApi,
  billListDeleteApi,
  billListCreateApi,
  enterprisePayTypeApi,
  billImportApi
} from '@/api/enterprise.ts'
export default {
  name: 'FinanceList',
  components: {
    formBox: defineAsyncComponent(() => import('./components/formBox')),
    dialogForm: defineAsyncComponent(() => import('./components/index')),
    viewDetails: defineAsyncComponent(() => import('./components/viewDetails')),
    exportExcel: defineAsyncComponent(() => import('@/components/common/exportExcel')),
    importExcel: defineAsyncComponent(() => import('@/components/common/importExcel'))
  },
  data() {
    return {
      TABLE_HEIGHT,
      drawer: false,
      input: '',
      detailsData: {},
      fromData: {},
      formBoxConfig: {},

      timeVal: [
        dayjs().startOf('months').format('YYYY/MM/DD'),
        dayjs().endOf('months').format('YYYY/MM/DD')
      ],
      exportData: {
        data: [],
        cols: [{ wpx: 70 }, { wpx: 70 }, { wpx: 120 }, { wpx: 140 }, { wpx: 120 }]
      },
      saveName: '导出收支记账模板.xlsx',
      where: {
        page: 1,
        limit: 15,
        sort: 'id',
        types: ''
      },
      loading: false,
      itemOptions: [],
      rolesConfig: [],
      tableData: [],
      detailData: null,
      treeData: null,
      companyData: null,

      gettime: '',
      totalExpend: 0,
      totalIncome: 0,
      total: 0
    }
  },

  created() {
    this.where.time = `${this.timeVal[0]}-${this.timeVal[1]}`
    this.getTableData()
    this.getPayType()
  },
  methods: {
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },

    getNum() {
      let num = 0
      num = this.totalIncome - this.totalExpend
      return num.toFixed(2)
    },
    sortChange(column) {
      if (column.order === 'ascending') {
        this.where.sort = column.prop + ' asc'
      } else if (column.order === 'descending') {
        this.where.sort = column.prop + ' desc'
      } else {
        this.where.sort = ''
      }

      this.getTableData()
    },

    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    // 获取表格数据
    getTableData() {
      this.loading = true
      billListApi(this.where).then((res) => {
        this.tableData = res.data.list
        this.total = res.data.count
        this.loading = false
        this.totalExpend = res.data.expend == 0 ? '0.00' : res.data.expend
        this.totalIncome = res.data.income == 0 ? '0.00' : res.data.income
      })
    },
    importExcelDFn() {
      this.$refs.importExcel.btnClick()
    },
    normalizeImportType(value) {
      return String(value || '').replace(/^示例[:：]/, '').trim()
    },
    parseImportDate(value) {
      if (value instanceof Date) {
        return dayjs(value).isValid() ? dayjs(value).format('YYYY-MM-DD HH:mm:ss') : ''
      }
      const raw = String(value || '').trim()
      if (!raw) {
        return ''
      }
      const excelSerial = Number(raw)
      if (!Number.isNaN(excelSerial) && excelSerial > 20000 && excelSerial < 60000) {
        const date = dayjs('1899-12-30').add(excelSerial, 'days')
        return date.isValid() ? date.format('YYYY-MM-DD HH:mm:ss') : ''
      }
      const formats = ['YYYY-MM-DD HH:mm:ss', 'YYYY/M/D HH:mm:ss', 'YYYY-MM-DD', 'YYYY/M/D']
      const date = dayjs(raw, formats, true)
      if (date.isValid()) {
        return date.format('YYYY-MM-DD HH:mm:ss')
      }
      const isoDate = dayjs(raw)
      return isoDate.isValid() ? isoDate.format('YYYY-MM-DD HH:mm:ss') : ''
    },
    buildImportRow(row, rowIndex) {
      const rawType = String(row[0] || '').trim()
      if (/^示例[:：]/.test(rawType)) {
        return null
      }
      const types = this.normalizeImportType(rawType)
      if (!types || types.includes('账目类型')) {
        return null
      }
      if (!['收入', '支出'].includes(types)) {
        throw new Error(`第${rowIndex + 1}行账目类型只能填写收入或支出`)
      }
      const dateAtColumn4 = this.parseImportDate(row[3])
      const dateAtColumn5 = this.parseImportDate(row[4])
      const useTemplateOrder = !dateAtColumn4 && !!dateAtColumn5
      const editTime = useTemplateOrder ? dateAtColumn5 : dateAtColumn4
      if (!editTime) {
        throw new Error(`第${rowIndex + 1}行收支时间格式不正确`)
      }
      return {
        types,
        cate_id: String(row[1] || '').trim(),
        mark: row[5],
        edit_time: editTime,
        pay_type: String((useTemplateOrder ? row[3] : row[4]) || '').trim(),
        num: row[2]
      }
    },
    // 导入
    importExcelData(data) {
      const res = []
      if (data.length <= 0) {
        this.$message.error('批量导入内容为空')
      } else {
        try {
          for (let i = 0; i <= data.length - 1; i++) {
            const row = this.buildImportRow(data[i], i)
            if (row) {
              res.push(row)
            }
          }
        } catch (error) {
          this.$message.error(error.message)
          return
        }
        if (!res.length) {
          this.$message.error('未读取到有效导入数据')
          return
        }
        const data_s = {
          data: res
        }
        billImportApi(data_s).then((res) => {
          if (res.status == 200) {
            this.$refs.formBox.tableFrom.search = 0
            this.where.page = 1
            const today = dayjs().format('YYYY/MM/DD')
            const hasCurrentFilter = this.where.time && this.where.time.includes(today)
            if (!hasCurrentFilter) {
              this.where.time = ''
              this.$refs.formBox.tableFrom.time = ''
              const timeIndex = this.$refs.formBox.search.findIndex((item) => item.field_name_en === 'time')
              if (timeIndex !== -1) {
                this.$refs.formBox.search[timeIndex].data_dict = []
              }
            }
            this.getTableData()
          }
        })
      }
    },

    async getPayType() {
      const data = {
        page: 1,
        limit: 0
      }
      const result = await enterprisePayTypeApi(data)
      this.exportFileUrl = result.data.import_temp
    },
    arraySpanMethod({ row }) {
      //table合计行合并单元格
      if (row.types === 10) {
        return { rowspan: 7, colspan: this.tableData.length - 1 }
      }
    },

    // 导出模板
    exportTemplate() {
      this.saveName = '导出账目模板.xlsx'
      let aoaData = [
        [
          '账目类型',
          '账目分类',
          '金额(元)',
          '收支时间',
          '支付方式',
          '备注信息'
        ]
      ]

      this.exportData.data = aoaData
      this.$refs.exportExcel.exportExcel()
    },
    // 导出
    getExportData() {
      this.saveName = '导出账目_' + dayjs(new Date()).format('MM_DD_HH_mm_ss') + '.xlsx'
      let aoaData = [
        [
          '账目类型',
          '账目分类',
          '金额(元)',
          '收支时间',
          '支付方式',
          '备注信息'
        ]
      ]

      let obj = {}
      for (let key in this.where) {
        obj[key] = this.where[key]
      }

      obj.page = 0
      obj.limit = 0
      billListApi(obj).then((res) => {
        let data = res.data.list
        if (data.length > 0) {
          data.forEach((value) => {
            aoaData.push([
              value.types == 0 ? '支出' : '收入',
              value.cate ? value.cate.name : '',
              value.num,
              value.edit_time,
              value.pay_type,
              value.mark
            ])
          })

          this.exportData.data = aoaData
          this.$refs.exportExcel.exportExcel()
        }
      })
    },
    // 查看详情
    async handleCheck(item) {
      this.detailsData = {
        data: item
      }

      setTimeout(() => {
        this.$refs.viewDetails.handelOpen()
      }, 300)
    },
    // 添加科目
    async addFinance() {
      billListCreateApi()
        .then((res) => {
          this.formBoxConfig = {
            title: res.data.title,
            width: '500px',
            method: res.data.method,
            action: res.data.action.substr(4)
          }

          this.rolesConfig = res.data.rule
          this.$refs.dialogForm.openBox()
        })
        .catch((error) => {})
    },
    // 编辑
    async handleEdit(item) {
      billListEditApi(item.id).then((res) => {
        this.formBoxConfig = {
          title: res.data.title,
          width: '500px',
          method: res.data.method,
          action: res.data.action.substr(4)
        }
        this.rolesConfig = res.data.rule
        this.$refs.dialogForm.openBox()
      })
    },
    getItemArray(arr, type) {
      const arrs = []
      arr.map((value) => {
        if (value.types === type) {
          arrs.push(value)
        }
      })
      return arrs
    },
    // 删除
    async handleDelete(item, index) {
      await modalSure('你确定要删除资金记录吗')
      await billListDeleteApi(item.id)
      this.getTableData()
    },
    confirmData(data) {
      this.where.page = 1
      this.where = { ...this.where, ...data }
      if (data.search == 1) {
        this.$refs.formBox.tableFrom.search = 0
        this.getTableData()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.inTotal {
  margin-top: 0px;
  margin-bottom: 0px;
}

:deep(.el-drawer__body) {
  height: 100%;
  overflow-y: auto;
}
.hand {
  cursor: pointer;
}
.positiveF {
  color: #ed4014;
}
.footer {
  line-height: 42px;
  .expend {
    font-size: 14px;
    padding: 10px 0;
    color: #909399 !important;
    div {
      > span {
        padding-right: 15px;
      }
      > span:last-of-type {
        padding-right: 0;
      }
    }
    .expend-color {
      color: #ffba00;
      margin-right: 6px;
    }
    .income {
      color: #13ce66;
      margin-right: 6px;
    }
    .positive {
      color: #1890ff;
      margin-right: 6px;
    }
  }
  .page-fixed {
    bottom: auto;
  }
}
</style>
