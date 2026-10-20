<template>
  <div>
    <el-form :inline="true" class="from-s">
      <div class="flex-row">
        <el-form-item class="select-bar">
          <el-date-picker
            v-model="timeVal"
            :clearable="false"
            end-placeholder="结束时间"
            v-bind="$pickerProps(pickerOptions)"
            placeholder="选择时间"
            range-separator="至"
            start-placeholder="开始时间"
            class="time"
            format="YYYY/MM/DD"
            type="daterange"
            value-format="YYYY/MM/DD"
            @change="onchangeTime"
          />
        </el-form-item>
        <el-form-item>
          <el-tooltip content="重置搜索条件" effect="dark" placement="top">
            <div class="reset" @click="reset"><i class="iconfont iconqingchu"></i></div>
          </el-tooltip>
        </el-form-item>
      </div>
    </el-form>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import pickerOptions from '@/libs/pickerOptions.ts'
export default {
  emits: ['confirmData'],
  name: 'FormBox',
  data() {
    return {
      timeVal: [
        dayjs().subtract(30, 'days').format('YYYY/MM/DD'),
        dayjs(new Date()).format('YYYY/MM/DD')
      ],
      pickerOptions,
      tableFrom: {
        time:
          dayjs().subtract(30, 'days').format('YYYY/MM/DD') +
          '-' +
          dayjs(new Date()).format('YYYY/MM/DD'),
        type: ''
      }
    }
  },
  methods: {
    selectType() {
      this.confirmData()
    },
    reset() {
      this.timeVal = [
        dayjs().subtract(30, 'days').format('YYYY/MM/DD'),
        dayjs(new Date()).format('YYYY/MM/DD')
      ]
      ;(this.tableFrom.time =
        dayjs().subtract(30, 'days').format('YYYY/MM/DD') + '-' + dayjs(new Date()).format('YYYY/MM/DD')),
        this.confirmData()
    },
    // 选择时间区域
    onchangeTime(e) {
      if (e == null) {
        this.tableFrom.time = ''
        this.confirmData()
      } else {
        this.timeVal = e
        this.tableFrom.time = this.timeVal[0] + '-' + this.timeVal[1]
        this.confirmData()
      }
    },
    confirmData() {
      this.$emit('confirmData', this.tableFrom)
    }
  }
}
</script>

<style lang="scss" scoped>
.el-form-item {
  margin-bottom: 0;
}
</style>
