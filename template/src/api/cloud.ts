import request from './request'
import type { Id, ReqData } from '@/types/api'
/**
 * 云文件--企业空间--空间列表
 * @returns {*}
 */
export function folderSpaceListApi() {
  return request.get(`cloud/space/list`)
}

/**
 * 云文件--回收站--批量删除(彻底删除)
 * @param data
 * @returns {*}
 */
export function folderAllDestroyApi(data?: ReqData) {
  return request.delete(`folder/destroy`, data)
}


/**
 * 云文件--回收站--批量恢复文件
 * @param data
 * @returns {*}
 */
export function folderAllDestroyFileApi(data?: ReqData) {
  return request.post(`folder/recover`, data)
}





/**
 * 云文件--我的文件--常用/共享文件数
 * @returns {*}
 */
export function folderTotalApi() {
  return request.get(`folder/total`)
}



/**
 * 云文件--文件夹--列表
 * @returns {*}
 */
export function folderDirListApi() {
  return request.get(`folder/dir_lst`)
}

/**
 * 云文件--文件夹--移动文件/目录
 * @returns {*}
 */
export function folderMoveApi(id: Id, data?: ReqData) {
  return request.post(`folder/move/${id}`, data)
}

/**
 * 云文件--文件夹--批量移动文件/目录
 * @returns {*}
 */
export function folderMoveAllApi(data?: ReqData) {
  return request.post(`folder/move`, data)
}

/**
 * 云文件--企业空间--创建空间
 * @returns {*}
 */
export function folderSpaceAddApi(data?: ReqData) {
  return request.post(`cloud/space/create`, data)
}

/**
 * 云文件--企业空间--编辑空间
 * @returns {*}
 */
export function folderSpaceRenameApi(id: Id, data?: ReqData) {
  return request.post(`enterprise/folder_space/rename/${id}`, data)
}

/**
 * 云文件--企业空间--编辑权限获取
 * @returns {*}
 */
export function folderSpaceShareRuleApi(id: Id, data?: ReqData) {
  return request.get(`cloud/space/rules/${id}`, data)
}

/**
 * 云文件--企业空间--子目录权限获取
 * @returns {*}
 */
export function folderSpaceSubRuleApi(fid: Id, id: Id) {
  return request.get(`cloud/file/${fid}/rules/${id}`)
}


/**
 * 云文件--企业空间--编辑权限修改
 * @returns {*}
 */
export function folderSpaceShareEditApi(id: Id, data?: ReqData) {
  return request.put(`cloud/space/update/${id}`, data)
}

/**
 * 云文件--企业空间--编辑子文件权限
 * @returns {*}
 */
export function folderSubRuleApi(fid: Id, id: Id, data?: ReqData) {
  return request.put(`cloud/file/${fid}/rules/${id}`, data)
}

/**
 * 云文件--企业空间--空间刪除
 * @returns {*}
 */
export function folderSpaceDeleteApi(id: Id) {
  return request.delete(`cloud/space/delete/${id}`)
}

/**
   * 云文件--企业空间--空间详情列表
   * @returns {*}

   */
export function folderSpaceEntListApi(fid: Id, data?: ReqData) {
  return request.get(`cloud/file/${fid}/list`, data)
}
/**
   * 云文件--企业空间--最近预览
   * @returns {*}

   */
export function folderRecentlyListApi(data?: ReqData) {
  return request.get(`cloud/space/lately`, data)
}


/**
   * 云文件--企业空间--文件夹创建
   * @returns {*}

   */
export function folderSpaceEntMakeApi(fid: Id, data?: ReqData) {
  return request.post(`cloud/file/${fid}/folder`, data)
}

/**
   * 云文件--企业空间--文件创建
   * @returns {*}

   */
export function folderSpaceEntCreateApi(id: Id, data?: ReqData) {
  return request.post(`cloud/file/${id}/create`, data)
}

/**
 * 云文件--企业空间--文件夹/文件 重命名
 * @returns {*}
 */
export function folderSpaceEntRenameApi(fid: Id, id: Id, data?: ReqData) {
  return request.post(`cloud/file/${fid}/rename/${id}`, data)
}

/**
 * 云文件--企业空间--回收站--列表
 * @returns {*}

 */
export function folderSpaceEntRecycleApi(data?: ReqData) {
  return request.get(`cloud/space/recycle`, data)
}
/**
 * 云文件--企业空间--回收站--彻底删除
 * @returns {*}

 */
export function folderForceDeleteApi(id: Id) {
  return request.delete(`cloud/space/force_delete/${id}`)
}
/**
 * 云文件--企业空间--回收站--批量彻底删除
 * @returns {*}

 */
export function folderForceDeletesApi(data?: ReqData) {
  return request.delete(`cloud/space/force_deletes`, data)
}

