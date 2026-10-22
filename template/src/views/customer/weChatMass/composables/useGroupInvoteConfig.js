import { modalSure } from '@/libs/public'
import {  } from 'vue'
import { useRouter } from 'vue-router/composables'
import { roterPre } from '@/settings'
import { useTable } from '@/composables/useTable'
import { groupInvoteConfigListApi, groupInvoteConfigDeleteApi } from '@/api/weCom'

// 自动建群配置表单页路由，列表页新增 / 编辑共用
export const GROUP_INVOTE_CONFIG_FORM_PATH = roterPre + '/customer/weChatMass/groupInvoteConfig/form'

/**
 * 自动建群配置列表逻辑：组合通用列表（分页 + loading + 请求） + 新增 / 编辑跳转 + 删除
 */
export const useGroupInvoteConfig = () => {
  const router = useRouter()

  // 接口只支持按配置名称（name）模糊搜索
  const searchFields = [
    {
      field_name: '群聊名称',
      field_name_en: 'name',
      form_value: 'input'
    }
  ]

  const tableColumns = [
    { field: 'qr_code', name: '群二维码', width: 100, slot: 'qr_code' },
    { field: 'group_chat', name: '群聊列表', minWidth: 200, slot: 'group_chat' },
    { field: 'room_base_name', name: '新群群聊名称', minWidth: 140, slot: 'room_base_name' },
    { field: 'chat_ids', name: '群聊数量', minWidth: 90, slot: 'chat_count' },
    { field: 'auto_create_room', name: '自动建群', minWidth: 100, slot: 'auto_create_room' },
    { field: 'room_base_id', name: '群聊序号', minWidth: 100, slot: 'room_base_id' },
    { field: 'created_at', name: '创建时间', minWidth: 160 },
    { field: 'action', name: '操作', minWidth: 190, slot: 'action' }
  ]

  const table = useTable(groupInvoteConfigListApi)

  const handleAdd = () => {
    router.push({ path: GROUP_INVOTE_CONFIG_FORM_PATH })
  }

  const handleToEditPage = (row) => {
    router.push({
      path: GROUP_INVOTE_CONFIG_FORM_PATH,
      query: { id: row.id }
    })
  }

  const handleDelete = (row) => {
    modalSure(`确认删除自动建群配置「${row.name || row.room_base_name}」吗`).then(async () => {
      const res = await groupInvoteConfigDeleteApi(row.id)
      // http 库业务失败（status 400 等）仍会 resolve，需显式判定后再刷新列表
      if (res.status !== 200) return
      // 删除的是当前页最后一条时回退一页，避免停在空页
      if (table.page.value > 1 && table.tableData.value.length <= 1) {
        table.page.value -= 1
        return
      }
      table.fetchData()
    })
  }

  return {
    ...table,

    searchFields,
    tableColumns,

    handleAdd,
    handleToEditPage,
    handleDelete
  }
}
