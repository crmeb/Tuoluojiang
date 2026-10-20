<!-- 申请发票信息侧滑弹窗 -->
<template>
  <div class="station">
    <el-drawer
      class="invoice-apply-drawer"
      :append-to-body="true"
      :before-close="handleClose"
      :direction="direction"
      :modal="true"
      :size="formData.width"
      :title="formData.title ? formData.title : title"
      v-model="drawer"
      :close-on-click-modal="false"
    >
      <div class="invoice">
        <el-form ref="form" :model="rules" :rules="rule" label-width="110px">
          <div class="from-item-title mb15">
            <span>基本信息</span>
          </div>

          <!-- 基本信息 -->
          <div class="form-box">
            <div class="form-item">
              <el-form-item>
                <template #label>
                  <span><span class="color-tab">* </span>客户名称:</span>
                </template>

                <span class="font-color">{{ rules.source }}</span>
              </el-form-item>
            </div>
            <div class="form-item">
              <el-form-item>
                <template #label>
                  <span><span class="color-tab">* </span>订单名称:</span>
                </template>

                <span class="font-color">{{ rules.name }}</span>
              </el-form-item>
            </div>

            <div class="form-item">
              <el-form-item prop="category_id">
                <template #label>
                  <span>发票类目:</span>
                </template>
                <el-select
                  v-model="rules.category_id"
                  placeholder="请选择发票类型"
                  clearable
                >
                  <el-option v-for="item in categoryList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
            </div>
            <div class="form-item">
              <el-form-item prop="bill_date">
                <template #label>
                  <span>期望开票日期:</span>
                </template>
                <el-date-picker
                  v-model="rules.bill_date"
                  placeholder="请选择期望开票时间"
                  clearable
                  type="date"
                >
                </el-date-picker>
              </el-form-item>
            </div>
          </div>
          <div class="line" />
          <!-- 邮寄信息 -->
          <div class="from-item-title mb15">
            <span>邮寄信息</span>
          </div>
          <div class="form-box">
            <div class="form-item" style="width: 100%">
              <el-form-item>
                <template #label>
                  <span>开票要求:</span>
                </template>
                <el-radio-group v-model="rules.collect_type" @change="collectTypeFn">
                  <el-radio label="mail" value="mail">电子</el-radio>
                  <el-radio label="express" value="express">纸质</el-radio>
                </el-radio-group>
              </el-form-item>
            </div>

            <div v-if="rules.collect_type == 'express'" class="form-item">
              <el-form-item prop="collect_name">
                <template #label>
                  <span>联系人:</span>
                </template>
                <el-input
                  v-model.trim="rules.collect_name"
                  placeholder="请输入联系人"
                  clearable
                />
              </el-form-item>
            </div>
            <div v-if="rules.collect_type == 'express'" class="form-item">
              <el-form-item prop="collect_tel">
                <template #label>
                  <span>联系电话:</span>
                </template>
                <el-input
                  v-model.trim="rules.collect_tel"
                  placeholder="请输入联系电话"
                  clearable
                />
              </el-form-item>
            </div>

            <div v-if="rules.collect_type == 'mail'" class="form-item">
              <el-form-item prop="collect_email">
                <template #label>
                  <span>邮箱地址:</span>
                </template>
                <el-input
                  v-model="rules.collect_email"
                  placeholder="请输入邮箱地址"
                  clearable
                />
              </el-form-item>
            </div>
            <div v-else class="form-item">
              <el-form-item prop="mail_address">
                <template #label>
                  <span>邮寄地址:</span>
                </template>
                <el-input
                  v-model.trim="rules.mail_address"
                  placeholder="请输入邮寄详细地址"
                  clearable
                />
              </el-form-item>
            </div>
          </div>
          <div class="line" />

          <!-- 发票信息 -->
          <div class="from-item-title mb15">
            <span>发票信息</span>
          </div>
          <div class="form-box">
            <div class="form-item">
              <el-form-item>
                <template #label>
                  <span><span class="color-tab">* </span>发票类型:</span>
                </template>
                <el-select v-model="rules.types" placeholder="请选择发票类型" clearable>
                  <el-option v-for="item in invoiceOptions" :key="item.value" :label="item.label" :value="item.value" />
                </el-select>
              </el-form-item>
            </div>
            <div class="form-item">
              <el-form-item prop="amount">
                <template #label>
                  <span>开票金额(元):</span>
                </template>
                <el-input-number
                  v-model="rules.amount"
                  :controls="false"
                  :min="0"
                  placeholder="请输入开票金额"
                  :precision="2"
                  clearable
                ></el-input-number>
              </el-form-item>
            </div>

            <div class="form-item">
              <el-form-item prop="title">
                <template #label>
                  <span>发票抬头:</span>
                </template>
                <el-input
                  v-model.trim="rules.title"
                  placeholder="请输入抬头信息"
                  clearable
                />
              </el-form-item>
            </div>
            <div v-if="rules.types > 1" class="form-item">
              <el-form-item prop="ident">
                <template #label>
                  <span>纳税人识别号:</span>
                </template>
                <el-input
                  v-model.trim="rules.ident"
                  placeholder="请输入纳税人识别号"
                  clearable
                />
              </el-form-item>
            </div>
            <div v-if="rules.types > 2" class="form-item">
              <el-form-item>
                <!-- prop="bank" -->
                <template #label>
                  <span>开户行:</span>
                </template>
                <el-input
                  v-model.trim="rules.bank"
                  placeholder="请输入开户行"
                  clearable
                />
              </el-form-item>
            </div>
            <div v-if="rules.types > 2" class="form-item">
              <el-form-item>
                <!-- prop="account" -->
                <template #label>
                  <span>开户账号:</span>
                </template>
                <el-input
                  v-model.trim="rules.account"
                  placeholder="请输入开户账号"
                  clearable
                />
              </el-form-item>
            </div>
            <div v-if="rules.types > 2" class="form-item">
              <el-form-item>
                <!-- prop="address" -->
                <template #label>
                  <span>开票地址:</span>
                </template>
                <el-input
                  v-model="rules.address"
                  placeholder="请输入开票地址"
                  clearable
                  @input="onInput()"
                />
              </el-form-item>
            </div>
            <div v-if="rules.types > 2" class="form-item">
              <el-form-item>
                <!-- prop="tel" -->
                <template #label>
                  <span>电话:</span>
                </template>
                <el-input v-model.trim="rules.tel" placeholder="请输入电话号码" clearable />
              </el-form-item>
            </div>
          </div>
          <div class="line" />

          <div class="form-box">
            <div class="form-item" style="width: 100%">
              <el-form-item>
                <template #label>
                  <span>备注:</span>
                </template>
                <el-input
                  v-model="rules.mark"
                  placeholder="请填写备注信息"
                  :rows="4"
                  maxlength="255"
                  show-word-limit
                  type="textarea"
                />
              </el-form-item>
            </div>
          </div>
        </el-form>
        <div class="button from-foot-btn fix btn-shadow">
          <el-button @click="handleClose">取消</el-button>
          <el-button :loading="loading" type="primary" @click="handleConfirm('ruleForm')">确定</el-button>
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import { clientInvoiceEditApi, clientInvoiceSaveApi, clientInvoiceDetailApi } from '@/api/client.ts'
import helper from '@/libs/helper.ts'
import { getStorageJson } from '@/utils/storage.ts'

