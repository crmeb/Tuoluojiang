import { ref, getCurrentInstance } from 'vue';

export const useContract = () => {
  const checkContractRef = ref(null);
  const contractFromData = ref({});

  const { proxy } = getCurrentInstance();

  const openCheckContractPanel = (contractId) => {

    Object.assign(contractFromData.value, {
      title: proxy.$t('contract.editcustomer'),
      width: '1100px',
      data: {},
      link_type: 'customer',
      types: 'customer',
      type: 'add'
    });

    if (!checkContractRef.value) return;
    checkContractRef.value.tabIndex = '1'
    checkContractRef.value.tabNumber = 1
    checkContractRef.value.openBox({
      id: contractId
    });
  }

  return {
    checkContractRef,
    contractFromData,

    openCheckContractPanel
  };
}