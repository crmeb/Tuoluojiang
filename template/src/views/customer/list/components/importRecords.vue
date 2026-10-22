<template>
  <el-dialog title="导入/导出记录" :visible.sync="dialogVisible" width="1100px" @close="handleClose">
    <template v-if="recordList.length>0">
    <el-table :data="recordList" size="small" style="width: 100%;" height="400px" >
      <el-table-column label="文件名称" prop="name" min-width="200">
        <template #default="scope">
          <el-tooltip v-if="scope.row.name" effect="dark" :content="scope.row.name" placement="top-start">
            <div class="lh-center">
              <i class="iconfont iconExcelgeshi"></i>
              <span class="name over-text">{{ scope.row.name }}</span>
              <i title="下载文件" class="iconfont iconxiazai" v-if="scope.row.file_path"
                @click="downloadFile(scope.row)"></i>
            </div>
          </el-tooltip>
          <span v-else>--</span>

        </template>
      </el-table-column>
      <el-table-column label="操作人" prop="admin.name" />
      <el-table-column label="操作时间" prop="created_at" width="180" />
      <el-table-column label="类型" prop="created_at" width="130" >
        <template #default="scope">
          {{scope.row.types == 1 ? '导入' : '导出'}}
        </template>
      </el-table-column>
      <el-table-column label="操作结果" prop="fail_msg">
        <template #default="scope">
          <!-- 失败时显示带提示的文本 -->
          <el-tooltip v-if="scope.row.status === 2" class="item" effect="dark" :content="scope.row.fail_msg"
            placement="top-start">
            <span v-html="getStatusText(scope.row, scope.row.status)"></span>
          </el-tooltip>
          <!-- 非失败时直接显示文本 -->
          <span v-else v-html="getStatusText(scope.row, scope.row.status)"></span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="80">
        <template #default="scope">
          <el-button type="text" size="mini" @click="deleteRecord(scope.row)">
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page="where.page"
      :page-size="where.limit" layout="total, prev, pager, next, jumper" :total="total" class="mb10"
      style="margin-top: 15px; text-align: right;">
    </el-pagination>
    </template>
    <template v-else>
   <default-page :index="14" :min-height="400" />
    </template>
  </el-dialog>
</template>

<script>
import { modalSure } from '@/libs/public'
import { fileLinkDownLoad } from '@/utils/file'
import { clientExportRecordApi,clientExportRecordDeleteApi } from '@/api/client'
export default {
  name: 'ImportExportRecord',
components: {
    defaultPage: () => import('@/components/common/defaultPage')
  },
  data() {
    return {
      dialogVisible: false,

      total: 0,
      where: {
        page: 1,
        limit: 10,
        types: ''
      },
      recordList: [

      ]
    }
  },

  methods: {
    // 关闭弹窗，通知父组件
    handleClose() {
      this.dialogVisible = false;
    },
    openBox(keyword) {
      this.dialogVisible = true;
      this.where.types = keyword
      this.getRecordList()
    },
    // 获取导入导出记录列表
    async getRecordList() {
      const res = await clientExportRecordApi(this.where)
      this.recordList = res.data.list
      this.total = res.data.count

    },
    getStatusText(row, status) {
      let statusText = ''
      if (row.types == 0) {
        // 导出
        if (status == 0) {
          statusText = '正在导出中'

        } else if (status == 1) {
          statusText = '导出成功'
        } else if (status == 2) {
          statusText = '导出失败'
        }

      } else if (row.types == 1) {
        // 导入
        if (status == 0) {
          statusText = '正在导入中'
        } else if(status == 2){
          statusText = '导入失败'
        }else {
          statusText = `导入成功<span style="color:#19BE6B">${row.success_count}</span>条，失败<span style="color:#F56C6C">${row.fail_count}</span>条`
        }
      }
      return statusText
    },

    // 下载文件（实际需对接后端接口）
    downloadFile(row) {
      fileLinkDownLoad(row.file_path, row.name)
    },
   async deleteRecord(row) {
      await modalSure('确定删除当前数据')
      await clientExportRecordDeleteApi(row.id)
      this.getRecordList()
    },
    // 每页条数改变
    handleSizeChange(val) {
      this.where.limit = val;
      this.getRecordList()
    },
    // 当前页码改变
    handleCurrentChange(val) {
      this.where.page = val;
      this.getRecordList()
    }
  }
};
</script>

<style scoped lang="scss">
.iconExcelgeshi {
  color: #19BE6B;
}

.iconxiazai {
  margin-left: 5px;
  cursor: pointer;
}

.name {
  margin-left: 10px;
  width: 200px;
}
</style>
