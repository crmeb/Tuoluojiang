<template>
  <div class="divBox">
    <el-drawer
      :title="edit ? '编辑日程' : '新增日程'"
      v-model="drawer"
      :close-on-click-modal="false"
      size="600px"
      :before-close="handleClose"
    >
      <div class="box" ref="scrollRef">
        <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="100px" class="demo-ruleForm">
          <el-form-item label="待办标题：" prop="title">
            <el-input v-model="ruleForm.title" placeholder="请输入待办标题"></el-input>
          </el-form-item>

          <el-form-item label="参与人：" prop="member" v-if="ruleForm.cid !== 5" style="margin-bottom: 15px">
            <!-- 选择成员 -->
            <select-member :value="superiorUser" :is-avatar="true" @getSelectList="getSelectList" style="width: 100%">
            </select-member>
          </el-form-item>
          <!-- 开始时间 -->
          <el-form-item label="开始时间：" prop="start_time">
            <div class="start-time">
              <el-date-picker
                :style="{ width: ruleForm.all_day ? '85%' : '190px' }"
                v-model="startDate"
                type="date"
                :clearable="false"
                placeholder="选择日期"
                format="YYYY-MM-DD"
                @change="durationTypeFn"
                :validate-event="false"
              >
              </el-date-picker>
              <el-time-picker
                v-if="!ruleForm.all_day"
                style="width: 190px"
                :clearable="false"
                v-model="startTime"
                placeholder="选择时间"
                value-format="HH:mm:ss"
                @change="durationTypeFn"
                :validate-event="false"
              >
              </el-time-picker>
              <el-checkbox v-model="ruleForm.all_day" @change="allDayFn">全天</el-checkbox>
            </div>
          </el-form-item>

          <el-form-item label="时长：" prop="" v-if="!ruleForm.all_day && !edit">
            <el-select v-model="durationType" placeholder="请选择" @change="durationTypeFn">
              <el-option v-for="item in durationList" :key="item.value" :label="item.label" :value="item.value">
              </el-option>
            </el-select>
          </el-form-item>

          <!-- 结束时间 -->
          <el-form-item label="结束时间：" prop="end_time" v-if="durationType == 5 || ruleForm.all_day">
            <div class="end-time">
              <el-date-picker
                :style="{ width: ruleForm.all_day ? '85%' : '190px' }"
                v-model="endDate"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                :clearable="false"
                placeholder="选择日期"
                :validate-event="false"
              >
              </el-date-picker>
              <el-time-picker
                v-if="!ruleForm.all_day"
                v-model="endTime"
                value-format="HH:mm:ss"
                style="width: 190px"
                :clearable="false"
                placeholder="选择时间"
                :validate-event="false"
              >
              </el-time-picker>
              <div class="addWidth"></div>
            </div>
          </el-form-item>

          <el-form-item label="提醒时间：" prop="remind">
            <el-select v-model="ruleForm.remind" placeholder="请选择">
              <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"> </el-option>
            </el-select>
          </el-form-item>
          <el-form-item label="重复：" prop="period">
            <el-select v-model="ruleForm.period" @change="daysChange" placeholder="请选择">
              <el-option v-for="item in repeatOptions" :key="item.value" :label="item.label" :value="item.value">
              </el-option>
            </el-select>
          </el-form-item>

          <el-form-item prop="rate" label="频率:" v-if="ruleForm.period == 1 || ruleForm.period == 4">
            <el-input-number
              v-model="ruleForm.rate"
              :controls="false"
              :min="1"
              placeholder="请输入提醒频率"
            />
            <span>{{ ruleForm.period == 1 ? '天' : '年' }}</span>
          </el-form-item>
          <el-form-item prop="days" label="每周:" v-if="ruleForm.period == 2">
            <el-checkbox-group v-model="ruleForm.days">
              <el-checkbox-button v-for="item in week" :key="item.value" :label="item.value" :value="item.value">{{
                item.label
              }}</el-checkbox-button>
            </el-checkbox-group>
          </el-form-item>
          <el-form-item prop="days" label="每月:" v-if="ruleForm.period == 3" class="month">
            <el-checkbox-group v-model="ruleForm.days">
              <el-checkbox-button
                v-for="item in 31"
                :key="item"
                :label="item.toString()" :value="item.toString()"
                >{{ item > 9 ? item : '0' + item }}</el-checkbox-button
              >
            </el-checkbox-group>
          </el-form-item>
          <el-form-item label="重复截至日期:" v-if="ruleForm.period != 0">
            <el-date-picker
              style="width: 100%"
              v-model="ruleForm.fail_time"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              prefix-icon="el-icon-date"
              :clearable="true"
              placeholder="永不结束"
            />
          </el-form-item>
          <el-form-item label="日程类型：" prop="cid">
            <div v-if="ruleForm.type && ruleForm.cid < 7 && ruleForm.cid != 1">{{ cidName }}</div>
            <div class="start-time" v-else>
              <el-select
                v-model="ruleForm.cid"
                placeholder="请选择"
                @change="cidChangeFn"
                :style="{ color: fontColor }"
              >
                <el-option
                  v-for="item in tableData"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                  :style="{ color: item.color }"
                ></el-option>
              </el-select>
              <!--              <div class="colorBox" :style="{ fontColor }"></div>-->
              <!-- <el-color-picker v-model="color1" :disabled="true"></el-color-picker> -->
            </div>
            <span class="prompt" v-if="!edit"> 客户跟进、续费提醒、回款提醒、汇报待办，请去对应业务处进行添加</span>
          </el-form-item>

          <component
            v-if="drawer"
            is="ueditorFrom"
            :border="true"
            ref="ueditorFrom"
            :height="`400px`"
            :type="`simple`"
            :content="ruleForm.content"
            @input="ueditorEdit"
          />
        </el-form>
        <div class="button from-foot-btn fix btn-shadow">
          <el-button @click="handleClose">取消</el-button>
          <el-button type="primary" :loading="loading" @click="submitForm">保存</el-button>
        </div>
      </div>
    </el-drawer>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { scheduleStoreApi, scheduleTypesApi, scheduleEditApi, scheduleInfoApi } from '@/api/user.ts'
