<!-- @FileDescription: 下拉选择部门组件 -->
<template>
  <el-popover
    ref="treePopover"
    :visible="showPopover"
    placement="bottom-start"
    popper-class="popover"
    :width="isSite ? 306 : 266"
    trigger="manual"
  >
    <isFullScreen @call-parent-method="handlePopoverHide">
      <div class="tree-box">
        <div class="input">
          <el-input
            v-model="filterText"
            style="width: 218px"
            placeholder="请输入部门搜索"
            prefix-icon="el-icon-search"
          >
          </el-input>
        </div>

        <el-tree
          ref="tree"
          :data="treeData"
          :default-checked-keys="selectIds"
          :default-expanded-keys="treeExpandData"
          :filter-node-method="filterNode"
          :indent="8"
          :props="props"
          :check-strictly="checkStrictly"
          :show-checkbox="!onlyOne"
          highlight-current
          node-key="id"
          @check="depCheck"
        >
          <template #default="{ node, data }">
            <span class="custom-tree-node">
              <div class="flex flex-between">
                <div
                  :class="{
                    isChecked: departmentIds.includes(data.id)
                  }"
                  class="flex"
                  @click.stop="append(node, data)"
                >
                  <i class="tree-icon iconfont iconwenjianjia" />
                  <span class="label-text over-text">{{ data.label }}</span>
                </div>
                <div
                  v-if="isSite && departmentIds.includes(data.id)"
                  :class="{
                    checkedColor: data.id == is_mastartId
                  }"
                  class="main-department"
                  @click.stop="isMastart(data)"
                >
                  {{ data.id == is_mastartId ? '主部门' : '设为主部门' }}
                </div>
              </div>
            </span>
          </template>
        </el-tree>
      </div>
    </isFullScreen>

    <!-- 部门数据 -->
    <!-- 与 element-ui 一致：调用方的 class / style 落在包裹触发元素的 span 上（el-popover 不透传 attrs） -->
    <template #reference>
      <span v-bind="$attrs">
        <slot v-if="$slots.custom" name="custom"></slot>
        <div v-else ref="select" class="select plan-footer-one mr10" @click="handlePopoverShow">
          <span class="el-icon-arrow-down"></span>
          <span v-if="selectList && selectList.length == 0" class="placeholder">{{ placeholder }}</span>
          <div v-if="selectList.length > 0 && !isSearch" class="flex-box">
            <span
              v-for="(item, index) in selectList"
              :key="index"
              class="el-tag el-tag--small el-tag--info el-tag--light"
              @click.stop=""
            >
              {{ item.label || item.name }}
              <span v-if="isSite && item.id == is_mastartId">(主) </span>
              <i class="el-tag__close el-icon-close" @click.stop="cardTag(index)" />
            </span>
          </div>

          <!-- 以下是筛选条件展示样式 -->
          <div v-if="selectList.length > 0 && isSearch" class="flex">
            <div
              v-for="(item, index) in selectList.slice(0, 1)"
              :key="index"
              class="el-tag el-tag--small el-tag--info el-tag--light mr10 flex-search"
              @click.stop=""
            >
              <span class="left over-text"> {{ item.label || item.name }}</span>

              <i class="right el-tag__close el-icon-close" @click.stop="cardTag(0)" />
            </div>
            <el-tag v-if="selectList.length > 1" class="el-tag el-tag--small el-tag--info el-tag--light mt3">
              {{ selectList.length - 1 }}</el-tag
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
import { extractArrayIds, isInArray, removeDuplicateObjects, getArrayDifference } from '@/libs/public.ts'
import isFullScreen from '@/components/isFullScreen/index'

