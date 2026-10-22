<!-- 知识库图标选择：复用系统图标库 + 底色色板 -->
<template>
  <div class="icon-field">
    <div class="icon-picker" :class="{ 'is-empty': !icon }" @click="showIconDialog = true">
      <span v-if="icon" class="picker-value">
        <i :class="getIconClass(icon)" :style="{ color: color }" />
        <span class="picker-label">{{ getIconName(icon) }}</span>
      </span>
      <span v-else>请选择图标</span>
      <i class="el-icon-plus icon-suffix" />
    </div>

    <el-color-picker
      :value="color"
      :predefine="ICON_COLORS"
      class="color-picker"
      @input="handleColorChange"
    />

    <!-- 选择图标：复用系统图标库组件 -->
    <el-dialog
      title="选择知识库图标"
      :visible.sync="showIconDialog"
      :width="DIALOG_SIZE.XL"
      append-to-body
    >
      <div class="icon-box">
        <select-icon isEmit @select="handleSelectIcon" />
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import selectIcon from '@/components/form-common/select-icon.vue'
import { DIALOG_SIZE } from '@/constants/popupSize'
import { ICON_COLORS, DEFAULT_ICON_COLOR } from '../constants'
import { getIconClass, getIconName } from '../utils/icon'

withDefaults(defineProps<{ icon?: string; color?: string }>(), {
  icon: '',
  color: DEFAULT_ICON_COLOR
})

const emit = defineEmits<{
  (e: 'update:icon', icon: string): void
  (e: 'update:color', color: string): void
}>()

const showIconDialog = ref(false)

function handleSelectIcon(value: string) {
  emit('update:icon', value)
  showIconDialog.value = false
}

// el-color-picker 清空时给的是 null，回落到默认底色，避免图标变透明
function handleColorChange(value: string | null) {
  emit('update:color', value || DEFAULT_ICON_COLOR)
}
</script>

<style scoped lang="scss">
.icon-field {
  display: flex;
  align-items: center;
  gap: 12px;
}

.icon-picker {
  flex: 1;
  min-width: 0;
  height: 32px;
  display: flex;
  align-items: center;
  padding: 0 12px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fff;
  font-size: 12px;
  color: #303133;
  cursor: pointer;

  &.is-empty {
    color: #c0c4cc;
  }

  &:hover {
    border-color: #c0c4cc;
  }
}

.picker-value {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  overflow: hidden;

  .iconfont {
    font-size: 16px;
    line-height: 1;
  }

  .picker-label {
    margin-left: 8px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
}

.icon-suffix {
  margin-left: auto;
  padding-left: 8px;
  color: #c0c4cc;
}

.color-picker {
  flex-shrink: 0;

  // el-color-picker 默认 40px，收成与输入框一致的 32px
  ::v-deep .el-color-picker__trigger {
    width: 32px;
    height: 32px;
    padding: 4px;
  }
}

.icon-box {
  height: 460px;
}
</style>
