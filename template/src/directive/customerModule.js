import store from '@/store';

export default {
  inserted(el, binding) {
    const { value } = binding;
    const isCustomerModuleEnabled = store.getters['appConfig/isCustomerModuleEnabled'];

    if (value && !isCustomerModuleEnabled(value)) {
      el.parentNode && el.parentNode.removeChild(el);
    }
  },
};
