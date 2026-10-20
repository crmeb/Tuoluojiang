<template>
  <div class="divBox">
    <el-card class="normal-page">
      <oaFromBox
        v-if="search.length > 0"
        :search="search"
        :isViewSearch="false"
        :total="total"
        :title="`海氏量表`"
        :isAddBtn="true"
        @addDataFn="addFn"
        @confirmData="confirmData"
      ></oaFromBox>

      <div class="table-box mt10">
        <el-table :data="tableData" :height="TABLE_HEIGHT" style="width: 100%">
          <el-table-column prop="name" label="评估表名称" width="180"> </el-table-column>
          <el-table-column prop="position" label="关联职位" width="180">
            <template #default="scope">
              <span v-for="(item, index) in scope.row.positions" :key="index">{{ item.name }},</span>
            </template>
          </el-table-column>
          <el-table-column prop="card.name" label="创建人"> </el-table-column>
          <el-table-column prop="created_at" label="创建时间"> </el-table-column>
          <el-table-column prop="updated_at" label="更新时间"> </el-table-column>
          <el-table-column label="操作" width="180" fixed="right">
            <template #default="scope">
              <el-button type="primary" link @click="editFn(scope.row)" v-hasPermi="['hr:training:haishAssessment:edit']"
                >编辑</el-button
              >

              <el-button type="primary" link @click="deleteFn(scope.row)" v-hasPermi="['hr:training:haishAssessment:delete']"
                >删除</el-button
              >
            </template>
          </el-table-column>
        </el-table>
        <div class="page-fixed">
          <el-pagination
            :page-size="where.limit"
            :current-page="where.page"
            :page-sizes="[15, 20, 30]"
            layout="total,sizes, prev, pager, next, jumper"
            :total="total"
            @size-change="handleSizeChange"
            @current-change="pageChange"
          />
        </div>
      </div>
    </el-card>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { getHayGroupListApi, deleteHayGroupApi } from '@/api/config.ts'
import oaFromBox from '@/components/common/oaFromBox'
export default {
  emits: ['addType', 'editType'],
  name: 'CrmebOaEntTabelList',
  components: { oaFromBox },
  data() {
    return {
      TABLE_HEIGHT,
      total: 0,
      where: {
        page: 1,
        limit: 15
      },
      search: [
        {
          field_name: '评估表名称,关联职位',
          field_name_en: 'name',
          form_value: 'input'
        }
      ],
      tableData: []
    }
  },

  mounted() {
    this.getList()
  },

  methods: {
    async getList() {
      const result = await getHayGroupListApi(this.where)
      this.total = result.data.count
      this.tableData = result.data.list
    },

    deleteFn(val) {
      modalSure('你确定要删除这条数据吗').then(() => {
        deleteHayGroupApi(val.id).then((res) => {
          let totalPage = Math.ceil((this.total - 1) / this.where.limit)
          let currentPage = this.where.page > totalPage ? totalPage : this.where.page
          this.where.page = currentPage < 1 ? 1 : currentPage
          this.getList()
        })
      })
    },
    confirmData(data) {
      if (data == 'reset') {
        this.where = {
          page: 1,
          limit: 15
        }
        this.getList()
      } else {
        this.where = { ...this.where, ...data }
        this.where.page = 1
        this.getList()
      }
    },

    pageChange(page) {
      this.where.page = page
      this.getList()
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getList()
    },
    addFn() {
      this.$emit('addType')
    },
    editFn(val) {
      this.$emit('editType', val)
    }
  }
}
</script>

<style lang="scss" scoped>
.from {
  display: flex;
  justify-content: space-between;
  padding-bottom: 20px;
  border-bottom: 1px solid #eeeeee;
}
.card-box {
  min-height: calc(100vh - 65px);
}
</style>
