/**
 * OA 对 AI SDK 的轻量适配层。
 * OA 只管理自身的开关、路由显隐和页面级实例生命周期。
 */

import pinia from "@/stores";
import { useAppConfigStore } from "@/stores/appConfig";
import { roterPre } from "@/settings";
import {
  loadAiSdk,
  getAiBaseUrl,
  type GyroChatInstance,
  type GyroChatUrlOptions
} from "./plugin-loader";

// AI 侧站点地址，外部应用的嵌入代码要把 SDK 脚本地址写死给第三方站点
export { getAiBaseUrl };

interface Destroyable {
  destroy(): void;
}

/** 嵌入式实例的挂载目标 */
type MountTarget = string | HTMLElement;

let floatWidget: GyroChatInstance | null = null;
let floatTask: Promise<GyroChatInstance | null> | null = null;
let floatRevision = 0;
const pageWidgets = new Set<Destroyable>();

function isEnabled(value: unknown) {
  return value === "1" || value === 1 || value === true;
}

function getFloatConfig() {
  const config: { ai_status?: unknown; ai_image?: string } = useAppConfigStore(pinia).configData || {};
  return {
    enabled: isEnabled(config.ai_status),
    // 没配图标就交给 SDK 兜底，默认图标只保留 SDK 一份
    floatIcon: config.ai_image || undefined
  };
}

function shouldHideFloat(path = "") {
  return [
    `${roterPre}/chat/`,
    `${roterPre}/setting/uploadPicture`,
    `${roterPre}/setting/icons`,
    `${roterPre}/setting/auth`
  ].some(prefix => path.startsWith(prefix));
}

function track<T extends Destroyable>(instance: T, collection: Set<Destroyable>): T {
  const destroy = instance.destroy.bind(instance);
  let destroyed = false;
  instance.destroy = () => {
    if (destroyed) return;
    destroyed = true;
    collection.delete(instance);
    destroy();
  };
  collection.add(instance);
  return instance;
}

/**
 * 创建实例。
 *
 * token 属于实例身份而不是全局状态，同页实例可以使用不同身份。
 * AI 地址由 SDK 脚本自身的 origin 决定，不再重复下发。
 */
async function createChatInstance(options: Record<string, unknown>) {
  const GyroChat = await loadAiSdk();
  return GyroChat.createChat(options);
}

async function createPreview(options: Record<string, unknown>) {
  const GyroChat = await loadAiSdk();
  return GyroChat.createPreview(options);
}

/** 独立访问地址（分享链接、新标签页打开），路径由 SDK 给，OA 不手拼 */
/**
 * @param {{
 *   identity?: "workspace" | "visitor",
 *   appId?: string | number,
 *   shareKey?: string,
 * }} [options]
 * @returns {Promise<string>}
 */
export async function getAiUrl(options?: GyroChatUrlOptions) {
  const GyroChat = await loadAiSdk();
  return GyroChat.url(options);
}

function destroyFloat() {
  floatRevision += 1;
  floatWidget?.destroy();
  floatWidget = null;
}

function updateFloatVisibility(path: string) {
  if (!floatWidget) return;
  if (shouldHideFloat(path)) {
    floatWidget.hide();
  } else {
    floatWidget.show();
  }
}

async function ensureFloat(token: string, path = "", force = false) {
  try {
    const config = getFloatConfig();
    if (!config.enabled) {
      destroyFloat();
      return null;
    }

    if (force && floatTask) await floatTask;
    if (force) destroyFloat();

    if (!floatWidget && !floatTask) {
      const revision = floatRevision;
      floatTask = createChatInstance({
        token,
        icon: config.floatIcon
      })
        .then(instance => {
          if (revision !== floatRevision) {
            instance.destroy();
            return null;
          }
          // 创建即显示悬浮球，当前路由不该出现时立刻收起
          if (shouldHideFloat(path)) instance.hide();
          floatWidget = instance;
          return instance;
        })
        .finally(() => {
          floatTask = null;
        });
    }

    if (floatTask) await floatTask;
    updateFloatVisibility(path);
    return floatWidget;
  } catch (error) {
    console.error("AI 悬浮球初始化失败", error);
    return null;
  }
}

export const aiFloatEntryController = {
  ensure(token: string, path = "") {
    return ensureFloat(token, path);
  },
  refresh(token: string, path = "") {
    return ensureFloat(token, path, true);
  },
  updateVisibility: updateFloatVisibility
};

export const AiEmbeddedManager = {
  /**
   * 后台内部的临时调用：没有悬浮球，由页面自己决定什么时候拉起窗口。
   *
   * 外部应用必须走 visitor：它对外只经访客链路开放，用工作台的凭证与端点
   * 拉起来的就不是访客看到的那个应用了。访客形态因此也不下发企业凭证 ——
   * 服务端认的是 SDK 自己生成的访客身份。
   *
   * debug 只在这里开：宿主是后台本身，管理员点「使用」时应用可能还没发布。
   * visitor 请求头取 shareKey，workspace 请求头取当前 token。
   * 它不构成安全边界（浏览器可自行伪造），真正的门禁见 AI 侧 docs/SDK设计.md 附录 A。
   */
  /**
   * @param {{
   *   token?: string,
   *   identity?: "workspace" | "visitor",
   *   shareKey?: string,
   * }} [options]
   */
  async createTemporary({
    token,
    identity = "workspace",
    shareKey
  }: { token?: string; identity?: "workspace" | "visitor"; shareKey?: string } = {}) {
    try {
      const isVisitor = identity === "visitor";
      const instance = await createChatInstance(
        isVisitor
          ? { identity: "visitor", shareKey, debug: true, launcher: false }
          : { identity: "workspace", token, debug: true, launcher: false }
      );
      return track(instance, pageWidgets);
    } catch (error) {
      console.error("AI 临时会话初始化失败", error);
      return null;
    }
  },

  /**
   * 应用设置页的调试预览。
   *
   * 与对话共用一个脚本，但返回只负责草稿更新的轻量预览实例。
   */
  /**
   * @param {{
   *   mount: string | HTMLElement,
   *   appId?: string | number,
   *   identity?: "workspace" | "visitor",
   *   shareKey?: string,
   *   token?: string,
   * }} options
   */
  async createAdminPreview({
    mount,
    appId,
    identity = "workspace",
    shareKey,
    token
  }: {
    mount: MountTarget;
    appId?: string | number;
    identity?: "workspace" | "visitor";
    shareKey?: string;
    token?: string;
  }) {
    const instance = await createPreview(
      identity === "visitor"
        ? { identity: "visitor", mount, shareKey }
        : { identity: "workspace", mount, appId, token }
    );
    return track(instance, pageWidgets);
  },

  /**
   * 把 AI 作为页面主体铺满指定容器。
   *
   * 与悬浮球的差别只在承载方式：没有悬浮球、没有缩放收起按钮，
   * 高度由 OA 的容器决定。
   */
  /**
   * @param {{
   *   mount: string | HTMLElement,
   *   appId?: string | number,
   *   token?: string,
   * }} options
   */
  async createInline({ mount, appId, token }: { mount: MountTarget; appId?: string | number; token?: string }) {
    try {
      const instance = await createChatInstance({
        identity: "workspace",
        mount,
        appId,
        token
      });
      return track(instance, pageWidgets);
    } catch (error) {
      console.error("AI 页面初始化失败", error);
      return null;
    }
  },

  refreshFloatEntryAppList() {
    void floatWidget?.refresh();
  },

  destroyAll() {
    destroyFloat();
    Array.from(pageWidgets).forEach(instance => instance.destroy());
  }
};
