<!-- @FileDescription: 预览图片组件 -->
<template>
  <div>
    <el-image-viewer
      v-if="visible"
      :key="viewerKey"
      :url-list="urlList"
      :initial-index="initialIndex"
      :on-close="close"
    />
  </div>
</template>

<script>
import ElImageViewer from 'element-ui/packages/image/src/image-viewer'

export default {
  name: 'ImageViewer',
  components: { ElImageViewer },
  data() {
    return {
      visible: false,
      // 列表由调用方每次传入并整体替换，不再累积 unshift
      urlList: [],
      initialIndex: 0,
      viewerKey: 0
    }
  },
  methods: {
    /**
     * 打开图片预览
     * @param {Array|String} list 同组图片地址列表，传字符串时按单图处理
     * @param {Number} index 初始展示的下标
     */
    open(list, index = 0) {
      const urls = (Array.isArray(list) ? list : [list]).filter(Boolean)
      if (!urls.length) return
      this.urlList = urls
      this.initialIndex = Math.min(Math.max(index, 0), urls.length - 1)
      this.viewerKey += 1
      this.visible = true
    },
    close() {
      this.visible = false
    }
  }
}
</script>

<style lang="scss" scoped></style>
