import { getDictTreeListApi } from '@/api/form'
import type { ApiResult } from '@/types/api'

export interface DictTreeQuery {
  /** 字典标识 dict_ident */
  types: string
  level?: string | number
}

/**
 * 按字典标识缓存字典树请求，同一字典重复 / 并发读取只请求一次。
 * 由使用方持有实例并决定生命周期（如自定义表单页卸载即丢弃），不做全局缓存。
 */
export function createDictTreeCache() {
  const tasks = new Map<string, Promise<unknown[]>>()

  return {
    load(query: DictTreeQuery): Promise<unknown[]> {
      const cached = tasks.get(query.types)
      if (cached) return cached
      const task: Promise<unknown[]> = getDictTreeListApi(query).then((res: ApiResult<unknown[]>) => res.data)
      tasks.set(query.types, task)
      // 失败不缓存，下次读取重新请求
      task.catch(() => tasks.delete(query.types))
      return task
    }
  }
}
