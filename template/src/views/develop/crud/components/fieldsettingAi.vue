<template>
  <div>
    <el-dialog
      title="AI生成字段"
      v-model="dialogVisible"
      :close-on-click-modal="false"
      width="1000px"
      @close="handleClose"
    >
      <!-- 左侧区域 -->
      <div class="dialog-container">
        <div class="left-panel">
          <Mobile ref="mobile" @handleSend="handleSend"></Mobile>
        </div>

        <!-- 右侧区域 -->
        <div class="right-panel">
          <el-table :data="fields" style="width: 100%" height="400px">
            <el-table-column prop="field_name" label="字段名称">
              <template #default="scope">
                <el-input
                  v-model="scope.row.field_name"
                  @change="handleFieldNameChange(scope.row)"
                  placeholder="字段名称"
                />
              </template>
            </el-table-column>
            <el-table-column prop="form_value" label="字段类型">
              <template #default="scope">
                <div class="flex">
                  <el-select
                    v-model="scope.row.value"
                    placeholder="请选择"
                    @change="handleTypeChange(scope.row)"
                  >
                    <el-option v-for="item in typeOptions" :key="item.value" :label="item.label" :value="item.value" />
                  </el-select>
                  <el-button
                    v-if="!dictTypes.includes(scope.row.value) && scope.row.value !== 'input_select'"
                    type="primary" link
                    icon="el-icon-delete"
                    class="ml10"
                    @click="handleDelete(scope.$index)"
                  />
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="data_dict_id">
              <template #default="scope">
                <div class="flex">
                  <el-select
                    v-if="dictTypes.includes(scope.row.value)"
                    v-model="scope.row.data_dict_id"
                    placeholder="关联字典"
                    @change="selectChange(scope.row)"
                  >
                    <el-option v-for="item in dictList" :key="item.id" :label="item.name" :value="item.id" />
                  </el-select>
                  <!-- 一对一 -->
                  <div
                    v-if="scope.row.value == 'input_select'"
                    class="el-input__inner select plan-footer-on flex-between h32"
                    @click="checkboxDialogOpen(scope.row, scope.$index)"
                  >
                    <div class="over-text1" @click="checkboxDialogOpen(scope.row, scope.$index)">
                      <!-- {{ scope.row.association_field_names_list }} -->
                      <span
                        v-for="(items, indexs) in scope.row.association_field_names_list"
                        :key="indexs"
                        @click.stop=""
                      >
                        {{ items.field_name }},
                      </span>
                    </div>
                    <i class="el-tag__close el-icon-arrow-down" />
                  </div>
                  <el-button
                    v-if="dictTypes.includes(scope.row.value) || scope.row.value == 'input_select'"
                    type="primary" link
                    icon="el-icon-delete"
                    class="ml10"
                    @click="handleDelete(scope.$index)"
                  />
                </div>
              </template>
            </el-table-column>
          </el-table>

          <el-button class="add-text" type="primary" link icon="el-icon-plus" @click="handleAddField"> 添加字段 </el-button>

          <div class="footer-buttons">
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button :loading="loading" type="primary" @click="handleConfirm">确定</el-button>
          </div>
        </div>
      </div>
    </el-dialog>
    <!-- 引用实体弹窗 -->
    <checkboxDialog ref="checkboxDialog" @getData="getDataFn" :type="`field`" :showCrud="true"></checkboxDialog>
  </div>
</template>

<script>
import { pinyin } from 'pinyin-pro'
import Mobile from './mobile.vue'
import { getDictListApi } from '@/api/form.ts'
import { dataFieldTypeApi, chatHistoryApi, batchFieldSaveApi } from '@/api/develop.ts'
import checkboxDialog from '@/components/develop/checkboxDialog'

