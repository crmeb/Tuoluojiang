<template>
  <div class="preview-panel">
    <div ref="containerRef" class="preview-frame" />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import store from '@/store'
import { useAppPreview } from '../composables/useAppPreview'

/**
 * 外部应用按访客形态预览，因此要等应用详情带回 share_key 之后才能挂载 ——
 * 由父级用 v-if 控制渲染时机，这里只在挂载那一刻取一次参数。
 */
const props = defineProps<{
  appId: string
  identity: 'workspace' | 'visitor'
  shareKey?: string
}>()

const { containerRef, create, update } = useAppPreview(() => ({
  appId: props.appId,
  identity: props.identity,
  shareKey: props.shareKey,
  token: store.getters.token
}))

onMounted(create)

defineExpose({ update })
</script>

<style scoped lang="scss">
.preview-panel {
  flex: 1;
  width: 100%;
  border-radius: 8px;
  background: #f9fbfc;
}

.preview-frame {
  width: 100%;
  height: 100%;
}
</style>
