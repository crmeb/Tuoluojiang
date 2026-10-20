<!-- @FileDescription: 文件预览宿主组件（图片/音频/视频/不支持类型）。
     只负责展示，类型判断与取数由 utils/filePreview.js 单例服务完成。 -->
<template>
  <div class="file-preview-host">
    <el-dialog
      :class="['file-preview-dialog', `is-${dialogKind}`]"
      v-model="dialogVisible"
      :width="dialogWidth"
      :top="dialogTop"
      :append-to-body="true"
      :close-on-click-modal="false"
      :show-close="dialogKind !== 'video'"
      @closed="handleClosed"
    >
      <template #header>
        <div class="file-preview-title">
          <span class="over-text">{{ displayName }}</span>
          <i v-if="dialogKind === 'video'" class="el-icon-close" @click.stop="close"></i>
        </div>
      </template>

      <!-- 取数中：点击后立刻可见的反馈 -->
      <div v-if="status === 'loading'" class="file-preview-state">
        <i class="el-icon-loading"></i>
        <p class="file-preview-state__text">正在加载文件…</p>
      </div>

      <!-- 取数失败：给出重试与下载两条出路，而不是弹一句 toast 就没了 -->
      <div v-else-if="status === 'error'" class="file-preview-state">
        <i class="el-icon-warning-outline is-error"></i>
        <p class="file-preview-state__text">{{ errorMsg }}</p>
        <div class="file-preview-actions">
          <el-button v-if="canRetry" @click="handleRetry">重新加载</el-button>
          <el-button v-if="url" type="primary" @click="handleDownload">直接下载</el-button>
        </div>
      </div>

      <!-- 音频 -->
      <div v-else-if="kind === 'audio'" class="file-preview-audio">
        <previewMp3 :url="url" />
      </div>

      <!-- 视频 -->
      <div v-else-if="kind === 'video'" class="file-preview-video">
        <previewMp4 :url="url" />
      </div>

      <!-- 不支持在线预览：不再静默触发下载，交回给用户决定 -->
      <div v-else-if="kind === 'unknown'" class="file-preview-unsupported">
        <div class="file-preview-unsupported__icon">{{ fileExt || '文件' }}</div>
        <p class="file-preview-unsupported__name over-text">{{ displayName }}</p>
        <p v-if="sizeText" class="file-preview-unsupported__size">{{ sizeText }}</p>
        <p class="file-preview-unsupported__tip">该文件类型暂不支持在线预览</p>
        <div class="file-preview-actions">
          <el-button type="primary" :disabled="!url" @click="handleDownload">下载文件</el-button>
          <el-button :disabled="!url" @click="handleCopyLink">复制链接</el-button>
          <el-button @click="close">取消</el-button>
        </div>
      </div>
    </el-dialog>

    <!-- 图片 -->
    <previewImage ref="imageViewer" />
  </div>
</template>

<script>
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { FILE_KIND } from '@/constants/fileKind.ts'
import { fileLinkDownLoad, formatBytes } from '@/utils/file.ts'
import previewMp3 from './previewMp3'
import previewMp4 from './previewMp4'
import previewImage from './previewImage'

// 视频预览属规范中的工具型例外：播放器按 1200px 设计，不套用 DIALOG_SIZE
const VIDEO_DIALOG_WIDTH = '1200px'

