<template>
  <div :class="{ 'flex-layout-table': flexLayout }">
    <div class="mt10 table-box" :style="selectionCountStyle">
      <div class="table-wrapper">
        <div class="table-content">
          <el-table
            key="tab"
            ref="table"
            :data="tableData"
            v-loading="loading"
            default-expand-all
            :height="heightData"
            row-key="id"
            style="width: 100%"
            @selection-change="handleSelectionChange"
            border
          >
            <el-table-column width="55" v-if="isChecked" show-overflow-tooltip type="selection"> </el-table-column>
            <el-table-column
              v-for="header in tableHeaders"
              :key="header.field"
              :label="header.name"
              :min-width="getWidth(header)"
              :prop="header.field"
              show-overflow-tooltip
            >
              <template #header="scope">
                <div class="header-content">
                  <span>{{ header.name }}</span>

                  <!-- 拖拽图标（使用 el-icon-drag） -->
                  <i class="iconfont icontuozhuaitubiao"></i>
                </div>
              </template>
              <template #default="scope">
                <!-- 业务员 -->
                <div
                  style="display: flex; align-items: center; gap: 6px"
                  v-if="fieldHandle(header.field, 'salesman')"
                  class="flex"
                >
                  <el-avatar :size="24" :src="scope.row[header.field].avatar"></el-avatar>
                  <span>{{ scope.row[header.field].name || '--' }}</span>
                </div>
                <!-- 产品 -->
                <div v-else-if="header.field === 'products'">
                  <div
                    v-for="item in scope.row[header.field]"
                    :key="item.sku_id"
                    style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px"
                  >
                    <el-avatar shape="square" size="medium" :src="item.image"></el-avatar>
                    <span>{{ item.product_name || '--' }} | {{ item.price }} x {{ item.count }}</span>
                  </div>
                </div>
                <!-- 标签 -->
                <div
                  v-else-if="fieldHandle(header.field, 'labelList')"
                  class="customer-label"
                  :class="{ pointer: !labelClickable && scope.row[header.field] && scope.row[header.field].length }"
                >
                  <div
                    v-if="!scope.row[header.field].length"
                    :class="{ 'label-hit': labelClickable }"
                    @click="handleLabelClick(scope.row)"
                  >
                    --
                  </div>
                  <!-- 大于两条浮窗 -->
                  <el-popover
                    v-if="scope.row[header.field].length > 2"
                    placement="top-start"
                    width="400"
                    trigger="hover"
                  >
                    <template>
                      <div class="flex_box">
                        <div v-for="(item, index) in scope.row[header.field]" :key="index" class="tips">
                          <el-tag
                            v-if="item.name.length <= 6"
                            size="small"
                            class="mb10"
                            :class="{ 'label-hit': labelClickable }"
                            @click.native="handleLabelClick(scope.row)"
                          >
                            {{ item.name }}
                          </el-tag>
                          <el-tag
                            v-else
                            size="small"
                            class="mb10"
                            :class="{ 'label-hit': labelClickable }"
                            @click.native="handleLabelClick(scope.row)"
                          >
                            {{ item.name }}
                          </el-tag>
                        </div>
                      </div>
                    </template>
                    <template #reference>
                      <div>
                        <div class="flex_box">
                          <template v-for="(item, index) in scope.row[header.field]">
                            <el-tag
                              v-if="index < 2"
                              size="small"
                              :key="index"
                              class="tips"
                              :class="{ 'label-hit': labelClickable }"
                              @click.native="handleLabelClick(scope.row)"
                            >
                              {{ item.name }}
                            </el-tag>
                          </template>
                          <el-tag
                            v-if="scope.row[header.field].length > 2"
                            size="small"
                            :class="{ 'label-hit': labelClickable }"
                            @click.native="handleLabelClick(scope.row)"
                          >
                            ...
                          </el-tag>
                        </div>
                      </div>
                    </template>
                  </el-popover>
                  <!-- 不需要浮窗 -->
                  <template v-else>
                    <div class="flex_box">
                      <div v-for="(item, index) in scope.row[header.field]" :key="index" class="tips">
                        <el-tag
                          v-if="index < 2"
                          size="small"
                          :class="{ 'label-hit': labelClickable }"
                          @click.native="handleLabelClick(scope.row)"
                        >
                          {{ item.name }}
                        </el-tag>
                      </div>
                      <el-tag v-if="scope.row[header.field].length > 2" size="small">...</el-tag>
                    </div>
                  </template>
                </div>
                <span
                  v-else-if="
                    (keyword == 'customer' || keyword == 'customer_seas') && fieldHandle(header.field, 'liaisonList')
                  "
                >
                  {{ scope.row[header.field].liaison_name || '--' }}:
                  {{ scope.row[header.field].liaison_tel || '--' }}
                </span>
                <!-- 查看详情 -->
                <span
                  v-else-if="fieldHandle(header.field, 'contractList')"
                  @click="handleCheck(scope.row)"
                  class="point over-text"
                >
                  {{ scope.row[header.field] }}
                </span>
                <!-- 客户详情 -->
                <span v-else-if="fieldHandle(header.field, 'customerList')">
                  <span
                    :class="!scope.row[header.field] || 'point'"
                    @click="scope.row[header.field] && handleCustomerCheck(scope.row)"
                    >{{ scope.row[header.field] || '--' }}</span
                  >
                </span>
                <!-- 订单付款单号特殊处理 -->
                <span v-else-if="header.field === 'bill_no'">
                  <template v-if="scope.row[header.field] && scope.row[header.field].length > 0">
                    <span v-for="(item, index) in scope.row[header.field]" :key="index">
                      {{ item.bill_no }}<template v-if="index !== scope.row[header.field].length - 1"> /</template>
                    </span>
                  </template>
                  <span v-else>--</span>
                </span>
                <span v-else-if="header.field === 'work_customer'" class="flex">
                  <template v-if="scope.row[header.field] && scope.row[header.field].avatar">
                    <img
                      v-default-avatar="scope.row[header.field]"
                      :src="getAvatarSrc(scope.row[header.field])"
                      alt=""
                      style="width: 24px; height: 24px; border-radius: 50%; margin-right: 7px; vertical-align: bottom"
                    />
                    {{ scope.row[header.field].name }}

                    <span>
                      <span v-if="scope.row[header.field].type == 1" class="color-excel ml4">@微信</span>
                      <span
                        v-if="scope.row[header.field].type == 2 && scope.row[header.field].corp_name"
                        class="color-ppt ml4"
                        >{{ scope.row[header.field].corp_name }}</span
                      >
                    </span>
                  </template>
                  <span v-else>--</span>
                </span>

                <div v-else-if="header.input_type == 'member' && header.field != 'work_customer'">
                  <template v-if="scope.row[header.field] && scope.row[header.field].length > 0">
                    <div v-if="adminIndex == scope.$index" class="point" @click.stop="showAdmins(scope.$index)">
                      <!-- 选择成员 -->
                      <select-member
                        ref="childComponent"
                        :value="scope.row[header.field]"
                        :is-avatar="true"
                        :isSearch="true"
                        :onlyOne="scope.row[header.field].type === 'singleMember'"
                        @handlePopoverHide="getSelectList($event, scope.row[header.field])"
                        style="width: 100%"
                      >
                      </select-member>
                    </div>
                    <el-tooltip v-show="adminIndex !== scope.$index" class="item" effect="dark" placement="top">
                      <template #content>
                        <span>
                          <div class="flex">
                            <span v-for="item in scope.row[header.field]" class="flex co-member-item">
                              <img v-default-avatar="item" :src="getAvatarSrc(item)" alt="" class="avatar" />
                              <span> {{ item.name }}</span>
                            </span>
                          </div>
                        </span>
                      </template>
                      <div
                        @click.stop="showAdmins(scope.$index)"
                        class="flex"
                        :style="{ alignItems: scope.row[header.field].length == 1 ? 'center' : 'flex-start' }"
                      >
                        <template v-for="(item, index) in scope.row[header.field]">
                          <span v-if="index < 4" :key="index" class="flex" style="cursor: pointer; align-items: center">
                            <img v-default-avatar="item" :src="getAvatarSrc(item)" alt="" class="avatar" />
                            <span v-show="scope.row[header.field].length == 1"> {{ item.name }}</span>
                          </span>
                        </template>
                      </div>
                    </el-tooltip>
                  </template>
                  <span v-else>--</span>
                </div>
                <!-- 关注 -->
                <div v-else-if="fieldHandle(header.field, 'followedList')" class="icon-star">
                  <i
                    :class="
                      !scope.row[header.field] ? 'el-icon-star-off' : 'el-icon-star-on color-collect icon-star-on'
                    "
                    class="pointer"
                    @click="focusEvt(scope.row)"
                  ></i>
                </div>
                <!-- 关联字典字段展示 -->
                <div
                  v-else-if="
                    scope.row[header.field] && Object.prototype.hasOwnProperty.call(scope.row[header.field], 'color')
                  "
                  class="dictionaries-tag over-text"
                  :style="{
                    color: scope.row[header.field].color || '#1890ff',
                    background: scope.row[header.field].color
                      ? getColorFn(scope.row[header.field].color, '0.1')
                      : getColorFn('#1890ff', '0.1')
                  }"
                >
                  {{ scope.row[header.field].name }}
                </div>
                <span
                  v-else-if="header.field === 'payment_status'"
                  class="over-text2"
                  :class="{ success: scope.row[header.field] === 1, waiting: scope.row[header.field] === 0 }"
                >
                  {{ scope.row[header.field] === 1 ? '已结清' : '未结清' }}
                </span>
                <span v-else class="over-text2">
                  {{ getValue(scope.row[header.field], header) }}
                </span>
              </template>
            </el-table-column>
            <el-table-column fixed="right" label="操作" width="180" prop="address">
              <template #header>
                操作
                <!-- <i class="el-icon-setting pointer" @click="customSearchEvt"></i> -->
              </template>
              <template #default="scope">
                <slot :data="scope.row" name="options"></slot>
              </template>
            </el-table-column>
            <template #empty>
              <div class="empty-text">暂无数据</div>
            </template>
          </el-table>
        </div>
      </div>
      <div class="page-fixed">
        <el-pagination
          :current-page="where.page"
          :page-size="where.limit"
          :page-sizes="[15, 20, 30]"
          :total="total"
          layout="total, sizes,prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="pageChange"
        />
      </div>
    </div>
    <visible-dialog
      v-if="tableItemDialogVisible"
      :default-table-item-list="defaultTableItemList"
      :table-item-dialog-visible="tableItemDialogVisible"
      :transfer-data-list="transferDataList"
      :transfer-props="{ key: 'field', label: 'name' }"
      :visible-table-item-list="visibleTableItemList"
      @handleCloseTableItem="handleCloseTableItem"
      @handleConfirmVisible="handleConfirmVisible"
    ></visible-dialog>
  </div>
