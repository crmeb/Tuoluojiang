// 复用项目已有的文件类型图标素材（src/assets/images/fileIcon）
const fileIconContext = import.meta.glob('@/assets/images/fileIcon/*.png', {
  eager: true,
  query: '?url',
  import: 'default'
})

const fileIconModules: Record<string, string> = {}
Object.keys(fileIconContext).forEach((key) => {
  const name = key.slice(key.lastIndexOf('/') + 1).replace(/\.png$/, '')
  fileIconModules[name] = fileIconContext[key] as string
})

// 素材名与后缀名对不上的做一次映射
const ICON_ALIAS: Record<string, string> = {
  doc: 'word',
  docx: 'word',
  markdown: 'md',
  xls: 'xlsx',
  ppt: 'pptx'
}

/** 按文件后缀取图标地址，未收录的统一退回 txt 图标 */
export const getFileIcon = (fileType?: string) => {
  const key = (fileType || '').toLowerCase()
  return fileIconModules[ICON_ALIAS[key] || key] || fileIconModules.txt || ''
}
