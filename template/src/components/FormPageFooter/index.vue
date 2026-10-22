<!-- 表单页底部操作栏：默认「取消（内部返回） + 保存（emit save）」，
     可通过默认插槽自定义按钮（如「保存草稿 + 保存」） -->
<template>
  <div class="form-page-footer">
    <slot>
      <el-button size="small" @click="onCancel">取消</el-button>
      <el-button size="small" type="primary" :loading="loading" @click="onSave">保存</el-button>
    </slot>
  </div>
</template>

<script>
import { useRouter } from "vue-router/composables";

export default {
  name: "FormPageFooter",
  props: {
    // 保存按钮 loading 态
    loading: {
      type: Boolean,
      default: false
    }
  },
  setup(_, { emit }) {
    const router = useRouter();
    const onCancel = () => router.back();
    const onSave = () => emit("save");

    return { onCancel, onSave };
  }
};
</script>

<style lang="scss" scoped>
.form-page-footer {
  display: flex;
  justify-content: center;
  padding: 18px;
  border-top: 1px solid #eee;
}
</style>