/**
   * 云文件--企业空间--文件夹/文件 删除
   * @returns {*}

   */
export function folderSpaceEntDeleteApi(fid: Id, id: Id) {
  return request.delete(`cloud/file/${fid}/delete/${id}`)
}

/**
   * 云文件--企业空间-- 批量 文件夹/文件 删除
   * @returns {*}

   */
export function folderSpaceEntAllDeleteApi(fid: Id, data?: ReqData) {
  return request.delete(`cloud/file/${fid}/batch_delete`, data)
}

/**
   * 云文件--企业空间--文件夹/文件恢复（从回收站）
   * @returns {*}

   */
export function folderSpaceEntRecoverApi(id: Id) {
  return request.put(`cloud/space/recovery/${id}`)
}

/**
   * 云文件--企业空间--批量文件夹/文件恢复（从回收站）
   * @returns {*}

   */
export function folderSpaceEntAllRecoverApi(data?: ReqData) {
  return request.put(`cloud/space/batch_recovery`, data)
}


/**
 * 云文件--企业空间--批量文件夹/文件彻底删除（从回收站）
 * @returns {*}
 */
export function folderSpaceEntAllDestroyApi(fid: Id, data?: ReqData) {
  return request.delete(`enterprise/folder/${fid}/destroy`, data)
}

/**
   * 云文件--企业空间--空间资源详情
   * @returns {*} enterprise/folder/{fid}/dir_lst

   */
export function folderSpaceEntDetailApi(fid: Id, id: Id) {
  return request.get(`cloud/file/${fid}/info/${id}`)
}

/**
   * 云文件--企业空间--空间目录列表
   * @returns {*} enterprise/folder/{fid}/move/{id}

   */
export function folderSpaceEntDirApi(fid?: unknown) {
  return request.get(`cloud/space/dir`)
}

/**
 * 云文件--企业空间--移动文件或文件夹
 * @returns {*}
 */
export function folderSpaceEntMoveApi(fid: Id, id: Id, data?: ReqData) {
  return request.post(`cloud/file/${fid}/move/${id}`, data)
}

/**
 * 云文件--企业空间--复制文件
 * @returns {*}
 */
export function folderSpaceEntCopyApi(fid: Id, id: Id, data?: ReqData) {
  return request.post(`cloud/file/${fid}/copy/${id}`, data)
}

/**
 * 云文件--企业空间--转让空间
 * @returns {*}
 */
export function folderSpaceEntTransferApi(id: Id, data?: ReqData) {
  return request.post(`cloud/space/transfer/${id}`, data)
}

/**
 * 云文件--企业空间--批量移动文件或文件夹
 * @returns {*}
 */
export function folderSpaceEntAllMoveApi(fid: Id, data?: ReqData) {
  return request.post(`cloud/file/${fid}/batch_move`, data)
}

/**
   * 云文件--企业空间--浏览文件
   * @returns {*}

   */
export function folderSpaceEntViewApi(fid: Id, id: Id) {
  return request.post(`enterprise/folder/${fid}/view/${id}`)
}




/**
 * 云文件-我的文件-创建文件夹
 * @param data folder/create
 * @returns {*}
 */
export function folderMakeApi(data?: ReqData) {
  return request.post(`folder/make`, data)
}

/**
 * 云文件-我的文件-创建文件
 * @param data
 * @returns {*}
 */
export function folderCreateApi(data?: ReqData) {
  return request.post(`folder/create`, data)
}



/**
 * 云文件-我的文件-复制文件
 * @param id
 *  @param data
 * @returns {*}
 */
export function folderCopyApi(id: Id, data?: ReqData) {
  return request.post(`folder/copy/${id}`, data)
}

/**
 * 云文件-我的文件-文件详情
 * @param id
 * @returns {*}
 */
export function folderDetailApi(id: Id) {
  return request.get(`folder/detail/${id}`)
}
/**
 * 云文件-文件详情V1.7
 * @param id
 * @returns {*}
 */
export function getFileInfoApi(fid: Id, id: Id) {
  return request.get(`cloud/file/${fid}/info/${id}`)
}


/**
 * 云文件-模糊搜索-列表
 * @param data
 * @returns {*}
 */
export function folderMatchtListApi(fid: Id, data?: ReqData) {
  return request.get(`enterprise/folder/${fid}/match`, data)
}


/**
 * 云文件--列表--批量删除(从列表删除)
 * @param data
 * @returns {*}
 */
export function folderAllDeleteApi(data?: ReqData) {
  return request.delete(`folder/delete`, data)
}


/**
 * @description 获取附件详情接口
 */
export function getAttachInfoApi(id: Id) {
  return request.get(`system/attach/info/${id}`)
}