<!-- 发票管理页面 -->
<template>
  <div class="divBox">
    <el-card class="employees-card-bottom">
      <oaFromBox
        :treeData="treeDataGroup"
        :search="search"
        :isCategory="false"
        category="invoice"
        :isViewSearch="false"
        :sortSearch="false"
        :total="total"
        type="0"
        :title="$route.meta.title"
        btnText="一键转移"
        btnType="default"
        class="from-box"
        @addDataFn="confirmTransfer(1)"
        @confirmData="confirmData"
      ></oaFromBox>

      <div v-loading="loading" class="mt10">
        <div class="defaultPage" v-if="tableData.length == 0">
          <img class="img" src="../../../assets/images/defd.png" alt="" />

          <span class="text"> 暂无发票数据</span>
        </div>
        <div class="" v-else>
          <div style="width: 100%; overflow: auto" :style="{ height: TABLE_HEIGHT }">
            <!-- 发票页面 -->
            <div class="table-box" v-for="item in tableData" :key="item.id">
              <!-- 企业专用票 -->
              <div>
                <div class="header">
                  <el-checkbox-group v-model="checkList">
                    <el-checkbox :label="item.id" :value="item.id" class="header-left">
                      {{ titleTypeFn(item.types) }}·{{ item.collect_type === 'mail' ? '电子' : '纸质' }}
                      <el-tag v-if="item.status === 0">待审核</el-tag>
                      <el-tag v-else-if="item.status === 1" type="warning">待开票</el-tag>
                      <el-tag v-else-if="item.status === 2" type="danger">已拒绝</el-tag>
                      <el-tag v-else-if="item.status === 3" type="info">撤回开票</el-tag>
                      <el-tag v-else-if="item.status === 4" type="danger">申请冲红</el-tag>
                      <el-tag v-else-if="item.status === 5" type="success">已开票</el-tag>
                      <el-tag v-else type="danger">已冲红</el-tag>
                    </el-checkbox>
                  </el-checkbox-group>
                  <div class="left">
                    <el-button type="primary" link @click="handleCheck(item)">
                      查看
                    </el-button>
                    <el-button @click="invoiceWithdrawal(item)" type="primary" link v-if="item.status === 0">
                      撤回开票
                    </el-button>
                    <el-button
                      v-if="item.status === 5"
                      @click="handleBuild(item, buildData.void_invoice_switch, 'void_invoice_switch')"
                      type="primary" link
                    >
                      申请冲红
                    </el-button>
                    <el-dropdown
                      v-if="buildData.length > 1"
                      trigger="click"
                      placement="bottom-start"
                      @command="handleBuild($event, 6, item)"
                    >
                      <el-button type="primary" link> 申请冲红 </el-button>
                      <template #dropdown>
                        <el-dropdown-menu class="build-dropdown">
                          <el-dropdown-item
                            v-for="(item, index) in buildData"
                            :key="item.id"
                            class="over-text"
                            placement="top-end"
                            :command="index"
                          >
                            <i class="iconfont" :class="item.icon" :style="{ color: item.color }"></i>
                            {{ item.name }}
                          </el-dropdown-item>
                        </el-dropdown-menu>
                      </template>
                    </el-dropdown>
                    <el-button v-if="item.status === 4" @click="invoiceWithdrawal(item, 1)" type="primary" link>
                      冲红撤回
                    </el-button>
                  </div>
                </div>
                <!-- 企业专用发票 -->
                <div class="content main" v-if="item.types == '3'">
                  <div class="left">
                    <el-form label-width="110px">
                      <el-form-item label="发票抬头：" prop="name">
                        <span class="pointer copy" title="点击复制" :data-clipboard-text="item.title" @click="copy">
                          {{ item.title || '--' }}
                        </span>
                      </el-form-item>
                      <el-form-item label="纳税人识别号：" prop="name">
                        <span class="pointer copy" title="点击复制" :data-clipboard-text="item.ident" @click="copy">
                          {{ item.ident || '--' }}
                        </span>
                      </el-form-item>

                      <el-form-item label="开票金额：" prop="name">
                        <span
                          class="pointer copy"
                          title="点击复制"
                          :data-clipboard-text="item.amount"
                          @click="copy"
                          :class="item.price == item.amount ? '' : 'active'"
                          >{{ item.amount || '--' }}</span
                        >
                      </el-form-item>
                    </el-form>
                  </div>
                  <div class="line"></div>
                  <div class="right">
                    <el-form label-width="110px">
                      <el-form-item label="客户名称：" prop="name">
                        <span>{{ item.customer ? item.customer.customer_name : '--' }}</span>
                      </el-form-item>
                      <el-form-item v-if="item.collect_type == 'mail'" label="邮箱信息：" prop="name">
                        <span
                          class="pointer copy"
                          title="点击复制"
                          :data-clipboard-text="item.collect_email"
                          @click="copy"
                          >{{ item.collect_email || '--' }}</span
                        >
                      </el-form-item>
                      <el-form-item v-else label="邮寄信息：" prop="name">
                        <span
                          class="pointer copy text-height"
                          title="点击复制"
                          :data-clipboard-text="item.collect_name + item.collect_tel + item.mail_address"
                          @click="copy"
                          >{{ item.collect_name }} {{ item.collect_tel }} {{ item.mail_address || '--' }}</span
                        >
                      </el-form-item>
                      <el-form-item label="申请信息：" prop="name">
                        <span> {{ item.card ? item.card.name : '' }} {{ item.created_at || '--' }}</span>
                      </el-form-item>
                      <el-form-item label="付款金额：" prop="name">
                        <span :class="item.price == item.amount ? '' : 'active'">{{ item.price || '--' }}</span>
                      </el-form-item>
                    </el-form>
                  </div>
                </div>

                <!-- 企业普通发票 -->
                <div class="content main" v-if="item.types == '2'">
                  <div class="left">
                    <el-form label-width="110px">
                      <el-form-item label="发票抬头：" prop="name">
                        <span class="pointer copy" title="点击复制" :data-clipboard-text="item.title" @click="copy">
                          {{ item.title || '--' }}
                        </span>
                      </el-form-item>
                      <el-form-item label="纳税人识别号：" prop="name">
                        <span class="pointer copy" title="点击复制" :data-clipboard-text="item.ident" @click="copy">
                          {{ item.ident || '--' }}
                        </span>
                      </el-form-item>

                      <el-form-item label="开票金额：" prop="name">
                        <span
                          class="pointer copy"
                          title="点击复制"
                          :data-clipboard-text="item.amount"
                          @click="copy"
                          :class="item.price == item.amount ? '' : 'active'"
                        >
                          {{ item.amount || '--' }}
                        </span>
                      </el-form-item>
                    </el-form>
                  </div>
                  <div class="line"></div>
                  <div class="right">
                    <el-form label-width="110px">
                      <el-form-item label="客户名称：" prop="name">
                        <span>{{ item.customer ? item.customer.customer_name : '--' }}</span>
                      </el-form-item>
                      <el-form-item v-if="item.collect_type == 'mail'" label="邮箱信息：" prop="name">
                        <span
                          class="pointer copy"
                          title="点击复制"
                          :data-clipboard-text="item.collect_email"
                          @click="copy"
                          >{{ item.collect_email || '--' }}</span
                        >
                      </el-form-item>
                      <el-form-item v-else label="邮寄信息：" prop="name">
                        <span
                          class="pointer copy text-height"
                          title="点击复制"
                          :data-clipboard-text="item.collect_name + item.collect_tel + item.mail_address"
                          @click="copy"
                          >{{ item.collect_name }} {{ item.collect_tel }} {{ item.mail_address || '--' }}</span
                        >
                      </el-form-item>
                      <el-form-item label="申请信息：" prop="name">
                        <span> {{ item.card ? item.card.name : '' }} {{ item.created_at || '--' }}</span>
                      </el-form-item>
                      <el-form-item label="付款金额：" prop="name">
                        <span :class="item.price == item.amount ? '' : 'active'">{{ item.price || '--' }}</span>
                      </el-form-item>
                    </el-form>
                  </div>
                </div>

                <!-- 个人普通发票 -->
                <div class="content" v-if="item.types == '1'">
                  <div class="left">
                    <el-form label-width="110px">
                      <el-form-item label="发票抬头：" prop="name">
                        <span class="pointer copy" title="点击复制" :data-clipboard-text="item.title" @click="copy">
                          {{ item.title || '--' }}
                        </span>
                      </el-form-item>
                      <el-form-item label="开票金额：" prop="name">
                        <span
                          class="pointer copy"
                          title="点击复制"
                          :data-clipboard-text="item.amount"
                          @click="copy"
                          :class="item.price == item.amount ? '' : 'active'"
                          >{{ item.amount || '--' }}</span
                        >
                      </el-form-item>
                    </el-form>
                  </div>
                  <div class="line"></div>
                  <div class="right">
                    <el-form label-width="110px">
                      <el-form-item label="客户名称：" prop="name">
                        <span>{{ item.customer ? item.customer.customer_name : '--' }}</span>
                      </el-form-item>
                      <el-form-item v-if="item.collect_type == 'mail'" label="邮箱信息：" prop="name">
                        <span
                          class="pointer copy"
                          title="点击复制"
                          :data-clipboard-text="item.collect_email"
                          @click="copy"
                          >{{ item.collect_email || '--' }}</span
                        >
                      </el-form-item>
                      <el-form-item v-else label="邮寄信息：" prop="name">
                        <span
                          class="pointer copy text-height"
                          title="点击复制"
                          :data-clipboard-text="item.collect_name + item.collect_tel + item.mail_address"
                          @click="copy"
                          >{{ item.collect_name }} {{ item.collect_tel }} {{ item.mail_address || '--' }}</span
                        >
                      </el-form-item>

                      <el-form-item label="申请信息：" prop="name">
                        <span> {{ item.card ? item.card.name : '' }} {{ item.created_at || '--' }}</span>
                      </el-form-item>
                      <el-form-item label="付款金额：" prop="name">
                        <span :class="item.price == item.amount ? '' : 'active'">{{ item.price || '--' }}</span>
                      </el-form-item>
                    </el-form>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="page-fixed">
            <el-pagination
              :page-size="where.limit"
              :current-page="where.page"
              :page-sizes="[15, 20, 30]"
              layout="total, sizes,prev, pager, next, jumper"
              :total="total"
              @size-change="handleSizeChange"
              @current-change="pageChange"
            />
          </div>
        </div>
      </div>
    </el-card>

    <!-- 申请发票   -->
    <invoice-apply ref="invoiceApply" :form-data="formBoxConfig" @isOk="getTableData()" />
    <mergeInvoice ref="mergeInvoice" :form-data="formBoxConfig" @isOk="getTableData()"></mergeInvoice>
    <invoice-details :form-data="invoiceData" ref="invoiceDetailsView"></invoice-details>
    <invoice-view :form-data="invoiceData" ref="invoiceView"></invoice-view>
    <mark-dialog ref="markDialog" :config="configMark" @isMark="getTableData()"></mark-dialog>
    <transfer-dialog ref="transferDialog" :from-data="transferData" @handleTransfer="getTableData"></transfer-dialog>
    <!-- 发票撤回/申请弹窗 -->
    <el-dialog
      :title="title"
      top="25vh"
      class="addBox"
      :append-to-body="true"
      v-model="dialogVisible"
      width="540px"
    >
      <div class="line" />
      <el-form :model="form" :rules="rules" class="from">
        <el-form-item :label="reason + '：'" label-width="90px" prop="remarks">
          <el-input type="textarea" v-model="form.remarks" placeholder="请填写原因"></el-input>
        </el-form-item>
        <div class="footer">
          <el-button class="btn" @click="cancelFn">取消</el-button>
          <el-button type="primary" @click="submitFn" class="btn">确定</el-button>
        </div>
      </el-form>
    </el-dialog>
    <edit-examine ref="editExamine" :parameterData="parameterData" @isOk="getTableData()"></edit-examine>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useAppStore } from '@/stores/app.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import {
  getSalesman,
  uninvoicedListApi
} from '@/api/enterprise.ts'
import { clientInvoiceDeleteApi, clientInvoiceListApi, putInvoice, invalidApply, recallStatus } from '@/api/client.ts'
import ClipboardJS from 'clipboard'
import { getInvoiceType } from '@/libs/customer.ts'
import { approveApplyRevokeApi } from '@/api/business.ts'
import { configRuleApproveApi } from '@/api/config.ts'
import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'FinanceList',
  components: {
    oaFromBox,
    invoiceApply: defineAsyncComponent(() => import('@/components/invoice/invoiceApply')),
    invoiceDetails: defineAsyncComponent(() => import('@/components/invoice/invoiceDetails')),
    markDialog: defineAsyncComponent(() => import('@/views/customer/contract/components/markDialog')),
    transferDialog: defineAsyncComponent(() => import('@/views/customer/list/components/transferDialog')),
    mergeInvoice: defineAsyncComponent(() => import('@/components/invoice/mergeInvoice')),
    invoiceView: defineAsyncComponent(() => import('@/views/customer/invoice/components/invoiceView')),
    editExamine: defineAsyncComponent(() => import('@/views/user/examine/components/editExamine'))
  },

  computed: {
    ...mapStores(useAppStore)
  },
  data() {
    return {
      TABLE_HEIGHT,
      formBoxConfig: {},
      parameterData: {
        contract_id: '',
        customer_id: '',
        invoice_id: '',
        bill_id: ''
      },
      checkList: [],
      salesmanList: [],
      invoiceData: {},
      tableData: [],
      mergeOpen: false,
      title: '',
      reason: '',
      dialogFormVisible: false,
      dialogVisible: false,
      treeDataGroup: [
        {
          id: 1,
          label: '我负责的'
        },
        {
          id: 2,
          label: '下属负责的',
          default: true
        }
      ],
      where: {
        page: 1,
        limit: 15,
        name: '',
        time_field: '',
        time: '',
        status: '',
        view_search: 1
      },
      withdrawId: '',
      rules: {
        remarks: [{ required: true, message: '请填写撤回理由', trigger: 'blur' }]
      },
      tooltipTrue: true,
      putInvoiceId: '',
      form: {
        remark: ''
      },
      configMark: {},
      total: 0,
      transferData: {},
      ids: [],
      loading: false,
      buildData: [],
      id: 0,
      search: [
        {
          form_value: 'select',
          field_name_en: 'time_field',
          field_name: '时间类型',
          data_dict: [
            {
              value: 'time',
              name: '申请日期'
            },
            {
              value: 'bill_date',
              name: '开票日期'
            }
          ]
        },
        {
          form_value: 'date_picker',
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time'
        }
      ]
    }
  },
  created() {
    // this.where.way = this.types
    this.getSalesman()
    this.getConfigApprove(0)
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    titleTypeFn(val) {
      return getInvoiceType(val)
    },
    async getConfigApprove(type) {
      const result = await configRuleApproveApi(0)
      this.buildData = result.data
    },
    // V1.4获取申请开票表单
    handleBuild(command, val, type) {
      if (type === 'void_invoice_switch') {
        this.parameterData.invoice_id = command.id
        this.$refs.editExamine.openBox(val, command, type)
      } else {
        this.opneApply(1, '', val)
      }
    },
    pageChange(page) {
      this.where.page = page
      document.documentElement.scrollTop = 0
      this.getTableData()
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    async getSalesman() {
      const result = await getSalesman()
      this.salesmanList = result.data
    },

    // 发票撤回
    invoiceWithdrawal(val, type) {
      let id = type === 1 ? val.revoke_id : val.link_id
      modalSure('你确定要撤销申请吗').then(() => {
        this.getApplyRevoke(id)
      })
    },
    async getApplyRevoke(id) {
      await approveApplyRevokeApi(id)
      this.getTableData()
    },
    // 申请冲红
    apply(val) {
      this.title = '发票申请冲红'
      this.reason = '冲红原因'
      this.dialogVisible = true
      this.withdrawId = val.id
    },

    async submitFn() {
      let data = {
        remark: this.form.remarks
      }
      if (this.title == '发票申请冲红') {
        data.invalid = 1
        await invalidApply(this.withdrawId, data)
        this.cancelFn()
        this.getTableData()
      } else {
        await recallStatus(this.withdrawId, data)
        this.cancelFn()
        this.getTableData()
      }
    },
    cancelFn() {
      this.dialogVisible = false
      this.form.remarks = ''
    },

    // 冲红撤回
    async withdraw(val) {
      await modalSure('你确定要撤回申请冲红吗')
      let data = {
        invalid: -1
      }
      await invalidApply(val.id, data)
      this.getTableData()
    },

    // 获取表格数据
    getTableData() {
      this.checkList = []
      this.loading = true
      clientInvoiceListApi(this.where)
        .then((res) => {
          this.tableData = res.data.list
          this.total = res.data.count
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },
    editPaymentRecord(data) {
      return new Promise((resolve, reject) => {
        let val = {
          eid: data.eid,
          invoice_id: data.id,
          withdraw: ''
        }
        uninvoicedListApi(val).then((res) => {
          if (res.data.length == 0) {
            this.mergeOpen = false
          } else {
            this.mergeOpen = true
          }
          resolve(this.mergeOpen)
        })
      })
    },
    // 重新提交
    async handleEdit(row, type) {
      this.editPaymentRecord(row).then(() => {
        this.formBoxConfig = {
          title: '申请发票',
          width: '1000px',
          edit: true,
          rowData: row,
          data: row,
          type
        }

        if (this.mergeOpen) {
          this.$refs.mergeInvoice.openBox()
        } else {
          this.formBoxConfig.data = row
          this.$refs.invoiceApply.openBox(row.id)
        }
      })
    },
    submit() {
      let data = {
        invalid: 1,
        remark: this.form.remark
      }
      this.$refs.remark.validate((valid) => {
        if (valid) {
          putInvoice(this.putInvoiceId, data).then((res) => {
            this.dialogFormVisible = false
            this.form = {}
            this.getTableData()
          })
        }
      })
    },
    // 转移
    handleTransfer(type, row = []) {
      if (this.checkList.length <= 0 && type === 1) {
        this.$message.error('至少选择一项')
      } else {
        this.transferData = {
          title: type === 1 ? '批量转移设置' : '转移设置',
          width: '520px',
          type: 3,
          ids: this.checkList
        }

        this.$refs.transferDialog.handleOpen(this.types)
      }
    },
    setRemarks(row) {
      this.configMark = {
        title: '备注信息',
        width: '480px',
        id: row.id,
        type: 2,
        mark: row.mark
      }
      this.$refs.markDialog.handleOpen()
    },
    // 批量删除
    handleBatchDelete() {
      if (this.ids.length <= 0) {
        this.$message.error('至少选择一项')
      } else {
        modalSure('确定要批量删除发票记录吗').then(() => {
          const ids = []
          this.ids.forEach((value, index) => {
            if (value.status !== 1) {
              ids.push(value.id)
            }
          })
          if (ids.length <= 0) {
            this.$message.error('不能删除审核已通过的内容')
          } else {
            const id = ids.join(',')
            this.clientInvoiceDelete(id)
          }
        })
      }
    },
    // 删除
    handleDelete(item) {
      modalSure('请确定要撤回该记录吗').then(() => {
        this.clientInvoiceDelete(item.id)
      })
    },
    clientInvoiceDelete(id) {
      clientInvoiceDeleteApi(id).then((res) => {
        if (this.where.page > 1 && this.tableData.length <= 1) {
          this.where.page--
        }
        this.getTableData()
      })
    },
    // 编辑
    async handleCheck(item) {
      this.invoiceData = {
        title: '发票查看',
        width: '1000px',
        data: item
      }
      if (item.link_id == 0) {
        this.$refs.invoiceView.openBox(item.link_id)
      } else {
        this.$refs.invoiceDetailsView.openBox(item.link_id)
      }
    },
    confirmData(data) {
      if (data == 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          name: '',
          time_field: '',
          time: '',
          status: '',
          view_search: 1
        }
      } else {
        this.where.name = data.keyword_default
        this.where.time_field = data.time_field
        this.where.time = data.time
        if (data.view_search) {
          this.where.view_search = data.view_search
        }

        this.where.status = data.status
        this.where.scope_frame = data.scope_frame
      }
      this.where.page = 1
      this.getTableData()
    },
    handleSelectionChange(val) {
      this.ids = val
    },
    confirmTransfer(e) {
      if (e === 1) {
        this.handleTransfer(1)
      } else if (e === 2) {
        this.handleBatchDelete()
      }
    },
    copy() {
      this.$nextTick(() => {
        const clipboard = new ClipboardJS('.copy')
        clipboard.on('success', () => {
          this.$message.success('复制成功')
          clipboard.destroy()
        })
        this.appStore.setClickTab(false)
      })
    }
  }
}
</script>

<style scoped>
.inTotal {
  margin-top: 0px;
}
.text-height {
  line-height: 18px !important;
}
.main {
  width: 1146px;
  margin: 0 auto;
}
.active {
  color: red !important;
}
.invoice-info {
  color: #909399;
}
.invoice-info > div > span {
  color: #606266;
}

.footer {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  margin-bottom: 20px;
}

.mark {
  display: inline-block;
  width: 70px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
<style scoped lang="scss">
.from {
  margin: 0 24px;
}


.defaultPage {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 50px;

  .img {
    width: 200px;
    height: 150px;
    display: block;
  }
  .text {
    font-size: 13px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: #999999;
  }
}
.line {
  width: 100%;
  height: 4px;
  border-bottom: 1px solid #dcdfe6;
  margin-bottom: 20px;
  margin-top: 10px;
}

.el-button--primary.is-plain:hover {
  color: #1890ff;
  background: #e8f4ff;
  border-color: #a3d3ff;
}

.table-box {
  width: 100%;
  border: 1px solid #eaf4ff;
  margin-bottom: 20px;
  :deep(.el-checkbox__label) {
    font-size: 13px;
  }
  .header {
    padding: 0 20px;
    height: 48px;
    background: #f7fbff;
    border-radius: 4px 4px 0px 0px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    .header-left {
      font-size: 13px;
      font-family: PingFang SC-中黑体, PingFang SC;
      font-weight: 600;
      color: #303133;
    }
    .left .el-button {
      font-size: 13px;
    }
    .left {
      font-size: 13px;
      font-family: PingFang SC-常规体, PingFang SC;
      font-weight: normal;
      color: #1890ff;
      line-height: 12px;
    }
  }
  .content {
    width: 100%;
    padding: 20px 40px 0px 40px;
    display: flex;
    position: relative;
    justify-content: space-between;

    .line {
      position: absolute;
      top: 0;
      left: 50%;
      width: 4px;
      height: calc(100% - 40px);
      border-left: 1px dashed #ebf5ff;
      margin: 20px auto;
      transform: translateX(-10px);
    }
    .left {
      flex: 1;
    }
    .right {
      flex: 1;
    }
    :deep(.el-form-item__label) {
      font-size: 12px !important;
      font-family: PingFang SC-常规体, PingFang SC;
      font-weight: normal;
      color: #909399;
      line-height: 12px;
      height: auto;
    }
    :deep(.el-form-item) {
      margin-bottom: 20px;
    }
    :deep(.el-form-item .el-form-item__content) {
      line-height: 12px;
    }

    span {
      font-size: 13px;
      font-family: PingFang SC-常规体, PingFang SC;
      font-weight: 400;
      color: #303133;
      line-height: 12px;
    }
  }
}
.table-btn {
  display: inline-block;
  // width: 50px;
  height: 22px;
  line-height: 22px;
  border-radius: 3px;
  font-size: 13px;
  text-align: center;
  cursor: pointer;
  margin-left: 4px;

  &.blue {
    background: rgba(24, 144, 255, 0.05);

    color: #1890ff;
  }

  &.yellow {
    background: rgba(255, 153, 0, 0.05);
    color: #ff9900;
  }
  &.red {
    background: rgba(255, 153, 0, 0.05);

    color: #ed4014;
  }

  &.green {
    background: rgba(0, 192, 80, 0.05);

    color: #00c050;
  }

  &.gray {
    background: rgba(153, 153, 153, 0.05);
    color: #999999;
  }
}
.from-box :deep(.el-cascader) {
  width: 160px !important;
}
.from-box :deep(.el-select) {
  width: 160px;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.addBox .el-textarea__inner {
  width: 400px;
  height: 90px;
  font-size: 13px;
}
.el-dialog.addBox .el-dialog__body {
  padding: 0;
}
.el-dialog.addBox {
  border-radius: 6px;
  height: 249px;
}
</style>
