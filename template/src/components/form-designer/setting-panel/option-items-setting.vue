<template>
  <div class="option-items-pane">
    <template v-if="selectedWidget.type === 'radio'">
     
      <el-radio-group v-model="optionModel.defaultValue">
        <draggable
          tag="ul"
          :list="optionModel.customizeItems"
          v-bind="{ group: 'optionsGroup', ghostClass: 'ghost', handle: '.icontuodong' }"
          @change="emitDefaultValueChange"
        >
          <li v-for="(option, idx) in optionModel.customizeItems" :key="idx">
            <el-radio :label="option.value">
              <div class="checkBox">
                <el-input
                  v-model="option.name"
                  size="small"
                  @change="changeCard(option)"
                  style="width: 160px"
                ></el-input>
                <el-color-picker v-model="option.color" class="ml5 mr5 color"></el-color-picker>
                <span class="iconfont icontuodong iconadd" title="拖拽排序"></span>

                <span class="el-icon-delete iconadd" @click.stop="deleteOption(option, idx)"></span>
              </div>
            </el-radio>
          </li>
        </draggable>
      </el-radio-group>
      <el-button type="text" class="mt10" @click="addOption">新增选项</el-button>
    </template>

    <!-- 支持复选框 下方添加 checkbox -->
    <template v-if="selectedWidget.type === 'checkbox'">
      <el-checkbox-group v-model="optionModel.defaultValue">
        <draggable
          tag="ul"
          :list="optionModel.customizeItems"
          v-bind="{ group: 'optionsGroup', ghostClass: 'ghost', handle: '.icontuodong' }"
          @change="emitDefaultValueChange"
        >
          <li v-for="(option, idx) in optionModel.customizeItems" :key="idx">
            <el-checkbox :label="option.value">
              <div class="checkBox">
                <el-input
                  v-model="option.name"
                  size="small"
                  @change="changeCard(option)"
                  style="width: 160px"
                ></el-input>
                <el-color-picker v-model="option.color" class="ml5 mr5 color"></el-color-picker>
                <span class="iconfont icontuodong iconadd" title="拖拽排序"></span>

                <span class="el-icon-delete iconadd" @click.stop="deleteOption(option, idx)"></span>
              </div>
            </el-checkbox>
          </li>
        </draggable>
      </el-checkbox-group>
      <el-button type="text" class="mt10" @click="addOption">新增选项</el-button>
    </template>

  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import Draggable from 'vuedraggable'
