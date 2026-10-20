<template>
  <!--  在线模版选择弹窗-->
  <div>
    <el-dialog
      class="user-cloudfile-file-temp-dialog"
      :append-to-body="true"
      :before-close="handleClose"
      :fullscreen="true"
      :title="fromData.title"
      v-model="dialogVisible"
      :width="fromData.width"
      top="0"
    >
      <iframe :src="fileUrl" class="iframe"></iframe>
    </el-dialog>
  </div>
</template>

<script>
import { tempDownloadApi } from '@/api/enterprise.ts'
import { getAccount, toSrcFn } from '@/utils/format.ts'
import { getStorageJson } from '@/utils/storage.ts'
export default {
  emits: ['handleTemplate'],
  name: 'FileTemp',
  components: {},
  props: {
    fromData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    wps_type: {
      type: [String, Number],
      default: '0'
    }
  },
  data() {
    return {
      dialogVisible: false,
      name: '',
      fileUrl: ''
    }
  },
  watch: {},
  mounted() {
    let uniquedId = getStorageJson('enterprise', {}).uniqued
    let accountNum = getAccount(getStorageJson('userInfo', {}).phone, uniquedId)
    let type = 'iframe'
    let host = window.location.host
    this.fileUrl = `https://www.tuoluojiang.com/files?account=${accountNum}&uniqued=${uniquedId}&type=${type}&host=${host}`
    this.getLoad()
  },
  beforeUnmount() {
    window.removeEventListener('message', this._handleMessage)
  },
  methods: {
    getLoad() {
      // 在父页面添加事件监听器来接收消息
      this._handleMessage = async (event) => {
        // 处理接收到的数据
        if (event.data && typeof event.data === 'number' && this.fromData.fid) {
          let data = {
            id: this.fromData.id ? this.fromData.id : this.fromData.fid,
            temp_id: event.data
          }
          await tempDownloadApi(this.fromData.fid, data)
          this.handleClose()
          this.handleEmit()
        }
      }
      window.addEventListener('message', this._handleMessage)
    },

    handleClose() {
      this.dialogVisible = false
    },
    handleOpen() {
      this.dialogVisible = true
    },

    src(e) {
      return toSrcFn(e)
    },

    handleEmit() {
      this.$emit('handleTemplate')
    }
  }
}
</script>

<style lang="scss" scoped>
.iframe {
  width: 100%;
  height: 100%;
  border: none;
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.user-cloudfile-file-temp-dialog .el-dialog__body {
  height: calc(100% - 55px);
  padding: 0 !important;
}
</style>
