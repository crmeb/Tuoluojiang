// 一对一引用组件，用于选择一组数据中的一个值，并显示该值对应的名称。 //
该组件依赖于ReferenceSearchTable组件，该组件用于搜索并选择一组数据中的一个值。

<template>
  <div>
    <form-item-wrapper :designer="designer" :field="field" :rules="rules" :design-state="designState"
      :parent-widget="parentWidget" :parent-list="parentList" :index-of-parent-list="indexOfParentList"
      :sub-form-row-index="subFormRowIndex" :sub-form-col-index="subFormColIndex" :sub-form-row-id="subFormRowId">

      <el-select
        v-if="field.options.showType == 0 && (!getReadMode() || field.isShow)"
        ref="fieldEditor"
        v-model="fieldModel.id"
        class="full-width-input"
        :disabled="field.options.disabled"
        :size="toElSize(field.options.size)"
        :clearable="field.options.clearable"
        filterable
        :allow-create="field.options.allowCreate"
        :default-first-option="allowDefaultFirstOption"
        :automatic-dropdown="field.options.automaticDropdown"
        :multiple="field.options.multiple"
        :multiple-limit="field.options.multipleLimit"
        :placeholder="field.options.placeholder || i18nt('render.hint.selectPlaceholder')"
        :remote="field.options.remote"
        :remote-method="remoteMethod"
        @focus="handleFocusCustomEvent"
        @blur="handleBlurCustomEvent"
        @change="handleChangeEvent($event, fieldModel)"
      >
        <el-option
          v-for="(item, index) in list"
          :key="index"
          :label="item[index_field_name]"
          :value="item.id"
        />
      </el-select>

      <component v-else ref="selectMember" :is="name" :only-one="true" :id="field.options.fieldId"
        @getSelectList="getSelectList" @changeMastart="setReferRecord" @getSelection="getSelection">
        <template v-slot:custom>
          <el-input ref="fieldEditor" v-model="displayValue"
            :disabled="disabledList.includes(field.options.name) ? true : field.options.disabled" readonly clearable
            v-show="!getReadMode() || field.isShow" :size="toElSize(field.options.size)" class="hide-spin-button" type="text"
            :placeholder="field.options.placeholder" :prefix-icon="$icon(field.options.prefixIcon)"
            :suffix-icon="$icon(field.options.suffixIcon)" @click="onAppendButtonClick">
            <template #suffix>
              <i title="清除" v-if="!!displayValue && !field.options.disabled" class="el-input__icon el-icon-circle-close"
                @click.stop="handleClearEvent">
              </i>
            </template>
            <template #append>
              <el-button :disabled="field.options.disabled" @click.stop="onAppendButtonClick">
                {{ field.options.buttonText }}
              </el-button>
            </template>
          </el-input>
        </template>
      </component>

      <template v-if="getReadMode() && !field.isShow">
        <div class="readonly-mode-field" @click="isShowFn">{{ contentForReadMode || '--' }}</div>
      </template>
    </form-item-wrapper>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { dataModulerListApi, dataModulerFieldApi } from '@/api/develop.ts'
