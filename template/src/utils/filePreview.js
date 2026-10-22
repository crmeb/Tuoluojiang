/**
 * 文件预览单例服务。
 *
 * 设计要点：
 * 1. 全局只挂载一个预览宿主组件，业务页面不再各自引入 previewDialog + 挂 ref="viewFile"。
 * 2. 能不请求就不请求：附件列表上已带直链（file_url/url/att_dir）时直接开窗，零接口。
 * 3. 必须请求时（云盘）先开窗再补数据，点击后立刻有反馈。
 * 4. 自增令牌防竞态，连点不同文件只认最后一次。
 */

import Vue from 'vue'
import store from '@/store'
import router from '@/router'
import i18n from '@/lang'
import { roterPre } from '@/settings'
import { getFileInfoApi, getAttachInfoApi } from '@/api/cloud'
import { FILE_KIND, getFileKind, getFileExt, getDirectUrl, getDisplayName } from '@/constants/fileKind'
import PreviewHost from '@/components/openFile/previewDialog.vue'

let host = null
let seq = 0

/** 懒挂载全局唯一的预览宿主 */
function getHost() {
  if (host) return host
  const Ctor = Vue.extend(PreviewHost)
  host = new Ctor({ store, router, i18n })
  host.$mount()
  document.body.appendChild(host.$el)
  return host
}

/** 文档类走 openFile 路由，在新标签页预览 */
function openDocInNewTab(item, { source, spaceId }) {
  const query = new URLSearchParams()
  if (source === 'cloud') {
    query.set('id', item.id)
    query.set('fid', item.pid || spaceId || '')
  } else if (item.id) {
    query.set('id', item.id)
  } else {
    // 没有附件 id 时退化为直链只读预览
    query.set('url', getDirectUrl(item))
    query.set('name', getDisplayName(item))
  }
  window.open(`${roterPre}/openFile?${query.toString()}`, '_blank')
}

/** 从业务对象里取同组图片，用于 viewer 左右切换 */
function buildImageGroup(item, list) {
  const current = getDirectUrl(item)
  if (!Array.isArray(list) || !list.length) return { urls: [current], index: 0 }
  const urls = list
    .filter((row) => getFileKind(getFileExt(row)) === FILE_KIND.IMAGE)
    .map((row) => getDirectUrl(row))
    .filter(Boolean)
  if (!urls.length) return { urls: [current], index: 0 }
  const index = urls.indexOf(current)
  return { urls, index: index > -1 ? index : 0 }
}

/**
 * 后端异常有时会把 PHP 原始报错抛上来，直接展示对用户没有意义。
 * 只透出可读的中文提示，其余统一兜底文案。
 * @param {Error} error
 * @returns {String}
 */
function toReadableError(error) {
  const message = (error && error.message) || ''
  if (message && /[一-龥]/.test(message)) return message
  if (message) console.warn('[filePreview] 文件详情接口异常：', message)
  return '文件加载失败，请稍后重试'
}

/** 文件大小字段在各业务里命名不一 */
function pickSize(item) {
  return item.size || item.file_size || item.att_size || 0
}

/** 云盘/附件详情接口 */
function fetchFileInfo(item, { source, spaceId }) {
  if (source === 'cloud') {
    return getFileInfoApi(item.pid || spaceId, item.id)
  }
  return getAttachInfoApi(item.id)
}

/**
 * 打开文件预览
 * @param {Object} item 文件信息，需含 name/real_name，直链字段任选 file_url/url/att_dir/src
 * @param {Object} [options]
 * @param {String} [options.source] 'cloud' 表示云盘文件，需走文件详情接口
 * @param {Number|String} [options.spaceId] 云盘空间 id
 * @param {Array} [options.list] 同组文件列表，图片预览时用于左右切换
 * @returns {void}
 */
export function filePreview(item, options = {}) {
  if (!item) return
  const { source = '', spaceId = '', list = null } = options
  const name = getDisplayName(item)
  const directUrl = getDirectUrl(item)
  const ext = getFileExt(item)
  const kind = getFileKind(ext)

  // 文档类始终是同步开新标签，本身就是 0ms，无需弹窗
  if (kind === FILE_KIND.DOC) {
    openDocInNewTab(item, { source, spaceId })
    return
  }

  const instance = getHost()
  const token = ++seq

  // 有直链：直接开窗，不发任何请求
  if (directUrl) {
    const payload = { kind, name, ext, size: pickSize(item), url: directUrl }
    if (kind === FILE_KIND.IMAGE) {
      const group = buildImageGroup(item, list)
      payload.list = group.urls
      payload.index = group.index
    }
    instance.open(payload)
    return
  }

  // 无直链：先开窗显示 loading，再取数
  if (!item.id) {
    instance.open({ kind, name, ext, size: pickSize(item), url: '' })
    instance.fail('文件地址不存在，无法预览')
    return
  }

  instance.open({
    kind,
    name,
    ext,
    size: pickSize(item),
    url: '',
    onRetry: () => filePreview(item, options)
  })

  fetchFileInfo(item, { source, spaceId })
    .then((res) => {
      if (token !== seq) return // 已被后续点击取代
      const data = (res && res.data) || {}
      if (!data.file_url) {
        instance.fail('文件地址不存在，无法预览')
        return
      }
      const realExt = data.file_ext || ext
      instance.resolve({
        url: data.file_url,
        name: data.real_name || data.name || name,
        ext: realExt,
        size: data.file_size || data.size || 0,
        kind: getFileKind(realExt),
        list: data.file_url ? [data.file_url] : []
      })
    })
    .catch((error) => {
      if (token !== seq) return
      instance.fail(toReadableError(error))
    })
}
