import { ref } from 'vue'
import { getChannelCodeGroupApi } from '@/api/weCom'

/**
 * 渠道活码分组列表逻辑，供分组侧栏、批量移动分组弹窗等复用
 *
 * groupList 为后端原始分组数组（不含「全部」项），使用方按需自行拼装
 */
export const useChannelCodeGroup = () => {
  const groupList = ref<Array<{ id: number; name: string }>>([])

  const fetchGroupList = async () => {
    const res = await getChannelCodeGroupApi()
    groupList.value = res.data.list || []
  }

  return { groupList, fetchGroupList }
}
