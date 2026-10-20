<template>
  <div>
    <div class="v-height-flag">
      <div class="plan-title">
        <div class="p30">
          <el-tabs v-model="planIndex" @tab-click="handleClick">
            <el-tab-pane
              v-for="(item, index) in periodOptions"
              :key="index"
              :label="item.label"
              :name="String(item.value)"
              class="is-active"
            />
          </el-tabs>
        </div>
      </div>
      <div class="box">
        <div class="mb20">
          <div class="form-list">
            <label for="" style="margin-top: 0"><span class="color-tab">*</span> 开启状态：</label>
            <el-col :span="12" class="form-list-con">
              <el-switch
                v-model="from.status"
                active-text="开启"
                :active-value="1"
                inactive-text="关闭"
                :inactive-value="0"
              />
            </el-col>
          </div>
          <div class="form-list">
            <label for=""><span class="color-tab">*</span> 创建考核提醒：</label>
            <el-row :gutter="14">
              <el-col :span="12" class="form-list-con">
                <el-select
                  v-if="planIndex == 1"
                  v-model="from.remindDays"
                  placeholder="请选择创建考核提醒日期"
                  style="flex: 100%"
                >
                  <el-option
                    v-for="(item, index) in remindDaysData"
                    :key="index"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <div v-if="planIndex == 2" class="plan-add">
                  <el-input-number
                    v-model="from.remindDays"
                    :controls="false"
                    :max="28"
                    :min="1"
                    :precision="0"
                    placeholder="请选择创建考核提醒日期"
                  />
                  <p class="days">号</p>
                </div>
                <div v-if="planIndex >= 3" class="flex">
                  <!-- <el-col :span="12" > -->
                  <el-select v-model="from.remindMonth" class="plan-add" placeholder="请选择月份">
                    <el-option
                      v-for="(item, index) in months"
                      :key="'month' + index"
                      :label="'第' + item + '个月'"
                      :value="item"
                    />
                  </el-select>
                  <!-- </el-col> -->
                  <div class="plan-days">
                    <el-input-number
                      v-model="from.remindDays"
                      :controls="false"
                      :max="28"
                      :min="1"
                      :precision="0"
                      placeholder="请选择创建考核提醒日期"
                    ></el-input-number>
                    <p class="days">号</p>
                  </div>
                </div>
              </el-col>
              <el-col :span="24" class="assess-info">用于设置提醒考核人，为被考核人制定考核目标的时间</el-col>
            </el-row>
          </div>
          <div class="form-list">
            <label for=""><span class="color-tab">*</span> 自我评价提醒：</label>
            <el-row :gutter="14">
              <el-col :span="12">
                <el-row class="width100">
                  <el-col :span="12">
                    <el-select
                      v-model="from.make"
                      placeholder="请选择"
                      style="flex: 100%"
                      @change="handleMake"
                    >
                      <el-option
                        v-for="(item, index) in makeOptions"
                        :key="index"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </el-col>
                  <el-col :span="12" class="plan-days">
                    <el-input-number
                      v-model="days.makeDays"
                      :controls="false"
                      :max="3650"
                      :min="0"
                      placeholder="请输入天数"
                      :precision="0"
                    >
                    </el-input-number>
                    <p class="days">天</p>
                  </el-col>
                </el-row>
              </el-col>
              <el-col :span="24" class="assess-info">用于设置提醒被考核人，进行考核自我评价的时间</el-col>
            </el-row>
          </div>
          <div class="form-list">
            <label for=""><span class="color-tab">*</span> 上级评价提醒：</label>
            <el-row :gutter="14">
              <el-col :span="12">
                <el-row class="width100">
                  <el-col :span="12">
                    <el-select
                      v-model="from.eval"
                      placeholder="请选择"
                      style="flex: 100%"
                    >
                      <el-option
                        v-for="(item, index) in evalOptions"
                        :key="index"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </el-col>
                  <el-col :span="12" class="plan-days">
                    <el-input-number
                      v-model="days.evalDays"
                      :controls="false"
                      :max="100"
                      :min="0"
                      placeholder="请输入天数"
                      :precision="0"
                    />
                    <p class="days">天</p>
                  </el-col>
                </el-row>
              </el-col>
              <el-col :span="24" class="assess-info"
                >用于设置提醒考核人，对直属下级考核完成情况进行上级评价打分的时间</el-col
              >
            </el-row>
          </div>
          <div class="form-list">
            <label for=""><span class="color-tab">*</span> 考核自动结束：</label>
            <el-row :gutter="14">
              <el-col :span="12">
                <el-row class="width100">
                  <el-col :span="12">
                    <el-input
                      v-model="from.verify"
                      :disabled="true"
                      placeholder="上级评价结束后"
                    />
                  </el-col>
                  <el-col :span="12" class="plan-days">
                    <el-input-number
                      v-model="days.verifyDays"
                      :controls="false"
                      :max="100"
                      :min="0"
                      placeholder="请输入天数"
                      :precision="0"
                    />
                    <p class="days">天</p>
                  </el-col>
                </el-row>
              </el-col>
              <el-col :span="24" class="assess-info">用于设置上级评价完成后，考核流转为【结束】状态的时间</el-col>
            </el-row>
          </div>
          <div class="form-list" style="align-items: normal">
            <label for=""><span class="color-tab">*</span> 被考核人：</label>
            <el-row :gutter="14">
              <el-col :span="12" class="resource-check">
                <el-radio-group v-model="from.resource">
                  <el-radio label="1" value="1">按部门添加</el-radio>
                  <el-radio label="0" value="0">按人员添加</el-radio>
                </el-radio-group>
              </el-col>

              <el-col :span="24">
                <div class="assess-info">
                  按部门添加用于本部门所有人员均需参加考核的情况；按人员添加用于特殊个例人员需要参加考核的情况
                </div>
              </el-col>
            </el-row>
          </div>
          <div class="form-list">
            <label for=""
              ><span class="color-tab">*</span> {{ from.resource == 0 ? '被考核人员' : '被考核部门' }}：</label
            >
            <el-col :span="22" class="form-list-con" style="flex: 0 0 40%; max-width: 40%">
              <select-member
                v-if="from.resource == 0"
                :value="departmentObj.userList || []"
                placeholder="未选择被考核人时默认全员参与"
                @getSelectList="getSelectList"
                style="width: 100%"
              ></select-member>
              <select-department
                v-if="from.resource == 1"
                :value="departmentObj.frames || []"
                placeholder="未选择被考核人时默认全员参与"
                @changeMastart="changeMastart"
                style="width: 100%"
              ></select-department>
            </el-col>
          </div>
          <div class="form-list" style="margin-top: 10px">
            <label for=""></label>
            <el-button type="primary" link @click="handlePreserve(2)">保存查看被考核人员详情</el-button>
          </div>
        </div>
      </div>
      <div class="cr-bottom-button">
        <el-button :loading="loading" type="primary" @click="handlePreserve(1)">保存</el-button>
      </div>
    </div>

    <assess-box ref="assessBox"></assess-box>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import Common from '@/components/user/accessCommon.ts'
