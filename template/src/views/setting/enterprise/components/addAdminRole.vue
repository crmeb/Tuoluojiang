<template>
  <div>
    <el-drawer
      :title="editType === 0 ? '新增角色' : '编辑角色'"
      v-model="drawer"
      :direction="direction"
      :modal="true"
      :destroy-on-close="false"
      :close-on-click-modal="false"
      :size="`85%`"
      :before-close="handleClose"
    >
      <div v-if="drawer" class="container v-height-flag" style="padding-bottom: 56px;">
        <div class="form-box">
          <el-form :model="ruleForm" label-width="auto">
            <el-form-item label="角色名称：" prop="name" style="margin-left: 20px">
              <el-input v-model="ruleForm.role_name" placeholder="请输入管理员名称" style="width: 460px" />
            </el-form-item>
            <el-tabs v-model="tabIndex" type="border-card">
              <el-tab-pane label="权限数据" :name="`1`"></el-tab-pane>
              <el-tab-pane label="低代码应用" :name="`2`"></el-tab-pane>
            </el-tabs>
            <template v-if="tabIndex == 1"> </template>
          </el-form>
        </div>

        <div class="roles-box" v-show="tabIndex == 1">
          <rolesTree
            ref="rolesTree"
            :tree-data="treeData"
            :default-checked-keys="defaultCheckedKeys"
            :roles-list="rolesList"
            :table-data="modulePermissionList"
          />
        </div>

        <div v-show="tabIndex == 2" class="table-box ml20">
          <div class="mb14 flex mt10">
            <!-- <span class="fz13">实体数据权限</span> -->
            <el-input
              style="width: 250px"
              placeholder="请输入实体名称"
              prefix-icon="el-icon-search"
              class="mr10"
              v-model="searchKey"
            >
            </el-input>

            <el-select
              v-model="cate_id"
              placeholder="请选择关联应用(多选)"
              style="width: 250px"
              filterable
              multiple
              collapse-tags
            >
              <el-option v-for="item in application" :key="item.id" :label="item.name" :value="item.id"> </el-option>
            </el-select>
            <div style="margin-left: auto; width: 100px">
              <settingsPopover @handClick="rowFn($event, 'reade', 2)">
                <el-button type="primary">批量设置</el-button>
              </settingsPopover>
            </div>
          </div>

          <el-table
            v-loading="loading"
            :key="itemKey"
            :data="searchList"
            :height="height"
            style="width: 100%"
            @selection-change="handleSelectionChange"
          >
            <el-table-column type="selection" width="55"></el-table-column>
            <el-table-column prop="table_name" label="实体名称" min-width="120"> </el-table-column>
            <el-table-column width="300">
              <template #header="scope">
                <div class="flex">
                  查看权限 <settingsPopover @handClick="rowFn($event, 'reade', 1)" :icon="true"></settingsPopover>
                </div>
              </template>
              <template #default="scope">
                <div class="flex">
                  <el-select v-model="scope.row.reade" style="width: 100%" placeholder="请选择">
                    <el-option v-for="item in crudList" :key="item.value" :label="item.label" :value="item.value">
                    </el-option>
                  </el-select>
                  <select-department
                    v-if="scope.row.reade == 3"
                    :value="scope.row.reade_frames || []"
                    :placeholder="`请选择`"
                    :is-search="true"
                    @changeMastart="changeMastart($event, 'reade_frame', scope.row, scope.$index)"
                    style="width: 100%"
                  ></select-department>
                </div>
              </template>
            </el-table-column>
            <el-table-column width="110">
              <template #header="scope">
                <div class="flex">
                  新增权限
                  <settingsPopover
                    @handClick="rowFn($event, 'created', 1)"
                    :list="allowOptions"
                    :icon="true"
                  ></settingsPopover>
                </div>
              </template>
              <template #default="scope">
                <el-select v-model="scope.row.created" placeholder="请选择">
                  <el-option v-for="item in allowOptions" :key="item.value" :label="item.label" :value="item.value">
                  </el-option>
                </el-select>
              </template>
            </el-table-column>
            <el-table-column prop="date" width="280">
              <template #header="scope">
                <div class="flex">
                  修改权限 <settingsPopover @handClick="rowFn($event, 'updated', 1)" :icon="true"></settingsPopover>
                </div>
              </template>
              <template #default="scope">
                <div class="flex">
                  <el-select v-model="scope.row.updated" style="width: 100%" placeholder="请选择">
                    <el-option v-for="item in crudList" :key="item.value" :label="item.label" :value="item.value">
                    </el-option>
                  </el-select>
                  <select-department
                    v-if="scope.row.updated == 3"
                    :value="scope.row.updated_frames || []"
                    :placeholder="`请选择`"
                    :is-search="true"
                    @changeMastart="changeMastart($event, 'updated_frame', scope.row, scope.$index)"
                    style="width: 100%"
                  ></select-department>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="date" min-width="280">
              <template #header="scope">
                <div class="flex">
                  删除权限 <settingsPopover @handClick="rowFn($event, 'deleted', 1)" :icon="true"></settingsPopover>
                </div>
              </template>
              <template #default="scope">
                <div class="flex">
                  <el-select style="width: 100%" v-model="scope.row.deleted" placeholder="请选择">
                    <el-option v-for="item in crudList" :key="item.value" :label="item.label" :value="item.value">
                    </el-option>
                  </el-select>
                  <select-department
                    v-if="scope.row.deleted == 3"
                    :value="scope.row.deleted_frames || []"
                    :placeholder="`请选择`"
                    :is-search="true"
                    @changeMastart="changeMastart($event, 'transfer_frame', scope.row, scope.$index)"
                    style="width: 100%"
                  ></select-department>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="date" min-width="280">
              <template #header="scope">
                <div class="flex">
                  共享权限 <settingsPopover @handClick="rowFn($event, 'share', 1)" :icon="true"></settingsPopover>
                </div>
              </template>
              <template #default="scope">
                <div class="flex">
                  <el-select style="width: 100%" v-model="scope.row.share" placeholder="请选择">
                    <el-option v-for="item in crudList" :key="item.value" :label="item.label" :value="item.value">
                    </el-option>
                  </el-select>
                  <select-department
                    v-if="scope.row.share == 3"
                    :value="scope.row.share_frames || []"
                    :placeholder="`请选择`"
                    :is-search="true"
                    @changeMastart="changeMastart($event, 'share_frame', scope.row, scope.$index)"
                    style="width: 100%"
                  ></select-department>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="date" min-width="280">
              <template #header="scope">
                <div class="flex">
                  移交权限 <settingsPopover @handClick="rowFn($event, 'transfer', 1)" :icon="true"></settingsPopover>
                </div>
              </template>
              <template #default="scope">
                <div class="flex">
                  <el-select style="width: 100%" v-model="scope.row.transfer" placeholder="请选择">
                    <el-option v-for="item in crudList" :key="item.value" :label="item.label" :value="item.value">
                    </el-option>
                  </el-select>
                  <select-department
                    v-if="scope.row.transfer == 3"
                    :value="scope.row.transfer_frames || []"
                    :placeholder="`请选择`"
                    :is-search="true"
                    @changeMastart="changeMastart($event, 'transfer_frame', scope.row, scope.$index)"
                    style="width: 100%"
                  ></select-department>
                </div>
              </template>
            </el-table-column>
            <el-table-column fixed="right" width="100">
              <template #default="scope">
                <settingsPopover @handClick="rowFn($event, scope.row)"></settingsPopover>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
      <div class="from-foot-btn fix btn-shadow">
        <el-button @click="close">取消</el-button>
        <el-button type="primary" :loading="loading" @click="onSubmit">保存</el-button>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { systemRoleCreateApi, systemRoleStoreApi, systemRoleUpdateApi } from '@/api/config.ts'
