import store from '@/store';

/**
 * 登录 token 存取
 * 菜单与权限的加载见 @/utils/menu
 */

export function getToken() {
  return store.getters.token;
}
