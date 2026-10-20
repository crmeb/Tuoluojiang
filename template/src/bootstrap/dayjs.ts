// dayjs 插件与语言包是全局注册的：在启动时装一次，业务代码直接 `import dayjs from 'dayjs'` 即可。
// 插件对应原 moment 用法：带格式串解析（customParseFormat）、format 的 x / W / WW（advancedFormat + isoWeek）、
// startOf('isoWeek') / isoWeek() / isoWeekday()（isoWeek）、weekday()（weekday，按语言包的周起始日计算）。
import dayjs from 'dayjs'
import 'dayjs/locale/zh-cn'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import isoWeek from 'dayjs/plugin/isoWeek'
import weekday from 'dayjs/plugin/weekday'

dayjs.extend(customParseFormat)
dayjs.extend(advancedFormat)
dayjs.extend(isoWeek)
dayjs.extend(weekday)
dayjs.locale('zh-cn')
