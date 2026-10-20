import type { Directive, DirectiveBinding } from "vue";
import { getAvatarSrc, setImageDefaultAvatar, type AvatarUser } from "@/utils/avatar";

/** 指令在元素上缓存的状态 */
type AvatarElement = HTMLElement & {
  __defaultAvatarUser__?: AvatarUser;
  __defaultAvatarSrc__?: string | number;
  __defaultAvatarErrorHandler__?: () => void;
};

function updateAvatar(el: AvatarElement, binding: DirectiveBinding<AvatarUser>) {
  const user = binding.value;
  const src = getAvatarSrc(user);

  el.__defaultAvatarUser__ = user;

  if (el.__defaultAvatarSrc__ !== src) {
    el.__defaultAvatarSrc__ = src;
    el.setAttribute("src", String(src));
  }
}

const defaultAvatar: Directive<AvatarElement, AvatarUser> = {
  mounted(el, binding) {
    const onError = () => setImageDefaultAvatar(el, el.__defaultAvatarUser__);
    el.__defaultAvatarErrorHandler__ = onError;
    el.addEventListener("error", onError);
    updateAvatar(el, binding);
  },

  updated(el, binding) {
    updateAvatar(el, binding);
  },

  unmounted(el) {
    if (el.__defaultAvatarErrorHandler__) {
      el.removeEventListener("error", el.__defaultAvatarErrorHandler__);
      delete el.__defaultAvatarErrorHandler__;
    }
    delete el.__defaultAvatarUser__;
    delete el.__defaultAvatarSrc__;
  },
};

export default defaultAvatar;
