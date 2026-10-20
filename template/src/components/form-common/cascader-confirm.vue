<template>
  <el-cascader
    ref="cascader"
    v-model="tempValue"
    :options="options"
    :props="cascaderProps"
    :placeholder="placeholder"
    :clearable="clearable"
    :collapse-tags="collapseTags"
    :size="size"
    :style="cascaderStyle"
    :filterable="filterable"
    popper-class="cascader-confirm-popper"
    @visible-change="onVisibleChange"
    @remove-tag="onRemoveTag"
  />
</template>

<script>
export default {
  name: 'CascaderConfirm',
  emits: ['update:modelValue', 'change'],
  props: {
    modelValue: {
      type: [Array, Number, String],
      default: () => null
    },
    options: {
      type: Array,
      default: () => []
    },
    cascaderProps: {
      type: Object,
      default: () => ({})
    },
    placeholder: {
      type: String,
      default: '请选择'
    },
    clearable: {
      type: Boolean,
      default: true
    },
    collapseTags: {
      type: Boolean,
      default: true
    },
    // element-ui 的 small（32px）对应 Element Plus 的 default，EP 的 small 只有 24px
    size: {
      type: String,
      default: 'default'
    },
    cascaderStyle: {
      type: [String, Object],
      default: ''
    },
    filterable: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
  },
  data() {
    return {
      tempValue: [],
      confirmedValue: [],
      confirmBtn: null,
      confirmBtnEl: null,
      isDropdownVisible: false,
      isInternalReset: false
    }
  },
  watch: {
    value: {
      handler(val) {
        this.isInternalReset = true
        const newVal = val ?? []
        this.confirmedValue = JSON.parse(JSON.stringify(newVal))
        this.tempValue = JSON.parse(JSON.stringify(newVal))
        this.$nextTick(() => {
          this.isInternalReset = false
        })
      },
      immediate: true,
      deep: true
    },
    tempValue: {
      handler(newVal) {
        if (this.isInternalReset) return
        // 下拉面板关闭时的值变化来自 tag 删除或清空按钮，立即同步
        if (!this.isDropdownVisible) {
          this.syncValue()
        }
      },
      deep: true
    }
  },
  beforeUnmount() {
    this.removeConfirmBtn()
  },
  methods: {
    onVisibleChange(visible) {
      this.isDropdownVisible = visible
      if (visible) {
        this.isInternalReset = true
        this.tempValue = JSON.parse(JSON.stringify(this.confirmedValue))
        this.$nextTick(() => {
          this.isInternalReset = false
          this.injectConfirmBtn()
        })
      } else {
        this.isInternalReset = true
        this.tempValue = JSON.parse(JSON.stringify(this.confirmedValue))
        this.$nextTick(() => {
          this.isInternalReset = false
        })
        this.removeConfirmBtn()
      }
    },

    onRemoveTag() {
      // 处理下拉面板打开时的 tag 删除，用 $nextTick 确保 tempValue 已更新
      this.$nextTick(() => {
        if (!this.isInternalReset) {
          this.syncValue()
        }
      })
    },

    syncValue() {
      const newVal = JSON.parse(JSON.stringify(this.tempValue))
      // 值未变化时跳过，防止重复 emit
      if (JSON.stringify(newVal) === JSON.stringify(this.confirmedValue)) return
      this.confirmedValue = newVal
      this.$emit('update:modelValue', this.confirmedValue)
      this.$emit('change', this.confirmedValue)
    },

    injectConfirmBtn() {
      const poppers = document.querySelectorAll('.cascader-confirm-popper')
      if (!poppers.length) return

      const popper = poppers[poppers.length - 1]
      if (popper.querySelector('.cascader-confirm-btn-wrapper')) return

      const wrapper = document.createElement('div')
      wrapper.className = 'cascader-confirm-btn-wrapper'
      wrapper.style.cssText = 'padding: 6px 12px; text-align: right; border-top: 1px solid #EBEEF5;'

      const btn = document.createElement('button')
      btn.textContent = '确定'
      btn.className = 'el-button el-button--primary el-button--small'
      btn.style.cssText = 'margin: 0;'
      btn.addEventListener('click', this.onConfirm)

      wrapper.appendChild(btn)
      popper.appendChild(wrapper)
      this.confirmBtn = wrapper
      this.confirmBtnEl = btn
    },

    removeConfirmBtn() {
      if (this.confirmBtn && this.confirmBtn.parentNode) {
        this.confirmBtnEl.removeEventListener('click', this.onConfirm)
        this.confirmBtn.parentNode.removeChild(this.confirmBtn)
        this.confirmBtn = null
        this.confirmBtnEl = null
      }
    },

    onConfirm() {
      this.syncValue()
      this.$refs.cascader.togglePopperVisible(false)
    }
  }
}
</script>