import { getStorageJson } from '@/utils/storage.ts'
export default {
  emits: ['getList'],
  name: '',
  components: {
    ueditorFrom: defineAsyncComponent(() => import('@/components/form-common/oa-wangeditor')),
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member'))
  },
  props: ['leftTime'],
  data() {
    const checkMember = (rule, value, callback) => {
      if (this.superiorUser.length == 0) {
        return callback(new Error('请选择参与人'))
      } else {
        return callback()
      }
    }
    const checkDays = (rule, value, callback) => {
      if (this.ruleForm.days.length == 0) {
        return callback(new Error('请选择重复日期'))
      } else {
        return callback()
      }
    }

    return {
      edit: false,
      title: '选择成员',
      loading: false,
      cidName: '',
      superiorUser: [], // 参与人
      id: '', // 日程id
      drawer: false,
      startDate: '',
      startTime: '',
      endDate: '',
      endTime: '',
      fontColor: '#1890FF',
      tableData: [],
      durationType: 2,
      durationList: [
        { value: 1, label: '30分钟' },
        { value: 2, label: '1小时' },
        { value: 3, label: '2小时' },
        { value: 4, label: '3小时' },
        { value: 5, label: '自定义结束时间' }
      ],
      week: [
        { value: '1', label: '星期一' },
        { value: '2', label: '星期二' },
        { value: '3', label: '星期三' },
        { value: '4', label: '星期四' },
        { value: '5', label: '星期五' },
        { value: '6', label: '星期六' },
        { value: '7', label: '星期天' }
      ],

      ruleForm: {
        title: '',
        member: [],
        content: '',
        remind: 1,
        period: 0,
        all_day: false,
        cid: 1,
        rate: 1, // 重复频率
        days: [],
        start_time: '',
        end_time: '',
        fail_time: '',
        type: '', // 编辑
        start: '',
        end: ''
      },
      rules: {
        title: [
          {
            required: true,
            message: '请输入待办标题',
            trigger: 'change'
          }
        ],
        member: [{ required: true, validator: checkMember, trigger: ['blur', 'change'] }],
        days: [
          {
            required: true,
            validator: checkDays,
            trigger: 'blur'
          }
        ],

        remind: [
          {
            required: true,
            message: '请选择提醒时间',
            trigger: 'blur'
          }
        ],

        period: [
          {
            required: true,
            message: '请选择重复频率',
            trigger: 'blur'
          }
        ],
        start_time: [
          {
            required: true,
            message: '请选择开始时间',
            trigger: 'blur'
          }
        ],
        end_time: [
          {
            required: true,
            message: '请选择结束时间',
            trigger: 'blur'
          }
        ],
        cid: [
          {
            required: true,
            message: '请选择日程类型',
            trigger: 'change'
          }
        ],
        rate: [
          {
            required: true,
            message: '请输入频率',
            trigger: 'blur'
          }
        ]
      },
      repeatOptions: [
        {
          value: 0,
          label: '不重复'
        },
        {
          value: 1,
          label: '按天重复'
        },
        {
          value: 2,
          label: '按周重复'
        },
        {
          value: 3,
          label: '按月重复'
        },
        {
          value: 4,
          label: '按年重复'
        }
      ],
      options: [
        {
          value: -1,
          label: '不提醒'
        },
        {
          value: 0,
          label: '任务开始时'
        },
        {
          value: 1,
          label: '提前5分钟'
        },
        {
          value: 2,
          label: '提前15分钟'
        },
        {
          value: 3,
          label: '提前30分钟'
        },
        {
          value: 4,
          label: '提前1小时'
        },
        {
          value: 5,
          label: '提前2小时'
        },
        {
          value: 6,
          label: '提前1天'
        },
        {
          value: 7,
          label: '提前2天'
        },
        {
          value: 8,
          label: '提前1周'
        }
      ]
    }
  },

  methods: {
    formatTime() {
      const currentDate = dayjs().format('YYYY-MM-DD ')
      const baseTime = this.leftTime ? dayjs(this.leftTime) : dayjs()

      this.startDate = baseTime.format('YYYY-MM-DD ') || currentDate
      this.endDate = baseTime.format('YYYY-MM-DD ')

      this.startTime = dayjs().format('HH:mm:ss')
      this.endTime = dayjs().add(1, 'hours').format('HH:mm:ss')
    },
    durationTypeFn() {
      const startTime = dayjs(this.startDate).format('YYYY-MM-DD ') + this.startTime
      const durationMap = {
        1: 30,
        2: 60,
        3: 120,
        4: 180
      }

      if (durationMap.hasOwnProperty(this.durationType)) {
        this.ruleForm.end_time = dayjs(startTime)
          .add(durationMap[this.durationType], 'minutes')
          .format('YYYY-MM-DD HH:mm:ss')
      } else if (this.durationType === 5) {
        this.ruleForm.end_time = dayjs(this.endDate + ' ' + this.endTime).format('YYYY-MM-DD HH:mm:ss')
      }
    },

    // 提交日程表单
    submitForm() {
      this.durationTypeFn()
      this.ruleForm.member = []

      // 处理全天日程
      if (this.ruleForm.all_day) {
        this.ruleForm.all_day = 1
        this.ruleForm.start_time = dayjs(this.startDate).format('YYYY-MM-DD ') + '00:00:00'
        this.ruleForm.end_time = dayjs(this.endDate).format('YYYY-MM-DD ') + '23:59:59'
      } else {
        this.ruleForm.all_day = 0
        if (this.startDate && this.startTime) {
          this.ruleForm.start_time = dayjs(this.startDate).format('YYYY-MM-DD ') + this.startTime
        }
        if (this.edit && this.endDate && this.endTime) {
          this.ruleForm.end_time = dayjs(this.endDate).format('YYYY-MM-DD ') + this.endTime
        }
      }

      // 日期验证
      if (this.isDateInvalid(this.ruleForm.start_time, this.ruleForm.end_time)) {
        return this.$message.error('结束日期不能小于开始日期')
      }
      if (this.ruleForm.fail_time && this.isDateInvalid(this.ruleForm.start_time, this.ruleForm.fail_time)) {
        return this.$message.error('重复截至日期不能小于开始日期')
      }

      // 处理参与人
      this.superiorUser.forEach((item) => this.ruleForm.member.push(item.value))

      // 表单验证
      this.$refs.ruleForm.validate((valid) => {
        if (valid) {
          this.ruleForm.type ? this.editFn() : this.addFn()
        }
      })
    },

    isDateInvalid(start, end) {
      return Date.parse(start) > Date.parse(end)
    },

    daysChange(e) {
      this.ruleForm.days = []
    },

    // 编辑
    async editFn() {
      this.loading = true
      const result = await scheduleEditApi(this.id, this.ruleForm)
      if (result.status == 200) {
        await this.$emit('getList')
        await this.handleClose()
      }
      this.loading = false
    },

    // 新增
    async addFn() {
      this.loading = true
      const result = await scheduleStoreApi(this.ruleForm)
      if (result.status == 200) {
        await this.$emit('getList')
        await this.handleClose()
      }
      this.loading = false
    },
    openBox(data) {
      this.ruleForm.days = []
      this.getTypes()

      if (data) {
        if (data.edit) {
          this.edit = true
          this.durationType = 5
          this.id = data.id
          let info = {
            start_time: data.date.start,
            end_time: data.date.end
          }
          scheduleInfoApi(data.id, info).then((res) => {
            if (res.data.all_day == 1) {
              res.data.all_day = true
            } else {
              res.data.all_day = false
            }
            this.ruleForm.type = data.type
            this.ruleForm.period = res.data.period
            this.ruleForm.start = data.date.start
            this.ruleForm.end = data.date.end
            this.ruleForm.remind = res.data.remindInfo.ident
            this.ruleForm.title = res.data.title
            this.ruleForm.cid = res.data.cid
            this.ruleForm.all_day = res.data.all_day
            this.ruleForm.rate = res.data.rate

            // 非重复日程接口返回 days 为 null，需兜底避免中断后续赋值
            ;(res.data.days || []).map((item) => {
              this.ruleForm.days.push(item + '')
            })

            if (this.ruleForm.all_day) {
              if (data.type == 2) {
                this.endDate = dayjs(res.data.end_time).format('YYYY-MM-DD ')
                this.startDate = dayjs(res.data.start_time).format('YYYY-MM-DD ')
              } else {
                this.endDate = dayjs(data.date.end).format('YYYY-MM-DD ')
                this.startDate = dayjs(data.date.start).format('YYYY-MM-DD ')
              }
            } else {
              if (data.type == 2) {
                this.endTime = dayjs(res.data.end_time).format('HH:mm:ss')
                this.endDate = dayjs(res.data.end_time).format('YYYY-MM-DD ')
                this.startDate = dayjs(res.data.start_time).format('YYYY-MM-DD ')
                this.startTime = dayjs(res.data.start_time).format('HH:mm:ss')
              } else {
                this.endTime = dayjs(data.date.end).format('HH:mm:ss')
                this.endDate = dayjs(data.date.end).format('YYYY-MM-DD ')
                this.startDate = dayjs(data.date.start).format('YYYY-MM-DD ')
                this.startTime = dayjs(data.date.start).format('HH:mm:ss')
              }
            }
            this.ruleForm.member = res.data.member || []
            this.ruleForm.content = res.data.content
            this.ruleForm.fail_time = res.data.fail_time
            this.cidName = res.data.type.name
            this.fontColor = res.data.color
            this.superiorUser = []
            ;(res.data.user || []).map((item) => {
              item.value = item.id
              this.superiorUser.push(item)
            })
          })
        } else {
          this.getUser()
          this.startDate = data.startDate
          this.startTime = data.startTime
          this.endDate = data.endDate
          this.endTime = dayjs(data.time).add(1, 'hours').format('HH:mm:ss')
          this.durationTypeFn()
        }
      } else {
        this.formatTime()
        this.durationTypeFn()
        this.getUser()
      }
      setTimeout(() => {
        let top = this.$refs.scrollRef
        top.scrollTo({ top: 0 })
      }, 300)

      this.drawer = true
    },

    cidChangeFn(e) {
      let colorN = this.tableData.filter((item) => {
        if (item.id == e) {
          return item
        }
      })
      this.fontColor = colorN[0].color
    },

    getUser() {
      const userInfo = getStorageJson('userInfo')
      if (userInfo) {
        const { id, name, avatar } = userInfo
        const list = { id, value: id, name, avatar }
        this.superiorUser.push(list)
      }
    },

    allDayFn(e) {
      if (this.rules.all_day) {
      }
    },
    handleClose() {
      this.drawer = false
      this.$refs.ueditorFrom.clear()

      this.fontColor = '#1890FF'
      this.id = ''
      this.edit = false
      this.superiorUser = []
      this.ruleForm = {
        title: '',
        member: [],
        content: '',
        remind: 1,
        period: 0,
        all_day: false,
        cid: 1,
        rate: 1, // 重复频率
        days: [],
        start_time: '',
        end_time: '',
        fail_time: null,
        start: '',
        end: '',
        type: ''
      }
      this.ruleForm.content = ''
      this.durationType = 2
      this.formatTime()
      this.durationTypeFn()

      this.$refs.ruleForm.resetFields()
    },
    ueditorEdit(e) {
      this.ruleForm.content = e
    },

    async getTypes() {
      this.tableData = []
      const result = await scheduleTypesApi()
      result.data.map((item) => {
        if (item.is_public !== 0) {
          this.tableData.push(item)
        }
      })
    },

    // 获取人员回调
    getSelectList(data) {
      this.superiorUser = data
    }
  }
}
</script>
<style scoped lang="scss">
.demo-ruleForm {
  margin-bottom: 50px;
}
.divBox {
  margin: 0;
  padding: 0;
}
.box {
  padding: 36px 20px;
  height: 100%;
  overflow: auto;
}
.prompt {
  font-size: 12px;
  color: #909399;
}
.colorBox {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  margin-left: 10px;
  border: 1px solid #dcdfe6;
}
.flex-user {
  display: flex;
  flex-wrap: wrap;
}

