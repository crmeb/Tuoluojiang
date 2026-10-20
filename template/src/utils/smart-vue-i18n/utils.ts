/** 语言包：键值可以是字符串、函数或嵌套对象 */
export type Messages = Record<string, any>

// 值存在
export function isDef<T>(value: T): value is NonNullable<T> {
  return value !== undefined && value !== null
}

/**
 * 从对象中获取指定路径的值
 * @param object - 目标对象
 * @param path - 属性路径，支持点号分隔符
 * @returns 返回属性值或null
 */
export function get(object: unknown, path: string): any {
  // 将路径字符串按点号分隔成数组
  const keys = path.split('.')
  // 初始化结果为目标对象
  let result: any = object

  // 遍历路径数组，逐级获取属性值
  keys.forEach((key) => {
    // 判断当前结果和属性值是否都存在，若存在则获取属性值，否则置为null
    result = isDef(result) && isDef(result[key]) ? result[key] : null
  })

  // 返回最终结果
  return result
}

/**
 * 判断一个值是否为纯对象（对象字面量 / Object.create(null)）
 * 用于把数组、函数、Vue 实例、VueI18n 实例等可能带循环引用的结构挡在深合并之外
 * @param x - 需要判断的值
 * @returns 是纯对象返回 true，否则返回 false
 */
export function isPlainObject(x: unknown): x is Record<string, unknown> {
  if (x === null || typeof x !== 'object') return false
  const proto = Object.getPrototypeOf(x)
  return proto === null || proto === Object.prototype
}


/**
 * 从源对象中复制指定属性到目标对象中
 * @param to - 目标对象
 * @param from - 源对象
 * @param key - 属性名
 */
const { hasOwnProperty } = Object.prototype

function assignKey(to: Messages, from: Messages, key: string) {
  // 获取源对象中指定属性的值
  const val = from[key]

  // 如果源对象中指定属性的值不存在，则直接返回
  if (!isDef(val)) {
    return
  }

  // 只有目标与源同为纯对象时才深合并；其余情况（函数、数组、Vue / VueI18n 实例等
  // 可能带循环引用的结构）一律直接覆盖，避免 deepAssign 无限递归爆栈
  if (!hasOwnProperty.call(to, key) || !isPlainObject(val) || !isPlainObject(to[key])) {
    to[key] = val
  } else {
    // 否则，递归调用deepAssign函数，将源对象中指定属性的值深度合并到目标对象中指定属性的值上
    to[key] = deepAssign(Object(to[key]), from[key])
  }
}


/**
 * 深度合并对象
 * @param to - 目标对象
 * @param from - 源对象
 * @returns 合并后的目标对象
 */
export function deepAssign(to: Messages, from: Messages): Messages {
  // 遍历源对象的所有属性
  Object.keys(from).forEach((key) => {
    // 调用assignKey函数进行属性合并
    assignKey(to, from, key)
  })

  // 返回合并后的目标对象
  return to
}
