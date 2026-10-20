import { defineComponent } from 'vue'
import si18nPlugin, { configure, si18n, st } from './smart-vue-i18n/index'

import enLocale from "@/lang/en-US";
import zhLocale from "@/lang/zh-CN";
import enLocale_render from "@/lang/en-US_render";
import zhLocale_render from "@/lang/zh-CN_render";
import enLocale_extension from "@/lang/en-US_extension";
import zhLocale_extension from "@/lang/zh-CN_extension";

const langResources = {
  'en-US': {
    ...enLocale,
    ...enLocale_render,
    ...enLocale_extension
  },

  'zh-CN': {
    ...zhLocale,
    ...zhLocale_render,
    ...zhLocale_extension
  }
}

configure({
  lang: localStorage.getItem('form_cache') || 'zh-CN',
  messages: langResources
})

export const changeLocale = function(langName: string) {
  si18n.setLang(langName)
  localStorage.setItem('form_cache', langName)
}

export const translate = function(key: string) {
  return st(key)
}

/** 表单设计器多语言插件：在 app 上安装 $st / $st2 / $si18n */
export const formI18nPlugin = si18nPlugin

export default defineComponent({
  methods: {
    i18nt(key: string) {
      return this.$st(key)
    },

    /* 如果key1不存在，则查找key2 */
    i18n2t(key1: string, key2: string) {
      return this.$st2(key1, key2)
    },

  }
})
