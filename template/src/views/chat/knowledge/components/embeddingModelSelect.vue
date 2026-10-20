<!-- 向量模型下拉：创建知识库弹窗与详情页「设置」页签共用，选项加载 / 远程搜索 / 回显都在组件内自理 -->
<template>
  <el-select
    class="embedding-model-select"
    :model-value="modelValue"
    filterable
    remote
    remote-show-suffix
    clearable
    :disabled="disabled"
    :remote-method="remoteSearch"
    :loading="isLoading"
    :placeholder="placeholder"
    @change="handleChange"
    @visible-change="handleVisibleChange"
  >
    <el-option v-for="item in options" :key="item.id" :label="item.name" :value="item.id">
      <span class="option-name">{{ item.name }}</span>
      <span class="option-extra">{{ item.model_id }}</span>
    </el-option>
  </el-select>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRemoteSelect } from '@/composables/useRemoteSelect.ts'
import { getModelsListApi, getModelsInfoApi } from '@/api/chatAi.ts'

interface EmbeddingModel {
  id: number
  name: string
  model_id?: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: number | string
    placeholder?: string
    disabled?: boolean
  }>(),
  {
    modelValue: '',
    placeholder: '请选择向量模型',
    disabled: false
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | string): void
  (e: 'change', value: number | string): void
}>()

// 组件可能常驻（弹窗挂在列表页上），挂载即请求会白拉一次，改为首次展开下拉时再取首屏
const {
  list,
  isLoading,
  keyword,
  remoteSearch,
  reset
} = useRemoteSelect<EmbeddingModel>(getModelsListApi, {
  immediate: false,
  params: () => ({ model_type: 2 })
})

// 已选模型可能不在首屏 10 条内，单独存一份并入选项，否则搜索或重置后回显只剩裸 id
const selectedModel = ref<EmbeddingModel | null>(null)

const options = computed<EmbeddingModel[]>(() => {
  const rows = list.value
  const selected = selectedModel.value
  if (!selected || rows.some((item) => item.id === selected.id)) return rows
  return [selected, ...rows]
})

/** 按 id 补齐选中项：先从已有选项里找，找不到再单独取详情 */
const ensureSelected = async (id?: number | string) => {
  if (!id) {
    selectedModel.value = null
    return
  }
  if (selectedModel.value && selectedModel.value.id === id) return
  const hit = list.value.find((item) => item.id === id)
  if (hit) {
    selectedModel.value = hit
    return
  }
  try {
    const res = await getModelsInfoApi(id)
    if (res.data) selectedModel.value = res.data
  } catch {
    // 模型可能已被删除，回显失败不阻塞表单
  }
}

watch(() => props.modelValue, ensureSelected, { immediate: true })

const handleChange = (val: number | string) => {
  emit('update:modelValue', val)
  emit('change', val)
}

const handleVisibleChange = (isOpen: boolean) => {
  // 首次展开才拉首屏；收起时若停留在搜索结果上则还原，避免下次打开只剩上次搜到的那几条
  if (isOpen) {
    if (!list.value.length) reset()
  } else if (keyword.value) {
    reset()
  }
}
</script>

<style scoped lang="scss">
.embedding-model-select {
  width: 100%;
}

.option-name {
  float: left;
}

.option-extra {
  float: right;
  margin-left: 20px;
  color: #909399;
  font-size: 12px;
}
</style>
