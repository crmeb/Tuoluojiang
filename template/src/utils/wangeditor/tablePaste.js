function toInt(val) {
  const n = parseInt(String(val || '').replace(/[^\d]/g, ''), 10)
  return Number.isFinite(n) ? n : 0
}

function appendStyle(el, styleText) {
  const prev = el.getAttribute('style') || ''
  const next = prev ? `${prev}; ${styleText}` : styleText
  el.setAttribute('style', next)
}

function getColWidthsFromColgroup(tableEl) {
  const colgroup = tableEl.querySelector('colgroup')
  if (!colgroup) return []
  const cols = Array.from(colgroup.querySelectorAll('col'))
  return cols
    .map((col) => {
      const wAttr = col.getAttribute('width')
      if (wAttr) return toInt(wAttr)
      const style = col.getAttribute('style') || ''
      const m = style.match(/width\s*:\s*([\d.]+)\s*(px)?/i)
      if (m) return toInt(m[1])
      return 0
    })
    .filter((n) => n > 0)
}

function calcFirstRowColCount(firstRowCells) {
  return firstRowCells.reduce((sum, cell) => {
    const colspan = toInt(cell.getAttribute('colspan')) || 1
    return sum + Math.max(colspan, 1)
  }, 0)
}

function calcRowColCount(trEl) {
  const cells = Array.from(trEl.querySelectorAll('th,td'))
  return cells.reduce((sum, cell) => {
    const colspan = toInt(cell.getAttribute('colspan')) || 1
    return sum + Math.max(colspan, 1)
  }, 0)
}

export function normalizePastedTableHtml(html) {
  if (!html || !/<table[\s>]/i.test(html)) return { html, changed: false }

  const parser = new DOMParser()
  const doc = parser.parseFromString(html, 'text/html')
  const tables = Array.from(doc.querySelectorAll('table'))
  if (!tables.length) return { html, changed: false }

  let changed = false

  tables.forEach((table) => {
    // 1) colgroup -> width on first row cells
    const widths = getColWidthsFromColgroup(table)
    const colgroup = table.querySelector('colgroup')
    if (colgroup) {
      colgroup.remove()
      changed = true
    }

    const firstRow = table.querySelector('tr')
    const firstRowCells = firstRow ? Array.from(firstRow.querySelectorAll('th,td')) : []
    const firstRowColCount = firstRowCells.length ? calcFirstRowColCount(firstRowCells) : 0
    // 通过各行最大列数推断（避免首行本身就是“单格+colspan=1”的异常）
    const rows = Array.from(table.querySelectorAll('tr'))
    const maxRowColCount = rows.reduce((max, tr) => Math.max(max, calcRowColCount(tr)), 0)
    const inferredColCount = widths.length || maxRowColCount || firstRowColCount

    if (widths.length && firstRowCells.length) {
      // 逐列给首行单元格写入宽度（考虑 colspan）
      let colIdx = 0
      firstRowCells.forEach((cell) => {
        const colspan = toInt(cell.getAttribute('colspan')) || 1
        const span = Math.max(colspan, 1)
        const w = widths[colIdx]
        if (w) {
          appendStyle(cell, `width: ${w}px`)
          changed = true
        }
        colIdx += span
      })
    }

    // 2) table/td/th 兜底样式，避免“挤成一列/塌陷”
    // 有明确列宽时才使用 fixed；否则用 auto 让浏览器自适应（避免 Word 表格被压瘪）
    if (widths.length) {
      appendStyle(table, 'width: 100%; table-layout: fixed;')
    } else {
      appendStyle(table, 'width: 100%; table-layout: auto;')
    }
    changed = true
    Array.from(table.querySelectorAll('th,td')).forEach((cell) => {
      appendStyle(cell, 'word-break: break-word; white-space: normal;')
    })

    // 3) 单列化兜底：若某行只有 1 格且缺少 colspan，而表格明显多列，则补 colspan
    if (inferredColCount > 1) {
      Array.from(table.querySelectorAll('tr')).forEach((tr) => {
        const cells = Array.from(tr.querySelectorAll('th,td'))
        if (cells.length !== 1) return
        const onlyCell = cells[0]
        const colspan = toInt(onlyCell.getAttribute('colspan'))
        // Word/Docx 粘贴常见：单元格实际应占满整行，但错误带了 colspan="1"
        if (colspan > 1) return
        // 只有在该行不是显式单列表格且有多列证据时才补
        onlyCell.setAttribute('colspan', String(inferredColCount))
        changed = true
      })
    }
  })

  const out = doc.body.innerHTML
  return { html: out, changed }
}

