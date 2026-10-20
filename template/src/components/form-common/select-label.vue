<!-- @FileDescription: 下拉选择标签组件 -->
<template>
  <div class="">
    <el-popover :placement="placement ? placement : 'bottom-start'" trigger="manual" :visible="showPopover"
      popper-class="popover" ref="treePopover">
      <div class="tree-box" id="treePopover">
        <div class="input">
          <el-input prefix-icon="el-icon-search" placeholder="请输入标签搜索" v-model="filterText">
          </el-input>
        </div>
        <el-tree highlight-current :props="props" :indent="4" :data="treeData" ref="tree" node-key="id"
          class="ml20 mr20 mt10 mb10" :filter-node-method="filterNode">
          <template #default="{ node, data }">
            <div class="custom-tree-node" @click.stop="selectFn(node, data)">
              <div class="flex-box">
                <span class="over-text" :class="{
                  isChecked: labelIds.includes(data[valType])
                }" :default-expanded-keys="treeExpandData">{{ node.label }}</span>

                <span class="all-text" v-if="data.pid == 0" @click.stop="selectAllFn(node, data)">{{
                  allIds.includes(data[valType]) ? '取消全选' : '全选'
                }}</span>
                <span v-if="data.pid != 0 && labelIds.includes(data[valType])" class="el-icon-check"></span>
              </div>
            </div>
          </template>
        </el-tree>
        <div class="btn">
          <span class="left" @click="handlePopoverHide">取消</span>
          <span class="right" @click="submitFn">确定</span>
        </div>
      </div>
      <!-- 标签数据 -->
      <template #reference>
        <slot v-if="$slots.custom" name="custom"></slot>
        <div class="select plan-footer-one mr10" ref="select" v-else-if="!slotType"
          @click.stop="handlePopoverShow">
          <div v-if="selectList && selectList.length == 0" class="placeholder flex-between">
            <span>{{ placeholder }}</span>
          </div>

          <div v-if="selectList.length > 0 && !isSearch">
            <span v-for="(item, index) in selectList" :key="index"
              class="el-tag el-tag--small el-tag--info el-tag--light mr10 mb4">
              {{ item.name }}
              <i class="el-tag__close el-icon-close" @click.stop="cardTag(index, item[valType])" />
            </span>
          </div>
          <!--
            搜索态下不再固定显示“第一个标签 + 剩余数量”。
            这里改为渲染“当前可见标签列表 + 可能存在的 +N 汇总标签”，
            具体显示几个标签由脚本根据容器实际宽度动态计算。
          -->
          <div v-if="selectList.length > 0 && isSearch" ref="searchTagDisplay" class="search-tag-display">
            <div
              v-for="(item, index) in visibleSearchTags"
              :key="`${item[valType]}_${index}`"
              class="el-tag el-tag--small el-tag--info el-tag--light search-tag-item"
              @click.stop=""
            >
              <span class="line1">{{ item.name }}</span>
              <span class="el-tag__close el-icon-close" @click.stop="cardTag(index, item[valType])" />
            </div>
            <div
              v-if="hiddenSearchTagCount > 0"
              class="el-tag el-tag--small el-tag--info el-tag--light search-tag-summary"
              @click.stop=""
            >
              +{{ hiddenSearchTagCount }}
            </div>
          </div>
          <!--
            隐藏测量容器：
            这里会把所有已选标签和一个“+N”示例标签提前渲染到不可见区域，
            仅用于读取真实宽度，避免靠字符数估算导致展示不准。
          -->
          <div v-if="selectList.length > 0 && isSearch" class="search-tag-measure" aria-hidden="true">
            <div
              v-for="(item, index) in selectList"
              :key="`measure_${item[valType]}_${index}`"
              ref="measureTags"
              class="el-tag el-tag--small el-tag--info el-tag--light search-tag-item"
            >
              <span class="line1">{{ item.name }}</span>
              <span class="el-tag__close el-icon-close"></span>
            </div>
            <div ref="summaryMeasure" class="el-tag el-tag--small el-tag--info el-tag--light search-tag-summary">+0</div>
          </div>
          <span class="el-icon-arrow-down" v-if="selectList.length == 0"></span>
          <span class="el-icon-error" v-else @click.stop="clearSelect"></span>
        </div>
        <div v-else @click.stop="handlePopoverShow">设置标签</div>
      </template>
    </el-popover>
  </div>
