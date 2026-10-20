import type { App } from 'vue';
import hasPermi from './permission/hasPermi';
import boxSelection from './box-selection';
import customerModule from './customerModule';
import defaultAvatar from './defaultAvatar';
import warehousePermi from './warehousePermi';

const install = function (app: App) {
  app.directive('hasPermi', hasPermi);
  app.directive('box-selection', boxSelection);
  app.directive('customer-module', customerModule);
  app.directive('default-avatar', defaultAvatar);
  app.directive('warehouse-permi', warehousePermi);
};

export default install;
