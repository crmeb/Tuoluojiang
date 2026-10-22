<template>
  <el-form-item label="选项类型">
    <el-radio-group v-model="optionModel.showType">
      <el-radio label="0">下拉样式</el-radio>
      <el-radio label="1">引用弹窗</el-radio>
    </el-radio-group>
  </el-form-item>
</template>

<script>
import i18n from '@/utils/i18n'

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
      this.$set(this.optionModel, 'showType', '0')
    }
  }
}
</script>

<style scoped></style>
@/utils/i18ns
