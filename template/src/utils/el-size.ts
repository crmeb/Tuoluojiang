/**
 * element-ui 尺寸（medium / small / mini）转成 Element Plus 尺寸（default / small / large）。
 * 表单设计器等已保存的配置里仍是旧值，渲染时经过这里转换。
 */
const SIZE_MAP: Record<string, string> = { medium: 'default', mini: 'small' }

export function toElSize(size?: string | null): string | undefined {
  if (!size) return undefined
  return SIZE_MAP[size] || size
}
