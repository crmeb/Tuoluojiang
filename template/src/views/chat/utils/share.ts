import { ElMessage as Message } from 'element-plus'
import { getAiUrl, getAiBaseUrl } from '@/libs/ai'
import { writeClipboard } from '@/utils/clipboard'

/**
 * 外部应用的访客端链接
 *
 * 路径由 AI SDK 给出，OA 不再手拼 `origin + /chat/visitor/ + shareKey` ——
 * 那样 AI 侧一改路由这里就断，而且没人会发现。
 */
export const getVisitorLink = (shareKey: string) =>
  getAiUrl({ identity: 'visitor', shareKey })

/** 嵌入代码里的 SDK 脚本地址，与 SDK 加载器同源（AI_BASE_URL → httpUrl → 当前 origin） */
export const getSdkUrl = () => new URL('/chat/sdk/index.js', getAiBaseUrl()).href

/**
 * 外部应用的嵌入代码
 *
 * 给第三方站点直接粘进 HTML 用：加载 AI 侧 SDK，再以访客身份挂一个客服弹窗。
 * 脚本地址见 getSdkUrl，不带加载器那个绕缓存的 t 参数——那是运行时的事，不该固化进用户拷走的代码。
 *
 * 首行注释随代码一起复制：拿到代码的多半是对方站点的维护者，
 * 贴哪儿、贴完会出现什么，写在代码里比写在复制提示里更跟得住。
 */
export const getEmbedCode = (shareKey: string) => {
  return `<!-- 陀螺匠 AI 客服：把下面这段代码插入网页 HTML（放在 </body> 之前即可），页面打开后右下角会出现对话入口 -->
<script src="${getSdkUrl()}"></script>
<script>
  window.GyroChat.createChat({
    identity: "visitor",
    shareKey: "${shareKey}",
  })
</script>`
}

/** 复制嵌入代码并提示结果 */
export const copyEmbedCode = async (shareKey: string) => {
  if (!shareKey) {
    Message.warning('该应用暂无分享链接')
    return
  }
  try {
    const isOk = await writeClipboard(getEmbedCode(shareKey))
    isOk ? Message.success('代码已复制') : Message.error('复制失败，请手动复制')
  } catch (error) {
    console.error('AI 嵌入代码生成失败', error)
    Message.error('代码生成失败，请稍后重试')
  }
}

/** 复制访客端链接并提示结果 */
export const copyVisitorLink = async (shareKey: string) => {
  if (!shareKey) {
    Message.warning('该应用暂无分享链接')
    return
  }
  try {
    const isOk = await writeClipboard(await getVisitorLink(shareKey))
    isOk ? Message.success('链接已复制') : Message.error('复制失败，请手动复制')
  } catch (error) {
    console.error('AI 分享链接生成失败', error)
    Message.error('链接生成失败，请稍后重试')
  }
}
