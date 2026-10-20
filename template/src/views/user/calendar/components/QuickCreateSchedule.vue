<template>
  <div class="quick-create-schedule">
    <el-input ref="titleInputRef" v-model="title" :disabled="loading" maxlength="256" placeholder="添加待办任务..."
 @keydown="handleTitleKeydown">
      <template v-if="loading" #suffix><i class="el-icon-loading" /></template>
    </el-input>

    <div class="quick-create-actions">
      <select-member ref="memberSelectorRef" :value="members" :is-avatar="true" @getSelectList="handleMemberChange">
        <template #custom>
          <button type="button" class="quick-action" @click.stop="openMemberSelector">
            <i class="iconfont iconjiaqiantubiao" />
            <span>{{ memberLabel }}</span>
          </button>
        </template>
      </select-member>

      <el-divider direction="vertical" class="custom-divider" />

      <div class="quick-date-trigger">
        <button type="button" class="quick-action" :class="{ active: endTime }" @click="openEndTimePicker">
          <i class="iconfont iconxiaoxi2" />
          <span>{{ endTimeLabel }}</span>
        </button>

        <el-date-picker
          ref="endTimePickerRef"
          v-model="endTime"
          class="quick-date-picker"
          placement="bottom-start"
          type="datetime"
          value-format="YYYY-MM-DD HH:mm:ss"
          format="YYYY-MM-DD HH:mm"
          placeholder="截止时间"
          :default-time="new Date(2000, 0, 1, 12, 0, 0)"
          :clearable="true"
        />
      </div>

      <el-divider direction="vertical" class="custom-divider" />

      <button type="button" class="quick-action" :class="{ active: content }" @click="openDescriptionDialog">
        <i class="iconfont iconbianji1" />
        <span>{{ content ? '已添加描述' : '描述' }}</span>
      </button>
    </div>

    <QuickDescriptionDialog ref="descriptionDialogRef" @confirm="handleDescriptionConfirm" />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { ElMessage as Message } from 'element-plus'
import SelectMember from '@/components/form-common/select-member.vue'
import { scheduleQuickCreateApi } from '@/api/user.ts'
import QuickDescriptionDialog from './QuickDescriptionDialog.vue'

interface MemberOption {
  id?: number | string
  value?: number | string
  name?: string
  label?: string
}

interface SelectMemberInstance {
  handlePopoverShow: () => void | Promise<void>
  clearSelection: () => void
}

interface InputInstance {
  focus: () => void
}

interface DatePickerInstance {
  focus: () => void
}

interface DescriptionDialogInstance {
  openBox: (value?: string) => void
}

const emit = defineEmits<{
  (event: 'created'): void
}>()

const titleInputRef = ref<InputInstance | null>(null)
const memberSelectorRef = ref<SelectMemberInstance | null>(null)
const endTimePickerRef = ref<DatePickerInstance | null>(null)
const descriptionDialogRef = ref<DescriptionDialogInstance | null>(null)
const title = ref('')
const members = ref<MemberOption[]>([])
const endTime = ref('')
const content = ref('')
const loading = ref(false)

const memberLabel = computed(() => {
  if (!members.value.length) return '添加参与人'
  if (members.value.length === 1) return members.value[0].name || members.value[0].label || '已选1人'
  return `已选${members.value.length}人`
})

const endTimeLabel = computed(() => (endTime.value ? endTime.value.slice(0, 16) : '截止时间'))

function handleTitleKeydown(event: KeyboardEvent) {
  // 输入法组合期间（拼音选字、空格上屏）不触发提交，仅非组合态按 Enter 提交
  if (event.isComposing || event.keyCode === 229) return
  if (event.key === 'Enter') {
    event.preventDefault()
    handleCreate()
  }
}

function openMemberSelector() {
  memberSelectorRef.value?.handlePopoverShow()
}

function handleMemberChange(list: MemberOption[]) {
  members.value = list
}

function openEndTimePicker() {
  endTimePickerRef.value?.focus()
}

function openDescriptionDialog() {
  descriptionDialogRef.value?.openBox(content.value)
}

function handleDescriptionConfirm(value: string) {
  content.value = value
}

function buildPayload() {
  const payload: {
    title: string
    member?: number[]
    end_time?: string
    content?: string
  } = {
    title: title.value.trim()
  }

  const memberIds = members.value
    .map((member) => Number(member.value ?? member.id))
    .filter((id) => Number.isInteger(id) && id > 0)

  if (memberIds.length) payload.member = memberIds
  if (endTime.value) payload.end_time = endTime.value
  if (content.value.trim()) payload.content = content.value.trim()

  return payload
}

async function resetForm() {
  title.value = ''
  members.value = []
  endTime.value = ''
  content.value = ''
  await nextTick()
  memberSelectorRef.value?.clearSelection()
}

async function handleCreate() {
  if (loading.value) return
  if (!title.value.trim()) {
    Message.warning('请输入待办任务')
    return
  }

  loading.value = true
  try {
    await scheduleQuickCreateApi(buildPayload())
    await resetForm()
    emit('created')
  } finally {
    loading.value = false
  }
}

function focus() {
  titleInputRef.value?.focus()
}

defineExpose({ focus })
</script>

<style lang="scss" scoped>
.quick-create-schedule {
  margin: 0 14px 8px;
  padding: 14px;
  border-radius: 6px;
  background: #f7f8fa;
}

:deep(.el-input__wrapper) {
  border-radius: 4px;
}

.quick-create-actions {
  display: flex;
  align-items: center;
  margin-top: 12px;
  color: #606266;
}

.quick-date-trigger {
  position: relative;
}

// el-date-picker 根节点带 Teleport（多根），不会继承 scoped 属性，需用 :deep 命中
.quick-date-trigger :deep(.quick-date-picker) {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 1px !important;
  height: 1px;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
}

.quick-action {
  border: 0;
  background: transparent;
  color: #606266;
  font-size: 12px;
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;

  &:hover,
  &.active {
    color: #1890ff;
  }

  i {
    margin-right: 4px;
    font-size: 14px;
  }
}

.custom-divider {
  margin-inline: 16px;
}
</style>
