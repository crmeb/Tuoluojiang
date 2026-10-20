<template>
  <div class="timeFrom">
    <!-- 请假表单 -->

    <el-form-item :error="errors.dateStart" :required="showRequired" label="开始时间" :label-width="itemLabelWidth">
      <div class="time-row">
        <el-date-picker
          v-model="timeData.dateStart"
          :type="timeData.timeType === 'day' ? 'date' : 'datetime'"
          placeholder="选择日期"
          :format="timeData.timeType === 'day' ? 'YYYY/MM/DD' : 'YYYY/MM/DD HH:mm:ss'"
          :value-format="timeData.timeType === 'day' ? 'YYYY/MM/DD' : 'YYYY/MM/DD HH:mm:ss'"
          :clearable="false"
          @change="onchangeTimeStart"
        ></el-date-picker>

        <el-select
          v-if="timeData.timeType === 'day'"
          v-model="timeData.timeStart"
          class="ml10"
          placeholder="请选择"
          @blur="onChange"
          @change="onchangeSel(1)"
        >
          <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"></el-option>
        </el-select>
      </div>
    </el-form-item>
    <el-form-item :error="errors.dateEnd" :required="showRequired" label="结束时间" :label-width="itemLabelWidth">
      <div class="time-row">
        <el-date-picker
          v-model="timeData.dateEnd"
          :type="timeData.timeType === 'day' ? 'date' : 'datetime'"
          placeholder="选择日期"
          :format="timeData.timeType === 'day' ? 'YYYY/MM/DD' : 'YYYY/MM/DD HH:mm:ss'"
          :value-format="timeData.timeType === 'day' ? 'YYYY/MM/DD' : 'YYYY/MM/DD HH:mm:ss'"
          :clearable="false"
          @change="onchangeTimeEnd"
        ></el-date-picker>
        <el-select
          v-if="timeData.timeType === 'day'"
          v-model="timeData.timeEnd"
          @blur="onChange"
          placeholder="请选择"
          class="ml10"
          @change="onchangeSel(2)"
        >
          <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"></el-option>
        </el-select>
      </div>
    </el-form-item>
    <el-form-item :error="errors.duration" :required="showRequired" :label="title" :label-width="itemLabelWidth">
      <div class="time-row">
        <el-input v-model="timeData.duration" style="width: 220px" @change="changeDuration" placeholder="请输入时长">
          <template #suffix>
            <span class="el-input__icon">{{ timeData.timeType === 'day' ? '天' : '小时' }}</span>
          </template>
        </el-input>
      </div>
    </el-form-item>
  </div>
</template>

