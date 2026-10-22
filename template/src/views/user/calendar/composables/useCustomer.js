import { ref, getCurrentInstance } from 'vue';
import {
  chargeEditApi
} from '@/api/enterprise'

export const useCustomer = () => {
  const customerEditRef = ref(null);
  const customerFromData = ref({});

  const { proxy } = getCurrentInstance();

  const openCustomerPanel = async (customerId) => {
    try {
      const res = await chargeEditApi(customerId);

      Object.assign(customerFromData.value, {
        title: proxy.$t('customer.editcustomer'),
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
      proxy.$message.error(error.message);
    }
  }

  return {
    customerEditRef,
    customerFromData,

    openCustomerPanel
  };
}