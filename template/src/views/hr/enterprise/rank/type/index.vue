<template>
  <div class="divBox">
    <el-card>
      <div class="btn-box">
        <el-button type="primary" @click="addtype">添加类型</el-button>
      </div>
      <div class="table-box mt20">
        <el-table :data="tableData" style="width: 100%;" :header-cell-style="{ background: '#F2F2F2' }">
          <el-table-column prop="id" label="ID" width="50" style="padding-left: 20px;" />
          <el-table-column prop="name" label="职级类别名称" min-width="180" />
          <el-table-column prop="number" label="职级数" min-width="180" />
          <el-table-column label="状态" width="180">
            <template #default="scope">
              <el-switch
                v-model="scope.row.status"
                active-text="开启"
                inactive-text="关闭"
                :active-value="1"
                :inactive-value="0"
                @change="handleStatus(scope.row)"
              />
            </template>
          </el-table-column>
          <el-table-column prop="address" label="操作" fixed="right" width="160">
            <template #default="scope">
              <el-button type="primary" link @click="deit(scope.row)">编辑</el-button>
              <el-button type="primary" link @click="delet(scope.row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
      <div class="page">
        <el-pagination
          layout="total, prev, pager, next, jumper"
          :total="count"
          :page-size="pagesize"
          :current-page="page"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import modalForm from '@/libs/modal-form.ts'
import { addRanktype, rankTypeInfo, rankTypeStatusApi, rankTypeEdit, rankTypeDelet } from '@/api/enterprise.ts'
export default {
  name: 'Type',
  data() {
    return {
      tableData: [],
      count: 1,
      pagesize: 10, // 每页显示的条数
      page: 1 // 当前页数
    }
  },
  created() {
    this.Info()
  },
  methods: {
    // 添加职级类型
    addtype() {
      modalForm(addRanktype()).then(({ message }) => {
        this.Info()
      })
    },
    // 获取列表
    async Info() {
      const result = await rankTypeInfo({ page: this.page, limit: this.pagesize })
      this.tableData = result.data.list
      this.count = result.data.count
    },
    // 编辑
    deit(data) {
      modalForm(rankTypeEdit(data.id)).then(({ message }) => {
        this.Info()
      })
    },
    // 删除
    delet(data) {
      modalSure('确定删除该类型吗').then(() => {
        this.tableData.splice(data.id, 1)
        rankTypeDelet(data.id).then((res) => {
          this.Info()
        })
      })
    },
    // 修改状态
    async handleStatus(item) {
      await rankTypeStatusApi(item.id, { status: item.status })
    },
    // 分页
    handleCurrentChange(val) {
      this.page = val
      this.Info()
    }
  }
}
</script>

<style scoped></style>
