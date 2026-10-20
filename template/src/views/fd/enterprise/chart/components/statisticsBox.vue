<template>
  <div>
    <oaFromBox
      ref="oaFromBox"
      :isAddBtn="false"
      :isTotal="false"
      :isViewSearch="false"
      :search="search"
      :sortSearch="false"
      @confirmData="confirmData"
    ></oaFromBox>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import pickerOptions from '@/libs/pickerOptions.ts'
import { billCateApi } from '@/api/enterprise.ts'
import oaFromBox from '@/components/common/oaFromBox'
export default {
  emits: ['confirmData'],
  name: 'FormBox',
  components: {
    oaFromBox
  },
  data() {
    return {
      pickerOptions,
      timeVal: [
        dayjs().startOf('month').format('YYYY/MM/DD'),
        dayjs().endOf('month').format('YYYY/MM/DD')
      ],
      tableFrom: {
        time: '',
        type: '',
        cate_id: []
      },
      statusOptions: [{ label: '全部', value: '' }],
      sexOptions: [
        { label: '全部', value: '' },
        { label: '收入', value: '1' },
        { label: '支出', value: '0' }
      ],
      search: [
        {
          field_name: '收支类型',
          field_name_en: 'type',
          form_value: 'select',
          value: '',
          data_dict: [
            {
              value: '',
              label: '全部'
            },
            {
              value: '1',
              label: '收入'
            },
            {
              value: '0',
              label: '支出'
            }
          ]
        },
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker',
          data_dict: [
            dayjs().startOf('month').format('YYYY/MM/DD'),
            dayjs().endOf('month').format('YYYY/MM/DD')
          ]
        },
        {
          field_name: '账目分类',
          field_name_en: 'cate_id',
          form_value: 'cascader',
          data_dict: [],
          props: {
            collapseTags: true
          }
        }
      ]
    }
  },
  mounted() {
    this.tableFrom.time = this.timeVal[0] + '-' + this.timeVal[1]
    this.initSearchValue()
    this.getBillCate()
    this.confirmData(this.tableFrom)
  },
  methods: {
    // 筛选表单的 ruleForm 初始为空，挂载后同步一次 search 中配置的默认值，保证收支类型回显「全部」
    initSearchValue() {
      this.$refs.oaFromBox?.$refs?.formList?.reset?.()
    },
    // 切换记账类型：重新拉取对应的账目分类并清空已选分类
    handType(type) {
      this.tableFrom.cate_id = []
      if (type === '') {
        this.statusOptions = []
        this.getBillCate(type)
      } else {
        this.statusOptions = this.getBillCate(type)
      }
    },
    unique(arr) {
      return Array.from(new Set(arr))
    },
    // 获取收入类型
    getBillCate(type) {
      const data = {
        types: type
      }
      const list = [{ label: '全部', value: '' }]
      billCateApi(data).then((res) => {
        res.data === undefined ? (res.data = []) : res.data
        this.statusOptions = res.data
        this.setDataDict('cate_id', res.data)
      })
      return list
    },
    confirmData(where) {
      const isReset = where === 'reset'
      if (isReset) {
        this.timeVal = [
          dayjs().startOf('month').format('YYYY/MM/DD'),
          dayjs().endOf('month').format('YYYY/MM/DD')
        ]
        this.setDataDict('time', this.timeVal)
        where = {
          time: (this.tableFrom.time = this.timeVal[0] + '-' + this.timeVal[1]),
          type: '',
          cate_id: []
        }
      }
      const prevType = this.tableFrom.type
      this.tableFrom = { ...this.tableFrom, ...where }
      // 收支类型变更时联动刷新账目分类（原 treeChange 的业务逻辑）
      if (!isReset && this.tableFrom.type !== prevType) {
        this.handType(this.tableFrom.type)
      }
      // Object.keys(this.tableFrom).forEach((key) => {
      //   if (key == 'cate_id') {
      //     let arr2 = [].concat.apply([], this.tableFrom.status)
      //     if (arr2.length > 0) {
      //       this.tableFrom.cate_id = this.unique(arr2)
      //     } else {
      //       this.tableFrom.cate_id = []
      //     }
      //   }
      // })
      this.$emit('confirmData', this.tableFrom)
    },
    setDataDict(field_name_en, data_dict) {
      for (let i = 0; i < this.search.length; i++) {
        if (this.search[i].field_name_en == field_name_en) {
          this.search[i].data_dict = data_dict
          break
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped></style>
