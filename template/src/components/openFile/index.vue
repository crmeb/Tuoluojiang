<!-- @FileDescription: 文件预览页面-文档类型 -->
<template>
  <div class="file-box">
    <div class="header flex-between">
      <div>{{ file.name }}.{{ file.file_ext }}</div>
      <el-button v-show="editBtn" :loading="loading" type="primary" @click="save">保存</el-button>
    </div>

    <div class="main">
      <component
        v-if="isSupportType"
        :is="type"
        ref="fileEdit"
        :fid="fid"
        :file="file"
        :url="file.file_url"
        :editBtn="editBtn"
        @startLoading="startLoading"
        @closeLoading="closeLoading"
      />
      <el-empty v-else-if="type" description="该文件类型暂不支持在线预览，请下载后查看" />
    </div>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { getFileInfoApi, getAttachInfoApi } from '@/api/cloud.ts'
import { getFileExtension } from '@/utils/file.ts'

// 已注册的预览/编辑组件名，用于兜底不支持的文件类型
const SUPPORT_TYPES = [
  'docx',
  'md',
  'mp3',
  'mp4',
  'pdf',
  'txt',
  'office',
  'docxEdit',
  'xlsxEdit',
  'xmindEdit',
  'xmindPreview'
]

export default {
  name: '',
  components: {
    docx: defineAsyncComponent(() => import('./previewDocx')),
    md: defineAsyncComponent(() => import('./previewMd')),
    mp3: defineAsyncComponent(() => import('./previewMp3')),
    mp4: defineAsyncComponent(() => import('./previewMp4')),
    pdf: defineAsyncComponent(() => import('./previewPdf')),
    txt: defineAsyncComponent(() => import('./previewTxt')),
    office: defineAsyncComponent(() => import('./previewOffice')),
    docxEdit: defineAsyncComponent(() => import('./editDocx')),
    xlsxEdit: defineAsyncComponent(() => import('./editXlsx')),
    xmindEdit: defineAsyncComponent(() => import('./editXmind')),
    xmindPreview: defineAsyncComponent(() => import('./previewXmind'))
  },
  data() {
    return {
      type: '',
      file: {},
      id: 0,
      fid: 0,
      loading: false,
      editBtn: false
    }
  },
  computed: {
    isSupportType() {
      return SUPPORT_TYPES.includes(this.type)
    }
  },
  created() {
    const { id, fid, url, name } = this.$route.query
    this.id = id
    // 直链预览：不依赖云盘文件 id，直接按 url 渲染，且强制只读
    if (url) {
      this.handleRes({ data: this.buildFileByUrl(url, name) }, { readonly: true })
    } else if (fid) {
      this.fid = fid
      this.getFileInfo()
    } else {
      this.getAttachInfo()
    }
  },
  methods: {
    // 获取文件详情和权限
    getFileInfo() {
      getFileInfoApi(this.fid, this.id).then((res) => {
        this.handleRes(res)
      })
    },

    getAttachInfo() {
      getAttachInfoApi(this.id).then((res) => {
        this.handleRes(res)
      })
    },

    /**
     * 由 url 拼出与接口一致的文件信息结构
     * @param {String} url 文件直链
     * @param {String} name 可选的文件名（含后缀），缺省时从 url 中截取
     * @returns {Object}
     */
    buildFileByUrl(url, name) {
      const pureUrl = String(url).split(/[?#]/)[0]
      let realName = name || ''
      if (!realName) {
        const lastPath = pureUrl.slice(pureUrl.lastIndexOf('/') + 1)
        try {
          realName = decodeURIComponent(lastPath)
        } catch (error) {
          realName = lastPath
        }
      }
      // 后缀优先取 url 上的，避免调用方传入不带后缀的展示名
      const fileExt = pureUrl.includes('.')
        ? getFileExtension(pureUrl)
        : realName.includes('.')
        ? getFileExtension(realName)
        : ''

      return {
        file_url: url,
        url,
        file_ext: fileExt,
        real_name: realName || '未命名文件',
        name: realName.replace(/\.[^.]+$/, '') || '未命名文件'
      }
    },

    // 判断文件是预览还是编辑
    handleRes(res, options = {}) {
      const readonly = options.readonly === true
      this.file = res.data
      this.type = res.data.file_ext
      if (res.data.name) {
        document.title = res.data.name
      } else {
        res.data.name = res.data.real_name.split('.')[0]
        document.title = res.data.name
      }

      // 编辑（只读模式下不提供任何编辑能力）
      if (
        !readonly &&
        res.data.auth &&
        res.data.auth.update == 1 &&
        ['docx', 'xlsx', 'txt', 'md', 'html', 'xmind'].includes(res.data.file_ext)
      ) {
        if (res.data.file_ext == 'html') {
          this.type = 'md'
        }
        this.editBtn = true
        if (res.data.file_ext == 'docx') {
          this.type = 'docxEdit'
        } else if (res.data.file_ext == 'xlsx') {
          this.type = 'xlsxEdit'
        } else if (res.data.file_ext == 'xmind') {
          this.type = 'xmindEdit'
        }

        // 仅预览 office 类文件
      } else if (['doc', 'pptx', 'ppt', 'xlsx', 'docx', 'xls'].includes(res.data.file_ext)) {
        this.type = 'office'
        // 仅预览 xmind 类文件
      } else if (['xmind'].includes(res.data.file_ext)) {
        this.type = 'xmindPreview'
        // html 复用 md 组件做纯文本预览
      } else if (res.data.file_ext === 'html') {
        this.type = 'md'
      }
    },
    startLoading() {
      this.loading = true
    },
    closeLoading() {
      this.loading = false
    },
    // 保存
    save() {
      this.loading = true
      if (this.type == 'docxEdit') {
        this.$refs.fileEdit.wordOption()
      } else if (this.type == 'xlsxEdit') {
        this.$refs.fileEdit.exportData(1)
      } else if (this.type == 'txt') {
        this.$refs.fileEdit.save()
      } else if (this.type == 'md') {
        this.$refs.fileEdit.save()
      } else if (this.type == 'xmindEdit') {
        this.$refs.fileEdit.save()
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.file-box {
  height: 100vh;
  overflow-y: auto;
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
  .header {
    z-index: 99;
    position: fixed;
    top: 0;
    width: 100%;
    height: 60px;
    display: flex;
    align-items: center;
    background: #fff;
    padding: 0 20px;
    font-family: PingFang SC, PingFang SC;
    font-weight: 500;
    font-size: 18px;
    color: #303133;
    border-bottom: 1px solid #dcdfe6ff;
  }
  .main {
    width: 100%;
    margin-top: 60px;
  }
}
</style>
