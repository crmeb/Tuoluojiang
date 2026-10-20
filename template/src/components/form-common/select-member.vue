<!-- @FileDescription: 下拉选择人员组件 -->
<template>
  <el-popover
    placement="bottom-start"
    trigger="manual"
    :visible="showPopover"
    width="266"
    popper-class="popover"
    ref="treePopover"
  >
    <isFullScreen @call-parent-method="handlePopoverHide">
      <!-- 人员数据 -->
      <div class="tree-box">
        <div class="input">
          <el-input prefix-icon="el-icon-search" placeholder="请输入人员搜索" v-model="filterText" />
        </div>
        <el-tree
          highlight-current
          :props="treeProps"
          :data="treeData"
          :show-checkbox="!onlyOne"
          :default-checked-keys="selectIds"
          :filter-node-method="filterNode"
          @check="handleCheck"
          @node-click="handleNodeClick"
          ref="tree"
          node-key="id"
        >
          <template #default="{ node, data }">
            <div class="custom-tree-node">
              <div class="flex flex-between">
                <div class="display-align" :class="{ isChecked: userIds.includes(data.value) }">
                  <i v-if="data.type === 0" class="tree-icon iconfont iconwenjianjia" />
                  <img v-if="data.type === 1" v-default-avatar="data" :src="getAvatarSrc(data)" alt="" class="avatar" />
                  {{ node.label }}
                </div>
                <div v-if="data.type === 1">
                  <img v-if="data.is_work" src="../../assets/images/bindWeChat.png" alt="" class="bindWeChat" />
                  <img v-else src="../../assets/images/unbindWeChat.png" alt="" class="bindWeChat" />
                </div>
              </div>
            </div>
          </template>
        </el-tree>
      </div>
    </isFullScreen>

    <!-- 人员数据 -->
    <!-- 与 element-ui 一致：调用方的 class / style 落在包裹触发元素的 span 上（el-popover 不透传 attrs） -->
    <template #reference>
      <span v-bind="$attrs">
        <slot v-if="$slots.custom" name="custom"></slot>
        <div
          class="select plan-footer-one mr10"
          :class="{ 'is-disabled': disabled }"
          ref="select"
          v-else
          @click="handlePopoverShow"
        >
          <span class="el-icon-arrow-down" v-if="!userList.length || disabled"></span>
          <span class="el-icon-circle-close" v-else @click.stop="clearAllUsers"></span>

          <span v-if="!userList.length" class="placeholder">{{ placeholder }}</span>
          <!-- 正常展示样式 -->
          <div class="flex-box" v-else-if="!isSearch">
            <span
              v-for="(item, index) in userList"
              :key="item.value"
              class="el-tag el-tag--small el-tag--info el-tag--light mr10"
              @click.stop=""
            >
              <img v-if="isAvatar" v-default-avatar="item" :src="getAvatarSrc(item)" alt="" class="avatar" /> {{ item.name }}
              <i v-if="!disabled" class="el-tag__close el-icon-close" @click.stop="removeUser(index, item)" />
            </span>
          </div>

          <!-- 筛选条件展示样式 -->
          <div class="flex-box" v-else>
            <span
              v-for="(item, index) in userList.slice(0, 1)"
              :key="item.value"
              class="el-tag el-tag--small el-tag--info el-tag--light mr10"
              @click.stop=""
            >
              <img v-if="isAvatar" v-default-avatar="item" :src="getAvatarSrc(item)" alt="" class="avatar" />
              {{ item.name }}
              <i v-if="!disabled" class="el-tag__close el-icon-close" @click.stop="removeUser(index, item)" />
            </span>
            <el-tag class="el-tag el-tag--small el-tag--info el-tag--light ml10" v-if="userList.length > 1">
              {{ userList.length - 1 }}</el-tag
            >
          </div>
        </div>
      </span>
    </template>
  </el-popover>
</template>

<script>
import { mapStores } from 'pinia'
import { useOrganizationStore } from '@/stores/organization.ts'
import { getAvatarSrc } from '@/utils/avatar.ts'
import { getArrayDifference, isInArray, removeDuplicateObjects } from '@/libs/public.ts'
import isFullScreen from '@/components/isFullScreen/index'

