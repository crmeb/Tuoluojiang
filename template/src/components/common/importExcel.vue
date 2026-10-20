<!-- @FileDescription: 公共-xlsx导入组件 -->
<template>
  <div class="import-excel">
    <input
      id="referenceUpload"
      class="input-file"
      type="file"
      accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
      @change="exportData"
    />
    <el-button v-show="false" @click="btnClick">{{
      distinguish == 2 ? '导入' : '导入'
    }}</el-button>
  </div>
</template>

<script>
import { readFirstSheetRows } from '@/utils/excel/readSheet.ts'
export default {
  emits: ['importExcelData'],
  name: 'ImportExcel',
  props: {
    columnNumber: {
      type: Number,
      default: 0
    },
    type: {
      type: String,
      default: ''
    },
    distinguish: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      rowNumber: 1
    }
  },
  methods: {
    btnClick() {
      // 点击事件
      document.querySelector('.input-file').click()
    },
    async exportData(event) {
      const input = event.currentTarget
      if (!input.files.length) {
        return
      }
      const f = input.files[0]
      try {
        const rows = await readFirstSheetRows(f)
        const arrRes = rows.slice(this.columnNumber)
        // 自定义方法向父组件传递数据
        this.$emit('importExcelData', arrRes, f.name)
      } catch (error) {
        this.$message.error('文件解析失败，请检查文件格式')
      } finally {
        // 清空选择，保证再次选择同一文件时仍会触发 change
        input.value = null
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.import-excel {
  position: fixed;
  top: -300px;
  left: 0;

  .input-file {
    position: fixed;
    top: -300px;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
  }
}
</style>
