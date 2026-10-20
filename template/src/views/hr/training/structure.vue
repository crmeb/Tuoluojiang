<template>
  <div class="divBox">
    <div class="box-height">
      <ueditorFrom
        :border="true"
        :height="height"
        :training="true"
        :editor-border="false"
        ref="ueditorFrom"
        :placeholder="`支持上传组织架构图`"
        :content="content"
      />
      <div class="cr-bottom-button btn-shadow">
        <el-button :loading="loading" type="primary" @click="handleConfirm()">保存</el-button>
      </div>
    </div>
  </div>
</template>
<script>
import ueditorFrom from '@/components/form-common/oa-wangeditor'
import { employeeTrainApi, getEmployeeTrainApi } from '@/api/config.ts'
export default {
  name: '',
  components: { ueditorFrom },
  props: {},
  data() {
    return {
      height: 'calc(100vh - 250px)',
      content: '',
      loading: false
    }
  },

  mounted() {
    this.getConent()
  },
  methods: {
    async getConent() {
      let type = 'organization_chart'
      const result = await getEmployeeTrainApi(type)
      this.content = result.data.content
    },
    handleConfirm() {
      this.content = this.$refs.ueditorFrom.getValue()
      if (this.content == '') {
        return this.$message.error('内容不能为空')
      }
      this.loading = true
      let type = 'organization_chart'
      let data = {
        content: this.content
      }
      employeeTrainApi(type, data)
        .then((res) => {
          this.loading = false
        })
        .catch((err) => {
          this.loading = false
        })
    }
  }
}
</script>
<style scoped lang="scss">
.divBox {
  padding: 0;
  position: relative;
  margin-left: 14px;
  border-radius: 8px;
  overflow: hidden;
}

.cr-bottom-button {
  position: absolute;
  left: 0px;
  right: 0;
  bottom: 0;
  width: calc(100% + 0px);
  border-radius: 0 0 8px 8px;
}

:deep(.w-e-toolbar) {
  border-radius: 8px 8px 0 0;
  border-bottom-color: #eee;
  background-color: transparent;
}
:deep(.main) {
  background-color: transparent;
}
:deep(.w-e-text-container) {
  background-color: transparent;
}
</style>
