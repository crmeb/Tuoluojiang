import { getAvatarSrc, setImageDefaultAvatar } from "@/utils/avatar";

function updateAvatar(el, binding) {
  const user = binding.value;
  const src = getAvatarSrc(user);

  el.__defaultAvatarUser__ = user;

  if (el.__defaultAvatarSrc__ !== src) {
    el.__defaultAvatarSrc__ = src;
    el.setAttribute("src", src);
  }
}

export default {
  inserted(el, binding) {
    const onError = () => setImageDefaultAvatar(el, el.__defaultAvatarUser__);
    el.__defaultAvatarErrorHandler__ = onError;
    el.addEventListener("error", onError);
    updateAvatar(el, binding);
  },

  componentUpdated(el, binding) {
    updateAvatar(el, binding);
  },

  unbind(el) {
    if (el.__defaultAvatarErrorHandler__) {
      el.removeEventListener("error", el.__defaultAvatarErrorHandler__);
      delete el.__defaultAvatarErrorHandler__;
    }
    delete el.__defaultAvatarUser__;
    delete el.__defaultAvatarSrc__;
  },
};
