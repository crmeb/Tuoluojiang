import { ref, watch, type Ref } from "vue";
import type { ApiResult } from "@/types/api";
import { usePage } from "./usePage";
import { useLoading } from "./useLoading";

/**
 * 通用列表页逻辑：组合分页 + loading + 数据请求
 *
 * 业务侧只需把「列表接口」和「初始搜索表单」传进来，分页切换会自动重拉，
 * 搜索 / 重置由 handleSearch 主动触发，返回的全部为顶层 ref（模板内自动解包）。
 *
 * 搜索表单之外的查询条件（页签、勾选等）通过 extraParams 传入，请求时合并，
 * 这类条件变化后调用 handleSearch()（不带参）即可回到第 1 页按当前条件重查。
 *
 * @param {Function} fetchApi 列表接口，接收查询参数，返回 Promise<{ data: { list, count } }>
 * @param {Object}   [options]
 * @param {Function} [options.generateSearchForm] 生成初始 / 重置时的搜索表单，默认空对象
 * @param {boolean}  [options.immediate=true]     是否在初始化时立即拉取一次
 * @param {Function} [options.extraParams]        返回搜索表单以外的查询参数，每次请求时调用并合并；
 *                                                immediate 为 true 时在 useTable 调用当场即会执行，
 *                                                其依赖的 ref 需在 useTable 之前声明
 * @param {number}   [options.pageSize=15]        初始每页条数
 */
/** 列表接口的分页响应结构 */
export interface PageData<T> {
  list: T[];
  count: number;
}

export interface UseTableOptions<S> {
  /** 生成初始 / 重置时的搜索表单，默认空对象 */
  generateSearchForm?: () => S;
  /** 是否在初始化时立即拉取一次 */
  immediate?: boolean;
  /** 返回搜索表单以外的查询参数，每次请求时调用并合并 */
  extraParams?: () => Record<string, unknown>;
  /** 初始每页条数 */
  pageSize?: number;
}

export const useTable = <T = any, S extends object = Record<string, any>>(
  fetchApi: (params: S & { page: number; limit: number }) => Promise<ApiResult<PageData<T>>>,
  options: UseTableOptions<S> = {}
) => {
  const { generateSearchForm = () => ({}) as S, immediate = true, extraParams, pageSize = 15 } = options;

  const { resetPage, ...pageInfo } = usePage(pageSize);
  const { isLoading, startLoading, stopLoading } = useLoading();

  const searchForm = ref(generateSearchForm()) as Ref<S>;
  const tableData = ref([]) as Ref<T[]>;

  const fetchData = async () => {
    if (isLoading.value) return;
    startLoading();
    try {
      const params = {
        ...searchForm.value,
        ...(extraParams ? extraParams() : {}),
        page: pageInfo.page.value,
        limit: pageInfo.pageSize.value
      };
      const res = await fetchApi(params);
      tableData.value = res.data.list;
      pageInfo.total.value = res.data.count;
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopLoading();
    }
  };

  // 不带参调用表示搜索表单不变（如 extraParams 依赖的条件变化），仅回到第 1 页重查
  // resetState 为搜索栏组件重置后回传的默认状态（排序、视图等），重置时需合并进搜索表单，
  // 否则重置后的请求会丢掉这些参数，与首次加载不一致
  const handleSearch = (value?: S | "reset", resetState?: Partial<S>) => {
    resetPage();
    if (value !== undefined) {
      searchForm.value =
        value === "reset" ? { ...generateSearchForm(), ...resetState } : value;
    }
    // page 本就是 1 时 watch 不会触发，需主动拉；若 page 被重置（如从第 3 页搜索），
    // watch 也会触发，但 fetchData 内 isLoading 守卫会拦掉重复请求
    fetchData();
  };

  // 标记本次 page 变更由「每页条数变化」联动产生，请求已由 pageSize 的 watch 发出，
  // 下方 page 的 watch 需跳过，避免重复请求
  let isPageResetBySize = false;

  // 每页条数变化：当前页可能超出新的总页数（如 19 条数据停在第 2 页时改成 30 条/页，
  // 若仍按 page=2 请求会拿到空列表），越界时先回到第 1 页再请求；未越界则保持当前页
  watch(pageInfo.pageSize, () => {
    const pageCount = Math.ceil(pageInfo.total.value / pageInfo.pageSize.value);
    if (pageInfo.page.value > pageCount && pageInfo.page.value !== 1) {
      isPageResetBySize = true;
      pageInfo.page.value = 1;
    }
    fetchData();
  });

  // 页码变化时自动重新请求
  watch(pageInfo.page, () => {
    if (isPageResetBySize) {
      isPageResetBySize = false;
      return;
    }
    fetchData();
  });

  if (immediate) fetchData();

  return {
    ...pageInfo,
    searchForm,
    tableData,
    isLoading,

    resetPage,
    fetchData,
    handleSearch
  };
};