export default {
  emits: ['getList'],
  name: 'FormDialog',
  components: {
    Mobile,
    checkboxDialog
  },
  props: {
    typesList: {
      // 字段类型
      type: Array,
      default: () => []
    },
    info: {
      // 表单信息
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      loading: false,
      dialogVisible: false,
      isProcessed: false,
      index: 0,
      height: 'calc(100vh - 120px)',
      typeOptions: [],
      dictList: [],
      dictObj: {},
      fields: [{ name: '', form_value: 'input', relation: '' }],
      dictTypes: ['radio', 'cascader_radio', 'checkbox', 'cascader', 'tag']
    }
  },
  methods: {
    handleClose() {
      this.$refs.mobile.list = []
      this.typeOptions = []
      this.dialogVisible = false
      this.resetForm()
    },

    // 获取全部字段类型
    async getTypeList() {
      // this.typeOptions = []
      const data = await dataFieldTypeApi()
      data.data.map((item) => {
        this.typeOptions = [...this.typeOptions, ...item.options]
      })
    },

    handleSend(message) {
      let data = {
        crud_id: this.info.id,
        fields: this.fields,
        message
      }
      chatHistoryApi(data)
        .then((res) => {
          if (res.status === 200) {
            // 解构获取数据并赋予更有意义的变量名
            const { data: fields } = res

            // 处理每个字段的函数
            const processField = (item) => ({
              ...item,
              value: 'input',
              is_default_value_not_null: 1,
              is_table_show_row: 1,
              create_modify: 1,
              update_modify: 1,
              data_dict_id: '',
              association_crud_id: '',
              association_field_names: [],
              association_field_names_list: [],
              is_uniqid: 0,
              options: []
            })

            // 使用map处理字段并赋值
            this.fields = fields.map(processField)
          }
          this.$refs.mobile.sendSuccess(res)
        })
        .catch((err) => {
          this.$refs.mobile.sendSuccess(err)
        })
    },

    openBox(data) {
      if (this.typesList && this.typesList.length == 0) {
        this.getTypeList()
      } else {
        this.typesList.map((item) => {
          this.typeOptions = [...this.typeOptions, ...item.options]
        })
      }
      this.fields = data || []
      this.dialogVisible = true
    },

    // 获取字典列表
    async getDictList(form_value) {
      let data = {
        page: 1,
        limit: '',
        form_value
      }
      if (this.dictObj && this.dictObj[form_value] && this.dictObj[form_value].length > 0) {
        this.dictList = this.dictObj[form_value]
        return
      }
      const result = await getDictListApi(data)
      if (result.data.list.length > 0) {
        this.dictList = result.data.list.filter((item) => {
          return item.status == 1
        })
        this.dictObj[form_value] = this.dictList
      } else {
        this.dictList = []
      }
    },

    handleTypeChange(val) {
      val.data_dict_id = ''
      if (this.dictTypes.includes(val.value)) {
        this.getDictList(val.value)
      }
    },

    handleFieldNameChange(row) {
      row.field_name_en = this.refreshFn(row.field_name)
    },

    // 刷新转拼音小写
    refreshFn(key) {
      const regex = /^[\u4e00-\u9fa5a-zA-Z][\u4e00-\u9fa5a-zA-Z_]{0,15}$/
      if (!regex.test(key)) {
        return false
      }
      let str = pinyin(key, { toneType: 'none' })
      const reg = /[\t\r\f\n\s]*/g
      if (typeof str === 'string') {
        str = str.replace(reg, '')
      }
      return str
    },

    // 一对一字段处理
    getDataFn(val) {
      this.$nextTick(() => {
        this.fields[this.index].association_crud_id = val.id
        this.fields[this.index].association_field_names = []
        this.fields[this.index].association_field_names_list = []
        val.selectList.map((item) => {
          this.fields[this.index].association_field_names.push(item.field_name_en)
          this.fields[this.index].association_field_names_list.push(item)
        })
      })
    },

    checkboxDialogOpen(row, index) {
      this.index = index
      if (row.association_field_names && row.association_field_names.length > 0) {
        let ids = []
        row.association_field_names_list.map((item) => {
          ids.push(item.id)
        })
        let data = {
          type: 'edit',
          id: Number(row.association_crud_id),
          ids,
          selectList: row.association_field_names_list
        }
        this.$refs.checkboxDialog.openBox(data)
      } else {
        this.$refs.checkboxDialog.openBox()
      }
    },

    handleAddField() {
      let item = {
        field_name: '',
        field_name_en: '',
        value: '',
        comment: '',
        is_default_value_not_null: 1,
        is_table_show_row: 1,
        create_modify: 1,
        update_modify: 1,
        data_dict_id: '',
        association_crud_id: '',
        association_field_names: [],
        is_uniqid: 0,
        options: []
      }
      this.fields.push(item)
    },

    handleDelete(index) {
      this.fields.splice(index, 1)
    },

    handleConfirm() {
      for (const item of this.fields) {
        if (/^[\u4e00-\u9fa5a-zA-Z][\u4e00-\u9fa5a-zA-Z_]{0,15}$/.test(item.field_name) == false) {
          this.$message.error('字段名称必须以中文，英文字母开头，中间可输入下划线，最多可输入16个字')
          return false
        }

        if (!item.value) {
          this.$message.error('请选择字段类型')
          return
        }
      }

      this.loading = true
      let data = {
        crud_id: this.info.id,
        icon:'',
        path:[],
        table_name: this.info.table_name,
        table_name_en: this.info.table_name_en,
        cate_ids: this.info.cate_ids,
        fields: this.fields
      }
      batchFieldSaveApi(data)
        .then((res) => {
          if (res.status == 200) {
            this.resetForm()
            this.$emit('getList')
          }
          this.loading = false
        })
        .catch((err) => {
          this.loading = false
        })
    },

    resetForm() {
      this.fields = [
        {
          field_name: '',
          field_name_en: '',
          value: '',
          comment: '',
          is_default_value_not_null: 1,
          is_table_show_row: 1,
          create_modify: 1,
          update_modify: 1,
          data_dict_id: '',
          association_crud_id: '',
          association_field_names: [],
          is_uniqid: 0,
          options: []
        }
      ]
      this.isProcessed = false
      this.dialogVisible = false
    }
  }
}
</script>

<style scoped>
.dialog-container {
  display: flex;
  width: 100%;
  height: 60vh; /* 可按需调整高度 */
}

.left-panel {
  width: 30%;
  height: 100%;
  box-sizing: border-box;
}

.right-panel {
  width: 70%;
  height: 100%;
  padding-left: 20px;
  box-sizing: border-box;
  background: #fff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.footer-buttons {
  margin-top: 20px;
  text-align: right;
  margin-bottom: 10px;
}
.add-text {
  margin-top: 12px;
  display: flex;
}
</style>
