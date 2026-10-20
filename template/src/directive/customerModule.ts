import type { Directive } from 'vue';
import pinia from '@/stores';
import { useAppConfigStore } from '@/stores/appConfig';

const customerModule: Directive<HTMLElement, string> = {
  mounted(el, binding) {
    const { value } = binding;
    if (value && !useAppConfigStore(pinia).isCustomerModuleEnabled(value)) {
      el.parentNode && el.parentNode.removeChild(el);
    }
  },
};

export default customerModule;
