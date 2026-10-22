<template>
  <div>
    <el-dialog
      :visible.sync="dialogVisible"
      title="移动到"
      width="480px"
      :append-to-body="true"
      :before-close="handleClose"
    >
      <div class="tree-box">
        <div class="move-search">
          <el-input v-model="searchKey" placeholder="搜索" size="small" prefix-icon="el-icon-search" clearable />
        </div>
        <el-scrollbar style="height: calc(100% - 42px)">
          <el-tree
            ref="tree"
            :data="displayTreeData"
            :props="defaultProps"
            :highlight-current="true"
            :default-expanded-keys="[1]"
            :expand-on-click-node="false"
            node-key="id"
            :indent="16"
            @node-click="handleClick"
          >
            <template #default="{ node, data }">
              <div class="custom-tree-node">
                <i class="tree-icon iconfont iconwenjianjia1 folder-icon" />
                <span class="over-text" :title="data.name">{{ data.name }}</span>
              </div>
            </template>
          </el-tree>
        </el-scrollbar>
      </div>
      <template #footer>
        <div class="dialog-footer text-right">
          <el-button size="small" @click="handleClose">{{ $t('public.cancel') }}</el-button>
          <el-button size="small" type="primary" :loading="saveLoading" @click="handleSave">{{
            $t('public.ok')
          }}</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { memorialCateListApi, memorialEditApi } from '@/api/user'

export default {
  name: 'MoveDialog',
  props: {
    moveData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dialogVisible: false,
      name: '1',
      treeData: [],
      searchKey: '', // 目录搜索关键字
      defaultProps: {
        children: 'children',
        label: 'name'
      },
      fileId: null,
      activeIndex: '1',
      formData: {
        type: null
      },
      saveLoading: false
    }
  },
  computed: {
    // 搜索过滤：保留名称命中的文件夹及其父级链，便于定位上下文
    displayTreeData() {
      const kw = this.searchKey.trim().toLowerCase()
      if (!kw) return this.treeData
      const filter = (nodes) => {
        const result = []
        ;(nodes || []).forEach((node) => {
          if (Array.isArray(node.children) && node.children.length) {
            const children = filter(node.children)
            if (children.length) {
              result.push({ ...node, children })
              return
            }
          }
          if (String(node.name || '').toLowerCase().includes(kw)) result.push({ ...node })
        })
        return result
      }
      return filter(this.treeData)
    }
  },
  watch: {
    // 搜索时展开全部命中层级，避免结果藏在折叠目录里
    searchKey(val) {
      if (!val) return
      this.$nextTick(() => {
        const nodesMap = this.$refs.tree && this.$refs.tree.store.nodesMap
        nodesMap &&
          Object.values(nodesMap).forEach((node) => {
            node.expanded = true
          })
      })
    }
  },
  mounted() {},
  methods: {
    async menuList() {
      const result = await memorialCateListApi()
      // 递归移除空 children：无下级的目录按叶子处理，不展示展开箭头
      const removeEmptyChildren = (nodes) => {
        ;(nodes || []).forEach((n) => {
          if (Array.isArray(n.children)) {
            if (n.children.length) removeEmptyChildren(n.children)
            else delete n.children
          }
        })
        return nodes
      }
      this.treeData = removeEmptyChildren(result.data.tree || [])
    },
    handleClose() {
      this.dialogVisible = false
    },
    handleOpen() {
      this.dialogVisible = true
      this.menuList()
    },
    handleClick(node, data) {
      this.fileId = node.id
    },
    handlerEmit() {
      this.$emit('handlerMove')
    },
    handleSave() {
      if (!this.fileId) {
        this.$message.error('选择移动的文件夹')
      } else {
        const data = {
          title: this.moveData.data.title,
          content: this.moveData.data.content,
          pid: this.fileId
        }
        this.memorialEdit(this.moveData.data.id, data)
      }
    },
    // 编辑
    memorialEdit(id, data) {
      this.saveLoading = true
      memorialEditApi(id, data)
        .then((res) => {
          this.saveLoading = false
          this.handleClose()
          this.handlerEmit()
        })
        .catch((err) => {
          this.saveLoading = false
        })
    }
  }
}
</script>

<style scoped lang="scss">
::v-deep .el-dialog__body {
  padding: 0 !important;
}
.tab-move {
  ::v-deep .el-tabs__nav-wrap::after {
    height: 0;
  }
  ::v-deep .el-tabs__header {
    margin: 0;
  }
  ::v-deep .el-tabs__active-bar {
    height: 0;
  }
  ::v-deep .el-tabs__item {
    padding-bottom: 15px;
    height: 0;
    line-height: 0;
  }
}
::v-deep .el-tabs__item.is-active {
  color: #303133;
  font-weight: 600;
}
.tree-box {
  height: 340px;

  .move-search {
    padding: 10px 10px 0;

    ::v-deep .el-input__inner {
      background-color: #f2f3f5;
      border: none;
      border-radius: 6px;
    }
  }

  ::v-deep .el-scrollbar__wrap {
    overflow-x: hidden;
  }

  // 与左侧目录树样式统一
  ::v-deep .el-tree {
    background: transparent;
    padding: 4px 10px 0;
  }

  ::v-deep .el-tree-node__expand-icon {
    color: #8a919f;
    font-size: 12px;
  }

  // 无下级的目录不展示展开箭头（保持占位对齐）
  ::v-deep .el-tree-node__expand-icon.is-leaf {
    color: transparent;
  }

  ::v-deep .el-tree-node__content {
    height: 44px;
    border-radius: 4px;
    font-family: PingFang SC, PingFang SC;
    font-size: 14px;
    color: #303133;

    &:hover {
      background-color: #e9ebee;
    }
  }

  ::v-deep .el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content {
    background: #e8f4ff;
  }

  ::v-deep .el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content .custom-tree-node {
    font-weight: 600;
    color: #1890ff;
  }

  .custom-tree-node {
    display: flex;
    align-items: center;
    min-width: 0;
    // 不在此处裁剪：负外边距图标的左缘会被切掉，标题截断由 .over-text 自身处理

    .tree-icon {
      margin-right: 4px;
      margin-left: -4px;
      font-size: 14px;
      color: #606266;
    }

    .over-text {
      flex: 1;
      min-width: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }
  }
}
</style>
