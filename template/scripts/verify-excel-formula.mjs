/**
 * 云盘 Excel 公式导出回归验证。
 * 根因：ExcelJS 公式不能带前导 '='，LuckyExcel 导入会再补 '='；
 * 若导出写成 '=A1+A2'，再次打开会变成 '==A1+A2'，计算值异常。
 *
 * 运行：node scripts/verify-excel-formula.mjs
 */
import assert from 'node:assert/strict'
import { createRequire } from 'node:module'
import { readFileSync, unlinkSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, dirname } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const require = createRequire(import.meta.url)
const ExcelJS = require('exceljs')
const LuckyExcel = require('luckyexcel')
const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const helperUrl = pathToFileURL(join(root, 'src/components/openFile/excelFormula.js')).href

const { toExcelJsFormula } = await import(helperUrl)

assert.equal(toExcelJsFormula('=A1+A2'), 'A1+A2')
assert.equal(toExcelJsFormula('==SUM(A1:A2)'), 'SUM(A1:A2)')
assert.equal(toExcelJsFormula('A1+A2'), 'A1+A2')
assert.equal(toExcelJsFormula(''), '')
assert.equal(toExcelJsFormula(null), '')

async function importFormulas(file) {
  return new Promise((resolve, reject) => {
    try {
      LuckyExcel.transformExcelToLucky(readFileSync(file), (exportJson) => {
        resolve(
          (exportJson.sheets?.[0]?.celldata || [])
            .filter((cell) => cell.v && cell.v.f)
            .map((cell) => cell.v.f)
        )
      })
    } catch (error) {
      reject(error)
    }
  })
}

async function writeAndRead(formula) {
  const file = join(tmpdir(), `oa-excel-formula-${Date.now()}-${Math.random()}.xlsx`)
  const wb = new ExcelJS.Workbook()
  const ws = wb.addWorksheet('Sheet1')
  ws.getCell('A1').value = 10
  ws.getCell('A2').value = 20
  ws.getCell('A3').value = { formula, result: 30 }
  await wb.xlsx.writeFile(file)
  const formulas = await importFormulas(file)
  try {
    unlinkSync(file)
  } catch (e) {
    // ignore
  }
  return formulas
}

// 对照：旧写法（带 =）会得到 ==
const buggy = await writeAndRead('=A1+A2')
assert.deepEqual(buggy, ['==A1+A2'], 'baseline: exporting with leading = must produce == on re-import')

// 修复写法：去掉 = 后应得到单 =
const fixed = await writeAndRead(toExcelJsFormula('=A1+A2'))
assert.deepEqual(fixed, ['=A1+A2'])
assert.equal(fixed[0].startsWith('=='), false)

const fixedSum = await writeAndRead(toExcelJsFormula('==SUM(A1:A2)'))
assert.deepEqual(fixedSum, ['=SUM(A1:A2)'])

console.log('verify-excel-formula: PASS')
