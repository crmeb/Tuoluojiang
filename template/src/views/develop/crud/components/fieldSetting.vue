<template>
  <div>
    <div class="flex-between">
      <div class="title-16">字段列表</div>
      <div class="flex">
        <el-button @click="handleAdd" class="mr10">AI生成字段</el-button>
        <field-popover  title="新建字段" :infoData="infoData" :typesObj="typesList" @getInfo="getList" ></field-popover>
      </div>
    </div>
    <!-- 筛选 -->
    <div class="flex mb10 h32">
      <div class="inTotal">共 {{ tableData.length }} 项</div>
      <div class="ml14">
        <el-input
          v-model="where.name"
          prefix-icon="el-icon-search"
          placeholder="请输入关键字"
          clearable
          style="width: 250px"
          @change="getList"
          @keyup.stop.prevent.enter="getList"
          class="input"
        ></el-input>
      </div>
    </div>
    <!-- 表格 -->
    <div class="table-box" v-loading="loading">
      <el-table row-key="id" :data="tableData" style="width: 100%">
        <el-table-column prop="field_name_en" label="字段名称" :min-width="150">
          <template #default="scope">
            <span class="mr10"> {{ scope.row.field_name_en }}</span>
            <el-tooltip v-if="scope.row.field_name_en == 'id'" class="item" effect="dark" content="主键字段" placement="top-start">
              <el-tag size="small" class="ml4">主</el-tag>
            </el-tooltip>
            <el-tooltip v-if="scope.row.is_default == 1" class="item" effect="dark" content="系统字段" placement="top-start">
              <el-tag size="small" class="ml4" type="warning">系</el-tag>
            </el-tooltip>
            <el-tooltip v-if="scope.row.is_re_table > 0" class="item" effect="dark" content="主从字段" placement="top-start">
              <el-tag size="small" class="ml4" type="info">从</el-tag>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="field_name" label="显示名称" />
        <el-table-column prop="form_value" label="字段类型" />
        <el-table-column prop="is_default_value_not_null" label="是否必填">
          <template #default="scope">
            {{ scope.row.is_default_value_not_null > 0 ? '--' : '是' }}
          </template>
        </el-table-column>
        <el-table-column prop="data_dict_name" label="关联字典">
          <template #default="scope">
            {{ scope.row.data_dict_name || '--' }}
          </template>
        </el-table-column>
        <el-table-column prop="association_crud_table_name" label="引用实体">
          <template #default="scope">
            {{ getValue(scope.row.association_crud_table_name) }}
          </template>
        </el-table-column>

        <el-table-column prop="is_main" label="主字段展示">
          <template #default="scope">
            <el-switch
              v-if="scope.row.form_value == 'input'"
              :width="60"
              @change="handleMain(scope.row)"
              :disabled="scope.row.form_value !== 'input' || scope.row.is_main == 1"
              v-model="scope.row.is_main"
              :active-value="1"
              :inactive-value="0"
              active-text="开启"
              inactive-text="关闭"
            >
            </el-switch>
          </template>
        </el-table-column>

        <el-table-column prop="address" label="操作" fixed="right" width="170">
          <template #default="scope">
            <el-button v-if="scope.row.is_default !== 1" type="primary" link @click="editFn(scope.row)">编辑</el-button>
            <el-button v-if="scope.row.is_default !== 1" type="primary" link @click="deleteFn(scope.row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>
    <oa-dialog
      ref="oaDialog"
      :fromData="fromData"
      :formConfig="formConfig"
      :formRules="formRules"
      :formDataInit="formDataInit"
      @submit="submit"
    ></oa-dialog>
    <!-- ai新建字段 -->
    <fieldsettingAi ref="fieldsettingAi" :info="info" :typesList="addTypesList" @getList="getList"></fieldsettingAi>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public.ts'
import Commnt from '@/components/develop/commonData'
import oaDialog from '@/components/form-common/dialog-form'
import fieldPopover from './fieldPopover'
import fieldsettingAi from './fieldsettingAi'
import { getDictListApi } from '@/api/form.ts'

import {
  dataFieldTypeApi,
  dataFieldListApi,
  dataFieldDeleteApi,
  dataFieldInfoApi,
  dataFieldUpdateApi,
  dataFieldMainApi
} from '@/api/develop.ts'

