/**
 * docx 图片尺寸读取 / 回写工具
 *
 * 背景：mammoth 转换 docx 时不会输出图片的显示尺寸（transformImage 拿到的
 * image 对象只有 contentType / altText / read），所以文档里调整过的图片尺寸
 * 打开时会丢失，图片按原始像素显示。这里直接读 word/document.xml，按文档
 * 顺序取出每张图片的显示尺寸，再补回 mammoth 生成的 <img> 上。
 *
 * 另外后端用 PHPWord 把 HTML 转成 docx，它只认 <img> 的 width / height
 * 属性（style 里的宽高会被忽略），所以保存前必须把尺寸写进属性。
 */
import JSZip from 'jszip'

// 1px = 9525 EMU（English Metric Unit）
const EMU_PER_PX = 9525

// 各单位到 px 的换算：PHPWord 写出的 v:shape 用 px，Word 自身多用 pt
const UNIT_TO_PX: Record<string, number> = {
  px: 1,
  pt: 96 / 72,
  pc: 16,
  in: 96,
  cm: 96 / 2.54,
  mm: 96 / 25.4
}

// 把 CSS 长度转成整数 px，无法识别（如 auto、百分比）时返回 0
export function cssLengthToPx(value: unknown) {
  const matched = String(value)
    .trim()
    .match(/^(-?[\d.]+)\s*(px|pt|pc|in|cm|mm)?$/i)
  if (!matched) return 0
  const ratio = UNIT_TO_PX[(matched[2] || 'px').toLowerCase()]
  return Math.round(parseFloat(matched[1]) * ratio) || 0
}

// 从 v:shape 的 style 属性里取宽高
export interface ImageSize {
  width: number
  height: number
}

function readVmlSize(shapeXml: string): ImageSize {
  const style = (shapeXml.match(/style="([^"]*)"/i) || [])[1] || ''
  const size = { width: 0, height: 0 }
  style.split(';').forEach((item) => {
    const index = item.indexOf(':')
    if (index < 0) return
    const key = item.slice(0, index).trim().toLowerCase()
    if (key === 'width') size.width = cssLengthToPx(item.slice(index + 1))
    if (key === 'height') size.height = cssLengthToPx(item.slice(index + 1))
  })
  return size
}

// 从 w:drawing 的 wp:extent 里取宽高（单位 EMU）
function readDrawingSize(drawingXml: string): ImageSize {
  const extent = drawingXml.match(/<wp:extent[^>]*\bcx="(\d+)"[^>]*\bcy="(\d+)"/i)
  if (!extent) return { width: 0, height: 0 }
  return {
    width: Math.round(Number(extent[1]) / EMU_PER_PX),
    height: Math.round(Number(extent[2]) / EMU_PER_PX)
  }
}

/**
 * 按文档顺序提取 docx 正文里每张图片的显示尺寸
 * @param {ArrayBuffer|Uint8Array} buffer docx 文件内容
 * @returns {Promise<Array<{width: number, height: number}>>} 顺序与 mammoth 输出的 <img> 一致
 */
export function extractDocxImageSizes(buffer: ArrayBuffer | Uint8Array): Promise<ImageSize[]> {
  return JSZip.loadAsync(buffer)
    .then((zip) => {
      const entry = zip.file('word/document.xml')
      return entry ? entry.async('string') : ''
    })
    .then((xml) => {
      if (!xml) return []
      // 两种图片写法用同一个正则匹配，保证结果是文档顺序
      const matched = xml.match(/<w:drawing[\s\S]*?<\/w:drawing>|<v:shape[\s\S]*?<\/v:shape>/g) || []
      return matched.map((item) => (item.indexOf('<w:drawing') === 0 ? readDrawingSize(item) : readVmlSize(item)))
    })
    .catch(() => [])
}

/**
 * 把提取到的尺寸按顺序补回 mammoth 生成的 HTML 上
 * @param {string} html mammoth 转换结果
 * @param {Array<{width: number, height: number}>} sizes extractDocxImageSizes 的返回值
 * @returns {string}
 */
export function applyDocxImageSizes(html: string, sizes: ImageSize[] | null | undefined) {
  if (!html || !sizes || !sizes.length) return html
  let index = 0
  return html.replace(/<img([^>]*?)\/?>/gi, (match, attributes: string) => {
    const size = sizes[index++]
    if (!size || !size.width || !size.height) return match
    // 去掉原有的宽高声明，统一由 docx 里记录的显示尺寸决定
    const style = (attributes.match(/\sstyle="([^"]*)"/i) || [])[1] || ''
    const keptStyle = style
      .split(';')
      .filter((item) => item.trim() && !/^\s*(max-)?(width|height)\s*:/i.test(item))
      .map((item) => item.trim() + ';')
      .join(' ')
    const rest = attributes
      .replace(/\s(?:width|height|style)="[^"]*"/gi, '')
      .replace(/\s*\/\s*$/, '')
      .trim()
    const finalStyle = `width: ${size.width}px; height: ${size.height}px;${keptStyle ? ' ' + keptStyle : ''}`
    // 后端 PHPWord 用 DOMDocument::loadXML 解析，标签必须自闭合
    return `<img ${rest} width="${size.width}" height="${size.height}" style="${finalStyle}" />`
  })
}

/**
 * 保存前规范化 <img>：把编辑器里的显示尺寸同步到 width / height 属性
 *
 * wangEditor 缩放图片后只更新 style 里的宽高（width / height 属性保持为空），
 * 而后端 PHPWord 只读属性，导致缩放结果保存不下来。
 * @param {string} html 编辑器输出的 HTML
 * @returns {string}
 */
export function normalizeImageSizeForSave(html: string) {
  if (!html) return html
  return html.replace(/<img([^>]*?)\/?>/gi, (match, attributes: string) => {
    const style = (attributes.match(/\sstyle="([^"]*)"/i) || [])[1] || ''
    const styleSize = { width: 0, height: 0 }
    style.split(';').forEach((item) => {
      const index = item.indexOf(':')
      if (index < 0) return
      const key = item.slice(0, index).trim().toLowerCase()
      if (key === 'width') styleSize.width = cssLengthToPx(item.slice(index + 1))
      if (key === 'height') styleSize.height = cssLengthToPx(item.slice(index + 1))
    })

    const attrWidth = cssLengthToPx((attributes.match(/\swidth="([^"]*)"/i) || [])[1] || '')
    const attrHeight = cssLengthToPx((attributes.match(/\sheight="([^"]*)"/i) || [])[1] || '')
    // style 是编辑器缩放后的实时尺寸，优先级高于属性上的旧值
    const width = styleSize.width || attrWidth
    const height = styleSize.height || attrHeight

    const keptStyle = style
      .split(';')
      .filter((item) => item.trim() && !/^\s*(max-)?(width|height)\s*:/i.test(item))
      .map((item) => item.trim() + ';')
      .join(' ')
    const rest = attributes
      .replace(/\s(?:width|height|style)="[^"]*"/gi, '')
      .replace(/\s*\/\s*$/, '')
      .trim()

    // 没有尺寸信息时保持自适应，交给 Word 按原始尺寸排版
    if (!width || !height) {
      const fallbackStyle = `max-width: 100%; height: auto;${keptStyle ? ' ' + keptStyle : ''}`
      return `<img ${rest} style="${fallbackStyle}" />`
    }

    const finalStyle = `width: ${width}px; height: ${height}px;${keptStyle ? ' ' + keptStyle : ''}`
    return `<img ${rest} width="${width}" height="${height}" style="${finalStyle}" />`
  })
}
