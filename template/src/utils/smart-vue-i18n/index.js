import {deepAssign, get, isPlainObject} from './utils'
import {IS_PROD} from '@/config/env'

const install = (Vue, options) => {
    const proto = Vue.prototype
    proto.$si18n = proto.$si18n || {}
    // 初始化多语言
    deepAssign(proto.$si18n, options)

    const _vm = new Vue({
        data: options
    })
    Object.defineProperty(Vue.prototype.$si18n, 'lang', {
        get() {
            return _vm.lang
        }
    })

    proto.$st = (path, ...args) => {
        let messages = _vm.messages[_vm.lang]
        if (!proto.$si18n.messages) {
            if (!IS_PROD) {
                console.error('[yxI18n] Locale not correctly registered')
            }
            return () => path
        }
        //const message = get(messages, path) || get(messages, path)
        const message = get(messages, path)
        return typeof message === 'function'
            ? message(...args)
            : (message !== null ? message : path)
    }

    proto.$st2 = (path, path2) => {
      let messages = _vm.messages[_vm.lang]
      const message = get(messages, path)
      return (message !== null) ? message : get(messages, path2)
    }

    proto.$si18n.add = (messages = {}) => {
        deepAssign(proto.$si18n.messages, messages)
    }

    proto.$si18n.setLang = lang => {
        _vm.lang = lang
    }

    Vue.mixin({
        beforeCreate() {
            // 注意：`i18n` 这个选项名被 vue-i18n 占用，根实例与手动 new 出来的组件
            // （如 utils/filePreview 的预览宿主）传进来的是 VueI18n 实例，其内部
            // `_vm` 为 Vue 实例、带循环引用，deepAssign 进去会无限递归爆栈。
            // 这里只接受纯对象形式的语言包。
            const messages = this.$options.i18n
            if (isPlainObject(messages)) {
                this.$si18n.add(messages)
            }
        }
    })
}

export default {
    install
}