import settingsPopover from './settingsPopover'
import { getcrudCateListApi } from '@/api/develop.ts'
export default {
  emits: ['adminRole'],
  name: 'AddAdminRole',
  components: {
    rolesTree: defineAsyncComponent(() => import('@/components/setting/rolesTree')),
    settingsPopover: defineAsyncComponent(() => import('./settingsPopover')),
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department'))
  },
  props: {
    editType: {
      type: [Boolean, Number],
      default: 0
    },
    roleId: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      tabIndex: '1',
      input2: '',
      loading: false,
      height: `calc(100vh - 284px)`,
      application: [], // 应用数据
      modulePermissionList: [],
      entityOptions: [],
      departmentShow: false,
      drawer: false,
      direction: 'rtl',
      ruleForm: {
        role_name: '',

        status: 1
      },
      searchKey: '',
      cate_id: '',
      itemKey: null,

      crudList: [
        {
          value: 0,
          label: '不允许'
        },
        {
          value: 1,
          label: '仅本人'
        },
        {
          value: 5,
          label: '直属下级'
        },
        {
          value: 2,
          label: '本部门'
        },
        {
          value: 3,
          label: '自定义部门'
        },
        {
          value: 4,
          label: '全部数据'
        }
      ],
      allowOptions: [
        {
          label: '允许',
          value: 1
        },
        {
          label: '不允许',
          value: 0
        }
      ],
      ids: [],
      treeData: [],
      defaultCheckedKeys: [],
      rolesList: [],
      activeIndex: '',
      activeType: '',
      activeMastart: null,
      activeMastartObj: null
    }
  },

  computed: {
    searchList: function () {
      let list = []
      if (this.searchKey !== '' && this.cate_id.length == 0) {
        this.tableData.map((item) => {
          if (item.table_name.includes(this.searchKey)) {
            list.push(item)
          }
        })
      }
      if (this.cate_id.length > 0 && this.searchKey == '') {
        this.tableData.map((item) => {
          this.cate_id.map((el) => {
            if (item.cate_ids[0] == el) {
              list.push(item)
            }
          })
        })
      }
      if (this.cate_id.length > 0 && this.searchKey !== '') {
        this.tableData.map((item) => {
          this.cate_id.map((el) => {
            if (item.table_name.includes(this.searchKey) && item.cate_ids[0] == el) {
              list.push(item)
            }
          })
        })
      }
      if (this.searchKey == '' && this.cate_id.length == 0) {
        list = this.tableData
      }
      return list
    }
  },
  mounted() {
    this.getCrudAllType()
  },

  methods: {
    openBox(val) {
      this.modulePermissionList = []
      this.drawer = true
      if (!val) {
        this.getTreeData()
      } else {
        this.tableData = val.crud
        for (let key in val.module_permission) {
          this.modulePermissionList.push({
            key: key,
            ...val.module_permission[key]
          })
        }

        this.tableData.forEach((value, index) => {
          value.crud_id = value.id
        })
        this.treeData = val.tree
      }
    },
    handleSelectionChange(val) {
      this.ids = val
    },

    // 批量设置
    rowFn(item, row, type) {
      if (!type) {
        // 设置当前行实体的所有权限
        row.reade = item.value
        if (item.value != 0) {
          row.created = 1
        } else {
          row.created = item.value
        }
        row.updated = item.value
        row.deleted = item.value
        row.transfer = item.value
        row.share = item.value
      } else if (type === 1) {
        // 按列设置所有选中实体的某种类型权限

        if (this.ids.length == 0) return this.$message.error('请选择要设置权限的实体')
        this.searchList.forEach((val) => {
          this.ids.forEach((el) => {
            if (val.id === el.id) {
              val[row] = item.value
            }
          })
        })
      } else if (type === 2) {
        // 批量设置所有被选择实体的所有权限
        if (this.ids.length == 0) return this.$message.error('请选择要设置权限的实体')

        const idSet = new Set(this.ids.map((item) => item.id))

        const nextPerission = {
          reade: item.value,
          created: item.value != 0 ? 1 : item.value,
          updated: item.value,
          deleted: item.value,
          transfer: item.value,
          share: item.value
        }

        this.searchList.forEach((val) => {
          if (idSet.has(val.id)) {
            Object.assign(val, nextPerission)
          }
        })
      }
    },
    // 获取应用分类
    async getCrudAllType() {
      const data = await getcrudCateListApi()
      this.application = data.data.list
    },
    async getTreeData() {
      const result = await systemRoleCreateApi()
      this.treeData = result.data.tree
      this.tableData = result.data.crud
      this.tableData.forEach((value, index) => {
        value.crud_id = value.id
      })
    },
    async onSubmit() {
      this.loading = true
      const obj = await this.$refs.rolesTree.getNodeValue()
      this.ruleForm.rules = obj.rules
      this.ruleForm.apis = obj.apis
      this.ruleForm.module_permission = obj.module_permission
      this.ruleForm.crud = this.tableData
      const tempArr = []
      for (const i in this.activeMastart) {
        tempArr.push(this.activeMastart[i].id)
      }
      this.ruleForm.frame_id = tempArr
      if (!this.editType) {
        // 新增
        systemRoleStoreApi(this.ruleForm).then((res) => {
          if (res.status == 200) {
            this.loading = false
            this.$emit('adminRole')
            this.close()
          } else {
            this.loading = false
          }
        })
      } else {
        systemRoleUpdateApi(this.roleId, this.ruleForm).then((res) => {
          if (res.status == 200) {
            this.loading = false
            this.$emit('adminRole')
            this.close()
          } else {
            this.loading = false
          }
        })
      }
    },
    handleClose(done) {
      this.close()
      done()
    },
    close() {
      this.tabIndex = '1'
      this.drawer = false
      this.reseatData()
    },
    changeMastart(data, type, row, index) {
      let arr = []
      if (type === '') {
        this.activeMastart = data
      } else {
        data.map((item) => {
          arr.push(item.id)
        })

        if (type === 'deleted_frame') {
          row.deleted_frames = data
          row[type] = arr
        } else if (type === 'reade_frame') {
          row.reade_frames = data
          row[type] = arr
        } else if (type === 'updated_frame') {
          row.updated_frames = data
          row[type] = arr
        } else if (type === 'transfer_frame') {
          row.transfer_frames = data
          row[type] = arr
        } else if (type === 'share_frame') {
          row.share_frames = data
          row[type] = arr
        }

        // this.itemKey = Math.random()
      }
    },
    dataLevelFn(e) {
      this.$forceUpdate()
      this.ruleForm.data_level = e
      if (this.ruleForm.data_level == '4') {
        this.ruleForm.directly = 1
      }
    },

    // 重置数据
    reseatData() {
      if (this.$refs.department) {
        this.$refs.department.reseatData()
      }
      this.activeMastart = null
      this.activeMastartObj = null
      this.ruleForm = {
        role_name: '',
        frame_id: [],
        status: 1
      }
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-drawer__header) {
  padding-bottom: 20px;
  margin-bottom: 0;
  color: #1e2128;
  font-weight: 500;
}

:deep(.label-txt) {
  /*width: 90px;*/
  margin-left: 20px;
  text-align: right;
}
:deep(.el-tabs__item.is-active) {
  border-right-color: transparent !important;
  border-left-color: transparent !important;
  &::after {
    content: '';
    height: 2px;
    width: 100%;
    background-color: #1890ff;
    position: absolute;
    left: 0;
    top: 0;
  }
}
:deep(.el-tabs__item) {
  line-height: 40px !important;
}
:deep(.el-tabs__header) {
  background-color: #f7fbff;
  border-bottom: none;
}
:deep(.el-tabs__nav-wrap::after) {
  height: 0;
}
:deep(.el-tabs__active-bar) {
  top: 0;
}
.el-tabs--border-card {
  height: 39px;

  width: 100%;

  background-color: transparent;
  border: none;
  box-shadow: none;
}
:deep(.el-drawer__body) {
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.container {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
  // padding: 20px;
  // padding-top: 0;
}
.form-box {
  flex-shrink: 0;
}
:deep(.plan-footer-one) {
  cursor: pointer;

  padding: 0 5px !important;
}
.table-box {
  flex: 1;
  min-height: 0;
  overflow: auto;
}
.roles-box {
  // padding-left: 20px;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}
.form-box .el-form .el-form-item:first-child {
  margin: 20px 0;
}
</style>

<style>
.time-popover {
  padding: 0;
}
</style>
