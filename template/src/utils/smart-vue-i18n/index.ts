import { reactive, type App } from 'vue'
import {deepAssign, get, isPlainObject, type Messages} from './utils'
import {IS_PROD} from '@/config/env'

// 表单设计器专用的轻量多语言；状态放在模块级，插件安装前也能调用 st / setLang
const state = reactive<{ lang: string; messages: Messages }>({ lang: 'zh-CN', messages: {} })

export const st = (path: string, ...args: unknown[]): any => {
    const messages = state.messages[state.lang]
    if (!messages) {
        if (!IS_PROD) {
            console.error('[yxI18n] Locale not correctly registered')
        }
        return path
    }
    const message = get(messages, path)
    return typeof message === 'function'
        ? message(...args)
        : (message !== null ? message : path)
}

export const st2 = (path: string, path2: string): any => {
    const messages = state.messages[state.lang]
    const message = get(messages, path)
    return (message !== null) ? message : get(messages, path2)
}

export const si18n = {
    get lang() {
        return state.lang
    },
    get messages() {
        return state.messages
    },
    add(messages: Messages = {}) {
        deepAssign(state.messages, messages)
    },
    setLang(lang: string) {
        state.lang = lang
    }
}

export function configure(options: { lang?: string; messages?: Messages } = {}) {
    if (options.lang) state.lang = options.lang
    if (options.messages) deepAssign(state.messages, options.messages)
}

const install = (app: App) => {
    const proto = app.config.globalProperties
    proto.$si18n = si18n
    proto.$st = st
    proto.$st2 = st2

    app.mixin({
        beforeCreate() {
            // 注意：`i18n` 这个选项名也被 vue-i18n 用于组件级语言包，这里只接受纯对象形式
            const messages = (this.$options as { i18n?: unknown }).i18n
            if (isPlainObject(messages)) {
                si18n.add(messages)
            }
        }
    })
}

export default {
    install
}
