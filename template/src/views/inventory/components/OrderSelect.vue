<template>
  <el-select v-bind="$attrs" :value="value" :loading="isLoading" filterable remote clearable
    :remote-method="remoteSearch" :placeholder="placeholder" @change="handleChange"
    @visible-change="handleVisibleChange">
    <el-option v-for="item in options" :key="item.id" :label="item.contract_no" :value="item.id" />
  </el-select>
</template>

<script>
import { ref } from 'vue'
import { clientContractListApi } from '@/api/enterprise'
import { useLoading } from '@/composables/useLoading'

export default {
  name: 'OrderSelect',
  inheritAttrs: false,
  model: {
    prop: 'value',
    event: 'change'
  },
  props: {
    value: {
      type: [String, Number],
      default: ''
    },
    placeholder: {
      type: String,
      default: '请输入订单编号'
    },
    // 初始加载时的订单编号搜索词：入口带订单进入时传入，保证预选订单在选项中以正确回显编号
    defaultKeyword: {
      type: String,
      default: ''
    }
  },
  setup(props, { emit }) {
    const PAGE_SIZE = 10

    const { isLoading, startLoading, stopLoading } = useLoading()
    const options = ref([])

    // 拉取订单列表，contract_no 为可选的订单编号搜索关键字
    const fetchOptions = (contract_no = '') => {
      startLoading()
      clientContractListApi({ page: 1, limit: PAGE_SIZE, contract_no })
        .then((res) => {
          options.value = res.data.list || []
        })
        .finally(() => {
          stopLoading()
        })
    }

    // 远程搜索
    const remoteSearch = (query) => {
      fetchOptions(query.trim())
    }

    const handleChange = (val) => {
      emit('change', val)
      // 同步回传选中订单项，便于父级取订单编号（contract_no）等字段
      emit('select', options.value.find((item) => item.id === val) || null)
    }

    // 下拉展开且无关键字时，回填默认 10 条数据
    const handleVisibleChange = (visible) => {
      if (visible && !options.value.length) {
        fetchOptions()
      }
    }

    // 默认加载 10 条数据（有初始搜索词时按其过滤，确保预选订单命中）
    fetchOptions((props.defaultKeyword || '').trim())

    return {
      isLoading,
      options,
      remoteSearch,
      handleChange,
      handleVisibleChange
    }
  }
}
</script>

<style scoped lang="scss">
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
</style>