import { assessPlanGetApi, assessPlanPutApi } from '@/api/enterprise.ts'
export default {
  name: 'Plan',
  components: {
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department')),
    assessBox: defineAsyncComponent(() => import('./assessBox'))
  },
  data() {
    return {
      periodOptions: Common.periodOptions,
      planIndex: '2',
      from: {
        make: 'before',
        eval: 'before',
        verify: '',
        status: 0,
        remindDays: 1,
        remindMonth: 1,
        resource: '0'
      },
      months: 12,
      days: {
        makeDays: 3,
        evalDays: 3,
        verifyDays: 3
      },
      afterMaxDay: 3650,
      makeOptions: [
        { value: 'start', label: '考核开始后' },
        { value: 'before', label: '考核结束前' },
        { value: 'after', label: '考核结束后' }
      ],
      evalOptions: [
        { value: 'start', label: '考核开始后' },
        { value: 'before', label: '考核结束前' },
        { value: 'after', label: '考核结束后' }
      ],
      remindDaysData: [
        { value: 1, label: '星期一' },
        { value: 2, label: '星期二' },
        { value: 3, label: '星期三' },
        { value: 4, label: '星期四' },
        { value: 5, label: '星期五' },
        { value: 6, label: '星期六' },
        { value: 7, label: '星期七' }
      ],

      departmentObj: {
        userList: [], // 选择成员
        frames: []
      },

      heightInputRole: 36,
      id: null,
      loading: false
    }
  },
  created() {
    this.getTableData()
  },
  methods: {
    async getTableData() {
      const result = await assessPlanGetApi(this.planIndex)
      const data = result.data
      this.from.status = data.status
      this.from.make = data.make_type
      this.days.makeDays = data.make_day
      this.from.eval = data.eval_type
      this.days.evalDays = data.eval_day
      this.days.verifyDays = data.verify_day
      this.id = data.id
      this.from.resource = String(data.assess_type)
      this.from.remindDays = data.create_time
      this.from.remindMonth = data.create_month
      if (data.assess_type === 1) {
        // 接口未配置被考核部门时返回 null，需兜底为数组
        this.departmentObj.frames = data.test_frame || []
        this.departmentObj.userList = []
      } else {
        let list = []
        const testUsers = data.test_user || data.test || []
        testUsers.map((item) => {
          let card = {
            value: item.id,
            name: item.name,
            avatar: item.avatar
          }
          list.push(card)
        })
        this.departmentObj.userList = list
        this.departmentObj.frames = []
      }
      this.handleMake(this.from.make)
    },
    handleClick(number) {
      this.planIndex = number.paneName
      if (this.planIndex == 3) {
        this.months = 12
      } else if (this.planIndex == 4) {
        this.months = 6
      } else if (this.planIndex == 5) {
        this.months = 3
      } else {
        this.months = 12
      }
      this.getTableData()
    },

    handleMake(e) {
      if (e === 'after') {
        if (this.planIndex == 1) {
          this.afterMaxDay = 7
        } else if (this.planIndex == 2) {
          this.afterMaxDay = 28
        } else if (this.planIndex == 3) {
          this.afterMaxDay = 365
        } else if (this.planIndex == 4) {
          this.afterMaxDay = 180
        } else if (this.planIndex == 5) {
          this.afterMaxDay = 90
        }
      } else {
        this.afterMaxDay = 3650
      }
    },

    // 选择部门弹窗
    changeMastart(data) {
      this.departmentObj.frames = data && data.length > 0 ? data : []
    },
    // 选择成员弹窗
    getSelectList(data) {
      this.departmentObj.userList = data || []
    },

    handlePreserve(type) {
      if (this.from.name == '') {
        this.$message.error('请填写名称')
      } else if (this.days.makeDays == '') {
        this.$message.error('请填写目标定制天数')
      } else if (this.days.evalDays == '') {
        this.$message.error('请填写上级评价天数')
      } else if (this.days.verifyDays == '') {
        this.$message.error('请填写上级审核天数')
      } else if (this.days.makeDays > this.afterMaxDay) {
        this.days.makeDays = this.afterMaxDay
        this.$message.error('目标制定时间超过了最大天数')
      } else {
        let check = []
        let frame = []
        if (this.from.resource == 0) {
          if (this.departmentObj.userList.length > 0) {
            this.departmentObj.userList.forEach((item) => {
              check.push(item.value)
            })
          }
        } else {
          if (this.departmentObj.frames.length > 0) {
            this.departmentObj.frames.forEach((value) => {
              frame.push(value.id)
            })
          }
        }
        const data = {
          make_type: this.from.make,
          make_day: this.days.makeDays,
          eval_type: this.from.eval,
          eval_day: this.days.evalDays,
          verify_day: this.days.verifyDays,
          status: this.from.status,
          assess_type: this.from.resource,
          create_time: this.from.remindDays,
          create_month: this.from.remindMonth
        }
        if (this.from.resource == 0) {
          data.test = check
        } else {
          data.test_frame = frame
        }
        this.loading = true
        assessPlanPutApi(this.id, data)
          .then((res) => {
            if (res.status == 200) {
              this.loading = false
              if (type !== 1) {
                this.$refs.assessBox.id = this.id
                this.$refs.assessBox.openBox()
              }
            }
          })
          .catch((error) => {
            this.loading = false
          })
      }
    },
    cardTag(type, index) {
      type == 1 ? this.departmentObj.userList.splice(index, 1) : this.departmentObj.frames.splice(index, 1)
    }
  }
}
</script>

