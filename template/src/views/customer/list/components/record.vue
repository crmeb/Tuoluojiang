<!-- 客户-客户跟进记录页面组件 -->
<template>
  <div class="followUpRecord">
    <div class="btn-box1 mb10">
      <div class="title-16">跟进记录</div>
    </div>
    <record-upload :form-info="formInfo" @change="uploadChange"></record-upload>
    <div class="recordContent">
      <el-timeline>
        <el-timeline-item
          v-for="(activity, index) in liaisonData"
          :key="index"
          color="#1890FF"
          :icon="$icon(activity.icon)"
          :size="activity.size"
        >
          <div v-if="activity.types === 1">
            <div class="head">
              <img :src="activity.card.avatar" alt="" class="head-portrait" />
              <div class="head-right">
                <span class="head-name">{{ activity.card.name }}</span>
                <span class="head-time">{{ activity.created_at }}</span>
              </div>
              <el-dropdown class="more">
                <i class="el-icon-more" />
                <template #dropdown>
                  <el-dropdown-menu style="width: 100px; text-align: center">
                    <el-dropdown-item @click="handleContract(activity)">编辑 </el-dropdown-item>
                    <el-dropdown-item @click="handleDelete(activity)"> 删除 </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
            <div class="record">
              {{ activity.content }}
            </div>
            <div class="reminderTime" v-if="activity.time">
              <img src="../../../../assets/images/zhong.png" alt="" class="zhong" /> 提醒时间：{{ activity.time }}
            </div>
          </div>
          <div v-else>
            <div v-if="editIndex !== index">
              <div class="head">
                <img :src="activity.card.avatar" alt="" class="head-portrait" />
                <div class="head-right">
                  <span class="head-name">{{ activity.card.name }}</span>
                  <span class="head-time">{{ activity.created_at }}</span>
                </div>
                <el-dropdown class="more">
                  <i class="el-icon-more" />
                  <template #dropdown>
                    <el-dropdown-menu style="width: 100px; text-align: center">
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
                <div class="fileItem" @click="filePreview(fileItem, { list: activity.attachs })">
                  <span class="file backgrImg" v-if="toSrcIcon(fileItem.name) !== 'img'">
                    {{ getFileTypeFn(fileItem.name) }}
                  </span>
                  <img :src="fileItem.url" class="file" alt="" v-else />

                  {{ fileItem.real_name }}
                </div>
              </div>
            </div>

            <record-upload v-if="editIndex === index" :form-info="editFormInfo" @change="uploadChange"></record-upload>
          </div>
        </el-timeline-item>
      </el-timeline>
    </div>

    <remind-dialog ref="remindDialog" :config="remindConfig" @change="remindChange"></remind-dialog>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { getStorageJson } from '@/utils/storage.ts'
