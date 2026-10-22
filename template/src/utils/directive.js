let isDialogDirectivesRegistered = false

export function registerDialogDirectives(Vue) {
  if (isDialogDirectivesRegistered) {
    return
  }
  isDialogDirectivesRegistered = true

  Vue.directive('dialogDrag', {
    bind(el) {
      const dialogHeaderEl = el.querySelector('.el-dialog__header')
      const dragDom = el.querySelector('.el-dialog')
      dialogHeaderEl.style.cursor = 'move'

      const sty = dragDom.currentStyle || window.getComputedStyle(dragDom, null)

      dialogHeaderEl.onmousedown = (e) => {
        const disX = e.clientX - dialogHeaderEl.offsetLeft
        const disY = e.clientY - dialogHeaderEl.offsetTop
        let styL
        let styT

        if (sty.left.includes('%')) {
          styL = +document.body.clientWidth * (+sty.left.replace(/%/g, '') / 100)
          styT = +document.body.clientHeight * (+sty.top.replace(/%/g, '') / 100)
        } else {
          styL = +sty.left.replace(/px/g, '')
          styT = +sty.top.replace(/px/g, '')
        }

        document.onmousemove = function(e) {
          const l = e.clientX - disX
          const t = e.clientY - disY

          dragDom.style.left = `${l + styL}px`
          dragDom.style.top = `${t + styT}px`
        }

        document.onmouseup = function() {
          document.onmousemove = null
          document.onmouseup = null
        }
      }
    }
  })

  Vue.directive('dialogDragWidth', {
    bind(el, binding) {
      const dragDom = binding.value.$el.querySelector('.el-dialog')

      el.onmousedown = (e) => {
        const disX = e.clientX - el.offsetLeft

        document.onmousemove = function(e) {
          e.preventDefault()

          const l = e.clientX - disX
          dragDom.style.width = `${l}px`
        }

        document.onmouseup = function() {
          document.onmousemove = null
          document.onmouseup = null
        }
      }
    }
  })
}