export default {
  name: '',
  components: { oaDialog, fieldPopover, fieldsettingAi },
  props: {
    infoData: {
      type: Object,
      default: () => {}
    }
  },
  data() {
    return {
      loading: false,
      total: 0,
      where: {
        name: ''
      },
      info: this.infoData,
      fromData: {
        width: '600px',
        title: '新建字段',
        btnText: '确定',
        labelWidth: '100px',
        type: ''
      },
      type: 'add',
      rowData: {},
      rowId: 0,
      formConfig: [],
      formRules: Commnt.fieldRules,
      formDataInit: Commnt.fieldDataInit,
      typesList: {}, // 新增字段数据
      addTypesList: [], // 编辑字段数据
      tableData: [], // 表格数据
      dictList: [], // 字典数据
      
      valueItem: [
        'crud_id',
        'value',
        'field_name',
        'field_name_en',
        'is_uniqid',
        'is_default_value_not_null', // 允许空值
        'is_table_show_row', // 列表默认显示
        'create_modify', // 新增时修改
        'update_modify', // 更新时修改
        'comment',
        'data_dict_id',
        'data_type', // 数据选项
        'customizeItems',
        'association_crud_id',
        'association_field_names',
        'association_field_names_list'
      ]
    }
  },

  mounted() {
    this.getTypeList()
    this.getList()
  },

  methods: {
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
    // 数组转成字符串
    getValue(val) {
      let str = ''
      if (val == '') {
        str = '--'
      } else if (Array.isArray(val)) {
        str = val.toString()
      } else {
        str = val
      }
      return str || '--'
    },

    // 打开ai生成字段弹窗
    handleAdd() {
      this.$refs.fieldsettingAi.openBox()
    },

    // 获取全部字段类型
    async getTypeList() {
      const {data} = await dataFieldTypeApi()
      this.addTypesList = data
      this.typesList = {
        text: data[0].options,
        number: data[1].options,
        select: [...data[2].options, ...data[3].options],
        date: data[4].options,
        image:[data[5].options[0]],
        file:[data[5].options[1]],
        oneToOne: data[6].options,
      }
    },

    // 获取字段列表
    async getList() {
      this.loading = true
      const data = await dataFieldListApi(this.info.id, this.where)
      this.tableData = data.data
      this.loading = false
    },

    // 获取字段详情
   async editFn(row) {
      this.rowData = row
       if (['radio', 'cascader_radio', 'checkbox', 'cascader', 'tag'].includes(row.form_value)) {
       await this.getDictList()
         
       }
      this.rowId = row.id
      this.type = 'edit'
      let fieldList = []
      this.addTypesList.forEach((item) => {
        fieldList.push(...item.options)
      })

      let typeObj = {
        type: 'select',
        label: '字段类型：',
        key: 'value',
        options: fieldList
      }

      // 编辑回显数据
      dataFieldInfoApi(row.id).then((res) => {
        let obj = res.data.options
        for (let key in res.data) {
          if (this.valueItem.includes(key)) {
            this.formDataInit[key] = res.data[key]
          }
        }
        this.formDataInit.value = res.data.form_value
        this.formDataInit = { ...this.formDataInit, ...obj }
        this.fromData.title = `编辑字段`
        this.fromData.type = 'edit'
        let dynamicList = Commnt.keyValue[res.data.form_value]
        if (dynamicList) {
          dynamicList.forEach((item) => {
            item.form_value = res.data.form_value
            if (item.sign == 'dict') {
              item.options = this.dictList
            }
          })
        } else {
          dynamicList = []
        }
        this.formConfig = []
        // 深拷贝是为了不修改原数组
        let config = JSON.parse(JSON.stringify(Commnt.fieldConfig))
        config.splice(2, 0, ...dynamicList)
        this.formConfig = config
        this.formConfig.splice(2, 0, typeObj)
        this.$refs.oaDialog.openBox()
      })
    },
    // 删除字段
    deleteFn(row) {
      modalSure('您确定要删除此字段数据吗').then(() => {
        dataFieldDeleteApi(row.id).then((res) => {
          this.getList()
        })
      })
    },

    // 开启主展示字段
    handleMain(row) {
      if (row.is_main == 1) {
        dataFieldMainApi(row.id).then((res) => {
          this.getList()
        })
      }
    },

   

    // 新建/编辑字段弹窗回调
    submit(data) {
      data.crud_id = this.infoData.id
      if (this.type == 'edit') {
        dataFieldUpdateApi(this.rowId, data)
          .then((res) => {
            if (res.status == 200) {
              this.$refs.oaDialog.handleClose()
              this.getList()
            }
          })
          .catch((err) => {
            this.$message.error(err.message)
          })
      } 
    },
    pageChange(page) {
      this.where.page = page
      this.getList()
    },

    handleSizeChange(val) {
      this.where.limit = val
      this.getList()
    }
  }
}
</script>
<style scoped lang="scss">
.title {
  font-size: 16px;
  font-weight: 500;
}
.flex {
  margin: 10px 0;
  display: flex;
  align-items: center;
}
.h32 {
  height: 32px;
}
</style>
