<!-- 导入文档：先传附件拿 attach_id，确定时再提交给知识库 -->
<template>
  <el-dialog
    title="导入文档"
    v-model="visible"
    :width="DIALOG_SIZE.MD"
    :close-on-click-modal="false"
    append-to-body
    class="import-dialog"
    @opened="syncAcceptAttr"
    @closed="handleClosed"
  >
    <!-- 不给 el-upload 传 accept：拖拽分支会拿它做大小写敏感的静默过滤，
         .PDF 这类合法文件会被直接丢掉且无任何反馈。格式校验统一放在 handleUpload，
         accept 只写到原生 input 上（见 syncAcceptAttr），保证文件选择器照样过滤 -->
    <!-- loading 挂在拖拽区上而不是文件列表上：列表在没有文件时是隐藏的，
         传第一个文件时才有反馈可看 -->
    <el-upload
      ref="uploadRef"
      v-loading="isUploading"
      drag
      action="##"
      multiple
      :show-file-list="false"
      :http-request="handleUpload"
      class="import-upload"
    >
      <div class="upload-inner">
        <i class="el-icon-upload upload-icon" />
        <div class="upload-text">
          可直接将文件拖拽至此处上传，或 <span class="upload-link">点击添加</span>
        </div>
        <div class="upload-tip">
          支持 {{ acceptText }} 文件，最多可上传 {{ UPLOAD_MAX_COUNT }} 个文件，每个文件不超过
          {{ UPLOAD_MAX_SIZE_MB }}MB
        </div>
      </div>
    </el-upload>

    <div v-if="fileList.length" class="upload-list">
      <div v-for="(item, index) in fileList" :key="item.id" class="upload-item">
        <img :src="getFileIcon(item.fileType)" alt="" class="item-icon" />
        <span class="over-text item-name" :title="item.name">{{ item.name }}</span>
        <span class="item-size">{{ formatBytes(item.size) }}</span>
        <i class="el-icon-delete item-remove" @click="fileList.splice(index, 1)" />
      </div>
    </div>

    <!-- template 上的 v-if 让 $slots.footer 整个不存在，el-dialog 才不会留出空的底栏 -->
    <template v-if="fileList.length" #footer>
      <div class="dialog-footer">
        <span class="upload-title">已上传文档（{{ fileList.length }}/{{ UPLOAD_MAX_COUNT }}）</span>
        <div>
          <el-button @click="visible = false">取消</el-button>
          <el-button type="primary" :loading="isSubmitting" @click="handleSubmit">
            确定
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { uploader } from '@/utils/uploadCloud.ts'
import { getFileExtension, formatBytes } from '@/utils/file.ts'
import { knowledgeDocumentUploadApi } from '@/api/knowledge.ts'
import { getFileIcon } from '../utils/fileIcon.ts'
import { UPLOAD_ACCEPT, UPLOAD_MAX_COUNT, UPLOAD_MAX_SIZE_MB } from '../constants.ts'

interface UploadedFile {
  id: number
  name: string
  size: number
  fileType: string
}

const props = defineProps<{ kbId: number }>()

const emit = defineEmits<{ (e: 'isOk'): void }>()

const visible = ref(false)
const isSubmitting = ref(false)
const fileList = ref<UploadedFile[]>([])
const uploadRef = ref<any>(null)

// 一次可拖入多个文件，上传是并发的：用计数而不是布尔量，
// 否则先返回的那个就把 loading 关掉了，也数不准还能再传几个
const uploadingCount = ref(0)
const isUploading = computed(() => uploadingCount.value > 0)

const acceptAttr = computed(() => UPLOAD_ACCEPT.map((item) => `.${item}`).join(','))
const acceptText = computed(() => UPLOAD_ACCEPT.map((item) => item.toUpperCase()).join('、'))

// 附件走系统统一的云存储上传，way: 2 与 oa-upload 保持一致
const UPLOAD_OPTIONS = { way: 2, relation_type: '', relation_id: '', eid: '' }

