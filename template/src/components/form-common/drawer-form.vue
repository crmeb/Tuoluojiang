<!-- @FileDescription: 公共-全局-新增/编辑表单侧滑弹窗页面 -->
<template>
  <div class="oa-dialog">
    <el-drawer
      :title="fromData.title"
      v-model="show"
      direction="rtl"
      :before-close="handleClose"
      :size="fromData.width"
      :close-on-click-modal="fromData.type=='slot'?true:false"
    >
    <div v-if="fromData.type == 'slot'">
        <slot></slot>
      </div>
      <div class="invite" v-else>
        <oaForm
          ref="oaForm"
          :fromData="fromData"
          :formConfig="formConfig"
          :formDataInit="formDataInit"
          :formRules="formRules"
          @submit="submitOk"
          @selectChange="selectChange"
        ></oaForm>
      </div>
      <div class="button from-foot-btn fix btn-shadow" v-if="fromData.type != 'slot'">
        <el-button class="el-btn" @click="handleClose">取消</el-button>
        <el-button type="primary" @click="submit">保存</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import oaForm from './oa-form'

export default {
  name: 'DrawerForm',
  emits: ['submit', 'getRankList'],
  components: {
    oaForm
  },
  props: {
    // 弹窗样式
    fromData: {
      type: Object,
      default: () => {}
    },
    // 表单内容
    formConfig: {
      type: Array,
      default: () => []
    },
    // 表单绑定值
    formDataInit: {
      type: Object,
      default: () => {}
    },
    // 表单规则
    formRules: {
      type: Object,
      default: () => {}
    }
  },

  data() {
    return {
      show: false,
      loading: '',
      form: this.formDataInit
    }
  },

  methods: {
    openBox() {
      this.show = true
    },

    submitOk(form) {
      this.$emit('submit', form, this.fromData.type)
    },
    selectChange(val) {
      this.$emit('getRankList', val)
    },

    submit() {
      this.$refs.oaForm.submit()
    },

    handleClose() {
      this.show = false
      this.$refs.oaForm.closeFn()
    }
  }
}
</script>

<style lang="scss" scoped>
.invite {
  padding: 20px;
}
</style>
