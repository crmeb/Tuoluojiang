<template>
  <div class="divBox">
    <el-card class="employees-card-bottom role-page-card">
      <div class="role-page-header">
        <div>
          <div class="page-title">角色权限</div>
          <div class="page-desc">维护角色成员、启用状态与权限配置</div>
        </div>
        <el-button type="primary" icon="el-icon-plus" @click="addAdminRole">新增</el-button>
      </div>
      <div v-if="roleList.length" class="role-layout">
        <div class="role-sidebar">
          <div class="role-sidebar-title">
            <span>角色列表</span>
            <span class="role-total">共 {{ total }} 个</span>
          </div>
          <div class="role-search">
            <el-input
              v-model="roleKeyword"
              prefix-icon="el-icon-search"
              clearable
              placeholder="搜索角色"
            />
          </div>
          <div class="role-list">
            <div
              v-for="item in filteredRoleList"
              :key="item.id"
              :class="{ active: selectedRole && selectedRole.id === item.id }"
              class="role-list-item"
              @click="selectRole(item)"
            >
              <div class="role-item-main">
                <div class="role-name">{{ item.role_name }}</div>
                <div class="role-meta">
                  <span
                    class="role-status-dot"
                    :class="{ 'is-active': Number(item.status) === 1 }"
                    :title="Number(item.status) === 1 ? '已启用' : '已禁用'"
                  ></span>
                  <span>{{ getMemberCount(item) }} 人</span>
                </div>
              </div>
              <div class="role-actions-more" @click.stop>
                <el-dropdown trigger="click" @command="handleRoleCommand($event, item)">
                  <el-button type="primary" link icon="el-icon-more" class="role-more-button"></el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="status">{{
                        Number(item.status) === 1 ? '禁用' : '启用'
                      }}</el-dropdown-item>
                      <el-dropdown-item command="member">管理成员</el-dropdown-item>
                      <el-dropdown-item command="edit">编辑</el-dropdown-item>
                      <el-dropdown-item command="delete" divided>删除</el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>
            </div>
            <el-empty v-if="filteredRoleList.length === 0" :image-size="80" description="暂无匹配角色"></el-empty>
          </div>
        </div>

        <div v-if="selectedRole" v-loading="roleDetailLoading" class="role-detail">
          <div class="role-detail-content">
            <div class="readonly-form-row">
              <span class="readonly-form-label">角色名称：</span>
              <span class="readonly-form-value">{{ currentRole.role_name || '--' }}</span>
            </div>

            <el-tabs v-model="activeDetailTab" type="border-card" class="role-detail-tabs">
              <el-tab-pane label="权限数据" name="1">
                <div class="readonly-permission-layout">
                  <div class="readonly-menu-tree">
                    <div class="detail-column-title">菜单权限</div>
                    <el-tree
                      v-if="readonlyTreeData.length"
                      :key="selectedRole.id"
                      :data="readonlyTreeData"
                      :default-checked-keys="checkedMenuKeys"
                      :default-expanded-keys="defaultExpandedMenuKeys"
                      :expand-on-click-node="false"
                      :highlight-current="true"
                      :props="treeProps"
                      accordion
                      class="blue-theme readonly-tree"
                      node-key="value"
                      show-checkbox
                      @node-click="handlePermissionNodeClick"
                    />
                    <el-empty v-else :image-size="80" description="暂无菜单权限"></el-empty>
                  </div>

                  <div class="readonly-api-list">
                    <div class="detail-column-title">按钮/接口权限</div>
                    <div v-if="activeApiList.length" class="api-checkbox-list">
                      <el-checkbox
                        v-for="item in activeApiList"
                        :key="item.value"
                        :checked="getApiChecked(item)"
                        disabled
                        class="readonly-checkbox"
                      >
                        {{ item.label }}
                      </el-checkbox>
                    </div>
                    <el-empty v-else :image-size="80" description="请选择左侧菜单查看接口权限"></el-empty>
                  </div>

                  <div class="readonly-module-permission">
                    <div class="detail-column-title">数据权限</div>
                    <el-table :data="modulePermissionRows">
                      <el-table-column label="模块" min-width="140">
                        <template #default="{ row }">
                          {{ row.module_name || row.name || row.key || '--' }}
                        </template>
                      </el-table-column>
                      <el-table-column label="数据权限" min-width="140">
                        <template #default="{ row }">
                          {{ getModuleDataLevelText(row.data_level) }}
                        </template>
                      </el-table-column>
                      <el-table-column label="自定义部门" min-width="180">
                        <template #default="{ row }">
                          <el-tooltip
                            v-if="getFrameNameList(row).length"
                            :content="getFrameNames(row)"
                            placement="top"
                            effect="dark"
                          >
                            <div class="frame-select-preview">
                              <el-tag size="small" type="info" class="frame-select-tag">
                                {{ getFrameNameList(row)[0] }}
                              </el-tag>
                              <el-tag
                                v-if="getFrameNameList(row).length > 1"
                                type="info"
                                class="frame-select-count"
                              >
                                +{{ getFrameNameList(row).length - 1 }}
                              </el-tag>
                            </div>
                          </el-tooltip>
                          <span v-else class="frame-select-empty">--</span>
                        </template>
                      </el-table-column>
                    </el-table>
                  </div>
                </div>
              </el-tab-pane>
              <el-tab-pane label="低代码应用" name="2">
                <div class="readonly-crud-pane">
                  <el-table :data="crudList" class="readonly-crud-table">
                    <el-table-column label="实体名称" min-width="160" prop="table_name" show-overflow-tooltip />
                    <el-table-column label="查看权限" min-width="120">
                      <template #default="{ row }">{{ getCrudDataLevelText(row.reade) }}</template>
                    </el-table-column>
                    <el-table-column label="新增权限" width="100">
                      <template #default="{ row }">{{ Number(row.created) === 1 ? '允许' : '不允许' }}</template>
                    </el-table-column>
                    <el-table-column label="修改权限" min-width="120">
                      <template #default="{ row }">{{ getCrudDataLevelText(row.updated) }}</template>
                    </el-table-column>
                    <el-table-column label="删除权限" min-width="120">
                      <template #default="{ row }">{{ getCrudDataLevelText(row.deleted) }}</template>
                    </el-table-column>
                    <el-table-column label="共享权限" min-width="120">
                      <template #default="{ row }">{{ getCrudDataLevelText(row.share) }}</template>
                    </el-table-column>
                    <el-table-column label="移交权限" min-width="120">
                      <template #default="{ row }">{{ getCrudDataLevelText(row.transfer) }}</template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>
            </el-tabs>
          </div>
        </div>
      </div>
      <el-empty v-else description="暂无角色"></el-empty>

      <!-- 管理人员弹窗 -->
      <el-drawer
        :before-close="handleClose"
        v-model="drawer"
        :close-on-click-modal="false"
        size="700px"
        title="管理人员"
      >
        <template #header>
          <div class="drawer-title">
            <span>管理人员</span>
            <select-member ref="selectMember" :value="tableList || []" @getSelectList="getSelectList">
              <template v-slot:custom>
                <el-button type="primary" @click="openDepartment">选择成员</el-button>
              </template>
            </select-member>
          </div>
        </template>
        <div class="box">
          <el-table :data="tableList" style="width: 100%">
            <el-table-column label="ID" prop="id"> </el-table-column>
            <el-table-column label="姓名" prop="name"> </el-table-column>
            <el-table-column label="部门" prop="frame.name"> </el-table-column>
            <el-table-column label="启用状态" prop="position">
              <template #default="scope">
                <el-switch
                  v-model="scope.row.status"
                  active-text="启用"
                  :active-value="1"
                  inactive-text="禁用"
                  :inactive-value="0"
                  @change="handleChange($event, scope.row)"
                >
                </el-switch>
              </template>
            </el-table-column>
            <el-table-column label="操作" prop="position" width="80px">
              <template #default="scope">
                <el-button type="primary" link @click="handleDeleteUser(scope.row.id, scope.$index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </el-drawer>
    </el-card>

    <addAdminRole ref="adminRole" :edit-type="editType" :role-id="roleId" @adminRole="adminRole" />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import {
  systemRoleStatusApi,
  systemRoleListApi,
  systemRoleUserListApi,
  systemRoleAddUserApi,
  systemRoleDeleteApi,
  systemRoleEditApi,
  systemRoleDeleteUserApi,
  systemRoleShowUserApi
} from '@/api/config.ts'

