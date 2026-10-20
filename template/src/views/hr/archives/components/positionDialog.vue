<template>
  <el-dialog
      class="hr-archives-position-dialog"
    :title="config.title"
    :modal="true"
    v-model="dialogVisible"
    :width="config.width"
    :append-to-body="true"
    :before-close="handleClose"
    :close-on-click-modal="false"
  >
    <el-form ref="form" class="mt15" :model="ruleForm" :rules="rules" label-width="90px">
      <el-form-item :label="'职位名称' + '：'" prop="name">
        <el-input type="text" v-model="ruleForm.name" placeholder="请填写职位名称"></el-input>
      </el-form-item>
      <el-form-item label="职级类别：" prop="cateId">
        <el-select v-model="ruleForm.cateId" clearable placeholder="请选择职级类别" @change="handleCate">
          <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item :label="'职级' + '：'" prop="rankId">
        <el-select v-model="ruleForm.rankId" clearable placeholder="请选择职级">
          <el-option v-for="item in rankData" :key="item.value" :label="item.label" :value="item.value"></el-option>
        </el-select>
      </el-form-item>
    </el-form>
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button class="mr20" :loading="loading" type="primary" @click="handleConfirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { endJobSaveApi } from '@/api/enterprise.ts'
import { rankCateListApi, rankListApi } from '@/api/setting.ts'

export default {
  emits: ['handleConfig'],
  name: 'PositionDialog',
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
      dialogVisible: false,
      ruleForm: {
        name: '',
        cateId: '',
        rankId: ''
      },
      rules: {
        name: [{ required: true, message: '请填写职位名称', trigger: 'blur' }],
        cateId: [{ required: true, message: '请选择职级类别', trigger: 'change' }],
        rankId: [{ required: true, message: '请选择职级', trigger: 'change' }]
      },
      loading: false,
      propsPos: { value: 'id', label: 'name', multiple: false, checkStrictly: true },
      options: [],
      rankData: []
    }
  },
  mounted() {
    this.getRankCate()
  },
  methods: {
    handleOpen() {
      this.dialogVisible = true
    },

    handleClose() {
      this.ruleForm.name = ''
      this.ruleForm.cateId = ''
      this.ruleForm.rankId = ''
      this.$refs.form.resetFields()
      this.dialogVisible = false
    },
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          const data = {
            name: this.ruleForm.name,
            cate_id: this.ruleForm.cateId,
            rank_id: this.ruleForm.rankId
          }
          this.endJobs(data)
        }
      })
    },
    endJobs(data) {
      this.loading = true
      endJobSaveApi(data)
        .then((res) => {
          this.loading = false
          this.ruleForm.name = ''
          this.ruleForm.cateId = ''
          this.ruleForm.rankId = ''
          this.handleClose()
          this.$emit('handleConfig')
        })
        .catch((error) => {
          this.loading = false
        })
    },
    async getRankCate() {
      const data = {
        page: 1,
        limit: 0
      }
      const result = await rankCateListApi(data)
      this.options = result.data.list
    },
    handleCate(e) {
      this.getRankData(e)
      this.ruleForm.rankId = ''
    },
    async getRankData(id) {
      const data = {
        page: 1,
        limit: 0,
        cate_id: id
      }
      const result = await rankListApi(data)
      this.rankData = result.data.list
    }
  }
}
</script>

<style scoped lang="scss">
:deep(.el-select) {
  width: 100%;
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.hr-archives-position-dialog .el-dialog__footer {
  padding: 20px 0;
}
</style>
