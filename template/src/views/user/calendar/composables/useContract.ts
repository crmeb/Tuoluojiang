import { ref } from 'vue';

/** 合同查看侧滑组件的实例方法 */
interface ContractPanel {
  tabIndex: string
  tabNumber: number
  openBox: (data: { id: number | string }) => void
}

export const useContract = () => {
  const checkContractRef = ref<ContractPanel | null>(null);
  const contractFromData = ref<Record<string, unknown>>({});

  const openCheckContractPanel = (contractId: number | string) => {

    Object.assign(contractFromData.value, {
      title: '查看合同',
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