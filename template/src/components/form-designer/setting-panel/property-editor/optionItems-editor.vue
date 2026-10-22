<template>
  <el-form-item label-width="0">
    <el-divider class="custom-divider-margin-top">
      {{ i18nt('designer.setting.optionsSetting') }}
    </el-divider>
    <!-- 选项类型 -->
    <!-- <div class="tab-header">
      <div
        v-for="(item, index) in tabList"
        :key="index"
        class="box"
        :class="{ active: activeIndex == item.value }"
        @click="activeFn(item.value)"
      >
        {{ item.label }}
      </div>
    </div> -->

    <!-- 关联字典 -->
    <!-- <el-select
      v-if="activeIndex == '0'"
      style="width: 100%"
      v-model="optionModel.dataDictId"
      filterable
      size="small"
      placeholder="请选择关联字典"
      @change="getDictData"
    >
      <el-option v-for="(v, index) in options" :key="v.id" :label="v.name" :value="v.id" />
    </el-select> -->

    <!-- 静态数据 -->
    <template v-if="activeIndex == '1'">
      <option-items-setting
        v-if="level1.includes(selectedWidget.type)"
        :designer="designer"
        :optionModel="optionModel"
        :optionItems="selectedWidget.options.optionItems"
        :selected-widget="selectedWidget"
      />
      <option-cascsder-setting
        v-if="level4.includes(selectedWidget.type)"
        :designer="designer"
        :optionModel="optionModel"
        :optionItems="selectedWidget.options.optionItems"
        :selected-widget="selectedWidget"
      />
    </template>
  </el-form-item>
</template>

<script>
import { getDictListApi, getDictTreeListApi } from '@/api/form'
import i18n from '@/utils/i18n'
import OptionItemsSetting from '@/components/form-designer/setting-panel/option-items-setting'
import OptionCascsderSetting from '@/components/form-designer/setting-panel/option-cascsder-setting'

export default {
  name: 'optionItems-editor',
  mixins: [i18n],
  data() {
    return {
      activeIndex: '1',
      value: '1',
      options: [],
      level1: ['radio', 'checkbox'],
      level4: ['cascader-radio', 'cascader', 'tag'],
      tabList: [
        { label: '静态数据', value: '1' },
        // { label: '数据字典', value: '0' }
      ]
    }
  },
  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  },
  components: {
    OptionItemsSetting,
    OptionCascsderSetting
  },
  mounted() {
    this.getDictList()

    // this.activeIndex = this.optionModel.data_type
    if (this.optionModel.data_type == 0) {
      // this.getDictData(this.optionModel.dataDictId)

    }
  

    if (!this.optionModel.customizeItems || this.optionModel.customizeItems.length == 0) {
      let list = []
      this.$set(this.optionModel, 'customizeItems', list)
    }
  },
  watch: {
    // optionModel: {
    //   handler(newVal, oldVal) {
    //     this.activeIndex = newVal.data_type
    //   },
    //   deep: true
    // }
  },
  methods: {
    getDictData(val) {
      this.options.map((item) => {
        if (item.id == val) {
          getDictTreeListApi({ Level: '', types: item.ident }).then((res) => {
            this.optionModel.optionItems = res.data
          })
        }
      })
    },

    activeFn(val) {
      this.optionModel.data_type = val
      this.activeIndex = val
      if (this.optionModel.data_type == 0) {
        this.getDictData(this.optionModel.dataDictId)
      }
      if (!this.optionModel.customizeItems || this.optionModel.customizeItems.length == 0) {
        this.$set(this.optionModel, 'customizeItems', [
          // { name: '选项1', value: '1', color: '#1890ff' },
          // { name: '选项2', value: '2', color: '#1890ff' },
          // { name: '选项3', value: '3', color: '#1890ff' }
        ])
      }
    },
    getDictList() {
      getDictListApi().then((res) => {
        this.options = res.data.list
      })
    }
  }
}
</script>

<style scoped lang="scss">
.tab-header {
  display: flex;
  border: 1px solid #dcdfe6;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  justify-content: space-between;
  margin-bottom: 20px;
  margin-top: 20px;

  .box {
    width: 100%;
    height: 32px;
    line-height: 32px;
    text-align: center;
    cursor: pointer;
    font-size: 13px;
    color: #606266;
    border-right: 1px solid #dcdfe6;
  }

  .active {
    background: #1890ff;
    color: #fff;
  }
}
</style>
@/utils/i18ns
