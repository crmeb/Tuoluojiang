<template>
  <div>
    <el-table
      :data="tableData"
      v-loading="loading"
      element-loading-text="拼命加载中"
      element-loading-background="rgba(255, 255, 255, 0.7)"
      :height="height"
      ref="elTable"
      @selection-change="selectionChange"
      v-bind="$attrs"
      v-on="$listeners"
      :key="tableKey"
    >
      <template v-for="(col, index) in tableOptions">
        <el-table-column v-if="col.type === 'index'" type="index" width="50" label="序号" />

        <!-- 自定义插槽 -->
        <el-table-column v-else-if="col.type === 'slot'" :width="col.width || null" :label="col.label">
          <template #default="{ row }">
            <slot :row="row" :name="col.name"></slot>
          </template>
        </el-table-column>

        <!-- 操作 -->
        <el-table-column
          v-else-if="col.slot === 'options'"
          :width="col.width || null"
          :label="col.label"
          :fixed="col.fixed || null"
        >
          <template #default="{ row }">
            <slot :row="row" name="options"></slot>
          </template>
        </el-table-column>

        <el-table-column
          v-else
          :key="index"
          :width="col.width || null"
          :label="col.label"
          :fixed="col.fixed || null"
          v-bind="col"
          :min-width="col.width || null"
          :show-overflow-tooltip="true"
        >
          <template #default="{ row }">
            <span>{{ row[col.prop] || '--' }}</span>
          </template>
        </el-table-column>
      </template>
    </el-table>
    <div class="page-fixed" v-if="isShowPagination">
      <el-pagination
        :current-page="where.page"
        :page-size="where.limit"
        :page-sizes="[15, 20, 30]"
        :total="total"
        layout="total,sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: 'oaTable',
  props: {
    tableData: {
      type: Array,
      default: () => []
    },

    tableOptions: {
      type: Array,
      default: () => []
    },

    loading: {
      type: Boolean,
      default: () => true
    },
    total: {
      type: Number,
      default: () => 0
    },

    height: {
      type: [String, Number],
      default: () => null
    },

    fixed: {
      type: [String],
      default: () => null
    },

    showSearch: {
      type: Boolean,
      default: () => false
    },

    pageData: {
      type: Object,
      default: () => null
    },

    isShowPagination: {
      type: Boolean,
      default: () => true
    },

    pagination: {
      type: [Object, Boolean],
      default: () => false
    }
  },
  data() {
    return {
      tableKey: false,
      where: {
        page: 1,
        limit: 15
      }
    }
  },

  methods: {
    selectionChange(selection) {
      this.$emit('selectionChange', selection)
    },
    handleSizeChange(val) {
      this.$emit('handleSizeChange', val)
    },
    handleCurrentChange(val) {
      this.$emit('handleCurrentChange', val)
    }
  }
}
</script>