// import VisualDesign from '@/../lib/visual-design/designer.umd.js'
import ReferenceSearchTable from '@/components/mlReferenceSearch/reference-search-table.vue'
// const { FormItemWrapper, emitter, i18n, fieldMixin } = VisualDesign.VFormSDK
import FormItemWrapper from './form-item-wrapper'
import i18n, { translate } from '@/utils/i18n.ts'
import { putUpdateFieldApi } from '@/api/develop.ts'
import fieldMixin from '@/components/form-designer/form-widget/field-widget/fieldMixin'
export default {
  name: 'input-select-widget',
  mixins: [fieldMixin, i18n],
  props: {
    field: Object,
    parentWidget: Object,
    parentList: Array,
    indexOfParentList: Number,
    designer: Object,

    designState: {
      type: Boolean,
      default: false
    },

    subFormRowIndex: {
      /* 子表单组件行索引，从0开始计数 */ type: Number,
      default: -1
    },
    subFormColIndex: {
      /* 子表单组件列索引，从0开始计数 */ type: Number,
      default: -1
    },
    subFormRowId: {
      /* 子表单组件行Id，唯一id且不可变 */ type: String,
      default: ''
    }
  },
  components: {
    FormItemWrapper,
    ReferenceSearchTable,
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    selectOne: defineAsyncComponent(() => import('@/components/form-common/select-one')),
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department'))
  },
  data() {
    return {
      oldFieldValue: null, //field组件change之前的值
      fieldModel: {
        name: '',
        id: ''
      },
      displayValue: '',
      rules: [],
      list: [],
      index_field_name: '',
      disabledList: ['user_id', 'update_user_id'],
      name: 'selectMember',
      showReferenceDialogFlag: false,
      entity: null,
      curRefField: null,
      searchFilter: ''
    }
  },

  computed: {
    allowDefaultFirstOption() {
      return !!this.field.options.filterable && !!this.field.options.allowCreate
    },
    inputType() {
      if (this.field.options.type === 'number') {
        return 'text' //当input的type设置为number时，如果输入非数字字符，则v-model拿到的值为空字符串，无法实现输入校验！故屏蔽之！！
      }
      return this.field.options.type
    },

    contentForReadMode() {
      const { showType } = this.field.options
      if (showType == 0) {
        const item = this.list.find(i => i.id == this.fieldModel.id)
        const name = item?.[this.index_field_name] || ''
        this.fieldModel.name = name
        return name || '--'
      }
      return this.fieldModel?.name || '--'
    },

    dialogWidth() {
      return this.field.options.searchDialogWidth || '520px'
    }
  },
  watch: {
    fieldModel: {
      deep: true,
      immediate: true,
      handler(val) {
        this.displayValue = !!val ? val.name : ''
      }
    }
  },
  beforeCreate() {
    /* 这里不能访问方法和属性！！ */
  },

  created() {
    this.getData()
    this.getKey()
    const gDsv = this.getGlobalDsv()
    this.entity = gDsv['formEntity'] || this.$route.query.entity || this.$route.meta.entityName
    if (!!this.subFormItemFlag) {
      //设置为明细实体名称！！
      this.entity = this.subFormName
    }

    /* 注意：子组件mounted在父组件created之后、父组件mounted之前触发，故子组件mounted需要用到的prop
       需要在父组件created中初始化！！ */
    this.registerToRefList()
    this.initFieldModel()
    this.initEventHandler()
    this.buildFieldRules()
    this.handleOnCreated()
    if (!this.fieldModel) {
      this.fieldModel = { name: '', id: '' }
    }

  },
  beforeUnmount() { },

  mounted() {
    this.handleOnMounted()



  },

  beforeUnmount() {
    this.unregisterFromRefList()
  },

  methods: {
    handleFocusCustomEvent() { 
this.selectField(this.field)

    },
     selectField(field) {
      if (this.designer && this.designer.setSelected) {
        this.designer.setSelected(field)
        this.designer.emitEvent('field-selected', this.parentWidget) //发送选中组件的父组件对象
      }
    },
    getKey(id) {
      dataModulerFieldApi(this.field.options.fieldId).then((res) => {
        this.index_field_name = res.data.index_field_name
      })
    },
    remoteMethod() {
      if (!!this.field.options.remote && !!this.field.options.onRemoteQuery) {
        return this.remoteQuery
      } else {
        return undefined
      }
    },
    getData(page) {
      if (this.field.options.showType == 1) {

        //  dataModulerListApi(this.field.options.fieldId, { page: page || 1, limit: 10 }).then((res) => {
        //   this.list = res.data.list
        // })
      } else {
        dataModulerListApi(this.field.options.fieldId, { page: 0, limit: 0 }).then((res) => {
          this.list = res.data.list
        })
      }

    },
    // handlePopoverHide() {
    //   if (this.getReadMode()) {
    //     this.isShow = false
    //     this.putUpdateField()
    //   }
    // },

    onAppendButtonClick() {
      if (['user_id', 'update_user_id', 'owner_user_id'].includes(this.field.options.formFieldUniqid)) {
        this.name = 'selectMember'
        this.$refs.selectMember.handlePopoverShow()
      } else if (['frame_id'].includes(this.field.options.formFieldUniqid)) {
        this.name = 'selectDepartment'
        setTimeout(() => {
          this.$refs.selectMember.handlePopoverShow()
        }, 300)
      } else {
        this.name = 'selectOne'
        setTimeout(() => {
          this.$refs.selectMember.handlePopoverShow()
        }, 300)
      }
    },

    handleClearEvent() {
      this.fieldModel = {}
      this.handleChangeEvent(this.fieldModel)
    },

    setReferRecord(recordObj, selectedRow) {
      this.fieldModel = {
        id: recordObj[0].id,
        name: recordObj[0].label
      }
      this.handleChangeEvent(this.fieldModel)
      this.handleRecordSelectedEvent(selectedRow)
    },

    getSelectList(recordObj) {
      this.fieldModel = {
        id: recordObj[0].value,
        name: recordObj[0].name
      }
      // this.handlePopoverHide()
      this.handleChangeEvent(this.fieldModel)
    },
    getSelection(data) {
      this.fieldModel = {
        id: data.id,
        name: data.name
      }
      // this.handlePopoverHide()
      this.handleChangeEvent(this.fieldModel)
    },

    setFilter(newFilter) {
      this.searchFilter = newFilter
    },

    handleRecordSelectedEvent(selectedRow) {
      if (!!this.designState) {
        //设计状态不触发事件
        return
      }

      if (!!this.field.options.onRecordSelected) {
        let customFn = new Function('selectedRow', this.field.options.onRecordSelected)
        customFn.call(this, selectedRow)
      }
    }
  }
}
</script>

<style lang="scss">
@import '../../../../styles/global.scss';

.small-padding-dialog .el-dialog__body {
  padding: 0 10px 10px 10px !important;
}

.full-width-input {
  width: 100% !important;
}
</style>
