<template>
  <div>
    <el-dialog :title="this.id ? '编辑素材' : '新增素材'" :visible.sync="visible" width="650px" @close="handleClose">
      <el-form :model="form" label-width="auto" ref="form" :rules="rules">
        <el-form-item label="素材分类：" prop="group_id">
          <el-select v-model="form.group_id" placeholder="请输入选择素材分类（单选）" size="small" style="width: 100%">
            <el-option v-for="item in leftList" :key="item.id" :label="item.name" :value="item.id"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="素材内容：" prop="group_id">
          <materialContent ref="materialContent"></materialContent>
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose" size="small">取 消</el-button>
          <el-button type="primary" @click="handleConfirm" size="small">确 定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>
<script>
import { workMassTempSaveApi, workMassTempEditApi, workMassTempApi } from '@/api/weCom'
export default {
  name: '',
  components: {
    materialContent: () => import('./materialContent')
  },
  props: {
    leftList: {
      type: Array,
      default: []
    },
    group_id: { type: [String, Number], default: '' }
  },
  data() {
    return {
      visible: false,
      id: 0,
      form: { group_id: this.group_id, attach: [], content: '' },
      rules: {
        group_id: [{ required: true, message: '请选择素材分类', trigger: 'change' }]
      }
    }
  },
  watch: {
    group_id: function (val) {
      this.form.group_id = val
    }
  },

  methods: {
    handleConfirm() {
      if (this.$refs.materialContent) {
        this.form.attach = this.$refs.materialContent.uploadFileList
        this.form.content = this.$refs.materialContent.content
      }
      if (this.form.content == '') {
        this.$message.error('请填写内容')
        return false
      }

      this.form.attach.forEach((obj) => {
        delete obj.file
      })
      if (this.id) {
        workMassTempEditApi(this.id, this.form).then(() => {
          this.handleClose()
          this.$emit('getTableData')
        })
      } else {
        workMassTempSaveApi(this.form).then((res) => {
          this.handleClose()
          this.$emit('getTableData')
        })
      }
    },
    openBox(id) {
      if (id) {
        this.id = id
        this.getInfo(id)
      }
      if (this.group_id) {
        this.form.group_id = this.group_id
      }
      this.visible = true
    },
    getInfo(id) {
      workMassTempApi(id).then((res) => {
        this.form.group_id = res.data.group_id
        this.form.attach = res.data.attach
        if (this.form.attach.length > 0) {
          this.form.attach.forEach((item, index) => {
            if (item.file && item.file.id) {
              item.file_id = item.file.id
            }
          })
        }

        this.form.content = res.data.content
        this.$refs.materialContent.getData(this.form)
      })
    },
    handleClose() {
      this.$refs.form.resetFields()
      this.id = ''
      this.form = { group_id: '', attach: [], content: '' }
      this.$refs.materialContent.getData(this.form)
      this.visible = false
    }
  }
}
</script>
<style scoped lang="scss"></style>
