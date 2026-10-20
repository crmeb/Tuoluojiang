/** 带厂商前缀的全屏 API，旧版浏览器兼容用 */
type FullscreenElement = HTMLElement & {
  requestFullScreen?: () => void
  webkitRequestFullScreen?: () => void
  mozRequestFullScreen?: () => void
  msRequestFullscreen?: () => void
}

// 复制文本到剪贴板
export const copy = (text: string) => {
  // 使用textarea可以保留换行
  const input = document.createElement('textarea')
  // input.setAttribute('value', text)
  input.innerHTML = text
  document.body.appendChild(input)
  input.select()
  document.execCommand('copy')
  document.body.removeChild(input)
}

// 复制文本到剪贴板
export const setDataToClipboard = (data: string) => {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(data)
  }
}

// 复制图片到剪贴板
export const setImgToClipboard = (img: Blob) => {
  if (navigator.clipboard) {
    const data = [new ClipboardItem({ ['image/png']: img })]
    navigator.clipboard.write(data)
  }
}

const getOnfullscreEnevt = () => {
  const root = document.documentElement as FullscreenElement
  if (root.requestFullScreen) {
    return 'onfullscreenchange'
  } else if (root.webkitRequestFullScreen) {
    return 'onwebkitfullscreenchange'
  } else if (root.mozRequestFullScreen) {
    return 'onmozfullscreenchange'
  } else if (root.msRequestFullscreen) {
    return 'onmsfullscreenchange'
  }
}

export const fullscrrenEvent = getOnfullscreEnevt()

export const fullScreen = (element: FullscreenElement) => {
  if (element.requestFullScreen) {
    element.requestFullScreen()
  } else if (element.webkitRequestFullScreen) {
    element.webkitRequestFullScreen()
  } else if (element.mozRequestFullScreen) {
    element.mozRequestFullScreen()
  }
}

export const generateXmindFile = async (fileName: string) => {
  const { default: XmindParse } = await import('simple-mind-map/src/parse/xmind')
  const XMIND_FILE_TEMPLATE_DATA = {
    "data": {
      "text": "<p><span style=\"\n      color: #fff;\n      font-family: 微软雅黑, Microsoft YaHei;\n      font-size: 16px;\n      font-weight: bold;\n      font-style: normal;\n      text-decoration: none\n    \">中心主题</span></p>",
      "generalization": [],
      "expand": true,
      "richText": true,
      "isActive": false,
      "uid": "35b082b5-6ec8-4377-bf62-3fb34421e3ff"
    },
    "children": []
  };

  const xmindBlob = await XmindParse.transformToXmind(XMIND_FILE_TEMPLATE_DATA, fileName);
  const xmindFile = new File([xmindBlob], fileName, {
    type: 'application/x-xmind'
  });
  return xmindFile;
};
