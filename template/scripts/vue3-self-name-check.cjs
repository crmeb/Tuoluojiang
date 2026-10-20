// 迁移期检查：组件 name 与本地注册的子组件同名且模板里用到时，Vue 3 会优先解析成自身导致无限递归。用法：node scripts/vue3-self-name-check.cjs
// 找出「组件 name 与本地注册的子组件同名、且模板里用了这个标签」的 SFC：Vue 3 会优先解析成自身，造成无限递归
const fg = require('fast-glob'); const fs = require('fs')
const { parse } = require('vue/compiler-sfc')
const norm = (s) => s.replace(/-(\w)/g, (_, c) => c.toUpperCase()).replace(/^\w/, (c) => c.toUpperCase())
for (const f of fg.sync('src/**/*.vue')) {
  const { descriptor } = parse(fs.readFileSync(f, 'utf8'))
  const script = descriptor.script && descriptor.script.content
  if (!script || !descriptor.template) continue
  const nm = script.match(/\n\s{2}name:\s*['"]([^'"]+)['"]/)
  if (!nm) continue
  const self = norm(nm[1])
  const comp = script.match(/\n\s{2}components:\s*\{([\s\S]*?)\n\s{2}\}/)
  if (!comp) continue
  const keys = [...comp[1].matchAll(/^\s*['"]?([\w-]+)['"]?\s*[:,]?/gm)].map((m) => m[1])
  if (!keys.some((k) => norm(k) === self)) continue
  const tags = new Set()
  const walk = (n) => { if (n.type === 1) tags.add(norm(n.tag)); (n.children || []).forEach(walk) }
  descriptor.template.ast.children.forEach(walk)
  if (tags.has(self)) console.log(f, 'name=' + nm[1])
}
