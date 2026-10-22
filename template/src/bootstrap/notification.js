import notice from "@/libs/notice";

export function initNotification(store) {
  const token = store.getters.token;
  if (token) {
    return notice(token);
  }
  return undefined;
}
