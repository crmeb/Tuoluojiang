<template>
  <div class="tree-box">
    <div class="el-card__header clearfix">
      <el-input v-model="filterText" clearable placeholder="搜索部门" prefix-icon="el-icon-search" />
    </div>
    <div v-height>
      <el-scrollbar style="height: 100%" class="reset-scroll-bar">
        <el-tree
          ref="tree"
          :data="treeData"
          :props="defaultProps"
          :highlight-current="true"
          :filter-node-method="filterNode"
          :default-expanded-keys="defaultFrame"
          :expand-on-click-node="false"
          node-key="value"
          style="margin-top: 10px"
          @node-click="handleClick"
        >
          <template #default="{ node, data }">
            <div class="custom-tree-node over-text">
              <span class="flex-box">
                <i class="icon iconfont tree-icon iconwenjianjia" />
                {{ node.label }}
                <span v-if="data.user_count && isShowUserCount">({{ data.user_count }})</span>
              </span>
            </div>
          </template>
        </el-tree>
      </el-scrollbar>
    </div>
  </div>
</template>

<script>
export default {
  emits: ['frameId'],
  name: 'MenuTree',
  props: {
    treeData: {
      type: Array,
      default: () => {
        return []
      }
    },
    isShowUserCount: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      filterText: '',
      defaultProps: {
        children: 'children',
        label: 'label'
      },
      defaultFrame: []
    }
  },
  watch: {
    treeData: {
      handler(nVal, oVal) {
        this.$nextTick(() => {
          if (nVal.length) {
            const value = nVal[0].value
            this.defaultFrame = [value]
            this.$refs.tree.setCurrentKey(value)
          }
        })
      },
      deep: true
    },
    filterText(val) {
      this.$refs.tree.filter(val)
    }
  },
  methods: {
    // tree节点点击
    handleClick(node, data) {
      this.$emit('frameId', node.$treeNodeId === 1 ? '' : node.value)
    },
    // tree搜索过滤
    filterNode(value, data) {
      if (!value) return true
      return data.label.indexOf(value) !== -1
    }
  }
}
</script>

<style lang="scss" scoped>
.flex-box {
  font-family: PingFangSC-Regular, PingFang SC;
  font-size: 14px;
  font-weight: 400;
  color: #303133;
}
.tree-icon {
  margin-right: 5px;
  font-size: 15px;
  color: #ffca28;
}
.el-card__header {
  padding: 15px 20px;
}
.el-card__header :deep(.el-input .el-input__inner) {
  cursor: pointer;
}
.el-card__header :deep(.el-input__clear) {
  margin-top: -5px !important;
}

:deep(.el-scrollbar__wrap) {
  overflow-x: hidden;
}

:deep(.el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content) {
  background: rgba(24, 144, 255, 0.08);
  .flex-box {
    color: #1890ff !important;
    font-weight: 600;
  }
}
:deep(.el-tree-node__content) {
  height: 40px;
  border-radius: 4px;
}
:deep(.el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content .custom-tree-node),
.el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content .right-icon {
  display: inline-block;
  color: #1890ff;
  font-weight: 600;
}

:deep(.el-tree) {
  // padding: 14px 0;
}

:deep(.el-tree-node > .el-tree-node__children) {
  overflow: inherit;
}
.custom-tree-node {
  position: relative;
  width: 100%;
  .right-icon {
    display: none;
    position: absolute;
    right: 10px;
  }
}
.tree-box {
  border-right: 1px solid #eeeeee;

  margin: -20px 0 -20px -20px;
}
:deep(.el-tree) {
  background-color: transparent;
}
</style>
