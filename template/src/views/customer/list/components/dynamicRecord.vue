<!-- 客户-客户跟进记录页面组件 -->
<template>
  <div class="followUpRecord">
    <div class="title-16">动态记录</div>
    <record-upload v-if="addRecordShow" :form-info="formInfo" @change="uploadChange"></record-upload>
    <div class="recordContent">
      <el-timeline>
        <el-timeline-item
          v-for="(activity, index) in dynamicRecord"
          :key="index"
          :icon="activity.icon"
          :size="activity.size"
          :type="String(activity.type)"
          color="#1890FF"
        >
          <div v-if="activity.types === 1">
            <div class="head">
              <div class="head-right">
                <span class="head-name">{{ getTypeStr(activity.type, activity.link_type) }}</span>
                <span class="head-time">{{ activity.created_at }}</span>
              </div>
            </div>
            <div class="record">
              {{ activity.reason }}
            </div>
            <div v-if="activity.time" class="reminderTime">
              <img alt="" class="zhong" src="../../../../assets/images/zhong.png" /> 提醒时间：{{ activity.created_at }}
            </div>
          </div>
          <div v-else>
            <div v-if="editIndex !== index">
              <div class="head">
                <div class="head-right">
                  <span class="head-name">{{ getTypeStr(activity.type, activity.link_type) }}</span>
                  <span class="head-time"
                    >{{ activity.creator ? activity.creator.name : '系统' }} | {{ activity.created_at }}</span
                  >
                </div>
              </div>
              <div class="record"><span class="c-30">说明：</span> {{ activity.reason || '' }}</div>
          <div class="p20" v-if="activity.attachs.length > 0">
                <oa-uploadList :fileList="activity.attachs" :isTwoColumnShow="true"></oa-uploadList>
          </div>
              <!-- <div v-for="(fileItem, g) in activity.attachs" :key="g" >
                <div class="fileItem">
                  <img
                    :src="require(`@/assets/images/${fileTypeIcon(toSrc(fileItem.name))}.png`)"
                    alt="文件图标"
                    class="img"
                  />
                  {{ fileItem.real_name }}
                </div>

                <div class="mt10">
                  <span
                    v-if="toSrc(fileItem.name) === 4"
                    class="iconfont iconyulan pointer"
                    @click="handlePictureCardPreview(fileItem.url)"
                  ></span>
                  <span
                    class="iconfont iconxiazai pointer"
                    @click="downloadFile(fileItem.url, fileItem.real_name)"
                  ></span>
                </div>
              </div> -->
            </div>
            <record-upload v-if="editIndex === index" :form-info="formInfo" @change="uploadChange"></record-upload>
          </div>
        </el-timeline-item>
      </el-timeline>
    </div>

    <div v-if="dynamicRecord.length == 0" class="default">
      <EmptyState type="record" :text="$t('public.message14') + '~'" />
    </div>
    <!-- 查看图片 -->
    <el-image-viewer v-if="isImage" :on-close="closeImageViewer" :url-list="srcList" />
    <remind-dialog ref="remindDialog" :config="remindConfig" @change="remindChange"></remind-dialog>
  </div>
</template>

