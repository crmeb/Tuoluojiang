import pinia from '@/stores';
import { useUserStore } from '@/stores/user';

/**
 * 登录 token 存取
 * 菜单与权限的加载见 @/utils/menu
 */

export function getToken() {
  return useUserStore(pinia).token;
}

// 问卷分享页的访问凭证，请求层在分享页通过请求头 Curd-Unique 发送
const SHARE_UNIQUE_KEY = 'unique';

export function getShareUnique() {
  return localStorage.getItem(SHARE_UNIQUE_KEY);
}

export function setShareUnique(unique: string) {
  localStorage.setItem(SHARE_UNIQUE_KEY, unique);
}
