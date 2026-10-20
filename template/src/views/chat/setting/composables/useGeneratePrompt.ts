import { onScopeDispose, ref } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { useUserStore } from '@/stores/user'
import SettingMer from '@/libs/settingMer'
import { openSseStream } from '../../utils/sseStream'

const GENERATE_PROMPT_URL = `${SettingMer.https}/ai/applications/generate-prompt`

const DONE_TOKEN = '[DONE]'

interface GenerateOptions {
  /** AI模型配置ID，未选模型时接口无法执行 */
  modelsId: number | ''
  /** 生成上下文，人设场景传「应用名称 + 应用简介」 */
  content: string
  /** 生成类型，见 PROMPT_TYPE */
  type: string
  /** 开始生成（已通过前置校验、请求已发出），调用方在这里清空目标字段 */
  onStart?: () => void
  /** 正文分片，按到达顺序原样追加即可 */
  onChunk: (chunk: string) => void
}

/** 后端 SSE 帧：正文沿用 OpenAI 结构，控制帧用 type 区分 */
interface PromptStreamFrame {
  type?: string
  message?: string
  choices?: Array<{ delta?: { content?: unknown } }>
}

/** 取出一帧里的正文；控制帧（type=data/thinking/tool 等）一律返回空串 */
const readContent = (frame: PromptStreamFrame): string => {
  const content = frame.choices?.[0]?.delta?.content
  return typeof content === 'string' ? content : ''
}

/**
 * AI 流式生成提示内容
 *
 * 接口是 SSE，只推文本不落库，由调用方决定回填到哪个字段。
 * 组件销毁或重复触发时中断上一次请求。
 */
export const useGeneratePrompt = () => {
  const isGenerating = ref(false)
  let controller: AbortController | null = null

  const stop = () => {
    controller?.abort()
    controller = null
    isGenerating.value = false
  }

  /** 生成结束（正常收尾或失败）返回，失败已弹提示 */
  const generate = ({ modelsId, content, type, onStart, onChunk }: GenerateOptions): Promise<void> => {
    if (!modelsId) {
      Message.warning('请先选择AI模型')
      return Promise.resolve()
    }
    if (!content.trim()) {
      Message.warning('请先填写应用名称或应用简介')
      return Promise.resolve()
    }
    if (isGenerating.value) return Promise.resolve()

    isGenerating.value = true
    controller = new AbortController()

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'text/event-stream',
      laravel_lang: 'zh-cn'
    }
    const token = useUserStore().token
    if (token) headers.Authorization = `Bearer ${token}`

    return new Promise<void>((resolve) => {
      let started = false
      // 首个正文分片到达才清空目标字段：校验失败 / 鉴权失败时不该把用户已有文案抹掉
      const start = () => {
        if (started) return
        started = true
        onStart?.()
      }
      const settle = () => {
        controller = null
        isGenerating.value = false
        resolve()
      }

      openSseStream(GENERATE_PROMPT_URL, {
        headers,
        body: JSON.stringify({ models_id: modelsId, content, type }),
        signal: controller!.signal,
        onMessage: (data) => {
          if (data === DONE_TOKEN) return

          let frame: PromptStreamFrame
          try {
            frame = JSON.parse(data)
          } catch (error) {
            // 协议漂移只记日志：丢一帧不该中断整次生成
            console.warn('生成提示内容：无法解析的流式报文', data)
            return
          }

          if (frame.type === 'error') {
            Message.error(frame.message || '生成失败')
            stop()
            resolve()
            return
          }

          const chunk = readContent(frame)
          if (!chunk) return
          start()
          onChunk(chunk)
        },
        onError: (error) => {
          Message.error(error.message || '生成失败')
          settle()
        },
        onClose: settle
      })
    })
  }

  onScopeDispose(stop)

  return { isGenerating, generate, stop }
}