</template>
<script>
import { getAvatarSrc } from '@/utils/avatar'
import { getStorageJson } from '@/utils/storage'
import { getColor } from '@/utils/format'
import { customerSubscribeApi, contractSubscribeApi } from '@/api/enterprise'
import {
  salesmanCustomApi,
  saveSalesmanCustomApi,
  oddsSubscribeApi,
  cluesSubscribeApi,
  clientMemberApi
} from '@/api/client'
export default {
  name: 'customTable',
  components: {
    selectMember: () => import('@/components/form-common/select-member'),
    VisibleDialog: () => import('@/components/form-common/dialog-transfer')
  },
  props: {
    // 是否启用 flex 布局，启用后表格高度将适应父容器高度，无需传入 formBoxHeight 高度
    flexLayout: {
      type: Boolean,
      default: false
    },
    tableData: {
      type: Array,
      default: () => {
        return []
      }
    },
    keyword: {
      type: String,
      default: () => {
        return ''
      }
    },
    // @deprecated 请改用 flexLayout，由父容器 flex 布局接管表格高度。所有调用方迁移完成后将移除该 prop，具体使用可参考线索列表页，需要给 el-card.normal-page 增加 el-card-flex 类
    formBoxHeight: {
      type: Number,
      default: 0
    },
    loading: {
      type: Boolean,
      default: false
    },
    total: {
      type: Number,
      default: 0
    },
    isChecked: {
      type: Boolean,
      default: true
    },
    where: {
      type: Object,
      default: () => {
        return {}
      }
    },
    selectedCount: {
      type: Number,
      default: 0
    },
    // 客户标签列是否可点击，开启后点击标签会抛出 handleLabelClick，由调用方决定后续行为
    labelClickable: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      search: [],
      configType: 1,
      viewSearch: [],
      adminIndex: null,
      merberIds: [],

      fieldMap: {
        salesman: ['salesman', 'creator', 'before_salesman'],
        followedList: ['customer_followed', 'contract_followed', 'followed'],
        customerList: ['customer_name', 'contract_customer'],
        contractList: ['name', 'contract_name'],
        liaisonList: ['liaison_tel'],
        labelList: ['customer_label']
      },
      defaultTableItemList: [],
      transferDataList: [],
      // 客户关注、商机关注、订单关注
      visibleTableItemList: [],
      getSalesmanCustom: {},
      tableHeaders: [], // 表格的表头
      srcList: [],
      inputRefs: {},
      userId: getStorageJson('userInfo', {}).id,
      tableItemDialogVisible: false,
      tableLayoutTimer: null
    }
  },
  computed: {
    heightData() {
      if (this.flexLayout) {
        return '100%'
      }
      const safeHeight = Math.max(Number(this.formBoxHeight) || 0, 0)
      return `calc(100vh - 279px - ${safeHeight}px)`
    },
    selectionCountStyle() {
      return {
        '--selection-count': this.selectedCount > 0 ? `'${this.selectedCount}'` : 'none'
      }
    }
  },
  watch: {
    tableData: {
      handler(newVal) {
        this.scheduleTableLayout()
      },
      immediate: true
    },
    tableHeaders() {
      this.scheduleTableLayout()
    },
    heightData() {
      this.scheduleTableLayout()
    }
  },
  mounted() {
    this.salesmanCustom()
    this.scheduleTableLayout()
  },

  beforeDestroy() {
    if (this.tableLayoutTimer) {
      clearTimeout(this.tableLayoutTimer)
    }
  },

  methods: {
    getAvatarSrc,
    scheduleTableLayout() {
      if (this.tableLayoutTimer) {
        clearTimeout(this.tableLayoutTimer)
      }
      this.tableLayoutTimer = setTimeout(() => {
        this.tableLayoutTimer = null
        this.$nextTick(() => {
          if (this.$refs.table?.doLayout) {
            this.$refs.table.doLayout()
          }
        })
      }, 100)
    },
    fieldHandle(field, type) {
      return this.fieldMap[type].includes(field)
    },
    setInputRef(el, rowId, field) {
      if (el) {
        if (!this.inputRefs[rowId]) {
          this.$set(this.inputRefs, rowId, {})
        }
        this.$set(this.inputRefs[rowId], field, el)
      }
    },

    // 查看详情
    handleCheck(data) {
      this.$emit('handleCheck', data)
    },
    // 查看客户详情
    handleCustomerCheck(data) {
      this.$emit('handleCustomerCheck', data)
    },
    handleSelectionChange(val) {
      this.checkedId = val.map((item) => item.id)
      this.ids = val
      this.$emit('handleSelectionChange', this.ids, this.checkedId)
    },
    getColorFn(color, opacity) {
      return getColor(color, opacity)
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.where.page = 1
      this.$emit('getTableData')
    },
    pageChange(page) {
      this.where.page = page
      this.$emit('getTableData')
    },
    async focusEvt(item) {
      let status = item.followed ? 0 : 1
      if (this.keyword == 'odds') {
        await oddsSubscribeApi(item.id, status, { status: status })
        item.followed = status
      } else if (this.keyword == 'clue' || this.keyword == 'clue_seas') {
        await cluesSubscribeApi(item.id, status, { status: status })
        item.followed = status
      } else if (this.keyword === 'customer') {
        status = item.customer_followed ? 0 : 1
        await customerSubscribeApi(item.id, status, { status })
        item.customer_followed = status
      } else {
        await contractSubscribeApi(item.id, status, { status: status })
        item.contract_followed = status
      }
    },
    // 数组转成字符串
    getValue(val, header) {
      let str = ''
      if (val == '') {
        str = '--'
      } else if (header.field === 'creator') {
        str = val.name
      } else if (Array.isArray(val)) {
        str = val.join('/')
      } else {
        str = val
      }
      return str || '--'
    },
    salesmanCustom() {
      salesmanCustomApi(this.keyword).then((res) => {
        res.data.search = res.data.search.filter((item) => {
          return item.field != 'clue_id'
        })

        const {
          search_select: searchSelectList,
          search: searchList,
          list,
          list_select,
          sort_field,
          sort_value
        } = res.data
        const search = []
        const viewSearch = []

        // 先处理searchList的字段转换（只遍历一次）
        const processedSearchList = searchList.map((item) => {
          if (item.input_type === 'date') {
            item.input_type = 'date_picker'
          } else if (item.field === 'customer_label') {
            item.input_type = 'tag'
          } else if (item.input_type === 'checked') {
            item.input_type = 'select'
          } else if (item.input_type === 'select') {
            if (item.field === 'area_cascade') {
              item.input_type = 'cascader_address'
            } else if (item.type === 'multiple') {
              item.input_type = 'cascader'
            } else if (item.field === 'contract_category') {
              item.input_type = 'cascader'
              item.emitPath = false
            } else if (!item.input_type) {
              item.input_type = 'cascader'
            } else if (item.type === 'single') {
              const isCascader = item.dict?.some((i) => i.children?.length > 0)
              if (isCascader) {
                item.input_type = 'cascader_radio'
                item.multiple = false
                item.emitPath = false
                item.checkStrictly = true
              }
            }
          }

          // 处理字典映射
          if (item.dict) {
            this.mapDict(item.dict)
          }

          return {
            ...item,
            form_value: item.input_type,
            field_name_en: item.field,
            field_name: item.name,
            title: item.name,
            options: item.dict,
            data_dict: item.dict,
            type: item.input_type,
            is_city_show: ''
          }
        })

        searchSelectList.forEach((selectField) => {
          const matchedItem = processedSearchList.find((item) => item.field === selectField)
          if (matchedItem) {
            search.push(matchedItem)
          }
        })

        processedSearchList.forEach((item) => {
          if (!searchSelectList.includes(item.field)) {
            viewSearch.push(item)
          }
        })

        const fieldMap = list.reduce((map, item) => {
          map[item.field] = item
          return map
        }, {})

        this.transferDataList = list
        this.defaultTableItemList = list_select
        this.tableHeaders = list_select.map((field) => fieldMap[field]).filter(Boolean)
        this.getSalesmanCustom = res.data
        this.getSalesmanCustom.search

        // 赋值与触发事件
        this.search = search
        this.viewSearch = viewSearch
        this.$emit('getSearch', {
          search,
          viewSearch,
          timeSearchObj: { sort_field, sort_value },
          tableHeaders: this.tableHeaders
        })
      })
    },
    // 点击客户标签列的标签（或无标签时的占位符），仅在调用方开启 labelClickable 时抛出
    handleLabelClick(row) {
      if (!this.labelClickable) return
      this.$emit('handleLabelClick', row)
    },
    // 客户标签宽度
    getWidth(header) {
      if (header.field === 'customer_label' || header.field === 'products') {
        return '270'
      } else if (header.input_type === 'member' && header.field !== 'work_customer') {
        return '200'
      } else {
        return '150'
      }
    },
    mapDict(dict) {
      for (let i = 0; i < dict.length; i++) {
        dict[i].name = dict[i].label
        if (dict[i].children) {
          this.mapDict(dict[i].children)
        }
      }
    },

    triggerUpload() {
      this.$refs.upload.$el.querySelector('input[type="file"]').click()
    },

    // 显示编辑负责人
    showAdmins(index) {
      if (this.keyword !== 'customer') {
        return false
      }
      this.adminIndex = index
    },

    // 隐藏成员选择器
    hideMembersPicker() {
      if (this.adminIndex) {
        clientMemberApi(this.tableData[this.adminIndex].id, { data: this.merberIds }).then((res) => {
          this.adminIndex = null
          this.$emit('getTableData')
        })
      }
    },

    getSelectList(data, item) {
      let arr = []
      this.merberIds = []
      data.map((item) => {
        arr.push(item.value)
      })
      item.map((el) => {
        this.merberIds.push(el.id)
      })
      if (!this.isArrayEqual(this.merberIds, arr)) {
        this.merberIds = arr
        this.hideMembersPicker()
      } else {
        this.adminIndex = null
      }
    },

    isArrayEqual(arr1, arr2) {
      if (arr1.length !== arr2.length) return false
      return arr1.every((value, index) => value === arr2[index])
    },
    // 关闭显示列弹框
    handleCloseTableItem() {
      this.tableItemDialogVisible = false
    },
    // 自定义设置表头
    customSearchEvt(type) {
      this.configType = type
      // 1:自定义筛选条件 2:表头设置
      if (type === 1) {
        this.defaultTableItemList = this.getSalesmanCustom.search_select
        this.transferDataList = this.getSalesmanCustom.search
      } else {
        this.defaultTableItemList = this.getSalesmanCustom.list_select
        this.transferDataList = this.getSalesmanCustom.list
      }
      this.tableItemDialogVisible = true
    },
    handleConfirmVisible(array) {
      let data = {
        select_type: this.configType == 1 ? 'search_select' : 'list_select',
        data: array
      }
      saveSalesmanCustomApi(this.keyword, data).then((res) => {
        this.tableItemDialogVisible = false
        this.tableHeaders = []
        this.salesmanCustom()
        this.$emit('getTableData')
      })
    }
  }
}
</script>
<style scoped lang="scss">
::v-deep .co-member-item {
  flex-flow: column;

  & + .co-member-item {
    margin-left: 4px;
  }

  .avatar + span {
    margin-top: 4px;
  }
}

