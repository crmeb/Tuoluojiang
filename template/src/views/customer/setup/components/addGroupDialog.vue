<!-- 客户标签设置-添加标签组弹窗 -->
<template>
  <el-dialog
    title="添加标签组"
    v-model="visible"
    :width="DIALOG_SIZE.SM"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
      <el-form-item label="标签组名称：" prop="name">
        <el-input v-model="form.name" maxlength="16" show-word-limit placeholder="请输入标签组名称" />
      </el-form-item>

      <el-form-item label="标签组类型：" prop="group_type">
        <el-radio-group v-model="form.group_type">
          <el-radio v-for="item in LABEL_GROUP_TYPE_OPTIONS" :key="item.value" :label="item.value" :value="item.value">
            {{ item.label }}
          </el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="排序：">
        <el-input-number :controls="false" :min="0" :max="999999" v-model="form.sort" placeholder="请输入排序" />
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
import { ref, reactive } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { LABEL_GROUP_TYPE, LABEL_GROUP_TYPE_OPTIONS } from '@/constants/labelGroupType.ts'
import { clientConfigLabelSaveApi } from '@/api/enterprise.ts'

// 表单初始值：标签组默认手动标签，排序默认 0
const createForm = () => ({
  name: '',
  group_type: LABEL_GROUP_TYPE.MANUAL,
  sort: 0
})

export default {
  name: 'AddGroupDialog',
  setup(props, { emit }) {
    const visible = ref(false)
    const loading = ref(false)
    const formRef = ref(null)

    const form = reactive(createForm())

    const rules = {
      name: [{ required: true, message: '请输入标签组名称', trigger: 'blur' }],
      group_type: [{ required: true, message: '请选择标签组类型', trigger: 'change' }]
    }

    const resetForm = () => {
      Object.assign(form, createForm())
      formRef.value && formRef.value.clearValidate()
    }

    const openBox = () => {
      resetForm()
      visible.value = true
    }

    const handleClose = () => {
      visible.value = false
      resetForm()
    }

    const handleConfirm = () => {
      formRef.value.validate((valid) => {
        if (!valid) return

        loading.value = true
        // 标签组为顶级节点，pid 固定为 0
        clientConfigLabelSaveApi({
          name: form.name,
          pid: 0,
          sort: form.sort === '' || form.sort === undefined ? 0 : form.sort,
          group_type: form.group_type
        })
          .then(() => {
            loading.value = false
            handleClose()
            emit('isOk')
          })
          .catch(() => {
            loading.value = false
          })
      })
    }

    return {
      DIALOG_SIZE,
      LABEL_GROUP_TYPE_OPTIONS,

      visible,
      loading,
      formRef,
      form,
      rules,

      openBox,
      handleClose,
      handleConfirm
    }
  }
}
</script>

<style scoped lang="scss">
:deep(.el-dialog__body) {
  padding-bottom: 0;
}
:deep(.el-input-number) {
  width: 100%;
  .el-input__inner {
    text-align: left;
  }
}
.dialog-footer {
  padding-top: 0;
}
</style>
