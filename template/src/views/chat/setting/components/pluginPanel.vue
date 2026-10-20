<template>
  <div class="plugin-panel">
    <div class="panel-subtitle">插件</div>

    <pluginSection
      label="MCP"
      :enabled="isPluginEnabled(PLUGIN_TYPE.MCP)"
      @change="togglePlugin(PLUGIN_TYPE.MCP, $event)"
    >
      <mcpPlugin />
    </pluginSection>

    <pluginSection
      label="知识库"
      :enabled="isPluginEnabled(PLUGIN_TYPE.KNOWLEDGE)"
      @change="togglePlugin(PLUGIN_TYPE.KNOWLEDGE, $event)"
    >
      <knowledgePlugin />
    </pluginSection>

    <pluginSection
      v-if="form.app_type === APP_TYPE.INTERNAL"
      label="数据库"
      :enabled="isPluginEnabled(PLUGIN_TYPE.DATABASE)"
      @change="togglePlugin(PLUGIN_TYPE.DATABASE, $event)"
    >
      <databasePlugin />
    </pluginSection>
  </div>
</template>

<script setup lang="ts">
import pluginSection from './pluginSection.vue'
import databasePlugin from './databasePlugin.vue'
import mcpPlugin from './mcpPlugin.vue'
import knowledgePlugin from './knowledgePlugin.vue'
import { APP_TYPE, PLUGIN_TYPE } from '../constants'
import { useApplicationSetting } from '../composables/useApplicationSetting'

const { form, isPluginEnabled, togglePlugin } = useApplicationSetting()
</script>

<style scoped lang="scss">
.panel-subtitle {
  margin-bottom: 14px;
  font-weight: 500;
  font-size: 12px;
  color: #606266;
}
</style>
