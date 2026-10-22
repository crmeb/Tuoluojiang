<template>
  <div class="inline-edit-select">
    <div v-if="!editing" class="text-view" @click="handleTextClick">
      <div class="line1">{{ text || '- -' }}</div>
    </div>
    <el-select
      v-else
      ref="selectRef"
      v-model="currentValue"
      size="small"
      automatic-dropdown
      :placeholder="placeholder"
      @change="handleChange"
      @visible-change="handleVisibleChange"
    >
      <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value">
        <slot name="option" :item="item">{{ item.label }}</slot>
      </el-option>
    </el-select>
  </div>
</template>

<script>
import { ref, nextTick } from 'vue'

/**
 * 行内编辑下拉组件
 *
 * 默认展示 text 文本，点击后切换为 el-select 并自动展开下拉；
 * 选中后抛出 change 事件交给外部处理，自身切回文本态；
 * 点击组件外部时依赖 el-select 自带的 clickoutside 收起下拉，
 * 通过 visible-change 事件切回文本态，无需手动挂全局监听。
 */
export default {
  name: 'inlineEditSelect',
  props: {
    // 当前选中值，进入编辑态时用于下拉回显定位（注意与 options 的 value 类型保持一致）
    value: {
      type: [String, Number],
      default: ''
    },
    // 文本态展示的内容
    text: {
      type: String,
      default: ''
    },
    // 下拉选项，{ label, value } 结构
    options: {
      type: Array,
      default: () => []
    },
    // 是否允许进入编辑态，false 时点击文本抛出 disabled-click 事件
    editable: {
      type: Boolean,
      default: true
    },
    placeholder: {
      type: String,
      default: '请选择'
    }
  },
  setup(props, { emit }) {
    const editing = ref(false)
    const currentValue = ref('')
    const selectRef = ref(null)

    const handleTextClick = () => {
      if (!props.editable) {
        emit('disabled-click')
        return
      }
      currentValue.value = props.value
      editing.value = true
      emit('open')
      nextTick(() => {
        const select = selectRef.value
        if (select) {
          select.focus()
          // 刚挂载的 select 调 focus 不一定触发 automatic-dropdown，手动兜底展开
          if (!select.visible) select.toggleMenu()
        }
      })
    }

    const handleChange = (val) => {
      emit('change', val)
      editing.value = false
    }

    const handleVisibleChange = (visible) => {
      if (!visible) {
        editing.value = false
      }
    }

    return {
      editing,
      currentValue,
      selectRef,
      handleTextClick,
      handleChange,
      handleVisibleChange
    }
  }
}
</script>

<style lang="scss" scoped>
.inline-edit-select {
  .text-view {
    cursor: pointer;
  }
  .el-select {
    width: 100%;
  }
}
</style>