<style lang="scss" scoped>
.box {
  height: calc(100vh - 200px);
  overflow-y: scroll;
}
.plan-title {
  margin: 0 -20px;
  border-bottom: 2px solid #f0f2f5;
  .p30 {
    padding: 0 30px;
  }
}
.cr-bottom-button {
  position: absolute;
  left: 0px;
  right: 0;
  bottom: -56px;
  width: 100%;
  border-radius: 0 0 8px 8px;
}
:deep(.el-scrollbar__wrap) {
  padding-right: 60px;
}
:deep(.el-tabs__item) {
  line-height: 24px;
}
.form-list {
  display: flex;
  margin-top: 20px;
  margin-right: 20px;

  // 只命中行标题：Element Plus 的 el-radio 根节点也是 label
  > label {
    margin-top: 8px;
    width: 20%;
    text-align: right;
    font-weight: normal;
    font-size: 13px;
  }
  :deep(.el-radio-group) {
    display: flex;
    min-width: 500px;
    .el-radio {
      margin-bottom: 0;
    }
  }
  :deep(.el-row) {
    width: 80%;
  }
  .resource-check {
    width: 120px;
    // 旧版 el-radio 同为 label，会继承行标题的 20% 宽度、右对齐与 8px 上边距，这里按原尺寸还原
    :deep(.el-radio) {
      width: 100px;
      height: 16px;
      margin-top: 8px;
      text-align: right;
    }
  }
  .resource-text {
    width: calc(100% - 160px);

    .assess-info {
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 13px;
      color: #909399;
      margin-top: 10px;
      line-height: 16px;
      white-space: nowrap;
    }
  }
  .assess-info {
    white-space: nowrap;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 13px;
    color: #909399;
    margin-top: 10px;
  }
  :deep(.el-select) {
    width: 100%;
  }
  .form-list-con {
    // Element Plus 的开关外层高 32px，旧版为 20px
    :deep(.el-switch) {
      height: 20px;
    }
    .plan-icon {
      color: #666666;
      padding: 0 10px;
    }
  }
  .plan-add {
    position: relative;
    width: calc(50%);
    margin-left: 0px;
    :deep(.el-input-number) {
      width: 100%;
    }
    .days {
      position: absolute;
      font-size: 13px;
      right: 20px;
      z-index: 2;
      top: 8px;
    }
  }
  .plan-days {
    position: relative;
    width: calc(50% - 14px);
    // Element Plus 的 el-col 用 flex-basis 定宽，需同步扣除左边距，否则会换行
    flex: 0 0 calc(50% - 14px);
    max-width: calc(50% - 14px);
    margin-left: 14px;
    :deep(.el-input-number) {
      width: 100%;
    }
    .days {
      position: absolute;
      font-size: 13px;
      right: 20px;
      z-index: 2;
      top: 8px;
    }
  }
  :deep(.el-input-number) {
    width: 100%;
  }
  :deep(.el-input__inner) {
    text-align: left;
    font-size: 13px;
    width: 100%;
  }
}
.width100 {
  width: 100% !important;
}
.flex-box {
  span {
    margin-right: 6px;
  }
  span:last-of-type {
    margin-left: 0;
  }
}
:deep(.el-radio) {
  margin-bottom: 14px;
  display: block;
  &:last-of-type {
    margin-bottom: 0;
  }
}
.plan-footer-one {
  background-color: #fff;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  color: #606266;
  display: inline-block;
  font-size: inherit;
  min-height: 32px;
  line-height: 32px;
  outline: none;
  padding: 0 15px;
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  width: 100%;
}
</style>
