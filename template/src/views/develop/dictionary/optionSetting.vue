<template>
  <div class="divBox">
    <el-card class="card-box">
      <div class="boder">
        <div class="station-header title-16">
          <i class="el-icon-arrow-left" @click="goBack"></i>
          字典选项设置
        </div>
      </div>
      <div class="content" v-if="left.length > 0">
        <div class="left">
          <div v-for="(item, index) in left" :key="index" class="item" :class="activeId == item.id ? 'active' : ''"
            @click="handleClick(item, index)">
            {{ item.name }}
          </div>
        </div>
        <div class="right">
          <div class="title">{{ activeName }}</div>
          <div class="mt20">
            <template v-if="activeVal.level == 1">
              <draggable tag="div" :list="customizeItems"
                v-bind="{ group: 'optionsGroup', ghostClass: 'ghost', handle: '.icontuodong' }"
                @change="emitDefaultValueChange">
                <div v-for="(option, idx) in customizeItems" :key="idx" class="mb14">
                  <div class="checkBox">
                    <el-input v-model="option.name" size="small" style="width: 300px">
                      <template #suffix>
                        <span>
                          <el-color-picker size="small" v-model="option.color"></el-color-picker>
                        </span>
                      </template>
                    </el-input>

                    <span class="iconfont icontuodong iconadd" title="拖拽排序"></span>

                    <span class="el-icon-delete iconadd" @click.stop="deleteOption(option, idx)"></span>
                  </div>
                </div>
              </draggable>
              <div class="add-text" @click="addOption">+ 添加</div>
            </template>

            <!-- 树形 -->
            <el-tree :data="customizeItems" node-key="value" default-expand-all v-else>
              <template #default="{ node, data }">
                <div class="custom-tree-node">
                  <el-input v-model="data.name" placeholder="数据值" size="small" style="width: 300px;" />
                  <template>
                    <span class="iconfont icontianjia1 iconadd" @click="addFn(node, data)" />
                    <span class="iconfont icona-ziji1x iconadd" title="添加子项" @click="addChildFn(node, data)" />
                    <span class="el-icon-delete iconadd" title="删除" @click="deleteFn(node, data)" />
                  </template>
                </div>
              </template>
            </el-tree>
          </div>
        </div>
      </div>



      <div v-else>
        <default-page :index="18" />
      </div>
    </el-card>
    <div class="cr-bottom-button">
      <el-button @click="goBack" size="small">取消</el-button>
      <el-button type="primary" @click="saveEvt" size="small" :loading="submitLoading">保存</el-button>
    </div>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import { crudDictListApi, crudDictBatchApi } from '@/api/develop'
