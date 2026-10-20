<!-- 渠道活码-批量移动分组弹窗 -->
<template>
  <el-dialog
    v-model="visible"
    title="批量移动分组"
    :width="DIALOG_SIZE.SM"
    :close-on-click-modal="false"
    @close="reset"
  >
    <el-form ref="formRef" :model="form" label-width="90px">
      <el-form-item label="选择分组">
        <el-select
          v-model="form.groupId"
          filterable
          clearable
          value-on-clear=""
          placeholder="请输入分组名称"
          class="w-full"
        >
          <el-option v-for="item in groupList" :key="item.id" :label="item.name" :value="item.id" />
        </el-select>
        <div class="form-tip">选择要移动至的活码分组，支持根据分组名称模糊搜索；不选择则将活码移出分组。</div>
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
import { ref, reactive } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { batchChannelCodeGroupApi } from '@/api/weCom.ts'
import { useChannelCodeGroup } from '../composables/useChannelCodeGroup.ts'

export default {
  name: 'BatchMoveGroupDialog',
  emits: ['ok'],
  setup(props, { emit, expose }) {
    const visible = ref(false)
    const loading = ref(false)
    const formRef = ref(null)
    // groupId 为空表示不选分组，提交时按 0（移出分组）处理
    const form = reactive({ groupId: '' })
    const ids = ref([])

    const { groupList, fetchGroupList } = useChannelCodeGroup()

    function openBox(selectedIds) {
      reset()
      ids.value = selectedIds
      fetchGroupList()
      visible.value = true
    }

    function reset() {
      form.groupId = ''
      ids.value = []
    }

    async function submit() {
      loading.value = true
      try {
        await batchChannelCodeGroupApi({
          id: ids.value,
          group_id: form.groupId === '' ? 0 : form.groupId
        })
        visible.value = false
        emit('ok')
      } finally {
        loading.value = false
      }
    }

    expose({ openBox })

    return { DIALOG_SIZE, visible, loading, formRef, form, groupList, openBox, reset, submit }
  }
}
</script>

<style lang="scss" scoped>
.w-full {
  width: 100%;
}

.form-tip {
  margin-top: 4px;
  font-size: 12px;
  line-height: 18px;
  color: #909399;
}
</style>
