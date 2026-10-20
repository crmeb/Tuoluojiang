<template>
  <el-dialog
    title="编辑标签组"
    v-model="visible"
    width="530px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <!-- 标签组名称输入 -->
    <el-form ref="form" :model="form" label-width="100px" label-position="top" class="mt10">
      <el-form-item label="标签组名称">
        <el-input v-model="form.group.name" placeholder="请输入标签组名称" maxlength="50"></el-input>
      </el-form-item>

      <el-form-item label="标签组类型">
        <el-radio-group v-model="form.group.group_type">
          <el-radio v-for="item in LABEL_GROUP_TYPE_OPTIONS" :key="item.value" :label="item.value" :value="item.value">
            {{ item.label }}
          </el-radio>
        </el-radio-group>
      </el-form-item>

      <!-- 标签列表 -->
      <el-form-item label="标签">
        <!-- 可拖拽列表 -->
        <div ref="scrollTarget" class="content">
          <draggable
            tag="div"
            :list="form.label"
            v-bind="{ group: 'optionsGroup', ghostClass: 'ghost', handle: '.icontuodong' }"
            @change="emitDefaultValueChange"
            :item-key="$draggableKey"
          >
            <!-- <div class="tag-list"> -->
            <!-- 标签项 -->
            <template #item="{ element: tag, index }">
              <div class="tag-item">
                <el-input
                  :ref="`input_${index}`"
                  v-model="tag.name"
                  placeholder="请输入标签内容"
                  maxlength="50"
                ></el-input>
                <div class="tag-actions">
                  <span class="iconfont icontuodong" title="拖拽排序"></span>
                  <span class="el-icon-delete" title="删除" @click.stop="handleDeleteTag(tag, index)"></span>
                </div>
              </div>
            </template>
            <!-- </div> -->
          </draggable>
        </div>
        <!-- 添加标签按钮 -->
        <el-button type="primary" link class="add-tag-btn" @click="handleAddTag">
          <i class="el-icon-plus"></i> 添加标签
        </el-button>
      </el-form-item>
    </el-form>

    <!-- 底部按钮 -->
    <template #footer>
      <div class="flex flex-between">
        <el-button type="primary" link class="delete-group-btn" @click="handleDeleteGroup"> 删除标签组 </el-button>
        <div>
          <el-button @click="visible = false">取消</el-button>
          <el-button type="primary" @click="handleConfirm">确定</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import Draggable from 'vuedraggable'
import { clientSaveLabelsApi } from '@/api/client.ts'
import { clientConfigLabelDeleteApi } from '@/api/enterprise.ts'
import { LABEL_GROUP_TYPE, LABEL_GROUP_TYPE_OPTIONS } from '@/constants/labelGroupType.ts'
export default {
  emits: ['openLable', 'getTableData'],
  name: 'TagGroupEditor',
  components: {
    Draggable
  },
  props: {
    // 初始数据（编辑时传入）
    initialData: {
      type: Object,
      default: () => ({
        groupName: '',
        tags: []
      })
    }
  },
  data() {
    return {
      LABEL_GROUP_TYPE_OPTIONS,

      visible: false,
      rowIndex: -1,
      id: '',
      form: {
        group: {
          id: '',
          name: '',
          group_type: LABEL_GROUP_TYPE.MANUAL
        },
        label: []
      }
    }
  },

  methods: {
    focusLabelInput(index) {
      const input = this.$refs[`input_${index}`]
      const target = Array.isArray(input) ? input[0] : input
      target?.focus()
    },
    // 添加标签
    handleAddTag() {
      this.form.label.push({ name: '', id: 0, sort: this.form.label.length + 1 })
      // 滚动到底部
      this.$nextTick(() => {
        const scrollTarget = this.$refs.scrollTarget
        scrollTarget.scrollTo({
          top: scrollTarget.scrollHeight,
          behavior: 'smooth'
        })
      })
      setTimeout(() => {
        this.focusLabelInput(this.form.label.length - 1)
      }, 200)
    },
    openBox(item) {
      this.form.group.name = item.name
      this.form.group.id = item.id
      // 历史数据可能缺失 group_type，回显时兜底为手动标签
      this.form.group.group_type = item.group_type ?? LABEL_GROUP_TYPE.MANUAL
      this.form.label = JSON.parse(JSON.stringify(item.children))

      if (item.children.length == 0) {
        this.form.label.push({ name: '', id: 0, sort: 0 })
        setTimeout(() => {
          this.focusLabelInput(this.form.label.length - 1)
        }, 200)
      }
      this.visible = true
    },
    emitDefaultValueChange() {
      let arr = []
      this.form.label.map((item, index) => {
        let obj = {
          id: item.id,
          sort: index
        }
        arr.push(obj)
      })
    },

    // 删除标签
    handleDeleteTag(item, index) {
      if (!item.id) {
        this.form.label.splice(index, 1)
      } else {
        this.rowIndex = index
        this.$emit('openLable', item)
      }
    },
    delFn() {
      this.form.label.splice(this.rowIndex, 1)
    },

    // 删除标签组
    handleDeleteGroup() {
      this.$confirm('确定要删除这个标签组吗？删除后不可恢复', '警告', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          clientConfigLabelDeleteApi(this.form.group.id).then((res) => {
            this.$emit('getTableData')
          })

          this.visible = false
        })
        .catch(() => {})
    },

    // 确认提交
    handleConfirm() {
      if (this.form.group.name == '') return this.$message.error('标签组名称不能为空')

      const filteredLabels = this.form.label.filter((item) => {
        return item?.name?.trim() !== ''
      })
      this.form.label = filteredLabels
      this.form.label.forEach((item, index) => {
        item.sort = index
      })

      clientSaveLabelsApi(this.form).then((res) => {
        this.visible = false
        this.$emit('getTableData')
      })
    },

    /**
     * 拖拽开始事件
     */
    onStart() {
      // 可添加拖拽开始时的逻辑
    },

    /**
     * 拖拽结束事件
     */
    onEnd() {
      // 可添加拖拽结束时的逻辑
    },

    // 关闭弹窗
    handleClose() {
      this.rowIndex = -1
      this.visible = false
    }
  }
}
</script>

<style scoped lang="scss">
.content {
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
}
.tag-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.tag-item .el-input {
  flex: 1;
  margin-right: 10px;
}
:deep(.el-dialog__footer) {
  padding-top: 0;
}

.tag-actions {
  color: #909399;
  font-size: 16px;
  .icontuodong {
    cursor: move;
    margin-right: 4px;
  }
  .el-icon-delete {
    cursor: pointer;
  }
}

.dialog-footer {
  text-align: right;
}
.delete-group-btn {
  color: #ed4014;
}
:deep(.el-dialog__body) {
  padding: 0 20px;
}
:deep(.el-form-item__label) {
  padding: 0 !important;
}
:deep(.el-form-item) {
  margin-bottom: 15px;
}
:deep(.el-dialog__body) {
  max-height: 580px;
  overflow-y: scroll;
}
</style>
