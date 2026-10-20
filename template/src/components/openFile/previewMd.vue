<template>
  <div class="box">
    <div v-if="!editBtn" class="m20">
      <!-- 预览文件 -->
      <div class="markdown-body" v-html="renderedMd"></div>
    </div>
    <!-- 编辑文件 md/html-->
    <mavon-editor v-else v-model="htmlMD" ref="md" :toolbars="toolbars"> </mavon-editor>
  </div>
</template>

<script>
import { fileUpload } from '@/api/public.ts'
import { processResourceUrl } from '@/utils/resourceUtil.ts'
import mavon from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'
export default {
  emits: ['closeLoading'],
  name: '',
  props: {
    url: {
      type: String,
      default: ''
    },
    editBtn: {
      type: Boolean,
      default: false
    },
    fid: {
      type: String,
      default: ''
    },
    file: {
      type: Object,
      default: () => ({})
    }
  },
  components: {
    mavonEditor: mavon.mavonEditor
  },
  computed: {
    // 预览复用 mavon-editor 内置的 markdown-it 实例（原 markdown-it-vue 不支持 Vue 3）
    renderedMd() {
      return mavon.markdownIt.render(this.htmlMD || '')
    }
  },
  data() {
    return {
      htmlMD: '',
      toolbars: {
        bold: true, // 粗体
        italic: true, // 斜体
        header: true, // 标题
        underline: true, // 下划线
        strikethrough: true, // 中划线
        mark: true, // 标记
        superscript: true, // 上角标
        subscript: true, // 下角标
        quote: true, // 引用
        ol: true, // 有序列表
        ul: true, // 无序列表
        link: true, // 链接
        imagelink: false, // 图片链接
        code: true, // code
        table: true, // 表格
        fullscreen: true, // 全屏编辑
        readmodel: true, // 沉浸式阅读
        htmlcode: true, // 展示html源码
        help: true, // 帮助
        /* 1.3.5 */
        undo: true, // 上一步
        redo: true, // 下一步
        trash: true, // 清空
        save: false, // 保存（触发events中的save事件）
        /* 1.4.2 */
        navigation: true, // 导航目录
        /* 2.1.8 */
        alignleft: true, // 左对齐
        aligncenter: true, // 居中
        alignright: true, // 右对齐
        /* 2.2.1 */
        subfield: true, // 单双栏模式
        preview: true // 预览
      }
    }
  },
  watch: {
    url(newVal) {
      this.fetchMarkdownAndConvertToHtml()
    }
  },

  mounted() {
    this.fetchMarkdownAndConvertToHtml()

    // if (this.file.file_ext == 'html') {
    //   this.toolbars.subfield = true
    // }
  },
  methods: {
    async fetchMarkdownAndConvertToHtml() {
      const response = await fetch(processResourceUrl(this.url))
      this.htmlMD = await response.text()
    },
    save() {
      let data = {
        content: this.htmlMD,
        is_file: 1
      }
      fileUpload(this.fid, this.file.id, data).then((res) => {
        if (res.status === 200) {
          let path = this.$route.path
          this.$router.push({ path, query: { ...this.$route.query, word_url: res.data.file_url } })
        }
        this.$emit('closeLoading')
      })
    }
  }
}
</script>
<style scoped lang="scss">
.box {
  width: 1000px;
  margin: 0 auto;
  background: #fff;

  margin-top: 90px;
}
.p20 {
  padding: 20px;
}
:deep(.v-note-wrapper) {
  height: calc(100vh - 120px) !important;
  // overflow-y: auto;
}
</style>
