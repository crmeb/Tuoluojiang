import { BASE_URL } from "@/config/env";

const PUBLIC_PATH = BASE_URL || "/";
const PUBLIC_BASE = PUBLIC_PATH.endsWith("/") ? PUBLIC_PATH : `${PUBLIC_PATH}/`;
const DEFAULT_AVATARS = [
  "image/face1.png",
  "image/face2.png",
  "image/face3.png",
  "image/face4.png",
  "image/face5.png",
  "image/face6.png",
].map((path) => `${PUBLIC_BASE}${path}`);

const objectAvatarIndexCache = new WeakMap();
const failedAvatarSourceCache = new Set();

function hashString(value) {
  return String(value).split("").reduce((hash, char) => {
    return (hash * 31 + char.charCodeAt(0)) >>> 0;
  }, 0);
}

function getAvatarKey(user) {
  if (!user || typeof user !== "object") {
    return user || "";
  }

  return (
    user.id ||
    user.uid ||
    user.user_id ||
    user.userId ||
    user.value ||
    user.account ||
    user.mobile ||
    user.phone ||
    user.name ||
    user.real_name ||
    user.label ||
    user.card?.id ||
    user.card?.uid ||
    user.card?.name ||
    user.info?.card?.id ||
    user.info?.card?.name ||
    getAvatarSource(user)
  );
}

function getAvatarIndex(user) {
  const key = getAvatarKey(user);

  if (key) {
    return hashString(key) % DEFAULT_AVATARS.length;
  }

  if (user && typeof user === "object") {
    if (!objectAvatarIndexCache.has(user)) {
      objectAvatarIndexCache.set(user, Math.floor(Math.random() * DEFAULT_AVATARS.length));
    }
    return objectAvatarIndexCache.get(user);
  }

  return Math.floor(Math.random() * DEFAULT_AVATARS.length);
}

function isDefaultAvatarSource(source) {
  return DEFAULT_AVATARS.some((avatar) => source && source.includes(avatar));
}

function isKnownUnavailableAvatarSource(source) {
  return /admin_face(?:\/|%2f)face\d+\.(png|jpe?g|webp|gif)(\?.*)?$/i.test(String(source));
}

function isUnavailableAvatarSource(source) {
  if (!source) {
    return true;
  }

  return isKnownUnavailableAvatarSource(source) || failedAvatarSourceCache.has(source);
}

export function getDefaultAvatar(user) {
  return DEFAULT_AVATARS[getAvatarIndex(user)];
}

function getAvatarSource(user) {
  let source = "";

  if (!user || typeof user !== "object") {
    source = user || "";
  } else {
    source = user.avatar || user.photo || user.head_img || user.headImg || user.card?.avatar || user.info?.card?.avatar || "";
  }

  return isUnavailableAvatarSource(source) ? "" : source;
}

export function getAvatarSrc(user) {
  return getAvatarSource(user) || getDefaultAvatar(user);
}

export function setImageDefaultAvatar(target, user) {
  if (!target) {
    return;
  }

  const currentSource = target.getAttribute("src");
  if (currentSource && !isDefaultAvatarSource(currentSource)) {
    failedAvatarSourceCache.add(currentSource);
  }

  const fallback = getDefaultAvatar(user);
  if (target.getAttribute("src") !== fallback) {
    target.setAttribute("src", fallback);
  }
}

function getElementClassText(target) {
  const classNames = [];
  let node = target;
  let depth = 0;

  while (node && depth < 4) {
    classNames.push(node.className || "");
    node = node.parentElement;
    depth += 1;
  }

  return classNames.join(" ");
}

function isAvatarImageElement(target) {
  const source = target.currentSrc || target.getAttribute("src") || "";
  const classText = getElementClassText(target);
  const altText = target.getAttribute("alt") || "";

  return (
    isKnownUnavailableAvatarSource(source) ||
    /(^|\s)(avatar|head-portrait|header-img|drop-avatar|img-body|user-info|manList-avatar)(\s|$)/i.test(classText) ||
    /(avatar|头像)/i.test(altText)
  );
}

function handleGlobalAvatarError(event) {
  const target = event && event.target;
  if (!target || target.tagName !== "IMG" || !isAvatarImageElement(target)) {
    return;
  }

  const source = target.currentSrc || target.getAttribute("src") || "";
  if (isDefaultAvatarSource(source)) {
    return;
  }

  setImageDefaultAvatar(target, source);
}

export function setupGlobalAvatarFallback() {
  if (typeof document === "undefined" || document.__globalAvatarFallbackRegistered__) {
    return;
  }

  document.addEventListener("error", handleGlobalAvatarError, true);
  document.__globalAvatarFallbackRegistered__ = true;
}
