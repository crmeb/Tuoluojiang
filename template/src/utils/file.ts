import { ElMessage as Message } from 'element-plus'
import { saveAs } from 'file-saver'
import { downloadUrlApi } from '@/api/public'
import type { FileLike } from '@/constants/fileKind'

const FILE_TYPE_ICONS: Record<string, string> = {
  mp3: 'iconMP3geshi mp3',
  mp4: 'iconMP4geshi mp4 ',
  svg: 'iconSVGgeshi svg ',
  ppt: 'iconPPTgeshi ppt',
  pptx: 'iconPPTgeshi ppt',
  xls: 'iconExcelgeshi xls',
  xlsx: 'iconExcelgeshi xls',
  xlsm: 'iconExcelgeshi xls',
  pdf: 'iconPDFgeshi pdf',
  png: 'iconPNGgeshi png',
  jpeg: 'iconPDFgeshi pdf',
  jpg: 'iconJPGgeshi jpg',
  PNG: 'iconPDFgeshi png',
  xmind: 'iconXmindgeshi pdf',
  doc: 'iconwordgeshi doc',
  docx: 'iconwordgeshi doc',
  ai: 'iconAIgeshi ppt',
  sketch: 'iconSketchgeshi tif',
  ps: 'iconPSgeshi gif',
  gif: 'iconGIFgeshi gif',
  tif: 'iconTIFgeshi tif',
  zip: 'iconZipgeshi zip',
  xd: 'iconXDgeshi svg',
  md: 'iconXmindgeshi tif',
  eps: 'iconAIgeshi ppt'
}

const FILE_SORT_FIELDS: Record<string, string> = {
  1: 'file_name',
  2: 'created_at',
  3: 'updated_at',
  4: 'file_size',
  5: 'deleted_at',
  6: 'asc'
}

const FILE_TYPE_NAMES: Record<string, string> = {
  1: '',
  2: 'word',
  3: 'ppt',
  4: 'image',
  5: 'excel'
}

const IMAGE_EXTENSIONS = ['jpeg', 'gif', 'bmp', 'png', 'jpg', 'webp']

export function getFileTypeIconfont(type: number | string, fileType: string) {
  if (Number(type) === 1) {
    return 'iconwenjianjia color-file'
  }
  return FILE_TYPE_ICONS[fileType] || 'iconwordgeshi doc'
}

export function fileDownLoad(item: FileLike) {
  return downloadUrlApi({
    file_id: item.file_sn,
    type: 'folder'
  })
    .then((res) => {
      fileLinkDownLoad(res.data.download_url, item.name || item.file_name)
    })
    .catch((error) => {
      Message.error(error.message)
    })
}

export function fileLinkDownLoad(url: string, name?: string) {
  saveAs(url, name)
}

export function trim(value: string) {
  return value.replace(/(^\s*)|(\s*$)/g, '')
}

export function fileSortName(id: number | string) {
  return FILE_SORT_FIELDS[id] || 'desc'
}

export function fileTypeName(id: number | string) {
  return FILE_TYPE_NAMES[id]
}

export function getFileExtension(filename: string) {
  return filename.slice(filename.lastIndexOf('.') + 1).toLowerCase()
}

export function fileIsImage(value: unknown) {
  const type = String(value || '').toLowerCase()
  if (type.startsWith('image/')) {
    return IMAGE_EXTENSIONS.includes(type.slice('image/'.length))
  }
  return IMAGE_EXTENSIONS.includes(getFileExtension(type))
}

export function getFileType(fileUrl: string) {
  const fileType = getFileExtension(fileUrl)
  if (fileType === 'ppt' || fileType === 'pptx') {
    return 'iconppt color-ppt'
  }
  if (fileType === 'doc' || fileType === 'docx') {
    return 'iconwendang1 color-doc'
  }
  if (fileIsImage(fileType)) {
    return 'img'
  }
  if (fileType === 'xls' || fileType === 'xlsx' || fileType === 'xlsm') {
    return 'iconbiaoge color-excel'
  }
  if (fileType === 'pdf') {
    return 'iconpdf color-pdf'
  }
  return ''
}

export function formatBytes(bytes: number, decimals = 2) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}
