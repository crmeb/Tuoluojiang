<!-- 订单-订单记录页面组件 -->
<template>
  <div class="followUpRecord">
    <div class="mb10 btn-box1">
      <div class="title-16">订单记录</div>
      <el-button type="primary" @click="addRecord">添加订单记录</el-button>
    </div>
    <file-upload :formInfo="formInfo" v-if="addRecordShow" @change="uploadChange"></file-upload>
    <div class="recordContent">
      <el-timeline>
        <el-timeline-item
          v-for="(activity, index) in liaisonData"
          :key="index"
          :icon="$icon(activity.icon)"
          color="#1890FF"
          :size="activity.size"
        >
          <div v-if="editIndex !== index">
            <div class="head">
              <img :src="activity.user.avatar" alt="" class="head-portrait" />
              <div class="head-right">
                <span class="head-name">{{ activity.user.name }}</span>
                <span class="head-time">{{ activity.created_at }}</span>
              </div>
              <el-dropdown class="more">
                <i class="el-icon-more" />
                <template #dropdown>
                  <el-dropdown-menu style="text-align: center; width: 100px">
                    <el-dropdown-item @click="handleEdit(activity, index)"> 编辑 </el-dropdown-item>
                    <el-dropdown-item @click="handleDelete(activity)"> 删除 </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
            <div class="record">
              {{ activity.content }}
            </div>
            <div v-for="(fileItem, g) in activity.attachs" :key="g" class="flex">
              <div class="fileItem">
                <span class="file" v-if="toSrcIcon(fileItem.name) !== 'img'" @click="filePreview(fileItem, { list: activity.attachs })">
                  {{ getFileTypeFn(fileItem.name) }}
                </span>
                <img v-else :src="fileItem.url" alt="" class="img" />
                {{ fileItem.name }}
              </div>

              <div class="mt10">
                <span class="iconfont iconyulan pointer" @click="filePreview(fileItem, { list: activity.attachs })"></span>
                <span class="iconfont iconxiazai pointer" @click="downloadFile(fileItem.url, fileItem.name)"></span>
              </div>
            </div>
          </div>
          <file-upload :formInfo="formInfo" v-if="editIndex === index" @change="uploadChange"></file-upload>
        </el-timeline-item>
      </el-timeline>
    </div>
    <div v-if="liaisonData.length == 0" class="default">
      <EmptyState type="record" :text="'暂无数据' + '~'" />
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { contracFileListApi, contracFileDeleteApi } from '@/api/client.ts'
import { toSrcFn } from '@/utils/format.ts'
import { modalSure } from '@/libs/public.ts'
import { fileLinkDownLoad, getFileExtension, getFileType } from '@/utils/file.ts'
import EmptyState from '@/components/common/emptyState.vue'
import { filePreview } from '@/utils/filePreview.ts'
export default {
  emits: ['refresh-detail'],
  name: 'Record',
  components: {
    EmptyState,
    fileUpload: defineAsyncComponent(() => import('./fileUpload'))
  },
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      liaisonData: [],
      srcList: [],
      addRecordShow: false,
      isImage: false,
      isTitle: '编辑弹窗',
      editIndex: -1
    }
  },
  mounted() {},

  methods: {
    filePreview,
    getTableData() {
      this.liaisonData = []
      let data = {
        cid: this.formInfo.data.cid
      }
      contracFileListApi(data).then((res) => {
        res.data.list.map((item, index) => {
          if (index == 0) {
            item.icon = 'iconfont icondangqian'
          }
          this.liaisonData.push(item)
        })
      })
    },
    // 关闭查看图片弹窗
    closeImageViewer() {
      this.isImage = false
      this.srcList = []
    },

    getFileTypeFn(name) {
      return getFileExtension(name)
    },
    // 查看图片
    handlePictureCardPreview(row) {
      this.srcList.push(row)
      this.isImage = true
    },
    // 下载文件
    downloadFile(row, name) {
      fileLinkDownLoad(row, name)
    },
    // 添加
    addRecord() {
      this.formInfo.type = 'add'
      this.addRecordShow = !this.addRecordShow
    },
    uploadChange(e) {
      if (e == 'add') {
        this.addRecordShow = false
      } else {
        this.editIndex = -1
      }
      this.getTableData()
      this.$emit('refresh-detail')
    },
    toSrcIcon(name) {
      return getFileType(name)
    },
    // 编辑
    handleEdit(row, index) {
      this.formInfo.type = 'edit'
      this.formInfo.editData = row
      this.editIndex = index
    },
    // 删除

    async handleDelete(row, index) {
      await modalSure('您确定要删除此订单记录?')
      await contracFileDeleteApi(row.id)
      this.getTableData()
      this.$emit('refresh-detail')
    },
    // 判断上传的文件格式文件是否有无图片，无图则为默认
    toSrc(e) {
      return toSrcFn(e)
    }
  }
}
</script>

