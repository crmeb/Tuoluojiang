<template>
  <div>
    <el-form-item label="文字提示">
      <el-switch v-model="optionModel.isShowTips"></el-switch>
    </el-form-item>
    <el-form-item label="提示内容" v-if="optionModel.isShowTips">
      <el-input v-model="optionModel.tips" type="textarea" :rows="3" placeholder="请输入提示内容"></el-input>
    </el-form-item>
  </div>
</template>

<script>
export default {
  name: 'showTips-editor',

  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  }
}
</script>

<style scoped></style>
