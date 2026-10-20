<!-- 表单页顶部：返回箭头 + 标题 -->
<template>
  <div class="form-page-header">
    <i class="el-icon-arrow-left" @click="onBack" />
    <span class="form-page-header__title">{{ title }}</span>
  </div>
</template>

<script>
import { useRouter } from "vue-router";

export default {
  name: "FormPageHeader",
  props: {
    title: {
      type: String,
      default: ""
    }
  },
  setup(_, { emit, listeners }) {
    const router = useRouter();
    // 页面监听了 back 事件时走自定义返回逻辑（如切回列表视图），否则默认路由返回
    const onBack = () => (listeners && listeners.back ? emit("back") : router.back());

    return { onBack };
  }
};
</script>

<style lang="scss" scoped>
.form-page-header {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 60px;
  padding: 0 20px;
  border-bottom: .5px solid #eee;

  .el-icon-arrow-left {
    font-size: 18px;
    color: #606266;
    cursor: pointer;
  }

  &__title {
    font-size: 18px;
    font-weight: 500;
    color: #303133;
  }
}
</style>
