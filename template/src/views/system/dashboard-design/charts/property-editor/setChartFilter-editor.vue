<template>
  <div>
    <div>
      <el-form-item label="过滤条件">
        <el-button @click="setCondition">{{ setConditionText }}</el-button>
        <!-- <div class="ml-a-span" @click="setCondition" v-else></div> -->
      </el-form-item>
      <el-drawer
        :append-to-body="true"
        title="条件设置"
        v-model="dialogIsShow"
        :close-on-click-modal="false"
        direction="rtl"
        class="condition_copyer"
        size="650px"
      >
        <condition-dialog
          v-if="dialogIsShow"
          :formArray="viewSearch"
          eventStr="event"
          :additionalBoolean="conditionConf.additional_search_boolean"
          @updateConditionDialog="updateConditionDialog"
          @saveCondition="conditionConfirm"
        />
      </el-drawer>
    </div>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useConditionDialogStore } from '@/stores/conditionDialog.ts'
import conditionDialog from '@/components/develop/conditionDialog'
import { viewSearchApi } from '@/api/develop.ts'
export default {
  emits: ['update:optionModel'],
  name: 'setChartFilter-editor',
  components: { conditionDialog },
  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  },
  computed: {
    ...mapStores(useConditionDialogStore)
  },
  data() {
    return {
      cutOption: {},
      conditionConf: {},
      viewSearch: [],
      dialogIsShow: false,
      setConditionText: ''
    }
  },
  watch: {
    optionModel: {
      handler(newVal, oldVal) {
        this.cutOption = JSON.parse(JSON.stringify(newVal))
        if (this.cutOption.setChartFilter.list && this.cutOption.setChartFilter.list.length > 0) {
          let filterArr = []
          this.cutOption.setChartFilter.list.map((item) => {
            filterArr.push(item)
          })
          let data = {
            list: filterArr,
            type: '',
            additional_search_boolean: this.cutOption.setChartFilter.additional_search_boolean || '0'
          }
          this.conditionDialogStore.setFieldOptions(data)
        } else {
          let data = {
            list: [],
            type: '',
            additional_search_boolean: '1'
          }
          this.conditionDialogStore.setFieldOptions(data)
        }
        this.getSetConditionText()

        // 图表-刷新过滤条件选项
        if (this.cutOption.dataEntity) {
          this.getvalue(this.cutOption.dataEntity)
        }
      },
      deep: true,
      immediate: true
    }
    // 'optionModel.dataEntity': {
    //   handler(newVal) {
    //     if (this.optionModel.name === this.cutOption.name) {
    //       this.cutOption.setChartFilter = {
    //         equation: '',
    //         items: []
    //       }
    //       this.getSetConditionText()
    //     }
    //   },
    //   immediate: true
    // }
  },
  mounted() {
    // this.cutOption = this.optionModel
    this.getSetConditionText()
  },
  methods: {
    // 获取过滤条件数据
    getvalue(id) {
      viewSearchApi(id).then((res) => {
        if (res.status === 200) {
          this.viewSearch = res.data
        }
      })
    },
    updateConditionDialog() {
      this.dialogIsShow = false
    },
    setCondition() {
      if (!this.optionModel.dataEntity) {
        this.$message.warning('请先选择图标数据实体')
        return
      }
      let actionFilter = this.initFilter({ ...this.cutOption.setChartFilter })
      this.conditionConf = JSON.parse(JSON.stringify(actionFilter))

      this.dialogIsShow = true
    },
    initFilter(filter) {
      let { equation } = filter
      if (!equation || equation === '') {
        filter.type = 1
        filter.equation = ''
      } else if (equation === '1') {
        filter.type = 2
        filter.equation = '1'
      } else {
        filter.type = 3
      }
      return filter
    },
    getSetConditionText() {
      let actionFilter = { ...this.cutOption.setChartFilter }
      let length = actionFilter && actionFilter.list ? actionFilter.list.length : 0
      this.setConditionText = length > 0 ? `已设置条件（${length}）` : '点击设置'
    },
    conditionConfirm(e) {
      this.cutOption.setChartFilter = Object.assign({}, e)
      this.dialogIsShow = false

      this.$emit('update:optionModel', this.cutOption)
    }
  }
}
</script>
<style lang="scss" scoped>
:deep(.condition_content) {
  padding-right: 24px;
}
</style>
