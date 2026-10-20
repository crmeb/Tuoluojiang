/** 数量过千折算成 k，如 1234 → 1.2k */
export const formatCount = (value?: number) => {
  const count = value || 0
  return count >= 1000 ? `${(count / 1000).toFixed(1)}k` : String(count)
}

/** 字节转 KB，保留一位小数 */
export const formatKb = (bytes?: number) => `${((bytes || 0) / 1024).toFixed(1)}KB`

/**
 * HTML 实体解码
 *
 * 接口把 content_html 当富文本存，回包时尖括号等字符是转义过的（`<` → `&lt;`）。
 * markdown 编辑器里放的是纯文本，回填不解一层的话，用户每存一次就会多叠一层转义。
 */
export const decodeHtmlEntities = (text: string) => {
  const el = document.createElement('textarea')
  el.innerHTML = text || ''
  return el.value
}
