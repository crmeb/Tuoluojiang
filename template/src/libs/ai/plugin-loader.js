/**
 * AI SDK 脚本加载器
 *
 * 只有一个产物，对话与预览分别从 GyroChat.createChat / createPreview 创建。
 * 原先拆成两份脚本，这里就得维护两套加载任务、两个全局变量、两份缓存。
 */

import SettingMer from "@/libs/settingMer";
import { AI_BASE_URL } from "@/config/env";

const sdkScriptSrc = "/chat/sdk/index.js";
let sdkLoadTask = null;

function getConfiguredAiBaseUrl() {
  return AI_BASE_URL || SettingMer.httpUrl || location.origin;
}

export function getAiBaseUrl() {
  return new URL(getConfiguredAiBaseUrl(), location.origin);
}

function loadGlobalScript(src, globalName, errorLabel) {
  return new Promise((resolve, reject) => {
    if (window[globalName]) {
      resolve(window[globalName]);
      return;
    }

    const script = document.createElement("script");
    // 时间戳绕开浏览器与中间层缓存，避免 AI 侧发版后 OA 仍加载旧 SDK。
    const scriptUrl = new URL(src, getAiBaseUrl());
    scriptUrl.searchParams.set("t", Date.now().toString());
    script.src = scriptUrl.href;
    script.async = true;
    script.onload = () => {
      if (window[globalName]) {
        resolve(window[globalName]);
        return;
      }
      script.remove();
      reject(new Error(`${errorLabel}加载成功但未暴露 ${globalName}`));
    };
    script.onerror = () => {
      script.remove();
      reject(new Error(`${errorLabel}脚本加载失败`));
    };
    document.head.appendChild(script);
  });
}

export function loadAiSdk() {
  if (window.GyroChat) return Promise.resolve(window.GyroChat);
  if (!sdkLoadTask) {
    sdkLoadTask = loadGlobalScript(sdkScriptSrc, "GyroChat", "AI SDK").catch((error) => {
      sdkLoadTask = null;
      throw error;
    });
  }
  return sdkLoadTask;
}
