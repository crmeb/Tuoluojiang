<!-- @FileDescription: 审批意见弹窗（拒绝申请时填写审批意见后提交；同意申请为二次确认后直接提交） -->
<template>
  <el-dialog
    v-model="visible"
    title="审批意见"
    :width="DIALOG_SIZE.SM"
    :close-on-click-modal="false"
    :append-to-body="true"
    @close="reset"
  >
    <el-form ref="formRef" :model="form" :rules="rules">
      <el-form-item prop="content">
        <el-input
          v-model="form.content"
          type="textarea"
          :rows="4"
          placeholder="请输入审批意见（选填）"
          maxlength="200"
          show-word-limit
        />
      </el-form-item>
    </el-form>
    <template #footer>
      <span>
        <el-button @click="visible = false">取 消</el-button>
        <el-button type="primary" :loading="loading" @click="submit">确 定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import { ref, reactive, computed } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { approveVerifyStatusApi } from '@/api/business.ts'

export default {
  name: 'VerifyOpinion',
  emits: ['ok'],
  setup(props, { emit, expose }) {
    const visible = ref(false)
    const loading = ref(false)
    const formRef = ref(null)
    const id = ref(0)
    // 1 同意 0 拒绝
    const status = ref(1)
    const form = reactive({ content: '' })
    // 审批意见任何情况下均为选填
    const rules = computed(() => ({
      content: []
    }))

    // 1 同意：二次确认后直接提交；0 拒绝：弹出审批意见弹窗
    function openBox(applyId, verifyStatus) {
      id.value = applyId
      status.value = verifyStatus
      if (verifyStatus === 1) {
        modalSure('确定同意该审批申请吗').then(() => submitVerify(applyId, 1, ''))
        return
      }
      reset()
      visible.value = true
    }
    function reset() {
      form.content = ''
      formRef.value && formRef.value.clearValidate()
    }
    async function submitVerify(applyId, verifyStatus, content) {
      loading.value = true
      try {
        await approveVerifyStatusApi(applyId, verifyStatus, { content })
        visible.value = false
        emit('ok', verifyStatus)
      } finally {
        loading.value = false
      }
    }
    function submit() {
      formRef.value.validate((ok) => {
        if (ok) submitVerify(id.value, status.value, form.content)
      })
    }
    expose({ openBox })

    return { DIALOG_SIZE, visible, loading, formRef, status, form, rules, openBox, reset, submit }
  }
}
</script>
