// 文件预览的类型分类。原先 utils/file.js 把图片和音视频混在一个数组里，
// 导致无法针对不同类型给出不同的即时反馈，这里按预览方式拆开。

import { getFileExtension } from '@/utils/file'

/** 图片：直接走 el-image-viewer */
const IMAGE_EXTS = Object.freeze(['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico'])

/** 视频：弹窗内嵌 video */
const VIDEO_EXTS = Object.freeze(['mp4', 'mov', 'avi', 'rmvb', 'wmv', 'mkv', 'flv', 'webm'])

/** 音频：弹窗内嵌 audio */
const AUDIO_EXTS = Object.freeze(['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac'])

/** 文档：跳 openFile 路由，在新标签页预览 */
const DOC_EXTS = Object.freeze([
  'doc',
  'docx',
  'xls',
  'xlsx',
  'xlsm',
  'ppt',
  'pptx',
  'pdf',
  'txt',
  'md',
  'html',
  'xmind'
])

export const FILE_KIND = Object.freeze({
  IMAGE: 'image',
  VIDEO: 'video',
  AUDIO: 'audio',
  DOC: 'doc',
  UNKNOWN: 'unknown'
})

/**
 * 判断文件的预览分类
 * @param {String} nameOrExt 文件名（含后缀）或纯后缀
 * @returns {String} FILE_KIND 之一
 */
export function getFileKind(nameOrExt) {
  if (!nameOrExt) return FILE_KIND.UNKNOWN
  const ext = getFileExtension(String(nameOrExt))
  if (IMAGE_EXTS.includes(ext)) return FILE_KIND.IMAGE
  if (VIDEO_EXTS.includes(ext)) return FILE_KIND.VIDEO
  if (AUDIO_EXTS.includes(ext)) return FILE_KIND.AUDIO
  if (DOC_EXTS.includes(ext)) return FILE_KIND.DOC
  return FILE_KIND.UNKNOWN
}

/**
 * 取文件在业务对象上的直链地址。各业务列表字段名不统一，这里统一兜底。
 * @param {Object} item 文件信息
 * @returns {String}
 */
export function getDirectUrl(item) {
  if (!item) return ''
  return item.file_url || item.url || item.att_dir || item.src || ''
}

/**
 * 取文件展示名。云盘用 real_name，附件用 name。
 * @param {Object} item 文件信息
 * @returns {String}
 */
export function getDisplayName(item) {
  if (!item) return ''
  return item.real_name || item.name || item.file_name || ''
}

/** 仅在字符串确实带后缀时才取后缀，避免把不含点的展示名整个当成后缀 */
function extOf(value) {
  if (!value) return ''
  const pure = String(value).split(/[?#]/)[0]
  return pure.includes('.') ? getFileExtension(pure) : ''
}

/**
 * 取文件后缀。各业务字段命名不一致，按可靠度依次兜底。
 * @param {Object} item 文件信息
 * @returns {String}
 */
export function getFileExt(item) {
  if (!item) return ''
  return item.file_ext || extOf(item.name) || extOf(item.real_name) || extOf(getDirectUrl(item))
}
