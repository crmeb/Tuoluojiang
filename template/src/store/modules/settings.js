import defaultSettings from '@/settings';

const { fixedHeader, sidebarLogo, helpShow, bbsShow, forumShow } = defaultSettings;

const state = {
  fixedHeader: fixedHeader,
  sidebarLogo: sidebarLogo,
  helpShow: helpShow,
  bbsShow: bbsShow,
  forumShow: forumShow,
};

export default {
  namespaced: true,
  state,
};
