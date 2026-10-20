import { ref, getCurrentInstance } from 'vue';

/** 发票查看侧滑组件的实例方法 */
interface InvoicePanel {
  openBox: (id: number | string) => void
}
import {
  clientInvoiceDetailApi
} from '@/api/client'

export const useInvoice = () => {
  const invoiceEditRef = ref<InvoicePanel | null>(null);
  const invoiceFromData = ref<Record<string, unknown>>({});

  const proxy = getCurrentInstance()!.proxy!;

  const openInvoicePanel = async (invoiceId: number | string) => {
    if (!invoiceEditRef.value) return;
    try {
      const res = await clientInvoiceDetailApi(invoiceId);

      invoiceFromData.value = {
        title: '发票查看',
        width: '1000px',
        data: res.data,
      };

      invoiceEditRef.value.openBox(invoiceId);
    } catch (error) {
      proxy.$message.error((error as Error).message);
    }
  }

  return {
    invoiceEditRef,
    invoiceFromData,

    openInvoicePanel
  };
}