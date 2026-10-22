<!-- 添加客户弹窗组件 -->
<template>
  <div class="station">
    <el-drawer
      :title="formData.title"
      :visible.sync="drawer"
      :direction="direction"
      :modal="true"
      :wrapperClosable="false"
      :before-close="handleClose"
      :size="formData.width"
    >
      <oaForm
        :form-info="fromInfo"
        :types="types"
        ref="oaForm"
        :uid="uid"
        @handleClose="handleClose"
        @submitOk="submitOk"
        @addContinueOk="addContinueOk"
      ></oaForm>
    </el-drawer>
    <!-- 添加商机 -->
    <addForm ref="addForm" :form-data="formBoxConfig" :keyword="`odds`"></addForm>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import { oddsCreateApi } from '@/api/client'
import { chargeCreateApi, chargeEditApi, chargeEditSubmitApi } from '@/api/enterprise'
import { clientCustomerSaveApi } from '@/api/client'
export default {
  name: 'Index',
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    addForm: () => import('@/views/customer/components/addForm'),
    oaForm: () => import('@/components/customer/oaForm'),
    addContract: () => import('@/views/customer/contract/components/addContract')
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      fromInfo: [],
      row: {},
      types: 0,
      force: 0,
      uid: '',
      formBoxConfig: {},
      contractFromData: {}
    }
  },

  methods: {
    // 新增客户表单
    getData() {
      chargeCreateApi({ link_id: this.formData.linkId || 0 }).then((res) => {
        this.fromInfo = res.data
      })
    },
    getEditData(id) {
      chargeEditApi(id).then((res) => {
        this.fromInfo = res.data
      })
    },
    handleClose() {
      this.fromInfo = []
      this.row = {}
      this.uid = ''
      this.drawer = false
    },
    // 提交并继续添加订单
    addContinueOk(data) {
      this.submitOk(data, 1)
    },

    // 提交成功
    submitOk(data, type) {
      data.types = this.types
      data.force = this.force
      // 编辑客户信息
      if (this.row.id) {
        chargeEditSubmitApi(this.row.id, data)
          .then((res) => {
            if (res.status == 200) {
              if (type == 1) {
                this.formBoxConfig = {
                  title: '新增商机',
                  width: '1000px',
                  types: 'odds'
                }
                oddsCreateApi({eid: this.row.id}).then((result) => {
                  result.data.forEach((item) => {
                    item.data.forEach((el) => {
                      if (el.key == 'eid') {
                        el.value = this.row.id
                      }
                    })
                  })
                  setTimeout(() => {
                    this.$refs.addForm.openBox(result.data)
                    this.$refs.oaForm.resetForm()
                  }, 300)
                })
              } else {
                this.drawer = false
                this.$refs.oaForm.resetForm()
              }
              this.$emit('isOkEdit')
            } else {
              this.$refs.oaForm.saveLoading = false
              this.$refs.oaForm.addContractLoading = false
            }
          })
          .catch((err) => {
            if (err.data.status == 2001) {
              modalSure(err.data.message).then(() => {
                this.force = 1
                this.submitOk(data, type)
                this.force = 0
              })
              this.force = 0
            }
            this.$refs.oaForm.saveLoading = false
            this.$refs.oaForm.addContractLoading = false
          })
      } else {
        // 添加新客户

        clientCustomerSaveApi(data)
          .then((res) => {
            if (res.status == 200) {
              if (type == 1) {
                this.formBoxConfig = {
                  title: '新增商机',
                  width: '1000px',
                  types: 'odds'
                }
                oddsCreateApi({eid: res.data.id}).then((result) => {
                  result.data.forEach((item) => {
                    item.data.forEach((el) => {
                      if (el.key == 'eid') {
                        el.value = res.data.id
                      }
                    })
                  })
                  this.$refs.addForm.openBox(result.data)
                  this.$refs.oaForm.resetForm()
                })
              } else {
                this.drawer = false
                this.$refs.oaForm.resetForm()
              }
              this.$refs.oaForm.saveLoading = false
              this.$emit('isOkEdit')
            } else {
              this.$refs.oaForm.saveLoading = false
            }
          })
          .catch((err) => {
            if (err.data.status == 2001) {
              modalSure(err.data.message).then(() => {
                this.force = 1
                this.submitOk(data, type)
                this.force = 0
              })

              this.force = 0
            }
            this.$refs.oaForm.saveLoading = false
          })
      }
    },

    openBox(type, row, str) {
      this.types = type
      if (str === 'edit') {
        this.row = row
        this.uid = row.uid
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
.station ::v-deep .el-drawer__body {
  padding: 30px 24px 50px 24px;
}
::v-deep .el-form--inline .el-form-item {
  display: flex;
}

.from-foot-btn {
  button {
    height: auto;
  }
}
::v-deep .el-tag.el-tag--info {
  border: none;
}
</style>
