/** 后端统一响应体；api 层是 js，TS 侧 await 后按此标注 */
export interface ApiResult<T = unknown> {
  status?: number
  message?: string
  data: T
}

/** 路径参数中的主键，后端既接受数字也接受字符串 */
export type Id = number | string

/** 请求 query / body；存量调用点传入的结构各异，这里只约束为对象 */
export type ReqData = object
