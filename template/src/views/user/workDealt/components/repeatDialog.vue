<template>
  <div>
    <el-dialog
      :title="repeatData.title"
      v-model="dialogVisible"
      :width="repeatData.width"
      :before-close="handleClose"
    >
      <div class="body mt15">
        <el-form ref="form" :model="rules" :rules="rule" label-width="80px">
          <el-form-item :label="'重复类型' + ':'">
            <el-select
              v-model="rules.repeat"
              placeholder="请选择提醒方式"
              @change="handleRepeat"
            >
              <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
          <el-form-item prop="rate" :label="'频率' + ':'">
            <el-input-number
              v-model="rules.rate"
              :controls="false"
              :min="1"
              placeholder="请输入提醒频率"
            />
            <span class="day-title">{{ dayTitle }}</span>
          </el-form-item>
          <el-form-item prop="weekDays" v-if="rules.repeat === 1" :label="'每周' + ':'">
            <el-checkbox-group v-model="rules.weekDays">
              <el-checkbox-button v-for="item in week" :key="item.value" :label="item.value" :value="item.value">{{
                item.label
              }}</el-checkbox-button>
            </el-checkbox-group>
          </el-form-item>
          <el-form-item prop="monthDays" v-if="rules.repeat === 2" :label="'每月' + ':'" class="month">
            <el-checkbox-group v-model="rules.monthDays">
              <el-checkbox-button
                v-for="item in 31"
                :key="item"
                :label="item.toString()" :value="item.toString()"
                >{{ item > 9 ? item : '0' + item }}</el-checkbox-button
              >
            </el-checkbox-group>
          </el-form-item>
          <el-form-item :label="'结束日期' + ':'">
            <el-date-picker
              v-model="rules.time"
              type="datetime"
              prefix-icon="el-icon-date"
              :clearable="true"
              placeholder="永不结束"
            />
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer text-center">
          <el-button @click="handleClose">取消</el-button>
          <el-button type="primary" @click="handleConfirm">确定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import dayjs from 'dayjs'
export default {
  emits: ['handleRepeatData'],
  name: 'RepeatDialog',
  components: {},
  props: {
    repeatData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    const checkWeek = (rule, value, callback) => {
      if (this.rules.repeat === 1 && value.length <= 0) {
        return callback(new Error('请选择重复星期'))
      } else {
        return callback()
      }
    }
    const checkMonth = (rule, value, callback) => {
      if (this.rules.repeat === 2 && value.length <= 0) {
        return callback(new Error('请选择重复天'))
      } else {
        return callback()
      }
    }
    return {
      dialogVisible: false,
      rules: {
        repeat: 0,
        rate: 1,
        end_time: '',
        weekDays: [],
        monthDays: [],
        close: false,
        time: ''
      },
      rule: {
        rate: [{ required: true, message: '请输入提醒频率', trigger: 'blur' }],
        weekDays: [{ required: false, validator: checkWeek, trigger: 'change' }],
        monthDays: [{ required: false, validator: checkMonth, trigger: 'change' }]
      },
      options: [
        { value: 0, label: '按天重复' },
        { value: 1, label: '按周重复' },
        { value: 2, label: '按月重复' },
        { value: 3, label: '按年重复' }
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
      dayTitle: '天',
      option: [
        { value: '', label: '永不结束' },
        { value: 1, label: '选择日期' }
      ]
    }
  },
  watch: {
    repeatData: {
      handler(nVal) {
        if (nVal.edit) {
          this.rules.repeat = nVal.data.period
          this.rules.rate = nVal.data.rate
          if (nVal.data.period === 1) {
            this.rules.weekDays = nVal.data.days
          } else if (nVal.data.period === 2) {
            this.rules.monthDays = nVal.data.days
          }
          this.handleRepeat(nVal.data.period)
          this.rules.time = nVal.data.end_time.indexOf('0000-00-00') > -1 ? '' : nVal.data.end_time
        }
      },
      deep: true
    },
  },
  methods: {
    handleClose() {
      this.dialogVisible = false
      this.rules.close = true
      this.confirmData()
    },
    confirmData() {
      this.$emit('handleRepeatData', this.rules)
    },
    handleRepeat(e) {
      if (e === 0) {
        this.dayTitle = '天'
      } else if (e === 1) {
        this.dayTitle = '周'
      } else if (e === 2) {
        this.dayTitle = '月'
      } else if (e === 3) {
        this.dayTitle = '年'
      } else {
        this.dayTitle = '天'
      }
    },
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          if (this.rules.time) {
            this.rules.end_time = dayjs(this.rules.time).format('YYYY-MM-DD HH:mm:ss')
          } else {
            this.rules.end_time = ''
          }
          this.rules.close = false
          this.dialogVisible = false
          this.confirmData()
        }
      })
    }
  }
}
</script>

<style scoped lang="scss">
.body {
  :deep(.el-select),
  :deep(.el-input) {
    width: 100%;
  }
  :deep(.el-input__inner) {
    text-align: left;
  }
  .day-title {
    padding-left: 10px;
  }
  .month {
    :deep(.el-checkbox-button__inner) {
      padding: 9px 14px;
    }
    :deep(.el-checkbox-button:nth-of-type(9n + 1) .el-checkbox-button__inner) {
      border-left: 1px solid #dcdfe6;
    }
  }
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
}
</style>
