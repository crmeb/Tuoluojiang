/** 写剪贴板，非安全上下文（http 局域网）下 navigator.clipboard 不可用，退化为 execCommand */
export const writeClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch (error) {
    const input = document.createElement('textarea')
    input.value = text
    input.style.position = 'fixed'
    input.style.opacity = '0'
    document.body.appendChild(input)
    input.select()
    const isOk = document.execCommand('copy')
    document.body.removeChild(input)
    return isOk
  }
}
