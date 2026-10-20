/**
 * 企微渠道活码 / 自动建群配置共用类型
 */
import type { FormItemRule } from 'element-plus'

/** 列表行：操作按钮用到的字段 */
export interface ChannelCodeRow {
  id: number
  name?: string
  room_base_name?: string
  status?: number
}

/** 企微客户群明细 */
export interface GroupChatItem {
  chat_id: string
  name?: string
  [key: string]: unknown
}

/** 加群配置（列表行 / 详情内嵌对象，配置 ID 分别在 id / code_id 上） */
export interface GroupInvoteConfig {
  id?: number
  code_id?: number
  group_chat?: GroupChatItem[]
}

/** 标签 / 员工等只关心 id 的关联项 */
export interface IdItem {
  id: number | string
  [key: string]: unknown
}

/** 欢迎语附件，file 为本地上传用的临时字段，提交前剔除 */
export interface WelcomeAttach {
  file?: unknown
  [key: string]: unknown
}

export type ArrayValidator = NonNullable<FormItemRule['validator']>
