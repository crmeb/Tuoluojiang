<!-- 添加客户弹窗组件 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-list-liaison-dialog-drawer"
      :title="formData.title"
      v-model="drawer"
      :direction="direction"
      :modal="true"
      :append-to-body="true"
      :close-on-click-modal="false"
      :before-close="handleClose"
      :size="formData.width"
    >
      <oaForm
        :form-info="fromInfo"
        ref="oaForm"
        @handleClose="handleClose"
        :btnShow="false"
        @submitOk="submitOk"
      ></oaForm>
    </el-drawer>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { liaisonCreateApi, liaisonEditCreateApi } from '@/api/enterprise.ts'
import { clientLiaisonSaveApi as liaisonSaveApi, clientLiaisonEditApi as liaisonEditSaveApi } from '@/api/client.ts'
export default {
  emits: ['isLiaison'],
  name: 'liaisonDialog',
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    oaForm: defineAsyncComponent(() => import('@/components/customer/oaForm'))
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      fromInfo: [],
      row: {},
      customInfo: {},
      contractFromData: {},
      edit: ''
    }
  },

  methods: {
    // 新增联系人表单
    getData() {
      let data = {
        link_id: this.customInfo.id || ''
      }
      liaisonCreateApi(data).then((res) => {
        this.fromInfo = res.data
        if (this.customInfo.id) {
          this.fromInfo.forEach((item) => {
            item.data.forEach((val) => {
              if (val.key == 'eid') {
                val.value = this.customInfo.id || ''
              }
            })
          })
        }
      })
    },
    handleClose() {
      this.drawer = false
      this.$refs.oaForm.resetForm()
    },

    // 编辑联系人表单
    getEditData(id) {
      liaisonEditCreateApi(id).then((res) => {
        this.fromInfo = res.data.form
      })
    },

    // 提交成功
    submitOk(data) {
      if (this.edit === 'edit') {
        data.eid = this.customInfo.id
        liaisonEditSaveApi(this.row.id, data)
          .then((res) => {
            if (res.status == 200) {
              this.drawer = false
              this.$emit('isLiaison')
              this.$refs.oaForm.resetForm()
            } else {
              this.$refs.oaForm.saveLoading = false
            }
          })
          .catch((err) => {
            this.$refs.oaForm.resetForm()
          })
      } else {
        if (this.customInfo.id) {
          data.eid = this.customInfo.id
        }

        liaisonSaveApi(data)
          .then((res) => {
            if (res.status == 200) {
              this.drawer = false
              this.$emit('isLiaison')
              this.$refs.oaForm.resetForm()
            } else {
              this.$refs.oaForm.resetForm()
            }
          })
          .catch((err) => {
            this.$refs.oaForm.resetForm()
          })
      }
    },

    openBox(row, customInfo, edit) {
      this.edit = edit
      if (customInfo) {
        this.customInfo = customInfo
      }
      if (row) {
        this.row = row
        this.getEditData(row.id)
      } else {
        this.getData()
      }
      this.drawer = true
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-form--inline .el-form-item) {
  display: flex;
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
.from-foot-btn {
  button {
    height: auto;
  }
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-list-liaison-dialog-drawer .el-drawer__body {
  padding: 30px 24px 50px 24px;
}
</style>
