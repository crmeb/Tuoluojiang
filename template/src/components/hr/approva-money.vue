<!-- @FileDescription: 人事-审批设置-金额组件 -->
<template>
  <div>
    <el-input-number
      v-model="num"
      :min="minNum"
      :max="maxNum"
      :precision="precisionNum"
      :step="Math.pow(10, precisionNum ? -precisionNum : 0)"
    ></el-input-number>
    <div class="daxie">大写：{{ numFrom }}</div>
  </div>
</template>

<script>
import { intToChinese } from '@/utils/index.ts'
export default {
  emits: ['update:modelValue'],
  name: 'MoneyFrom',
  props: {
    modelValue: {
      default: undefined,
      type: [Number, String]
    },
    min: {
      default: undefined,
      type: Number
    },
    max: {
      default: undefined,
      type: Number
    },
    precision: {
      default: undefined,
      type: Number
    }
  },
  data() {
    return {
      num: Number(this.modelValue),
      minNum: this.min,
      maxNum: this.max,
      precisionNum: this.precision
    }
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
    numFrom: function () {
      return intToChinese(this.num)
    }
  },
  watch: {
    value(n) {
      this.num = Number(n)
    },
    min(n) {
      this.minNum = n
    },
    max(n) {
      this.maxNum = n
    },
    precision(n) {
      this.precisionNum = n
    },
    num() {
      this.onChange()
    }
  },
  methods: {
    onChange() {
      this.$emit('update:modelValue', this.num)
    }
  }
}
</script>

<style scoped lang="scss">
.daxie {
  height: 24px;
  color: #999;
  font-size: 13px;
}
</style>
