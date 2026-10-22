import { ref, reactive, getCurrentInstance } from 'vue';
import {
  clientInvoiceDetailApi
} from '@/api/client'

export const useInvoice = () => {
  const invoiceEditRef = ref(null);
  const invoiceFromData = ref({});

  const { proxy } = getCurrentInstance();

  const openInvoicePanel = async (invoiceId) => {
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
      proxy.$message.error(error.message);
    }
  }

  return {
    invoiceEditRef,
    invoiceFromData,

    openInvoicePanel
  };
}