import permission from './permission/permission';
import hasPermi from './permission/hasPermi';
import boxSelection from './box-selection';
import customerModule from './customerModule';
import defaultAvatar from './defaultAvatar';
import warehousePermi from './warehousePermi';

const install = function (Vue) {
  Vue.directive('permission', permission);
  Vue.directive('hasPermi', hasPermi);
  Vue.directive('box-selection', boxSelection);
  Vue.directive('customer-module', customerModule);
  Vue.directive('default-avatar', defaultAvatar);
  Vue.directive('warehouse-permi', warehousePermi);
};

if (window.Vue) {
  window['permission'] = permission;
  window['hasPermi'] = hasPermi;
  Vue.use(install); // eslint-disable-line
}

// permission.install = install;
// export default permission;
export default install;
