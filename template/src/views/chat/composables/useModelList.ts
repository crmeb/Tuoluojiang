import { modalSure } from '@/libs/public'
import { ref, computed } from 'vue'
import { useTable } from '@/composables/useTable'
import { getModelsOptionsApi, getModelsListApi, delModelsApi } from '@/api/chatAi'
import { MODEL_TYPE_OPTIONS, type ProviderOption } from '../utils/model'

// 每页条数的兜底初值，实际由 useGridPageSize 按容器宽高算出并覆盖
const PAGE_SIZE = 12

/**
 * 模型设置列表逻辑
 *
 * 搜索表单只承载 oaFromBox 的字段（模型名称、模型类别）；
 * 供应商选项用于卡片回显名称与 logo，并透传给新增/编辑弹窗。
 *
 * @param {Object}  [options]
 * @param {boolean} [options.immediate=true] 是否初始化就请求；每页条数按容器尺寸算时应传 false
 */
export const useModelList = (options: { immediate?: boolean } = {}) => {

  const providerOptions = ref<ProviderOption[]>([])

  const table = useTable(getModelsListApi, {
    pageSize: PAGE_SIZE,
    immediate: options.immediate !== false,
    generateSearchForm: () => ({ name: '', model_type: '' })
  })

  const searchFields = [
    {
      form_value: 'input',
      field_name_en: 'name',
      field_name: '模型名称'
    },
    {
      form_value: 'select',
      field_name_en: 'model_type',
      field_name: '模型类别',
      // formList 的 el-option 取 name / value，直接复用常量里的选项
      data_dict: [...MODEL_TYPE_OPTIONS]
    }
  ]

  // 列表接口只返回 provider，供应商名称与 logo 需按 provider 回查选项
  const providerMap = computed(() =>
    providerOptions.value.reduce<Record<string, ProviderOption>>((map, item) => {
      map[item.value] = item
      return map
    }, {})
  )

  const isEmpty = computed(() => !table.isLoading.value && table.tableData.value.length === 0)

  const getProviderOptions = async () => {
    const res = await getModelsOptionsApi()
    providerOptions.value = res.data || []
  }

  const handleDelete = async (row: { id: number }) => {
    try {
      await modalSure('确认删除此模型')
    } catch (e) {
      return
    }
    await delModelsApi(row.id)
    if (table.page.value > 1 && table.tableData.value.length <= 1) {
      // 当页删空时回退页码，watch 会自动重查
      table.page.value--
    } else {
      table.fetchData()
    }
  }

  getProviderOptions()

  return {
    ...table,

    providerOptions,
    providerMap,
    isEmpty,
    searchFields,

    getProviderOptions,
    handleDelete
  }
}
