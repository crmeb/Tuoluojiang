<template>
  <div class="divBox">
    <!-- 组织架构页面 -->
    <el-card>
      <el-row>
        <el-col v-bind="gridl" class="p20">
          <tree :parent-tree="treeData" :frame-id="frame_id" @frameId="getFrameId" />
        </el-col>
        <el-col v-bind="gridr" class="boder-left p20">
          <div class="table-box ml20">
            <div class="header-16">
              <div class="title-16">
                员工列表
                <el-popover placement="right" trigger="hover" popper-class="monitor-yt-popover">
                  <div class="prompt-bag">
                    <p>
                      1. 可在组织架构中快速邀请成员，为成员设置所在部门；成员的详细信息可在
                      <span @click="goTheJob" style="color: #1890ff; cursor: pointer">员工档案</span>
                      中添加设置
                    </p>
                    <p>
                      2. 选中的部门可为其添加子部门、修改名称、删除部门，公司名称在
                      <span @click="goTheEnterprise" style="color: #1890ff; cursor: pointer">企业信息 </span>中进行修改
                    </p>
                    <p>3. 删除部门需先删除此部门下的子部门及成员</p>
                  </div>
                  <template #reference>
                    <i class="el-icon-question"></i>
                  </template>
                </el-popover>
              </div>
              <el-button type="primary" icon="el-icon-plus" @click="openBox('addDrawerIsShow', 'add')"
                >新增</el-button
              >
            </div>
            <div class="flex lh-center mt16">
              <div class="total-16">共 {{ total }} 条</div>
              <el-input
                prefix-icon="el-icon-search"
                clearable
                placeholder="请输入姓名、电话号码"
                v-model="formData.name"
                @change="handleSearch"
                style="width: 250px"
                @keyup.enter="handleSearch"
              >
              </el-input>
            </div>
            <div class="table-container" v-loading="loading">
              <el-table
                ref="multipleTable"
                :data="tableData"
                tooltip-effect="dark"
                style="width: 100%"
                :height="TABLE_HEIGHT"
                @selection-change="handleSelectionChange"
              >
                <el-table-column prop="name" label="姓名" min-width="100">
                  <template #default="scope">
                    <div class="flex lh-center">
                      <img :src="scope.row.avatar" alt="" class="img" />
                      <div>
                        {{ scope.row.name }}
                      </div>
                      <img
                        v-if="scope.row.work_member_id != 0"
                        src="../../../../assets/images/bindWeChat.png"
                        alt=""
                        class="bindWeChat"
                      />
                      <img v-else src="../../../../assets/images/unbindWeChat.png" alt="" class="bindWeChat" />
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="frame.name" label="部门" min-width="140">
                  <template #default="scope">
                    <div class="frame-name over-text" v-for="(item, index) in scope.row.frames" :key="index">
                      <span class="icon-h">
                        {{ item.name
                        }}<span v-show="item.is_mastart === 1 && scope.row.frames.length > 1" title="主部门">(主)</span>
                        <span v-show="item.is_admin == 1" title="主管" class="guan">(管)</span>
                      </span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="job.name" label="职位" min-width="110">
                  <template #default="scope">
                    <div class="over-text">{{ scope.row.job && scope.row.job.name ? scope.row.job.name : '--' }}</div>
                  </template>
                </el-table-column>
                <el-table-column prop="phone" label="手机号码" min-width="110" />
                <el-table-column prop="join_time" label="加入时间" min-width="100" />
                <el-table-column label="操作" width="120">
                  <template #default="scope">
                    <el-button type="primary" link @click="onEdit(scope.row.id)" v-hasPermi="['hr:enterprise:group:edit']">
                      编辑
                    </el-button>

                    <el-button type="primary" link @click="onDelete(scope.row.id, scope.$index)" v-if="!scope.row.uid">
                      删除
                    </el-button>
                  </template>
                </el-table-column>
              </el-table>

              <div class="page-fixed">
                <el-pagination
                  :page-size="formData.limit"
                  :current-page="formData.page"
                  :page-sizes="[15, 20, 30]"
                  layout="total,sizes, prev, pager, next, jumper"
                  :total="total"
                  @current-change="pageChange"
                  @size-change="handleSizeChange"
                />
              </div>
            </div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!--  添加人员弹窗/批量导入  -->
    <updataXls ref="personBox" :person-config="personConfig" :frame-id="frame_id" />
    <!--  被邀请人列表弹窗  -->
    <inviteesTable ref="inviteesTableBox" @handleInvitees="handleInvitees" @getQuantity="getQuantity" />
    <!--  编辑用户  -->
    <editUser ref="editUser" :user-id="userId" @closed="userId = ''" @saved="handleTree" />
    <!-- 通用弹窗表单   -->
    <dialogForm ref="dialogForm" :roles-config="rolesConfig" :form-data="formBoxConfig" @isOk="isOk" />
    <!-- 人员新增/修改  -->
    <UserDetails :tabtypes="1" :actionType="``" @getList="handleTree" ref="userDetails" />
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { useOrganizationStore } from '@/stores/organization.ts'
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { EventBus } from '@/libs/bus.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { configFrameApi } from '@/api/setting.ts'
import { userListApi } from '@/api/user.ts'
import { roterPre } from '@/settings.ts'
import { enterpriseCardDeleteApi, getQuantity } from '@/api/enterprise.ts'
export default {
  name: 'Index',
  components: {
    tree: defineAsyncComponent(() => import('./components/tree')),
    dialogForm: defineAsyncComponent(() => import('./components/index')),
    editUser: defineAsyncComponent(() => import('./components/editUser')),
    updataXls: defineAsyncComponent(() => import('./components/updataXls')),
    inviteesTable: defineAsyncComponent(() => import('./components/inviteesTable')),
    // 档案信息弹窗
    UserDetails: defineAsyncComponent(() => import('@/views/hr/archives/components/userDetails.vue'))
  },
  computed: {
    ...mapStores(useUserStore, useOrganizationStore)
  },
  data() {
    return {
      TABLE_HEIGHT,
      treeData: [],
      loading: false,
      tableData: [],
      frame_id: 0,
      num: 0,
      personConfig: {},
      total: 0,
      formData: {
        page: 1,
        limit: 15,
        name: ''
      },

      gridl: {
        xl: 5,
        lg: 5,
        md: 8,
        sm: 8,
        xs: 8
      },
      gridr: {
        xl: 19,
        lg: 19,
        md: 16,
        sm: 16,
        xs: 16
      },
      rolesConfig: [],
      formBoxConfig: {
        title: '邀请成员',
        width: '500px',
        method: 'post',
        action: '/user/create'
      },
      userId: '', // 用户id
      indexType: null
    }
  },
  watch: {
    filterText(val) {
      this.$refs.tree.filter(val)
    }
  },
  beforeUnmount() {
    EventBus.$off('getList', this.handleBusGetList)
  },
  created() {
    this.getList()
  },
  mounted() {
    EventBus.$on('getList', this.handleBusGetList)
  },
  methods: {
    handleBusGetList() {
      this.getList()
      this.organizationStore.getDepartment({ force: true })
    },
    // 获取选中id
    getFrameId(data) {
      this.frame_id = data
      this.formData.page = 1
      this.getTableList()
    },
    openBox(componentName, type) {
      this.$refs.userDetails.init({ componentName, type })
    },
    goTheJob() {
      this.$router.push({
        path: `${roterPre}/hr/archives/onTheJob`
      })
    },
    goTheEnterprise() {
      this.$router.push({
        path: `${roterPre}/setting/enterprise/info/basic`
      })
    },
    // 获取待审核数量
    async getQuantity() {
      let type = 'inviter_review'
      const result = await getQuantity(type)
      this.num = result.data.num
    },

    // 获取权限列表
    async getList() {
      const result = await configFrameApi()
      this.treeData = result.data
      await this.getTableList()
    },
    // 操作关闭
    handleClose() {
      this.isOpen = false
    },
    roleChange() {},
    isOk(data) {
      if (data) {
        this.rolesConfig[0].control[0].rule[1].value = data.url
        const oInput = document.createElement('input')
        const value =
          '【' +
          data.name +
          '】邀请你加入【' +
          this.userStore.enterprise.enterprise_name +
          '】办公系统，请点击链接使用手机号验证登录 ' +
          data.url +
          ' 链接有效期为7天，请尽快登录加入团队！'
        oInput.value = value
        document.body.appendChild(oInput)
        oInput.select()
        document.execCommand('Copy')
        oInput.style.display = 'none'
        document.body.removeChild(oInput)
      }
      this.$refs.dialogForm.handleClose()
    },
    // 添加人员
    // addPerson(type) {
    //   switch (type) {
    //     case 1:
    //       this.personConfig.title = '添加人员'
    //       break
    //     case 2:
    //       this.personConfig.title = '批量导入人员'
    //       break
    //   }
    //   this.personConfig.type = type
    //   this.$refs.personBox.open()
    // },
    // 被邀请人列表
    inviteesList() {
      this.$refs.inviteesTableBox.open()
    },
    // table 选中
    handleSelectionChange(val) {
      this.multipleSelection = val
    },
    // 企业成员
    getTableList() {
      this.loading = true
      userListApi({
        pid: this.frame_id,
        page: this.formData.page,
        limit: this.formData.limit,
        name: this.formData.name
      }).then((res) => {
        this.tableData = res.data.list
        this.loading = false
        if (this.tableData && this.tableData.length > 0) {
          this.tableData.forEach((value) => {
            if (value.frames.length > 1) {
              value.frames.sort((a, b) => {
                return b.is_mastart - a.is_mastart
              }) //降序
            }
          })
        }
        this.total = res.data.count
      })
    },
    handleInvitees() {
      this.handleSearch()
    },
    handleSearch() {
      this.formData.page = 1
      this.getTableList()
    },
    // 分页
    pageChange(page) {
      this.formData.page = page
      this.getTableList()
    },
    handleSizeChange(size) {
      this.formData.limit = size
      this.getTableList()
    },
    // 编辑用户
    onEdit(id) {
      this.userId = id
      this.$refs.editUser.open()
    },
    // 设置所在部门
    openDepartment(type) {
      this.indexType = type
      if (type === 1) {
        this.isSite = true
        this.onlyDepartment = false
        const selection = this.$refs.multipleTable.selection
        if (!selection.length) return this.$message.error('请选择成员')
      } else {
        this.isSite = false
        this.onlyDepartment = true
      }
      this.$refs.department.reseatData()
      this.openStatus = true
    },

    handleTree() {
      setTimeout(() => {
        this.getList()
      }, 1000)
    },
    //  删除人员
    onDelete(id, index) {
      modalSure('确定删除该人员吗').then(() => {
        enterpriseCardDeleteApi(id).then((res) => {
          this.tableData.splice(index, 1)
        })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.divBox {
  height: 100%;
}
.mt16 {
  margin-top: 7px;
}
.img {
  display: block;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 4px;
}
.bindWeChat {
  display: block;
  width: 16px;
  height: 16px;
  margin-left: 6px;
}

.prompt-bag {
  background-color: #edf5ff;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 12px;
  color: #606266;
}
:deep(.el-button) {
  font-size: 13px;
}

.el-icon-question {
  cursor: pointer;
  color: #1890ff;
  font-size: 15px;
}
:deep(.el-card__body) {
  padding: 0 20px;
}
.iconzhuyaobumen {
  color: #ff9900;
}
.boder-left {
  min-height: calc(100vh - 77px);
  border-left: 1px solid #eeeeee;
}
.p20 {
  padding: 20px 0;
}

.table-container {
  margin-top: 10px;
}

.frame-name {
  .iconfont {
    padding-right: 6px;
  }
}
.icon-h {
  position: relative;
  & > span {
    color: #1890ff;
  }
  .guan {
    color: #ff9900;
  }
}
.icon {
  position: absolute;
  top: 0;
  right: -15px;
  display: inline-block;
  width: 15px;
  height: 15px;
  font-size: 10px;
  font-weight: 500;
  text-align: center;
  line-height: 15px;
  color: #fff;
  border-radius: 50%;
  background-color: #ff9900;
}
:deep(.select-bar) {
  line-height: 32px;
}
</style>
<style>
.monitor-yt-popover {
  background: #edf5ff;
  border: 1px solid #97c3ff;
  padding: 13px 15px 6px 15px;
}
</style>