export default {
  inheritAttrs: false,
  name: 'SelectMember',
  emits: ['getSelectList', 'handlePopoverHide'],
  components: { isFullScreen },
  props: {
    // 选中人员数据
    value: {
      type: Array,
      default: () => []
    },
    // 选中的人员id
    selectIdData: {
      type: Array,
      default: () => []
    },
    // 角色权限禁用特殊处理
    disabled: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: '请选择人员'
    },
    // 只能单选一个人员
    onlyOne: {
      type: Boolean,
      default: false
    },
    // 开启权限
    role: {
      type: Number,
      default: 0
    },
    // 是否展示人员头像
    isAvatar: {
      type: Boolean,
      default: false
    },
    // 是否为搜索模式
    isSearch: {
      type: Boolean,
      default: false
    },
    // 禁用人员名单
    disabledList: {
      type: Array,
      default: () => []
    },
    // 绑定企微标识,未绑定企微无法选中
    isqiWeiWork: {
      type: Boolean,
      default: false
    },
    // 可选人员白名单(人员id数组)，传 null 表示不限制，传数组(含空数组)则仅可选白名单内人员
    enableIds: {
      type: Array,
      default: null
    }
  },
  data() {
    return {
      treeProps: {
        children: 'children',
        label: 'label'
      },
      filterText: '',
      selectIds: [],
      showPopover: false,
      userList: [], // 选中的人员数据
      userIds: [], // 选中的人员的id
      rawTreeData: [] // 未经白名单过滤的原始人员树
    }
  },

  watch: {
    selectIdData: {
      async handler(newVal, oldVal) {
        // 当新值为空数组时，直接清空选择
        if (newVal.length === 0) {
          this.userList = []
          this.clearSelection()
          return
        }

        // 若 treeData 尚未加载，等待下次更新
        if (!this.rawTreeData.length) {
          await this.getTreeData()
        }

        const list = this.findUsersByIds(this.treeData, newVal)
        this.formatUserData(list, true)
      },
      immediate: true
    },

    filterText(val) {
      this.$refs.tree?.filter(val)
    },

    value: {
      async handler(newVal) {
        if (!newVal.length) {
          this.clearSelection()
          return
        }

        if (!this.rawTreeData.length) {
          await this.getTreeData()
        }

        if (newVal[0] && newVal[0].name) {
          this.formatUserData(this.value)
        } else {
          const list = this.findUsersByIds(this.treeData, newVal)
          this.formatUserData(list, true)
        }
      },
      immediate: true
    },

    // 白名单变化后重新同步树的勾选态
    enableIds() {
      this.$nextTick(() => {
        this.selectIds = this.findNodeIdsByIds(this.treeData, this.userIds)
        this.$refs.tree?.setCheckedKeys(this.selectIds)
      })
    }
  },

  computed: {
    ...mapStores(useOrganizationStore),
    hasCustomSlot() {
      return !!this.$slots.custom
    },

    // 白名单集合，为 null 表示不限制可选范围
    enableIdSet() {
      return Array.isArray(this.enableIds) ? new Set(this.enableIds.map((id) => String(id))) : null
    },

    // 实际渲染的人员树：白名单存在时仅保留白名单人员及其所属部门
    treeData() {
      return this.enableIdSet ? this.filterTreeByEnableIds(this.rawTreeData) : this.rawTreeData
    }
  },

  mounted() {
    document.addEventListener('click', this.handleGlobalClick)
    this.getTreeData()
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleGlobalClick)
  },

  methods: {
    getAvatarSrc,
    // 清除选择
    clearSelection() {
      this.userList = []
      this.userIds = []
      this.selectIds = []
      // 确保 tree 已挂载再调用
      this.$nextTick(() => this.$refs.tree?.setCheckedKeys([]))
    },

    // 清空全部选中人员
    clearAllUsers() {
      if (this.disabled) return
      this.clearSelection()
      this.$emit('getSelectList', [])
    },

    // 格式化用户数据
    formatUserData(val, isIdList = false) {
      if (!val.length) return

      this.userList = []

      if (!isIdList) {
        // 确保每个用户对象都有 value 属性
        val.forEach((item) => {
          if (!item.value) {
            item.value = item.id
          }
        })
      }

      this.userList = removeDuplicateObjects(val, 'value')
      this.userIds = this.userList.map((item) => this.getUserValue(item))
      this.selectIds = this.findNodeIdsByIds(this.treeData, this.userIds)

      // 确保 tree 已挂载再调用
      this.$nextTick(() => this.$refs.tree?.setCheckedKeys(this.selectIds))
    },

    // 移除用户
    removeUser(index, item) {
      if (this.disabled) return
      this.userList.splice(index, 1)

      this.userIds = this.userList.map((item) => this.getUserValue(item))
      this.selectIds = this.findNodeIdsByIds(this.treeData, this.userIds)
      // 确保 tree 已挂载再调用
      this.$nextTick(() => this.$refs.tree?.setCheckedKeys(this.selectIds))
      this.$emit('getSelectList', this.userList)
    },

    // 处理多选
    handleCheck(data, status) {
      if (data.user_count === 0) {
        return false
      }

      if (this.isDisabledUser(data)) {
        return this.$message.warning('不能选择此人员')
      }

      // 选择成员
      const members = this.getDepartmentMembers(data)

      // 点击选中
      if (isInArray(status.checkedKeys, data.id)) {
        this.userList.push(...members)
        // 根据value过滤重复成员
        this.userList = removeDuplicateObjects(this.userList, 'value')
      } else {
        this.userList = getArrayDifference(this.userList, members, 'value')
      }

      this.userIds = this.userList.map((item) => this.getUserValue(item))
      this.selectIds = this.findNodeIdsByIds(this.treeData, this.userIds)

      // 确保 tree 已挂载再调用
      this.$nextTick(() => this.$refs.tree?.setCheckedKeys(this.selectIds))

      this.$emit('getSelectList', this.userList)
    },

    // 过滤节点
    filterNode(value, data) {
      if (!value) return true
      return data.label.indexOf(value) !== -1
    },

    // 处理节点点击（单选）
    handleNodeClick(user) {
      if (user.type === 0) return false

      if (this.isDisabledUser(user)) {
        return this.$message.warning('不能选择此人员')
      }

      if (!this.onlyOne) {
        return
      }

      if (this.userIds.some((id) => this.isSameId(id, this.getUserValue(user)))) {
        return this.$message.warning('已选中该成员')
      }

      this.userList = [user]
      this.userIds = this.userList.map((item) => this.getUserValue(item))
      this.userList = removeDuplicateObjects(this.userList)

      this.$emit('getSelectList', this.userList)
      this.$emit('handlePopoverHide', this.userList)
      this.$refs.treePopover?.hide()
    },

    // 点击是否落在触发框或弹层内：Element Plus 的 popover 根节点是片段，$el 不能用来判断
    isInsidePopover(target) {
      const popper = this.$refs.treePopover?.popperRef
      return [popper?.triggerRef, popper?.contentRef].some((el) => el?.contains?.(target))
    },
    // 处理全局点击
    handleGlobalClick(event) {
      if (!this.isInsidePopover(event.target)) {
        this.$emit('handlePopoverHide', this.userList)
        this.showPopover = false
        this.filterText = ''
      }
    },

    // 显示弹出框
    async handlePopoverShow() {
      if (this.disabled) return
      await this.getTreeData()
      this.showPopover = true
    },

    // 隐藏弹出框
    handlePopoverHide() {
      this.showPopover = false
      this.filterText = ''
      this.$emit('handlePopoverHide', this.userList)
    },

    // 获取树数据
    async getTreeData() {
      // 已有缓存时同步取用，避免多一个微任务导致回显时序变化
      const cached = this.organizationStore.memberList
      const treeData = cached.length ? cached : await this.organizationStore.getMember()
      this.rawTreeData = treeData || []
      this.selectIds = this.findNodeIdsByIds(this.treeData, this.userIds)
    },

    /**
     * 按白名单过滤人员树，仅保留白名单内人员及其所属部门
     * @param {Array} tree 树数据
     * @returns {Array} 过滤后的树数据
     */
    filterTreeByEnableIds(tree) {
      const result = []

      tree.forEach((node) => {
        if (node.type === 0) {
          const children = this.filterTreeByEnableIds(node.children || [])
          if (children.length) {
            result.push({ ...node, children, user_count: this.countTreeMembers(children) })
          }
          return
        }

        if (this.enableIdSet.has(String(this.getUserValue(node)))) {
          result.push(node)
        }
      })

      return result
    },

    /**
     * 统计树中的人员数量
     * @param {Array} tree 树数据
     * @returns {Number} 人员数量
     */
    countTreeMembers(tree) {
      return tree.reduce((total, node) => {
        return total + (node.type === 0 ? this.countTreeMembers(node.children || []) : 1)
      }, 0)
    },

    getUserValue(user) {
      if (!user) return ''
      return user.value !== undefined && user.value !== null ? user.value : user.id
    },

    isSameId(a, b) {
      return String(a) === String(b)
    },

    isDisabledUser(user) {
      const value = this.getUserValue(user)
      return this.disabledList.some((id) => this.isSameId(id, value))
    },

    /**
     * 递归获取某部门所有成员
     * @param {Object} department 部门数据
     * @returns {Array} 成员列表
     */
    getDepartmentMembers(department) {
      const members = []

      function traverse(children) {
        children.forEach((child) => {
          if (child.children) {
            traverse(child.children)
          } else if (child.type !== 0) {
            members.push(child)
          }
        })
      }

      traverse([department])
      return members
    },

    /**
     * 根据ID数组查找用户对象
     * @param {Array} tree 树数据
     * @param {Array} ids ID数组
     * @returns {Array} 用户对象列表
     */
    findUsersByIds(tree, ids) {
      const idSet = new Set(ids.map((id) => String(id)))
      const result = []

      function traverse(node) {
        const value = node.value !== undefined && node.value !== null ? node.value : node.id
        if (idSet.has(String(value)) && node.type !== 0) {
          result.push(node)
        }
        if (node.children) {
          for (const child of node.children) {
            traverse(child)
          }
        }
      }

      for (const node of tree) {
        traverse(node)
      }

      return result
    },

    /**
     * 根据ID数组查找节点ID
     * @param {Array} tree 树数据
     * @param {Array} ids ID数组
     * @returns {Array} 节点ID列表
     */
    findNodeIdsByIds(tree, ids) {
      const idSet = new Set(ids.map((id) => String(id)))
      const result = []

      function traverse(node) {
        const value = node.value !== undefined && node.value !== null ? node.value : node.id
        if (idSet.has(String(value)) && node.type !== 0) {
          result.push(node.id)
        }
        if (node.children) {
          for (const child of node.children) {
            traverse(child)
          }
        }
      }

      for (const node of tree) {
        traverse(node)
      }

      return result
    }
  }
}
</script>

