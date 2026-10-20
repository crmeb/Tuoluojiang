import notice from "@/libs/notice";
import pinia from "@/stores";
import { useUserStore } from "@/stores/user";

// 当前消息长连接的关闭函数；原先挂在根实例的 data.notice 上
let closeCurrent: (() => void) | undefined;

export function initNotification() {
  const token = useUserStore(pinia).token;
  if (token) {
    return notice(token);
  }
  return undefined;
}

export function closeNotice() {
  closeCurrent && closeCurrent();
  closeCurrent = undefined;
}

/** 按当前 token 重建消息长连接 */
export function restartNotice() {
  closeNotice();
  closeCurrent = initNotification();
}