</template>
<script>
import { clientConfigLabelApi } from '@/api/enterprise.ts'
import { extractArrayIds, isInArray, removeDuplicateObjects, getArrayDifference } from '@/libs/public.ts'
export default {
  name: '',
  emits: ['submit', 'handleLabelConf', 'expand-change'],
  props: {
    // 选中的标签数据
    value: {
      type: Array,
      default: () => {
        return []
      }
    },
    placement: {
      type: String,
      default: 'bottom-start'
    },
    labelList: {
      type: Array,
      default: () => {
        return []
      }
    },
    ids: {
      // 客户管理—批量设置标签表格id集合
      type: Array,
      default: () => {
        return null
      }
    },
    list: {
      type: Array,
      default: () => {
        return []
      }
    },
    placeholder: {
      type: String,
      default: '请选择标签'
    },

    isSearch: {
      type: Boolean,
      default: false
    },
    slotType: {
      type: String,
      default: ''
    },
    props: {
      type: Object,
      default: () => {
        return {
          children: 'children',
          label: 'name'
        }
      }
    }
  },
  data() {
    return {
      valType: 'id',
      filterText: '',
      allIds: [],
      expandData: [],
      isSlots: false,
      showPopover: false,
      treeExpandData: [],
      labelIds: [], // 选中标签id
      selectList: [], // 选中标签数据
      treeData: [],
      visibleSearchTagCount: 0,
      hiddenSearchTagCount: 0,
      tagResizeObserver: null
    }
  },
  computed: {
    // 根据宽度计算结果裁剪出当前真正需要展示的标签列表
    visibleSearchTags() {
      return this.selectList.slice(0, this.visibleSearchTagCount)
    }
  },
  watch: {
    filterText(val) {
      this.$refs.tree.filter(val)
    },
    value: {
      handler(newVal, oldValue) {
        if (newVal.length > 0) {
          this.labelIds = newVal.map(Number)
          if (newVal.length > 0) {
            this.getTreeData()
          } else {
            this.allIds = []
          }
        }
      },
      deep: true
    },
    labelList(newVal, oldValue) {
      if (this.labelList.length > 0) {
        this.selectList = newVal
        this.labelIds = extractArrayIds(this.selectList, this.valType)
      } else {
        this.selectList = []
        this.labelIds = []
        this.allIds = []
      }
    },
    labelIds(val) {
      this.allIdsChange()
    },
    selectList: {
      // 选中标签增删后，等待 DOM 更新完成再重新计算可见数量
      handler() {
        this.syncSearchTagDisplay()
      },
      deep: true
    }
  },

  mounted() {
    if (this.$slots.custom) {
      this.isSlots = true
    }
    if (this.list.length > 0) {
      this.valType = 'id'
      this.treeData = this.list
      this.formatTreeData()
    } else {
      this.valType = 'id'
    }
    if (this.labelList.length > 0) {
      this.selectList = this.labelList
      this.labelIds = extractArrayIds(this.selectList, this.valType)
    }
    if (this.value.length > 0) {
      this.labelIds = this.value.map(Number)
      this.getTreeData()
    } else {
      this.labelIds = []
      this.selectList = []
      this.allIds = []
    }
    window.addEventListener('resize', this.handleWindowResize)
    this.$nextTick(() => {
      // 首次挂载后做一次计算，保证回显数据也能按宽度正确展示
      this.syncSearchTagDisplay()
      this.initTagResizeObserver()
    })
  },
  beforeUnmount() {
    // 组件销毁时释放监听，避免重复注册和潜在内存泄漏
    window.removeEventListener('resize', this.handleWindowResize)
    if (this.tagResizeObserver) {
      this.tagResizeObserver.disconnect()
      this.tagResizeObserver = null
    }
  },
  methods: {
    // 浏览器窗口尺寸变化时，搜索框总宽度可能变化，需要重新计算
    handleWindowResize() {
      this.updateVisibleSearchTags()
    },

    initTagResizeObserver() {
      // 仅监听 window.resize 不够，因为表单项本身可能因父容器折行、布局切换而变窄/变宽
      // 这里额外监听组件根容器尺寸变化，保证在页面布局变动时同步更新展示结果
      if (typeof ResizeObserver === 'undefined' || !this.$refs.select) return
      if (this.tagResizeObserver) {
        this.tagResizeObserver.disconnect()
      }
      this.tagResizeObserver = new ResizeObserver(() => {
        this.updateVisibleSearchTags()
      })
      this.tagResizeObserver.observe(this.$refs.select)
    },

    syncSearchTagDisplay() {
      // 只在搜索态下做宽度计算，普通态仍保持原来的标签全部渲染逻辑
      if (!this.isSearch) return
      this.$nextTick(() => {
        this.initTagResizeObserver()
        this.updateVisibleSearchTags()
      })
    },

    getSummaryTagWidth(hiddenCount) {
      // “+N” 的宽度会随着数字位数变化，例如 +1 / +10 / +100 宽度都不同
      // 因此这里直接复用隐藏测量节点，写入真实文本后再读取实际宽度
      const summaryEl = this.$refs.summaryMeasure
      if (!summaryEl) return 0
      summaryEl.textContent = `+${hiddenCount}`
      return Math.ceil(summaryEl.offsetWidth)
    },

    updateVisibleSearchTags() {
      /*
        搜索态标签展示策略：
        1. 先读取容器可用宽度。
        2. 再读取每个标签的真实渲染宽度。
        3. 从前往后尝试塞入更多标签。
        4. 如果后面还有未展示标签，则预留一个 “+N” 的空间。
        5. 最终得到“能完整显示的标签数量”和“剩余隐藏数量”。

        这样做可以保证：
        - 空间足够时，尽可能完整展示更多标签。
        - 空间不足时，不会只显示第一个标签，而是尽量多显示几个，再补一个 +N。
      */
      if (!this.isSearch) return
      if (!this.selectList.length) {
        this.visibleSearchTagCount = 0
        this.hiddenSearchTagCount = 0
        return
      }

      const displayEl = this.$refs.searchTagDisplay
      let measureTags = this.$refs.measureTags || []
      if (!displayEl || displayEl.clientWidth <= 0 || !measureTags.length) return

      measureTags = Array.isArray(measureTags) ? measureTags : [measureTags]
      const tagWidths = measureTags.map(item => Math.ceil(item.offsetWidth))
      const availableWidth = displayEl.clientWidth
      const tagGap = 4
      const totalWidth = tagWidths.reduce((sum, width, index) => {
        return sum + width + (index > 0 ? tagGap : 0)
      }, 0)

      // 如果所有标签本来就能放下，则直接全部展示，不需要 +N
      if (totalWidth <= availableWidth) {
        this.visibleSearchTagCount = this.selectList.length
        this.hiddenSearchTagCount = 0
        return
      }

      let usedWidth = 0
      let visibleCount = 0
      for (let index = 0; index < tagWidths.length; index++) {
        const currentWidth = tagWidths[index]
        const nextWidth = visibleCount === 0 ? currentWidth : usedWidth + tagGap + currentWidth
        const hiddenCount = tagWidths.length - index - 1
        const summaryWidth = hiddenCount > 0 ? this.getSummaryTagWidth(hiddenCount) : 0
        const requiredWidth = hiddenCount > 0 ? nextWidth + tagGap + summaryWidth : nextWidth

        // 当前标签和后续需要的 +N 都能放下，才把该标签记为可见
        if (requiredWidth <= availableWidth) {
          usedWidth = nextWidth
          visibleCount = index + 1
          continue
        }
        break
      }

      // 至少保留 1 个标签位，避免所有标签都被折叠成只剩 +N
      this.visibleSearchTagCount = Math.max(1, visibleCount)
      this.hiddenSearchTagCount = Math.max(0, this.selectList.length - this.visibleSearchTagCount)
    },
    // 获取树形结构默认展开节点
    getRoleTreeRootNode(res) {
      if (!Array.isArray(res) || res.length === 0) return
      this.treeExpandData.push(res[0][this.valType])
    },

    submitFn() {
      let data = {
        ids: this.labelIds,
        list: this.selectList
      }
      this.$emit('submit', data)
      this.$emit('handleLabelConf', data)
      this.handlePopoverHide()
    },


    findNamesByIds(tree, id) {

      const targetIds = new Set(id.map(Number))
      const result = []

      // 深度优先遍历
      const dfs = (node) => {
        if (targetIds.has(node.id)) result.push(node)
        if (node.children?.length) {
          node.children.forEach(dfs)
        }
      }

      tree.forEach(dfs)
      return result
    },
    cardTag(index, id) {
      this.selectList.splice(index, 1)
      this.labelIds = this.labelIds.filter((item) => item != id)
      let data = {
        ids: this.labelIds,
        list: this.selectList
      }
      this.$emit('submit', data)
      this.$emit('handleLabelConf', data)
    },
    allIdsChange() {
      this.treeData.map((item) => {
        let arr = extractArrayIds(item[this.props.children], this.valType)
        if (this.isContained(this.labelIds, arr)) {
          this.allIds.push(item[this.valType])
        } else {
          this.allIds = this.allIds.filter((item) => item != item[this.valType])
        }
      })
    },

    filterNode(value, data) {
      if (!value) return true
      return data.name.indexOf(value) !== -1
    },

    handleGlobalClick(e) {
      let treePopover = document.getElementById('treePopover')
      if (treePopover) {
        if (!treePopover.contains(e.target)) {
          let data = {
            ids: this.labelIds,
            list: this.selectList
          }
          this.$emit('submit', data)
          this.$emit('handleLabelConf', data)
          this.handlePopoverHide()
        }
      }
    },
    handlePopoverShow() {
      if (this.ids && this.ids.length == 0) {
        this.$message.error('至少选一项')
        return false
      }
      if (this.list.length == 0 && this.treeData.length == 0) {
        this.getTreeData()
      } else {
        if (this.list.length > 0) {
          this.valType = 'id'
          this.treeData = this.list
          this.formatTreeData()
        }
      }
      this.showPopover = true
      document.addEventListener('click', this.handleGlobalClick)
    },
    handlePopoverHide() {
      this.allIds = []
      this.showPopover = false
      document.removeEventListener('click', this.handleGlobalClick)
    },
    // 清空
    clearSelect() {
      this.selectList = []
      this.labelIds = []
      this.allIds = []
      let data = {
        ids: [],
        list: []
      }

      this.$emit('submit', data)
      this.$emit('handleLabelConf', data)
    },

    // 判断一个数组里面是否完全包含另一个数组
    // 定义函数
    isContained(a, b) {
      // a和b其中一个不是数组，直接返回false
      if (!(a instanceof Array) || !(b instanceof Array)) return false
      const len = b.length
      // a的长度小于b的长度，直接返回false

      if (a.length < len) return false
      for (let i = 0; i < len; i++) {
        // 遍历b中的元素，遇到a没有包含某个元素的，直接返回false
        if (!a.includes(b[i])) return false
      }
      // 遍历结束，返回true
      return true
    },
    toggleExpand(node) {
      node.expanded = !node.expanded
      this.$emit('expand-change', node)
    },

    // 选择标签单选
    selectFn(node, data) {
      if (node.parent && !node.isLeaf) {
        this.toggleExpand(node)
      }
      if (data.pid == 0) return false
      if (isInArray(this.labelIds, data[this.valType])) {
        this.labelIds = this.labelIds.filter((item) => item != data[this.valType])
        this.selectList = this.selectList.filter((item) => item[this.valType] != data[this.valType])
        // this.confirmData()
        return false
      } else {
        this.selectList.push(data)
        this.labelIds = extractArrayIds(this.selectList, this.valType)
      }
      // this.confirmData()
    },

    //  选择标签多选
    selectAllFn(node, data) {
      const children = data[this.props.children]
      if (!children || children.length == 0) {
        this.$message.error('暂无子级标签可选择')
        return false
      }
      if (isInArray(this.allIds, data[this.valType])) {
        // 取消全选
        this.allIds = this.allIds.filter((item) => item != data[this.valType])
        this.selectList = getArrayDifference(this.selectList, data[this.props.children])
        this.labelIds = extractArrayIds(this.selectList, this.valType)
        // this.confirmData()
        return false
      }

      // 全选
      this.allIds.push(data.id)
      if (data[this.props.children].length > 0) {
        this.labelIds = []
        data[this.props.children].map((item) => {
          this.selectList.push(item)
        })
        this.selectList = removeDuplicateObjects(this.selectList, this.valType)
        this.labelIds = extractArrayIds(this.selectList, this.valType)
      }
    
    },

    getTreeData() {
      if (this.treeData.length > 0) {
        if (this.value.length > 0) {
          this.selectList = this.findNamesByIds(this.treeData, this.value)
        }
        return false
      }

      let data = {
        page: 0,
        limit: 0
      }
      clientConfigLabelApi(data).then((res) => {
        this.treeData = res.data.list || []
        this.formatTreeData()
        this.getRoleTreeRootNode(this.treeData)
        if (this.value.length > 0) {
          this.selectList = this.findNamesByIds(this.treeData, this.labelIds)
        }
      })
    },

    formatTreeData() {
      if (!Array.isArray(this.treeData)) {
        this.treeData = []
        return
      }
      // 只保留存在子级标签的分组；外部传入的一级扁平选项没有 children，直接保留原数据
      const arr = this.treeData.filter((item) => Array.isArray(item[this.props.children]) && item[this.props.children].length > 0)
      this.treeData = arr.length > 0 ? arr : this.treeData
    }
  }
}
</script>
<style scoped lang="scss">
.input {
  margin-top: 12px;
  margin-bottom: 12px;
  margin: 0 20px;
}

