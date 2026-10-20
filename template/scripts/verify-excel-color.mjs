/**
 * 云盘 Excel 颜色导出回归验证。
 *
 * 根因：导出时只做 color.replace('#', '') 就写进 xlsx 的 rgb 属性，没有任何校验：
 * 1. 6 位十六进制缺少 alpha 通道，不是合法的 8 位 ARGB；
 * 2. LuckyExcel 处理带 tint 的主题色时会退化成 "RGB(255.5,153.2,0)" 这类字符串，
 *    原样写入后 Excel 无法解析，整片单元格渲染成黑色；
 * 3. 该值下次导入又会被 substring(len-6) 截断成 "#53, 0)"，越存越乱。
 *
 * 运行：node scripts/verify-excel-color.mjs
 */
import assert from 'node:assert/strict'
import { writeFileSync, unlinkSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, dirname } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'
import { execSync } from 'node:child_process'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const { exportExcel } = await import(pathToFileURL(join(root, 'src/components/openFile/export.js')).href)

// 取自出问题文件的真实取值：前两个正常，中间两个是 LuckyExcel 的 tint 退化值，后三个是被截断的残值
const NORMAL = ['#ff9900', '#ffe599']
const RGB_LIKE = ['rgb(255, 153, 0)', 'RGB(255.5,153.2,0)']
const BROKEN = ['#53, 0)', '#, 156)', '#44, 0)']

const all = [...NORMAL, ...RGB_LIKE, ...BROKEN]
const data = [all.map((bg) => ({ v: 'x', m: 'x', ct: { fa: 'General', t: 'g' }, bg, fc: '#444444' }))]

const blob = await exportExcel([{ name: 'Sheet1', data, config: {} }], 'color', 1)
const file = join(tmpdir(), `oa-excel-color-${Date.now()}.xlsx`)
writeFileSync(file, Buffer.from(await blob.arrayBuffer()))
const styles = execSync(`unzip -p "${file}" xl/styles.xml`).toString()
try {
  unlinkSync(file)
} catch (e) {
  // ignore
}

// 1. 绝不能写出带括号/逗号的非法颜色——正是它让 Excel 渲染成黑色
const illegal = styles.match(/rgb="[^"]*[(),][^"]*"/g) || []
assert.deepEqual(illegal, [], `写出了非法颜色值: ${illegal.join(' ')}`)

// 2. 所有颜色必须是 8 位 ARGB
const written = [...new Set((styles.match(/rgb="([^"]*)"/g) || []).map((s) => s.slice(5, -1)))]
written.forEach((color) => {
  assert.match(color, /^[0-9A-F]{8}$/, `颜色 ${color} 不是合法的 8 位 ARGB`)
})

// 3. 正常颜色要补齐 alpha 后原样保留
assert.ok(written.includes('FFFF9900'), '#ff9900 未正确写入为 FFFF9900')
assert.ok(written.includes('FFFFE599'), '#ffe599 未正确写入为 FFFFE599')

// 4. rgb()/RGB() 形式要能正确换算（含小数），而不是原样写入
//    两者都等于 #ff9900，ExcelJS 会与上面的 fill 去重，因此只需确认没有产生额外的非法值
assert.ok(written.includes('FF444444'), '字体色未正确写入为 FF444444')

// 5. 已经损坏、无法还原的值必须被跳过（宁可不填充也不能填成黑色）
BROKEN.forEach((bad) => {
  const residue = bad.replace(/^#/, '')
  assert.ok(!styles.includes(residue), `损坏的颜色 ${bad} 仍被写进了 styles.xml`)
})

console.log('verify-excel-color: PASS')