export default {
  inheritAttrs: false,
  emits: ['changeMastart'],
  name: '',
  components: {
    isFullScreen
  },
  props: {
    checkStrictly: {
      type: Boolean,
      default: false
    },
    // 选中人员数据
    value: {
      type: Array,
      default: () => {
        return []
      }
    },
    // 开启设为主部门
    isSite: {
      type: Boolean,
      default: false
    },
    selectId: {
      // 选择的部门id集合
      type: Array,
      default: () => {
        return []
      }
    },
    placeholder: {
      type: String,
      default: '请选择部门'
    },
    // 只能单选一个人员
    onlyOne: {
      type: Boolean,
      default: false
    },
    isSearch: {
      type: [Boolean, String],
      default: false
    },
    // 开启权限
    role: {
      type: Number,
      default: 0
    },
    // 禁用人员名单
    disabledList: {
      type: Array,
      default: () => {
        return []
      }
    }
  },
  computed: {
    ...mapStores(useOrganizationStore)
  },
  data() {
    return {
      props: {
        children: 'children',
        label: 'label'
      },
      showPopover: false,
      isSlots: false,
      treeHeight: '',
      filterText: '',
      selectIds: [],
      is_mastartId: 0,
      treeExpandData: [],
      departmentIds: [], // 选中部门id
      selectList: [], // 选中部门
      treeData: []
    }
  },
  watch: {
    filterText(val) {
      this.$refs.tree?.filter(val)
    },
    async selectId(newVal) {
      this.selectIds = newVal
      if (!this.treeData.length) {
        await this.getTreeData()
      }
      // 根据选中的部门id获取完整的对象数组
      this.selectList = this.getSelectListByIds(newVal)
    },
    // deep: 1 对齐 Vue 2 语义：push / splice 等原地修改也要触发
    selectList: {
      handler() {
        if (this.isSite && this.selectList.length) {
          if (!this.is_mastartId) {
            this.is_mastartId = this.selectList[0].id
            this.selectList[0].is_mastart = true
          }
        }
      },
      deep: 1
    },
    value(newVal, oldValue) {
      this.selectList = newVal
      this.departmentIds = extractArrayIds(this.selectList)
      this.selectIds = this.findNamesByIds(this.treeData, this.departmentIds)
      this.$nextTick(() => this.$refs.tree?.setCheckedKeys(this.selectIds))
      if (this.isSite) {
        this.selectList.map((item) => {
          if (item.is_mastart) {
            this.is_mastartId = item.id
          }
        })
      }
    }
  },
  async mounted() {
    document.addEventListener('click', this.handleGlobalClick)
    // 判断是否有插槽内容
    if (this.$slots.custom) {
      this.isSlots = true
    }
    await this.getTreeData()
    if (this.value.length > 0) {
      this.selectList = this.value
      this.departmentIds = extractArrayIds(this.selectList)
      this.selectIds = this.findNamesByIds(this.treeData, this.departmentIds)
      this.$nextTick(() => this.$refs.tree?.setCheckedKeys(this.selectIds))

      if (this.isSite) {
        this.selectList.map((item) => {
          if (item.is_mastart) {
            this.is_mastartId = item.id
          }
        })
      }
    }
    if (this.selectId.length > 0) {
      this.selectList = this.getSelectListByIds(this.selectId)
    }
  },
  methods: {
    // 点击是否落在触发框或弹层内：Element Plus 的 popover 根节点是片段，$el 不能用来判断
    isInsidePopover(target) {
      const popper = this.$refs.treePopover?.popperRef
      return [popper?.triggerRef, popper?.contentRef].some((el) => el?.contains?.(target))
    },
    handleGlobalClick(event) {
      if (!this.isInsidePopover(event.target)) {
        this.showPopover = false
      }
    },

    // 根据选中的部门id获取完整的对象数组
    getSelectListByIds(ids) {
      if (!ids || ids.length === 0) return []

      const result = []
      const idSet = new Set(ids.map((id) => String(id)))

      const traverse = (nodes) => {
        for (const node of nodes) {
          if (idSet.has(String(node.id)) || idSet.has(String(node.value))) {
            result.push({
              id: node.id,
              value: node.value,
              label: node.label,
              name: node.label,
              pid: node.pid,
              path: node.path,
              is_mastart: node.is_mastart || false
            })
          }
          if (node.children && node.children.length > 0) {
            traverse(node.children)
          }
        }
      }

      traverse(this.treeData)
      return result
    },

    async handlePopoverShow() {
      await this.getTreeData()
      this.showPopover = true
    },
    handlePopoverHide() {
      this.showPopover = false
    },
    // 获取树形结构默认展开节点
    getRoleTreeRootNode(res) {
      if (!res || !res.length) return
      if (this.treeExpandData.includes(res[0].id)) return
      this.treeExpandData.push(res[0]?.id)
    },

    cardTag(index) {
      this.selectList.splice(index, 1)
      this.$emit('changeMastart', this.selectList)
    },

    // 设置部门
    isMastart(key) {
      if (key.is_mastart) {
      } else {
        this.is_mastartId = key.id
      }
      this.selectList.forEach((item) => {
        if (item.id == this.is_mastartId) {
          item.is_mastart = true
        } else {
          item.is_mastart = false
        }
      })
      this.$emit('changeMastart', this.selectList)
    },

    // 多选
    depCheck(data, status) {
      if (this.checkStrictly) {
        // 去除data对象里面的child字段和值
        const { children, ...rest } = data
        data = rest
      }
      if (this.isDisabledDepartment(data)) {
        return this.$message.warning('此部门已加入其他考勤组')
      }
      const arr = this.getDepartmentDataById(data, data.id)
      // 点击选中
      if (isInArray(status.checkedKeys, data.id)) {
        this.selectList.push(...arr)
        // 根据id过滤重复成员
        this.selectList = removeDuplicateObjects(this.selectList, 'id')
      } else {
        // 数组去差集
        this.selectList = getArrayDifference(this.selectList, arr, 'id')
      }
      setTimeout(() => {
        this.departmentIds = extractArrayIds(this.selectList, 'id')
        this.selectIds = this.findNamesByIds(this.treeData, this.departmentIds)
        this.$refs.tree?.setCheckedKeys(this.selectIds)
        this.$emit('changeMastart', this.selectList)
      }, 200)
    },

    filterNode(value, data) {
      if (!value) return true
      return data.label.indexOf(value) !== -1
    },
    //选择部门单选
    append(node, data) {
      if (this.isDisabledDepartment(data)) {
        return this.$message.warning('此部门已加入其他考勤组')
      }
      if (!this.onlyOne) {
        return false
      }
      if (this.departmentIds.some((id) => this.isSameId(id, data.id))) {
        return this.$message.warning('已选中该部门')
      }

      data.is_mastart = false
      this.selectList = [
        {
          id: data.id,
          value: data.value,
          label: data.label,
          name: data.label,
          is_mastart: false,
          pid: data.pid,
          path: data.path
        }
      ]
      this.departmentIds = extractArrayIds(this.selectList)
      this.$emit('changeMastart', this.selectList)
      this.$refs.treePopover.hide()
    },

    async getTreeData() {
      // 已有缓存时同步取用，避免多一个微任务导致回显时序变化
      const cached = this.organizationStore.departmentList
      const treeData = cached.length ? cached : await this.organizationStore.getDepartment()
      this.treeData = treeData || []
      this.getRoleTreeRootNode(this.treeData)
      this.selectIds = this.findNamesByIds(this.treeData, this.departmentIds)
    },

    isSameId(a, b) {
      return String(a) === String(b)
    },

    isDisabledDepartment(department) {
      return this.disabledList.some((id) => this.isSameId(id, department.id))
    },

    findNamesByIds(tree, id) {
      let ids = new Set(id.map((value) => String(value)))
      let result = []
      function traverse(node) {
        if (ids.has(String(node.id)) || ids.has(String(node.value))) {
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
    },
    /**
     * 递归获取某部门所有成员（包括部门自身和所有子部门、用户）
     * @param {Object} data  初始数据
     * @param {Number} deptId 部门id
     * @returns {*[]}
     */
    getDepartmentDataById(data, deptId) {
      let department = data
      if (department) {
        let members = []
        // 收集当前部门本身（去除children字段）
        const { children, ...deptInfo } = department
        members.push(deptInfo)

        // 递归函数，用于遍历子部门
        function traverseChildren(children) {
          if (!children) return
          children.forEach((child) => {
            // 收集子节点（去除children字段）
            const { children: grandChildren, ...childInfo } = child
            members.push(childInfo)
            // 递归处理子节点的children
            if (grandChildren && grandChildren.length > 0) {
              traverseChildren(grandChildren)
            }
          })
        }
        traverseChildren(department.children)
        return members
      }

      return []
    },
    // 获取部门
    getSubDepartmentsById(departmentsData, departmentId) {
      let subDepartments = []
      function traverseDepartments(departments) {
        for (const department of departments) {
          subDepartments.push({
            id: department.id,
            value: department.value,
            label: department.label,
            name: department.label,
            is_mastart: false,
            pid: department.pid,
            path: department.path
          })
          if (department.children && department.children.length > 0) {
            traverseDepartments(department.children)
          }
        }
      }
      traverseDepartments(departmentsData)

      return subDepartments
    }
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleGlobalClick)
  }
}
</script>
<style lang="scss" scoped>
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
    font-size: 13px;
    color: #303133;

    .iconwenjianjia {
      color: #1890ff;
      margin-right: 6px;
    }
    .avatar {
      display: inline-block;
      width: 20px;
      height: 20px;
      border-radius: 50%;
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
.mt3 {
  margin-top: 3px;
}
.main-department {
  // width: 80px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 14px;
  color: #1890ff;
  text-align: right;
}
.checkedColor {
  color: #909399 !important;
}
.flex-search {
  margin-top: 3px;
  display: flex;
  align-items: center;
  max-width: 80%;

  .left {
    max-width: 90%;
  }
  .right {
    margin-top: 3px;
    width: 10%;
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
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  color: #c0c4cc;
  display: inline-block;
  font-size: inherit;
  min-height: 32px;
  line-height: 30px;
  outline: none;
  font-size: 13px;
  padding: 0 10px;
  -webkit-transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  width: 100%;
  .el-tag.el-tag--info {
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 13px;
    color: #303133;
  }
}
.el-icon-arrow-down {
  font-weight: 400;
  position: absolute;
  right: 10px;
  top: 10px;
}

.label-text {
  display: inline-block;
  max-width: 150px;
}
.isChecked {
  color: #1890ff !important;
}
:deep(.el-popper) {
  padding: 0;
  margin-top: 5px;
}
.el-tag {
  margin-right: 10px;
}
</style>
<style>
.popover {
  padding: 0px !important;
}
</style>
