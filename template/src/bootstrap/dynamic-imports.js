import Vue from "vue";

import("@/views/business/components/formSetting/components/form-create-designer/src/index")
  .then(({ formCreate }) => Vue.use(formCreate));
import("@/views/system/dashboard-design/charts/charts-loader")
  .then(({ loadChartsExtension }) => loadChartsExtension(Vue));
