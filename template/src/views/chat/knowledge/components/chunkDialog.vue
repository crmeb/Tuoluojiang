<!-- 新增 / 编辑分段：只提交 content_html（markdown 源文），content 由后端反推，
     设计稿右侧的「关联问题」本期不做 -->
<template>
  <el-dialog
    :title="isEdit ? '编辑分段' : '新增分段'"
    v-model="visible"
    :width="DIALOG_SIZE.XL"
    :close-on-click-modal="false"
    append-to-body
    @closed="handleClosed"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="0" label-suffix="：">
      <el-form-item label="" prop="content">
        <div v-loading="isUploading" class="editor-box">
          <mavon-editor
            v-if="visible"
            ref="editorRef"
            :model-value="form.contentHtml"
            :toolbars="MARKDOWN_TOOLBARS"
            :subfield="false"
            :box-shadow="false"
            :ishljs="true"
            default-open="edit"
            placeholder="请输入分段内容"
            @change="handleEditorChange"
            @imgAdd="handleImageAdd"
          />
          <div class="editor-count">字数：{{ form.content.length }}/{{ CHUNK_CONTENT_MAX }}</div>
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <div>
        <el-button @click="visible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="isSubmitting || isUploading"
          @click="handleSubmit"
        >
          保存
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import mavon from 'mavon-editor'
import 'mavon-editor/dist/css/index.css'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { knowledgeChunkCreateApi, knowledgeChunkUpdateApi } from '@/api/knowledge.ts'
import { uploader } from '@/utils/uploadCloud.ts'
import { CHUNK_CONTENT_MAX, MARKDOWN_TOOLBARS } from '../constants.ts'
import { decodeHtmlEntities } from '../utils/format.ts'

interface FormState {
  id: number
  content: string
  contentHtml: string
}

const props = defineProps<{ docId: number }>()

const emit = defineEmits<{ (e: 'isOk'): void }>()

// mavon-editor 的类型声明只导出 default，命名导入过不了 vue-tsc，从 default 上取组件
const mavonEditor = mavon.mavonEditor

const visible = ref(false)
const isSubmitting = ref(false)
const formRef = ref<any>(null)
const editorRef = ref<any>(null)
const uploadingCount = ref(0)
const isUploading = computed(() => uploadingCount.value > 0)

// 与项目富文本图片上传保持一致；关闭 uploader 自带成功提示，避免多图上传时连续弹窗
const IMAGE_UPLOAD_OPTIONS = {
  way: 2,
  relation_type: '',
  relation_id: 0,
  eid: 0,
  showTips: false
}

// 弹窗关闭后旧上传即使稍后返回，也不能写进下一次打开的编辑器
let editorSession = 0

const generateForm = (): FormState => ({ id: 0, content: '', contentHtml: '' })

const form = reactive<FormState>(generateForm())

const isEdit = computed(() => !!form.id)

const rules = {
  content: [
    { required: true, message: '请输入分段内容', trigger: 'change' },
    { max: CHUNK_CONTENT_MAX, message: `分段内容不能超过 ${CHUNK_CONTENT_MAX} 字`, trigger: 'change' }
  ]
}

// content 不提交，只作为校验与字数统计的本地镜像。
// 编辑器初始化时也会回调一次空内容，这里只做「有内容就清掉报错」，
// 真正的校验留给保存，避免刚打开弹窗就飘一条必填提示
function handleEditorChange(value: string) {
  form.contentHtml = value
  form.content = (value || '').trim()
  if (form.content && formRef.value) formRef.value.clearValidate('content')
}

function removePendingImage(file: File) {
  const toolbar = editorRef.value && editorRef.value.$refs.toolbar_left
  if (toolbar && toolbar.$imgDelByFilename) toolbar.$imgDelByFilename(file)
}

/** 工具栏选图、拖入图片和编辑区粘贴图片都会走 mavon-editor 的 imgAdd 事件 */
async function handleImageAdd(position: string | number, file: File) {
  const session = editorSession
  uploadingCount.value++
  try {
    const res = await uploader(file, '1', IMAGE_UPLOAD_OPTIONS)
    if (session !== editorSession) return
    if (!res || res.status !== 200 || !res.data || !res.data.url) {
      removePendingImage(file)
      return
    }
    if (editorRef.value) editorRef.value.$img2Url(position, res.data.url)
  } catch {
    if (session === editorSession) removePendingImage(file)
  } finally {
    if (session === editorSession) uploadingCount.value--
  }
}

function handleClosed() {
  editorSession++
  uploadingCount.value = 0
  Object.assign(form, generateForm())
  if (formRef.value) formRef.value.clearValidate()
}

function handleSubmit() {
  formRef.value.validate(async (valid: boolean) => {
    if (!valid) return
    isSubmitting.value = true
    try {
      const params = { content_html: form.contentHtml }
      const res = isEdit.value
        ? await knowledgeChunkUpdateApi(form.id, params)
        : await knowledgeChunkCreateApi({ doc_id: props.docId, ...params })
      // request.js 对业务失败（status 400）是 resolve 而非 reject，
      // 不判一次就会「保存失败却关掉弹窗」，把用户写的内容一并清掉
      if (res.status !== 200) return
      visible.value = false
      emit('isOk')
    } finally {
      isSubmitting.value = false
    }
  })
}

/** 不传 row 为新增；传分段行走编辑 */
function openBox(row?: { id: number; content: string; content_html?: string | null }) {
  editorSession++
  uploadingCount.value = 0
  Object.assign(form, generateForm())
  if (row) {
    form.id = row.id
    form.content = row.content || ''
    // 自动分块只有纯文本，没有 markdown 源文时直接把纯文本喂给编辑器
    form.contentHtml = decodeHtmlEntities(row.content_html || '') || row.content || ''
  }
  visible.value = true
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
.editor-box {
  // 位于 Element Plus 表单内容区（flex 行布局）中，需占满整行
  width: 100%;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;

  :deep(.v-note-wrapper) {
    min-height: 400px;
    // 编辑器不超出视口，长内容在编辑区内部滚动（扣除弹窗上下边距、标题、底部按钮与字数栏）
    max-height: calc(100vh - 380px);
    // 弹窗内不需要再叠一层边框，外层 editor-box 已经给了
    border: none;
  }
}

.editor-count {
  padding: 6px 12px;
  border-top: 1px solid #dcdfe6;
  text-align: right;
  font-size: 12px;
  color: #606266;
}
</style>