async function handleUpload({ file }: { file: File }) {
  const fileType = getFileExtension(file.name).toLowerCase()
  if (!UPLOAD_ACCEPT.includes(fileType)) {
    Message.error(`不支持 ${fileType} 格式`)
    return
  }
  if (file.size / 1024 / 1024 > UPLOAD_MAX_SIZE_MB) {
    Message.error(`单个文件不能超过 ${UPLOAD_MAX_SIZE_MB}MB`)
    return
  }
  // 已入列的 + 正在传的一起算，否则一次拖 30 个文件时每个都以为还有余量
  if (fileList.value.length + uploadingCount.value >= UPLOAD_MAX_COUNT) {
    Message.error(`最多可上传 ${UPLOAD_MAX_COUNT} 个文件`)
    return
  }

  uploadingCount.value++
  try {
    const res = await uploader(file, '0', UPLOAD_OPTIONS)
    if (!res || !res.data) return
    fileList.value.push({
      id: res.data.id,
      name: res.data.name || file.name,
      size: res.data.size || file.size,
      fileType
    })
  } catch {
    // 错误提示由 uploader 统一处理
  } finally {
    uploadingCount.value--
  }
}

async function handleSubmit() {
  isSubmitting.value = true
  try {
    const res = await knowledgeDocumentUploadApi({
      kb_id: props.kbId,
      attach_ids: fileList.value.map((item) => item.id)
    })
    // request.js 对业务失败（status 400）是 resolve 而非 reject，
    // 不判一次就会「导入失败却关掉弹窗」，把已传的文件一并清掉
    if (res.status !== 200) return
    visible.value = false
    emit('isOk')
  } finally {
    isSubmitting.value = false
  }
}

function handleClosed() {
  fileList.value = []
}

/**
 * accept 只写在原生 input 上，让「点击添加」的文件选择器仍按格式过滤；
 * el-upload 的 accept prop 不能用，见模板里的说明。
 * el-dialog 的内容是首次打开才渲染的，所以挂在 opened 上而不是 onMounted。
 */
async function syncAcceptAttr() {
  // 首次打开时弹窗内容与 ref 是同一轮渲染出来的，等一拍再取才拿得到
  await nextTick()
  const input = uploadRef.value && uploadRef.value.$el.querySelector('input[type="file"]')
  if (input) input.setAttribute('accept', acceptAttr.value)
}

function openBox() {
  fileList.value = []
  visible.value = true
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
.import-upload {
  :deep(.el-upload),
  :deep(.el-upload-dragger) {
    width: 100%;
  }

  :deep(.el-upload-dragger) {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-color: #dcdfe6;
  }
}

.upload-inner {
  text-align: center;
}

.upload-icon {
  font-size: 47px;
  color: #1890ff;
  line-height: 1;
}

.upload-text {
  margin-top: 16px;
  font-size: 13px;
  line-height: 18px;
  color: #303133;
}

.upload-link {
  color: #1890ff;
}

.upload-tip {
  margin-top: 8px;
  font-size: 12px;
  line-height: 17px;
  color: #909399;
}

// 底栏左侧是已传数量，右侧是按钮组，两边水平对齐
.dialog-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.upload-title {
  font-size: 13px;
  line-height: 20px;
  color: #606266;
}

.upload-list {
  // 最大高度到顶后内部滚动，避免 20 个文件把弹窗撑出屏幕
  margin-top: 20px;
  max-height: 370px;
  overflow-y: auto;
}

.upload-item {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 46px;
  margin-bottom: 8px;
  padding: 0 14px;
  border-radius: 6px;
  background: #fafafa;

  &:last-child {
    margin-bottom: 0;
  }
}

.item-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.item-name {
  flex: 1;
  min-width: 0;
  font-size: 13px;
  color: #303133;
}

.item-size {
  flex-shrink: 0;
  font-size: 12px;
  color: #909399;
}

.item-remove {
  flex-shrink: 0;
  font-size: 16px;
  color: #909399;
  cursor: pointer;

  &:hover {
    color: #f56c6c;
  }
}

:deep() .el-dialog__body {
  padding: 20px !important;
}
</style>