<style lang="scss" scoped>
.followUpRecord {
  font-family: PingFangSC-Regular, PingFang SC;
}
.mt30 {
  margin-top: 30px;
}
.addText {
  font-size: 12px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
  .iconfont {
    color: #303133;
    font-size: 12px;
  }
}

.btn-box1 {
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.addContent {
  margin-top: 20px;
  display: flex;
  padding-right: 50px;

  .left {
    img {
      display: block;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      margin-right: 10px;
    }
  }
  .right {
    width: 100%;

    .box {
      width: 100%;
      min-height: 134px;
      border: 1px solid #dcdfe6;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      .uploadBox {
        margin: 14px;
      }
    }
    .footer {
      margin-top: 30px;
      margin-bottom: 10px;
      height: 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  }
}
.topBtn {
  width: 102px;
  height: 32px;
  font-size: 13px;
}
.iconzhong {
  margin-left: 9px;
  color: #79a8d7 !important;
  font-size: 15px;
}
.reminderTime {
  margin-left: 45px;
  width: 246px;
  height: 28px;
  background: #f1f9ff;
  border-radius: 4px;
  line-height: 28px;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #1890ff;
  margin-top: 10px;
}
.iframe {
  width: 100%;
  height: 252px;
  border: none;
}
.el-tag {
  padding: 0px;
  margin-right: 16px;
}
.recordContent {
  padding-top: 38px;
}
.el-timeline {
  padding-left: 0px;
}
.head {
  display: flex;
  align-items: center;
  .more {
    position: absolute;
    right: 0;
  }

  .el-icon-more {
    color: #909399;
  }
  .head-portrait {
    width: 35px;
    height: 35px;
    border-radius: 50%;
  }
  .head-right {
    margin-left: 10px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    .head-name {
      font-size: 14px;

      font-weight: 400;
      color: #303133;
    }
    .head-time {
      font-size: 13px;
      line-height: 17px;
      font-weight: 400;
      color: #c0c4cc;
      margin-bottom: 13px;
    }
  }
}
.record {
  padding-left: 45px;
  font-size: 14px;
  line-height: 21px;
  font-weight: 400;
  color: #303133;
}
:deep(.el-timeline-item__wrapper) {
  position: relative;
  top: -12px;
}
.line {
  width: 100%;
  height: 4px;
  border-bottom: 1px solid #dcdfe6;
  margin-bottom: 20px;
}
.info {
  height: 28px;
  display: flex;
  align-items: center;
  padding: 0 8px;
  position: relative;
  margin-bottom: 10px;
  .el-icon-error {
    color: #ccc;
    font-size: 13px;
    position: absolute;
    top: -5px;
    right: -5px;
  }
}
.img {
  width: 30px;
  height: 38px;
  margin-right: 4px;
  vertical-align: middle;
}
.flex {
  display: flex;
}
.mt10 {
  margin-top: 20px;
}

.fileItem {
  display: flex;
  align-items: center;
  margin: 5px;
  padding: 5px 10px;
  background-color: #f5f5f5;
  width: fit-content;
  margin-left: 38px;
  font-size: 13px;

  font-weight: 400;
  color: #333333;
  border-radius: 4px;
}
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
.iconfont {
  color: #1890ff;
}
:deep(.el-timeline-item__tail) {
  position: absolute;
  left: 4px;
  height: 100%;

  border-left: 1px solid #dfe4ed;
}
:deep(.el-timeline-item__node--normal) {
  left: 0px;
  width: 10px;
  height: 10px;
}
:deep(.el-textarea__inner) {
  min-height: 96px;
  // word-break: break-all;
  border: none;
  box-shadow: none;
  font-size: 12px;
}
:deep(.icondangqian) {
  font-size: 16px;
  color: #1890ff;
  background-color: none;
}
.default {
  margin-top: 80px;
  .img {
    width: 200px;
    height: 150px;
  }
  .text {
    font-size: 12px;
    font-family: PingFangSC, PingFang SC;
    font-weight: 400;
    color: #c0c4cc;
  }
  height: 369px;
  display: flex;
  flex-direction: column;
  align-items: center;
}
</style>
