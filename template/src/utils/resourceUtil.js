import { IS_DEV } from "@/config/env";

const isDev = IS_DEV;
const devServerUrl = "http://dev.oa.crmeb.net";
const httpResourceReg = /\bhttp:\/\/[^\s"'<>\\)]+/gi;

const getCurrentProtocol = () => {
  if (typeof window === "undefined" || !window.location) {
    return "";
  }

  return window.location.protocol;
};

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

const shouldUpgradeInsecureResource = (protocol = getCurrentProtocol()) => protocol === "https:";

export const normalizeRequestUrl = (url, baseURL) => {
  if (typeof url !== "string" || typeof baseURL !== "string" || !baseURL) {
    return url;
  }

  const base = baseURL.replace(/\/+$/, "");
  const prefixes = [base];
  if (base.indexOf("/") === 0 && typeof location !== "undefined" && location.origin) {
    prefixes.push(location.origin + base);
  }

  for (const prefix of prefixes) {
    if (url === prefix) {
      return "";
    }
    if (url.indexOf(prefix + "/") === 0) {
      return url.slice(prefix.length + 1);
    }
  }

  return url;
};

/**
 * 统一处理后端返回的资源地址：
 * 1. 开发模式下移除固定测试域名，使资源走本地代理
 * 2. HTTPS 页面下将 http 资源升级为 https，避免 Mixed Content
 * @param {string} url 远端资源的 url 或包含资源链接的富文本内容
 * @param {Object} options 可选配置，便于单元测试覆盖协议分支
 * @returns {string} 经过处理后的 url
 */
export const processResourceUrl = (url, options = {}) => {
  if (typeof url !== "string" || !url) {
    return url;
  }

  const devMode = typeof options.isDev === "boolean" ? options.isDev : isDev;
  const proxyHost = options.devServerUrl || devServerUrl;
  const protocol = options.protocol || getCurrentProtocol();
  let normalizedUrl = url;

  if (devMode && proxyHost) {
    normalizedUrl = normalizedUrl.replace(new RegExp(escapeRegExp(proxyHost), "g"), "");
  }

  if (!shouldUpgradeInsecureResource(protocol)) {
    return normalizedUrl;
  }

  return normalizedUrl.replace(httpResourceReg, (matchedUrl) => matchedUrl.replace(/^http:/i, "https:"));
};

/**
 * 将资源地址补全为绝对地址，供微软 Office 在线预览等需要公网可访问地址的第三方服务使用。
 * 开发模式下相对路径补回测试域名，避免第三方服务解析到本机 localhost。
 * @param {string} url 资源地址，可为相对路径
 * @param {Object} options 可选配置，便于单元测试覆盖环境分支
 * @returns {string} 绝对地址
 */
export const resolveAbsoluteResourceUrl = (url, options = {}) => {
  if (typeof url !== "string" || !url) {
    return url;
  }

  if (/^https?:\/\//i.test(url)) {
    return url;
  }

  const devMode = typeof options.isDev === "boolean" ? options.isDev : isDev;
  const pageOrigin =
    options.origin || (typeof window !== "undefined" && window.location ? window.location.origin : "");
  const origin = devMode ? options.devServerUrl || devServerUrl : pageOrigin;

  if (!origin) {
    return url;
  }

  return `${origin.replace(/\/+$/, "")}/${url.replace(/^\/+/, "")}`;
};

/**
 * 递归处理接口响应或缓存数据中的资源地址，覆盖头像、logo、富文本 img 等深层字段。
 * @param {*} data 任意接口响应数据
 * @param {Object} options processResourceUrl 透传配置
 * @param {WeakSet<object>} seen 循环引用保护
 * @returns {*} 处理后的数据
 */
export const processResourceData = (data, options = {}, seen = new WeakSet()) => {
  if (typeof data === "string") {
    return processResourceUrl(data, options);
  }

  if (!data || typeof data !== "object") {
    return data;
  }

  if (seen.has(data)) {
    return data;
  }

  if (Array.isArray(data)) {
    seen.add(data);
    data.forEach((item, index) => {
      data[index] = processResourceData(item, options, seen);
    });
    return data;
  }

  if (Object.prototype.toString.call(data) !== "[object Object]") {
    return data;
  }

  seen.add(data);
  Object.keys(data).forEach((key) => {
    data[key] = processResourceData(data[key], options, seen);
  });

  return data;
};
