import { createApp } from "vue";

// Parse invitation params before the app bootstraps.
import "./bootstrap/invitation";

// Configure dayjs plugins and locale before any UI modules format dates.
import "./bootstrap/dayjs";

// Load global styles before any UI modules.
import "./bootstrap/styles";

// Core app modules.
import App from "./App.vue";
import pinia from "./stores";
import router from "./router";
import { setApp } from "./app-context";
import { IS_DEV } from "./config/env";

// Bootstrap registrations.
import { registerPlugins } from "./bootstrap/plugins";
import { registerComponents } from "./bootstrap/global-components";
import { registerSideEffects } from "./bootstrap/side-effects";
import { EventBus } from "./libs/bus";

// Notification bootstrap.
import { restartNotice } from "./bootstrap/notification";

const app = createApp(App);

app.use(pinia);
// 在安装路由前注册权限守卫，不依赖 vue-router 延迟读取守卫列表的实现细节。
registerSideEffects(app, router);
app.use(router);
registerPlugins(app);
registerComponents(app);
setApp(app);

// Third-party scripts.
import("./bootstrap/third-party");

restartNotice();
EventBus.$on("auth-token-updated", () => restartNotice());

app.mount("#app");

// 仅开发环境暴露，便于在浏览器控制台 / DevTools 脚本里调试路由与全局状态
if (IS_DEV) window.__APP__ = app;

export default app;
