import Vue from "vue";

// Parse invitation params before the app bootstraps.
import "./bootstrap/invitation";

// Configure moment locale before any UI modules format dates.
import "./bootstrap/moment";

// Load global styles before any UI modules.
import "./bootstrap/styles";

// Core app modules.
import App from "./App";
import store from "./store";
import router from "./router";
import i18n from "./lang";

// Bootstrap registrations.
import { registerPlugins } from "./bootstrap/plugins";
import { registerComponents } from "./bootstrap/global-components";
import { registerSideEffects } from "./bootstrap/side-effects";
import { EventBus } from "./libs/bus";

// Lazy-loaded extensions.
import "./bootstrap/dynamic-imports";

// Third-party scripts.
import "./bootstrap/third-party";

// Notification bootstrap.
import { initNotification } from "./bootstrap/notification";

registerPlugins(Vue, i18n);
registerComponents(Vue);
registerSideEffects(Vue, router, store);

const _notice = initNotification(store);
Vue.config.productionTip = false;

export default new Vue({
  el: "#app",
  router,
  data: {
    notice: _notice,
  },
  methods: {
    closeNotice() {
      this.notice && this.notice();
    },
    restartNotice() {
      this.closeNotice();
      this.notice = initNotification(store);
    },
  },
  created() {
    EventBus.$on("auth-token-updated", this.restartNotice);
  },
  beforeDestroy() {
    EventBus.$off("auth-token-updated", this.restartNotice);
  },
  store,
  i18n,
  render: (h) => h(App),
});
