import router from '../router'
import settings from '@/settings'
import i18n from '@/lang'
import { MessageBox } from 'element-ui'

export function modalSure(title, confirmButton = i18n.t('public.ok'), tips = i18n.t('public.tips')) {
  return new Promise((resolve, reject) => {
    MessageBox.confirm(`${title || i18n.t('publicjs.title1')}?`, tips, {
      confirmButtonText: confirmButton,
      cancelButtonText: i18n.t('public.cancel'),
      type: 'warning'
    })
      .then(() => {
        resolve()
      })
      .catch(() => {
        reject()
      })
  })
}



/**
 * @description 短信是否登录
 */
export function wss(wsSocketUrl) {
  const ishttps = document.location.protocol === 'https:'
  if (ishttps) {
    return wsSocketUrl.replace('ws:', 'wss:')
  } else {
    return wsSocketUrl.replace('wss:', 'ws:')
  }
}



/**
 * 跳转页面
 * @param {String} url
 * @param {Object} query
 */
export const pageJumpTo = (url, query = {}) => {
 
  router.push({
    path: settings.roterPre + url,
    query: query
  })
}

/**
 * 修改消息推送页面跳转
 * @param {Object} item
 * @returns {*}
 */
export const toMessageDetailUrl = (item) => {
  const selectedType = ['delete', 'recall']
  if (!item.url || selectedType.includes(item.action)) return false
  pageJumpTo(item.url)
}

/**
 * 判断数组中是否包含某值
 * @param {Array} arr
 * @param {Number} value
 * @returns {Boolean}
 */
export const isInArray = (arr, value) => {
  return arr.includes(value)
}

/**
 * 计算两个时间差(小时分钟)
 * @param {startDate} 开始时间 9:00
 * @param {endDate} 结束时间 18:00
 * @returns {String}
 */
export const getInervalHour = (startDate, endDate) => {
  let dateDiff = endDate - startDate
  let residue1 = dateDiff % (24 * 3600 * 1000)
  let hours = Math.floor(residue1 / (3600 * 1000))
  let residue2 = residue1 % (3600 * 1000)
  let minutes = Math.floor(residue2 / (60 * 1000))
  return [hours, minutes]
}

/**
 * 计算两个时间差(小时分钟)
 * @param {startDate} 开始时间 9:00
 * @param {endDate} 结束时间 18:00
 * @returns {String}
 */
export const getInervalTwoHour = (startDate, endDate, start, end) => {
  let dateDiff = endDate - startDate - (end - start)
  let residue1 = dateDiff % (24 * 3600 * 1000)
  let hours = Math.floor(residue1 / (3600 * 1000))
  let residue2 = residue1 % (3600 * 1000)
  let minutes = Math.floor(residue2 / (60 * 1000))
  return [hours, minutes]
}
/**
 * 计算两个时间和(小时分钟)
 * @param {startDate} 开始时间 9:00
 * @param {endDate} 结束时间 18:00
 * @returns {String}
 */
export const getHour = (startDate, endDate, start, end) => {
  let dateDiff = endDate - startDate + (end - start)
  let residue1 = dateDiff % (24 * 3600 * 1000)
  let hours = Math.floor(residue1 / (3600 * 1000))
  let residue2 = residue1 % (3600 * 1000)
  let minutes = Math.floor(residue2 / (60 * 1000))
  return [hours, minutes]
}


/**
 * 根据filter 数组对象去重
 * @param {Array} array
 * @param {String} filter 过滤参数
 * @returns {Array} {*}
 */
export const removeDuplicateObjects = (array, filter = 'id') => {
  let uniqueArray = array.reduce(
    (accumulator, item) => {
      // 使用对象的id作为键来判断是否已经存在
      if (!accumulator[item[filter]]) {
        accumulator[item[filter]] = true
        accumulator.result.push(item)
      }
      return accumulator
    },
    { result: [] }
  )

  return uniqueArray.result
}


/**
 * 根据filter 数组差集
 * @param {Array} array1
 * @param {Array} array2
 * @param {String} filter 过滤参数
 * @returns {*}
 */

export const getArrayDifference = (array1, array2, filter = 'id') => {
  return array1.filter((v) => array2.every((val) => val[filter] != v[filter]))
}


/**
 * 根据filter 根据id找出对应的数据集合
 * @param {Array} arr1
 * @param {Array} arr2
 * @param {String} filter 过滤参数
 * @returns {*}
 */

export const getIdsArray=(arr1,arr2, filter = 'id')=> {
  return  arr1.filter((ele) => 
    arr2.filter((x) => x === ele[filter]).length > 0
);
}


/**
 * 根据filter 过滤filter集合
 * @param {Array} arr
 * @param {String} filter 过滤参数
 * @returns {*}
 */
export const extractArrayIds = (arr, filter = 'id') => {
    return arr&&arr.length > 0 ? arr.map((department) => department[filter]) : []

}
