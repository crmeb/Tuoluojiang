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

type AvatarKey = string | number;

interface AvatarCard {
  id?: AvatarKey;
  uid?: AvatarKey;
  name?: string;
  avatar?: string;
}

/** 各业务里带头像的用户对象，字段命名不统一 */
export interface AvatarUserObject {
  id?: AvatarKey;
  uid?: AvatarKey;
  user_id?: AvatarKey;
  userId?: AvatarKey;
  value?: AvatarKey;
  account?: string;
  mobile?: string;
  phone?: string;
  name?: string;
  real_name?: string;
  label?: string;
  avatar?: string;
  photo?: string;
  head_img?: string;
  headImg?: string;
  card?: AvatarCard;
  info?: { card?: AvatarCard };
}

/** 可以是用户对象，也可以直接是头像地址 / 用户标识 */
export type AvatarUser = AvatarUserObject | AvatarKey | null | undefined;

declare global {
  interface Document {
    __globalAvatarFallbackRegistered__?: boolean;
  }
}

const objectAvatarIndexCache = new WeakMap<object, number>();
const failedAvatarSourceCache = new Set<string>();

function hashString(value: AvatarKey) {
  return String(value).split("").reduce((hash, char) => {
    return (hash * 31 + char.charCodeAt(0)) >>> 0;
  }, 0);
}

function getAvatarKey(user: AvatarUser): AvatarKey {
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

function getAvatarIndex(user: AvatarUser): number {
  const key = getAvatarKey(user);

  if (key) {
    return hashString(key) % DEFAULT_AVATARS.length;
  }

  if (user && typeof user === "object") {
    if (!objectAvatarIndexCache.has(user)) {
      objectAvatarIndexCache.set(user, Math.floor(Math.random() * DEFAULT_AVATARS.length));
    }
    return objectAvatarIndexCache.get(user)!;
  }

  return Math.floor(Math.random() * DEFAULT_AVATARS.length);
}

function isDefaultAvatarSource(source: string | null | undefined) {
  return DEFAULT_AVATARS.some((avatar) => source && source.includes(avatar));
}

function isKnownUnavailableAvatarSource(source: unknown) {
  return /admin_face(?:\/|%2f)face\d+\.(png|jpe?g|webp|gif)(\?.*)?$/i.test(String(source));
}

function isUnavailableAvatarSource(source: AvatarKey) {
  if (!source) {
    return true;
  }

  return isKnownUnavailableAvatarSource(source) || failedAvatarSourceCache.has(String(source));
}

export function getDefaultAvatar(user: AvatarUser): string {
  return DEFAULT_AVATARS[getAvatarIndex(user)];
}

function getAvatarSource(user: AvatarUser): AvatarKey {
  let source: AvatarKey = "";

  if (!user || typeof user !== "object") {
    source = user || "";
  } else {
    source = user.avatar || user.photo || user.head_img || user.headImg || user.card?.avatar || user.info?.card?.avatar || "";
  }

  return isUnavailableAvatarSource(source) ? "" : source;
}

export function getAvatarSrc(user: AvatarUser) {
  return getAvatarSource(user) || getDefaultAvatar(user);
}

export function setImageDefaultAvatar(target: Element | null | undefined, user: AvatarUser) {
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

function getElementClassText(target: Element) {
  const classNames: string[] = [];
  let node: Element | null = target;
  let depth = 0;

  while (node && depth < 4) {
    classNames.push(String(node.className || ""));
    node = node.parentElement;
    depth += 1;
  }

  return classNames.join(" ");
}

function isAvatarImageElement(target: HTMLImageElement) {
  const source = target.currentSrc || target.getAttribute("src") || "";
  const classText = getElementClassText(target);
  const altText = target.getAttribute("alt") || "";

  return (
    isKnownUnavailableAvatarSource(source) ||
    /(^|\s)(avatar|head-portrait|header-img|drop-avatar|img-body|user-info|manList-avatar)(\s|$)/i.test(classText) ||
    /(avatar|头像)/i.test(altText)
  );
}

function handleGlobalAvatarError(event: Event) {
  const target = event && (event.target as HTMLImageElement | null);
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
