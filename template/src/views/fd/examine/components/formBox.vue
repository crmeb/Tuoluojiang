<template>
  <div>
    <el-form :inline="true" class="from-s">
      <div class="flex-row">
        <el-form-item label="业务类型" class="select-bar">
          <el-select v-model="tableFrom.type" @change="handleConfirm">
            <el-option v-for="item in sexOptions" :key="item.value" :label="item.label" :value="item.value"></el-option>
          </el-select>
        </el-form-item>

        <el-form-item label="管理范围" class="select-bar">
          <manage-range ref="manageRange" @change="changeMastart"></manage-range>
        </el-form-item>
        <el-form-item label="审核状态" class="select-bar">
          <el-select v-model="tableFrom.status" @change="handleConfirm">
            <el-option
              v-for="(itemn, indexn) in fromStatus"
              :key="indexn"
              :value="itemn.val"
              :label="itemn.text"
            ></el-option>
          </el-select>
        </el-form-item>

        <el-form-item class="select-bar">
          <el-select
            class="sel"
            style="width: 100px"
            placeholder="时间类型"
            v-model="tableFrom.time_field"
          >
            <el-option label="付款日期" value="date"></el-option>
            <el-option label="申请日期" value="time"></el-option>
          </el-select>

          <el-date-picker
            class="time"
            v-model="paymentTimeVal"
            type="daterange"
            placeholder="选择时间"
            format="YYYY/MM/DD"
            value-format="YYYY/MM/DD"
            range-separator="至"
            start-placeholder="开始时间"
            end-placeholder="结束时间"
            clearable
            v-bind="$pickerProps(pickerOptions)"
            @change="establishTime"
          />
        </el-form-item>
        <el-form-item>
          <el-tooltip effect="dark" content="重置搜索条件" placement="top">
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
import manageRange from '@/components/form-common/select-manageRange'

export default {
  emits: ['confirmData'],
  name: 'FormBox',
  components: { manageRange },
  props: ['activeName'],
  data() {
    return {
      pickerOptions,
      fromStatus: [
        { text: '全部', val: '' },
        { text: '待审核', val: 0 },
        { text: '已通过', val: 1 },
        { text: '未通过', val: 2 }
      ],

      paymentTimeVal: [
        dayjs().startOf('months').format('YYYY/MM/DD'),
        dayjs().endOf('months').format('YYYY/MM/DD')
      ],

      tableFrom: {
        time_field: 'date',
        type: '',
        status: '',
        name: '',
        time:
          dayjs().startOf('months').format('YYYY/MM/DD') +
          '-' +
          dayjs().endOf('months').format('YYYY/MM/DD'),
        salesman_name: '',
        scope_frame: 'all'
      },
      sexOptions: [
        { label: '全部', value: '' },
        { label: '订单回款', value: 0 },
        { label: '订单续费', value: 1 },
        { label: '订单支出', value: 2 }
      ]
    }
  },
  methods: {

    changeStatus() {
      this.confirmData()
    },

    // 确认
    handleConfirm() {
      this.confirmData()
    },
    handType(e) {
      if (e === '') {
        this.tableFrom.type = ''
      }
      this.confirmData()
    },
    // 选择成员完成回调
    changeMastart(data) {
      this.tableFrom.scope_frame = data
      this.confirmData()
    },
    establishTime(e) {
      if (e == null) {
        this.tableFrom.time = ''
        this.confirmData()
      } else {
        this.establishTimeVal = e
        this.tableFrom.time = this.establishTimeVal[0] + '-' + this.establishTimeVal[1]
        this.confirmData()
      }
    },
    // 类型
    handStatus(e) {
      this.tableFrom.status = e
    },
    reset() {
      this.tableFrom = {
        time:
          dayjs().startOf('months').format('YYYY/MM/DD') +
          '-' +
          dayjs().endOf('months').format('YYYY/MM/DD'),
        type: '',
        status: '',
        search: '',
        select: 2,
        time_field: 'date',
        scope_frame: 'all'
      }
      this.$refs.manageRange.frame_id = 'all'
      this.paymentTimeVal = [
        dayjs().startOf('months').format('YYYY/MM/DD'),
        dayjs().endOf('months').format('YYYY/MM/DD')
      ]
      this.confirmData()
    },
    confirmData() {
      this.$emit('confirmData', this.tableFrom)
    }
  }
}
</script>

<style lang="scss" scoped></style>
