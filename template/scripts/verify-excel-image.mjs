/**
 * 云盘 Excel 图片导出回归验证。
 *
 * 根因：export.js 里 setImages 被定义了两次，后一个覆盖前一个，
 * 导致带单元格锚点的定位分支是死代码，所有图片都退回按像素估算位置；
 * 且失效分支里的 col_st / row_st 是未声明赋值，一旦生效会在严格模式下抛错。
 *
 * LuckyExcel 导入的图片带 fromCol/fromRow/toCol/toRow 锚点，应按锚点定位（twoCellAnchor）；
 * 在 Luckysheet 里新插入的图片只有 default 像素位置，退回估算（oneCellAnchor）。
 *
 * 运行：node scripts/verify-excel-image.mjs
 */
import assert from 'node:assert/strict'
import { writeFileSync, unlinkSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, dirname } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'
import { execSync } from 'node:child_process'

const root = join(dirname(fileURLToPath(import.meta.url)), '..')
const { exportExcel } = await import(pathToFileURL(join(root, 'src/components/openFile/export.js')).href)

// 1x1 透明 png
const PNG =
  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='

const cell = { v: 'x', m: 'x', ct: { fa: 'General', t: 'g' } }
const visible = [0, 72, 144, 216, 288, 360]

async function exportWith(images) {
  const blob = await exportExcel(
    [
      {
        name: 'Sheet1',
        data: [[cell]],
        config: {},
        images,
        visibledatacolumn: visible,
        visibledatarow: visible
      }
    ],
    'image',
    1
  )
  const file = join(tmpdir(), `oa-excel-image-${Date.now()}-${Math.random()}.xlsx`)
  writeFileSync(file, Buffer.from(await blob.arrayBuffer()))
  const list = execSync(`unzip -l "${file}"`).toString()
  let drawing = ''
  if (list.includes('xl/drawings/drawing1.xml')) {
    drawing = execSync(`unzip -p "${file}" xl/drawings/drawing1.xml`).toString()
  }
  try {
    unlinkSync(file)
  } catch (e) {
    // ignore
  }
  return { list, drawing }
}

// --- LuckyExcel 导入的图片：带单元格锚点 ---
{
  const { list, drawing } = await exportWith({
    img1: {
      src: PNG,
      fromCol: 1,
      fromRow: 2,
      toCol: 3,
      toRow: 4,
      default: { left: 72, top: 144, width: 144, height: 144 }
    }
  })
  assert.ok(list.includes('xl/media/'), '带锚点的图片没有被写入 xlsx')
  assert.ok(drawing.includes('twoCellAnchor'), '带 from/to 锚点的图片应使用 twoCellAnchor 精确定位')
  assert.match(drawing, /<xdr:col>1<\/xdr:col>/, '锚点起始列不是 fromCol 指定的 1')
  assert.match(drawing, /<xdr:row>2<\/xdr:row>/, '锚点起始行不是 fromRow 指定的 2')
}

// --- Luckysheet 新插入的图片：只有 default 像素位置 ---
{
  const { list, drawing } = await exportWith({
    img1: {
      src: PNG,
      default: { left: 72, top: 144, width: 144, height: 144 }
    }
  })
  assert.ok(list.includes('xl/media/'), '仅有 default 的图片没有被写入 xlsx')
  assert.ok(drawing.includes('oneCellAnchor'), '仅有 default 的图片应退回按像素估算的 oneCellAnchor')
}

// --- 没有图片时不应产生 drawing，也不能抛错 ---
{
  const { list } = await exportWith({})
  assert.ok(!list.includes('xl/drawings/'), '没有图片时不应写出 drawing')
}

console.log('verify-excel-image: PASS')
