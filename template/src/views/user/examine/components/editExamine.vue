<template>
  <div>
    <!-- 编辑审批 -->
    <el-drawer
      class="user-examine-edit-examine-drawer"
      size="628px"
      v-model="drawer"
      :show-close="true"
      :direction="direction"
      :before-close="close"
      :append-to-body="true"
      :close-on-click-modal="false"
    >
      <template #header>
        <div>
          <div v-if="configData" class="headerBox acea-row row-middle row-between">
            <div v-if="!isEdit" class="acea-row row-middle">
              <div
                class="portrait mr10"
                :style="{ backgroundColor: configData.approve ? configData.approve.color : configData.color }"
              >
                <i class="iconfont" :class="configData.approve ? configData.approve.icon : configData.icon"></i>
              </div>
              <div class="nameBox">
                <span class="st1">{{ configData.approve ? configData.approve.name : configData.name }}</span>
              </div>
            </div>
            <div v-else class="acea-row row-middle">
              <div
                class="portrait mr10"
                :style="{ backgroundColor: configData.approve ? configData.approve.color : configData.color }"
              >
                <i class="iconfont" :class="configData.approve ? configData.approve.icon : configData.icon"></i>
              </div>
              <div class="nameBox">
                <span class="st1">{{ configData.approve ? configData.approve.name : configData.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
      <div class="ex-content">
        <el-scrollbar style="height: 100%">
          <div class="ex-content-con">
            <div class="acea-row mb20">
              <div class="shu mr10"></div>
              <span class="title">提交审批</span>
            </div>
            <component
              :is="formCreateComponent"
              v-if="formCreateComponent"
              class="edit-examine-form"
              ref="fc"
              v-model:api="fapi"
              :option="option"
              :rule="rules"
              @change="handleChange"
            />
            <process-from
              v-if="configData && configData.examine != 0"
              :examine-data="examineData"
              :edit-user="editUser"
            ></process-from>
          </div>
        </el-scrollbar>
      </div>
      <div class="button from-foot-btn fix btn-shadow">
        <el-button @click="close">取消</el-button>
        <el-button type="primary" :loading="loading" @click="handleConfirm('ruleForm')">
          确定
        </el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import { defineAsyncComponent, markRaw } from 'vue'
import { loadApprovalForm } from '@/libs/approval-form'
import request from '@/api/request.ts'
import {
  approveApplyEditApi,
  approveApplyFormApi,
  approveApplyListApi,
  attendanceAbnormalDateApi,
  approveHolidayTypeSelectApi
} from '@/api/business.ts'

export default {
  emits: ['scheduleRecord', 'isOk'],
  components: {
    processFrom: defineAsyncComponent(() => import('./process'))
  },
  props: {
    parameterData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    typeNum: {
      type: Number,
      default: 1
    }
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      fapi: null,
      formCreateComponent: null,
      abnormal: '', //异常日期
      record: '', //异常记录
      holiday: '', //假期类型
      abnormal_id: '', //异常日期id
      record_id: 0, //异常记录id
      holiday_id: 0,
      abnormal_label: '',
      record_label: '',
      calculateType: '',
      option: {
        form: {
          labelWidth: 'auto',
          labelSuffix: '：'
        },
        submitBtn: false,
        global: {
          frame: {
            props: {
              closeBtn: false,
              okBtn: false,
              onLoad: (e) => {
                e.fApi = this.$refs.fc.fapi
              }
            }
          }
        }
      }, // 表单配置
      rules: [],
      approveId: 0,
      configData: null,
      formData: {},
      isRequest: true,
      examineData: {},
      id: 0,
      isEdit: false,
      loading: false,
      editUser: false, // 审批人添加按钮
      approverDelete: true,
      copyerDelete: true,
      tryDateOptions: null, // 异常时间
      tryYiChangOptions: null, // 异常记录
      holidayTypeOptions: null
    }
  },
  created() {},
  mounted() {
    if (this.typeNum == 1) {
      this.holidayType()
      this.attendanceAbnormalDate()
    }
  },
  watch: {},
  methods: {
    handleClose() {
      this.id = 0
      this.drawer = false
    },
    close() {
      this.drawer = false
      this.id = 0
    },
    openBox(command, val, type) {
      this.configData = command
      this.id = command.id ? command.id : command
      this.approveId = this.configData.approve_id ? this.configData.approve_id : this.configData.id
      if (!this.isEdit) {
        // 添加
        this.approveApply(this.id, val, type)
      } else {
        // 编辑
        this.approveApply(command.approve_id)
      }
    },
    // 获取表单配置
    approveApply(id, val, type) {
      Promise.all([loadApprovalForm(), approveApplyFormApi(id, this.parameterData)]).then(([FormCreate, res]) => {
        this.formCreateComponent = markRaw(FormCreate)
        this.configData = res.data.info
        res.data.forms.forEach((item) => {
          if ((item.type === 'moneyFrom' || item.type === 'inputNumber') && item.props) {
            if (item.props.max) {
              item.props.max = Number(item.props.max)
            }
            if (item.props.min) {
              item.props.min = Number(item.props.min)
            }
            if (item.props.precision) {
              item.props.precision = Number(item.props.precision)
            }
          }
          if (item.children) {
            item.children.forEach((it) => {
              if (it.title == '异常日期') {
                this.abnormal = it.field
              }
              if (it.title == '异常记录') {
                this.record = it.field
              }
            })
          }
        })
        this.rules = res.data.forms
        // res.data.forms.map((item) => {
        //   if (item.children && item.type !== 'span') {
        //     item.children.forEach((per) => {
        //       const props = per.props;
        //       const isDisabledField = props && props.disabled;
        //       if (!isDisabledField) {
        //         delete per.value
        //       }
        //       if (per.calculateType) {
        //         per.calculateType = item.type
        //         this.calculateType = item.type
        //         if (per.title === '异常日期' && per.calculateType === 'refillFrom') {
        //           per.options = this.tryDateOptions
        //         }
        //         if (per.title === '假期类型' && per.calculateType === 'leaveFrom') {
        //           per.options = this.holidayTypeOptions
        //         }

        //         this.rules.push(per)
        //       } else {
        //         this.rules.push(per)
        //       }
        //     })
        //   } else {
        //     item.calculateType = item.type
        //     this.rules.push(item)
        //   }
        // })

        if (!this.isEdit) {
          this.drawer = true
          this.$nextTick(() => {
            const data = this.handleFromObj()
            if (this.configData.examine == 0) return
            this.approveApplyList(this.approveId, data)
          })
        } else {
          if (this.configData.examine == 0) return false
          this.approveApplyEdit(this.id, { types: 0 })
        }
      }).catch(() => {
        this.$message.error('审批表单加载失败，请重试')
      })
    },
    // 获取人员配置表单
    approveApplyList(id, data) {
      approveApplyListApi(this.approveId || this.id, data).then((res) => {
        this.examineData = res.data
        var rules = res.data.rules
        if (rules && rules.edit) {
          var rulesEdit = rules.edit + ''
          this.approverDelete = rulesEdit.includes('1')
          this.copyerDelete = rulesEdit.includes('2')
        }

        if (rules && rules.edit && rulesEdit.length > 0) {
          // 是否可删除审批人与抄送人
          if (this.examineData.list.length > 0) {
            this.examineData.list.map((value) => {
              if (value.types == 1) {
                // 判断审批人是否为指定审批人
                if (rules.abnormal > 0 && (value.settype === 2 || value.settype === 7) && value.users.length <= 0) {
                  value.users.push(rules)
                }
                if (rulesEdit.includes('1')) {
                  value.users.map((val) => {
                    val.isDelete = true
                  })
                } else {
                  this.editUser = true
                }
              }
              if (value.types == 2 && rulesEdit.includes('2')) {
                value.users.map((val) => {
                  val.isDelete = true
                })
              }
            })
          }
        } else {
          this.editUser = true
        }
      })
    },
    holidayType() {
      approveHolidayTypeSelectApi().then((res) => {
        this.holidayTypeOptions = res.data
      })
    },
    handleChange(e) {
      // V1.4动态显示隐藏申请开票的邮寄字段
      let expressList = ['liaisonMan', 'telephone', 'mailingAddress']
      let timeType
      const data = this.handleFromObj()
      this.eachFormRule(this.rules, (it) => {
        if (it.field === e && it.title == '假期类型' && this.holidayTypeOptions) {
          this.holidayTypeOptions.map((item) => {
            if (item.value == data[e]) {
              timeType = item.duration_type
            }
          })
        }
      })

      this.eachFormRule(this.rules, (item) => {
        if (data[e] == 'express') {
          if (expressList.includes(item.symbol)) {
            item.hidden = false
          }
          if (item.symbol == 'invoicingEmail') {
            item.hidden = true
          }
        } else if (data[e] == 'mail') {
          if (expressList.includes(item.symbol)) {
            item.hidden = true
          }
          if (item.symbol == 'invoicingEmail') {
            item.hidden = false
          }
        }

        if (this.fapi.rule[9] && this.fapi.rule[9].value == 1) {
          if (item.symbol == 'dutyParagraph') {
            item.hidden = true
            item.effect.required = true
          }
        } else {
          if (item.symbol == 'dutyParagraph') {
            item.hidden = false
            item.effect.required = true
          }
        }
        if (!!JSON.stringify(timeType) && item.type == 'timeFrom') {
          if (!item.props) item.props = {}
          item.props.timeType = timeType == 0 ? 'day' : 'time'
        }
      })
      if (data[e] && data[e].timeType == 'time') {
        const dateStart = data[e].dateStart
        const dateEnd = data[e].dateEnd

        // 将日期时间字符串转换为 Date 对象
        const startDate = new Date(dateStart)
        const endDate = new Date(dateEnd)

        // 计算时间差
        const timeDifference = endDate - startDate

        const millisecondsInHour = 60 * 60 * 1000
        const hours = timeDifference / millisecondsInHour
        data[e].duration = hours ? hours.toFixed(2) : ''
      }

      if (this.tryDateOptions) {
        this.tryDateOptions.map((item) => {
          if (item.value == data[e]) {
            this.abnormal_label = item.label
          }
        })
      }
      if (this.tryYiChangOptions) {
        this.tryYiChangOptions.map((item) => {
          if (item.value == data[e]) {
            this.record_label = item.label
          }
        })
      }

      let label
      for (let i in data) {
        if (data[i] == data[e]) {
          label = i
        }
        if (i == this.abnormal) {
          this.abnormal_id = data[i]
        }
        if (i == this.record) {
          this.record_id = data[i]
        }
        if (i == this.holiday) {
          this.holiday_id = data[i]
        }
      }

      // 赋值异常记录
      if (label == this.abnormal) {
        this.attendanceAbnormalRecord(data[e])
      }
      if (!this.isEdit) {
        if (this.configData.examine == 0) return
        this.approveApplyList(this.id, data)
      } else {
        if (this.configData.examine == 0) return
        this.approveApplyList(this.configData.approve_id, data)
      }
    },
    // 递归回显审批内容
    calculateLevel(arr, option) {
      if (!Array.isArray(arr)) return arr
      arr.forEach((value) => {
        if (!value || typeof value !== 'object') return
        if (option[value.field]) {
          value.value = option[value.field]
        } else {
          this.calculateLevel(value.children, option)
        }
      })
      return arr
    },

    // 编辑
    approveApplyEdit(id, data) {
      approveApplyEditApi(this.id, data).then((res) => {
        this.drawer = true
        var option = res.data
        this.calculateLevel(this.rules, option)

        this.eachFormRule(this.rules, (item) => {
          if (item.title == '异常日期') {
            this.attendanceAbnormalDate()
            if (item.value && item.value.abnormal_id) {
              this.abnormal_id = item.value.abnormal_id.value
              this.abnormal_label = item.value.abnormal_id.label
            }
            item.value = this.abnormal_label

            if (!this.abnormal_id) return false
            this.attendanceAbnormalRecord(this.abnormal_id)
          }
        })

        const data = this.handleFromObj()

        this.approveApplyList(this.approveId, data)
      })
    },
    handleFromData() {
      const attr = []
      this.fapi.rule.map((item) => {
        const condition = {}
        condition[item.field] = item.value ? item.value : ''
        attr.push(condition)
      })
      return attr
    },
    // 递归遍历表单规则，嵌套容器（refillFrom、leaveFrom 等）的 children 会一并展开
    // callback(rule, calculateType)：calculateType 为所属容器类型，顶层规则回退到自身 calculateType
    eachFormRule(rules, callback, parentRule) {
      if (!Array.isArray(rules)) return
      rules.forEach((rule) => {
        if (!rule || typeof rule !== 'object') return
        const children = Array.isArray(rule.children) ? rule.children : null
        const isWrapper = !!children && children.some((child) => child && typeof child === 'object' && child.field)
        if (rule.field) callback(rule, parentRule ? parentRule.type : rule.calculateType)
        if (isWrapper) this.eachFormRule(children, callback, rule)
      })
    },
    handleFromObj() {
      const obj = {}
      if (!this.fapi) return obj
      // 以 form-create 登记的字段为准：approvalBill 等明细组件的 children 只是行模板，
      // 不是主表单字段（真实值在容器自身的 value 上），不能混进提交数据
      const validFields = this.fapi.fields()
      this.eachFormRule(this.fapi.rule, (rule, calculateType) => {
        if (validFields.indexOf(rule.field) === -1) return
        if (Object.prototype.toString.call(rule.value) === '[object Object]') {
          rule.value.type = calculateType
        }
        obj[rule.field] = rule.value ? rule.value : ''
      })

      return obj
    },
    // 提交
    handleConfirm() {
      const id = !this.isEdit ? this.id : this.approveId
      this.approveApplySave(id)
    },
    // 保存
    approveApplySave(id) {
      const FromData = {
        title: '添加职位',
        method: 'post',
        action: `approve/apply/save/${id}`
      }
      this.$refs.fc.fapi.submit((formData) => {
        Object.keys(formData).forEach((k) => {
          if (formData[k] === undefined) formData[k] = ''
        })

        const processInfo = this.examineData.list
        if (processInfo && processInfo.length && this.configData.examine != 0) {
          let len = 0
          for (let i = 0; i < processInfo.length; i++) {
            const value = processInfo[i]
            if (!this.approverDelete && value.types == 1 && value.users.length <= 0) {
              this.$message.error('自选节点不能为空')
              return
            }
            if (!this.copyerDelete && value.types == 2 && value.users.length <= 0) {
              this.$message.error('自选节点不能为空')
              return
            }
            if (
              this.approverDelete &&
              value.types == 1 &&
              (value.settype == 4 || value.settype == 1) &&
              value.users.length <= 0
            ) {
              this.$message.error('自选节点不能为空')
              return
            }
            if (value.users.length <= 0) {
              len++
            }
          }
          if (len === processInfo.length) {
            this.$message.error('自选节点不能为空')
            return
          }
          processInfo.forEach((value, index) => {
            if (value.users.length <= 0) {
              processInfo.splice(index, 1)
            }
          })
        }
        const data = {
          formInfo: formData,
          processInfo: processInfo,
          link_id: this.parameterData.remind_id || 0
        }

        if (this.isEdit) {
          data.apply_id = this.id
        }
        this.loading = true

        request[FromData.method.toLowerCase()](FromData.action, data)
          .then((res) => {
            this.loading = false
            if (res.status == 200) {
              this.close()
              this.$emit('scheduleRecord')
              this.$emit('isOk')
            }
          })
          .catch((err) => {
            this.loading = false
          })
      }).catch(() => {}) // 校验不通过时 reject，错误已显示在表单上
    },
    // 调用接口赋值 异常日期
    attendanceAbnormalDate() {
      attendanceAbnormalDateApi().then((res) => {
        if (res.status == 200) {
          this.tryDateOptions = res.data
          this.tryDateOptions = this.tryDateOptions.map((it) => {
            it.label = it.date
            it.value = it.id
            return it
          })
        }
      })
    },
    // 异常记录
    attendanceAbnormalRecord(id) {
      this.eachFormRule(this.rules, (item) => {
        if (item.symbol == 'attendanceExceptionDate' && item.options && item.options.length > 0) {
          const option = item.options.filter((it) => it.value == id)
          this.tryYiChangOptions = option.length ? option[0].record : []
        }
        if (item.symbol == 'attendanceExceptionRecord' && this.tryYiChangOptions && this.tryYiChangOptions.length > 0) {
          item.options = this.tryYiChangOptions
          item.value = ''
        }
      })
    }
  }
}
</script>

<style scoped lang="scss">
.headerBox {
  .portrait {
    width: 36px;
    height: 36px;
    border-radius: 5px;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    i {
      font-size: 20px;
      color: #fff;
    }
  }
  .nameBox {
    span {
      display: block;
    }
    .st1 {
      font-size: 15px;
      font-weight: 600;
      color: rgba(0, 0, 0, 0.85);
    }
    .st2 {
      font-size: 13px;
      color: #999999;
    }
  }
}
.iconBox {
  width: 46px;
  height: 46px;
  background: #1890ff;
  border-radius: 6px;
  color: #fff;
  font-size: 26px;
  line-height: 46px;
  text-align: center;
}

.iBox {
  background: #cccccc;
  i {
    color: #fff;
  }
}

.titleBox {
  color: #000000;
  font-size: 16px;
  line-height: 46px;
  font-weight: 600;
}

.ex-content {
  padding: 20px 0 20px 20px;
  height: 100%;
  .ex-content-con {
    padding-right: 24px;
  }
  :deep(.select-item) {
    margin-top: 0 !important;
  }
  :deep(.el-divider--horizontal) {
    margin-top: 0;
    margin-bottom: 30px;
  }
  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }
  .shu {
    width: 3px;
    height: 16px;
    background: #1890ff;
    display: inline-block;
  }
  .title {
    font-size: 14px;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.85);
  }
}
:deep(.el-input) {
  font-size: 12px;
}
:deep(.el-cascader) {
  width: 100%;
}
:deep(.el-form-item) {
  margin-bottom: 20px;
}

:deep(.el-form-item:last-child) {
  margin-bottom: 0px;
}

:deep(.el-col > .el-form-item) {
  margin-bottom: 0;
}

:deep(.el-col + .el-col),
:deep(.el-form-item + .el-form-item) {
  margin-top: 20px;
}

.edit-examine-form {
  margin-bottom: 20px;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.user-examine-edit-examine-drawer .el-drawer__body {
  padding-bottom: 40px;
}
</style>
