import dayjs from 'dayjs'

/** 旧版日期面板实例，快捷项通过 pick 事件回填 */
interface ShortcutPicker {
  $emit: (event: 'pick', value: Array<Date | string>) => void
}

//日历搜索快捷键
export default {
  shortcuts: [
    {
      text: '今天',
      onClick(picker: ShortcutPicker) {
        const end = new Date();
        const start = new Date();
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '昨天',
      onClick(picker: ShortcutPicker) {
        const end = new Date();
        const start = new Date();
        start.setTime(start.getTime() - 3600 * 1000 * 24 * 1);
        picker.$emit('pick', [start, start]);
      },
    },
    {
      text: '本月',
      onClick(picker: ShortcutPicker) {
        const start = new Date();
        const end = new Date();
        start.setMonth(start.getMonth());
        start.setDate(1);
        end.setMonth(end.getMonth() + 1);
        end.setDate(0);
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '上月',
      onClick(picker: ShortcutPicker) {
        const start = dayjs().subtract(1, 'month').startOf('month').format('YYYY/MM/DD');
        const end = dayjs().subtract(1, 'month').endOf('month').format('YYYY/MM/DD');
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '最近7天',
      onClick(picker: ShortcutPicker) {
        const end = new Date();
        const start = new Date();
        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '最近30天',
      onClick(picker: ShortcutPicker) {
        const end = new Date();
        const start = new Date();
        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '最近90天',
      onClick(picker: ShortcutPicker) {
        const end = new Date();
        const start = new Date();
        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '最近1年',
      onClick(picker: ShortcutPicker) {
        const end = new Date();
        const start = new Date();
        start.setTime(start.getTime() - 3600 * 1000 * 24 * 365);
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '本年',
      onClick(picker: ShortcutPicker) {
        const start = dayjs().startOf('year').format('YYYY/MM/DD HH:mm:ss');
        const end = dayjs().format('YYYY/MM/DD HH:mm:ss');
        picker.$emit('pick', [start, end]);
      },
    },
    {
      text: '去年',
      onClick(picker: ShortcutPicker) {
        const start = dayjs().subtract(1, 'year').startOf('year').format('YYYY/MM/DD HH:mm:ss');
        const end = dayjs().subtract(1, 'year').endOf('year').format('YYYY/MM/DD HH:mm:ss');
        picker.$emit('pick', [start, end]);
      },
    },
  ]
};