.icon-star {
  i {
    font-size: 18px;
  }

  .icon-star-on {
    font-size: 24px;
    margin-left: -3px;
  }
}
.flex_box {
  display: flex;
  flex-wrap: wrap;
  // gap: 4px;
}
.tips {
  margin-right: 4px;
}
.dictionaries-tag {
  max-width: 100px;
  display: inline-block;
  margin: 0;
  box-sizing: border-box;
  height: 24px;
  padding: 0 8px;
  text-align: center;
  font-size: 12px;
  margin-top: 8px;
  border-radius: 3px;
}
.avatar {
  margin-left: 4px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 4px;
  vertical-align: center;
}
.point {
  cursor: pointer;
  color: #1890ff;
}
::v-deep .el-table__empty-block {
  width: 100% !important; /* 让空状态容器宽度占满表格内容区 */
}

.empty-text {
  text-align: center;
  padding: 40px 0; /* 增加上下间距，使视觉效果更好 */
  color: #606266; /* 保持与Element默认文本颜色一致 */
}
.customer-label .el-tag {
  border: 0;
}
/* 客户标签列开启点击时，仅标签与无标签占位符本身响应点击，避免单元格空白处误触 */
.label-hit {
  display: inline-block;
  cursor: pointer;
}
.header-content {
  width: 100%;
  position: relative;

  .icontuozhuaitubiao {
    // position: absolute;
    // right: 0;
    font-weight: 400;
    font-size: 18px;
    display: none;
    color: #cccccc;
  }
}
.header-content:hover .icontuozhuaitubiao {
  position: absolute;
  right: -5px;
  font-weight: 400;
  font-size: 18px;
  display: inline-block;
}
.table-box {
  ::v-deep .el-table__column-resize-proxy {
    margin-top: 46px;
    border-left-color: #6fbaff;
  }

  ::v-deep .el-table--border {
    border: none;
    .el-table__cell {
      border-right: none;
    }
  }
  ::v-deep .el-table th.el-table__cell > .cell {
    padding-right: 0px;
  }

  ::v-deep .el-table__fixed-right .el-table__fixed-body-wrapper {
    border-right: 1px solid #fff;
  }

  ::v-deep .el-loading-mask {
    z-index: 800;
  }
}

.over-text2 {
  &.success {
    color: #19be6b;
  }

  &.waiting {
    color: #ff9d00;
  }
}
.upload-demo {
  display: none;
}

::v-deep .el-table__header .el-table-column--selection .cell {
  position: relative;
  overflow: visible;
}

::v-deep .el-table__header .el-table-column--selection .cell::after {
  content: var(--selection-count);
  position: absolute;
  top: -8px;
  right: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 16px;
  height: 16px;
  padding: 0 3px;
  font-size: 11px;
  line-height: 16px;
  color: #fff;
  background-color: #1890ff;
  border-radius: 8px;
  pointer-events: none;
}
</style>
