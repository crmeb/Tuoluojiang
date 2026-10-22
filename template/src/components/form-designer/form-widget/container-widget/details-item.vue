<template>
  <!-- 明细表详情 -->
  <container-item-wrapper :widget="widget">
    <el-row
      :key="widget.id"
      class="card-container"
      :class="[!!widget.options.folded ? 'folded' : '', customClass]"
      :shadow="widget.options.shadow"
      :style="{
        width: widget.options.cardWidth + '!important' || '',
        marginTop: widget.options.topMargin + 20 + 'px !important' || '',
        marginBottom: widget.options.bottomMargin + 'px !important' || ''
      }"
      :ref="widget.id"
      v-show="!widget.options.hidden"
    >
      <div class="title mb10">
        明细表
        <span v-if="previewState && !isShow" class="iconfont iconbianji3 ml10" @click="isShow = true"></span>
      </div>
      <el-table :data="tableData" :key="refreshKey" size="mini" border style="width: 100%; margin-top: 10px">
        <!-- 操作列 -->
        <el-table-column label="操作" width="180" fixed="left" align="center">
          <template #default="scope">
            <template v-if="(previewState && isShow) || !previewState">
              <span title="新增" class="el-icon-circle-plus-outline" @click.stop="handleAdd(scope.$index)"></span>
              <span
                class="el-icon-delete"
                v-if="tableData.length > 1"
                title="删除"
                @click.stop="handleDelete(scope.$index)"
              ></span>
            </template>
            <!-- 行索引 -->
            <span class="row-index">#{{ scope.$index + 1 }}</span>
          </template>
        </el-table-column>

        <!-- 动态生成的列（根据 widget.widgetList） -->
        <template v-for="(subWidget, swIdx) in widget.widgetList">
          <el-table-column min-width="200px" :align="subWidget.options.labelAlign || 'left'">
            <template #header>
              <span class="required" v-if="subWidget.options.required">*</span>
              {{ subWidget.options.label }}
            </template>
            <template #default="scope">
              <el-form
                label-position="top"
                :validate-on-rule-change="true"
                :model="scope.row"
                :ref="`form_${scope.$index}_${swIdx}`"
                @submit.native.prevent
              >
                <component
                  :ref="`item_${scope.$index}`"
                  :is="getWidgetName(subWidget)"
                  :field="subWidget"
                  :form-model="scope.row"
                  :table-data="scope.row"
                  :designer="null"
                  :key="scope.$index"
                  :parent-list="widget.widgetList"
                  :parent-widget="{ type: 'details' }"
                  :index-of-parent-list="scope.$index"
                  @valueChange="(val) => (scope.row[subWidget.options.name] = val)"
                >
                </component>
              </el-form>
            </template>
          </el-table-column>
        </template>
      </el-table>
      <div class="mt14" v-if="previewState && isShow">
        <el-button size="small" @click="isShow = false">取消</el-button>
        <el-button size="small" type="primary" @click="handlDetailsHideFn">保存</el-button>
      </div>
    </el-row>
  </container-item-wrapper>
</template>

<script>
import { putUpdateFieldApi } from '@/api/develop'
import emitter from '@/utils/emitter'
import i18n from '@/utils/i18n'
import refMixin from '@/components/form-render/refMixin'
import ContainerItemWrapper from '@/components/form-render/container-item/container-item-wrapper'
import containerItemMixin from '@/components/form-render/container-item/containerItemMixin'
import FieldComponents from '@/components/form-designer/form-widget/field-widget/index'

