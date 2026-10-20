<template>
  <div class="knowledge-plugin">
    <fieldLabel
      label="知识库"
      collapsible
      :expanded="expanded"
      :tips="FIELD_TIPS.KNOWLEDGE"
      @toggle="expanded = !expanded"
    >
      <template #extra>
        <span class="text-btn" @click="openDialog">知识库设置</span>
      </template>
    </fieldLabel>

    <el-collapse-transition>
      <div v-show="expanded" class="plugin-field">
        <div v-if="!knowledge.length" class="empty">暂未绑定知识库</div>
        <div v-for="(item, index) in knowledge" :key="item.id" class="resource-card">
          <div class="card-icon" :style="{ backgroundColor: item.color || DEFAULT_ICON_COLOR }">
            <i :class="getIconClass(item.icon)" />
          </div>
          <div class="resource-info">
            <div class="over-text name" :title="item.name">{{ item.name }}</div>
            <div class="over-text desc" :title="item.description">{{ item.description || '暂无描述' }}</div>
          </div>
          <i class="el-icon-delete remove" title="移除" @click="handleRemove(index)" />
        </div>
      </div>
    </el-collapse-transition>

    <knowledgeSelectDialog ref="dialogRef" @submit="handleSubmit" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { modalSure } from '@/libs/public.ts'
import fieldLabel from './fieldLabel.vue'
import knowledgeSelectDialog from './knowledgeSelectDialog.vue'
import { DEFAULT_ICON_COLOR } from '../../knowledge/constants.ts'
import { getIconClass } from '../../knowledge/utils/icon.ts'
import { FIELD_TIPS } from '../constants.ts'
import type { KnowledgeItem } from '../types.ts'
import { useApplicationSetting } from '../composables/useApplicationSetting.ts'

const { knowledge, form } = useApplicationSetting()

const expanded = ref(true)
const dialogRef = ref<any>(null)

const openDialog = () => {
  dialogRef.value.openBox(knowledge.value)
}

const syncIds = () => {
  form.knowledge_ids = knowledge.value.map((item) => item.id)
}

const handleSubmit = (list: KnowledgeItem[]) => {
  knowledge.value = list
  syncIds()
}

const handleRemove = (index: number) => {
  modalSure('确定移除此知识库吗').then(() => {
    knowledge.value.splice(index, 1)
    syncIds()
  })
}
</script>

<style scoped lang="scss">
.plugin-field {
  margin-top: 10px;
}

.empty {
  padding: 12px 0;
  font-size: 12px;
  color: #909399;
  text-align: center;
}

.resource-card {
  display: flex;
  align-items: center;
  padding: 12px;
  border-radius: 4px;
  background: #fff;

  & + & {
    margin-top: 8px;
  }
}

.card-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 5px;
  color: #fff;
  font-size: 20px;
}

.resource-info {
  flex: 1;
  min-width: 0;
  margin-left: 12px;

  .name {
    font-weight: 500;
    font-size: 13px;
    line-height: 22px;
    color: #303133;
  }

  .desc {
    font-size: 12px;
    line-height: 22px;
    color: #909399;
  }
}

.remove {
  flex-shrink: 0;
  margin-left: 12px;
  font-size: 16px;
  color: #606266;
  cursor: pointer;
}

.text-btn {
  font-weight: 400;
  font-size: 13px;
  color: #1890ff;
  cursor: pointer;
}
</style>
