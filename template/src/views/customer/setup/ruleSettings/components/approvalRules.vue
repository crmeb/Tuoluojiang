<template>
  <div>
    <div class="card-box">
      <el-card :body-style="{ padding: '20px 20px 0' }" class="box-card">
        <div class="setting-container">
          <!-- <el-col v-bind="grid1">&nbsp;</el-col> -->

          <div class="card-list">
            <div class="tips mb20">请前往人事>审批设置中增加审批流程，请关联对应控件组进行添加</div>
            <form-create
              v-if="fromData"
              :option="fromData.rule"
              :rule="fromData.rule"
              @submit="onSubmit"
              class="form-base"
            />
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>
<script>
import request from '@/api/request'
import formCreate from '@form-create/element-ui'
export default {
  name: 'ApprovalRules',
  props: {
    ruleForm: {
      default: () => {},
      type: Object
    },
    fromData: {
      default: () => {},
      type: Object
    }
  },
  components: {
    formCreate: formCreate.$form()
  },
  data() {
    return {
      rules: {}
    }
  },
  methods: {
    onSubmit(formData) {
      request[this.fromData.method.toLowerCase()](this.fromData.action, formData)
    }
  }
}
</script>
<style lang="scss" scoped>
.card-box {
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;

  .box-card {
    height: calc(100vh - 137px);
    .setting-container {
      .card-list {
        .info {
          font-size: 12px;
          color: #909399;
          margin-left: 8px;
        }
      }
    }
  }
}

.tips {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 12px;
  color: #909399;
  transform: translateX(54px);
}

.tips,
.form-base {
  width: 460px;
  margin-inline: auto;
}
</style>
