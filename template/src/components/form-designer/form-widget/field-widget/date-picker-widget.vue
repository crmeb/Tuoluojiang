<template>
  <form-item-wrapper
    :designer="designer"
    :field="field"
    :rules="rules"
    :design-state="designState"
    :parent-widget="parentWidget"
    :parent-list="parentList"
    :index-of-parent-list="indexOfParentList"
    :sub-form-row-index="subFormRowIndex"
    :sub-form-col-index="subFormColIndex"
    :sub-form-row-id="subFormRowId"
    ref="showPanel"
  >
    <el-date-picker
      v-if="isInputVisable"
      ref="fieldEditor"
      type="date"
      v-model="fieldModel"
      class="full-width-input"
      :readonly="field.options.readonly"
      :disabled="field.options.disabled"
      :size="toElSize(field.options.size)"
      :clearable="field.options.clearable"
      :editable="field.options.editable"
      :format="toDayjsFormat(field.options.format)"
      :value-format="toDayjsFormat(field.options.valueFormat)"
      :placeholder="field.options.placeholder || i18nt('render.hint.datePlaceholder')"
      @focus="handleFocusCustomEvent"
      @blur="handleBlurCustomEvent"
      @change="handleChangeEvent"
    >
    </el-date-picker>
    <template v-else>
      <div class="readonly-mode-field" @click="isShowFn">{{ contentForReadMode || '--' }}</div>
    </template>
  </form-item-wrapper>
</template>

<script>
import { toDayjsFormat } from '@/utils/date-format.ts'
import dayjs from 'dayjs'
import FormItemWrapper from './form-item-wrapper'
import i18n, { translate } from '@/utils/i18n.ts'
import fieldMixin from '@/components/form-designer/form-widget/field-widget/fieldMixin'

export default {
  name: 'date-picker-widget',
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
    FormItemWrapper
  },
  inject: ['refList', 'formConfig', 'globalOptionData', 'globalModel'],
  data() {
    return {
      oldFieldValue: null, //field组件change之前的值
      fieldModel: null,
      rules: []
    }
  },
  watch: {
    isInputVisable(val) {
      val && this.$nextTick(() => {
        this.isInputVisable && this.$refs.fieldEditor.focus();
      });
    }
  },
  computed: {
    isInputVisable() {
      return !this.getReadMode() || this.field.isShow;
    },
    contentForReadMode() {
      return this.fieldModel ? this.fieldModel : '--'
    }
  },
  beforeCreate() {
    /* 这里不能访问方法和属性！！ */
  },

  created() {
    /* 注意：子组件mounted在父组件created之后、父组件mounted之前触发，故子组件mounted需要用到的prop
         需要在父组件created中初始化！！ */
    this.initFieldModel()
    this.registerToRefList()
    this.initEventHandler()
    this.buildFieldRules()
    this.handleOnCreated()
  },

  mounted() {
    this.handleOnMounted()
    if (this.field.options.defaultType == '1') {
      this.fieldModel = dayjs().format('YYYY-MM-DD')
      this.handleChangeEvent(this.fieldModel)
    }
  },

  beforeUnmount() {
    this.unregisterFromRefList()
  },

  methods: {
    toDayjsFormat
  }
}
</script>

<style lang="scss" scoped>
@import '../../../../styles/global.scss'; //* form-item-wrapper已引入，还需要重复引入吗？ *//

:deep(.full-width-input) {
  width: 100% !important;
}
</style>
