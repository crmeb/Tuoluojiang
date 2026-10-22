<template>
  <el-dialog title="选择素材弹窗" :visible.sync="dialogTableVisible">
    <div class="flex mb10">
      <el-input
        v-model="where.name"
        size="small"
        placeholder="请输入素材内容"
        style="width: 200px"
        class="mr10"
        @change="getTableData()"
      ></el-input>
      <el-select
        v-model="where.group_id"
        placeholder="请选择素材分类"
        size="small"
        style="width: 200px"
        @change="getTableData()"
      >
        <el-option v-for="(item, index) in options" :value="item.id" :label="item.name" :key="index"></el-option>
      </el-select>
      <el-tooltip content="重置搜索条件" effect="dark" placement="top">
        <div class="reset ml10" @click="reset"><i class="iconfont iconqingchu"></i></div>
      </el-tooltip>
    </div>
    <div class="tips">共有{{ total }}篇素材</div>
    <el-table :data="gridData" height="400px">
      <el-table-column width="80">
        <template #default="scope">
          <span class="iconfont icondangqian" v-if="activeId == scope.row.id"></span>
          <span class="iconfont iconweidadao" v-else @click="selectIdFn(scope.row)"></span>
        </template>
      </el-table-column>
      <el-table-column property="id" label="ID" width="150"></el-table-column>
      <el-table-column property="content" label="素材内容"></el-table-column>
    </el-table>
    <el-pagination
      :page-size="where.limit"
      :current-page="where.page"
      :total="total"
      @current-change="pageChange"
      layout="total, prev, pager, next"
    />
  </el-dialog>
</template>
<script>
import { workMassTempGroupApi, workMassTempListApi } from '@/api/weCom'
export default {
  name: '',
  components: {},
  props: {},
  data() {
    return {
      gridData: [],
      activeId: '',
      activeRow: {},
      options: [],
      total: 0,
      dialogTableVisible: false,
      where: {
        page: 1,
        limit: 15,
        group_id: '',
        name: ''
      }
    }
  },

  methods: {
    openBox() {
      this.getOptionData()
      this.getTableData()
      this.dialogTableVisible = true
    },
    reset() {
      this.where = {
        page: 1,
        pageSize: 10,
        group_id: '',
        name: ''
      }
      this.getTableData()
    },

    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    selectIdFn(row) {
      this.activeId = row.id
      this.activeRow = row
      this.$emit('selectMaterial', row)
      setTimeout(() => {
        this.dialogTableVisible = false
      }, 500)
    },
    async getOptionData() {
      const res = await workMassTempGroupApi()
      this.options = res.data.list
    },
    getTableData() {
      workMassTempListApi(this.where).then((res) => {
        this.gridData = res.data.list
        this.total = res.data.count
      })
    }
  }
}
</script>
<style scoped lang="scss">
.icondangqian {
  cursor: pointer;
  color: #1890ff;
  font-size: 14px;
}
.iconweidadao {
  cursor: pointer;
  color: #909399;
  font-size: 14px;
}
.tips {
  font-size: 13px;
  color: #909399;
  margin-bottom: 10px;
}
</style>
