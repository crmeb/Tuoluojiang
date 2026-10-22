<!-- @FileDescription: 动态表单页面 -->
<template>
  <div>
    <el-form ref="form" :model="form" :rules="formRules" label-width="auto" @submit.native.prevent>
      <template v-for="(item, key, index) in formConfig">
        <el-form-item :key="key" :label="item.label" :prop="item.key"
          v-if="item.isShow === 'data_type' ? form[item.isShow] != 1 : form[item.isShow] != 0">
        <!-- 单选 -->
        <template v-if="item.type == 'radio'">
          <el-radio-group v-model="form[item.key]" class="vertical">
            <el-radio v-for="(itemOption, index) in item.options" :key="index" :label="itemOption.label">{{
              itemOption.value
            }}</el-radio>
          </el-radio-group>
        </template>

        <!-- 选择日期 -->
        <el-date-picker v-if="item.type == 'date'" size="small" v-model="form[item.key]" :placeholder="item.placeholder"
          type="date" :format="item.format" :value-format="item.format" style="width: 100%">
        </el-date-picker>
        <!-- 输入框 -->
        <el-input v-if="item.type == 'input'" v-model="form[item.key]" :placeholder="item.placeholder"
          :maxlength="item.maxlength" size="small" style="width: 100%" :disabled="item.disabled"
          :show-word-limit="item.showWordLimit"></el-input>
        <!-- 刷新生成英文输入框 -->
        <el-input v-if="item.type == 'inputEn'" :disabled="fromData.type == 'edit' ? true : false"
          v-model="form[item.key]" :placeholder="item.placeholder" size="small" class="refresh-input"
          @focus="refreshFn(item.refresh, item.key)">
          <template #suffix>
            <el-button type="primary" class="refresh" :disabled="fromData.type == 'edit' ? true : false"
              size="small" @click.stop="refreshFn(item.refresh, item.key)">
              刷新生成</el-button>
          </template>
        </el-input>
        <!-- 文本域 -->
        <el-input class="textarea" v-if="item.type == 'textarea'" type="textarea" placeholder="请输入内容"
          :maxlength="item.maxlength" v-model="form[item.key]" style=" height:70px "
          :placeholder="item.placeholder" :show-word-limit="item.showWordLimit" size="small">
        </el-input>

        <!-- 富文本 -->
        <ueditor-from v-if="item.type === 'richText'" ref="ueditorFrom" :border="true" :content="form[item.key]"
          :height="`400px`" @input="ueditorEdit($event, item)">
        </ueditor-from>
        <!-- 密码输入框 -->
        <el-input type="password" v-if="item.type == 'password'" prefix-icon="el-icon-lock" v-model="form[item.key]"
          :placeholder="item.placeholder" show-password style="width: 100%" size="small"></el-input>
        <!-- 计数器输入框 -->
        <div v-if="item.type == 'number'">
          <el-input-number style="width: 100%" size="small" v-model="form[item.key]" :min="item.min"
            :max="item.max"></el-input-number>
        </div>
        <!-- 数字输入框 -->
        <el-input v-if="item.type == 'inputNumber'" type="number" size="small" v-model="form[item.key]"
          :placeholder="item.placeholder"></el-input>
        <!-- 开关组件 -->
        <el-switch v-if="item.type == 'switch'" v-model="form[item.key]" size="small" :active-value="item.activeValue"
          :inactive-value="item.inactiveValue" :active-text="item.activeText" :inactive-text="item.inactiveText">
        </el-switch>
        <!-- 选择时间（月） -->
        <el-date-picker v-if="item.type == 'month'" style="width: 100%" v-model="form[item.key]" type="month"
          size="small" :picker-options="pickerOptions" placeholder="选择月">
        </el-date-picker>
        <!-- 选择（单选） -->
        <div v-if="item.type == 'select'">
          <div class="flex">
            <el-select style="width: 100%" v-model="form[item.key]" :disabled="item.disabled" filterable size="small"
              @change="selectChange" :placeholder="item.placeholder">
              <el-option v-for="(v, index) in item.options" :key="v.id" :label="v.label || v.name || v.table_name"
                :value="v.id || v.value">
              </el-option>
            </el-select>
            <div v-if="item.sign == 'dict'" class="fang">
              <el-button size="small" @click="goDict(item)">添加</el-button>
            </div>
          </div>
        </div>

        <!-- 选择人员 -->
        <template v-if="item.type == 'user_id'">
          <select-member :onlyOne="item.only_one" :value="userList" :disabled="item.disabled"
            :disabledList="item.disabledList" @getSelectList="getSelectList($event, item)" style="width: 100%">
          </select-member>
        </template>

        <!-- 一对一引用 -->
        <div v-if="item.type == 'input_select'" class="el-input__inner select plan-footer-on flex-between h32"
          @click="checkboxDialogOpen()">
          <div class="over-text1" @click="checkboxDialogOpen()">
            <span @click="checkboxDialogOpen()" v-for="(items, indexs) in fieldList" :key="indexs" @click.stop="">
              {{ items.field_name }},
            </span>
          </div>
          <i class="el-tag__close el-icon-arrow-down" />
        </div>

        <!-- 选择（多选） -->
        <el-select style="width: 100%" v-if="item.type == 'multipleSelect'" v-model="form[item.key]"
          :disabled="fromData.type == 'edit'" multiple size="small" filterable
          :placeholder="item.placeholder ? item.placeholder : '请选择'">
          <el-option v-for="(v, index) in item.options" :key="v.id" :label="v.name" :value="v.id"> </el-option>
        </el-select>

        <!--低代码选择应用-实体 -->
        <div v-if="item.type == 'cascaderSelect'">
          <el-cascader v-model="form[item.key]" :options="item.options" :show-all-levels="false" filterable size="small"
            style="width: 100%" clearable :placeholder="item.placeholder ? item.placeholder : '请选择'">
          </el-cascader>
        </div>

        <!-- 级联选择器(自定义) -->
        <div v-if="item.type == 'cascader'">
          <el-cascader v-model="form[item.key]" :options="item.options" filterable size="small" style="width: 100%"
            :show-all-levels="false" :props="item.props" clearable
            :placeholder="item.placeholder ? item.placeholder : '请选择'">
            <template #default="{ node, data }">
              <template v-if="data.table_name">
                <span>{{ data.table_name }}</span>
                <span> （{{ data.table_name_en }}）</span>
              </template>
            </template>
          </el-cascader>
        </div>
        <template v-if="item.type == 'cascaderNew'">
          <el-cascader v-model="form[item.key]" :options="item.options" :props="{ checkStrictly: true }" clearable
            filterable style="width: 100%"></el-cascader>
        </template>
        <template v-if="item.type == 'icon'">
          <el-input placeholder="请选择图标" v-model="form[item.key]" readonly @click.native="showIconDialog = true"
            clearable>
            <template #suffix>
              <i v-if="!form[item.key]" class="el-icon-circle-plus-outline" style="cursor: pointer;"></i>
              <i v-else class="el-icon-circle-close" style="cursor: pointer;"
                @click.stop="handleClearIcon"></i>
            </template>
          </el-input>

        </template>
        <template v-if="item.type == 'uni_img'">
          <oa-systemImage @getImage="getImage($event, item.key)"></oa-systemImage>
        </template>
        <div class="tips" v-if="item.tips ">{{ item.tips }}</div>

        </el-form-item>
      </template>


    </el-form>
    <!-- 选择图标 -->
    <el-dialog title="选择菜单图标" :visible.sync="showIconDialog" :append-to-body="true" width="50%">
      <div class="icon-box">
        <select-icon ref="selectIconRef" :isEmit="true" @select="handleSelectIcon"></select-icon>
      </div>
    </el-dialog>
    <!-- 引用实体弹窗 -->
    <checkboxDialog ref="checkboxDialog" @getData="getDataFn" :type="`field`" :showCrud="true"></checkboxDialog>
  </div>