export default {
  name: 'details-item',
  componentName: 'ContainerItem',
  mixins: [emitter, i18n, refMixin, containerItemMixin],
  components: {
    ContainerItemWrapper,
    ...FieldComponents
  },
  props: {
    widget: Object,
    formData: Object,
    previewState: {
      type: Boolean,
      default: false
    }
  },
  inject: ['refList', 'sfRefList', 'globalModel'],
  provide() {
    return {
      getReadMode: () => this.previewState && !this.isShow
    }
  },
  data() {
    return {
      tableData: [{}],
      tableKey: '',
      refreshKey: 1,
      rowForm: {},
      isShow: false
    }
  },
  computed: {
    customClass() {
      return this.widget.options.customClass || ''
    }
  },

  created() {
    this.initRefList()
    this.tableKey = this.widget.table_name_en + '_' + this.widget.children_table_name_en

    if (this.formData[this.tableKey] && this.formData[this.tableKey].length > 0) {
      this.formModel[this.tableKey] = this.formData[this.tableKey]
      this.tableData.length = 0 // 清空旧数据
      this.tableData.push(...this.formData[this.tableKey])
    } else {
      this.initTableData()
    }
  },
  beforeDestroy() {
    this.unregisterFromRefList()
  },
  watch: {
    '$store.state.user.activeField': {
      deep: true,
      handler: function (newVal, oldVal) {
        this.fieldShowFn(newVal)
      }
    }
  },
  methods: {
    fieldShowFn(val) {
      this.widget.widgetList.forEach((item) => {
        this.$set(item, 'isShow', item.id == val.id)
      })
    },

    initTableData() {
      this.tableData = [this.createEmptyRow()] // 至少保留一行
      this.formModel[this.tableKey] = this.tableData
    },

    // 创建空行（根据widgetList定义所有字段）
    createEmptyRow() {
      const emptyRow = {}
      this.widget.widgetList.forEach((subWidget) => {
        // 初始化字段默认值，确保响应式
        emptyRow[subWidget.options.name] = subWidget.options.defaultValue || ''
      })
      return emptyRow
    },
    getWidgetName(widget) {
      return widget.type + '-widget'
    },

    handleAdd(index) {
      this.tableData.splice(index + 1, 0, this.createEmptyRow())
    },
    handleDelete(index) {
      if (this.tableData.length <= 1) {
        this.$message.warning('至少保留一行数据')
        return
      }

      this.tableData.splice(index, 1)
      this.refreshKey++
    },

    // 编辑保存
    async handlDetailsHideFn(val) {
      const validatePromises = []

      // 遍历所有行
      this.tableData.forEach((row, rowIndex) => {
        this.widget.widgetList.forEach((subWidget, colIndex) => {
          const formKey = `form_${rowIndex}_${colIndex}`
          const formRef = this.$refs[formKey]?.[0]
          if (formRef) {
            const promise = new Promise((resolve) => {
              formRef.validate((valid) => {
                resolve({
                  rowIndex,
                  colIndex,
                  fieldName: subWidget.options.name,
                  valid
                })
              })
            })
            validatePromises.push(promise)
          }
        })
      })

      const results = await Promise.all(validatePromises)

      const allPassed = results.every((item) => item.valid)

      if (allPassed) {
        if (val != 1) {
          putUpdateFieldApi(this.widget.table_name_en, this.formData.id, {
            field_name: this.tableKey,
            value: this.tableData
          }).then((res) => {
            this.isShow = false
          })
        }

        return true
      } else {
        const errorFields = results.filter((item) => !item.valid)
        this.$message.error(`明细表中有必填项，请补充完整！`)
        return false
      }
    },

    toggleCard() {
      this.widget.options.folded = !this.widget.options.folded
    }
  }
}
</script>

<style lang="scss" scoped>
::v-deep .el-form-item__content {
  flex-shrink: 0;
  margin: 0 !important; /* 清除默认左外边距（关键） */
  padding: 0 !important;
}
.title {
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
  font-size: 13px;
  text-align: left;
}
::v-deep .el-row {
  padding: 0;
}
::v-deep .el-form-item {
  margin-bottom: 0 !important;
}
::v-deep .el-form-item__label {
  display: none;
}
.el-icon-delete {
  cursor: pointer;
  font-size: 16px;
  margin-right: 8px;
  color: #909399;
  font-weight: 500;
}
.el-icon-circle-plus-outline {
  cursor: pointer;
  font-size: 16px;
  margin-right: 8px;
  color: #909399;
  font-weight: 500;
}

.float-right {
  float: right;
}
.required {
  color: red;
}
</style>
