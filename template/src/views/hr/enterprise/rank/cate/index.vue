<template>
  <div class="divBox">
    <el-card>
      <div class="btn-box">
        <el-button type="primary" @click="addcate">添加类别</el-button>
      </div>
      <div class="table-box mt10">
        <el-table
          :data="tableData"
          :header-cell-style="{ background: '#F2F2F2', color: 'black' }"
          :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
          border
          default-expand-all
          row-key="id"
          style="width: 100%"
        >
          <el-table-column label="ID" min-width="80" prop="id" style="padding-left: 20px" />
          <el-table-column label="职级类别名称" min-width="180" prop="name" />
          <el-table-column label="职级数" min-width="180" prop="number" />
          <el-table-column label="前置职级类别" min-width="180" prop="cate.name" />
          <el-table-column label="操作" flxed="right" prop="address" width="160">
            <template #default="scope">
              <el-button type="primary" link @click="edit(scope.row)">编辑</el-button>
              <el-button type="primary" link @click="delet(scope.row)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import modalForm from '@/libs/modal-form.ts'
import { rankCateCreateApi, rankCateListApi, rankCateEditApi, rankCateDeleteApi } from '@/api/setting.ts'

export default {
  name: 'Cate',
  data() {
    return {
      tableData: []
    }
  },
  created() {
    this.Info()
  },
  methods: {
    // 获取列表
    async Info() {
      const result = await rankCateListApi()
      this.tableData = result.data
    },
    // 添加类别
    addcate() {
      modalForm(rankCateCreateApi()).then(() => {
        this.Info()
      })
    },
    // 编辑
    edit(data) {
      modalForm(rankCateEditApi(data.id)).then(() => {
        this.Info()
      })
    },
    // 删除
    delet(data) {
      modalSure('确定删除该类别吗').then(() => {
        this.tableData.splice(data.id, 1)
        rankCateDeleteApi(data.id).then(() => {
          this.Info()
        })
      })
    }
  }
}
</script>