:deep(.el-checkbox-button__inner) {
  padding: 9px 14px;
}
:deep(.el-checkbox-button .el-checkbox-button__inner) {
  border: 1px solid #dcdfe6;
}

.user {
  padding: 0 15px;
  height: 32px;
  text-align: center;
  line-height: 32px;
  font-size: 12px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;
  border-radius: 6px;
  color: #909399;
  border: 1px solid #dcdfe6;
  margin-right: 10px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  position: relative;
  .img {
    display: block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 5px;
    object-fit: cover;
  }
  .iconcha {
    cursor: pointer;
    position: absolute;
    right: -3px;
    top: -16px;
    font-size: 14px;
    color: #c0c4cc;
  }
}
.addPeople {
  cursor: pointer;
  width: 100px;
  height: 32px;
  background-color: rgba(24, 144, 255, 0.08);
  font-size: 14px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;
  color: #1890ff;
  text-align: center;
  line-height: 32px;
  border-radius: 6px;
  .icontianjia {
    font-size: 12px;
    margin-right: 4px;
  }
}
.start-time {
  display: flex;
  justify-content: space-between;
}
.end-time {
  display: flex;
  justify-content: space-between;
  .addWidth {
    width: 52px;
  }
}
.el-select {
  width: 100%;
}
.boder {
  height: 444px;
  border-radius: 4px 4px 4px 4px;
  border: 1px solid #dcdfe6;
  margin-bottom: 50px;
}

.footer {
  padding-top: 80px;
  text-align: center;
}
:deep(.el-drawer__body) {
  padding-bottom: 0;
}
:deep(.el-drawer__header) {
  padding: 16px 24px 16px 24px;
}
</style>