<script>
import { clientRecordApi } from '@/api/enterprise'
import { toSrcFn } from '@/utils/format'
import { fileLinkDownLoad } from '@/utils/file'
import EmptyState from '@/components/common/emptyState.vue'
import RecordUpload from './recordUpload'
import RemindDialog from './remindDialog'
import OaUploadList from '@/components/form-common/oa-uploadList.vue'
export default {
  name: 'dynamicRecord',
  components: {
    EmptyState,
    ElImageViewer: () => import('element-ui/packages/image/src/image-viewer'),
    recordUpload: RecordUpload,
    remindDialog: RemindDialog,
    oaUploadList: OaUploadList
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
      dynamicRecord: [],
      srcList: [],
      liaison: {},
      addRecordShow: false,
      isTitle: '添加跟进记录',
      textarea: '',
      isEdit: null,
      isImage: false,
      id: '',
      form: {
        content: '',
        files: [],
        types: 0,
        time: ''
      },
      editIndex: -1,
      remindConfig: {}
    }
  },
  mounted() {
    this.getTableData()
  },

  methods: {
    getTableData() {
      this.dynamicRecord = []
      const key = this.formInfo.link_type === 'customer' ? 'eid' : 'id';
      this.liaison.eid = this.formInfo.data?.[key] ?? this.formInfo.id
      this.liaison.link_type = this.formInfo.link_type
      if(!this.liaison.eid) return false
   
      clientRecordApi(this.liaison).then((res) => {
        this.liaisonTotal = res.data.count
        res.data.list.map((item, index) => {
          if (index == 0) {
            item.icon = 'iconfont icondangqian'
          }
          this.dynamicRecord.push(item)
        })
      })
    },

    fileTypeIcon(type) {
      const iconMap = {
        1: 'doc',
        2: 'ppt',
        3: 'xls',
        4: 'record2',
        5: 'pdf'
      }
      return iconMap[type] || 'file-default'
    },
    // 关闭查看图片弹窗
    closeImageViewer() {
      this.isImage = false
      this.srcList = []
    },
    getTypeStr(type, link) {
      if (link === 'customer') {
        let obj = {
          0:'跟进客户',
          1: '转为公海客户',
          2: '领取客户',
          3: '标为流失',
          4: '取消流失',
          5: '移交客户',
          6: '新增客户',
          7: '线索转客户',
          10: '数据变更'
        }
        return obj[type]
      } else if (link === 'clue') {
        let obj = {
          0:'跟进线索',
          1: '新增线索',
          2: '修改线索',
          3: '领取线索',
          4: '退回线索池',
          5: '转客户',
          6: '转移',
          10: '数据变更'
        }
        return obj[type]
      } else if (link === 'contract') {
        let obj = {
          10: '数据变更',
          5: '移交订单',
          6: '新增订单'
        }
        return obj[type]
      } else if (link === 'liaison') {
        let obj = {
          6: '新增联系人',
          10: '数据变更'
        }
        return obj[type]
      } else if (link === 'odds') {
        let obj = {
          0:'跟进商机',
          1: '新增商机',
          2: '修改商机',
          6: '转移',
          10: '数据变更'
        }
        return obj[type]
      }else if (link === 'contract_doc') {
        let obj = {
         '-1':'合同签约审批拒绝',
          '1':'新增合同签约',
          '2':'合同签约审批通过',
          '3':'合同签约完成',
          '4':'拒绝合同签约',
          '5':'签约已过期',
          '6':'签约已撤销'
        }
        return obj[type]
      }
    },
    // 查看图片
    handlePictureCardPreview(row) {
      this.srcList.push(row)
      this.isImage = true
    },
    uploadChange(e) {
      if (e.type === 'add') {
        this.addRecordShow = false
      } else {
        this.editIndex = -1
      }
      this.getTableData()
    },
    // 下载文件
    downloadFile(row, name) {
      fileLinkDownLoad(row, name)
    },
    // 添加
    addRecord() {
      this.formInfo.type = 'add'
      this.addRecordShow = !this.addRecordShow
      this.isTitle = '添加跟进记录'
    },

    remindChange() {
      this.getTableData()
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
.el-button--medium {
  padding: 10px 15px !important;
}
.recordContent {
  padding-top: 38px;
}
.el-timeline {
  padding-left: 0px;
}
.head {
  // display: flex;
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
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: rgba(243, 248, 254, 1);

    .head-name {
      font-size: 14px;
      height: 40px;
      // width: 100%;
      line-height: 40px;
      font-weight: 600;
      color: #303133;
      padding-left: 20px;
    }
    .head-time {
      font-size: 13px;
      line-height: 17px;
      font-weight: 400;
      color: #909399;
      padding-right: 30px;
    }
  }
}
.record {
  padding-left: 20px;
  font-size: 14px;
  line-height: 30px;
  font-weight: 400;
  color: #303133;
  border: 1px solid #f3f8fe;
}
.c-30 {
  color: #909399;
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
  margin: 10px;
  padding: 4px 7px;
  background-color: #f5f5f5;
  width: fit-content;
  margin-left: 38px;
  font-size: 13px;
  line-height: 28px;
  font-weight: 400;
  color: #333333;
  border-radius: 4px;
}
.iconfont {
  color: #1890ff;
}
::v-deep .el-timeline-item__tail {
  position: absolute;
  left: 4px;
  height: 100%;

  border-left: 1px solid #dfe4ed;
}
::v-deep .el-timeline-item__node--normal {
  left: 0px;
  width: 10px;
  height: 10px;
}
::v-deep .el-timeline-item__wrapper {
  position: relative;
  top: -12px;
}
::v-deep .el-textarea__inner {
  min-height: 96px;
  border: none;
  font-size: 12px;
}
::v-deep .icondangqian {
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
