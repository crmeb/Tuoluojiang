<!-- 客户-选择客户标签弹窗组件 -->
<template>
  <el-dialog
      class="customer-list-label-dialog"
    title="选择标签"
    :append-to-body="true"
    v-model="dialogVisible"
    width="50%"
    :before-close="handleClose"
  >
    <div class="label-content">
      <div class="label-filter">
        <el-input v-model="searchName" placeholder="请输入客户标签" clearable style="width: 250px" @change="searchFn" />
        <el-select v-model="groupType" placeholder="全部" style="width: 160px" @change="handleGroupTypeChange">
          <el-option label="全部" value="" />
          <el-option v-for="item in LABEL_GROUP_TYPE_OPTIONS" :key="item.value" :label="item.label" :value="item.value" />
        </el-select>
      </div>
      <el-scrollbar class="label-scroll">
        <el-checkbox-group v-model="checkGroup">
          <template v-for="child in tableData" :key="child.id">
            <div class="box" v-if="child.children?.length">
              <div class="label-title">
                <span class="line" /> {{ child.name }}
                <el-button
                  type="primary" link
                  style="padding: 0"
                  v-if="config.label && config.label.length > 0"
                  @click="checkAll(child, child.child ? 'child' : 'children')"
                  >{{ child.checked ? '取消全选' : '全选' }}</el-button
                >
              </div>
              <el-checkbox-button
                v-for="item in child.children"
                fill="#1890FF"
                :key="item.id"
                :class="item.pid === 0 ? 'label-title' : ''"
                border
                :label="item.id" :value="item.id"
                @change="(val) => onChange(val, child, 'children')"
                >{{ item.name }}</el-checkbox-button
              >
            </div>
          </template>
        </el-checkbox-group>
      </el-scrollbar>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button type="primary" @click="handleConfirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { clientConfigLabelApi } from '@/api/enterprise.ts'
import { LABEL_GROUP_TYPE, LABEL_GROUP_TYPE_OPTIONS } from '@/constants/labelGroupType.ts'

export default {
  name: 'LabelDialog',
  emits: ['handleLabelConf', 'loaded'],
  props: {
    config: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      LABEL_GROUP_TYPE_OPTIONS,

      dialogVisible: false,
      isIndeterminate: true,
      labelWidth: 90,
      tableData: [],
      labelData: [],
      // 累积的全量标签映射（id -> 标签），保证按类型筛选后仍能取到已勾选但当前未展示的标签
      labelMap: {},
      arr: [],
      allData: [],
      where: {
        page: 0,
        limit: 0
      },
      searchName:'',
      // 标签组类型筛选，'' 表示全部（不传该参数）
      groupType: LABEL_GROUP_TYPE.MANUAL,

      pageArr: [1],
      total: 0,
      checkGroup: [],
      configDate: {},
      checkList: []
    }
  },
  watch: {
    config: {
      handler(nVal) {
        if (nVal.list && nVal.list.length > 0) {
          this.tableData = nVal.list
          this.collectLabelData()
        }

        if (nVal.label && nVal.label.length > 0) {
          this.checkGroup = []
          nVal.label.map((value) => {
            this.checkGroup.push(value.id)
          })
          const checkSet = new Set(nVal.label.map((value) => value.id))

          this.tableData.forEach((item) => {
            item.checked = item.children.every((v) => checkSet.has(v.id))
          })
        } else {
          this.checkGroup = []
        }
        this.checkGroup = this.checkGroup.map(Number)
      },
      deep: true
    }
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    searchFn() {
      this.tableData = []
      if (this.searchName) {
        // 使用 Set 来存储已添加的父项 ID，避免重复
        const addedParentIds = new Set()
        
        this.labelData.forEach((value) => {
          if(value.children && value.children.length > 0) {
            // 检查当前父项是否有子项匹配搜索条件
            const hasMatchingChild = value.children.some((val) => {
              return val.name.indexOf(this.searchName) > -1
            })
            
            // 如果有匹配的子项且父项尚未添加，则添加到结果中
            if (hasMatchingChild && !addedParentIds.has(value.id)) {
              this.tableData.push(value)
              addedParentIds.add(value.id)
            }
          }
        })
      } else {
        this.tableData = this.labelData
      }
    },
    onChange(val, parent, child) {
      if (val) {
        parent.checked = parent[child].every((v) => {
          return this.checkGroup.indexOf(v.id) > -1
        })
      } else {
        parent.checked = false
      }
    },
    checkAll(item, child) {
      if (item.checked) {
        item[child].forEach((v) => {
          if (this.checkGroup.indexOf(v.id) > -1) {
            this.checkGroup.splice(this.checkGroup.indexOf(v.id), 1)
          }
        })
        item.checked = false
        return
      }
      item[child].forEach((v) => {
        if (this.checkGroup.indexOf(v.id) === -1) {
          this.checkGroup.push(v.id)
        }
      })
      item.checked = true
    },

    // 由 tableData 展开出扁平标签数组，并累积进全量标签映射
    collectLabelData() {
      this.labelData = []
      ;(this.tableData || []).forEach((value) => {
        if (value.children && value.children.length > 0) {
          this.labelData.push(value)
          value.children.forEach((val) => {
            this.labelData.push(val)
          })
        }
      })
      this.labelData.forEach((item) => {
        this.labelMap[item.id] = item
      })
    },
    // 列表
    getTableData() {
      if (this.tableData.length > 0) return false
      return this.fetchLabels()
    },
    // 请求标签列表，全部类型不传 group_type
    fetchLabels() {
      const data = {
        page: this.where.page,
        limit: this.where.limit
      }
      if (this.groupType !== '') {
        data.group_type = this.groupType
      }

      return clientConfigLabelApi(data).then((res) => {
        this.tableData = res.data.list || []
        this.total = res.data.count
        this.collectLabelData()
        // 抛出全量标签数据，供只拿到标签 id 的调用方回显标签名称
        this.$emit('loaded', this.labelData)
      })
    },
    // 切换标签组类型：重新拉取列表，已有搜索词时再叠加本地过滤
    handleGroupTypeChange() {
      this.fetchLabels().then(() => {
        if (this.searchName) this.searchFn()
      })
    },
    handleOpen() {
      this.dialogVisible = true
      this.tableData.forEach((v) => {
        v.checked = false
      })
    },
    handleClose() {
      this.dialogVisible = false
    },
    handleConfirm() {
      // 从累积的全量标签映射中取，避免按类型筛选后丢失已勾选但当前未展示的标签
      this.arr = this.checkGroup.map((id) => this.labelMap[id]).filter(Boolean)
      this.dialogVisible = false
      this.arr = this.arr.filter((obj, index) => this.arr.findIndex((item) => item.id === obj.id) === index)
      this.configDate = {
        data: this.arr,
        type: this.config.edit
      }

      this.$emit('handleLabelConf', this.configDate)
    }
  }
}
</script>

