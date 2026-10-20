<template>
  <el-dialog
      class="user-workbench-assess-target-dialog"
    :title="config.title"
    v-model="dialogVisible"
    :width="config.width"
    :append-to-body="true"
    :before-close="handleClose"
  >
    <el-form ref="form" :model="rules" label-width="90px" :rules="rule" v-if="config.data">
      <el-form-item label="指标名称：" class="assess-item">
        <div class="info">
          {{ config.data.name }}
          <font color="#f90"
            >({{ config.types == 1 ? '满分' + config.data.ratio + '分' : '权重' + config.data.ratio + '%' }})</font
          >
        </div>
      </el-form-item>
      <el-form-item label="指标说明：" style="margin-bottom: 6px">
        <div class="info">{{ config.data.content }}</div>
      </el-form-item>
      <el-form-item label="完成情况：">
        <el-input
          type="textarea"
          :rows="4"
          v-model="config.data.finish_info"
          resize="none"
          placeholder="请输入完成情况"
        />
      </el-form-item>
      <el-form-item
        :label="config.types == 1 ? '自评分(分)：' : '完成度(%)：'"
        class="assess-item"
        :title="config.types == 1 ? '最高' + config.data.ratio + '分' : '最大完成度100%'"
      >
        <el-input-number
          v-model="finishRatio"
          :placeholder="config.types == 1 ? '0～' + config.data.ratio : '0～100'"
          :controls="false"
          :min="0"
          :precision="0"
          :max="config.types == 1 ? config.data.ratio : 100"
        />
        <span class="ml8">{{ config.types == 1 ? '最高' + config.data.ratio + '分' : '%（最高100%）' }}</span>
      </el-form-item>
    </el-form>
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button :loading="loading" type="primary" @click="handleConfirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { userAssessEvalApi } from '@/api/user.ts'
export default {
  emits: ['isMark'],
  name: 'MarkDialog',
  props: {
    config: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      rules: {
        remarks: ''
      },
      rule: {},
      dialogVisible: false,
      loading: false,
      finishRatio: 0
    }
  },
  watch: {
    config: {
      handler(nVal) {
        this.finishRatio = nVal.data.finish_ratio
      },
      deep: true
    }
  },
  methods: {
    handleOpen() {
      this.dialogVisible = true
    },
    handleClose() {
      this.dialogVisible = false
    },
    handleConfirm() {
      if (this.config.data.finish_ratio === undefined) {
        this.$message.error('自评分不能为空')
      } else {
        const data = {
          assess_id: this.config.assessId,
          space_id: this.config.spaceId,
          target_id: this.config.targetId,
          finish_info: this.config.data.finish_info,
          finish_ratio: this.finishRatio
        }
        this.clientEval(data)
      }
    },
    //
    clientEval(data) {
      this.loading = true
      userAssessEvalApi(data)
        .then((res) => {
          this.$emit('isMark')
          this.handleClose()
          this.config.data.finish_ratio = this.finishRatio
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped lang="scss">
.assess-item {
  margin-bottom: 0;
}
:deep(.el-input-number) {
  width: 120px;
}
.ml8 {
  display: inline-block;
  margin-left: 8px;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.user-workbench-assess-target-dialog .el-dialog__header {
  padding: 11px 20px;

  .el-dialog__headerbtn {
    top: 15px;
  }
}
.el-dialog.user-workbench-assess-target-dialog .el-dialog__body {
  padding-top: 15px;
}
</style>
