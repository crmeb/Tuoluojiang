/**
 * 检查 Element Plus 弹出类组件的触发元素是否「恰好一个」。
 *
 * el-tooltip / el-popover / el-popconfirm / el-dropdown 的触发元素经 ElOnlyChild 渲染，
 * 只接受一个有效子节点：为空（如唯一子节点带 v-if 且条件为假）会告警 no valid child node found，
 * 多个会告警 requires exact only one valid child，两种情况弹出层都无法挂到触发元素上。
 *
 * 用法：node scripts/vue3-only-child-check.mjs
 */
import fs from 'node:fs'
import fg from 'fast-glob'
import { parse } from 'vue/compiler-sfc'

const NODE_ELEMENT = 1
const NODE_TEXT = 2
const NODE_COMMENT = 3

// 触发元素所在的插槽：popover / popconfirm 在 #reference，tooltip / dropdown 在默认插槽
const TRIGGER_SLOT = {
  'el-tooltip': 'default',
  'el-popover': 'reference',
  'el-popconfirm': 'reference',
  'el-dropdown': 'default',
}

const slotName = (tpl) => {
  const dir = tpl.props.find((p) => p.type === 7 && p.name === 'slot')
  return dir ? (dir.arg ? dir.arg.content : 'default') : null
}

const meaningful = (children) =>
  children.filter((c) => c.type !== NODE_COMMENT && !(c.type === NODE_TEXT && !c.content.trim()))

function triggerChildren(node, slot) {
  const named = node.children.filter((c) => c.type === NODE_ELEMENT && c.tag === 'template' && slotName(c))
  const target = named.find((c) => slotName(c) === slot)
  if (target) return meaningful(target.children)
  if (slot !== 'default') return null // 没写 #reference：可能用 virtual-ref 等，跳过
  return meaningful(node.children.filter((c) => !named.includes(c)))
}

const directive = (node, name) => node.props?.some((p) => p.type === 7 && p.name === name)
const label = (c) => (c.type === NODE_ELEMENT ? `<${c.tag}>` : c.type === NODE_TEXT ? '文本' : '插值')

function describe(children) {
  if (children.length === 0) return '触发插槽为空'
  // v-if / v-else-if / v-else 链算一个节点
  const units = children.filter((c) => !(directive(c, 'else') || directive(c, 'else-if')))
  if (units.length > 1) return `触发插槽有 ${units.length} 个节点：${units.map(label).join(' ')}`
  const [c] = units
  if (directive(c, 'if')) return children.some((x) => directive(x, 'else')) ? null : '唯一子节点带 v-if 且没有 v-else'
  if (directive(c, 'for')) return '唯一子节点是 v-for'
  if (c.type === NODE_ELEMENT && c.tag === 'template') return '唯一子节点是 <template>'
  if (c.type === NODE_ELEMENT && c.tag === 'slot') return '唯一子节点是 <slot>'
  return null
}

const problems = []
for (const file of fg.sync('src/**/*.vue')) {
  const { descriptor } = parse(fs.readFileSync(file, 'utf8'), { filename: file })
  if (!descriptor.template?.ast) continue
  const visit = (node) => {
    if (node.type === NODE_ELEMENT && TRIGGER_SLOT[node.tag]) {
      const children = triggerChildren(node, TRIGGER_SLOT[node.tag])
      const reason = children && describe(children)
      if (reason) {
        problems.push(`${file}:${node.loc.start.line} <${node.tag}> ${reason}`)
        if (process.argv.includes('--source')) problems.push(children.map((c) => '    ' + c.loc.source.replace(/\n\s*/g, ' ').slice(0, 220)).join('\n'))
      }
    }
    node.children?.forEach(visit)
  }
  visit(descriptor.template.ast)
}

problems.forEach((p) => console.log(p))
console.log(`\n共 ${problems.length} 处`)
