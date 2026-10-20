<script>
import xitongImg from '@/assets/images/empty/xitong.png';
import duanxinImg from '@/assets/images/empty/duanxin.png';
import qiweiImg from '@/assets/images/empty/qiwei.png';
import dingdingImg from '@/assets/images/empty/dingding.png';
import qitaImg from '@/assets/images/empty/qita.png';

const messagePushData = [
  {
    icon: xitongImg,
    name: '系统通知',
    is_edit: false,
    status: 'system_status'
  },
  {
    icon: duanxinImg,
    name: '短信',
    status: 'sms_status',
    is_edit: false,
    key: 'sms_template_id',
    placeholder: '请输入一号通中短信模板编号'
  },
  {
    icon: qiweiImg,
    name: '企微BOT',
    is_edit: false,
    status: 'work_webhook_status',
    key: 'work_webhook_url',
    placeholder: '请输入企业微信机器人生成的Webhook'
  },
  {
    icon: dingdingImg,
    name: '钉钉BOT',
    is_edit: false,
    status: 'ding_webhook_status',
    key: 'ding_webhook_url',
    placeholder: '请输入钉钉机器人生成的Webhook'
  },
  {
    icon: qitaImg,
    name: '其他BOT',
    is_edit: false,
    status: 'other_webhook_status',
    key: 'other_webhook_url',
    placeholder: '请输入其他第三方机器人生成的Webhook'
  }
]
const weekOptions = [
  {
    value: '1',
    label: '周一'
  },
  {
    value: '2',
    label: '周二'
  },
  {
    value: '3',
    label: '周三'
  },
  {
    value: '4',
    label: '周四'
  },
  {
    value: '5',
    label: '周五'
  },
  {
    value: '6',
    label: '周六'
  },
  {
    value: '7',
    label: '周日'
  }
]
const timeOptions = [
  {
    value: '0',
    label: '每隔N秒'
  },
  {
    value: '1',
    label: '每隔N分钟'
  },
  {
    value: '2',
    label: '每隔N小时'
  },
  {
    value: '3',
    label: '每隔N天'
  },
  {
    value: '4',
    label: '每天'
  },
  {
    value: '5',
    label: '每星期'
  },
  {
    value: '6',
    label: '每月'
  },
  {
    value: '7',
    label: '每年'
  }
]

const activities = [
  {
    title: '当发生动作时',
    icon: 'iconfont icondangqian',
    color: '#1C9399',
    id: 1
  },
  {
    title: '就执行操作',
    icon: 'iconfont icondangqian',
    color: '#eebe77',
    id: 2
  },
  {
    title: '',
    icon: '',
    color: ''
  }
]
export default { messagePushData, weekOptions, timeOptions, activities }
</script>