<style scoped lang="scss">
.avatar {
  display: inline-block;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  margin-right: 6px;
  vertical-align: middle; /* 垂直居中 */
}

.bindWeChat {
  display: block;
  width: 16px;
  height: 16px;
  margin-right: 6px;
}

.tree-box {
  min-height: 150px;
  position: sticky;
  padding: 24px 12px;
  z-index: 9999;
  background: #fff;
  min-width: 150px;
  border-radius: 4px;
  border: 1px solid #e6ebf5;

  .input {
    padding: 0 12px;
  }

  .custom-tree-node {
    width: 100%;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 14px;
    color: #303133;

    .iconwenjianjia {
      color: #1890ff;
      margin-right: 6px;
    }
  }

  :deep(.el-tree) {
    margin-top: 12px;
    max-height: 350px;
    overflow-y: auto;
    scrollbar-width: none; /* firefox */
    -ms-overflow-style: none; /* IE 10+ */

    .is-checked {
      color: #1890ff !important;
    }

    .el-tree-node__content {
      height: 32px;
      line-height: 32px;
    }

    .el-tree-node__content:hover {
      background: rgba(24, 144, 255, 0.05);
    }
  }
}

.plan-footer-one {
  position: relative;
  cursor: pointer;
  -webkit-appearance: none;
  background-color: #fff;
  background-image: none;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  box-sizing: border-box;
  color: #c0c4cc;
  display: inline-block;
  font-size: inherit;
  min-height: 32px;
  line-height: 30px;
  outline: none;
  font-size: 13px;
  padding: 0 10px;
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  width: 100%;

  .el-tag.el-tag--info {
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 13px;
    color: #303133;
  }

  &.is-disabled {
    cursor: not-allowed;
    background-color: #f5f7fa;
    border-color: #e4e7ed;
    color: #c0c4cc;
  }
}

.el-icon-arrow-down,
.el-icon-circle-close {
  font-weight: 400;
  position: absolute;
  right: 10px;
  top: 8px;
  cursor: pointer;
}

.el-icon-circle-close {
  color: #909399;
  font-size: 14px;
}

.isChecked {
  color: #1890ff !important;
}

:deep(.el-popper) {
  padding: 0;
  margin-top: 5px;
}
</style>

<style>
.popover {
  padding: 0px !important;
}
</style>
