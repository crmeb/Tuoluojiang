/**
 * 基于 XMLHttpRequest 的 SSE 客户端（写法对齐 chat 项目 `core/chat/api/chat.stream-xhr.ts`）。
 *
 * 不用 EventSource：它只能 GET、也带不了 Authorization 头；
 * 不用 fetch + ReadableStream：部分自建网络库接管 http(s) 后响应体不增量投递，
 * 表现为「一直转圈、结束时一次性刷出全文」。只有 XHR 三者都满足。
 */

/** SSE 允许 \r\n / \n / \r 三种换行，空行即一帧结束 */
const LINE_SEPARATOR = /\r\n|\n|\r/

/**
 * 取出一帧里所有 data 字段，按规范用 \n 连接。
 * 注释行（`:` 开头，通常是心跳）与 event / id / retry 字段一律忽略。
 */
const readFrameData = (frame: string): string | undefined => {
  const values: string[] = []

  for (const line of frame.split(LINE_SEPARATOR)) {
    if (line === '' || line.startsWith(':')) continue

    const colon = line.indexOf(':')
    if ((colon < 0 ? line : line.slice(0, colon)) !== 'data') continue

    // 规范规定冒号后紧跟的单个空格属于分隔符，不算内容
    const value = colon < 0 ? '' : line.slice(colon + 1)
    values.push(value.startsWith(' ') ? value.slice(1) : value)
  }

  return values.length > 0 ? values.join('\n') : undefined
}

/** 后端校验失败等场景会返回普通 JSON，从中取出可展示的错误文案 */
const readErrorMessage = (text: string): string => {
  try {
    const json = JSON.parse(text)
    return typeof json?.message === 'string' && json.message ? json.message : ''
  } catch (error) {
    return ''
  }
}

export interface SseStreamRequest {
  method?: string
  headers?: Record<string, string>
  body?: string
  /** 外部中断信号；中断后不再触发 onMessage / onError / onClose */
  signal?: AbortSignal
  /** 一帧里 data 字段的拼接结果 */
  onMessage: (data: string) => void
  onError: (error: Error) => void
  /** 服务端正常结束。中断与失败都不会走到这里 */
  onClose: () => void
}

const EVENT_STREAM_CONTENT_TYPE = 'text/event-stream'

export const openSseStream = (url: string, request: SseStreamRequest) => {
  const { method = 'POST', headers = {}, body, signal, onMessage, onError, onClose } = request
  const xhr = new XMLHttpRequest()

  let settled = false
  // responseText 是累积的，记住上一帧的结束位置，每次只扫新增部分
  let offset = 0
  // 响应头不是 SSE 时改走「整包读完再报错」，不要按帧解析
  let isEventStream = true

  const fail = (error: Error) => {
    if (settled) return
    settled = true
    xhr.abort()
    onError(error)
  }

  const drain = () => {
    const text = xhr.responseText
    // 每次新建：正则带 g 时 lastIndex 是实例状态，模块级共享会被并发的流互相踩
    const separator = /\r\n\r\n|\n\n|\r\r/g
    separator.lastIndex = offset

    for (let match = separator.exec(text); match; match = separator.exec(text)) {
      const data = readFrameData(text.slice(offset, match.index))
      offset = match.index + match[0].length
      separator.lastIndex = offset

      if (data !== undefined) onMessage(data)
      // onMessage 里可能已经中断（错误帧、调用方停止生成）
      if (settled) return
    }
  }

  const onAbort = () => {
    if (settled) return
    settled = true
    xhr.abort()
  }

  xhr.open(method, url, true)
  for (const [name, value] of Object.entries(headers)) xhr.setRequestHeader(name, value)

  xhr.onreadystatechange = () => {
    if (xhr.readyState !== XMLHttpRequest.HEADERS_RECEIVED || settled) return

    const contentType = xhr.getResponseHeader('content-type') || ''
    isEventStream = xhr.status >= 200 && xhr.status < 300 && contentType.includes(EVENT_STREAM_CONTENT_TYPE)
  }

  xhr.onprogress = () => {
    if (settled || !isEventStream) return
    try {
      drain()
    } catch (error) {
      fail(error instanceof Error ? error : new Error(String(error)))
    }
  }

  xhr.onload = () => {
    if (settled) return
    if (!isEventStream) {
      // 走到这里说明后端没进流式分支（参数校验失败、登录态失效等），响应体是普通 JSON
      return fail(new Error(readErrorMessage(xhr.responseText) || '生成失败'))
    }

    // 最后一批数据未必触发过 progress，收尾再扫一次
    try {
      drain()
    } catch (error) {
      return fail(error instanceof Error ? error : new Error(String(error)))
    }
    if (settled) return

    settled = true
    onClose()
  }

  xhr.onerror = () => fail(new Error('连接失败'))
  xhr.ontimeout = () => fail(new Error('连接超时'))
  xhr.onloadend = () => signal?.removeEventListener('abort', onAbort)

  if (signal?.aborted) return

  signal?.addEventListener('abort', onAbort, { once: true })
  xhr.send(body)
}
