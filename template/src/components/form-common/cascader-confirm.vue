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
  props: {
    value: {
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
    size: {
      type: String,
      default: 'small'
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
  data() {
    return {
      tempValue: [],
      confirmedValue: [],
      confirmBtn: null,
      confirmBtnEl: null,
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
        if (this.$refs.cascader && !this.$refs.cascader.dropDownVisible) {
          this.syncValue()
        }
      },
      deep: true
    }
  },
  beforeDestroy() {
    this.removeConfirmBtn()
  },
  methods: {
    onVisibleChange(visible) {
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
      this.$emit('input', this.confirmedValue)
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
      btn.className = 'el-button el-button--primary el-button--mini'
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
      this.$refs.cascader.dropDownVisible = false
    }
  }
}
</script>
