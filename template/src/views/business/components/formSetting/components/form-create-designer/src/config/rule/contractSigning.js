import uniqueId from '@form-create/utils/lib/unique'
const label = '合同签约'
const name = 'contractSigning'
export default {
  icon: 'iconfont iconqingjia2',
  label,
  name,
  loadChildren: false,
  rule() {
    return {
      type: name,
      field: uniqueId(),
      symbol: name,
      children: [
        {
          checkType: 0,
          display: true,
          effect: { fetch: '' },
          field: uniqueId(),
          hidden: false,
          info: '',
          input: false,
          title: '客户名称',
          symbol: 'eid',
          type: 'select',
          _fc_drag_tag: 'select',
          options: []
        },
        {
          effect: { fetch: '', required: true },
          field: uniqueId(),
          props: { type: 'radio' },
          title: '签约方式',
          symbol: 'signType',
          value: '2',
          type: 'radio',
          _fc_drag_tag: 'radio',
          options: [
            { value: '2', label: '电子签' },
            { value: '1', label: '线下签约' }
          ]
        },
        {
          effect: { fetch: '', required: true },
          field: uniqueId(),
          props: { type: 'radio' },
          title: '合同期限',
          symbol: 'termType',
          value: '2',
          type: 'radio',
          _fc_drag_tag: 'radio',
          options: [
            { value: '2', label: '签约日起算' },
            { value: '1', label: '固定期限' },
            { value: '0', label: '无期限' }
          ]
        },
        {
          type: 'input',
          field: uniqueId(),
          effect: { fetch: '', required: true },
          display: true,
          hidden: false,
          info: '',
          props: { placeholder: '请填写合同时长' },
          title: '合同时长（天）',
          symbol: 'dateCount',
          _fc_drag_tag: 'input'
        },
        {
          checkType: 0,
          display: true,
          effect: { fetch: '', required: true },
          field: uniqueId(),
          hidden: false,
          info: '',
          props: { placeholder: '请选择合同开始日期' },
          title: '合同开始日期',
          symbol: 'startDate',
          type: 'datePicker',
          _fc_drag_tag: 'datePicker'
        },
        {
          checkType: 0,
          display: true,
          effect: { fetch: '', required: true },
          field: uniqueId(),
          hidden: false,
          info: '',
          props: { placeholder: '请选择合同结束日期' },
          title: '合同结束日期',
          symbol: 'endDate',
          type: 'datePicker',
          _fc_drag_tag: 'datePicker'
        },

        {
          display: true,
          field: uniqueId(),
          hidden: false,
          info: '',
          title: '附件',
          symbol: 'signFile', // 合同附件
          type: 'uploadFrom',
          _fc_drag_tag: 'uploadFrom'
        },

        {
          type: 'input',
          field: uniqueId(),
          display: true,
          hidden: false,
          info: '',
          props: { type: 'textarea', placeholder: '请填写备注信息' },
          title: '备注',
          symbol: 'mark',
          _fc_drag_tag: 'textarea'
        },
        {
          field: uniqueId(),
          display: true,
          hidden: false,
          title: '签署方',
          label: '签署方',
          props: { member: false },
          type: 'approvalBill',
          symbol: 'signatory',
          _fc_drag_tag: 'approvalBill',
          children: [
            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写企业名称' },
              title: '企业名称',
              symbol: 'companyName',
              _fc_drag_tag: 'input'
            },
            {
              display: true,
              field: uniqueId(),
              hidden: false,
              info: '',
              props: { member: true, range: ['oneself'], placeholder: '' },
              title: '经办人',
              symbol: 'name',
              type: 'departmentTree',
              _fc_drag_tag: 'memberTree'
            },
            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写联系电话' },
              title: '电话',
              symbol: 'phone',
              _fc_drag_tag: 'input'
            }
          ]
        },
        {
          field: uniqueId(),
          display: true,
          hidden: false,
          title: '产品清单',
          label: '产品清单',
          props: { member: false },
          type: 'approvalBill',
          symbol: 'productInfo',
          _fc_drag_tag: 'approvalBill',
          children: [
            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写产品名称' },
              title: '产品名称',
              symbol: 'product_name',
              _fc_drag_tag: 'input'
            },
            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写规格' },
              title: '规格',
              symbol: 'sku',
              _fc_drag_tag: 'input'
            },

            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写数量' },
              title: '数量',
              symbol: 'count',
              _fc_drag_tag: 'input'
            },

            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写折扣' },
              title: '折扣',
              symbol: 'discount',
              _fc_drag_tag: 'input'
            },
            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写总价' },
              title: '总价',
              symbol: 'total_price',
              _fc_drag_tag: 'input'
            },
            {
              type: 'input',
              field: uniqueId(),
              display: true,
              hidden: false,
              info: '',
              props: { placeholder: '请填写备注' },
              title: '备注',
              symbol: 'remark',
              _fc_drag_tag: 'input'
            }
          ]
        }
      ]
    }
  },

  basic() {
    return [
      {
        checkType: 0,
        display: true,
        field: uniqueId(),
        hidden: false,
        info: '',
        props: {
          value: '1.开具发票是否需要审批流，在客户规格设置中配置<br>2.支持财务进行发票开具/拒绝开票',
          title: '开具发票规则'
        },
        input: false,
        title: '',
        type: 'infoForm',
        _fc_drag_tag: 'infoForm'
      }
    ]
  }
}
