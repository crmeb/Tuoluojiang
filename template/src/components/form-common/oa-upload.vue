<!-- @FileDescription: 全局公用上传文件组件  -->
<template>
  <div>
    <div class="uploadBox" v-if="allowUpload">
      <el-upload
        class="upload-demo mr10"
        :class="fileList.length > 0 ? 'mb12' : ''"
        action="##"
        :headers="myHeaders"
        :show-file-list="false"
        :http-request="uploadServerLog"
        :multiple="maxLength > 1"
      >
        <el-button v-if="!percentShow" :disabled="disabled">
          <span class="iconfont iconshangchuan" />
          上传文件
        </el-button>

        <div class="addText" v-else>
          <img src="../../assets/images/loading.gif" alt="" class="l_gif" />
        </div>
      </el-upload>
    </div>

    <upload-list
      :isTwoColumnShow="isTwoColumnShow"
      v-if="fileList.length"
      :file-list="fileList"
      :show-close="!onlyRead && !disabled"
      @fileDelete="fileDelete"
    ></upload-list>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { uploader } from '@/utils/uploadCloud.ts'
import i18n from '@/utils/i18n.ts'
import helper from '@/libs/helper.ts'

export default {
  emits: ['update:modelValue', 'getVal'],
  name: 'OaUpload',
  mixins: [i18n],
  props: {
    modelValue: {
      type: Array,
      default: () => []
    },
    options: {
      type: Object,
      default: () => {
        return {}
      }
    },
    fileTypes: {
      type: Array,
      default: () => []
    },
    maxLength: {
      type: Number,
      default: 1
    },
    isTwoColumnShow: {
      // 附件展示两列
      type: Boolean,
      default: false
    },
    maxSize: {
      type: Number,
      default: 0
    },
    disabled: {
      type: Boolean,
      default: false
    },

    onlyImage: { type: Boolean, default: false },
    onlyRead: { type: Boolean, default: false }
  },
  components: {
    uploadList: defineAsyncComponent(() => import('@/components/form-common/oa-uploadList'))
  },
  data() {
    return {
      // data() 早于 computed 初始化，这里不能读 this.value
      fileList: this.modelValue,
      percentShow: false,
      leftPercent: 0,
      leftTime: 0,
      myHeaders: {
        authorization: 'Bearer ' + localStorage.getItem('token')
      },
      chunkSize: 1024 * 1024 * 5,
      uploadSize: 50
    }
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
    allowUpload() {
      return this.fileList.length < this.maxLength && !this.onlyRead
    }
  },
  watch: {
    value(n) {
      this.fileList = n
    }
  },
  methods: {
    clearFiles() {
      this.fileList = []
      this.$emit('update:modelValue', [])
    },
    // 进度条模拟时间
    progress(data) {
      this.mockLeftText(data)
    },

    // 模拟剩余时间
    mockLeftText(data) {
      this.leftPercent = Math.round(data * 100)
      this.leftTime = Math.round(this.fakeTotalTime * (1 - data)) // fakeTotalTime 可以给个假的总的时间，例如10s
    },
    fileDelete(index) {
      this.fileList.splice(index, 1)

      this.$emit('update:modelValue', this.fileList)
      this.$emit('getVal', this.fileList)
    },

    // 上传文件方法
    async uploadServerLog(params) {
      let fileTypeCheckResult = false
      let extFileName = params.file.name.substring(params.file.name.lastIndexOf('.') + 1)
      if (this.fileTypes.length > 0) {
        fileTypeCheckResult = this.fileTypes.some((ft) => {
          return extFileName.toLowerCase() === ft.toLowerCase()
        })
      } else {
        while (this.fileTypes.pop()) {}
        this.fileTypes.push(...helper.uploadTypes)
        fileTypeCheckResult = this.fileTypes.some((ft) => {
          return extFileName.toLowerCase() === ft.toLowerCase()
        })
      }
      if (!fileTypeCheckResult) {
        this.$message.error('不支持格式' + extFileName)
        return false
      }
      if (this.maxSize) {
        let fileSizeCheckResult = params.file.size / 1024 / 1024 <= this.maxSize
        if (!fileSizeCheckResult) {
          this.$message.error(this.i18nt('render.hint.fileSizeExceed') + this.maxSize + 'MB')
          return false
        }
      }
      this.percentShow = true
      const file = params.file
      let onlyImg = this.onlyImage ? 1 : 0
      let options = {
        way: 2,
        relation_type: '',
        relation_id: '',
        eid: ''
      }

      if (this.options.way) {
        options = this.options
      }

      uploader(file, onlyImg, options)
        .then((res) => {
          this.percentShow = false

          // 切片上传  上传成功返回的是文件列表
          if (res.data) {
            this.fileList.push({
              real_name: res.data.fileListname ? res.data.fileListname : res.data.name,
              size: res.data.size,
              name: res.data.name,
              url: res.data.url,
              id: res.data.id,
              file_url: res.data.url
            })

            this.$emit('update:modelValue', this.fileList)
            this.$emit('getVal', this.fileList)
          }
        })
        .catch((err) => {
          this.percentShow = false
        })
    },

    handleRemove(file) {
      this.fileList.splice(
        this.fileList.findIndex((item) => item.name === file.name),
        1
      )
      this.$emit('update:modelValue', this.fileList)
    }
  }
}
</script>

<style scoped lang="scss">
.uploadBox {
  :deep(.el-upload-list__item-thumbnail) {
    width: 44px !important;
    height: 44px !important;
    margin-left: -83px !important;
    margin-top: -3px;
  }
  :deep(.el-upload-list__item-name) {
    line-height: 42px !important;
    margin-left: -36px !important;
    width: auto !important;
    margin-right: 0 !important;
  }
  :deep(.el-upload-list__item) {
    height: 60px !important;
    background: #f5f5f5 !important;
    border: none;
    width: 220px !important;
    margin-top: 10px !important;
  }

  .upload-demo {
    line-height: initial;
  }
}
.percent {
  margin-top: 20px;
}
:deep(.el-button) {
  height: 32px;
  padding: 0 12px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #606266;

  & > span {
    display: flex;
    height: 100%;
    align-items: center;

    .iconfont {
      margin-right: 4px;
    }
  }
}
</style>
