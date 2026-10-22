<template>
  <div>
    <!-- 上传 -->
    <div class="upload-box" @click="openUpload" v-if="!url">
      <span class="el-icon-plus"></span>
    </div>
    <div class="pic" v-if="url">
      <img class="img" :src="url" />
      <div class="pic-upload">
        <div class="pic-upload-content">
          <i @click="handleAvatar()" class="el-icon-delete"></i>
        </div>
      </div>
    </div>
    <el-dialog :append-to-body="true" :before-close="handleClose" :visible.sync="dialogVisible" title="选择图片"
      width="850px">
      <upload-picture ref="uploadPicture" :check-button="true" @getImage="getImage"></upload-picture>
    </el-dialog>
  </div>
</template>
<script>
export default {
  props: {
    value: {
      type: String,
      default: ''
    }
  },
  components: {
    uploadPicture: () => import('@/components/uploadPicture/index')
  },
  data() {
    return {
      dialogVisible: false,
      url: ''
    }
  },
  methods: {
    getImage(data) {
      this.url = data.att_dir
      let obj = {
        url: this.url,
        id: data.id,
        name: data.name,
      }
      this.$emit('getImage', obj)
      this.handleClose()

    },
    handleAvatar() {
      this.url = ''
      let obj = {
        url: '',
        id: '',
        name: '',
      }
      this.$emit('getImage', obj)
    },
    // 打开图片上传弹窗
    openUpload() {
      this.dialogVisible = true
    },
    // 关闭图片选择弹窗
    handleClose() {
      this.dialogVisible = false
      this.$refs.uploadPicture.getFileList('')
      this.$refs.uploadPicture.selectItem = []
      this.$refs.uploadPicture.checkPicList = []

    },
  }
}
</script>
<style scoped>
.upload-box {
  cursor: pointer;
  width: 45px;
  height: 45px;
  background: #FFFFFF;
  border-radius: 4px 4px 4px 4px;
  border: 1px dashed #DCDFE6;
  text-align: center;
  line-height: 45px;

  .el-icon-plus {
    font-size: 11px;
    color: #C0C4CC;
  }
}

.pic {
  width: 45px;
  height: 45px;
  border-radius: 4px;
  position: relative;

  img {
    border-radius: 4px;
    width: 100%;
    height: 100%;
  }

  &:hover {
    .pic-upload {
      display: block;
    }
  }

  .pic-upload {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    right: 0;
    display: none;
    border-radius: 4px;
    background-color: rgba(0, 0, 0, 0.6);

    .pic-upload-content {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;

      i {
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        margin-right: 10px;

        &:last-of-type {
          margin-right: 0;
        }
      }
    }
  }
}
</style>