import { getDictDataListApi, getDictDataDeleteApi, getDictTreeListApi } from '@/api/form'
import Draggable from 'vuedraggable'
import { roterPre } from '@/settings'
export default {
  components: {
    Draggable,
    defaultPage: () => import('@/components/common/defaultPage')
  },
  data() {
    return {
      activeId: 1,
      activeName: '客户回访设置',
      activeVal: {},
      left: [],
      optionModel: {
        defaultValue: ''
      },
      entityId: '', // 实体id
      customizeItems: [],

      submitLoading: false
    }
  },

  mounted() {
    window.x = this;
    if (this.$route.query.id) {
      this.entityId = this.$route.query.id
      this.getList(this.$route.query.id)
    }
  },
  methods: {
    findParentNode(treeNodeId) {
      let parentNode = null;
      const find = (node) => {
        if (!node.children) return;
        for (const n of node.children) {
          if (n.$treeNodeId === treeNodeId) {
            parentNode = node;
          }
          if (n.children) {
            find(n);
          }
        }
      }
      find({
        children: this.customizeItems
      });

      return parentNode;
    },
    appendNode(target, pid) {
      const maxValue = this.findMaxValueInTree(this.customizeItems)
      const newValue = String(maxValue + 1)
      const newNode = {
        name: '选项' + newValue,
        value: newValue,
        status: 1
      };
      pid !== undefined && (newNode.pid = pid);
      target.push(newNode);
    },
    addFn(node, data) {
      const parentNode = this.findParentNode(data.$treeNodeId, "$treeNodeId");
      if (!parentNode.children) {
        parentNode.children = [];
      }
      this.appendNode(parentNode.children, data.pid);
    },
    addChildFn(node, data) {
      if (!data.children) {
        this.$set(data, 'children', [])
      }
      this.appendNode(data.children, data.id);
    },
    async deleteFn(node, data) {
      await modalSure('你确定要删除这条数据吗')
      node.remove()
    },
    // 获取最大值
    findMaxValueInTree(nodes) {
      if (!nodes || nodes.length === 0) return 0
      let max = -Infinity
      function dfs(nodeList) {
        for (const node of nodeList) {
          // 转换 value 为数字并更新最大值
          const currentValue = Number(node.value)
          if (!isNaN(currentValue)) {
            max = Math.max(max, currentValue)
          }

          // 递归处理子节点
          if (node.children && node.children.length > 0) {
            dfs(node.children)
          }
        }
      }

      // 统一处理数组形式
      dfs(Array.isArray(nodes) ? nodes : [nodes])
      return max === -Infinity ? 0 : max // 空树返回 0
    },
    goBack() {
      if (this.$route.query.keyName) {
        this.$router.push({
          path: `${roterPre}/crud/module/${this.$route.query.keyName}/list`
        })
      } else {
        this.$router.push({
          path: `${roterPre}/develop/crud/design`,
          query: { id: this.entityId, tabIndex: 4 }
        })
      }
    },
    handleClick(item, index) {
      this.activeVal = item
      this.activeId = item.id
      this.activeName = item.name
      let obj = {
        types: item.ident,
        // level: 1
      }
      getDictTreeListApi(obj).then((res) => {
        this.customizeItems = res.data
      })
    },

    getList(id) {
      crudDictListApi(id).then((res) => {
        this.left = res.data
        this.handleClick(this.left[0])
      })
    },

    async deleteOption(item, index) {
      await modalSure('你确定要删除这条内容吗')
      if (item.id) {
        await getDictDataDeleteApi(item.id)
      }

      await this.customizeItems.splice(index, 1)
    },

    async saveEvt() {
      if (this.submitLoading) return
      this.submitLoading = true
      try {
        await crudDictBatchApi({ dict_data: this.customizeItems, dict_type_id: this.activeVal.id })
        this.goBack()
      } catch (error) {
        this.$message.error(error.message)
      } finally {
        this.submitLoading = false
      }
    },
    // 排序
    emitDefaultValueChange() {
      let arr = []
      this.customizeItems.map((item, index) => {
        let obj = {
          id: item.id,
          sort: index
        }
        arr.push(obj)
      })
    },

    addOption() {
      if (this.customizeItems) {
        const maxValue = Math.max(...this.customizeItems.map((item) => Number(item.value)))

        let newValue = maxValue + 1
        let obj = {
          name: `选项${newValue}`,
          value: newValue + '', // value值
          color: '#1890ff',
          status: 1,
          sort: 0
        }
        this.customizeItems.push(obj)
      } else {
        this.customizeItems = [
          {
            name: '选项1',
            color: '#1890ff',
            value: '1',
            status: 1,
            sort: 0
          }
        ]
      }
    },

    // 返回页面
    backFn() {
      this.$router.push({
        path: `${roterPre}/develop/dictionary`
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.card-box {
  height: calc(100vh - 140px);
  overflow-y: auto;
  background: #fff;
}

::v-deep .el-card__body {
  padding: 0;
}

.cr-bottom-button {
  position: fixed;
  left: -20px;
  right: 0;
  bottom: 0;
  width: calc(100% + 220px);
}

::v-deep .el-tree-node__content {
  height: 32px;
  margin-bottom: 14px;
}
::v-deep .el-tree-node  :hover {
background: transparent;
}
::v-deep .el-tree-node__expand-icon.is-leaf {
  display: none;
}

.station-header {
  height: 60px;
  line-height: 60px;
  border-bottom: 1px solid #eeeeee;
  padding: 0 20px;

  .el-icon-arrow-left {
    cursor: pointer;
  }
}

.content {
  display: flex;

  .left {
    padding-top: 12px;
    width: 270px;
    height: calc(100vh - 210px);
    border-right: 1px solid #eeeeee;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;

    .item {
      height: 40px;
      padding-left: 20px;
      line-height: 40px;
      font-size: 14px;
      color: #303133;
      cursor: pointer;
    }

    .active {
      background: #f1f9ff;
      color: #1890ff;
      font-weight: 500;
      cursor: pointer;
      border-right: 1px solid #1890ff;
    }
  }

  .right {
    padding: 0 20px;
    padding-top: 12px;

    ::v-deep .el-color-picker__color {
      border-color: #dddddd;
    }

    .title {
      font-family: PingFang SC, PingFang SC;
      font-weight: 500;
      font-size: 14px;
      color: #303133;
    }

    .add-text {
      cursor: pointer;
      width: 74px;
      height: 32px;
      background: rgba(24, 144, 255, 0.06);
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 13px;
      line-height: 32px;
      text-align: center;
      color: #1890ff;
      border-radius: 2px;
    }
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

.icontuodong li.ghost {
  background: #fff;
  border: 2px dotted #409eff;
}

.icontuodong {
  cursor: move;
}

.drag-option {
  cursor: move;
}

.iconadd {
  cursor: pointer;
  width: 15px;
  color: #909399;
  font-size: 14px;
  margin-left: 6px;
}

::v-deep .el-color-picker__trigger {
  border: none;
  margin-bottom: 2px;
}
</style>
