import { ref } from "vue";

/**
 * 分页状态
 *
 * @param {number} [initialPageSize=15] 初始每页条数，卡片流等非表格列表可按栅格传入其它值
 */
export const usePage = (initialPageSize = 15) => {
  const page = ref(1);
  const pageSize = ref(initialPageSize);
  const total = ref(0);

  const resetPage = () => {
    page.value = 1;
  }

  return {
    page,
    pageSize,
    total,

    resetPage
  };
}