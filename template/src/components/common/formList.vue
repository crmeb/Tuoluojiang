<template>
  <div class="flex">
    <template v-if="type">
      <el-input
        v-model="ruleForm.keyword_default"
        :placeholder="`请输入关键字`"
        class="search-form search-width"
        clearable
        prefix-icon="el-icon-search"
        @change="handleEmit()"
      >
      </el-input>
      <manage-range
        v-if="type == 0"
        ref="manageRange"
        :all="`all`"
        :scopeFrames="scopeFrames"
        class="search-form"
        @change="changeFrame"
      ></manage-range>
    </template>
    <div v-for="(val, index) in list" :key="index" class="form-box">
      <!-- 表单 -->
      <div>
        <manage-range
          v-if="['manage'].includes(val.form_value)"
          ref="manageRange"
          :all="`all`"
          class="search-form"
          @change="changeFrame"
        ></manage-range>

        <!-- 输入框类型 -->
        <el-input
          v-if="isInputType(val.form_value)"
          v-model="ruleForm[val.field_name_en]"
          :placeholder="`请输入${val.field_name}`"
          class="mr10"
          clearable
          prefix-icon="el-icon-search"
          :style="{ width: val.width || elementWidth + 'px' }"
          @change="handleEmit(val)"
        >
        </el-input>

        <!-- 下拉选择类型 -->
        <el-select
          v-else-if="isSelectType(val.form_value)"
          v-model="ruleForm[val.field_name_en]"
          clearable
          :placeholder="`${val.field_name}`"
          class="mr10"
          collapse-tags
          filterable
          :remote="isLargeOptionList(val)"
          :remote-method="(query) => handleSelectFilter(query, val)"
          :style="{ width: elementWidth + 'px' }"
          v-bind="val.props || {}"
          @change="handleEmit(val)"
          @visible-change="(visible) => handleSelectVisibleChange(visible, val)"
        >
          <el-option
            v-for="(items, optionIndex) in getDisplayOptions(val)"
            :key="index + '_' + optionIndex + '_' + (items.value ?? items.id ?? 'empty')"
            :label="items.name || items.label"
            :value="items.value ?? items.id"
          ></el-option>
        </el-select>

        <!-- 开关类型 -->
        <el-select
          v-else-if="val.form_value === 'switch'"
          v-model="ruleForm[val.field_name_en]"
          :placeholder="`${val.field_name}`"
          class="mr10"
          clearable
          filterable
          :style="{ width: elementWidth + 'px' }"
          @change="handleEmit(val)"
        >
          <el-option label="是" value="1"></el-option>
          <el-option label="否" value="0"></el-option>
        </el-select>

        <!-- 数字类型 -->
        <el-input-number
          v-else-if="val.form_value == 'number'"
          v-model="ruleForm[val.field_name_en]"
          :controls="false"
          :min="0"
          :placeholder="`${val.field_name}`"
          class="mr10"
          :style="{ width: elementWidth + 'px' }"
          @change="handleEmit(val)"
        ></el-input-number>

        <!-- 级联选择器（单选，可通过 val.props 传入 checkStrictly / emitPath / label / value 等配置） -->
        <div v-else-if="val.form_value == 'cascaderSelect'" class="mr10">
          <el-cascader
            v-model="ruleForm[val.field_name_en]"
            :options="getCascaderOptions(val, index)"
            :props="val.props || {}"
            :show-all-levels="false"
            clearable
            filterable
            :placeholder="val.placeholder || '请选择实体'"
            :style="{ width: elementWidth + 'px' }"
            @change="handleEmit(val)"
          >
          </el-cascader>
        </div>

        <!-- 地区选择 -->
        <el-cascader
          v-else-if="['cascader_address'].includes(val.form_value)"
          v-model="ruleForm[val.field_name_en]"
          :options="addressList"
          :placeholder="`${val.field_name}`"
          :props="cascaderAddressProps"
          class="mr10 address-cascader"
          clearable
          collapse-tags
          filterable
          :style="{ width: elementWidth + 'px' }"
          v-bind="val.props || {}"
          @change="handleEmit(val)"
        ></el-cascader>

        <!-- 普通级联选择器 -->
        <cascader-confirm
          v-else-if="isCascaderType(val.form_value)"
          v-model="ruleForm[val.field_name_en]"
          :options="getCascaderOptions(val, index)"
          :placeholder="`${val.field_name}`"
          :cascader-props="getCascaderProps(val)"
          :cascader-style="{ width: elementWidth + 'px' }"
          class="mr10"
          @change="handleEmit(val)"
        />

        <!-- 日期选择 -->
        <el-date-picker
          v-else-if="isDateType(val.form_value)"
          v-model="val.data_dict"
          :clearable="val.data_dict && !val.data_dict.length > 0"
          :end-placeholder="`${val.field_name_end ? val.field_name_end : val.field_name}`"
          v-bind="$pickerProps(val.pickerOptions || pickerOptions)"
          range-separator="至"
          :start-placeholder="`${val.field_name}`"
          class="time mr10"
          format="YYYY/MM/DD"
          style="width: 235px"
          type="daterange"
          clearable
          value-format="YYYY/MM/DD"
          @change="onchangeTime($event, val)"
        />

        <!-- 时间类型 + 日期区间组合：前面选时间类型（type_field），后面选时间（field_name_en） -->
        <div v-else-if="val.form_value === 'date_type_picker'" class="date-type-picker mr10">
          <el-select
            v-model="ruleForm[val.type_field]"
            class="date-type-select"
            @change="handleEmit(val)"
          >
            <el-option v-for="item in val.type_options" :key="item.value" :label="item.name" :value="item.value" />
          </el-select>
          <el-date-picker
            v-model="val.data_dict"
            :end-placeholder="`${val.field_name_end ? val.field_name_end : val.field_name}`"
            v-bind="$pickerProps(val.pickerOptions || pickerOptions)"
            range-separator="至"
            :start-placeholder="`${val.field_name}`"
            class="time"
            clearable
            format="YYYY/MM/DD"
            style="width: 235px"
            type="daterange"
            value-format="YYYY/MM/DD"
            @change="onchangeTime($event, val)"
          />
        </div>

        <!-- 月份选择 -->
        <el-date-picker
          v-else-if="val.form_value === 'month'"
          v-model="val.data_dict"
          :placeholder="val.field_name"
          class="time mr10"
          format="YYYY-MM"
          type="month"
          value-format="YYYY-MM"
          @change="onchangeTime($event, val)"
        >
        </el-date-picker>

        <!-- 月份选择区间 -->
        <el-date-picker
          v-else-if="val.form_value === 'monthrange'"
          v-model="val.data_dict"
          :end-placeholder="`${val.field_name}`"
          :start-placeholder="`${val.field_name}`"
          class="time mr10"
          format="YYYY/MM"
          range-separator="至"
          type="monthrange"
          value-format="YYYY/MM"
          @change="onchangeTime($event, val)"
        >
        </el-date-picker>

        <!-- 一对一 -->
        <div v-else-if="isInputSelectType(val)" class="mr10" :style="{ width: elementWidth + 'px' }">
          <select-one
            :id="val.id"
            :showType="val.association_show_type"
            :placeholder="val.field_name"
            :value="val.data_dict || {}"
            class="mr10"
            @getSelection="getSelection($event, val)"
          ></select-one>
        </div>

        <!-- 选择人员 -->
        <div v-else-if="member.includes(val.field_name_en)" class="mr10" :style="{ width: elementWidth + 'px' }">
          <select-member
            ref="selectMember"
            :only-one="val.onlyOne"
            :placeholder="val.field_name"
            :isSearch="true"
            :selectIdData="val.value || val.option"
            :enableIds="val.enableIds === undefined ? null : val.enableIds"
            class="mr10"
            style="width: 100%"
            @getSelectList="getSelectList($event, val)"
          ></select-member>
        </div>

        <!-- 选择部门 -->
        <div v-else-if="val.field_name_en === 'frame_id'" class="mr10" :style="{ width: elementWidth + 'px' }">
          <select-department
            :isSearch="true"
            :only-one="true"
            :placeholder="val.field_name"
            :value="val.data_dict || []"
            @changeMastart="changeMastart($event, val)"
          ></select-department>
        </div>

        <!-- 选择标签 -->
        <div v-else-if="val.form_value === 'tag'" :style="{ width: elementWidth + 'px' }">
          <select-label
            ref="selectLabel"
            :isSearch="true"
            :value="ruleForm[val.field_name_en] || []"
            :labelList="labelList"
            :list="getTagOptions(val, index)"
            :placeholder="val.field_name"
            :props="{ children: 'children', label: 'name' }"
            class="mr10"
            @handleLabelConf="handleLabelConf($event, val)"
          ></select-label>
        </div>

        <!-- 选择仓库（默认单选，配置 multiple 时多选，多选值为仓库 ID 数组）；
             配置 with_type 时左侧内嵌调出/调入类型切换（组合控件加宽为 300px），
             类型值写入 ruleForm[type_field || 'warehouse_type'] 随查询透传 -->
        <div class="mr10" v-else-if="val.form_value === 'warehouse_select'"
          :style="{ width: (val.with_type ? 250 : elementWidth) + 'px' }">
          <WareHouseTypeSelect v-if="val.with_type" v-model="ruleForm[val.field_name_en]"
            v-model:type="ruleForm[val.type_field || 'warehouse_type']" :multiple="!!val.multiple"
            :default-first="!!val.default_first" style="width: 100%" :permission="val.permission"
            @change="handleEmit(val)" @type-change="handleEmit(val)" />
          <WareHouseSelect v-else v-model="ruleForm[val.field_name_en]" :multiple="!!val.multiple"
            :default-first="!!val.default_first" style="width: 100%" :permission="val.permission" @change="handleEmit(val)" />
        </div>

      </div>
    </div>
    <div>
      <el-tooltip content="重置搜索条件" effect="dark" placement="top">
        <div class="reset" @click="resetSearch()"><i class="iconfont iconqingchu"></i></div>
      </el-tooltip>
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import pickerOptions from '@/libs/pickerOptions.ts'
import { getDictTreeListApi } from '@/api/form.ts'
import WareHouseSelect from '@/views/inventory/components/WareHouseSelect.vue'
import WareHouseTypeSelect from '@/views/inventory/components/WareHouseTypeSelect.vue'

