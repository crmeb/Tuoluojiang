import Cookies from 'js-cookie';
import { processResourceUrl } from '@/utils/resourceUtil';
import { BASE_API, WS_URL } from '@/config/env';
// 请求接口地址 如果没有配置自动获取当前网址路径
const resolvedApiUrl = processResourceUrl(BASE_API || `${location.origin}`);

function getDefaultWsUrl() {
  const apiUrl = BASE_API || resolvedApiUrl;
  if (apiUrl) {
    try {
      const url = new URL(apiUrl, location.origin);
      url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:';
      return url.origin;
    } catch (e) {
      // ignore malformed env and fallback to current host
    }
  }

  return (location.protocol === 'https:' ? 'wss' : 'ws') + '://' + location.host;
}

const resolvedWsUrl = WS_URL || getDefaultWsUrl();
const merInfo = Cookies.get('MerInfo');
const login_title: string = merInfo ? JSON.parse(merInfo).login_title : '';
const SettingMer = {
  // 服务器地址
  httpUrl: resolvedApiUrl,
  // 接口请求地址
  https: resolvedApiUrl + '/api/ent',
  // https: 'http://123.56.28.2:20200/api/ent',
  // socket连接
  wsSocketUrl: resolvedWsUrl,
  // 路由标题
  title: login_title || 'OA系统-总后台',
};

export default SettingMer;
