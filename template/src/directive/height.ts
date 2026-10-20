import type { App, ObjectDirective } from 'vue'

const height = (el: Element | null, all?: boolean) => {
    if (!el) {
      return 0
    }
    const style = document.defaultView!.getComputedStyle(el)
    return parseFloat(
      String(parseFloat(style.marginTop) +
        parseFloat(style.marginBottom) +
        (all ? 0 : parseFloat(style.paddingTop)) +
        (all ? 0 : parseFloat(style.paddingBottom)) +
        parseFloat(style.borderTopWidth) +
        parseFloat(style.borderBottomWidth))
    )
  }
  
  const heightDirective: ObjectDirective<HTMLElement, number | boolean | undefined> = {
    mounted(el, data) {
      let dom: HTMLElement | null = null
      el.classList.add('v-height-box')
      let value = 0
      let flag = false
      if (data.value && data.value !== true) {
        value = data.value as number
      }
      if (!value) {
        let parent = el.parentNode as HTMLElement | null
        let child: HTMLElement = el
        while (parent) {
          flag = parent.classList.contains('v-height-box')
          if (flag || parent.classList.contains('divBox') || parent.classList.contains('app-main')) {
            if (flag) {
              dom = parent
            }
            parent = null
          } else {
            value += height(parent)
            if (parent.classList.contains('v-height-flag')) {
              ;[...parent.children].forEach((item) => {
                if (item !== child) {
                  value += height(item, true) + item.clientHeight
                }
              })
            }
            child = parent
            parent = parent.parentNode as HTMLElement | null
          }
        }
        if (value) {
          value *= -1
        }
      }
  
      if (flag) {
        const style = document.defaultView!.getComputedStyle(dom!)
        value += -1 * parseFloat(String(parseFloat(style.paddingTop) + parseFloat(style.paddingBottom)))
      }
  
      el.style.height = (dom ? dom.clientHeight : window.innerHeight - 132) + value + 'px'
      el.style.overflow = 'auto'
    },
    updated(el, data, vnode) {
      heightDirective.mounted!(el, data, vnode, null)
    }
  }
  
  export default {
    install(app: App) {
      app.directive('height', heightDirective)
      app.directive('height-flag', {
        mounted(el: HTMLElement) {
          el.classList.add('v-height-flag')
        }
      })
  
      app.directive('height-box', {
        mounted(el: HTMLElement) {
          el.classList.add('v-height-box')
        }
      })
    }
  }