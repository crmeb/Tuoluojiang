// 迁移期检查：用 Vue 3 编译器编译 src 下全部 SFC，汇总模板/脚本编译错误。
// 用法：node scripts/vue3-compile-check.mjs [按关键字打印具体位置]
import { parse, compileTemplate, compileScript } from 'vue/compiler-sfc'
import fg from 'fast-glob'
import fs from 'node:fs'
const files = await fg('src/**/*.vue')
const counts = {}
let errFiles = 0
for (const f of files) {
  const src = fs.readFileSync(f, 'utf8')
  const { descriptor, errors } = parse(src, { filename: f })
  const errs = [...errors]
  let bindings
  try {
    if (descriptor.script || descriptor.scriptSetup) bindings = compileScript(descriptor, { id: 'x' }).bindings
  } catch (e) {
    errs.push(e)
  }
  if (descriptor.template) {
    // 带上脚本的绑定信息，才能查出「v-model 绑定到 prop」这类错误
    const r = compileTemplate({
      source: descriptor.template.content,
      filename: f,
      id: 'x',
      compilerOptions: { bindingMetadata: bindings }
    })
    errs.push(...r.errors)
  }
  if (errs.length) {
    errFiles++
    for (const e of errs) {
      const msg = (typeof e === 'string' ? e : e.message).split('\n')[0]
      const key = msg.replace(/".*?"/g, '""').slice(0, 90)
      counts[key] = (counts[key] || 0) + 1
      if (process.argv[2] && msg.includes(process.argv[2])) console.log(f + ':' + (e.loc ? e.loc.start.line : '?') + ' ' + msg)
    }
  }
}
console.log('files with errors:', errFiles, '/', files.length)
console.log(Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 30).map(([k, v]) => v + '  ' + k).join('\n'))
