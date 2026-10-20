<template>
  <el-form-item label="选项类型">
    <el-radio-group v-model="optionModel.showType">
      <el-radio value="0">下拉样式</el-radio>
      <el-radio value="1">引用弹窗</el-radio>
    </el-radio-group>
  </el-form-item>
</template>

<script>
import i18n from '@/utils/i18n.ts'

export default {
  name: 'showType-editor',
  mixins: [i18n],
  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  },
  mounted() {
    if (!this.optionModel?.showType) {
      this.optionModel.showType = '0'
    }
  }
}
</script>

<style scoped></style>
