<template>
  <div class="setting-header">
    <div class="header-left">
      <i class="el-icon-arrow-left back" @click="emit('back')" />
      <div class="app-logo">
        <img v-if="pic" :src="pic" alt="" />
        <i v-else class="icon iconfont iconshitosheji" />
      </div>
      <div class="header-info">
        <span class="app-name">{{ title || '--' }}</span>
        <el-tabs v-model="activeTab" class="header-tabs">
          <el-tab-pane v-for="item in TABS" :key="item.value" :label="item.label" :name="item.value" />
        </el-tabs>
      </div>
    </div>

    <div class="header-actions">
      <el-button plain size="small" :loading="saving" @click="emit('save')">保存</el-button>
      <el-button type="primary" size="small" :loading="saving" @click="emit('publish')">发布</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

/** 目前只有「设置」一个页签，后续加编排 / 数据分析时在这里扩展 */
const TABS = [{ value: 'setting', label: '设置' }]

withDefaults(
  defineProps<{
    title?: string
    pic?: string
    saving?: boolean
  }>(),
  {
    title: '',
    pic: '',
    saving: false
  }
)

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'save'): void
  (e: 'publish'): void
}>()

const activeTab = ref(TABS[0].value)
</script>

<style scoped lang="scss">
.setting-header {
  width: 100%;
  height: 69px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30px 0 20px;
  border-bottom: 1px solid #eeeeee;
  font-family: PingFang SC, PingFang SC;
}

.header-left {
  display: flex;
  align-items: center;
}

.back {
  margin-right: 10px;
  font-size: 13px;
  color: #606266;
  cursor: pointer;
}

.app-logo {
  width: 48px;
  height: 48px;
  border-radius: 4px;
  line-height: 48px;
  text-align: center;
  overflow: hidden;

  img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .iconshitosheji {
    font-size: 24px;
    color: #fff;
  }
}

.header-info {
  margin-left: 13px;
  padding: 11px 0;
}

.app-name {
  font-weight: 500;
  font-size: 14px;
  color: #303133;
}

.header-tabs {
  :deep(.el-tabs__nav-wrap::after) {
    height: 0;
  }

  :deep(.el-tabs__header) {
    margin: 0;
  }

  :deep(.el-tabs__item) {
    height: 37px;
    line-height: 37px;
    font-weight: 500;
    font-size: 13px;
    color: #606266;
  }

  :deep(.el-tabs__item.is-active) {
    color: #1890ff;
  }
}
</style>
