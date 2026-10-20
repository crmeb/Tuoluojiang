<template>
  <div class="station">
    <!-- 文件属性详情侧滑 -->
    <el-drawer
      :before-close="handleClose"
      :direction="direction"
      :modal="true"
      :size="formData.width"
      :title="formData.title"
      v-model="drawer"
    >
      <div v-if="fileData.id !== undefined" class="content">
        <div class="logo">
          <i
            v-if="fileData.type == 0"
            :class="getFileTypeIconfont(fileData.type, fileData.file_ext)"
            class="icon iconfont"
          />
          <img
            v-if="fileData.type == 1"
            src="../../../../assets/images/cloud/file.png"
            alt=""
            style="width: 80px; height: 80px"
          />
          <p>详细信息</p>
        </div>
        <el-row :gutter="20" class="content-list mt15">
          <el-col class="content-left"><i class="icon iconfont iconwenjianleixing" /></el-col>
          <el-col class="content-right">
            <div>文件类型</div>
            <div v-if="fileData.type !== 1">{{ fileData.file_ext }}</div>
            <div v-else>文件夹</div>
          </el-col>
        </el-row>
        <el-row :gutter="20" class="content-list mt15" v-if="fileData.type !== 1">
          <el-col class="content-left"><i class="icon iconfont iconwenjiandaxiao1" /></el-col>
          <el-col class="content-right">
            <div>文件大小</div>
            <div>{{ formatBytesFn(fileData.file_size) }}</div>
          </el-col>
        </el-row>
        <el-row :gutter="20" class="content-list">
          <el-col class="content-left"><i class="icon iconfont iconwenjianweizhi" /></el-col>
          <el-col class="content-right">
            <div>文件位置</div>
            <div v-if="fileData.path.length > 0">
              全部位置
              <span>/ {{ fileData.path }}</span>
            </div>
            <div v-else>
              全部位置
            </div>
          </el-col>
        </el-row>

        <el-row :gutter="20" class="content-list">
          <el-col class="content-left"><i class="icon iconfont iconchuangjianren" /></el-col>
          <el-col class="content-right">
            <div>创建人</div>
            <div>{{ fileData.user.name || '--' }}</div>
          </el-col>
        </el-row>
        <el-row :gutter="20" class="content-list">
          <el-col class="content-left"><i class="icon iconfont iconchuangjianshijian" /></el-col>
          <el-col class="content-right">
            <div>创建时间</div>
            <div>{{ fileData.created_at }}</div>
          </el-col>
        </el-row>
        <el-row :gutter="20" class="content-list">
          <el-col class="content-left"><i class="icon iconfont iconxiugaishijian" /></el-col>
          <el-col class="content-right">
            <div>更新时间</div>
            <div>{{ fileData.updated_at }}</div>
          </el-col>
        </el-row>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import { formatBytes, getFileType, getFileTypeIconfont } from '@/utils/file.ts'
import { folderDetailApi, folderSpaceEntDetailApi } from '@/api/cloud.ts'
export default {
  name: 'FileAttribute',
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      drawer: false,
      direction: 'rtl',
      fileData: {}
    }
  },
  watch: {
    formData: {
      handler(nVal) {
        if (this.formData.is_file) {
          this.getView()
        } else {
          this.getSpace()
        }
      },
      deep: true
    }
  },
  methods: {
    getFileTypeIconfont,
    handleClose() {
      this.drawer = false
      this.fileData = {}
    },
    openBox() {
      this.drawer = true
    },
    formatBytesFn(size) {
      if (size) {
        size = Number(size)
        return formatBytes(size)
      } else {
        return '--'
      }
    },
    getFileType(type, url) {
      return getFileType(url)
    },

    // 我的文件-文件详情
    async getView() {
      const result = await folderDetailApi(this.formData.id)
      this.fileData = result.data
    },
    // 企业文件-文件详情
    async getSpace() {
      const result = await folderSpaceEntDetailApi(this.formData.fid, this.formData.id)
      this.fileData = result.data
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  padding: 20px 25px 0 25px;
  .logo {
    width: 100%;
    text-align: center;
    border-bottom: 1px solid rgba(216, 216, 216, 0.3);
    i {
      font-size: 80px;
    }
    p {
      margin: 10px 0;
      color: rgba(0, 0, 0, 0.5);
      text-align: left;
    }
  }
  .content-list {
    padding-bottom: 10px;
    margin-bottom: 10px;
    color: #999999;
    .content-left {
      width: 40px;
      i {
        font-size: 20px;
      }
    }
    .content-right {
      width: calc(100% - 40px);
      div:first-of-type {
        color: #000000;
        font-size: 14px;
        margin-bottom: 10px;
      }
    }
  }
  .content-list:last-of-type {
    margin-bottom: 0;
  }
}
</style>
