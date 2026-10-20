import { ref, type Ref } from "vue";
import type { ApiResult, Id } from "@/types/api";
import { useLoading } from "./useLoading";

/**
 * 通用详情抽屉逻辑：开关 + 按 id 拉取详情 + loading。
 *
 * 适用于「基本信息 + 商品列表」这类详情侧滑弹窗，业务侧只需传入详情接口，
 * 通过 defineExpose / ctx.expose 暴露 open(id) 给列表页调用即可。
 *
 * @param {Function} fetchApi 详情接口，接收 id，返回 Promise<{ data }>
 * @returns {{ isOpen, isLoading, detail, open, close }}
 */
export const useDetailDrawer = <T = any>(fetchApi: (id: Id) => Promise<ApiResult<T>>) => {
  const { isLoading, startLoading, stopLoading } = useLoading();

  const isOpen = ref(false);
  const detail = ref(null) as Ref<T | null>;

  const open = async (id: Id) => {
    isOpen.value = true;
    detail.value = null;
    startLoading();
    try {
      const res = await fetchApi(id);
      detail.value = res.data;
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopLoading();
    }
  };

  const close = () => {
    isOpen.value = false;
  };

  return {
    isOpen,
    isLoading,
    detail,

    open,
    close
  };
};
