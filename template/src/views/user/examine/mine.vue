<template>
  <div class="divBox">
    <!-- 我提交的 -->
    <el-card class="employees-card-bottom">
      <submission ref="submission" :defaultStatus="defaultStatus" @get-content-value="getContentValue" />
    </el-card>
    <edit-examine v-if="isShow" ref="editExamine" @isOk="getTableData()" :typeNum="typeNum"></edit-examine>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import submission from './components/mySubmission'
export default {
  name: 'Index',
  components: {
    submission,
    editExamine: defineAsyncComponent(() => import('./components/editExamine'))
  },
  data() {
    return {
      isShow: false,
      typeNum: 1,
      // 由外部入口（如工作台「我申请的」）通过 query.status 指定的默认审批状态筛选值
      defaultStatus: this.getQueryStatus()
    }
  },
  methods: {
    /**
     * 解析路由 query 上的审批状态筛选值
     * 仅接受合法状态值（0 审核中 / 1 已通过 / 2 已拒绝 / -1 已撤销），其余一律不预置筛选
     * @returns {Number|String} 合法状态值，非法或未传时返回空字符串
     */
    getQueryStatus() {
      const rawStatus = this.$route.query.status
      if (rawStatus === undefined || rawStatus === '') return ''
      const status = Number(rawStatus)
      return [0, 1, 2, -1].includes(status) ? status : ''
    },

    /**
     * 获取表格数据
     * 调用 submission 组件的 getTableData 方法来获取表格数据
     */
    getTableData() {
      this.$refs.submission.getTableData()
    },
   
    /**
     * 获取内容值
     * @param {object} row - 表格行数据
     * @param {function} callback - 回调函数，用于返回内容值
     */
    getContentValue(row, callback) {
      let str = ''
      if (row.content.type === 'input' || row.content.type === 'inputNumber') {
        str = row.content.title + ' : ' + row.value
      } else if (row.content.type === 'radio' || row.content.type === 'select') {
        str = row.content.title + ' : ' + this.getRadioValue(row.value, row.content.options)
      } else if (row.content.type === 'timeFrom') {
        str =
          '开始时间 : ' +
          row.value.dateStart +
          ' 结束时间 : ' +
          row.value.dateEnd +
          ' ' +
          row.content.props.titleIpt +
          ' : ' +
          row.value.duration
      }
      callback(str)
    },

    /**
     * 获取单选框的值
     * @param {any} value - 单选框的值
     * @param {array} option - 单选框的选项数组
     */
    getRadioValue(value, option) {
      let str = ''
      if (option) {
        for (let i = 0; i < option.length; i++) {
          if (value == option[i].value) {
            str = option[i].label
            break
          }
        }
        return str
      }
    }
  }
}
</script>