// 表单类型分组配置
const FORM_TYPE_GROUPS = {
  input: ['input', 'input_number', 'textarea', 'input_float', 'input_price', 'input_percentage'],
  select: ['radio', 'select', 'checkbox'],
  cascader: ['cascader', 'cascader_radio'],
  date: ['date_time_picker', 'date_picker']
}

export default {
  emits: ['handleEmit', 'resetSearch'],
  name: 'FormList',
  components: {
    WareHouseSelect,
    WareHouseTypeSelect,
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
    ueditorFrom: defineAsyncComponent(() => import('@/components/form-common/oa-wangeditor')),
    manageRange: defineAsyncComponent(() => import('@/components/form-common/select-manageRange')),
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department')),
    selectLabel: defineAsyncComponent(() => import('@/components/form-common/select-label')),
    selectOne: defineAsyncComponent(() => import('@/components/form-common/select-one')),
    cascaderConfirm: defineAsyncComponent(() => import('@/components/form-common/cascader-confirm'))
  },
  props: {
    list: {
      type: Array,
      default: () => {
        return []
      }
    },
    scopeFrames: {
      type: Array,
      default: () => {
        return []
      }
    },
    timeValue: {
      type: [Array, String],
      default: () => []
    },

    type: {
      type: String,
      default: ''
    },
    btnShow: {
      type: Boolean,
      default: true
    },
    is_develp: {
      type: Boolean,
      default: false
    },
    isTimeArray: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      saveLoading: false,
      activeVal: '',
      activeItem: {},
      elementWidth: '210',
      ruleForm: {},
      form: {},
      rule: {},
      autosize: {
        minRows: 6
      },
      pickerOptions,
      activeIndex: null,
      fileParams: {
        relation_type: 'client', // 上传类型 客户：client  订单：contract
        relation_id: 0, // 关联id订单id或者别的id
        way: 2,
        eid: 0
      },
      member: ['user_id', 'update_user_id', 'owner_user_id', 'creator', 'salesman', 'salesman_id', 'test_uid', 'card_id'],
      heightInputRole: 32,
      memberShow: false,
      userList: [],
      addressList: [],
      addressLoading: false,
      timeVal: [],
      labelList: [], // 选中客户标签
      cityList: [],
      selectFilterMap: {}
    }
  },
  computed: {
    cascaderAddressProps() {
      return {
        checkStrictly: false,
        label: 'name',
        value: 'value',
        multiple: true,
        emitPath: true
      }
    }
  },
  watch: {
    list: {
      handler() {
        this.initAddressList()
      },
      deep: true,
      immediate: true
    },
    timeValue: {
      handler(newVal) {
        this.timeVal = newVal
      },
      deep: true,
      immediate: true
    }
  },

  created() {
    this.initSearchDefault()
  },

  methods: {
    /**
     * 初始化筛选项默认值
     * 把配置了 search_default 的筛选项回显到表单，仅同步界面，不触发查询
     * （查询参数由使用方在自己的 where 中同步初始化）
     */
    initSearchDefault() {
      this.list.forEach((item) => {
        if (!item || !item.field_name_en) return
        // 时间类型下拉不可清空，始终回显默认类型
        if (item.form_value === 'date_type_picker') {
          this.ruleForm[item.type_field] = item.type_default
        }
        const defaultValue = item.search_default
        if (defaultValue === undefined || defaultValue === null || defaultValue === '') return
        this.ruleForm[item.field_name_en] = defaultValue
      })
    },

    // 判断是否为输入框类型
    isInputType(formValue) {
      return FORM_TYPE_GROUPS.input.includes(formValue)
    },

    // 判断是否为下拉选择类型
    isSelectType(formValue) {
      return FORM_TYPE_GROUPS.select.includes(formValue)
    },

    // 判断是否为级联选择类型
    isCascaderType(formValue) {
      return FORM_TYPE_GROUPS.cascader.includes(formValue)
    },

    // 判断是否为日期类型
    isDateType(formValue) {
      return FORM_TYPE_GROUPS.date.includes(formValue)
    },

    // 判断是否为输入选择类型
    isInputSelectType(val) {
      return (
        val.form_value == 'input_select' && !this.member.includes(val.field_name_en) && val.field_name_en !== 'frame_id'
      )
    },

    // 搜索配置包含省市区时，提前加载地区树
    initAddressList() {
      const hasAddressSearch = this.list.some((item) => item.form_value === 'cascader_address')
      if (hasAddressSearch) {
        this.getCityList()
      }
    },

    normalizeAddressOptions(options) {
      if (!Array.isArray(options)) {
        return []
      }

      return options.map((item) => {
        const value = item.value !== undefined ? item.value : item.id
        const label = item.name || item.label
        const children = this.normalizeAddressOptions(item.children)
        const normalizedItem = {
          ...item,
          id: item.id !== undefined ? item.id : value,
          value,
          name: label,
          label
        }
        if (children.length > 0) {
          normalizedItem.children = children
        } else {
          delete normalizedItem.children
        }
        return {
          ...normalizedItem
        }
      })
    },

    // 获取选项列表
    getOptionList(val) {
      return val.data_type == 1 || !val.data_type ? val.data_dict : val.customize_items
    },

    // 判断选项是否超过阈值
    isLargeOptionList(val) {
      const options = this.getOptionList(val)
      return Array.isArray(options) && options.length > 50
    },

    // 剔除字典里的空项：空项的 value 为 undefined，会被 el-select 判定为“已选中”，
    // 展开时自动滚动到该项，导致下拉不是从第一条开始显示
    filterValidOptions(options) {
      const isValid = (item) => {
        if (!item) return false
        const value = item.value ?? item.id
        const label = item.name ?? item.label
        return value !== undefined && value !== null && value !== '' && label !== undefined && label !== null && label !== ''
      }
      // 没有空项时返回原数组引用，避免每次渲染都生成新数组
      return options.every(isValid) ? options : options.filter(isValid)
    },

    // 返回实际渲染的选项列表（大数据量时限制数量）
    getDisplayOptions(val) {
      const rawOptions = this.getOptionList(val)
      if (!Array.isArray(rawOptions)) return rawOptions || []
      const allOptions = this.filterValidOptions(rawOptions)

      // 选项 <= 50，保持原行为
      if (allOptions.length <= 50) return allOptions

      const fieldKey = val.field_name_en
      const filtered = this.selectFilterMap[fieldKey]

      // 基础列表：有搜索结果用搜索结果，否则取前 50 条
      let base = Array.isArray(filtered) ? filtered.slice(0, 50) : allOptions.slice(0, 50)

      // 合并当前已选中的值，确保选中项标签正常显示
      const selected = this.ruleForm[fieldKey]
      if (selected != null && selected !== '') {
        const selectedArr = Array.isArray(selected) ? selected : [selected]
        if (selectedArr.length > 0) {
          const baseValues = new Set(base.map((o) => o.value || o.id))
          const missing = allOptions.filter((o) => {
            const v = o.value || o.id
            return selectedArr.includes(v) && !baseValues.has(v)
          })
          if (missing.length > 0) {
            base = missing.concat(base)
          }
        }
      }

      return base
    },

    // 下拉框展开/收起时清除搜索过滤
    handleSelectVisibleChange(visible, val) {
      if (visible) {
        delete this.selectFilterMap[val.field_name_en]
      }
    },

    // 远程搜索回调
    handleSelectFilter(query, val) {
      const fieldKey = val.field_name_en
      if (!query) {
        return
      }
      const allOptions = this.getOptionList(val)
      if (!Array.isArray(allOptions)) return
      const keyword = query.toLowerCase()
      const result = allOptions.filter((item) => {
        const label = (item.name || item.label || '').toLowerCase()
        return label.includes(keyword)
      })
      this.selectFilterMap[fieldKey] = result
    },

    // 获取级联选择器选项
    getCascaderOptions(val, index) {
      return val.data_type == 1 || !val.data_type
        ? this.getUniqueOptions(val.data_dict, index)
        : this.getUniqueOptions(val.customize_items, index)
    },

    // 获取级联选择器属性
    getCascaderProps(val) {
      return {
        checkStrictly: val.checkStrictly === undefined ? false : val.checkStrictly,
        emitPath: val.emitPath === undefined ? true : val.emitPath,
        label: 'name',
        value: 'value',
        multiple: val.multiple === undefined ? true : val.multiple
      }
    },

    // 获取标签选项
    getTagOptions(val, index) {
      return val.data_type == 1 || !val.data_type
        ? this.getUniqueOptions(val.data_dict, index)
        : this.getUniqueOptions(val.customize_items, index)
    },

    getUniqueOptions(options, parentIndex) {
      if (!options || !Array.isArray(options)) {
        return []
      }
      return options.map((item, index) => {
        // 确保每个选项都有唯一的value或id
        const uniqueValue = item.value !== undefined ? item.value : item.id
        const uniqueKey = uniqueValue !== undefined ? uniqueValue : `${parentIndex}_${index}_default`
        return {
          ...item,
          // 添加唯一标识符以防万一value和id都不存在
          uniqueKey
        }
      })
    },
    reset() {
      this.timeVal = []
      this.list.map((item) => {
        if (Object.hasOwnProperty.call(item, 'value')) {
          this.ruleForm[item.field_name_en] = item.value
        }
      })
    },

    setValue(key = 'value') {
      this.list.forEach((item, index) => {
        if (item && item[key]) {
          this.ruleForm[item.field_name_en] = item[key]

          if (item.type === 'date_picker') {
            if (!Array.isArray(item[key]) && item[key].length > 0) {
              item.data_dict = item[key].split('-')
            } else if (Array.isArray(item[key]) && item[key].length > 0) {
              item.data_dict = item[key]
            } else {
              item.data_dict = []
            }
          } else if (item.type === 'input_select' && !this.member.includes(item.field_name_en)) {
            if (item.options.length > 0) {
              item.data_dict = item.options[0]
            } else {
              item.data_dict = []
            }
          } else if (item.type === 'cascader_address') {
            this.getCityList()
          } else if (item.type === 'personnel') {
            item.value = item.value || []
          }
        } else {
          this.ruleForm[item.field_name_en] = ''
        }
      })

      this.$emit('handleEmit', this.ruleForm, 'firstTrigger')
    },

    // 选择时间区域
    onchangeTime(e, val) {
      if (val.data_dict == null) {
        this.ruleForm[val.field_name_en] = ''
        this.$emit('handleEmit', this.ruleForm)
      } else {
        if (typeof val.data_dict === 'string') {
          this.ruleForm[val.field_name_en] = val.data_dict
        } else {
          this.ruleForm[val.field_name_en] = val.data_dict.join('-')
        }
        this.handleEmit()
      }
    },

    handleEmit(val, item) {
      this.$emit('handleEmit', this.ruleForm)
    },

    // 打开一对一表格
    handleTable(row, index) {
      this.activeIndex = index
      this.$refs.tableDialog.openBox(row.id)
    },

    oneLabel(index) {
      this.list[index].data_dict = []
      let key = this.list[index].field_name_en
      this.ruleForm[key] = ''
      this.$emit('handleEmit', this.ruleForm)
    },

    resetSearch(type) {
      // 1. 清理标签、关键字、表单
      this.labelList = []
      this.ruleForm.keyword_default = ''
      this.reset()

      // 2. 清理标签组件选中项
      if (this.$refs.selectLabel) {
        this.$refs.selectLabel.selectList = []
      }

      // 3. 重置管理范围组件
      this.$nextTick(() => {
        const ref = this.$refs.manageRange
        if (!ref) return
        const target = this.type === 0 ? ref : Array.isArray(ref) ? ref[0] : ref
        target?.reset?.()
      })

      // 4. 统一清理 list 中各控件的值
      const arrayTypes = new Set([
        'input_select',
        'user_id',
        'frame_id',
        'salesman',
        'salesman_id',
        'personnel',
        'date_picker',
        'date_time_picker',
        'monthrange',
        'test_uid'
      ])

      this.list.forEach((item) => {
        if (item.form_value === 'date_type_picker') {
          item.data_dict = []
          this.ruleForm[item.type_field] = item.type_default
        } else if (arrayTypes.has(item.form_value)) {
          item.data_dict = []
          item.value = []
          item.option = []
        } else {
          item.value = ''
          this.ruleForm[item.field_name_en] = ''
        }
      })

      // 5. 清理人员选择组件
      if (this.$refs.selectMember) {
        if (Array.isArray(this.$refs.selectMember)) {
          this.$refs.selectMember.forEach((ref) => ref.clearSelection?.())
        } else {
          this.$refs.selectMember.clearSelection?.()
        }
      }

      // 6. 通知父组件
      // 配置了 default_first 的仓库筛选项属于「必选条件」：上面清空后，仓库组件会在本轮更新中
      // 自动回填首仓并自带发起一次查询（携带的正是清空后的 ruleForm）。此时重置若再发一次
      // 「空仓库」查询，两次查询并发下生效的往往是前一次，界面就会出现
      // 「筛选框显示首仓、列表却是全部仓库」的不一致，故这里把查询让给回填那次
      const hasDefaultWarehouse = this.list.some(
        (item) => item && item.form_value === 'warehouse_select' && item.default_first
      )
      this.$emit('resetSearch', hasDefaultWarehouse ? type || 'keepDefaultSearch' : type)
    },
    // 选择人员回调
    getSelectList(data, val) {
      this.ruleForm[val.field_name_en] = ''
      let ids = []
      if (data.length > 0) {
        data.forEach((item) => {
          ids.push(item.value)
        })

        this.ruleForm[val.field_name_en] = ids
      }
      val.value = ids

      this.handleEmit()
    },

    // 选择部门完成回调
    changeMastart(data, val) {
      val.data_dict = data || []
      this.ruleForm[val.field_name_en] = data[0] ? data[0].id : ''
      this.handleEmit()
    },

    // 选择一对一回调
    getSelection(data, val) {
      val.data_dict = data || []
      this.ruleForm[val.field_name_en] = data.id
      this.handleEmit()
      this.activeIndex = -1
    },

    // 选中标签回调
    handleLabelConf(data, val) {
      this.ruleForm[val.field_name_en] = data.ids
      val.value = data.ids
      this.handleEmit(this.activeVal, this.activeItem)
      this.activeIndex = -1
    },

    getCityList(index) {
      if (this.addressLoading || this.addressList.length > 0) {
        return
      }
      this.addressLoading = true
      let obj = {
        type_id: 2
      }
      getDictTreeListApi(obj)
        .then((res) => {
          const { data, status } = res
          this.addressList = this.normalizeAddressOptions(data)
        })
        .catch((error) => {
          console.error('获取城市列表失败:', error)
        })
        .finally(() => {
          this.addressLoading = false
        })
    },

    changeFrame(e) {
      this.scope_frame = e
      this.ruleForm.scope_frame = this.scope_frame
      this.handleEmit()
    },

    heightInput() {
      setTimeout(() => {
        const height = this.$refs.getHeight[0].clientHeight
        this.heightInputRole = height === 0 ? 36 : height
      }, 200)
    },

    // 删除客户标签
    cardTag(index, index_s, type) {
      if (type === 1) {
        // 删除人员
        let key = this.list[this.activeIndex]?.field_name_en
        this.list[index].data_dict = []
        this.ruleForm[key] = ''
      }
      this.$emit('handleEmit', this.ruleForm)
    }
  }
}
</script>

