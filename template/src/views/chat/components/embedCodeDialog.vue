<!--
  外部应用嵌入代码弹窗
  「复制代码」不再直接写剪贴板：先把代码和接入步骤摊开给用户看，确认后再复制，
  否则拿到代码的人不知道该贴到哪、贴完会发生什么。
-->
<template>
  <el-dialog
    title="嵌入代码"
    v-model="visible"
    :width="DIALOG_SIZE.LG"
    append-to-body
    class="embed-code-dialog"
  >
    <div class="guide">
      <div class="guide-title">使用说明</div>
      <ol class="guide-steps">
        <li>点击下方「复制代码」，复制整段嵌入代码。</li>
        <li>
          打开需要接入的网站页面 HTML，将代码粘贴到 <code>&lt;/body&gt;</code> 标签之前；多个页面需要接入时，每个页面都要粘贴，或放入站点公共模板。
        </li>
        <li>保存并刷新页面，右下角出现对话入口即接入成功，访客点击即可与「{{ appName }}」对话。</li>
      </ol>
      <div class="guide-tips">
        注意：应用需保持「已发布」状态，取消发布或删除应用后已嵌入的代码将失效；若网站配置了内容安全策略（CSP），需放行
        <code>{{ sdkOrigin }}</code>。
      </div>
    </div>

    <div class="code-block">
      <div class="code-header">
        <span>HTML</span>
        <span class="code-copy" @click="handleCopy">
          <i class="el-icon-document-copy"></i>
          复制
        </span>
      </div>
      <pre class="code-content"><code>{{ code }}</code></pre>
    </div>

    <template #footer>
      <el-button @click="visible = false">关 闭</el-button>
      <el-button type="primary" @click="handleCopy">复制代码</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { getEmbedCode, getSdkUrl, copyEmbedCode } from '../utils/share.ts'

const visible = ref(false)
const appName = ref('')
const shareKey = ref('')
const code = ref('')
const sdkOrigin = ref('')

const handleCopy = () => copyEmbedCode(shareKey.value)

const openBox = (row: { name?: string; share_key?: string }) => {
  const key = row.share_key || ''
  if (!key) {
    Message.warning('该应用暂无分享链接')
    return
  }
  try {
    code.value = getEmbedCode(key)
    sdkOrigin.value = new URL(getSdkUrl()).origin
  } catch (error) {
    console.error('AI 嵌入代码生成失败', error)
    Message.error('代码生成失败，请稍后重试')
    return
  }
  appName.value = row.name || ''
  shareKey.value = key
  visible.value = true
}

defineExpose({ openBox })
</script>

<style scoped lang="scss">
.guide {
  font-size: 14px;
  line-height: 22px;
  color: #606266;

  .guide-title {
    font-weight: 500;
    color: #303133;
  }

  .guide-steps {
    margin: 8px 0 0;
    padding-left: 20px;
    list-style: decimal;

    li + li {
      margin-top: 4px;
    }
  }

  .guide-tips {
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 4px;
    background: rgba(255, 153, 0, 0.08);
    font-size: 13px;
    line-height: 20px;
    color: #ff9900;
  }

  code {
    padding: 0 4px;
    border-radius: 3px;
    background: #f2f3f5;
    font-family: Menlo, Consolas, monospace;
    font-size: 12px;
    color: #303133;
    word-break: break-all;
  }
}

.code-block {
  margin-top: 16px;
  border-radius: 6px;
  background: #1f2329;
  overflow: hidden;

  .code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 36px;
    padding: 0 14px;
    background: #2b3038;
    font-size: 12px;
    color: #a0a6b1;
  }

  .code-copy {
    cursor: pointer;

    &:hover {
      color: #fff;
    }
  }

  .code-content {
    margin: 0;
    padding: 14px;
    overflow-x: auto;
    font-family: Menlo, Consolas, monospace;
    font-size: 13px;
    line-height: 20px;
    color: #e6e8eb;
    white-space: pre;
  }
}
</style>
