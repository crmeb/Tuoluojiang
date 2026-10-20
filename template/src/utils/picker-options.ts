/**
 * element-ui 的 `picker-options` 在 Element Plus 中拆成了独立 prop：
 * disabledDate → `disabled-date`，shortcuts → `shortcuts`（且快捷项由 onClick(picker) 改为 value），
 * onPick({ minDate, maxDate }) → `calendar-change` 事件（参数为 [minDate, maxDate]）。
 * 模板里写 `v-bind="$pickerProps(options)"`，沿用原有的 pickerOptions 对象。
 */
type LegacyShortcut = { text: string; onClick?: (picker: any) => void; value?: unknown }
type LegacyPickerOptions = {
  disabledDate?: (date: Date) => boolean
  shortcuts?: LegacyShortcut[]
  onPick?: (range: { minDate: Date | null; maxDate: Date | null }) => void
  [key: string]: unknown
}

function toShortcut(item: LegacyShortcut) {
  if (item.value !== undefined || !item.onClick) return item
  const onClick = item.onClick
  return {
    text: item.text,
    // 点击时再执行原 onClick，捕获它 $emit('pick', value) 的值；保证「今天」等相对日期按点击时刻计算
    value: () => {
      let picked: unknown
      onClick({ $emit: (_event: string, value: unknown) => (picked = value) })
      return picked
    },
  }
}

export function toPickerProps(options?: LegacyPickerOptions | string | null) {
  if (!options || typeof options !== 'object') return {}
  const props: Record<string, unknown> = {}
  if (options.disabledDate) props.disabledDate = options.disabledDate
  if (options.shortcuts) props.shortcuts = options.shortcuts.map(toShortcut)
  if (options.onPick) {
    const onPick = options.onPick
    props.onCalendarChange = ([minDate, maxDate]: [Date | null, Date | null]) => onPick({ minDate, maxDate })
  }
  return props
}
