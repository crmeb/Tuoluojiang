import type { App } from "vue";
import VueLazyload from "vue-lazyload";
import ElementPlus from "element-plus";
import zhCn from "element-plus/es/locale/lang/zh-cn";
import height from "@/directive/height";
import resize from "@/directive/resize";
import SmartWidget from "@/components/smart-widget";
import directive from "@/directive";
import { registerElementIcons } from "./element-icons";
import { formI18nPlugin } from "@/utils/i18n";
import { toPickerProps } from "@/utils/picker-options";
import { draggableKey } from "@/utils/draggable-key";

import errorImg from "@/assets/images/no.png";
import loadingImg from "@/assets/images/moren.jpg";

export function registerPlugins(app: App) {
  // 日期选择器的 picker-options 兼容层，见 utils/picker-options
  app.config.globalProperties.$pickerProps = toPickerProps;
  // vuedraggable 4 的 item-key：列表项没有稳定 id 时用它生成
  app.config.globalProperties.$draggableKey = draggableKey;

  app.use(ElementPlus, {
    zIndex: 1000,
    locale: zhCn,
  });

  app.use(VueLazyload, {
    preLoad: 1.3,
    error: errorImg,
    loading: loadingImg,
    attempt: 1,
    listenEvents: ["scroll", "wheel", "mousewheel", "resize", "animationend", "transitionend", "touchmove"],
  });

  app.use(formI18nPlugin);
  registerElementIcons(app);
  app.use(SmartWidget);
  app.use(resize);
  app.use(height);
  app.use(directive);
}
