<template>
  <el-dialog :title="title" v-model="visible" :width="DIALOG_SIZE.XS" append-to-body @closed="handleClosed">
    <div class="channel-code-preview">
      <img v-if="qrCode" :src="qrCode" alt="" class="channel-code-preview__img" />
      <el-button type="primary" class="save-btn" @click="saveQrcode">保存{{ title }}</el-button>
    </div>
  </el-dialog>
</template>

<script>
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { saveAs } from "file-saver";

const DEFAULT_TITLE = '渠道活码'

export default {
  name: 'ChannelCodePreview',
  data() {
    return {
      DIALOG_SIZE,
      visible: false,
      qrCode: '',
      // 弹窗标题兼作保存按钮与空态提示的文案，供自动建群配置等场景复用为「群活码」
      title: DEFAULT_TITLE,
      fileName: `${DEFAULT_TITLE}.png`
    }
  },
  methods: {
    /**
     * @param {Object} row 行数据，取 qr_code 作为图片地址
     * @param {Object} [options] 复用配置：title 覆盖文案，fileName 覆盖下载文件名（不含扩展名）
     */
    openBox(row, options = {}) {
      const title = options.title || DEFAULT_TITLE
      if (!row || !row.qr_code) {
        this.$message.warning(`暂无${title}`)
        return
      }
      this.title = title
      this.qrCode = row.qr_code
      this.fileName = `${options.fileName || row.name || title}.png`
      this.visible = true
    },
    saveQrcode() {
      saveAs(this.qrCode, this.fileName);
    },
    handleClosed() {
      this.qrCode = ''
      this.title = DEFAULT_TITLE
      this.fileName = `${DEFAULT_TITLE}.png`
    }
  }
}
</script>

<style scoped lang="scss">
.channel-code-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 0 20px;

  .save-btn {
    margin-top: 30px;
    min-width: 140px;
  }

  &__img {
    display: block;
    width: 240px;
    height: 240px;
    object-fit: contain;
  }
}
</style>
