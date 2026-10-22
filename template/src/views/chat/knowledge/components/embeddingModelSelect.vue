<!-- 向量模型下拉：创建知识库弹窗与详情页「设置」页签共用，选项加载 / 远程搜索 / 回显都在组件内自理 -->
<template>
  <el-select
    class="embedding-model-select"
    :value="value"
    filterable
    remote
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
import { useRemoteSelect } from '@/composables/useRemoteSelect'
import { getModelsListApi, getModelsInfoApi } from '@/api/chatAi'

interface EmbeddingModel {
  id: number
  name: string
  model_id?: string
}

const props = withDefaults(
  defineProps<{
    value?: number | string
    placeholder?: string
    disabled?: boolean
  }>(),
  {
    value: '',
    placeholder: '请选择向量模型',
    disabled: false
  }
)

const emit = defineEmits<{
  (e: 'input', value: number | string): void
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

watch(() => props.value, ensureSelected, { immediate: true })

const handleChange = (val: number | string) => {
  emit('input', val)
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

  // remote + filterable 时 element 会把 iconClass 置空，下拉箭头没有字形，
  // 按 inventory/components/OrderSelect.vue 的方式补回来
  :deep(.el-input) {
    --rotate: 180deg;

    &.is-focus {
      --rotate: 0deg;
    }

    // 仅改写下拉箭头字形；clearable 的清空图标与箭头共用 .el-select__caret.el-input__icon，
    // 需排除，否则清空按钮会被画成箭头，用户以为在展开下拉实则触发清空
    .el-select__caret.el-input__icon:not(.el-icon-circle-close) {
      transition: transform 0.3s;
      rotate: var(--rotate);

      &::before {
        content: '\e6df';
      }
    }
  }
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
