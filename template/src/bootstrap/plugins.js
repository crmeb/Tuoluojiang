import Cookies from "js-cookie";
import VueLazyload from "vue-lazyload";
import Element from "element-ui";
import VueSmartWidget from "vue-smart-widget";
import vResize from "@theshy/v-resize";
import height from "@/directive/height";
import directive from "@/directive";

import errorImg from "@/assets/images/no.png";
import loadingImg from "@/assets/images/moren.jpg";

export function registerPlugins(Vue, i18n) {
  Vue.use(Element, {
    size: Cookies.get("size") || "medium",
    zIndex: 1000,
    i18n: (key, value) => i18n.t(key, value),
  });

  Vue.use(VueLazyload, {
    preLoad: 1.3,
    error: errorImg,
    loading: loadingImg,
    attempt: 1,
    listenEvents: ["scroll", "wheel", "mousewheel", "resize", "animationend", "transitionend", "touchmove"],
  });

  Vue.use(VueSmartWidget);
  Vue.use(vResize);
  Vue.use(height);
  Vue.use(directive);
}
