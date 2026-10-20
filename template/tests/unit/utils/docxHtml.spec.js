import { DIVIDER_TEXT, normalizeDocxHtml, restoreDocxEditorHtml } from '@/utils/wangeditor/docxHtml'

describe('wangEditor DOCX HTML compatibility', () => {
  it('converts and restores todo items without submitting checkbox inputs', () => {
    const saved = normalizeDocxHtml(
      '<div data-w-e-type="todo"><input type="checkbox" disabled checked><strong>done</strong></div>'
    )

    expect(saved).not.toContain('<input')
    expect(saved).toContain('☒')

    const restored = restoreDocxEditorHtml(saved)
    expect(restored).toContain('data-w-e-type="todo"')
    expect(restored).toContain('type="checkbox"')
    expect(restored).toContain('checked=""')
    expect(restored).toContain('<strong>done</strong>')
  })

  it('removes editor-only table markup and keeps column widths on cells', () => {
    const saved = normalizeDocxHtml(
      '<table style="width: auto;height:auto"><colgroup contentEditable="false"><col width="120"></col><col width="80"></col></colgroup><tbody><tr><td width="auto">A</td><td width="auto">B</td></tr></tbody></table>'
    )

    expect(saved).not.toContain('<colgroup')
    expect(saved).not.toContain('contenteditable')
    expect(saved).not.toContain('width="auto"')
    expect(saved).toContain('width: 120px')
    expect(saved).toContain('width: 80px')
    expect(saved).toContain('border-collapse: collapse')
  })

  it('keeps code text while removing the nested code element', () => {
    const saved = normalizeDocxHtml('<pre><code class="language-js">const value = 1;\nreturn value;</code></pre>')

    expect(saved).toContain('<pre')
    expect(saved).not.toContain('<code')
    expect(saved).toContain('const value = 1;\nreturn value;')
  })

  it('restores Word code paragraphs as wangEditor code blocks', () => {
    const restored = restoreDocxEditorHtml(
      '<p><span style="font-family: Consolas;">const value = 1;</span></p><p>normal text</p>'
    )

    expect(restored).toContain('<pre><code>const value = 1;</code></pre>')
    expect(restored).toContain('<p>normal text</p>')
  })

  it('round-trips dividers through a Word-safe text marker', () => {
    const saved = normalizeDocxHtml('<p>before</p><hr><p>after</p>')
    expect(saved).not.toContain('<hr')
    expect(saved).toContain(DIVIDER_TEXT)

    const restored = restoreDocxEditorHtml(saved)
    expect(restored).toContain('<hr>')
  })
})
