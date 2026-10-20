const DIVIDER_TEXT = '─'.repeat(40)
const ALIGNMENT_MARKER_PREFIX = 'oa-docx-paragraph-align-'
// PHPWord 保存左右对齐时使用 start / end，Word 文件也可能使用 left / right。
const DOCX_ALIGNMENTS: Record<string, string> = {
  left: 'left',
  start: 'left',
  center: 'center',
  right: 'right',
  end: 'right',
  both: 'justify',
  distribute: 'justify'
}

/**
 * Mammoth 会读取段落 alignment，但转换 HTML 时不输出它。
 * 通过临时书签把对齐信息随段落带入 HTML，避免按顺序匹配时被空段落、标题和列表错位。
 */
export function markDocxParagraphAlignment<T extends { alignment?: string; children: unknown[] }>(paragraph: T) {
  const alignment = DOCX_ALIGNMENTS[paragraph.alignment || '']
  if (!alignment) return paragraph

  return {
    ...paragraph,
    children: [
      { type: 'bookmarkStart', name: ALIGNMENT_MARKER_PREFIX + alignment },
      ...paragraph.children
    ]
  }
}

/** 恢复 Word 段落的对齐样式，并移除转换期间使用的临时书签。 */
export function restoreDocxParagraphAlignment(html: string) {
  if (!html || !html.includes(ALIGNMENT_MARKER_PREFIX)) return html

  const doc = new DOMParser().parseFromString(html, 'text/html')
  doc.querySelectorAll(`a[id^="${ALIGNMENT_MARKER_PREFIX}"]`).forEach((marker) => {
    const alignment = marker.id.slice(ALIGNMENT_MARKER_PREFIX.length)
    const paragraph = marker.closest<HTMLElement>('p, h1, h2, h3, h4, h5, h6, li, td, th')
    if (paragraph && ['left', 'center', 'right', 'justify'].includes(alignment)) {
      paragraph.style.textAlign = alignment
    }
    marker.remove()
  })
  return doc.body.innerHTML
}

function appendStyle(element: HTMLElement, name: string, value: string) {
  if (!element.style.getPropertyValue(name)) {
    element.style.setProperty(name, value)
  }
}

function cleanEditorAttributes(root: Element) {
  Array.from(root.querySelectorAll('*')).forEach((element) => {
    Array.from(element.attributes).forEach((attribute) => {
      if (attribute.name.startsWith('data-w-e-') || attribute.name === 'contenteditable') {
        element.removeAttribute(attribute.name)
      }
    })
  })
}

function normalizeTodo(doc: Document) {
  Array.from(doc.querySelectorAll('div[data-w-e-type="todo"]')).forEach((todo) => {
    const checkbox = todo.querySelector<HTMLInputElement>('input[type="checkbox"]')
    const paragraph = doc.createElement('p')
    const marker = checkbox && checkbox.checked ? '☒ ' : '☐ '

    paragraph.appendChild(doc.createTextNode(marker))
    Array.from(todo.childNodes).forEach((node) => {
      if (node !== checkbox) paragraph.appendChild(node.cloneNode(true))
    })
    todo.replaceWith(paragraph)
  })
}

function normalizeCodeBlocks(doc: Document) {
  Array.from(doc.querySelectorAll('pre > code')).forEach((code) => {
    const pre = code.parentElement!
    while (code.firstChild) pre.insertBefore(code.firstChild, code)
    code.remove()
    appendStyle(pre, 'font-family', 'Consolas, "Courier New", monospace')
    appendStyle(pre, 'white-space', 'pre-wrap')
  })
}

function normalizeDividers(doc: Document) {
  Array.from(doc.querySelectorAll('hr')).forEach((divider) => {
    const paragraph = doc.createElement('p')
    paragraph.textContent = DIVIDER_TEXT
    paragraph.style.color = '#d9d9d9'
    divider.replaceWith(paragraph)
  })
}

function normalizeTables(doc: Document) {
  Array.from(doc.querySelectorAll('table')).forEach((table) => {
    const columnWidths = Array.from(table.querySelectorAll<HTMLElement>('colgroup col')).map((col) => {
      const width = parseFloat(col.getAttribute('width') || col.style.width)
      return Number.isFinite(width) && width > 0 ? width : 0
    })
    const firstRowCells = Array.from(table.querySelectorAll<HTMLElement>('tr:first-child > th, tr:first-child > td'))

    if (columnWidths.length && firstRowCells.length) {
      let columnIndex = 0
      firstRowCells.forEach((cell) => {
        const span = Math.max(parseInt(cell.getAttribute('colspan') as string, 10) || 1, 1)
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

    Array.from(table.querySelectorAll<HTMLElement>('th, td')).forEach((cell) => {
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
export function normalizeDocxHtml(html: string) {
  if (!html) return html

  const doc = new DOMParser().parseFromString(html, 'text/html')
  normalizeTodo(doc)
  normalizeCodeBlocks(doc)
  normalizeDividers(doc)
  normalizeTables(doc)
  cleanEditorAttributes(doc.body)
  return doc.body.innerHTML
}

function removeTodoMarker(element: Node) {
  const nodes: Node[] = [element]
  while (nodes.length) {
    const node = nodes.shift()!
    if (node.nodeType === Node.TEXT_NODE && /^[\s]*(☐|☒)\s*/.test(node.textContent || '')) {
      node.textContent = (node.textContent || '').replace(/^[\s]*(☐|☒)\s*/, '')
      return
    }
    nodes.unshift(...Array.from(node.childNodes || []))
  }
}

function isCodeParagraph(element: Element) {
  if (element.tagName !== 'P' || !(element.textContent || '').trim()) return false

  return [element, ...Array.from(element.querySelectorAll('[style]'))].some((node) =>
    /(^|[\s,"])(consolas)([\s,"]|$)/i.test((node as HTMLElement).style.fontFamily || '')
  )
}

function restoreCodeBlock(doc: Document, paragraph: Element) {
  const pre = doc.createElement('pre')
  const code = doc.createElement('code')
  code.textContent = paragraph.textContent || ''
  pre.appendChild(code)
  paragraph.replaceWith(pre)
}

/** 恢复经过 Word 保存后仍可识别的 wangEditor 节点。 */
export function restoreDocxEditorHtml(html: string) {
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
