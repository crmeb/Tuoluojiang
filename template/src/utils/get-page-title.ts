import defaultSettings from '@/settings';

// 路由标题不再参与页面标题（原先按 route.<title> 查语言包，业务路由标题均为中文，从未命中）
export default function getPageTitle() {
  return `${defaultSettings.title}`;
}
