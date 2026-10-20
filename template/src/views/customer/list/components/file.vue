<!-- 客户-附件页面组件 -->
<template>
  <div class="station">
    <div class="mb10 btn-box1">
      <div class="title-16">附件列表</div>
      <el-upload
        :headers="myHeaders"
        :http-request="uploadServerLog"
        :show-file-list="false"
        action="##"
        class="mr10 upload-real"
      >
        <el-button v-if="!percentShow" type="primary">
          <i class="el-icon-plus"></i>
          添加文件
        </el-button>
        <div v-else class="addText">
          <img alt="" class="l_gif" src="../../../../assets/images/loading.gif" />
        </div>
      </el-upload>
    </div>
    <el-table :data="fileData">
      <el-table-column label="文件封面" prop="name" width="80">
        <template #default="scope">
          <span class="file" v-if="toSrcIcon(scope.row.name) !== 'img'">
            {{ getFileTypeFn(scope.row.name) }}
          </span>

          <img v-else :src="scope.row.att_dir" alt="" class="img" @click="filePreview(scope.row, { list: fileData })" />
        </template>
      </el-table-column>
      <el-table-column label="文件名称" min-width="180" prop="real_name">
        <template #default="scope">
          <span class="over-text">{{ scope.row.real_name }}</span>
        </template>
      </el-table-column>
      <el-table-column label="附件大小" min-width="180" prop="att_size">
        <template #default="scope">
          <span class="over-text">{{ formatBytesFn(scope.row.att_size) }}</span>
        </template>
      </el-table-column>
      <el-table-column label="上传用户" min-width="100" prop="card.name" />
      <el-table-column label="上传时间" min-width="130" prop="created_at">
        <template #default="scope">
          <span class="over-text">{{ dayjs(scope.row.created_at).format('YYYY-MM-DD') }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" fixed="right" prop="address" width="180">
        <template #default="scope">
          <el-button type="primary" link @click="filePreview(scope.row, { list: fileData })">
            查看
          </el-button>

          <el-button type="primary" link @click="getFileLinkDownLoad(scope.row.att_dir, scope.row.name)">
            下载
          </el-button>

          <el-dropdown>
            <el-button type="primary" link class="el-dropdown-link ml10">
              更多
              <i class="el-icon-arrow-down" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu style="text-align: center">
                <el-dropdown-item @click="handleLabel(scope.row)"> 重命名 </el-dropdown-item>
                <el-dropdown-item @click="handleFileDelete(scope.row, scope.$index)">
                  删除</el-dropdown-item
                >
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination">
      <el-pagination
        :current-page="where.page"
        :page-size="where.limit"
        :total="total"
        layout="total, prev, pager, next, jumper"
        @current-change="pageChange"
      />
    </div>

    <!-- 重命名弹窗 -->
    <el-dialog :append-to-body="true" v-model="dialogFormVisible" title="修改文件名" width="40%">
      <el-form>
        <el-form-item label="重命名：" label-width="100px">
          <el-input v-model="real_name" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="dialogFormVisible = false">取 消</el-button>
          <el-button type="primary" @click="putRealName">确 定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import { getStorageJson } from '@/utils/storage.ts'
import { uploader } from '@/utils/uploadCloud.ts'
import { fileIsImage, fileLinkDownLoad, formatBytes, getFileExtension, getFileType } from '@/utils/file.ts'
import { clientFileDeleteApi, clientFileListApi, putRealName } from '@/api/client.ts'
import { modalSure } from '@/libs/public.ts'
import { filePreview } from '@/utils/filePreview.ts'
export default {
  emits: ['refresh-detail'],
  name: 'File',
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  computed: {
    dayjs: () => dayjs
  },
  data() {
    return {
      real_name: '',
      fileName: '',
      RealNameId: '',
      userId: getStorageJson('userInfo', {}).id,
      dialogFormVisible: false,
      fileData: [],
      where: {
        page: 1,
        limit: 15,
        eid: 0,
        cid: 0
      },
      percentShow: false,
      percent: 0,
      timer: null,
      total: 0,
      loading: false,
      srcList: [],
      fileParams: {},
      myHeaders: {
        authorization: 'Bearer ' + localStorage.getItem('token')
      }
    }
  },
  mounted() {
    // 设置上传参数
    this.fileParams.relation_type = 'client'
    this.fileParams.eid = this.formInfo.data.eid
    this.fileParams.cid = this.formInfo.data.cid
    this.where.eid = this.formInfo.data.eid
    this.where.cid = this.formInfo.data.cid
  },
  methods: {
    filePreview,
    getFileTypeFn(name) {
      return getFileExtension(name)
    },
    getTableData() {
      this.fileParams.eid = this.formInfo.data.eid
      this.where.cid = this.formInfo.data.cid
      this.where.eid = this.formInfo.data.eid
      clientFileListApi(this.where).then((res) => {
        this.fileData = res.data.list
        this.fileParams.eid = this.formInfo.data.eid
        this.total = res.data.count
        if (this.fileData.length > 0) {
          this.fileData.map((value) => {
            if (fileIsImage(value.att_type)) {
              this.srcList.push(value.att_dir)
            }
          })
        }
      })
    },

    pageChange(val) {
      this.where.page = val
      this.getTableData()
    },
    formatBytesFn(size) {
      if (size) {
        return formatBytes(Number(size))
      } else {
        return '--'
      }
    },

    uploadServerLog(params) {
      this.percentShow = true
      const file = params.file
      uploader(file, 0, this.fileParams)
        .then((res) => {
          // 获取上传文件渲染页面
          if (res.data) {
            this.getTableData()
            this.percentShow = false
            this.$emit('refresh-detail')
          }
        })
        .catch((err) => {
          this.percentShow = false
        })
    },

    toSrcIcon(name) {
      return getFileType(name)
    },

    getFileLinkDownLoad(url, name) {
      fileLinkDownLoad(url, name)
    },

    // 重命名
    handleLabel(row) {
      this.RealNameId = row.id
      this.dialogFormVisible = true
      this.real_name = row.real_name
    },
    putRealName() {
      let data = {
        real_name: this.real_name
      }
      putRealName(this.RealNameId, data).then((res) => {
        this.dialogFormVisible = false
        this.getTableData()
      })
    },
    // 删除附件
    handleFileDelete(row, index) {
      modalSure('附件删除后，不可恢复，确认要删除吗').then(() => {
        clientFileDeleteApi(row.id).then((res) => {
          this.fileData.splice(index, 1)
          if (this.where.page > 1 && this.fileData.length <= 0) {
            this.where.page--
          }
          this.getTableData()
          this.$emit('refresh-detail')
        })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.file {
  display: flex;
  width: 30px;
  height: 38px;
  background: url('../../../../assets/images/cloud/file-box.png') no-repeat;
  background-size: 30px 38px;
  color: #fff !important;
  justify-content: center;
  line-height: 38px;
  font-size: 12px;
  margin-right: 10px;
}
.percent {
  margin-top: 20px;
}
.station {
  height: 100%;
}
.img {
  display: inline-block;
  width: 30px;
  height: 30px;
}
:deep(.el-input__inner) {
  text-align: left;
}
.icon-cover {
  font-size: 28px;
}
.el-button {
  font-size: 13px !important;
}
.btn-box1 {
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ml10 {
  margin-left: 10px;
}
:deep(.el-dropdown-menu__item) {
  font-size: 13px !important;
}
</style>
