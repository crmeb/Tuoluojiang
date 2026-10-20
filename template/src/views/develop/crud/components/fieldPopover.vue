<!-- @FileDescription:  应用举例：低代码新建字段 -->
<template>
  <div class="popover-box">
    <el-popover placement="left-end" width="296" trigger="click" v-model:visible="popoverVisible">
      <div class="flex-box">
        <div v-for="item in list" :key="item.value" class="item" @click="handleClick(item)">
          <i class="iconfont" :class="item.icon"></i>
          <div class="field-text">{{ item.label }}</div>
        </div>
      </div>
      <template #reference>
        <el-button type="primary" v-if="title">
          {{ title }}<i class="el-icon-arrow-down">
          </i></el-button>
        <span v-else title="新建字段" class="el-icon-plus ml10 pointer"></span>
      </template>
    </el-popover>
    <fieldDialog ref="fieldDialog" @submit="submit" :typesObj="typeValue" :rowData="rowData" :dictList="dictList">
    </fieldDialog>
  </div>
</template>
<script>
import {
  dataFieldTypeApi,
  dataFieldSaveApi
} from '@/api/develop.ts'
import fieldDialog from './fieldDialog'
import { getDictListApi } from '@/api/form.ts'
export default {
  emits: ['getInfo'],
  name: '',
  components: { fieldDialog },
  props: {
    infoData: {
      type: Object,
      default: () => { }
    },
    typesObj: { // 字段类型对象
      type: Object,
      default: () => { }
    },
    title: {
      // 弹出框标题
      type: String,
      default: ''
    },

  },
  data() {
    return {
      rowData: {},
      typeValue: {},
      dictList: [],
      popoverVisible: false,
      list: [
        {
          icon: 'iconwenben1',
          label: '文本',
          value: 'text'
        }, {
          icon: 'iconshuzi2',
          label: '数字',
          value: 'number'
        }, {
          icon: 'iconxuanxiang',
          label: '选项',
          value: 'select'
        }, {
          icon: 'iconshijian1',
          label: '时间',
          value: 'date'
        }, {
          icon: 'icontupian5',
          label: '图片',
          value: 'image'
        }, {
          icon: 'iconwenjian5',
          label: '文件',
          value: 'file'
        }, {
          icon: 'iconguanlian',
          label: '一对一关联',
          value: 'oneToOne'
        }
      ]
    }
  },
  watch: {
    typesObj: {
      handler(newVal, oldVal) {
        if (newVal) {
          this.typeValue = newVal
        }
      },
      deep: true
    }
  },
  methods: {
    // 新建/编辑字段弹窗回调
    submit(data) {
      data.crud_id = this.infoData.id
      dataFieldSaveApi(data)
        .then((res) => {
          if (res.status == 200) {
            this.$refs.fieldDialog.handleClose()
this.$emit('getInfo')
          }
        })
        .catch((err) => {
          this.$message.error(err.message)
        })
    },
    async handleClick(val) {
      this.popoverVisible = false
      this.type = 'add'
      this.rowData = val

      const tasks = [];


      if (!this.typesObj) {
        tasks.push(this.getTypeList())
      }


      if (val.value == 'select') {
        tasks.push(this.getDictList())
      }

      try {
        await Promise.all(tasks)
        this.$refs.fieldDialog.openBox()
      } catch (error) {
        this.$message.error(error.message)
      }
    },
    // 获取字段类型
    async getTypeList() {
      const { data } = await dataFieldTypeApi()
      this.typeValue = {
        text: data[0].options,
        number: data[1].options,
        select: [...data[2].options, ...data[3].options],
        date: data[4].options,
        image: [data[5].options[0]],
        file: [data[5].options[1]],
        oneToOne: data[6].options,
      }

    },
    // 获取字典列表
    async getDictList() {
      let data = {
        page: 1,
        limit: '',
        form_value: this.rowData.value
      }
      const result = await getDictListApi(data)

      if (result.data.list.length > 0) {
        this.dictList = result.data.list.filter((item) => {
          return item.status == 1
        })
      } else {
        this.dictList = []
      }

    },
  }
}
</script>
<style scoped lang="scss">
.flex-box {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;

}

.item {
  cursor: pointer;
  width: 130px;
  height: 32px;
  display: flex;
  align-items: center;
  padding-left: 12px;
  border-radius: 4px 4px 4px 4px;
  border: 0px solid rgba(0, 0, 0, 0.88);
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;

  .iconfont {

    margin-right: 6px;
  }
}

.item:hover {
  background: #F7F7F7;
}
</style>