export default {
  name: 'EnterpriseAdmin',
  components: {
    addAdminRole: defineAsyncComponent(() => import('@/views/setting/enterprise/components/addAdminRole')),
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member'))
  },
  data() {
    return {
      memberShow: false,
      roleId: '',
      roles: '',
      roleKeyword: '',
      roleList: [],
      selectedRole: null,
      roleDetail: null,
      roleDetailLoading: false,
      detailRequestId: 0,
      activeDetailTab: '1',
      activeMenuNode: null,
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled'
      },
      tableList: [],
      total: 0,
      page: 1,
      limit: 9999,
      editType: 0, // 0 新增 1编辑
      drawer: false
    }
  },
  computed: {
    filteredRoleList() {
      const keyword = this.roleKeyword.trim()
      if (!keyword) return this.roleList
      return this.roleList.filter((item) => item.role_name && item.role_name.includes(keyword))
    },
    memberList() {
      return this.tableList
    },
    currentRole() {
      const detailRule = this.roleDetail && this.roleDetail.rule ? this.roleDetail.rule : {}
      return Object.assign({}, this.selectedRole || {}, detailRule)
    },
    checkedMenuKeys() {
      const rules = this.currentRole && this.currentRole.rules
      return Array.isArray(rules) ? rules : []
    },
    checkedApiKeys() {
      const apis = this.currentRole && this.currentRole.apis
      if (Array.isArray(apis)) return apis.map((item) => Number(item))
      if (apis && typeof apis === 'object') return Object.keys(apis).map((item) => Number(item))
      return []
    },
    readonlyTreeData() {
      const treeData = this.roleDetail && Array.isArray(this.roleDetail.tree) ? this.roleDetail.tree : []
      return this.getReadonlyTreeData(treeData)
    },
    defaultExpandedMenuKeys() {
      const firstNode = this.readonlyTreeData[0]
      return firstNode && firstNode.value !== undefined ? [firstNode.value] : []
    },
    activeApiList() {
      return this.activeMenuNode && Array.isArray(this.activeMenuNode.apis) ? this.activeMenuNode.apis : []
    },
    modulePermissionRows() {
      const modulePermission = this.roleDetail && this.roleDetail.module_permission
      if (!modulePermission) return []
      if (Array.isArray(modulePermission)) return modulePermission
      return Object.keys(modulePermission).map((key) => ({
        key,
        ...(modulePermission[key] || {})
      }))
    },
    crudList() {
      return this.roleDetail && Array.isArray(this.roleDetail.crud) ? this.roleDetail.crud : []
    }
  },
  mounted() {
    this.getList()
  },
  methods: {
    async getList() {
      const result = await systemRoleListApi()
      const roleList = Array.isArray(result.data) ? result.data : result.data.list || []
      const activeId = this.selectedRole && this.selectedRole.id
      this.roleList = roleList
      this.total = Array.isArray(result.data) ? roleList.length : result.data.count || roleList.length

      if (this.roleList.length === 0) {
        this.selectedRole = null
        this.roleDetail = null
        this.tableList = []
        return
      }

      const nextRole = this.roleList.find((item) => item.id === activeId) || this.roleList[0]
      this.selectRole(nextRole)
    },
    selectRole(item) {
      if (!item) return
      this.selectedRole = item
      this.roleId = item.id
      this.getRoleDetail(item.id)
      this.getTableList('', item.id)
    },
    handelRole(item) {
      this.selectRole(item)
      this.drawer = true
    },
    handleRoleCommand(command, item) {
      if (command === 'member') {
        this.handelRole(item)
      } else if (command === 'edit') {
        this.handleEdit(item.id)
      } else if (command === 'delete') {
        this.handleDeleteRole(item)
      } else if (command === 'status') {
        item.status = Number(item.status) === 1 ? 0 : 1
        this.setRoleStatus(item)
      }
    },
    handleClose() {
      this.drawer = false
    },
    getDataLevel(data) {
      let str = ''
      if (data == 1) {
        str = '仅本人'
      } else if (data == 2) {
        str = '本部门'
      } else if (data == 3) {
        str = '自定义部门'
      } else if (data == 5) {
        str = '直属下级'
      } else if (data == 4) {
        str = '全部数据'
      }
      return str
    },
    // 添加管理员身份
    addAdminRole() {
      this.editType = 0
      this.$refs.adminRole.openBox()
    },
    adminRole() {
      this.getList()
    },
    async setRoleStatus(item) {
      const status = item.status
      const oldStatus = Number(status) === 1 ? 0 : 1
      try {
        await systemRoleStatusApi(item.id, { status })
        if (this.selectedRole && this.selectedRole.id === item.id) {
          this.selectedRole.status = status
          if (this.roleDetail && this.roleDetail.rule) {
            this.roleDetail.rule.status = status
          }
        }
      } catch (error) {
        item.status = oldStatus
      }
    },
    async getRoleDetail(id) {
      const requestId = ++this.detailRequestId
      this.roleDetailLoading = true
      this.roleDetail = null
      this.activeMenuNode = null
      try {
        const { data } = await systemRoleEditApi(id)
        if (requestId !== this.detailRequestId) return
        this.roleDetail = data || null
        this.activeDetailTab = '1'
        this.$nextTick(() => {
          this.activeMenuNode = this.findFirstApiNode(this.readonlyTreeData)
        })
      } finally {
        if (requestId === this.detailRequestId) {
          this.roleDetailLoading = false
        }
      }
    },
    getTableList(num, id) {
      this.page = num || 1
      const roleId = id || this.roleId
      return systemRoleUserListApi(roleId, {
        page: this.page,
        limit: this.limit
      }).then((res) => {
        this.tableList = res.data && res.data.list ? res.data.list : []
      })
    },
    // 编辑权限
    handleEdit(id) {
      this.roleId = id
      this.editType = 1
      systemRoleEditApi(id).then(({ data }) => {
        const tempObj = {}
        const childData = this.$refs.adminRole
        childData.ruleForm.role_name = data.rule.role_name
        childData.ruleForm.status = data.rule.status
        childData.activeMastart = data.rule.frame
        childData.defaultCheckedKeys = data.rule.rules
        childData.rolesList = data.rule.apis
        childData.activeMastartObj = tempObj
        childData.openBox(data)
      })
    },
    // 删除权限
    async handleDeleteRole(row, index) {
      if (!row) return
      await modalSure('删除管理员身份')
      await systemRoleDeleteApi(row.id)
      await this.getList()
    },
    getSelectList(data) {
      const frameId = []
      if (data.length === 0) {
        return false
      }
      data.forEach((el) => {
        frameId.push(el.value || el.id)
      })

      systemRoleAddUserApi({
        role_id: this.roleId,
        user_id: frameId,
        frame_id: []
      })
        .then((res) => {
          this.getTableList('', this.roleId)
          this.openStatus = false
        })
        .catch((error) => {
          this.openStatus = false
        })
    },
    // 打开选择部门成员
    openDepartment() {
      this.$refs.selectMember.handlePopoverShow()
    },
    // 成员切换状态
    handleChange(value, row) {
      systemRoleShowUserApi({
        uid: row.id,
        status: value,
        role_id: this.roleId
      }).then((res) => {})
    },
    // 删除成员
    async handleDeleteUser(id, index) {
      await modalSure('删除该成员')
      await systemRoleDeleteUserApi({
        uid: id,
        role_id: this.roleId
      })
      await this.getTableList('', this.roleId)
    },
    pageChange(num) {
      this.page = num
      this.getTableList(num)
    },
    handleSizeChange(num) {
      this.limit = num
      this.getTableList()
    },
    getMemberCount(item) {
      return Number(item && item.user_count) || 0
    },
    getStatusText(status) {
      if (status === undefined || status === null) return '--'
      return Number(status) === 1 ? '启用' : '禁用'
    },
    getStatusTagType(status) {
      return Number(status) === 1 ? '' : 'info'
    },
    getReadonlyTreeData(treeData) {
      return treeData.map((item) => ({
        ...item,
        disabled: true,
        children: Array.isArray(item.children) ? this.getReadonlyTreeData(item.children) : []
      }))
    },
    findFirstApiNode(treeData) {
      for (const item of treeData) {
        if (item.apis && item.apis.length) return item
        if (item.children && item.children.length) {
          const child = this.findFirstApiNode(item.children)
          if (child) return child
        }
      }
      return null
    },
    handlePermissionNodeClick(data) {
      this.activeMenuNode = data
    },
    getApiChecked(item) {
      return this.checkedApiKeys.includes(Number(item && item.value))
    },
    getCrudDataLevelText(value) {
      const map = {
        0: '不允许',
        1: '仅本人',
        2: '本部门',
        3: '自定义部门',
        4: '全部数据',
        5: '直属下级'
      }
      return map[Number(value)] || '不允许'
    },
    getModuleDataLevelText(value) {
      const map = {
        1: '仅本人',
        2: '直属下级',
        3: '本部门',
        4: '自定义部门',
        5: '全部数据'
      }
      return map[Number(value)] || '--'
    },
    getFrameNameList(row) {
      if (!row) return []
      if (Array.isArray(row.frame_names) && row.frame_names.length) {
        return row.frame_names.filter(Boolean)
      }
      const frames = row.frames || row.frame || row.frame_list
      if (Array.isArray(frames) && frames.length) {
        return frames.map((item) => item && item.name).filter(Boolean)
      }
      return []
    },
    getFrameNames(row) {
      const names = this.getFrameNameList(row)
      return names.length ? names.join('、') : '--'
    },
    getMemberAvatar(item) {
      const name = (item && item.name) || ''
      return name.slice(0, 1) || '--'
    },
    getMemberDepartment(item) {
      if (!item) return '--'
      if (item.frame && item.frame.name) return item.frame.name
      if (item.frames && item.frames.length > 0) {
        return item.frames.map((frame) => frame.name).join('、')
      }
      return '--'
    }
  }
}
</script>

