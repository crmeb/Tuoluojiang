<!-- 客户群聊弹窗 -->

<template>
  <el-dialog title="选择群聊弹窗" v-model="dialogTableVisible" width="760px">
    <el-input
      placeholder="请输入群聊名称"
      v-model="where.name"
      @change="getList"
      style="width: 250px"
      class="mr10"
    >
    </el-input>

    <div class="tips mb10 mt10">
      共有{{ total }}个群聊<template v-if="max">，最多可选择{{ max }}个</template>
    </div>
    <div>
      <el-table
        ref="groupTableRef"
        :data="tableData"
        :row-key="getRowKey"
        style="width: 100%"
        height="500px"
        @selection-change="handleSelectionChange"
      >
        <!-- reserve-selection 配合 row-key，保证翻页 / 搜索后已选群聊不丢失 -->
        <el-table-column type="selection" width="55" :reserve-selection="true" :selectable="checkSelectable">
        </el-table-column>
        <el-table-column property="name" label="群聊名称" width="150">
          <template #default="scope">
            {{ scope.row.name || '--' }}
          </template>
        </el-table-column>
        <el-table-column property="admin.name" label="群主"> </el-table-column>
        <el-table-column property="member_num" label="群人数"></el-table-column>
        <el-table-column property="retreat_group_num" label="退群人数"></el-table-column>
        <el-table-column property="group_create_time" label="创建时间" width="200"></el-table-column>
      </el-table>
      <el-pagination
        :page-size="where.limit"
        :current-page="where.page"
        :total="total"
        @current-change="pageChange"
        layout="total, prev, pager, next"
      />
    </div>

    <template #footer>
      <span>
        <el-button @click="handleClose">取消</el-button>
        <el-button type="primary" @click="handleConfirm">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>
<script>
import { getWorkMassGroupChat, getGroupChat } from '@/api/weCom.ts'

// type: 1-群聊群发 2-自动拉群
const GROUP_CHAT_API_MAP = {
  1: getWorkMassGroupChat,
  2: getGroupChat
}
export default {
  emits: ['selectGroups'],
  name: 'groupChat',
  props: {
    // 最多可选群聊数，0 表示不限制
    max: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      dialogTableVisible: false,
      tableData: [],
      selectValue: [],
      revertingSelection: false, // 超限回退中，用于屏蔽级联的 selection-change
      total: 0,
      type: 1,
      ids: [], //群主id
      where: { page: 1, limit: 10, admin_id: '', name: '' }
    }
  },

  methods: {
    // selected：调用方已选中的群聊，用于打开弹窗时回显勾选
    openBox(ids, type = 1, selected = []) {
      this.type = type
      this.where.admin_id = ids || ''
      this.where.page = 1
      this.where.name = ''
      this.selectValue = Array.isArray(selected) ? selected.slice() : []
      this.dialogTableVisible = true
      // 弹窗内容懒渲染，等表格挂载后再回填选中项，随后拉取列表
      this.$nextTick(() => {
        this.restoreSelection()
        this.getList()
      })
    },

    getRowKey(row) {
      return row.chat_id || row.id
    },

    // 把外部已选群聊写入表格 selection；列表加载后 el-table 会按 row-key 匹配上当前页的行并显示勾选
    restoreSelection() {
      const table = this.$refs.groupTableRef
      if (!table) return
      const rows = this.selectValue.slice() // clearSelection 会清空 selectValue，先取副本
      table.clearSelection()
      rows.forEach((row) => {
        if (this.getRowKey(row)) table.toggleRowSelection(row, true)
      })
    },

    // 已达上限时禁用未选中的行，只保留取消勾选的能力
    checkSelectable(row) {
      if (!this.max || this.selectValue.length < this.max) return true
      const key = this.getRowKey(row)
      return this.selectValue.some((item) => this.getRowKey(item) === key)
    },

    handleSelectionChange(val) {
      // 回退过程中每次 toggle 都会回调本方法，忽略以免重复提示
      if (this.revertingSelection) return
      // 兜底拦截超限（如表头全选一次选中整页），多出的行逐个取消
      if (this.max && val.length > this.max) {
        const overflow = val.slice(this.max)
        this.selectValue = val.slice(0, this.max)
        this.$message.warning(`最多只能选择${this.max}个群聊`)
        this.revertingSelection = true
        this.$nextTick(() => {
          const table = this.$refs.groupTableRef
          if (table) overflow.forEach((row) => table.toggleRowSelection(row, false))
          this.revertingSelection = false
        })
        return
      }
      this.selectValue = val
    },
    handleConfirm() {
      // 传副本，避免父组件持有表格内部 selection 数组的引用
      this.$emit('selectGroups', this.selectValue.slice())
      this.handleClose()
    },
    pageChange(page) {
      this.where.page = page
      this.getList()
    },
    handleClose() {
      this.dialogTableVisible = false
    },

    getList() {
      const api = GROUP_CHAT_API_MAP[this.type] || getGroupChat
      api(this.where).then(({ data }) => {
        this.tableData = data?.list || []
        this.total = data?.count || 0
      })
    }
  }
}
</script>
<style scoped lang="scss">
.tips {
  font-size: 12px;
  color: #909399;
}
</style>
