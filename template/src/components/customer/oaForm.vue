<!--
  @FileDescription: 客户/订单新增的动态表单组件
  功能：提供动态表单渲染，支持多种表单控件类型
-->
<template>
  <div>
    <!-- 表单主体 -->
    <el-form ref="form" :model="ruleForm" :rules="rule" :label-width="formLabelWidth" @submit.native.prevent size="small">
      <!-- 遍历表单分组 -->

      <div v-for="(item, itemIndex) in formInfo" :key="itemIndex" class="p20">
        <!-- 分组标题：父组件可用 header-<ident> 插槽自定义（如插入编辑按钮），未提供时回退为默认标题 -->
        <div v-if="showGroupHeader(item)" class="from-item-title mb20 flex-between" style="width: 100%">
          <slot :name="groupHeaderSlotName(item)" :group="item" :type="productType">
            <span>{{ item.title }}</span>
          </slot>
        </div>

        <div class="form-box" v-if="item.ident == 'product'">
          <!-- <div
            v-if="item.ident == 'product' && viewMode"
            class="iconfont iconbianji3"
            title="编辑产品清单"
            @click="editProduct"
          >
            编辑
          </div> -->
          <slot name="product" :type="productType"></slot>
        </div>

        <!-- 表单字段区域 -->
        <div class="form-box" v-else>
          <!-- 遍历表单字段 -->
          <el-row :gutter="20">
            <el-col
              v-for="(val, index) in item.data"
              :key="index"
              :span="viewMode && !['file', 'images', 'oaWangeditor'].includes(val.type) ? 12 : 24"
            >
              <el-form-item v-show="val.input_type !== 'hidden'" :prop="val.key" class="label-box inline-edit-item">
                <template #label>
                  <span class="label">{{ val.key_name }}：</span>
                </template>
                <i v-if="viewMode && savingKeyMap[val.key]" class="el-icon-loading inline-edit-saving-icon" />
                <!-- 文本输入框 -->
                <template v-if="val.input_type === 'input' && val.type === 'text'">
                  <el-input
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    :maxlength="val.max"
                    :min="val.min"
                    :placeholder="val.placeholder ? val.placeholder : '请输入' + val.key_name"
                    clearable
                    size="small"
                    class="clickZone"
                    :ref="`input_${val.key}`"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    @keyup.enter.native="handlePopoverHide(ruleForm[val.key])"
                  />
                  <div v-else :class="fieldViewClass(val)">
                    {{ ruleForm[val.key] || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      class="edit-icon iconfont iconbianji1"
                      @click.stop="handleClick(val)"
                    ></i>
                  </div>
                </template>

                <el-input v-if="val.input_type === 'hidden'" v-model="ruleForm[val.key]" type="hidden" />

                <!-- 数字输入框 -->
                <template v-if="val.type === 'number'">
                  <el-input-number
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    :controls="false"
                    :max="val.max"
                    :min="val.min"
                    :placeholder="val.placeholder ? val.placeholder : '请选择' + val.key_name"
                    :precision="val.decimal_place"
                    size="small"
                    style="width: 100%"
                    class="clickZone"
                    :ref="`input_${val.key}`"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    @keyup.enter="handlePopoverHide(ruleForm[val.key])"
                  />
                  <div v-else :class="fieldViewClass(val)">
                    {{ ruleForm[val.key] || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 文本域 -->
                <template v-if="val.type === 'textarea'">
                  <el-input
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    :autosize="autosize"
                    :maxlength="val.max"
                    :placeholder="val.placeholder ? val.placeholder : '请输入' + val.key_name"
                    clearable
                    show-word-limit
                    size="small"
                    type="textarea"
                    class="clickZone"
                    style="width: 100%"
                    :ref="`input_${val.key}`"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    @keyup.enter="handlePopoverHide(ruleForm[val.key])"
                  />

                  <div v-else :class="[fieldViewClass(val), 'white-space-pre-line']">
                    {{ ruleForm[val.key] || '--'
                    }}<i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 级联选择器 -->
                <template v-if="val.input_type === 'select' && val.options_level > 1">
                  <el-cascader
                    v-if="!viewMode || editKey == val.key"
                    :ref="`input_${val.key}`"
                    v-model="ruleForm[val.key]"
                    :options="val.options"
                    :placeholder="val.placeholder ? val.placeholder : '请选择' + val.key_name"
                    :props="{
                      checkStrictly: true,
                      label: 'label',
                      value: 'value',
                      multiple: val.type !== 'single' && val.type !== 'cascader'
                    }"
                    :show-all-levels="val.key !== 'path'"
                    clearable
                    collapse-tags
                    filterable
                    size="small"
                    style="width: 100%"
                    class="clickZone"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    @keyup.enter.native="handlePopoverHide(ruleForm[val.key])"
                  />
                  <div v-else :class="fieldViewClass(val)">
                    {{ val.text || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 下拉选择器 -->
                <template v-if="val.input_type === 'select' && val.options_level <= 1 && val.key !== 'clue_id'">
                  <el-select
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    :multiple="val.type !== 'single'"
                    :placeholder="getSelectPlaceholder(val)"
                    clearable
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    filterable
                    :remote="isRemoteSelectField(val)"
                    :remote-method="(query) => remoteSelectSearch(val, query)"
                    :loading="!!remoteLoadingMap[val.key]"
                    reserve-keyword
                    size="small"
                    @change="changeValue(val)"
                    @visible-change="(visible) => handleSelectVisibleChange(val, visible)"
                    style="width: 100%"
                    class="clickZone"
                    :ref="`input_${val.key}`"
                    @keyup.enter.native="handlePopoverHide(ruleForm[val.key])"
                  >
                    <el-option
                      v-for="el in getSelectOptions(val)"
                      :key="el.value"
                      :disabled="el.disabled"
                      :label="el.label"
                      :value="el.value"
                    />
                  </el-select>
                  <div v-else :class="fieldViewClass(val)">
                    {{ val.text || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 远程搜索 -->
                <div v-if="val.key === 'clue_id'" class="flex">
                  <el-select
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    placeholder="请输入线索名称搜索"
                    filterable
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    remote
                    clearable
                    reserve-keyword
                    :remote-method="remoteMethod"
                    :loading="loading"
                    style="width: 100%"
                    class="clickZone"
                    :ref="`input_${val.key}`"
                    @keyup.enter.native="handlePopoverHide(ruleForm[val.key])"
                  >
                    <el-option v-for="item in cluesOption" :key="item.value" :label="item.label" :value="item.value">
                    </el-option>
                  </el-select>
                  <div v-else :class="fieldViewClass(val)">
                    {{ val.text || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </div>

                <!-- 客户标签选择器 -->
                <template v-if="val.key == 'customer_label'">
                  <div
                    v-if="!viewMode || editKey == val.key"
                    class="el-input__inner select plan-footer-one clickZone"
                    :class="{ 'is-saving-disabled': !!savingKeyMap[val.key] }"
                    @click="handleLabel(val)"
                  >
                    <div v-if="labelList && labelList.length == 0" class="placeholder">
                      {{ val.placeholder ? val.placeholder : '请选择' + val.key_name }}
                    </div>
                    <div>
                      <span
                        v-for="(item, labelIndex) in labelList"
                        :key="labelIndex"
                        class="el-tag el-tag--small el-tag--info el-tag--light"
                        @click.stop="cardTag(labelIndex)"
                      >
                        {{ item.name }}
                        <i class="el-tag__close el-icon-close" @click.stop="cardTag(labelIndex)" />
                      </span>
                    </div>
                  </div>
                  <div v-else :class="fieldViewClass(val)">
                    <span>{{ labelList.length ? joinName(labelList) : '--' }}</span>
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 人员选择器 -->
                <template v-if="val.input_type === 'member'">
                  <select-member
                    v-if="!viewMode || editKey == val.key"
                    :value="val.options"
                    :onlyOne="val.type == 'singleMember' ? true : false"
                    @handlePopoverHide="getSelectList($event, val)"
                    style="width: 100%"
                    class="clickZone"
                  >
                  </select-member>
                  <div v-else :class="[fieldViewClass(val), 'member-box']">
                    <template v-if="val.options && val.options.length > 0">
                      <span v-for="(item, index) in val.options" :key="index" class="lh-center mr10">
                        <img v-default-avatar="item" :src="getAvatarSrc(item)" alt="" class="avatar" />
                        {{ item.name }}
                      </span>
                    </template>
                    <template v-else> -- </template>
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 单选按钮组 -->
                <template v-if="val.type === 'radio'">
                  <el-radio-group
                    v-model="ruleForm[val.key]"
                    v-if="!viewMode || editKey == val.key"
                    class="clickZone"
                    style="width: 100%"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                    @keyup.enter.native="handlePopoverHide(ruleForm[val.key])"
                  >
                    <el-radio v-for="(el, index) in val.options" :key="index" :label="el.value">
                      {{ el.label }}
                    </el-radio>
                  </el-radio-group>
                  <div v-else :class="fieldViewClass(val)">
                    {{ val.text || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 多选按钮组 -->
                <template v-if="val.type === 'checked' && val.key !== 'customer_label'">
                  <el-checkbox-group
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    class="clickZone"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                  >
                    <el-checkbox v-for="(check, checkIndex) in val.options" :key="checkIndex" :label="check.value">
                      {{ check.label }}
                    </el-checkbox>
                  </el-checkbox-group>
                  <div v-else :class="fieldViewClass(val)">
                    {{ val.text || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 日期选择器 -->
                <template v-if="val.type === 'date'">
                  <el-date-picker
                    style="width: 100%"
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    :format="'yyyy-MM-dd'"
                    :placeholder="val.placeholder ? val.placeholder : '请选择' + val.key_name"
                    :value-format="'yyyy-MM-dd'"
                    clearable
                    size="small"
                    type="date"
                    class="clickZone"
                    :ref="`input_${val.key}`"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                  />
                  <div v-else :class="fieldViewClass(val)">
                    {{ ruleForm[val.key] || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 日期时间选择器 -->
                <template v-if="val.type === 'datetime'">
                  <el-date-picker
                    v-if="!viewMode || editKey == val.key"
                    v-model="ruleForm[val.key]"
                    :format="'yyyy-MM-dd HH:mm:ss'"
                    :placeholder="val.placeholder ? val.placeholder : '请选择' + val.key_name"
                    :value-format="'yyyy-MM-dd HH:mm:ss'"
                    default-time="12:00:00"
                    clearable
                    size="small"
                    style="width: 100%"
                    type="datetime"
                    class="clickZone"
                    :ref="`input_${val.key}`"
                    :disabled="isReadonlyField(val) || !!savingKeyMap[val.key]"
                  />
                  <div v-else :class="fieldViewClass(val)">
                    {{ ruleForm[val.key] || '--' }}
                    <i
                      v-if="!isReadonlyField(val)"
                      @click.stop="handleClick(val)"
                      class="edit-icon iconfont iconbianji1"
                    ></i>
                  </div>
                </template>

                <!-- 文件上传 -->
                <template v-if="val.type === 'file'">
                  <upload-file
                    :maxLength="val.max"
                    :only-image="false"
                    :value="val.files"
                    @getVal="getVal($event, val)"
                  />
                </template>
                <!-- 图片上传 -->
                <upload-file
                  v-if="val.type === 'images'"
                  :maxLength="val.max"
                  :only-image="true"
                  :value="val.files"
                  @getVal="getVal($event, val)"
                />

                <!-- 富文本编辑器 -->
                <template v-if="val.type === 'oaWangeditor'">
                  <div v-if="!viewMode || editKey == val.key" class="clickZone" style="height: 100%; width: 100%">
                    <ueditor-from
                      ref="ueditorFrom"
                      :border="true"
                      :content="ruleForm[val.key]"
                      :height="`400px`"
                      @input="(value) => ueditorEdit(val, value)"
                    />
                    <div class="mt14" v-if="viewMode">
                      <el-button size="small" :disabled="!!savingKeyMap[val.key]" @click="editKey = ''">取消</el-button>
                      <el-button
                        size="small"
                        type="primary"
                        :loading="!!savingKeyMap[val.key]"
                        @click="handlePopoverHide(ruleForm[val.key])"
                        >保存</el-button
                      >
                    </div>
                  </div>

                  <template v-else>
                    <div
                      v-if="ruleForm[val.key]"
                      class="readonly-mode-field"
                      @click.stop="handleClick(val)"
                      v-html="ruleForm[val.key]"
                    ></div>
                    <div v-else class="readonly-mode-field" @click.stop="handleClick(val)">--</div>
                  </template>
                </template>
              </el-form-item>
            </el-col>
          </el-row>
        </div>
      </div>
    </el-form>

    <!-- 表单操作按钮 -->
    <div v-if="isShowFooter" class="button from-foot-btn fix btn-shadow">
      <el-button class="el-btn" size="small" @click="resetForm"> 取消 </el-button>

      <el-button v-if="btnShow && types !== 3" :loading="addContractLoading" size="small" @click="addContract">
        {{ type == 'contract' ? '保存并添加回款' : '保存并添加商机' }}
      </el-button>

      <el-button :loading="saveLoading" size="small" type="primary" @click="handleConfirm('ruleForm')">
        保存
      </el-button>
    </div>
    <!-- 客户标签弹窗 -->
    <label-dialog ref="labelDialog" :config="labelData" @handleLabelConf="handleLabelConf"></label-dialog>
  </div>
</template>

<script>
import { getAvatarSrc } from '@/utils/avatar'
import { getStorageJson } from '@/utils/storage'
import { extractArrayIds, modalSure } from '@/libs/public'
import {
  contractCategorySelectApi,
  clientConfigLabelApi,
  chargeEditSubmitApi,
  customerSelectApi
} from '@/api/enterprise'
import {
  clientCluesSearchApi,
  clientContractEditApi,
  clientLiaisonEditApi,
  savecluesEditApi,
  oddsEditApi
} from '@/api/client'

// 支持远程搜索的下拉字段登记表：字段 key -> 远程配置。
// 后端表单配置默认只下发 20 条选项，登记后该字段改为输入关键字调接口检索。
// 新增其他远程搜索字段（不同业务模块的同类字段）只需在此补一行，无需改模板。
const REMOTE_SELECT_FIELDS = {
  // 订单-客户名称
  contract_customer: { api: customerSelectApi, placeholder: '请输入客户名称搜索' },
  // 商机-客户名称
  odds_customer: { api: customerSelectApi, placeholder: '请输入客户名称搜索' }
}

// 远程搜索单次拉取条数，接口上限 100
const REMOTE_SELECT_LIMIT = 50

export default {
  name: 'OaForm',
  components: {
    selectMember: () => import('@/components/form-common/select-member'),
    uploadFile: () => import('@/components/form-common/oa-upload'),
    ueditorFrom: () => import('@/components/form-common/oa-wangeditor'),
    labelDialog: () => import('@/views/customer/list/components/labelDialog')
  },

  props: {
    // 表单配置信息
    formInfo: {
      type: Array,
      default: () => []
    },
    uid: {
      type: String,
      default: ''
    },
    viewMode: {
      // 查看模式true
      type: Boolean,
      default: false
    },
    id: {
      type: [String, Number], // 客户、线索、商机id
      default: undefined
    },
    keyWord: {
      type: String, // 线索、商机
      default: 'customer'
    },

    // 表单类型
    type: {
      type: [Number, String],
      default: ''
    },

    // 表单子类型
    types: {
      type: [Number, String],
      default: ''
    },

    // 是否显示附加按钮
    btnShow: {
      type: Boolean,
      default: true
    },
    // 是否显示附加按钮
    isShowFooter: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      defaultLabelList: [], // 默认标签列表
      drawer: true, // 抽屉状态
      loading: false,
      saveLoading: false, // 保存加载状态
      ruleForm: {}, // 表单数据
      rule: {}, // 表单验证规则
      autosize: {
        // 文本域自适应配置
        minRows: 6
      },
      itemData: {}, // 当前编辑的字段信息
      productType: 'edit',
      editKey: '',
      savingKeyMap: {}, // 字段保存中状态 { [field.key]: boolean }

      labelData: {},
      oldValue: null,
      cluesOption: [],
      remoteOptionsMap: {}, // 远程搜索结果 { [field.key]: 选项数组 }，无值表示未搜索、沿用默认选项
      remoteLoadingMap: {}, // 远程搜索加载状态 { [field.key]: boolean }
      remoteRequestIdMap: {}, // 远程搜索请求序号 { [field.key]: number }，用于丢弃过期响应
      treeData: [], // 树形数据
      imageList: [], // 图片列表
      labelAllList: [], // 全部客户标签
      addContractLoading: false, // 添加订单加载状态
      attachList: [], // 附件列表
      labelList: [] // 选中客户标签
    }
  },
  computed: {
    fieldList() {
      return this.formInfo.flatMap((group) => (group.data || []));
    },
    formLabelWidth() {
      const maxLabelCharLength = this.fieldList.reduce((max, field) => {
        const length = (field.key_name?.length || 0) + (!!field.required ? 1 : 0);
        return Math.max(max, length);
      }, 0);

      return `${(maxLabelCharLength + 1) * 14}px`;
    }
  },
  watch: {
    // 监听表单配置变化
    formInfo: {
      handler(nVal, oVal) {
        // 精准监听 + 防重复触发
        if (JSON.stringify(nVal) === JSON.stringify(oVal)) return false
        if (nVal.length == 0) return false
        this.formatFormInfo(nVal)
      },
      immediate: true,
      deep: true
    },

    // 监听附件列表变化
    attachList: {
      handler(nVal) {
        if (nVal.length > 0) {
          const filekey = this.getKey('file')
          let ids = nVal.map((item) => item.id)
          this.ruleForm[filekey] = ids
        }
      }
    },

    // 监听图片列表变化
    imageList: {
      handler(nVal) {
        if (nVal.length > 0) {
          const imgkey = this.getKey('images')
          let ids = nVal.map((item) => item.id)
          this.ruleForm[imgkey] = ids
        }
      }
    },
    editKey(newVal) {
      this.handleFocus(newVal)
    },
    'ruleForm.clue_id'(newVal) {
      if (!newVal) return
      const clue = this.cluesOption.find((item) => item.value === newVal)
      if (clue) this.fillLiaisonInfo(clue)
    },
    viewMode(newVal) {
      // 如果从true变为false（非视图模式），触发聚焦
      if (!newVal) {
        this.handleFocus(this.editKey)
      }
    }
  },

  mounted() {
    this.$nextTick(() => {
      if (this.viewMode) {
        this.handleDocumentClick = () => {
          this.handlePopoverHide('')
        }
        document.addEventListener('click', this.handleDocumentClick)
      }
    })
  },

  beforeDestroy() {
    if (this.viewMode && this.handleDocumentClick) {
      document.removeEventListener('click', this.handleDocumentClick)
    }
  },

  methods: {
    getAvatarSrc,
    removeEvent() {
      if (this.handleDocumentClick) {
        document.removeEventListener('click', this.handleDocumentClick)
      }
    },

    closeFn() {
      this.$refs.form.resetFields()
      for (let key in this.ruleForm) {
        this.ruleForm[key] = ''
      }
    },

    formatFormInfo(nVal, type) {
      // 1. 统一初始化：清空旧数据
      this.attachList = []
      this.imageList = []
      this.cluesOption = []
      this.remoteOptionsMap = {}
      this.remoteLoadingMap = {}

      // 2. 扁平化字段，一次循环完成所有逻辑
      const fields = nVal.flatMap((group) => group.data || [])

      fields.forEach((field) => {
        // 2.1 特殊类型预处理
        switch (field.key) {
          case 'customer_label':
            if (field.value) this.getLabel(field.value)
            break
          case 'clue_id':
            if (field.options.length) this.cluesOption = field.options
            break
        }

        // 远程搜索字段统一按单级下拉渲染，避免被误判成级联选择器
        if (this.isRemoteSelectField(field)) {
          field.options_level = 1
        }

        field.value = this.normalizeFieldValue(field, field.value)

        // 2.2 文件/图片自动挂载
        if (field.files?.length) {
          if (field.type === 'file') this.attachList = field.files
          if (field.type === 'images') this.imageList = field.files
        }

        // 2.3 选项类文本一次性计算
        if (
          ['radio', 'select', 'checked', 'single', 'multiple'].includes(field.type) &&
          field.key !== 'customer_label'
        ) {
          field.text = this.getText(field.options, field.value)
        }
        if (['checked', 'multiple'].includes(field.type) && field.value == '') {
          field.value = []
        }
        if (field.input_type === 'select' && field.value == 0) {
          field.value = ''
        }

        // 2.4 校验规则动态注入
        // trigger 必须同时含 change：el-select 内部 el-input 是 :validate-event="false"，
        // 且鼠标点选时 isSilentBlur 会吞掉 blur，它只在值变化时派发 el.form.change。
        // 若仅配 blur，选中后 el-form-item 走 validate('change') 匹配不到规则会直接 return，
        // 不重置 validateState，已有的红字错误提示便一直留在原地。
        if (field.required == 1) {
          this.$set(this.rule, field.key, [
            { required: true, message: `请输入${field.key_name}`, trigger: ['blur', 'change'] }
          ])
        }

        // 2.5 表单值智能合并：优先保留用户已填 > 新默认值 > 空数组（多选）
        const userValue = type == 'edit' ? this.normalizeFieldValue(field, this.ruleForm[field.key]) : field.value

        let finalValue =
          userValue ?? (['checked', 'multiple'].includes(field.type) && field.value === '' ? [] : field.value)

        // 选项类字段：对齐值与选项的类型，避免类型不一致导致 el-select 严格相等匹配不到（回显/「无匹配数据」异常）
        if (field.input_type === 'select') {
          finalValue =
            field.options_level > 1
              ? this.alignCascaderValue(finalValue, field.options, field.type !== 'single' && field.type !== 'cascader')
              : this.alignSelectValueType(finalValue, field.options)
        }

        this.$set(this.ruleForm, field.key, this.cloneValue(finalValue))
      })

      // 3. 强制刷新（仅一次）
      this.$nextTick(() => {
        this.$forceUpdate()
      })
    },
    /**
     * 获取上传文件的值
     * @param {Array} val - 文件数组
     * @param {Object} item - 当前字段配置
     */
    getVal(val, item) {
      let arr = val.map((el) => el.id)
      // if (this.ruleForm[item.key].length > 0) {
      //   this.ruleForm[item.key] = [...this.ruleForm[item.key], ...arr]
      // } else {
      this.ruleForm[item.key] = arr
      // }

      this.editKey = item.key
      this.itemData = item

      if (this.viewMode) {
        this.handlePopoverHide(this.ruleForm[item.key])
      }
    },
    /**
     * 分组标题插槽名，按分组标识区分，如 product 分组对应 header-product
     * @param {Object} group - 表单分组配置
     * @returns {String}
     */
    groupHeaderSlotName(group) {
      return `header-${group.ident}`
    },

    /**
     * 是否显示分组标题
     * 父组件提供了自定义标题插槽时无条件显示，否则沿用「分组启用且有字段」的默认规则
     * @param {Object} group - 表单分组配置
     * @returns {Boolean}
     */
    showGroupHeader(group) {
      if (this.$scopedSlots[this.groupHeaderSlotName(group)]) return true
      return group.status == 1 && (group.data || []).length > 0
    },

    joinName(obj) {
      return Object.values(obj)
        .filter((item) => item?.name) // 过滤无效项
        .map((item) => item.name)
        .join('、')
    },
    // 编辑产品清单
    editProduct() {
      this.editKey = 'product'
      this.productType = 'add'
    },

    isReadonlyField(field) {
      return !!(
        field?.readonly ||
        field?.system_field ||
        field?.disabled ||
        ['contract_no', 'odds_no'].includes(field?.key)
      )
    },
    fieldViewClass(field) {
      return this.isReadonlyField(field) ? 'readonly-display-field' : 'pointer'
    },
    handleClick(val) {
      if (val.key) {
        // 只读 div 上的 @click.stop 会阻止事件冒泡到 document，导致全局保存监听不触发。
        // 因此在切换到新字段前，先手动保存上一个正在编辑的字段，避免点击其他表单项时丢失修改。
        if (this.viewMode && this.editKey && this.editKey !== val.key) {
          this.handlePopoverHide('')
        }
        if (this.isReadonlyField(val)) {
          return
        }
        this.itemData = val
        this.editKey = val.key
        this.oldValue = this.cloneValue(this.ruleForm[val.key] ?? val.value)
      }
    },
    async handlePopoverHide(data) {
      if (!this.viewMode || !this.editKey) {
        return false
      }

      if (this.isReadonlyField(this.itemData) || ['contract_no', 'odds_no'].includes(this.editKey)) {
        this.editKey = ''
        return false
      }

      if (this.savingKeyMap[this.editKey]) {
        return false
      }

      // 富文本字段只能点击保存按钮触发
      if (this.itemData && this.itemData.type === 'oaWangeditor' && !data) {
        return false
      }
      // 获取所有el-form-item元素
      const formItems = document.querySelectorAll('.clickZone')
      let isClickInside = false

      // 检查点击目标是否在任何el-form-item内部
      formItems.forEach((item) => {
        if (item.contains(event.target)) {
          isClickInside = true
        }
      })

      // 如果点击的是外部区域
      if (!isClickInside || data) {
        const currentValue = this.normalizeFieldValue(this.itemData, this.ruleForm[this.editKey])
        if (!this.isSameValue(this.oldValue, currentValue)) {
          const obj = {
            field: this.editKey || this.itemData.key,
            value: data ? this.normalizeFieldValue(this.itemData, data) : currentValue
          }

          if ((this.editKey && this.editKey !== 'product') || data) {
            const apiMap = {
              clue: savecluesEditApi,
              clue_seas: savecluesEditApi,
              odds: oddsEditApi,
              customer: chargeEditSubmitApi,
              customer_seas: chargeEditSubmitApi,
              contract: clientContractEditApi,
              liaison: clientLiaisonEditApi
            }
            const currentApi = apiMap[this.keyWord]
            const fieldKey = this.editKey
            this.$set(this.savingKeyMap, fieldKey, true)

            try {
              await this.handleSubmit(currentApi, this.id, obj)
              this.$emit('fieldSaved', {
                field: fieldKey,
                value: obj.value
              })
              if (['status', 'eid', 'customer_label', 'clue_id'].includes(fieldKey)) {
                this.$emit('getDetails')
              } else {
                this.formInfo.forEach((item) => {
                  item.data.forEach((el) => {
                    if (el.key === fieldKey) {
                      el.value = this.cloneValue(currentValue)
                    }
                  })
                })

                setTimeout(() => {
                  this.formatFormInfo(this.formInfo, 'edit')
                }, 200)
              }

              if (this.editKey === fieldKey) {
                this.editKey = ''
                this.productType = 'edit'
              }
            } catch (error) {
              this.$message.error(error.data?.message || '保存失败')
            } finally {
              this.$set(this.savingKeyMap, fieldKey, false)
            }
          }
        } else {
          this.editKey = ''
        }
      }
    },
    async handleSubmit(api, id, data) {
      try {
        await api(id, data)
      } catch (error) {
        if (error?.data?.status === 2001) {
          try {
            await modalSure(error.data.message)
            return this.handleSubmit(api, id, {
              ...data,
              force: 1
            })
          } catch {
            throw {
              data: {
                message: '操作已取消'
              }
            }
          }
        }

        throw error
      }
    },
    // 聚焦方法
    handleFocus(key) {
      // 确保v-if条件为true时才执行
      if (!this.viewMode || this.editKey === key) {
        this.$nextTick(() => {
          const ref = this.$refs[`input_${key}`]
          // 统一处理数组或单个组件
          const input = Array.isArray(ref) ? ref[0] : ref
          // 安全调用 focus，仅当组件实例存在且拥有 focus 方法时才执行
          input && typeof input.focus === 'function' && input.focus()
        })
      }
    },

    // 打开客户标签
    handleLabel(val) {
      if (this.isReadonlyField(val)) {
        return
      }
      this.editKey = val.key
      this.itemData = val
      this.labelData = {
        title: '客户标签',
        width: '540px',
        label: this.labelList,
        edit: 1
      }
      this.$refs.labelDialog.handleOpen()
    },

    /**
     * 获取订单分类数据
     * @param {Number} index - 表单分组索引
     * @param {Number} index1 - 字段索引
     */
    async getCategory(index, index1) {
      const result = await contractCategorySelectApi()
      this.formInfo[index].data[index1].options = result.data
    },
    handleChange() {
      this.$forceUpdate()
    },
    remoteMethod(query) {
      const userInfo = getStorageJson('userInfo', {})
      let uid = this.uid || userInfo.id || ''
      if (query) {
        this.loading = true
        let data = {
          uid: uid,
          name: query
        }
        clientCluesSearchApi(data).then((res) => {
          this.cluesOption = res.data
          this.loading = false
        })
      }
    },

    /**
     * 判断字段是否为已登记的远程搜索下拉字段
     * @param {Object} field - 表单字段配置
     * @returns {Boolean}
     */
    isRemoteSelectField(field) {
      return !!(field && REMOTE_SELECT_FIELDS[field.key])
    },

    /**
     * 下拉框 placeholder：远程搜索字段提示可输入检索，其余沿用后端配置
     * @param {Object} field - 表单字段配置
     * @returns {String}
     */
    getSelectPlaceholder(field) {
      if (this.isRemoteSelectField(field)) {
        return REMOTE_SELECT_FIELDS[field.key].placeholder
      }
      return field.placeholder ? field.placeholder : '请选择' + field.key_name
    },

    /**
     * 下拉框选项：远程搜索有结果时用远程结果，未搜索/已清空关键字时回落到后端下发的默认选项
     * @param {Object} field - 表单字段配置
     * @returns {Array}
     */
    getSelectOptions(field) {
      const remoteOptions = this.remoteOptionsMap[field.key]
      if (this.isRemoteSelectField(field) && Array.isArray(remoteOptions)) {
        return remoteOptions
      }
      return field.options
    },

    /**
     * 远程搜索下拉选项
     * el-select 在 remote 模式下已内置 300ms 防抖，此处只做请求竞态控制
     * @param {Object} field - 表单字段配置
     * @param {String} query - 搜索关键字
     */
    remoteSelectSearch(field, query) {
      const config = REMOTE_SELECT_FIELDS[field.key]
      if (!config) return

      const keyword = (query || '').trim()
      // 关键字清空时回落到默认选项，避免下拉列表空白
      if (!keyword) {
        this.$set(this.remoteOptionsMap, field.key, null)
        this.$set(this.remoteLoadingMap, field.key, false)
        return
      }

      const requestId = (this.remoteRequestIdMap[field.key] || 0) + 1
      this.remoteRequestIdMap[field.key] = requestId
      this.$set(this.remoteLoadingMap, field.key, true)

      config
        .api({ name: keyword, page: 1, limit: REMOTE_SELECT_LIMIT })
        .then((res) => {
          // 连续输入时旧请求可能后返回，丢弃过期响应
          if (this.remoteRequestIdMap[field.key] !== requestId) return
          this.$set(this.remoteOptionsMap, field.key, res.data || [])
        })
        .catch(() => {
          if (this.remoteRequestIdMap[field.key] !== requestId) return
          this.$set(this.remoteOptionsMap, field.key, [])
        })
        .finally(() => {
          if (this.remoteRequestIdMap[field.key] !== requestId) return
          this.$set(this.remoteLoadingMap, field.key, false)
        })
    },

    /**
     * 下拉框展开状态变化
     * 收起时丢弃远程搜索结果，下次展开回到后端下发的默认选项，避免一直停留在上一次的搜索结果上
     * （el-select 选中后会重置内部 previousQuery，展开时不会再触发一次 remoteMethod('')）
     * @param {Object} field - 表单字段配置
     * @param {Boolean} visible - 下拉框是否展开
     */
    handleSelectVisibleChange(field, visible) {
      if (visible || !this.isRemoteSelectField(field)) return
      if (this.remoteOptionsMap[field.key]) {
        this.$set(this.remoteOptionsMap, field.key, null)
      }
    },

    /**
     * 把远程搜索选中的选项并入字段默认选项
     * 选项列表回落到默认值后（清空关键字、表单重算），已选客户仍能正确回显 label 与只读态文案
     * @param {Object} field - 表单字段配置
     */
    syncRemoteSelectedOption(field) {
      const remoteOptions = this.remoteOptionsMap[field.key]
      if (!this.isRemoteSelectField(field) || !Array.isArray(remoteOptions) || !remoteOptions.length) return

      const selected = this.ruleForm[field.key]
      const selectedValues = (Array.isArray(selected) ? selected : [selected])
        .filter((item) => item !== '' && item !== null && item !== undefined)
        .map(String)
      if (!selectedValues.length) return

      const options = field.options || []
      const appended = remoteOptions.filter(
        (opt) =>
          selectedValues.includes(String(opt.value)) && !options.some((item) => String(item.value) === String(opt.value))
      )
      if (appended.length) {
        this.$set(field, 'options', [...options, ...appended])
      }
    },

    fillLiaisonInfo(clue) {
      /**
       * 如果关联线索是只读模式，则该回调为初始化执行，不需要回填线索名称和手机号
       */
      const clueOptions = this.fieldList.find(field => field.key === "clue_id");
      if (clueOptions?.disabled) return;

      const keys = this.fieldList.map(field => field.key);
      if (keys.includes('liaison_name')) {
        this.$set(this.ruleForm, 'liaison_name', clue.name || '')
      }
      if (keys.includes('liaison_tel')) {
        this.$set(this.ruleForm, 'liaison_tel', clue.phone || '')
      }
    },

    cloneValue(value) {
      if (Array.isArray(value) || (value && typeof value === 'object')) {
        return JSON.parse(JSON.stringify(value))
      }
      return value
    },

    normalizeFieldValue(field, value) {
      if (
        !field ||
        field.type !== 'multiple' ||
        field.input_type !== 'select' ||
        field.options_level > 1 ||
        !Array.isArray(value)
      ) {
        return value
      }

      const shouldFlatten = value.every((item) => Array.isArray(item) && item.length <= 1)
      return shouldFlatten ? value.map((item) => item[0]).filter((item) => item !== undefined) : value
    },

    // 将下拉值的类型对齐到 options 中的同值项，规避 el-select 严格相等匹配不到的问题。
    // 找到同值项时取其规范 value（类型跟随后端 options），找不到则原样保留，不影响远程搜索等部分选项场景。
    alignSelectValueType(value, options) {
      if (value === null || value === undefined || value === '' || !Array.isArray(options)) {
        return value
      }
      const toCanonical = (v) => {
        const matched = options.find((opt) => String(opt.value) === String(v))
        return matched ? matched.value : v
      }
      return Array.isArray(value) ? value.map(toCanonical) : toCanonical(value)
    },

    // 级联字段的类型对齐：把路径里的每一级 value 换成选项树中同值节点的原始类型，路径层级保持不变。
    // 后端会把分类 id 下发成字符串（如 ['18']），而选项树里 value 是数字 18；
    // el-cascader 的 getNodeByValue 逐级用严格相等比对路径，类型对不上就匹配不到节点，表现为选中值不回显。
    alignCascaderValue(value, options, multiple) {
      if (!Array.isArray(options) || !options.length) {
        return value
      }
      if (multiple) {
        return Array.isArray(value) ? value.map((item) => this.alignCascaderPathType(item, options)) : value
      }
      return this.alignCascaderPathType(value, options)
    },

    alignCascaderPathType(value, options) {
      if (!Array.isArray(value)) {
        return this.alignOptionValueType(options, value)
      }
      return value.map((item) => this.alignOptionValueType(options, item))
    },

    // 在选项树中按值查找同值节点并取其原始 value，空值或查无此项时原样返回
    alignOptionValueType(nodes, targetValue) {
      if (targetValue === '' || targetValue === null || targetValue === undefined) {
        return targetValue
      }
      const matched = this.findNodeInTree(nodes, targetValue)
      return matched ? matched.value : targetValue
    },

    findNodeInTree(nodes, targetValue) {
      if (!Array.isArray(nodes)) {
        return null
      }
      const target = String(targetValue)
      for (const node of nodes) {
        if (String(node.value) === target) {
          return node
        }
        const foundNode = this.findNodeInTree(node.children, targetValue)
        if (foundNode) {
          return foundNode
        }
      }
      return null
    },

    isSameValue(source, target) {
      return JSON.stringify(source) === JSON.stringify(target)
    },

    getSubmitData() {
      const data = { ...this.ruleForm }
      delete data.contract_no
      delete data.odds_no
      return data
    },

    resolveOptionValue(value) {
      if (Array.isArray(value)) {
        return value[value.length - 1]
      }
      return value
    },

    getText(options, val) {
      if (Array.isArray(val)) {
        let resultNames = []
        val.forEach((id) => {
          const name = this.findNameInTree(options, this.resolveOptionValue(id))

          if (name) {
            resultNames.push(name)
          }
        })

        return resultNames.join('/')
      } else {
        return this.findNameInTree(options, this.resolveOptionValue(val))
      }
    },

    findNameInTree(nodes, targetId) {
      if (targetId === null || targetId === undefined || targetId === '') {
        return null
      }
      const target = String(targetId)
      for (const node of nodes) {
        if (String(node.value) === target) {
          return node.label
        }
        if (node.children && node.children.length > 0) {
          const foundName = this.findNameInTree(node.children, targetId)
          if (foundName) {
            return foundName
          }
        }
      }
      return null
    },
    // 选择人员回调
    getSelectList(data, val) {
      val.options = data
      this.ruleForm[val.key] = extractArrayIds(data, 'value')
      if (this.viewMode) {
        this.handlePopoverHide(this.ruleForm[val.key])
      }
    },

    // 选中客户标签成功回调
    handleLabelConf(res) {
      let arr = []
      this.labelList = res.data
      if (this.labelList.length > 0) {
        this.$refs.form.clearValidate('label')
      }

      res.data.map((item) => {
        arr.push(item.id)
      })
      this.ruleForm[this.itemData.key] = arr
      if (this.viewMode) {
        this.editKey = this.itemData.key
        this.handlePopoverHide(arr)
      }
    },

    // 删除客户标签
    cardTag(index) {
      this.labelList.splice(index, 1)
      let key = this.getKey('customer_label', 'key')
      let arr = []
      this.labelList.map((item) => {
        arr.push(item.id)
      })
      this.ruleForm[key] = arr
    },

    changeValue(val) {
      this.syncRemoteSelectedOption(val)
      if (val.key === 'oid' || val.key === 'contract_customer' || val.key === 'outbound_warehouse_id') {
        this.$emit('changeValue', val.key, this.ruleForm[val.key])
      }
    },

    // 获取标签数据
    async getLabel(arr) {
      this.labelAllList = []
      let data = {
        page: 1,
        limit: ''
      }
      const res = await clientConfigLabelApi(data)
      res.data.list.map((item) => {
        item.children.map((el) => {
          this.labelAllList.push(el)
        })
      })

      await this.myFilter(this.labelAllList, arr)
    },

    myFilter(arr1, arr2) {
      this.labelList = []
      const objArr = []
      arr1.map((item) => {
        arr2.map((el) => {
          if (item.id == el) {
            objArr.push(item)
          }
        })
      })

      this.$set(this, 'labelList', objArr)
    },

    /**
     * 富文本编辑回调
     * @param {String} val - 富文本内容
     */
    ueditorEdit(formItem, val) {
      this.ruleForm[formItem.key] = val
    },

    /**
     * 获取指定类型的字段key
     * @param {String} row - 字段类型或key
     * @param {String} key - 是否按key查找
     * @returns {String} 字段key
     */
    getKey(row, key) {
      let formKey = ''
      this.formInfo.forEach((item) => {
        item.data.forEach((val) => {
          if (key && val.key === row) {
            formKey = val.key
          }
          if (val.type === row) {
            formKey = val.key
          }
        })
      })
      return formKey
    },

    // 清除点击事件
    clearClick(row) {
      this.removeEvent()
    },

    /**
     * 重置表单
     */
    resetForm() {
      this.$refs.form.resetFields()
      this.labelAllList = []
      this.attachList = []
      this.labelList = []
      this.imageList = []
      this.saveLoading = false
      this.addContractLoading = false
      this.$emit('handleClose')
    },

    /**
     * 提交表单
     */
    handleConfirm() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          this.saveLoading = true
          this.$emit('submitOk', this.getSubmitData())
        }
      })
    },

    /**
     * 添加订单
     */
    addContract() {
      this.$refs.form.validate((valid) => {
        if (valid) {
          this.addContractLoading = true
          this.$emit('addContinueOk', this.getSubmitData())
        }
      })
    }
  }
}
</script>
<style lang="scss">
@import '@/styles/global.scss';
</style>
<style lang="scss" scoped>
/* 表单样式保持不变 */
:deep(.from-item-title) {
  border-left: 3px solid #1890ff;
  margin-bottom: 20px;

  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}

.member-box {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  .avatar {
    width: 16px;
    height: 16px;
    margin-right: 4px;
    border-radius: 50%;
  }
}

.el-tag {
  margin-right: 4px;
}

// 客户标签选择框：与 small 输入框统一 32px 基准高度，标签换行时再自动撑高
.plan-footer-one {
  display: flex;
  align-items: center;
  height: auto;
  min-height: 32px;
  padding: 3px 15px;
  line-height: 22px;

  > div {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
  }

  .el-tag {
    margin-right: 0;
  }

  .placeholder {
    height: 24px;
    line-height: 24px;
  }
}

.h-400 {
  height: 400px;
  overflow-y: auto;
}
.placeholder {
  color: #c0c4cc;
  height: 32px;
  line-height: 32px;
}

.pointer {
  padding: 5px 2em 6px 10px;
  word-break: break-all;
  position: relative;
  line-height: 21px;
}

.readonly-display-field {
  padding: 5px 10px 6px;
  word-break: break-all;
  position: relative;
  line-height: 21px;
  color: #606266;
}

.edit-icon {
  color: #606266;
  font-size: 14px;
  display: none;
  position: absolute;
  top: 8px;
  right: 15px;
}

.pointer:hover {
  width: 100%;
  display: flex;
  align-items: center;
  cursor: pointer;
  background-color: #f7f7f7;
  border-radius: 4px;
  .edit-icon {
    display: inline;
  }
}
.white-space-pre-line {
  white-space: pre-line;
  word-break: break-all;
  vertical-align: top;
}

.readonly-mode-field {
  ::v-deep p {
    img {
      width: 45px !important;
    }
  }
  ::v-deep table {
    width: 100%;
    border: 1px solid #e5e7eb;
    /* 表格字体：继承容器字体，确保一致性 */
    font-size: inherit;
    color: inherit;
  }
  ::v-deep th {
    background-color: #f3f4f6;
    text-align: center;
    min-width: 80px;
    border: 1px solid #e5e7eb;
    font-weight: 500;
  }
  ::v-deep td {
    text-align: left;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    word-wrap: break-word;
    word-break: break-all;
  }
}
.iconbianji3 {
  cursor: pointer;
  font-weight: 400 !important;
  font-size: 13px;
  color: #606266;
  padding: 10px;
  // margin-bottom: 10px;
}

::v-deep .el-radio {
  margin-right: 15px;
  margin-bottom: 0;
}

::v-deep .el-radio-group {
  display: flex;
  line-height: 0 !important;
  vertical-align: 0;
  font-size: 13px;
  margin-left: 10px;
}

.inline-edit-item {
  position: relative;
}
.inline-edit-saving-icon {
  position: absolute;
  top: 9px;
  right: 8px;
  color: #1890ff;
  font-size: 16px;
  z-index: 10;
  pointer-events: none;
}
.is-saving-disabled {
  background-color: #f5f7fa;
  border-color: #e4e7ed;
  color: #c0c4cc;
  cursor: not-allowed;
  pointer-events: none;
}
</style>