<style lang="scss" scoped>
// 级联选择器等 Element Plus 多根组件不带 scoped 属性，需用 :deep 命中
.flex :deep(.search-form) {
  margin-right: 10px;
}
.search-width {
  width: 200px;
}

.flex {
  display: flex;
  flex-wrap: wrap;
  align-content: flex-start;
  margin: 0;
  padding: 0;
  gap: 10px 0px;
}

.flex > * {
  margin-top: 0;
}

.flex-box {
  display: flex;
  align-items: center;
  line-height: 32px;
  .tag {
    background-color: #f0f2f5;
    border: none;
  }
}
.lh32 {
  margin-top: 3px;
}
.no-member {
  font-size: 13px;
  color: #c0c4cc;
}
.form-box {
  display: inline-block;
  // margin-bottom: 10px;
}
// 时间类型 + 日期区间拼成一个控件：去掉相接处的圆角与重复边框
.date-type-picker {
  display: flex;
  .date-type-select {
    width: 100px;
    :deep(.el-select__wrapper) {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }
  }
  // 日期选择器为多根组件，不带 scoped 属性
  :deep(.time) {
    margin-left: -1px;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
  }
  .date-type-select:hover,
  .date-type-select :deep(.is-focused),
  :deep(.time:hover),
  :deep(.time.is-active) {
    position: relative;
    z-index: 1;
  }
}
.plan-footer-one {
  width: 200px;
  height: 32px;
  line-height: 30px;
  .placeholder {
    font-size: 13px;
    color: #c0c4cc;
  }
  span {
    margin-right: 6px;
  }
}
.mt2 {
  margin-top: 3.5px;
}
.mt4 {
  margin-top: 4px;
}
:deep(.el-cascader__tags .el-tag:first-child) {
  max-width: 70%;
}
// 省市区 cascader 同时开启 multiple + filterable 时，内部搜索 input 的上下 margin(各2px)
// 会把 tags 撑高，导致 updateStyle 把内框算成 34px；去掉其上下 margin 让 tags 高度回落，最终对齐其它项的 32px
:deep(.address-cascader .el-cascader__search-input) {
  margin-top: 0;
  margin-bottom: 0;
}

:deep(.el-input__inner.select) {
  white-space: nowrap;
  font-size: 0;
}
:deep(.el-input__inner.select .el-tag) {
  vertical-align: middle;
}
:deep(.el-input__inner.select .el-tag:first-child) {
  max-width: 70%;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
