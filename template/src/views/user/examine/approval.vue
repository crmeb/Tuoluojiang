<template>
  <div class="divBox">
    <!-- 我审批的 -->
    <el-card class="employees-card-bottom">
      <div class="submission-header">
        <el-form :inline="true" class="from-s">
          <div class="flex-row flex-col">
            <div>
              <div class="submission-tab">
                <el-tabs v-model="examineActive">
                  <el-tab-pane
                    v-for="(item, index) in examineTabData"
                    :key="item.id"
                    :label="item.name"
                    :name="item.id"
                    lazy
                  ></el-tab-pane>
                </el-tabs>
              </div>
            </div>
          </div>
        </el-form>
      </div>

      <div class="splitLine"></div>
      <examine ref="examine" @get-content-value="getContentValue" :examineActive="examineActive"></examine>
    </el-card>
    <edit-examine ref="editExamine" @isOk="getTableData()"></edit-examine>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { approveConfigSearchApi } from '@/api/business.ts'
export default {
  name: 'Index',
  components: {
    examine: defineAsyncComponent(() => import('./components/examine')),
    editExamine: defineAsyncComponent(() => import('./components/editExamine'))
  },
  data() {
    return {
      examineActive: '1',
      where: {
        name: '',
        time: ''
      },
      typeData: [],
      examineTabData: [
        { name: '待审批', id: '1' },
        { name: '全部', id: '5' },
        { name: '已处理', id: '2' },
        { name: '抄送我的', id: '3' },
        { name: '已撤销', id: '4' }
      ]
    }
  },
  created() {
    this.getConfigSearch(1)
  },
  methods: {
    async getConfigSearch(id) {
      const result = await approveConfigSearchApi(id)
      this.typeData = result.data ? result.data : []
      this.typeData.unshift({ name: '全部', id: '' })
    },
    getTableData() {
      this.$refs.examine.getTableData()
    },

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

    getRadioValue(value, option) {
      let str = ''
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
</script>

<style lang="scss" scoped>
.splitLine {
  margin-bottom: 20px;
}
.submission-header {
  :deep(.el-row) {
    .icon-name {
      display: inline-block;
    }
    .header-right {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      text-align: right;
    }
  }
}
.submission-tab {
  :deep(.el-tabs__header) {
    margin-bottom: 0;
  }
  :deep(.el-tabs__nav-wrap::after) {
    height: 0;
  }
}
</style>
