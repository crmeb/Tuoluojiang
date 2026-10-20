<template>
  <el-dialog
    title="参数设置"
    v-model="visible"
    :width="DIALOG_SIZE.XL"
    :close-on-click-modal="false"
    @close="reset"
  >
    <el-table :data="rows" style="width: 100%">
      <el-table-column label="参数名称" width="180">
        <template #default="scope">
          <el-input v-model="scope.row.name" placeholder="请输入参数名称" />
        </template>
      </el-table-column>
      <el-table-column label="参数标识" width="180">
        <template #default="scope">
          <el-input v-model="scope.row.filed" placeholder="请输入参数标识" />
        </template>
      </el-table-column>
      <el-table-column label="默认值" width="180">
        <template #default="scope">
          <el-input v-model="scope.row.value" placeholder="请输入默认值" />
        </template>
      </el-table-column>
      <el-table-column label="提示">
        <template #default="scope">
          <el-input v-model="scope.row.message" placeholder="请输入提示" />
        </template>
      </el-table-column>
      <el-table-column label="操作" width="90">
        <template #default="scope">
          <el-button type="primary" link @click="handleRemove(scope.$index)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="add-text" @click="addRow">+ 添加参数</div>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" @click="submit">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { modalSure } from '@/libs/public.ts'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import type { ParamItem } from '../types.ts'

const emit = defineEmits<{ (e: 'submit', rows: ParamItem[]): void }>()

const visible = ref(false)
const rows = ref<ParamItem[]>([])

const isBlank = (row: ParamItem) => Object.values(row).every((value) => value === '')

const reset = () => {
  rows.value = []
}

const openBox = (list: ParamItem[]) => {
  rows.value = JSON.parse(JSON.stringify(list || []))
  visible.value = true
}

const addRow = () => {
  rows.value.push({ name: '', filed: '', value: '', message: '' })
}

const handleRemove = (index: number) => {
  modalSure('确定删除此参数').then(() => {
    rows.value.splice(index, 1)
  })
}

const submit = () => {
  // 整行留空视为未填写，直接丢弃；填了一半才提示补全
  const filled = rows.value.filter((row) => !isBlank(row))
  const invalid = filled.some((row) => !row.name || !row.filed || !row.value)
  if (invalid) {
    Message.error('请填写完整，参数设置不能为空')
    return
  }
  emit('submit', filled)
  visible.value = false
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
.add-text {
  margin-top: 20px;
  font-weight: 400;
  font-size: 13px;
  color: #1890ff;
  cursor: pointer;
}
</style>