.tree-box {
  padding-top: 20px;
  width: 242px;
  min-height: 150px;

  .custom-tree-node {
    position: relative;
    width: calc(100% - 40px);

    .flex-box {
      width: 100%;
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 14px;
      color: #303133;
      display: flex;
      align-items: center;

      span:first-of-type {
        display: inline-block;
        max-width: 80%;
      }

      .all-text {
        font-size: 13px;
        color: #1890ff;
        cursor: pointer;
        margin-left: 10px;
      }

      .el-icon-check {
        font-size: 18px;
        position: absolute;
        right: 0px;
        color: #1890ff;
      }
    }
  }

  :deep(.el-tree) {
    max-height: 350px;
    overflow-y: auto;
    scrollbar-width: none;
    /* firefox */
    -ms-overflow-style: none;

    /* IE 10+ */
    .is-checked {
      color: #1890ff !important;
      cursor: pointer;
    }

    .el-tree-node__content {
      height: 32px;
      line-height: 32px;
    }
  }
}

:deep(.el-tree-node__content>.el-tree-node__expand-icon) {
  padding-left: 0px;
}

.mb4 {
  margin-bottom: 4px;
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
  display: flex;
  align-items: center;
  outline: none;
  font-size: 13px;
  padding: 0 15px;
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

.btn {
  height: 40px;
  border-top: 1px solid #dcdfe6;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 14px;
  color: #303133;

  .left {
    cursor: pointer;
    height: 40px;
    width: 50%;
    line-height: 40px;
    text-align: center;
    border-right: 1px solid #dcdfe6;
  }

  .right {
    cursor: pointer;
    text-align: center;
    height: 40px;
    line-height: 40px;
    width: 50%;
  }
}

.isChecked {
  color: #1890ff !important;
}

.el-icon-arrow-down {
  font-weight: 400;
  position: absolute;
  right: 10px;
}

.el-icon-error {
  font-weight: 400;
  position: absolute;
  right: 10px;
}

:deep(.el-popper) {
  margin-top: 5px;
}

.flex-box {
  display: flex;
  align-items: center;
}

.search-tag-display {
  /* 搜索态标签展示区：单行排列，超出部分由脚本控制，不依赖浏览器自动换行 */
  display: flex;
  align-items: center;
  gap: 4px;
  width: 100%;
  min-width: 0;
  padding-right: 18px;
  overflow: hidden;
}

.search-tag-item {
  /* 标签允许在 flex 容器内收缩，但文本是否省略由内部 line1 控制 */
  display: inline-flex;
  align-items: center;
  min-width: 0;
  flex: 0 1 auto;
  margin-right: 0;
}

.search-tag-summary {
  /* +N 不参与伸缩，避免被压缩后数字显示异常 */
  flex: 0 0 auto;
  margin-right: 0;
}

.search-tag-measure {
  /* 隐藏但保留真实布局能力，用来测量标签和 +N 的最终宽度 */
  position: absolute;
  left: 0;
  top: -9999px;
  z-index: -1;
  visibility: hidden;
  display: flex;
  gap: 4px;
  white-space: nowrap;
  pointer-events: none;
}

.search-tag-measure .search-tag-item,
.search-tag-measure .search-tag-summary {
  flex: 0 0 auto;
}

.search-tag-item .line1 {
  /* 文本本身允许省略，避免单个超长标签把整个输入区域顶爆 */
  flex: 1 1 auto;
  min-width: 0;
  max-width: none;
}

.search-tag-item .el-tag__close {
  flex: 0 0 auto;
}

.line1 {
  display: inline-block;
  max-width: 80%;
  overflow: hidden;
  text-overflow: ellipsis; //文本溢出显示省略号
  white-space: nowrap; //文本不会换行
}
</style>
<style>
.popover {
  padding: 0px 12px 18px 12px;
}
</style>
