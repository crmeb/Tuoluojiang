const DIVIDER_TEXT = '─'.repeat(40)

function appendStyle(element, name, value) {
  if (!element.style.getPropertyValue(name)) {
    element.style.setProperty(name, value)
  }
}

function cleanEditorAttributes(root) {
  Array.from(root.querySelectorAll('*')).forEach((element) => {
    Array.from(element.attributes).forEach((attribute) => {
      if (attribute.name.startsWith('data-w-e-') || attribute.name === 'contenteditable') {
        element.removeAttribute(attribute.name)
      }
    })
  })
}

function normalizeTodo(doc) {
  Array.from(doc.querySelectorAll('div[data-w-e-type="todo"]')).forEach((todo) => {
    const checkbox = todo.querySelector('input[type="checkbox"]')
    const paragraph = doc.createElement('p')
    const marker = checkbox && checkbox.checked ? '☒ ' : '☐ '

    paragraph.appendChild(doc.createTextNode(marker))
    Array.from(todo.childNodes).forEach((node) => {
      if (node !== checkbox) paragraph.appendChild(node.cloneNode(true))
    })
    todo.replaceWith(paragraph)
  })
}

function normalizeCodeBlocks(doc) {
  Array.from(doc.querySelectorAll('pre > code')).forEach((code) => {
    const pre = code.parentElement
    while (code.firstChild) pre.insertBefore(code.firstChild, code)
    code.remove()
    appendStyle(pre, 'font-family', 'Consolas, "Courier New", monospace')
    appendStyle(pre, 'white-space', 'pre-wrap')
  })
}

function normalizeDividers(doc) {
  Array.from(doc.querySelectorAll('hr')).forEach((divider) => {
    const paragraph = doc.createElement('p')
    paragraph.textContent = DIVIDER_TEXT
    paragraph.style.color = '#d9d9d9'
    divider.replaceWith(paragraph)
  })
}

function normalizeTables(doc) {
  Array.from(doc.querySelectorAll('table')).forEach((table) => {
    const columnWidths = Array.from(table.querySelectorAll('colgroup col')).map((col) => {
      const width = parseFloat(col.getAttribute('width') || col.style.width)
      return Number.isFinite(width) && width > 0 ? width : 0
    })
    const firstRowCells = Array.from(table.querySelectorAll('tr:first-child > th, tr:first-child > td'))

    if (columnWidths.length && firstRowCells.length) {
      let columnIndex = 0
      firstRowCells.forEach((cell) => {
        const span = Math.max(parseInt(cell.getAttribute('colspan'), 10) || 1, 1)
        const width = columnWidths.slice(columnIndex, columnIndex + span).reduce((sum, item) => sum + item, 0)
        if (width > 0) cell.style.width = `${width}px`
        columnIndex += span
      })
    }

    Array.from(table.querySelectorAll('colgroup')).forEach((colgroup) => colgroup.remove())
    if (!table.style.width || table.style.width === 'auto' || table.style.width === '0px') {
      table.style.width = '100%'
    }
    if (table.style.height === 'auto') table.style.removeProperty('height')
    appendStyle(table, 'border-collapse', 'collapse')

    Array.from(table.querySelectorAll('th, td')).forEach((cell) => {
      if (cell.getAttribute('width') === 'auto') cell.removeAttribute('width')
      appendStyle(cell, 'border', '1px solid #d9d9d9')
      appendStyle(cell, 'padding', '4px')
    })
  })
}

/**
 * 将 wangEditor 的结构化节点转换为后端 Word 转换器可稳定处理的 HTML。
 * 转换后的待办和分割线使用可见字符承载语义，避免 Word 往返时被静默丢弃。
 */
export function normalizeDocxHtml(html) {
  if (!html) return html

  const doc = new DOMParser().parseFromString(html, 'text/html')
  normalizeTodo(doc)
  normalizeCodeBlocks(doc)
  normalizeDividers(doc)
  normalizeTables(doc)
  cleanEditorAttributes(doc.body)
  return doc.body.innerHTML
}

function removeTodoMarker(element) {
  const nodes = [element]
  while (nodes.length) {
    const node = nodes.shift()
    if (node.nodeType === Node.TEXT_NODE && /^[\s]*(☐|☒)\s*/.test(node.textContent || '')) {
      node.textContent = (node.textContent || '').replace(/^[\s]*(☐|☒)\s*/, '')
      return
    }
    nodes.unshift(...Array.from(node.childNodes || []))
  }
}

function isCodeParagraph(element) {
  if (element.tagName !== 'P' || !(element.textContent || '').trim()) return false

  return [element, ...Array.from(element.querySelectorAll('[style]'))].some((node) =>
    /(^|[\s,"])(consolas)([\s,"]|$)/i.test(node.style.fontFamily || '')
  )
}

function restoreCodeBlock(doc, paragraph) {
  const pre = doc.createElement('pre')
  const code = doc.createElement('code')
  code.textContent = paragraph.textContent || ''
  pre.appendChild(code)
  paragraph.replaceWith(pre)
}

/** 恢复经过 Word 保存后仍可识别的 wangEditor 节点。 */
export function restoreDocxEditorHtml(html) {
  if (!html) return html

  const doc = new DOMParser().parseFromString(html, 'text/html')
  Array.from(doc.body.children).forEach((element) => {
    const text = (element.textContent || '').trim()
    if (isCodeParagraph(element)) {
      restoreCodeBlock(doc, element)
    } else if (/^(☐|☒)\s*/.test(text)) {
      const checked = text.startsWith('☒')
      const todo = doc.createElement('div')
      const checkbox = doc.createElement('input')
      todo.setAttribute('data-w-e-type', 'todo')
      checkbox.type = 'checkbox'
      checkbox.disabled = true
      if (checked) checkbox.setAttribute('checked', '')
      Array.from(element.childNodes).forEach((node) => todo.appendChild(node.cloneNode(true)))
      removeTodoMarker(todo)
      todo.insertBefore(checkbox, todo.firstChild)
      element.replaceWith(todo)
    } else if (text === DIVIDER_TEXT) {
      element.replaceWith(doc.createElement('hr'))
    }
  })
  return doc.body.innerHTML
}

export { DIVIDER_TEXT }
