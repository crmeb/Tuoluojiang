<!-- 客户跟进记录填写弹窗组件 -->
<template>
  <div class="followUpRecord">
    <div class="addContent">
      <div class="left">
        <img :src="avatar" alt="" class="head-portrait" />
      </div>
      <div class="right">
        <div class="box" @click="focusFn">
          <el-input
            ref="inputFocus"
            v-model="form.content"
            autosize
            placeholder="填写跟进记录吧"
            resize="none"
            type="textarea"
          >
          </el-input>
          <div class="uploadBox">
            <el-tag v-for="(item, i) in uploadList" :key="i" class="mt10 mr10" type="info">
              <div class="info">
                <i class="el-icon-error" @click="deleteTag(item)"></i>
                <img v-if="toSrc(item.real_name) === 1" alt="" class="img" src="@/assets/images/doc.png" />
                <img v-else-if="toSrc(item.real_name) === 2" alt="" class="img" src="@/assets/images/ppt.png" />
                <img v-else-if="toSrc(item.real_name) === 3" alt="" class="img" src="@/assets/images/xls.png" />
                <img v-else-if="toSrc(item.real_name) === 4" alt="" class="img" src="@/assets/images/record2.png" />
                <img v-else-if="toSrc(item.real_name) === 5" alt="" class="img" src="@/assets/images/pdf.png" />
                <img v-else alt="" class="img" src="@/assets/images/record2.png" />
                <span class="text-info line1">{{ item.real_name }}</span>
              </div>
            </el-tag>
          </div>
        </div>
        <el-row class="footer">
          <el-col :span="12" class="flex">
            <el-upload
              :headers="myHeaders"
              :http-request="uploadServerLog"
              :show-file-list="false"
              action="##"
              class="mr10 upload-real"
            >
              <div v-if="!percentShow" class="addText mt20"><span class="iconfont iconfujian"></span> 添加附件</div>
              <div v-else class="addText mt20">
                <img alt="" class="l_gif" src="@/assets/images/loading.gif" />
              </div>
            </el-upload>
          </el-col>
          <el-col :span="12" class="text-right mt20">
            <el-button v-if="formInfo.type === 'edit'" @click="clientCancel">取消</el-button>
            <el-button :loading="loading" type="primary" @click="clientFollowSave">确定</el-button>
          </el-col>
        </el-row>
        <!-- <div v-if="formInfo.show !== 1" class="mt30"></div> -->
      </div>
    </div>
  </div>
</template>

<script>
import { getStorageJson } from '@/utils/storage.ts'
import { saveClientFollowApi, putClientFollowApi } from '@/api/client.ts'
import { ElImageViewer } from 'element-plus'
import { uploader } from '@/utils/uploadCloud.ts'
import { toSrcFn } from '@/utils/format.ts'
export default {
  name: 'recordUpload',
  emits: ['change'],
  components: {
    ElImageViewer
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
      addRecordShow: false,
      isTitle: '添加跟进记录',
      textarea: '',
      loading: false,
      percentShow: false,
      id: '',
      form: {
        content: '',
        files: [],
        types: 0,
        time: ''
      },
      follow_id: 0,
      rules: {
        content: [{ required: true, message: '请输入跟进信息', trigger: 'blur' }]
      },
      avatar: '',
      uploadData: {},
      uploadSize: 15,
      uploadList: [],
      myHeaders: {
        authorization: 'Bearer ' + localStorage.getItem('token')
      },
      successData: {
        type: 'add'
      }
    }
  },

  watch: {
    formInfo: {
      handler(nVal) {
        this.follow_id = nVal.follow_id ? nVal.follow_id : 0
        if (nVal.type === 'edit') {
          this.form.content = nVal.editData.content
          this.uploadList = nVal.editData.attachs
        }
      },
      immediate: true,
      deep: true
    }
  },
  mounted() {
    let userInfo = getStorageJson('userInfo', {})
    this.avatar = userInfo.avatar || ''
  },
  methods: {
    focusFn() {
      this.$refs.inputFocus.focus()
    },
    // 判断上传的文件格式文件是否有无图片，无图则为默认
    toSrc(e) {
      return toSrcFn(e)
    },

    // 上传文件方法
    uploadServerLog(params) {
      this.percentShow = true
      const file = params.file
      let options = {
        way: 2,
        relation_type: 'follow',
        relation_id: this.formInfo.type !== 'add' ? this.formInfo.editData.id : 0,
        eid: this.formInfo.data.eid
      }
      uploader(file, 0, options)
        .then((res) => {
          // 获取上传文件渲染页面
          if (res.data) {
            this.uploadList.push({
              id: res.data.attach_id,
              real_name: res.data.name
            })
            this.percentShow = false
          }
        })
        .catch((err) => {
          this.percentShow = false
        })
      this.percentShow = false
    },

    // 删除附件
    deleteTag(row) {
      this.uploadList = this.uploadList.filter((item) => {
        return item.id !== row.id
      })
    },

    successChange() {
      this.$emit('change', this.successData)
    },

    clientCancel() {
      this.successData.type = 'edit'
      this.successChange()
    },

    // 跟进记录--跟进详情
    clientFollowSave() {
      let attach_ids = []
      if (this.uploadList.length > 0) {
        this.uploadList.map((value) => {
          attach_ids.push(value.id)
        })
      } else {
        attach_ids = []
      }
      const data = {
        content: this.form.content,
        types: 0,
        attach_ids,
        eid: this.formInfo.types === 'odds' ? this.formInfo.odds_id : (this.formInfo.data.eid || this.formInfo.data.id),
        time: this.form.time,
        follow_id: this.follow_id,
        link_type: this.formInfo.link_type
      }
      if (this.loading) return;
      this.loading = true
      if (this.formInfo.type === 'add') {
        // 防抖input
        this.clientFollowAdd(data)
      } else {
        this.clientFollowEdit(this.formInfo.editData.id, data)
      }
    },

    // 跟进记录--添加
    async clientFollowAdd(data) {
      try {
        const res = await saveClientFollowApi(data)
        if (res.status === 200) {
          this.addRecordShow = false
          this.successData.type = 'add'
          this.successChange()
          this.form.content = ''
          this.form.time = ''
          this.uploadList = []
        }
      } catch (error) {
        this.$message.error(error.message);
      } finally {
        this.loading = false
      }
    },

    // 跟进记录--修改跟进详情
    async clientFollowEdit(id, data) {
      try {
        const res = await putClientFollowApi(id, data)
        if (res.status === 200) {
          this.clientCancel()
          this.form.content = ''
          this.form.time = ''
          this.uploadList = []
        }
      } catch (error) {
        this.$message.error(error.message);
      } finally {
        this.loading = false
      }
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
.addContent {
  margin-top: 10px;
  display: flex;

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
      border-radius: 4px;
      .uploadBox {
        margin: 14px;
      }
    }
    .footer {
      margin-bottom: 10px;
      height: 32px;
      display: flex;
      justify-content: space-between;
    }
  }
}

.info {
  height: 28px;
  display: flex;
  align-items: center;
  // padding: 0 8px;
  position: relative;
  margin-bottom: 10px;
  .el-icon-error {
    color: #ccc;
    font-size: 13px;
    position: absolute;
    top: -5px;
    right: -5px;
  }
  .text-info {
    display: inline-block;
    max-width: 180px;
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

:deep(.el-textarea__inner) {
  min-height: 96px;
  border: none;
  box-shadow: none;
  font-size: 12px;
}
:deep(.el-tag) {
  padding: 0 4px;
}
</style>
