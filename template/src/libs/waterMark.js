// 全局水印画布
const WATERMARK_ID = '1.23452384164.123412416'
let watermark = {}
let timer = null
let resizeHandler = null

let setWatermark = (str1, str2, type) => {
  let id = WATERMARK_ID
  if (document.getElementById(id) !== null) {
    document.body.removeChild(document.getElementById(id))
  }

  if (type === 'close') {
    return id
  }

  //创建一个画布
  let can = document.createElement('canvas')
  //设置画布的长宽
  can.width = 350
  can.height = 250
  const ctx = can.getContext('2d')

  //旋转角度
  ctx.rotate((343.53 * Math.PI) / 180)
  ctx.font = '14px Vedana'

  //设置填充绘画的颜色、渐变或者模式
  ctx.fillStyle = '#666666'
  ctx.fontWeight = '400'
  //设置文本内容的当前对齐方式
  ctx.textAlign = 'left'
  //设置在绘制文本时使用的当前文本基线
  ctx.textBaseline = 'top'

  ctx.fillText(str1, 0, 40) // 水印在画布的位置x，y轴
  ctx.fillText(str2, 120, 220)

  let div = document.createElement('div')
  div.id = id
  div.style.pointerEvents = 'none'
  div.style.top = '66px'
  div.style.left = '240px'
  div.style.position = 'fixed'
  div.style.zIndex = '100000'
  div.style.opacity = '0.1'
  div.style.width = document.documentElement.clientWidth + 'px'
  div.style.height = document.documentElement.clientHeight + 'px'
  div.style.background = 'url(' + can.toDataURL('image/png') + ') left  repeat'

  document.body.appendChild(div)
  return id
}

watermark.set = (str1, str2, type) => {
  // 复用单一 timer / resize 监听，避免路由切换重复 set 时叠加
  if (timer) {
    clearInterval(timer)
    timer = null
  }
  if (resizeHandler) {
    window.removeEventListener('resize', resizeHandler)
    resizeHandler = null
  }

  let id = setWatermark(str1, str2, type)

  if (type === 'close') return

  timer = setInterval(() => {
    if (document.getElementById(id) === null) {
      id = setWatermark(str1, str2, type)
    }
  }, 500)

  resizeHandler = () => {
    setWatermark(str1, str2, type)
  }
  window.addEventListener('resize', resizeHandler)
}

export default watermark
