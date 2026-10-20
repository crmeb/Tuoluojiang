<!-- 订单信息页面组件 -->
<template>
  <div class="station">
    <el-form ref="form" label-width="100px" :model="rules" :rules="rule">
      <div v-if="!formInfo.edit" class="from-item-title mb15">
        <span>基本信息</span>
      </div>
      <div class="form-box">
        <div class="form-item">
          <el-form-item prop="category_id">
            <template #label>
              <span>订单分类:</span>
            </template>
            <el-cascader
              v-model="rules.category_id"
              :options="options"
              ref="myCascader"
              :props="{ checkStrictly: true, label: 'name', value: 'id' }"
              clearable
              @change="categoryType"
            ></el-cascader>
          </el-form-item>
        </div>
        <div class="form-item">
          <el-form-item prop="title">
            <template #label>
              <span>订单名称:</span>
            </template>
            <el-input v-model.trim="rules.title" clearable placeholder="请输入订单名称" />
          </el-form-item>
        </div>
        <div class="form-item">
          <el-form-item prop="price">
            <template #label>
              <span>订单金额(元):</span>
            </template>
            <el-input-number
              v-model.trim="rules.price"
              :controls="false"
              :min="0"
              :precision="2"
              placeholder="请输入订单金额"
            ></el-input-number>
          </el-form-item>
        </div>
        <div class="form-item">
          <el-form-item prop="eid">
            <template #label>
              <span>客户名称:</span>
            </template>
            <el-select v-model="rules.eid" :filterable="true" @change="categoryName">
              <el-option
                :disabled="formInfo.edit ? true : false"
                v-for="item in nameList"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              ></el-option>
            </el-select>
          </el-form-item>
        </div>

        <div class="form-item">
          <el-form-item prop="startTime">
            <template #label>
              <span>开始时间:</span>
            </template>
            <el-date-picker
              v-model="rules.startTime"
              v-bind="$pickerProps(startDatePicker)"
              type="date"
              clearable
              format="YYYY/MM/DD"
              placeholder="请选择开始时间"
            ></el-date-picker>
          </el-form-item>
        </div>
        <div class="form-item">
          <el-form-item>
            <template #label>
              <span>结束时间:</span>
            </template>
            <el-date-picker
              v-model="rules.endTime"
              type="date"
              format="YYYY/MM/DD"
              clearable
              placeholder="请选择结束时间"
            ></el-date-picker>
          </el-form-item>
        </div>
        <!-- 签约状态 -->
        <div class="form-item">
          <el-form-item prop="sign_status">
            <template #label>
              <span>签约状态:</span>
            </template>
            <el-radio-group v-model="rules.sign_status">
              <el-radio :label="0" :value="0">未签约</el-radio>
              <el-radio :label="1" :value="1">已签约</el-radio>

              <el-radio :label="2" :value="2">作废</el-radio>
            </el-radio-group>
          </el-form-item>
        </div>
        <!-- 订单编号 -->
        <div class="form-item">
          <el-form-item>
            <template #label>
              <span> 订单编号: </span>
            </template>
            <el-input v-model="rules.contract_no" placeholder="系统自动生成" disabled />
          </el-form-item>
        </div>

        <div class="form-item">
          <el-form-item>
            <template #label>
              <span>订单状态:</span>
            </template>
            <el-switch
              v-model="rules.is_abnormal"
              active-text="异常"
              inactive-text="正常"
              :active-value="1"
              :inactive-value="0"
              inactive-color="#1890FF"
              active-color="#C0C4CC"
            />
          </el-form-item>
        </div>
      </div>

      <div class="line"></div>
      <div class="form-box">
        <div class="form-item width100">
          <el-form-item>
            <template #label>
              <span>备注:</span>
            </template>
            <el-input
              v-model="rules.mark"
              type="textarea"
              maxlength="255"
              show-word-limit
              :autosize="autosize"
              placeholder="请填写备注信息"
            />
          </el-form-item>
        </div>
      </div>
    </el-form>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import { contractList } from '@/api/enterprise.ts'
import { clientContractEditApi, clientContractSaveApi, clientNameApi } from '@/api/client.ts'