import { getClientFollowApi, delClientFollowApi } from '@/api/client.ts'
import { toSrcFn } from '@/utils/format.ts'
import { fileLinkDownLoad, getFileExtension, getFileType } from '@/utils/file.ts'
import { filePreview } from '@/utils/filePreview.ts'
export default {
  emits: ['refresh-detail'],
  name: 'Record',
  components: {
    recordUpload: defineAsyncComponent(() => import('./recordUpload')),
    remindDialog: defineAsyncComponent(() => import('./remindDialog'))
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
      isTitle: '添加跟进记录',
      userId: getStorageJson('userInfo', {}).id,
      id: '',
      form: {
        content: '',
        files: [],
        types: 0,
        time: ''
      },
      editFormInfo: {},
      editIndex: -1,
      remindConfig: {}
    }
  },
  mounted() {
    // this.getTableData()
    this.addRecord()
  },
  computed: {
    liaison() {
      return {
        eid: this.formInfo.eid || this.formInfo.data?.eid || this.formInfo.data?.id || '',
        link_type: this.formInfo.link_type || ''
      };
    }
  },
  watch: {
    liaison: {
      handler(newVal, oldVal) {
        const { eid, link_type } = newVal;
        if (eid && link_type) {
          this.getTableData();
        }
      },
      deep: true,
      immediate: false
    }
  },
  methods: {
    filePreview,
    getFileTypeFn(name) {
      return getFileExtension(name)
    },
    toSrcIcon(name) {
      return getFileType(name)
    },
    getTableData() {
      this.editIndex = -1
      this.liaisonData = []
      getClientFollowApi(this.liaison).then((res) => {
        this.liaisonTotal = res.data.count
        res.data.list.map((item, index) => {
          if (index == 0) {
            item.icon = 'iconfont icondangqian'
          }
          this.liaisonData.push(item)
        })
      })
    },

    uploadChange(e) {
      if (e.type === 'add') {
        this.addRecord()
      } else {
        this.editIndex = -1
      }
      this.getTableData()
      if (e.type === 'add') {
        this.$emit('refresh-detail')
      }
    },

    // 下载文件
    downloadFile(row, name) {
      fileLinkDownLoad(row, name)
    },

    // 添加
    addRecord() {
      this.editIndex = -1
      this.formInfo.type = 'add'
      this.isTitle = '添加跟进记录'
    },

    //提醒编辑
    handleContract(row = {}) {
      this.remindConfig = {
        eid: this.formInfo.data.eid,
        isEdit: true,
        data: row
      }
      this.$refs.remindDialog.handleOpen(true)
    },

    remindChange() {
      this.getTableData()
      this.addRecord()
    },

    // 编辑
    handleEdit(row, index) {
      this.editFormInfo = JSON.parse(JSON.stringify(this.formInfo))
      this.editFormInfo.type = 'edit'
      this.editFormInfo.editData = row
      this.editIndex = index
    },

    // 删除
    async handleDelete(row, index) {
      await modalSure('你确定要删除该数据吗')
      await delClientFollowApi(row.id)
      if (this.liaison.page > 1 && this.liaisonData.length <= 1) {
        this.liaison.page--
      }
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
.file {
  display: flex;
  width: 35px;
  height: 35px;
  background-size: 35px 35px;
  color: #fff !important;
  justify-content: center;
  line-height: 38px;
  font-size: 12px;
  margin-right: 10px;
}
.default {
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
.followUpRecord {
  font-family: PingFangSC-Regular, PingFang SC;
}
.btn-box1 {
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
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
.topBtn {
  width: 102px;
  height: 32px;
  font-size: 13px;
}
.zhong {
  display: inline-block;
  margin-left: 9px;
  margin-right: 4px;
  width: 17px;
  height: 19px;
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
  display: flex;
  align-items: center;
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

  .head-right {
    margin-left: 10px;
    display: flex;
    // flex-direction: column;
    // justify-content: center;
    .head-name {
      font-size: 14px;

      font-weight: 400;
      color: #303133;
    }
    .head-time {
      margin-left: 6px;
      font-size: 13px;
      line-height: 17px;
      font-weight: 400;
      color: #c0c4cc;
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
  width: 20px;
  height: 20px;
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
  cursor: pointer;
  display: flex;
  align-items: center;
  margin: 10px;
  padding: 4px 20px 4px 8px;
  background-color: #f5f5f5;
  width: fit-content;
  margin-left: 38px;
  font-size: 13px;
  font-weight: 400;
  color: #333333;
  border-radius: 4px;
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
:deep(.el-timeline-item__wrapper) {
  position: relative;
  top: -12px;
}
:deep(.el-textarea__inner) {
  min-height: 96px;
  border: none;
  box-shadow: none;
  font-size: 12px;
}
:deep(.icondangqian) {
  font-size: 16px;
  color: #1890ff;
  background-color: none;
}
.backgrImg {
  background: url('../../../../assets/images/cloud/file-box.png') no-repeat;
  background-size: 30px 38px;
}
.head-portrait {
  flex-shrink: 0;
  width: 35px;
  height: 35px;
  border-radius: 50%;
}
</style>
