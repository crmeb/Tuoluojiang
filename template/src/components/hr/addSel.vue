<!-- @FileDescription: 人事-审批设置-表单内容配置选项组件 -->
<template>
  <div>
    <draggable :list="activeData" :animation="340" group="selectItem" handle=".option-drag" @update="add1" :item-key="$draggableKey">
      <template #item="{ element: item, index }">
        <div class="select-item mb5">
          <div class="select-line-icon option-drag mr5">
            <i class="iconfont icontuodong" />
          </div>
          <el-input
            v-model="item.label"
            placeholder="请输入内容"
            class="mr5"
            @input="setOptionValue(item, $event)"
          ></el-input>
          <div class="close-btn select-line-icon" @click="activeData.splice(index, 1)">
            <i class="el-icon-remove-outline" />
          </div>
        </div>
      </template>
    </draggable>
    <div style="margin-left: 20px">
      <el-button style="padding-bottom: 0" icon="el-icon-circle-plus-outline" type="primary" link @click="addSelectItem"
        >添加选项</el-button
      >
    </div>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
export default {
  emits: ['update:modelValue'],
  name: 'AddSel',
  components: {
    draggable
  },
  props: {
    modelValue: {
      default: () => [],
      type: Array
    },
    disabled: Boolean
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
  },
  data() {
    return {
      activeData: this.modelValue
    }
  },
  watch: {
    value(n) {
      this.activeData = n
    }
  },
  methods: {
    add1() {
      this.$emit('update:modelValue', this.activeData)
    },
    addSelectItem() {
      this.activeData.push({
        label: '',
        value: ''
      })
      this.$emit('update:modelValue', this.activeData)
    },
    setOptionValue(item, val) {
      item.label = val
      item.value = val
      this.$emit('update:modelValue', this.activeData)
    }
  }
}
</script>

<style scoped lang="scss">
.icontuodong {
  cursor: pointer;
}
.select-item {
  display: flex;
  border: 1px dashed #fff;
  box-sizing: border-box;

  & .close-btn {
    cursor: pointer;
    color: #f56c6c;
  }

  & .el-input + .el-input {
    margin-left: 4px;
  }
}
</style>
