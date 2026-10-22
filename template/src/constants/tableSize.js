// 列表页表格尺寸 token。原先挂在 Vue.prototype.tableHeight 上，改为常量按需导入。

/**
 * 列表页 el-table 的统一高度。
 * 269px 是顶部导航 + 面包屑 + 搜索区 + 分页条的固定占位之和。
 */
export const TABLE_HEIGHT = 'calc(100vh - 269px)'
