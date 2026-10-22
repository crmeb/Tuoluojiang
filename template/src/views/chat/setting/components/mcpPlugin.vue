<template>
  <div class="mcp-plugin">
    <fieldLabel
      label="MCP"
      collapsible
      :expanded="expanded"
      :tips="FIELD_TIPS.MCP"
      @toggle="expanded = !expanded"
    >
      <template #extra>
        <span class="text-btn" @click="openMcp">MCP设置</span>
      </template>
    </fieldLabel>

    <el-collapse-transition>
      <div v-show="expanded" class="plugin-field">
        <div v-if="!mcpServices.length" class="empty">暂未绑定 MCP 服务</div>
        <div v-for="(item, index) in mcpServices" :key="item.id" class="resource-card">
          <div class="resource-info">
            <div class="over-text name">{{ item.name }}</div>
            <div class="over-text desc">{{ item.info }}</div>
          </div>
          <i class="el-icon-delete remove" title="移除" @click="handleRemove(index)" />
        </div>
      </div>
    </el-collapse-transition>

    <addMcp ref="addMcpRef" @mcpSubmit="handleSubmit" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { modalSure } from '@/libs/public'
import fieldLabel from './fieldLabel.vue'
import addMcp from './addMcp.vue'
import { FIELD_TIPS } from '../constants'
import type { McpItem } from '../types'
import { useApplicationSetting } from '../composables/useApplicationSetting'

const { form, mcpServices } = useApplicationSetting()

const expanded = ref(true)
const addMcpRef = ref<any>(null)

/** 选中的服务卡片只用于展示，提交体里只留 ID */
const syncIds = () => {
  form.mcp_service_ids = mcpServices.value.map((item) => item.id)
}

const openMcp = () => {
  addMcpRef.value.openBox(form.app_type, mcpServices.value)
}

const handleSubmit = (list: McpItem[]) => {
  mcpServices.value = list
  syncIds()
}

const handleRemove = (index: number) => {
  modalSure('确定删除此MCP服务吗').then(() => {
    mcpServices.value.splice(index, 1)
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

.resource-info {
  flex: 1;
  min-width: 0;

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