<style scoped lang="scss">
:deep(.el-scrollbar__wrap) {
  overflow-x: hidden;
}
.label-content {
  height: 420px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.label-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  // 与下方标签列表拉开间距，避免列表滚动时内容紧贴搜索区
  flex-shrink: 0;
  padding-bottom: 16px;
}
// 撑满搜索区之外的剩余高度，取代固定的 calc 计算
.label-scroll {
  flex: 1;
  min-height: 0;
}
.label-title {
  font-size: 14px;
  display: flex;
  align-items: center;
  font-family: PingFangSC-Semibold, PingFang SC;
  font-weight: 600;
  color: #333333;
  margin-bottom: 6px;

  position: relative;
}

.box {
  margin-top: 30px;
  &:first-child {
    margin-top: 14px;
  }
}

.line {
  display: inline-block;
  width: 4px;
  height: 14px;
  // margin-top: 5px;
  border-left: 2px solid #1890ff;
  margin-right: 6px;
}

:deep(.el-checkbox-button.is-checked .el-checkbox-button__inner) {
  box-sizing: border-box !important;
  background: rgba(24, 144, 255, 0.08);
  border-radius: 4px;
  border: 1px solid #1890ff !important;
  box-shadow: none;
  font-weight: 400;
  color: #1890ff;
  margin: 10px 10px 0px 0px;
}

:deep(.el-checkbox-button .el-checkbox-button__inner) {
  box-sizing: border-box !important;
  background: #f2f6fc;
  border-radius: 4px;
  margin: 10px 10px 0px 0px;
  border: 1px solid transparent;
  // Element Plus 用 outline 画按钮边框，element-ui 没有；字号与内边距按 element-ui 的 small 尺寸
  outline: none;
  font-size: 12px;
  font-weight: 500;
  padding: 9px 15px;
}
:deep(.el-checkbox-button .el-checkbox-button__inner:hover) {
  background-color: rgba(24, 144, 255, 0.08) !important;
}

:deep(.el-checkbox__label) {
  padding-left: 6px;
}

// 原 type="text" 按钮在 Element Plus 下是 link 按钮
:deep(.el-button.is-link) {
  font-size: 13px;
  margin-left: 4px;
}
.left {
 font-family: PingFang SC, PingFang SC;
font-weight: 400;
font-size: 12px;
color: #909399;
margin-right: 10px;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.customer-list-label-dialog .el-dialog__header {
  border-bottom: 1px solid #d8d8d8;
}
.el-dialog.customer-list-label-dialog {
  border-radius: 6px;
}
</style>