export default {
  emits: ['isOk'],
  name: 'InvoiceApply',
  props: {
    contractInvoice: {
      type: String,
      default: ''
    },
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    title: {
      type: String,
      default: ''
    }
  },
  data() {
    const checkEmail = (rule, value, callback) => {
      const mailReg = helper.mailboxReg
      if (!value) {
        return callback(new Error('邮箱不能为空'))
      } else {
        return callback()
      }
    }
    const checkIdent = (rule, value, callback) => {
      if (!value && this.rules.types > 1) {
        return callback(new Error('请输入纳税人识别号'))
      } else if (!helper.identReg.test(value)) {
        callback(new Error('税号输入不合法'))
      } else {
        callback()
      }
    }
    return {
      drawer: false,
      direction: 'rtl',
      categoryList: [],
      eid: 0,
      cid: 0,
      rules: {
        name: '',
        source: '',
        price: '',
        collect_type: 'mail',
        amount: undefined,
        bill_date: '',
        types: 2,
        title: '',
        ident: '',
        mail_address: '',
        bank: '',
        account: '',
        tel: '',
        mark: '',
        collect_name: '',
        collect_tel: '',
        method: 'mail',
        addressBox: '',
        collect_email: '',
        address: '',
        category_id: ''
      },
      methodOptions: [
        { value: 'express', label: '快递' },
        { value: 'mail', label: '邮件' }
      ],
      loading: false,
      invoiceOptions: [
        { value: 1, label: '个人普通发票' },
        { value: 2, label: '企业普通发票' },
        { value: 3, label: '企业专用发票' }
      ],
      rule: {
        name: [{ required: true, message: '请输入发票名称', trigger: 'blur' }],
        amount: [{ required: true, message: '请输入开票金额', trigger: 'blur' }],
        bill_date: [{ required: true, message: '请选择期望开票时间', trigger: 'blur' }],
        title: [{ required: true, message: '请输入抬头信息', trigger: 'blur' }],
        ident: [{ required: true, validator: checkIdent, trigger: 'blur' }],
        category_id: [{ required: true, message: '请选择发票类目', trigger: 'change' }],
        collect_name: [{ required: true, message: '电话号码不能为空', trigger: 'blur' }],
        collect_tel: [{ required: true, message: '电话号码不能为空', trigger: 'blur' }],
        collect_email: [{ required: true, validator: checkEmail, trigger: 'blur' }],
        mail_address: [{ required: true, message: '请填写邮寄地址', trigger: 'blur' }],
        address: [{ required: true, message: '请填写开票地址', trigger: 'blur' }],
        bank: [{ required: true, message: '请输入开户行', trigger: 'blur' }],
        account: [{ required: true, message: '请输入开户账号', trigger: 'blur' }],
        tel: [{ required: true, message: '电话号码不能为空', trigger: 'blur' }]
      }
    }
  },
  watch: {
    formData: {
      handler(nVal) {
        // 添加
        if (!nVal.edit) {
          if (nVal.data.cid == 0) {
            this.eid = nVal.data.id
            this.cid = 0
          }
          if (nVal.data.eid) {
            this.eid = nVal.data.eid
            this.cid = nVal.data.id
          }
          this.rules.name = nVal.data.title || '--'
          if (nVal.data.name) {
            this.rules.source = nVal.data.name
          } else {
            this.rules.source = nVal.data.client ? nVal.data.client.name : '--'
          }

          if (nVal.oneData) {
            this.rules = nVal.oneData
            this.rules.source = nVal.data.name ? nVal.data.name : nVal.data.client.name
            this.rules.types = Number(nVal.oneData.types)
            setTimeout(() => {
              this.rules.category_id = nVal.oneData.category_id
            }, 200)
          } else {
            this.rules.address = ''
          }
          // 发票抬头默认取客户名称，可修改
          if (!this.rules.title && this.rules.source !== '--') {
            this.rules.title = this.rules.source
          }

          this.rules.bill_date = dayjs(new Date()).format('YYYY-MM-DD')
          this.rules.price = nVal.data.price
        }
      },
      deep: true
    },
  },
  methods: {
    change(e) {
      this.$forceUpdate()
    },
    handleClose() {
      this.drawer = false
      this.reset()
    },
    openBox(id) {
      if (id) {
        clientInvoiceDetailApi(id).then((res) => {
          this.rules = res.data
          this.rules.source = res.data.client ? res.data.client.name : '--'
          this.eid = res.data.client ? res.data.client.id : res.data.eid
          this.rules.name = res.data.treaty ? res.data.treaty.title : '--'
          this.cid = res.data.treaty ? res.data.treaty.id : res.data.cid
          this.rules.types = Number(res.data.types)
        })
      }
      this.drawer = true
    },
    collectTypeFn(e) {
      this.rules.collect_type = e
    },
    onInput() {
      this.$forceUpdate()
    },
    reset() {
      for (let key in this.rules) {
        this.rules[key] = ''
      }
      this.rules.amount = undefined
      this.rules.types = 2
      this.rules.collect_type = 'mail'
    },
    handleMethod() {
      this.$refs.form.clearValidate('address')
      this.$refs.form.clearValidate('mailbox')
    },
    // 提交
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          var data = this.rules
          data.bill_date = dayjs(this.rules.bill_date).format('YYYY-MM-DD')
          data.cid = this.cid
          data.eid = this.eid

          let objId = {
            eid: this.formData.data.eid,
            category_id: this.rules.category_id
          }

          let categoryList = getStorageJson('categoryList', [])
          if (categoryList.length) {
            var flag = categoryList.find((cur) => cur.eid == this.formData.data.eid)

            if (flag) {
              categoryList.map((item) => {
                if (item.eid == objId.eid) {
                  item.category_id = objId.category_id
                }
              })
            } else {
              categoryList.push(objId)
            }
          }
          localStorage.setItem('categoryList', JSON.stringify(categoryList))
          if (this.formData.edit) {
            this.invoiceEdit(this.formData.rowData.id, data)
          } else {
            this.invoiceSave(data)
          }
        }
      })
    },
    // 发票申请
    invoiceSave(data) {
      this.loading = true
      clientInvoiceSaveApi(data)
        .then((res) => {
          this.handleClose()
          this.$emit('isOk')
          this.reset()
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },
    // 发票修改
    invoiceEdit(id, data) {
      this.loading = true
      clientInvoiceEditApi(id, data)
        .then((res) => {
          this.handleClose()
          this.$emit('isOk')
          this.reset()
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-form--inline .el-form-item) {
  display: flex;
}
:deep(.el-input-number) {
  width: 100%;
  .el-input__inner {
    text-align: left;
  }
}
.font-color {
  color: #606266;
}
.line {
  width: 100%;
  height: 4px;
  border-bottom: 1px dashed #dcdfe6;
  margin-bottom: 30px;
  margin-top: 10px;
}
:deep(.el-date-editor) {
  width: 100%;
}
.invoice {
  margin: 20px 20px 20px 20px;
  .from-foot-btn button {
    width: auto;
    height: auto;
  }
}
.from-item-title {
  border-left: 3px solid #1890ff;
  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}
.form-box {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  .form-item {
    width: 49%;
    :deep(.el-form-item__content) {
      width: calc(100% - 110px);
    }
    :deep(.el-select) {
      width: 100%;
    }
    :deep(.el-textarea__inner) {
      resize: none;
    }
  }
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.el-drawer.invoice-apply-drawer {
  width: 50% !important;
}
</style>
