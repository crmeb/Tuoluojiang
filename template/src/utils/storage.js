/**
 * 安全的 localStorage 读写工具
 * 避免直接使用 JSON.parse(localStorage.getItem('xxx')) 导致的崩溃
 */
import { processResourceData } from '@/utils/resourceUtil'

/**
 * 安全读取 localStorage 中的 JSON 数据
 * @param {string} key - 存储键名
 * @param {*} fallback - 解析失败或键不存在时的返回值，默认 null
 * @returns {*} 解析后的数据或 fallback
 */
export function getStorageJson(key, fallback = null) {
  try {
    const raw = localStorage.getItem(key)
    return raw ? processResourceData(JSON.parse(raw)) : fallback
  } catch (e) {
    console.warn(`[getStorageJson] 解析 "${key}" 失败:`, e)
    return fallback
  }
}

/**
 * 安全写入 JSON 数据到 localStorage
 * @param {string} key - 存储键名
 * @param {*} value - 要存储的数据
 */
export function setStorageJson(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(processResourceData(value)))
  } catch (e) {
    console.warn(`[setStorageJson] 写入 "${key}" 失败:`, e)
  }
}