</template>
<script>
import modalForm from '@/libs/modal-form'
import { getDictCreateApi, getDictListApi } from '@/api/form'
import { pinyin } from 'pinyin-pro'
import checkboxDialog from '@/components/develop/checkboxDialog'
import selectMember from '@/components/form-common/select-member'
import ueditorFrom from '@/components/form-common/oa-wangeditor'
import selectIcon from '@/components/form-common/select-icon'
import oaSystemImage from '@/components/form-common/oa-systemImage'

export default {
  name: '',
  components: { checkboxDialog, selectMember, ueditorFrom, selectIcon,oaSystemImage },
  props: {
    // 弹窗样式
    fromData: {
      type: Object,
      default: () => { }
    },
    // 表单内容
    formConfig: {
      type: Array,
      default: () => []
    },
    // 表单绑定值
    formDataInit: {
      type: Object,
      default: () => { }
    },
    // 表单规则
    formRules: {
      type: Object,
      default: () => { }
    }
  },
  data() {
    return {
      form: this.formDataInit,
      showIconDialog: false,
      strData: '',
      onlyOne: false,
      fieldList: [],
      dictList: [],
      itemIndex: 0,
      itemData: {},
      userList: [], // 人员数据
      pickerOptions: {
        disabledDate(time) {
          return time.getTime() < Date.now()
        }
      }
    }
  },
  watch: {
    formDataInit(val) {
      if (val.association_field_names_list && val.association_field_names_list.length > 0) {
        this.fieldList = val.association_field_names_list
      }
      this.form = val
    }
  },
  mounted() {
    if (this.formDataInit.association_field_names_list && this.formDataInit.association_field_names_list.length > 0) {
      this.fieldList = this.formDataInit.association_field_names_list
    }
  },

  methods: {
    getDataFn(data) {
      this.formDataInit.association_crud_id = data.id
      this.form.association_field_names = []
      this.fieldList = data.selectList
      data.selectList.map((item) => {
        this.form.association_field_names.push(item.field_name_en)
      })
    },
    // 获取系统图片
    getImage(data, key) {
      this.form[key] = data.url
    },
    handleSelectIcon(data) {
      this.form.icon = data
      this.showIconDialog = false
    },
    handleClearIcon() {
      this.$set(this.form, 'icon', '')
    },
    submit() {
      if (this.fromData.type == 'slot') {
        this.$emit('submit')
      } else {
        this.$refs.form.validate((valid) => {
          if (valid) {
            this.$emit('submit', this.form, this.fromData.type)
          }
        })
      }
    },

    // 获取字典列表
    async getDictList(item) {
      let data = {
        page: 1,
        limit: '',
        form_value: item.form_value
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

    // 刷新转拼音小写
    refreshFn(refresh, key) {
      if (this.form[refresh] == '') {
        return false
      }
      var regex = /^[\u4e00-\u9fa5a-zA-Z][\u4e00-\u9fa5a-zA-Z_]{0,15}$/
      if (!regex.test(this.form[refresh])) {
        return false
      }
      this.strData = pinyin(this.form[refresh], { toneType: 'none' })
      var reg = /[\t\r\f\n\s]*/g
      if (typeof this.strData === 'string') {
        this.strData = this.strData.replace(reg, '')
      }
       this.strData = this.strData.replace(/ü/, 'v')
      this.form[key] = this.strData
    }, // 首字母转成大写
    titleCase(str) {
      const newStr = str.slice(0, 1).toUpperCase() + str.slice(1).toLowerCase()
      return newStr
    },

    selectChange(e) {
      this.$emit('selectChange', e)
    },
    ueditorEdit(val, item) {
      this.form[item.key] = val
    },

    // 选择人员回调
    getSelectList(data, item) {
      this.userList = data
      let ids = []
      data.map((item) => {
        ids.push(item.value)
      })
      this.form[item.key] = ids
      this.$nextTick(() => {
        this.$refs.form && this.$refs.form.validateField(item.key)
      })
    },
    goDict(item) {
      modalForm(getDictCreateApi()).then(({ message }) => {
        this.getDictList(item)
        setTimeout(() => {
          item.options = this.dictList
        }, 500)
      })
      // window.open(`${roterPre}/develop/dictionary`, '_blank')
    },
    closeFn() {
      this.userList = []
      this.fieldList = []
      this.$refs.form.resetFields()
    },
    checkboxDialogOpen() {
      if (this.form.association_field_names && this.form.association_field_names.length > 0) {
        let ids = []
        this.fieldList.map((item) => {
          ids.push(item.id)
        })
        let data = {
          type: this.fromData.type,
          id: Number(this.form.association_crud_id),
          ids,
          selectList: this.fieldList
        }
        this.$refs.checkboxDialog.openBox(data)
      } else {
        this.$refs.checkboxDialog.openBox()
      }
    }
  }
}
</script>
<style scoped lang="scss">
.refresh-input {
  ::v-deep .el-input__suffix {
    position: absolute;
    right: 0;
  }
}

.fang {
  margin-left: 12px;
  z-index: 155;
}

.iconxitong-xitongshezhi-cebian {
  font-size: 18px;
  color: #c0c4cc;
}

.tips {
  // height: 12px;
  line-height: 12px;
  font-size: 12px;
  margin-top: 10px;
  color: #909399;
}

::v-deep .textarea .el-textarea__inner {
  // font-size: 12px;
  height: 70px;
}

::v-deep .invite .el-button--small {
  border-radius: 0 3px 3px 0;
}

::v-deep .el-textarea__inner {
  resize: none;
}

::v-deep .el-form-item {
  margin-bottom: 20px;
}
</style>