import i18n from '@/utils/i18n'
import { delCrudSaveApi, delCrudSortPutApi } from '@/api/develop'
import { getDictDataDeleteApi } from '@/api/form'
import CodeEditor from '@/components/code-editor/index'
import color from '@/views/business/components/formSetting/components/form-create-designer/src/config/rule/color'
export default {
  name: 'OptionItemsSetting',
  mixins: [i18n],
  components: {
    Draggable,
    CodeEditor
  },
  props: {
    designer: Object,
    selectedWidget: Object,
    optionItems:Array
  },
  data() {
    return {
      showImportDialogFlag: false,
      optionLines: '',
      cascaderOptions: '',
      showImportCascaderDialogFlag: false,
      separator: ',',
      optionModel: {},
      color: color
    }
  },
  computed: {},
  watch: {
    'selectedWidget.options': {
      handler(newVal) {
        this.$set(this, 'optionModel', newVal)
      },
      immediate: true, // 初始化时立即执行
      deep: true // 深度监听
    },
  'optionItems':{
    handler(newVal) {
       if(newVal.length>0){
        this.optionModel.customizeItems = newVal
       }
      },
      immediate: true, // 初始化时立即执行
      deep: true // 深度监听
  }
  },
  methods: {
    // 排序
    emitDefaultValueChange() {
      if (!!this.designer && !!this.designer.formWidget) {
        let fieldWidget = this.designer.formWidget.getWidgetRef(this.selectedWidget.options.name)
        if (!!fieldWidget && !!fieldWidget.refreshDefaultValue) {
          let arr = []
          this.optionModel.customizeItems.map((item, index) => {
            let obj = {
              id: item.id,
              sort: index
            }
            arr.push(obj)
          })
          delCrudSortPutApi({ data: arr }).then(() => {
            fieldWidget.refreshDefaultValue()
          })
        }
      }
    },
    async deleteOption(option, index) {
      await modalSure('你确定要删除这条数据吗')
      await getDictDataDeleteApi(option.id)
      this.optionModel.customizeItems.splice(index, 1)
    },

    async changeCard(item) {
      let obj = {
        name: item.name,
        value: item.value, // value值
        crud_id: this.$route.query.id || 0, //实体id
        field_id: this.optionModel.dataDictId||this.optionModel.fieldId, // 字段id
        data_id: item.id,
        color: item.color,
        status: 1
      }
      await delCrudSaveApi(obj)
    },

    addOption() {
      if (this.optionModel.customizeItems) {
        let newValue = this.optionModel.customizeItems.length + 1
        let obj = {
          name: `选项${newValue}`,
          value: newValue, // value值
          crud_id: this.$route.query.id || 0, //实体id
          field_id: this.optionModel.dataDictId||this.optionModel.fieldId, // 字段id
          data_id: 0,
          color: '#1890ff',
          status: 1
        }
        delCrudSaveApi(obj).then((res) => {
          this.$set(this.optionModel, 'customizeItems', [
            ...this.optionModel.customizeItems,
            {
              value: newValue,
              id: res.data.id,
              name: `选项${newValue}`,
              color: '#1890ff',
              status: 1
            }
          ])
        })
      } else {
        this.optionModel.customizeItems = [
          {
            value: 1,
            id: 1,
            name: '选项1',
            color: '#1890ff',
            status: 1
          }
        ]
      }
    },

    importOptions() {
      this.optionLines = ''
      if (this.optionModel.customizeItems.length > 0) {
        this.optionModel.customizeItems.forEach((opt) => {
          if (opt.value === opt.label) {
            this.optionLines += opt.value + '\n'
          } else {
            this.optionLines += opt.value + this.separator + opt.label + '\n'
          }
        })
      }

      this.showImportDialogFlag = true
    },

    saveOptions() {
      let lineArray = this.optionLines.split('\n')

      if (lineArray.length > 0) {
        this.optionModel.customizeItems = []
        lineArray.forEach((optLine) => {
          if (!!optLine && !!optLine.trim()) {
            if (optLine.indexOf(this.separator) !== -1) {
              this.optionModel.customizeItems.push({
                value: optLine.split(this.separator)[0],
                label: optLine.split(this.separator)[1]
              })
            } else {
              this.optionModel.customizeItems.push({
                value: optLine,
                label: optLine
              })
            }
          }
        })
      } else {
        this.optionModel.customizeItems = []
      }

      this.showImportDialogFlag = false
    },

    resetDefault() {
      if (
        this.selectedWidget.type === 'checkbox' ||
        (this.selectedWidget.type === 'select' && this.selectedWidget.options.multiple)
      ) {
        this.optionModel.defaultValue = []
      } else {
        this.optionModel.defaultValue = ''
      }
    },

    importCascaderOptions() {
      this.cascaderOptions = JSON.stringify(this.optionModel.customizeItems, null, '  ')
      this.showImportCascaderDialogFlag = true
    },

    saveCascaderOptions() {
      try {
        let newOptions = JSON.parse(this.cascaderOptions)
        this.optionModel.customizeItems = newOptions
        //TODO: 是否需要重置选项默认值？？

        this.showImportCascaderDialogFlag = false
      } catch (ex) {
        this.$message.error(this.i18nt('designer.hint.invalidOptionsData') + ex.message)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.option-items-pane ul {
  padding-inline-start: 6px;
  padding-left: 6px; /* 重置IE11默认样式 */
  li {
    margin-bottom: 6px;
  }
}
::v-deep .el-radio {
  margin-right: 5px;
  display: flex;
  height: 32px;
  align-items: center;
}

::v-deep .el-color-picker__trigger {
  display: flex;
}

.checkBox {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.icontuodong {
  margin-right: 6px;
}

.icontuodong li.ghost {
  background: #fff;
  border: 2px dotted #409eff;
}

.drag-option {
  cursor: move;
}
.iconadd {
  // display: inline-block;
  width: 15px;

  color: #909399;
  font-size: 12px;
}

.small-padding-dialog ::v-deep .el-dialog__body {
  padding: 10px 15px;
}

.dialog-footer .el-button {
  width: 100px;
}
</style>
@/utils/i18ns
