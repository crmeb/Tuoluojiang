<!-- 
  @FileDescription: Excel导出组件
  功能：封装Excel导出功能，支持自定义模板、单元格合并和样式设置
-->
<template>
  <!-- 无UI展示，仅提供功能 -->
  <span />
</template>

<script>
import { aoaToXlsxBlob } from '@/utils/excel/writeSheet.ts'

export default {
  name: 'ExportExcel',
  props: {
    // 是否使用模板模式
    template: {
      type: Boolean,
      default: false
    },
    // 导出文件名
    saveName: {
      type: String,
      default: '导出.xlsx'
    },
    // 单元格合并配置
    merges: {
      type: Array,
      default() {
        return []
      }
    },
    // 导出数据配置
    exportData: {
      type: Object,
      default() {
        return {
          data: [],    // 表格数据
          cols: [     // 列宽配置
            { wpx: 70 }, 
            { wpx: 70 }, 
            { wpx: 70 }, 
            { wpx: 70 }
          ],
          rows: []    // 行高配置
        }
      }
    }
  },
  methods: {
    /**
     * 执行Excel导出
     */
    async exportExcel() {
      if (this.exportData.data.length <= 0) {
        this.$message.error('内容为空')
        return
      }

      let merges
      let rows
      // 模板模式处理
      if (this.template) {
        if (this.merges.length) {
          // 自定义合并配置。标题行原先还配置了字体 / 背景样式，但 SheetJS 社区版不会写出样式，
          // 导出文件里从未生效；这里保持无样式，避免导出结果与之前不一致
          merges = this.merges
          rows = [{ hpx: 80 }]
        } else {
          // 默认合并配置
          merges = [{ s: { r: 1, c: 0 }, e: { r: 1, c: 10 } }]
          rows = [{ hpt: 30 }]
        }
      } else {
        // 非模板模式使用传入的行配置
        rows = this.exportData.rows
      }

      const blob = await aoaToXlsxBlob(this.exportData.data, { merges, rows, cols: this.exportData.cols })
      // 触发下载
      this.openDownloadDialog(blob, this.saveName)
    },

    /**
     * 打开下载对话框
     * @param {String|Blob} url - 下载地址或Blob对象
     * @param {String} saveName - 保存文件名
     */
    openDownloadDialog(url, saveName) {
      if (url instanceof Blob) {
        url = URL.createObjectURL(url) // 创建Blob URL
      }
      
      const aLink = document.createElement('a')
      aLink.href = url
      aLink.download = saveName || ''
      
      // 创建并触发点击事件
      const event = window.MouseEvent 
        ? new MouseEvent('click') 
        : document.createEvent('MouseEvents').initMouseEvent(
            'click', true, false, window, 0, 0, 0, 0, 0, 
            false, false, false, false, 0, null
          )
      
      aLink.dispatchEvent(event)
    }
  }
}
</script>

<style lang="scss" scoped>
/* 组件样式 */
.import-excel {
  margin-left: 10px;
  display: inline-block;
}
</style>