<style lang="scss" scoped>
.fromx {
  display: flex;
  justify-content: space-between;
}
.role-page-card {
  height: calc(100vh - 77px);
  min-height: 0;
  overflow: hidden;

  :deep(.el-card__body) {
    height: 100%;
    padding: 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
  }
}
.role-page-header {
  height: 56px;
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.page-title {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
  line-height: 24px;
}
.page-desc {
  margin-top: 6px;
  font-size: 13px;
  color: #909399;
}
.role-layout {
  display: grid;
  grid-template-columns: minmax(240px, 0.9fr) minmax(520px, 4fr);
  flex: 1;
  min-height: 0;
  overflow: hidden;
}
.role-sidebar {
  min-width: 0;
  min-height: 0;
  height: 100%;
  // background: #fff;
  border-right: 1px solid #eeeeee;
  display: flex;
  flex-direction: column;
}
.role-sidebar-title {
  height: 46px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 14px;
  font-weight: 500;
  color: #303133;
  // background: #fff;
  border-bottom: 1px solid #eeeeee;
}
.role-search {
  padding: 12px 14px;
  border-bottom: 1px solid #eeeeee;
}
.role-total {
  font-weight: 400;
  font-size: 13px;
  color: #909399;
}
.role-list {
  flex: 1 1 0;
  min-height: 0;
  overflow-y: auto;
  padding: 8px 0;
  scrollbar-width: none;
  -ms-overflow-style: none;

  &::-webkit-scrollbar {
    display: none;
  }
}
.role-list-item {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 30px;
  column-gap: 12px;
  align-items: center;
  min-height: 56px;
  padding: 8px 14px 8px 16px;
  border-left: 3px solid transparent;
  cursor: pointer;
  background: #fff;
  transition: all 0.2s;

  &:hover {
    background: #f5f7fa;

    .role-actions-more {
      opacity: 1;
      pointer-events: auto;
    }
  }

  &.active {
    border-left-color: #1890ff;
    background: #f0f7ff;
  }
}
.role-item-main {
  min-width: 0;
}
.role-name {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 14px;
  color: #303133;
}
.role-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
}
.role-status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #f56c6c;
  flex-shrink: 0;
}
.role-status-dot.is-active {
  background: #52c41a;
}
.role-actions-more {
  min-width: 0;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s;
}
.role-more-button {
  padding: 0;
  width: 30px;
  height: 30px;
  font-size: 16px;
  color: #909399;

  &:hover,
  &:focus {
    color: #1890ff;
  }
}
.role-detail {
  min-width: 420px;
  min-height: 0;
  padding: 0 20px;
  background: #fff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.role-detail-content {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
.readonly-form-row {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  min-height: 54px;
  font-size: 14px;
  color: #303133;
}
.readonly-form-label {
  flex-shrink: 0;
  color: #909399;
}
.readonly-form-value {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.role-detail-tabs {
  min-height: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  border: none;
  box-shadow: none;
}
.readonly-permission-layout {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(230px, 0.9fr) minmax(220px, 0.85fr) minmax(280px, 1.15fr);
  border-top: 1px solid #eeeeee;
}
.readonly-menu-tree,
.readonly-api-list,
.readonly-module-permission {
  min-width: 0;
  min-height: 0;
  box-sizing: border-box;
  overflow-y: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;

  &::-webkit-scrollbar {
    display: none;
  }
}
.readonly-menu-tree {
  padding: 0 18px 18px 0;
  border-right: 1px solid #eeeeee;
}
.readonly-api-list {
  padding: 0 18px 18px;
  border-right: 1px solid #eeeeee;
}
.readonly-module-permission {
  padding: 0 0 18px 18px;
}
.detail-column-title {
  height: 46px;
  line-height: 46px;
  font-size: 13px;
  font-weight: 500;
  color: #303133;
}
.api-checkbox-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-top: 4px;
}
.readonly-checkbox {
  margin-right: 0;
}
.readonly-crud-pane {
  height: 100%;
  min-height: 0;
  box-sizing: border-box;
  overflow: auto;
  padding-bottom: 18px;
}
.readonly-crud-table {
  border-top: 1px solid #eeeeee;
}
.frame-select-preview {
  display: inline-flex;
  align-items: center;
  max-width: 100%;
  vertical-align: middle;
}
.frame-select-tag,
.frame-select-count {
  height: 24px;
  line-height: 22px;
  max-width: 104px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.frame-select-count {
  margin-left: 6px;
  max-width: none;
}
.frame-select-empty {
  color: #c0c4cc;
}
:deep(.role-detail-tabs) {
  .el-tabs__header {
    flex-shrink: 0;
    margin: 0;
    background: #f7fbff;
    border-bottom: none;
  }

  .el-tabs__item {
    height: 40px;
    line-height: 40px;
  }

  .el-tabs__item.is-active::after {
    content: '';
    height: 2px;
    width: 100%;
    background-color: #1890ff;
    position: absolute;
    left: 0;
    top: 0;
  }

  .el-tabs__content {
    flex: 1;
    min-height: 0;
    padding: 0;
  }

  .el-tab-pane {
    height: 100%;
  }
}
:deep(.readonly-tree) {
  .el-tree-node__content {
    min-width: 0;
    height: 38px;
    line-height: 38px;
  }

  .el-tree-node__label {
    font-size: 13px;
  }
}
.box {
  padding: 20px;
  //height: 100%;

  overflow-y: scroll;
}
.drawer-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

@media screen and (max-width: 960px) {
  .role-layout {
    grid-template-columns: 1fr;
  }

  .role-sidebar {
    min-width: 0;
    border-right: none;
    border-bottom: 1px solid #eeeeee;
  }

  .role-detail {
    min-width: 0;
  }

  .role-list {
    height: auto;
    max-height: 320px;
  }

  .readonly-permission-layout {
    grid-template-columns: 1fr;
  }

  .readonly-menu-tree,
  .readonly-api-list,
  .readonly-module-permission {
    max-height: 320px;
    padding: 0 0 16px;
    border-right: none;
    border-bottom: 1px solid #eeeeee;
  }
}
</style>
