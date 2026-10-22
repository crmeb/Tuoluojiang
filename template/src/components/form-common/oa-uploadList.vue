<!-- @FileDescription: 上传文件后渲染组件  -->
<template>
  <div class="width100">
    <div class="file-box">
      <div
        class="fileItem"
        v-for="(fileItem, index) in Array.isArray(fileList) ? fileList : []"
        :key="index"
        :style="`width: ${isTwoColumnShow ? 'calc((100% - 15px) / 2)' : '100%'};`"
      >
        <div class="file-item-left">
          <div class="file" v-if="toSrcIcon(fileItem.name) !== 'img'" @click.stop="handlePreview(fileItem)">{{ getFileTypeFn(fileItem.name) }}</div>
          <img
            v-else
            @click.stop="handlePreview(fileItem)"
            :src="fileItem.file_url || fileItem.url || fileItem.src"
            alt=""
            class="img"
            loading="lazy"
          />
        </div>

        <div class="file-item-right">
          <div class="file-name over-text">{{ fileItem.name }}</div>
          <div class="file-size over-text" v-if="fileItem.size">{{ toSizeFile(fileItem.size) }}</div>
        </div>
        <!-- 新增预览和下载图标 -->
        <div class="file-actions">
          <div class="file-actions-inner">
            <i class="iconfont iconyulan" @click.stop="handlePreview(fileItem)"></i>
            <i class="iconfont iconxiazai" @click.stop="downLoad(fileItem)"></i>
            <i v-if="showClose" class="file-close el-icon-error" @click.stop="fileDelete(index)"></i>
          </div>
        </div>
      </div>
      <div v-if="!(Array.isArray(fileList) && fileList.length > 0)">--</div>
    </div>
  </div>
</template>

<script>
import { formatBytes, getFileExtension, getFileType } from '@/utils/file'
import { filePreview } from '@/utils/filePreview'
export default {
  name: 'OaUploadList',
  props: {
    fileList: {
      type: Array,
      default: () => []
    },
    showClose: {
      type: Boolean,
      default: false
    },
    isTwoColumnShow: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    // 把整组文件传给预览服务，图片预览时可左右切换
    handlePreview(fileItem) {
      filePreview(fileItem, { list: Array.isArray(this.fileList) ? this.fileList : [] })
    },
    toSrcIcon(name) {
      return getFileType(name)
    },
    toSizeFile(size) {
      return formatBytes(size)
    },
    downLoad(fileItem) {
      const url = fileItem.file_url || fileItem.url || fileItem.src
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', fileItem.name)
      // 隐藏链接，避免影响页面显示
      link.style.display = 'none'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    },
    getFileTypeFn(name) {
      return getFileExtension(name)
    },
    fileDelete(index) {
      // 不直接修改prop，而是发出事件让父组件处理
      this.$emit('fileDelete', index)
    }
  }
}
</script>

<style scoped lang="scss">
.file-box {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}
.fileItem {
  cursor: pointer;
  width: 100%;
  background-color: #F7F7F7;
  display: flex;
  height: auto;
  line-height: 1;
  align-items: center;
  padding: 8px 12px;
  color: #303133;

  position: relative;
  border-radius: 4px;
  // margin-bottom: 8px;
  &:last-of-type {
    margin-bottom: 0;
  }
  .file-close {
    font-size: 18px;
    // position: absolute;

    // right: 0px;
    color: #c0c4cc;
    cursor: pointer;
  }
  .file-item-left {
    width: 36px;
    cursor: pointer;
    .iconfont {
      font-size: 36px;
    }
    .img {
      display: inline-block;
      width: 36px;
      height: 36px;
      border-radius: 2px 2px 2px 2px;
    }
  }
  .file-item-right {
    flex: 1;
    overflow: hidden;
    height: 36px;
    display: flex;
    justify-content: space-between;
    flex-direction: column;
    padding: 0 20px 0 6px;
    .file-name {
      font-size: 13px;
      line-height: 1.5;
    }
    .file-size {
      font-size: 12px;
      line-height: 1.5;
    }
  }
}

.file {
  display: flex;
  width: 33px;
  height: 41px;
  background: url('../../assets/images/cloud/file-box.png') no-repeat;
  background-size: 33px 41px;
  color: #fff !important;
  justify-content: center;
  line-height: 41px;
  font-size: 13px;
  margin-right: 10px;
}

.file-actions {
  display: none;
  margin-left: 10px;
}

.file-actions-inner {
  font-size: 18px;
  color: #303033;
  display: flex;
  gap: 6px;
}

.fileItem:hover .file-actions {
  display: initial;
}
</style>
