<template>
  <el-select v-bind="$attrs" :model-value="modelValue" :loading="isLoading" filterable remote clearable
    :remote-method="remoteSearch" :placeholder="placeholder" @change="handleChange"
    @visible-change="handleVisibleChange">
    <el-option v-for="item in options" :key="item.id" :label="item.contract_no" :value="item.id" />
  </el-select>
</template>

<script>
import { ref } from 'vue'
import { clientContractListApi } from '@/api/enterprise.ts'
import { useLoading } from '@/composables/useLoading.ts'

export default {
  name: 'OrderSelect',
  inheritAttrs: false,
  emits: ['update:modelValue', 'change', 'select'],
  props: {
    modelValue: {
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
      emit('update:modelValue', val)
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
