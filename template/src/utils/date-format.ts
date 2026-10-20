/**
 * element-ui 的日期格式（yyyy / dd / timestamp）转成 Element Plus（dayjs）格式。
 * 表单设计器等已保存的配置里存的仍是旧格式，渲染时统一经过这里转换。
 */
export function toDayjsFormat(format?: string | null): string | undefined {
  if (!format) return format ?? undefined
  if (format === 'timestamp') return 'x'
  return format
    .replace(/yyyy/g, 'YYYY')
    .replace(/(?<!d)dd(?!d)/g, 'DD')
    .replace(/\bWW\b/g, 'ww')
}