export default {
  emits: ['handleContract'],
  name: 'ContractInfo',
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      rules: {
        title: '',
        eid: '',
        price: undefined,
        startTime: '',
        category_id: '',
        is_abnormal: 0,
        endTime: '',
        mark: '',
        sign_status: 0, // 签约状态
        contract_no: '' // 订单编号
      },
      nameList: [],
      options: [],
      handleData: {},
      rule: {
        title: [
          {
            required: true,
            message: '请输入订单名称',
            trigger: 'blur'
          }
        ],
        price: [
          {
            required: true,
            message: '请输入订单金额',
            trigger: 'blur'
          }
        ],
        startTime: [
          {
            required: true,
            message: '请选择开始时间',
            trigger: 'blur'
          }
        ],
        sign_status: [{ required: true, message: '请选择签约状态', trigger: 'change' }],
        eid: [{ required: true, message: '请选择客户名称', trigger: 'change' }],
        endTime: [
          {
            required: true,
            message: '请选择结束时间',
            trigger: 'blur'
          }
        ],
        category_id: [{ required: true, message: '请选择订单分类', trigger: 'change' }]
      },
      startDatePicker: this.beginDate(),
      endDatePicker: this.processDate(),
      editIndex: 0,
      autosize: {
        minRows: 6
      }
    }
  },
  watch: {
    formInfo: {
      handler(nVal) {
        this.clientNameList()

        if (nVal.edit) {
          if (nVal.editIndex === undefined) {
            this.rules = {
              eid: nVal.data.eid,
              title: nVal.data.title,
              price: nVal.data.price,
              category_id: nVal.data.category_id,
              startTime: nVal.data.start_date,
              endTime: nVal.data.end_date == '0000-00-00' ? '' : nVal.data.end_date,
              mark: nVal.data.mark,
              is_abnormal: nVal.data.is_abnormal,
              contract_no: nVal.data.contract_no,
              sign_status: nVal.data.sign_status
            }
          }
        } else {
          if (nVal.id) {
            this.rules.eid = nVal.id
          }
        }
      },
      immediate: true,
      deep: true
    }
  },

  methods: {
    reset() {
      this.rules = {
        title: '',
        price: undefined,
        category_id: '',
        startTime: '',
        endTime: '',
        eid: '',
        mark: '',
        contract_no: '',
        sign_status: 0
      }
    },

    // 获取客户列表
    async clientNameList() {
      const result = await clientNameApi()
      this.nameList = result.data
    },

    // 获取订单分类列表
    contractList() {
      contractList().then((res) => {
        this.options = res.data
      })
    },

    categoryType() {
      this.rules.title = this.$refs['myCascader'].getCheckedNodes()[0].label
    },

    categoryName(val) {
      // this.rules.eid = val;
    },
    // 提交
    handleConfirm() {
      return new Promise((resolve, reject) => {
        if (this.rules.endTime && new Date(this.rules.startTime) > new Date(this.rules.endTime)) {
          return this.$message.error('结束时间不能小于开始时间')
        }
        this.$refs.form.validate((valid) => {
          if (valid) {
            let name = this.nameList.filter((item) => item.id == this.rules.eid)

            this.formInfo.name = name[0].name
            const data = {
              eid: this.rules.eid,
              title: this.rules.title,
              category_id: this.rules.category_id,
              price: this.rules.price,
              start_date: dayjs(this.rules.startTime).format('YYYY-MM-DD'),
              end_date: this.rules.endTime ? dayjs(this.rules.endTime).format('YYYY-MM-DD') : '',
              is_abnormal: this.rules.is_abnormal,
              mark: this.rules.mark,
              sign_status: this.rules.sign_status
            }
            this.formInfo.edit ? this.clientContractEdit(this.formInfo.data.id, data) : this.clientContractSave(data)
            resolve(valid)
          }
        })
      })
    },
    handleEmit() {
      this.$emit('handleContract', this.handleData)
    },
    // 保存订单
    clientContractSave(data) {
      this.handleData.loading = true

      clientContractSaveApi(data)
        .then((res) => {
          this.handleData.loading = false
          this.handleData.success = 1
          this.handleData.title = this.rules.title
          this.handleData.price = this.rules.price
          this.handleData.eid = this.rules.eid
          this.handleData.id = res.data.id
          this.handleData.category_id = this.rules.category_id.pop()
          this.handleData.start_date = dayjs(this.rules.startTime).format('YYYY-MM-DD')
          this.handleData.end_date = this.rules.endTime ? dayjs(this.rules.endTime).format('YYYY-MM-DD') : ''
          this.handleData.contract_no = this.rules.contract_no
          this.handleData.sign_status = this.rules.sign_status
          this.handleData.mark = this.rules.mark
          this.handleData.name = this.formInfo.name
          this.handleEmit()

          this.reset()
        })
        .catch((error) => {
          this.handleData.loading = false
          this.handleEmit()
        })
    },
    // 修改订单
    clientContractEdit(id, data) {
      this.handleData.loading = true
      this.handleEmit()
      clientContractEditApi(id, data)
        .then((res) => {
          this.handleData.loading = false
          this.handleData.success = 1
          this.handleData.title = this.rules.title
          this.handleData.editIndex = this.editIndex++
          this.handleData.price = this.rules.price
          this.handleEmit()
        })
        .catch((error) => {
          this.handleData.loading = false
          this.handleEmit()
        })
    },
    beginDate() {
      const self = this
      return {
        disabledDate(time) {
          if (self.rules.endTime) {
            // 如果结束时间不为空，则小于结束时间
            return new Date(self.rules.endTime).getTime() < time.getTime()
          } else {
            // return time.getTime() > Date.now( //开始时间不选时，结束时间最大值小于等于当天
          }
        }
      }
    },
    processDate() {
      const self = this
      return {
        disabledDate(time) {
          if (self.rules.startTime) {
            // 如果开始时间不为空，则结束时间大于开始时间
            return new Date(self.rules.startTime).getTime() > time.getTime()
          } else {
            // return time.getTime() > Date.now() // 开始时间不选时，结束时间最大值小于等于当天
          }
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.station :deep(.el-drawer__body) {
  padding: 20px 20px 50px 20px;
}
.add-color {
  color: #606266;
}
.line {
  width: 100%;
  height: 4px;
  border-bottom: 1px dashed #dcdfe6;
  margin-bottom: 30px;
  margin-top: 10px;
}
:deep(.el-form--inline .el-form-item) {
  display: flex;
}
:deep(.el-input__inner) {
  text-align: left;
}
:deep(.el-date-editor),
:deep(.el-input-number),
:deep(.el-cascader) {
  width: 100%;
}
:deep(.el-select) {
  width: 100%;
}
.from-item-title {
  border-left: 5px solid #1890ff;
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
    width: 48%;
    :deep(.el-form-item__content) {
      width: calc(100% - 90px);
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
.from-foot-btn {
  button {
    height: auto;
  }
}
</style>
