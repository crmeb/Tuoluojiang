import { ref, getCurrentInstance } from 'vue';

/** 客户编辑侧滑组件的实例方法 */
interface CustomerPanel {
  tabIndex: string
  tabNumber: number
  openBox: (id: number | string) => void
}
import {
  chargeEditApi
} from '@/api/enterprise'

export const useCustomer = () => {
  const customerEditRef = ref<CustomerPanel | null>(null);
  const customerFromData = ref<Record<string, unknown>>({});

  const proxy = getCurrentInstance()!.proxy!;

  const openCustomerPanel = async (customerId: number | string) => {
    try {
      const res = await chargeEditApi(customerId);

      Object.assign(customerFromData.value, {
        title: '编辑客户',
        width: '1100px',
        data: res.data.data,
        isClient: true,
        edit: true
      });

      if (!customerEditRef.value) return;
      customerEditRef.value.tabIndex = '1'
      customerEditRef.value.tabNumber = 1
      customerEditRef.value.openBox(customerId);
    } catch (error) {
      proxy.$message.error((error as Error).message);
    }
  }

  return {
    customerEditRef,
    customerFromData,

    openCustomerPanel
  };
}