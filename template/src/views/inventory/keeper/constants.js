import { WAREHOUSE_PERMISSION_LIST } from "../constants";

export const MANAGER_TYPE = {
  NORMAL: 0,
  SUPER: 1
}

export const MANAGER_TYPES = [
  { label: "普通库管", value: MANAGER_TYPE.NORMAL },
  { label: "超级管理员", value: MANAGER_TYPE.SUPER }
];

// 单个仓库的可配置权限项（复用仓储公共权限常量，避免多处漂移）
export const PERMISSION_LIST = WAREHOUSE_PERMISSION_LIST;