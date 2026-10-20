import { modalSure } from '@/libs/public'
import { ref } from 'vue'
import { useTable } from '@/composables/useTable'
import { getChannelCodeApi, delChannelCodeApi, putGroupChatStatus } from '@/api/weCom'
import type { ChannelCodeRow } from './types'

/**
 * 企微渠道活码（自动拉群）列表逻辑
 *
 * 搜索表单只承载 oaFromBox 的字段（name/time），
 * 页签外条件（types、状态筛选、左侧分组）走 extraParams 合并进请求。
 */
export const useChannelCodeList = () => {

  // extraParams 依赖的条件需在 useTable 之前声明（immediate 请求当场就会取值）
  const status = ref<number | string>('')
  const groupId = ref<number | string>('')

  const table = useTable(getChannelCodeApi, {
    extraParams: () => ({
      types: '0',
      status: status.value,
      group_id: groupId.value
    })
  })

  const searchFields = [
    {
      form_value: 'input',
      field_name_en: 'name',
      field_name: '渠道活码名称'
    },
    {
      form_value: 'select',
      field_name_en: 'invite_group',
      field_name: '邀请建群状态',
      data_dict: [
        { value: 1, name: '开启' },
        { value: 0, name: '关闭' }
      ]
    },
    {
      form_value: 'select',
      field_name_en: 'auto_create_room',
      field_name: '自动建群状态',
      data_dict: [
        { value: 1, name: '开启' },
        { value: 0, name: '关闭' }
      ]
    },
    {
      form_value: 'date_picker',
      field_name_en: 'time',
      field_name: '创建时间'
    }
  ]

  // oaFromBox 左上角开启状态下拉
  const treeData = [
    {
      options: [
        { label: '全部', id: '', value: '' },
        { label: '开启', id: 1, value: 1 },
        { label: '关闭', id: 0, value: 0 }
      ]
    }
  ]

  const tableColumns = [
    { field: 'qr_code', name: '渠道活码', minWidth: 120, slot: 'qr_code', fixed: 'left' },
    { field: 'name', name: '渠道活码名称', minWidth: 120, fixed: 'left' },
    { field: 'users', name: '使用成员', minWidth: 120, slot: 'users' },
    { field: 'tags', name: '标签', minWidth: 160, slot: 'tags' },
    { field: 'group_names', name: '群聊', minWidth: 190, slot: 'group_names' },
    { field: 'auto_create_room', name: '自动建群', minWidth: 100, slot: 'auto_create_room' },
    { field: 'status', name: '开启状态', minWidth: 100, slot: 'status' },
    { field: 'creator', name: '创建人', minWidth: 100, slot: 'creator' },
    { field: 'created_at', name: '创建时间', minWidth: 150 },
    { field: 'action', name: '操作', width: 300, slot: 'action', fixed: 'right' }
  ]

  // 搜索 / 重置；重置时状态筛选与每页条数一并还原（分组选中态保留）
  const handleSearch = (value?: Record<string, any> | 'reset', resetState?: Record<string, any>) => {
    if (value === 'reset') {
      status.value = ''
      table.pageSize.value = 15
    }
    table.handleSearch(value, resetState)
  }

  // 开启状态下拉变化，回到第 1 页按当前搜索条件重查
  const handleStatusChange = (data: { value: number | string }) => {
    status.value = data.value
    table.handleSearch()
  }

  // 左侧分组切换，保留搜索关键字重查
  const handleGroupChange = (id: number | string) => {
    groupId.value = id
    table.handleSearch()
  }

  // 快捷开关：关闭需二次确认，取消或请求失败均回滚开关状态
  const handleSwitchStatus = async (row: ChannelCodeRow & { status: number }) => {
    if (row.status === 0) {
      try {
        await modalSure('关闭后二维码将失效，确认关闭吗')
      } catch (e) {
        row.status = 1
        return
      }
    }
    putGroupChatStatus(row.id, row.status).catch(() => {
      row.status = row.status === 1 ? 0 : 1
    })
  }

  const handleDelete = async (row: ChannelCodeRow) => {
    try {
      await modalSure('删除后二维码将无法使用，您确定要删除吗')
    } catch (e) {
      return
    }
    await delChannelCodeApi(row.id)
    if (table.page.value > 1 && table.tableData.value.length <= 1) {
      // 当页删空时回退页码，watch 会自动重查
      table.page.value--
    } else {
      table.fetchData()
    }
  }

  return {
    ...table,
    handleSearch,

    groupId,
    searchFields,
    treeData,
    tableColumns,

    handleStatusChange,
    handleGroupChange,
    handleSwitchStatus,
    handleDelete
  }
}