export default {
  name: 'FilePreviewHost',
  // 同步引入：这些组件体积很小，异步化只会带来 $refs 未就绪与首次点击等待 chunk 的问题
  components: { previewMp3, previewMp4, previewImage },
  data() {
    return {
      kind: '',
      status: 'ready', // loading | ready | error
      dialogVisible: false,
      url: '',
      fileName: '',
      fileExt: '',
      fileSize: 0,
      errorMsg: '',
      onRetry: null
    }
  },
  computed: {
    // loading/error 统一用小尺寸壳，就绪后再按类型撑开
    dialogKind() {
      if (this.status !== 'ready') return 'state'
      return this.kind || 'state'
    },
    dialogWidth() {
      if (this.status !== 'ready') return DIALOG_SIZE.XS
      if (this.kind === FILE_KIND.VIDEO) return VIDEO_DIALOG_WIDTH
      if (this.kind === FILE_KIND.AUDIO) return DIALOG_SIZE.SM
      return DIALOG_SIZE.XS
    },
    dialogTop() {
      return this.status === 'ready' && this.kind === FILE_KIND.VIDEO ? '10vh' : '15vh'
    },
    displayName() {
      if (!this.fileName) return '文件预览'
      return this.fileExt && !this.fileName.endsWith(`.${this.fileExt}`)
        ? `${this.fileName}.${this.fileExt}`
        : this.fileName
    },
    sizeText() {
      return this.fileSize ? formatBytes(this.fileSize) : ''
    },
    canRetry() {
      return typeof this.onRetry === 'function'
    }
  },
  methods: {
    /**
     * 打开预览。url 已知时立即渲染内容，未知时先弹壳并显示 loading。
     * @param {Object} payload { kind, name, ext, size, url, list, index, onRetry }
     */
    open(payload = {}) {
      const { kind, name = '', ext = '', size = 0, url = '', list, index, onRetry = null } = payload
      this.kind = kind
      this.fileName = name
      this.fileExt = ext
      this.fileSize = size
      this.url = url
      this.errorMsg = ''
      this.onRetry = onRetry

      if (url) {
        this.renderReady({ list, index })
      } else {
        this.status = 'loading'
        this.dialogVisible = true
      }
    },

    /**
     * 异步取数成功后补齐数据
     * @param {Object} payload { url, name, ext, size, kind, list, index }
     */
    resolve(payload = {}) {
      const { url = '', name, ext, size, kind, list, index } = payload
      if (url) this.url = url
      if (name !== undefined) this.fileName = name
      if (ext !== undefined) this.fileExt = ext
      if (size !== undefined) this.fileSize = size
      if (kind !== undefined) this.kind = kind
      this.renderReady({ list, index })
    },

    /**
     * 异步取数失败
     * @param {String} message
     */
    fail(message) {
      this.errorMsg = message || '文件加载失败'
      this.status = 'error'
      this.dialogVisible = true
    },

    renderReady({ list, index } = {}) {
      this.status = 'ready'
      if (this.kind === FILE_KIND.IMAGE) {
        // 图片走全屏 viewer，壳弹窗要收起来，避免两层叠加
        this.dialogVisible = false
        this.$refs.imageViewer.open(Array.isArray(list) && list.length ? list : [this.url], index || 0)
      } else {
        this.dialogVisible = true
      }
    },

    close() {
      this.dialogVisible = false
    },

    handleClosed() {
      // 卸载播放器，停止后台播放
      this.url = ''
      this.kind = ''
      this.status = 'ready'
      this.errorMsg = ''
      this.onRetry = null
    },

    handleRetry() {
      const retry = this.onRetry
      this.status = 'loading'
      this.errorMsg = ''
      retry && retry()
    },

    handleDownload() {
      if (!this.url) return
      fileLinkDownLoad(this.url, this.displayName)
    },

    async handleCopyLink() {
      if (!this.url) return
      const link = new URL(this.url, window.location.origin).href
      try {
        await navigator.clipboard.writeText(link)
        this.$message.success('链接已复制')
      } catch (error) {
        // 非安全上下文（http 局域网）下 clipboard 不可用，退化为 execCommand
        const input = document.createElement('textarea')
        input.value = link
        input.style.position = 'fixed'
        input.style.opacity = '0'
        document.body.appendChild(input)
        input.select()
        const ok = document.execCommand('copy')
        document.body.removeChild(input)
        ok ? this.$message.success('链接已复制') : this.$message.error('复制失败，请手动复制')
      }
    }
  }
}
</script>

<style scoped lang="scss">
.file-preview-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  .el-icon-close {
    z-index: 99;
    cursor: pointer;
  }
}

.file-preview-state {
  padding: 24px 0 8px;
  text-align: center;
  i {
    font-size: 32px;
    color: #909399;
    &.is-error {
      color: #e6a23c;
    }
  }
  &__text {
    margin-top: 12px;
    font-size: 14px;
    color: #606266;
  }
}

.file-preview-actions {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 10px;
}

.file-preview-audio {
  width: 100%;
  background: #fff;
  border-radius: 6px;
}

.file-preview-unsupported {
  padding: 8px 0;
  text-align: center;
  &__icon {
    width: 66px;
    height: 82px;
    margin: 0 auto;
    background: url('../../assets/images/cloud/file-box.png') no-repeat;
    background-size: 66px 82px;
    line-height: 82px;
    color: #fff;
    font-size: 16px;
    text-transform: uppercase;
  }
  &__name {
    margin-top: 16px;
    font-size: 14px;
    color: #303133;
  }
  &__size {
    margin-top: 6px;
    font-size: 12px;
    color: #909399;
  }
  &__tip {
    margin-top: 12px;
    font-size: 13px;
    color: #606266;
  }
}

.file-preview-video {
  z-index: 120;
}
</style>

<style lang="scss">
// 弹框挂到 body 上，scoped 样式选不到，按 class 定位
.el-dialog.file-preview-dialog {
  &.is-video {
    .el-dialog__body {
      padding: 0;
    }
    .el-dialog__header {
      width: 100%;
      position: absolute;
      top: 0;
      background: linear-gradient(180deg, #000000 0%, rgba(0, 0, 0, 0) 100%);
      font-family: PingFang SC, PingFang SC;
      font-weight: 500;
      font-size: 16px;
      color: #fff;
      border: none;
    }
    .el-dialog__headerbtn .el-dialog__close {
      color: #fff;
    }
  }
}
</style>