<script>
import { divTime } from '@/utils/index.ts'
export default {
  name: 'Index',
  emits: ['update:modelValue', 'change'],
  props: {
    formCreateInject: Object,
    modelValue: {
      type: Object,
      default: () => ({})
    },
    disabled: Boolean,
    timeType: {
      type: String,
      default: () => 'day'
    },
    time: {
      type: String,
      default: () => ''
    },
    titleIpt: {
      type: String,
      default: () => '时长'
    }
  },
  data() {
    return {
      timeNum: this.time,
      options: [
        {
          value: '1',
          label: '上午'
        },
        {
          value: '0',
          label: '下午'
        }
      ],
      // data() 早于 computed 初始化，这里不能用 this.value
      timeData: {
        dateStart: this.modelValue.dateStart,
        timeStart: this.modelValue.timeStart,
        dateEnd: this.modelValue.dateEnd,
        timeEnd: this.modelValue.timeEnd,
        duration: this.modelValue.duration,
        timeType: this.timeType
      },
      errors: {
        dateStart: '',
        dateEnd: '',
        duration: ''
      },
      showErrors: false,
      itemLabelWidth: 'auto',
      num: 0,
      title: this.titleIpt
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.syncOuterErrorVisibility()
    })
  },
  updated() {
    this.syncOuterErrorVisibility()
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
    showRequired() {
      return !!(this.formCreateInject && this.formCreateInject.rule && this.formCreateInject.rule.effect && this.formCreateInject.rule.effect.required)
    },
    outerValidateState() {
      return this.$parent && this.$parent.validateState
    }
  },
  watch: {
    value: {
      // deep：外部就地修改 value（如审批表单按假期类型回写 duration）时也要同步
      deep: true,
      handler(n) {
        // 必须复制一份：直接持有外部传入的 value 会与 form-create 的 rule.value 共用引用，
        // 之后组件内的就地修改会让 form-create 的变更检测失效，change 事件只在首次触发
        const next = { ...(n || {}) }
        if (next.timeType === undefined) next.timeType = this.timeType
        this.timeData = next
        if (this.isPristineValue(this.timeData) && this.outerValidateState !== 'error') {
          this.showErrors = false
        }
        this.updateErrors()
      }
    },
    timeType(n) {
      this.timeData.timeType = n
      this.updateErrors()
    },
    time(n) {
      this.timeNum = n
    },
    titleIpt(n) {
      this.title = n
      this.updateErrors()
    },
    outerValidateState(n) {
      if (n === 'error') {
        this.showErrors = true
        this.updateErrors()
      }
    }
  },
  methods: {
    createEmptyErrors() {
      return {
        dateStart: '',
        dateEnd: '',
        duration: ''
      }
    },
    isEmpty(value) {
      return value === '' || value === undefined || value === null
    },
    isPristineValue(value = {}) {
      return this.isEmpty(value.dateStart) && this.isEmpty(value.dateEnd) && this.isEmpty(value.timeStart) && this.isEmpty(value.timeEnd) && this.isEmpty(value.duration)
    },
    isFieldComplete(value) {
      return !this.isEmpty(value.dateStart) && !this.isEmpty(value.dateEnd) && !this.isEmpty(value.timeStart) && !this.isEmpty(value.timeEnd) && !this.isEmpty(value.duration)
    },
    getErrors(value = this.timeData) {
      const errors = this.createEmptyErrors()

      if (this.isEmpty(value.dateStart) || this.isEmpty(value.timeStart)) {
        errors.dateStart = '请选择开始时间'
      }
      if (this.isEmpty(value.dateEnd) || this.isEmpty(value.timeEnd)) {
        errors.dateEnd = '请选择结束时间'
      }
      if (this.isEmpty(value.duration)) {
        errors.duration = `请输入${this.title}`
      }

      return errors
    },
    updateErrors() {
      this.errors = this.showErrors ? this.getErrors() : this.createEmptyErrors()
    },
    syncOuterErrorVisibility() {
      const outerError = this.$el && this.$el.nextElementSibling
      if (outerError && outerError.classList && outerError.classList.contains('el-form-item__error')) {
        outerError.style.display = 'none'
      }
    },
    syncFieldState() {
      const value = { ...this.timeData }
      this.$emit('update:modelValue', value)
      this.$emit('change', value)
      this.errors = this.showErrors ? this.getErrors(value) : this.createEmptyErrors()

      if (!this.formCreateInject || !this.formCreateInject.api || !this.formCreateInject.rule) {
        return
      }

      this.$nextTick(() => {
        const field = this.formCreateInject.rule.field
        if (!this.isFieldComplete(value)) {
          this.syncOuterErrorVisibility()
          return
        }
        this.formCreateInject.api.clearValidateState(field)
        this.syncOuterErrorVisibility()
      })
    },
    onChange() {
      const time1 = Date.parse(new Date(this.timeData.dateStart))
      const time2 = Date.parse(new Date(this.timeData.dateEnd))
      if (time1 > time2) {
        return this.$message.error('结束时间不能小于开始时间')
      }
      if (this.timeData.timeType === 'day') {
        if (time2 == time1) {
          setTimeout(() => {
            if (this.timeData.timeStart === '0' && this.timeData.timeEnd === '1') {
              return this.$message.error('结束时间不能小于开始时间')
            }
          }, 200)
        }
        this.num = divTime(this.timeData.dateStart, this.timeData.dateEnd, 'day')
        if (time2 > time1) this.count()
        if (time2 === time1) this.count()
      } else {
        if (time2 > time1) this.timeData.duration = divTime(this.timeData.dateStart, this.timeData.dateEnd, 'time')
      }
    },
    count() {
      if (this.timeData.timeStart && this.timeData.timeEnd) {
        const len = parseFloat(this.timeData.timeStart) - parseFloat(this.timeData.timeEnd)
        if (len === 1) {
          this.timeData.duration = parseFloat(this.num) + 1
        } else if (len === 0) {
          this.timeData.duration = 0.5 + parseFloat(this.num)
        } else if (len === -1) {
          this.timeData.duration = parseFloat(this.num)
        }
        this.syncFieldState()
      }
    },
    onchangeTimeEnd(n) {
      this.timeData.dateEnd = n
      this.timeData.timeEnd = '1'
      this.onChange()
      this.syncFieldState()
    },
    onchangeTimeStart(n) {
      this.timeData.dateStart = n
      this.timeData.timeStart = '1'
      this.onChange()
      this.syncFieldState()
    },
    changeDuration() {
      this.syncFieldState()
    },
    onchangeSel(n) {
      this.count()
    }
  }
}
</script>

<style scoped lang="scss">
.el-form--label-top .el-form-item__label {
  float: none;
  display: inline-flex;
  justify-content: flex-start;
  padding: 0 0 10px 0;
}

.timeFrom {
  // 表单项内容区是 flex 容器，不设宽度会按内容收缩
  width: 100%;

  :deep(.el-date-editor),
  :deep(.el-date-editor--date) {
    width: 220px;
  }
}

// 日期与上午/下午同行：下拉框按线上 220px，空间不足时（如发起申请抽屉）收缩到剩余宽度
.time-row {
  display: flex;
  width: 100%;

  :deep(.el-date-editor) {
    flex-shrink: 0;
  }

  .el-select {
    flex: 0 1 220px;
    min-width: 0;
  }
}
</style>
