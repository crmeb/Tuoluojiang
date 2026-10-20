<template>
  <div class="tab-style-editor">
    <el-radio-group v-model="optionModel.tabStyle">
      <el-radio value="trapezoid">梯形</el-radio>
      <el-radio value="rectangle">矩形</el-radio>
      <el-radio value="linear">线性</el-radio>
    </el-radio-group>
  </div>
</template>

<script>
export default {
  name: 'tab-style-editor',
  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  }
}
</script>
<style lang="scss" scoped>
.tab-style-editor {
  padding-left: 2px;
}
</style>
