<template>
  <div class="divBox">
    <el-card class="normal-page" :body-style="{ padding: '0px 20px 20px 20px' }">
      <formBox
        :total="total"
        :info="info"
        :type="`module`"
        :keyName="keyName"
        :id="info.crudInfo.id"
        @getInfo="getInfo"
        @getList="getList"
        @confirmData="confirmData"
        @addData="addData"
        @handleDelete="handleDelete"
        @handleDropdown="handleDropdown"
      ></formBox>

      <!-- 表格数据 -->
      <div class="table-box mt10 non-resize-el-table" v-if="info.showField && info.showField.length > 0">
        <el-table
          v-if="!where.group"
          ref="table"
          :data="tableData"
          v-loading="loading"
          style="width: 100%"
          :height="TABLE_HEIGHT"
          @selection-change="handleSelectionChange"
          row-key="id"
          border
        >
          <el-table-column type="selection" min-width="55" show-overflow-tooltip> </el-table-column>
          <el-table-column
            v-for="(item, index) in info.showField"
            :prop="item.field_name_en"
            :label="item.field_name"
            :key="index"
            min-width="200"
          >
            <template #default="scope">
              <tableColumn :item="item" :scope="scope" :info="info" @checkRow="checkRow"></tableColumn>
            </template>
          </el-table-column>

          <el-table-column prop="address" label="操作" fixed="right" width="130">
            <template #default="scope">
              <el-button class="mr10" type="text" @click="checkRow(scope.row)">查看</el-button>
              <el-dropdown>
                <span class="el-dropdown-link el-button--text el-button"> 更多 <i class="el-icon-arrow-down" /></span>
                <el-dropdown-menu>
                  <el-dropdown-item @click.native="openShare(scope.row)"> 共享协作 </el-dropdown-item>
                  <el-dropdown-item v-if="scope.row.is_share" @click.native="cancelShare(scope.row)">
                    取消协作
                  </el-dropdown-item>
                  <el-dropdown-item @click.native="handleDropdown('transfer', scope.row)">
                    移交负责人
                  </el-dropdown-item>
                  <el-dropdown-item @click.native="deleteRow(scope.row)">删除</el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </template>
          </el-table-column>
        </el-table>
        <!-- 分组查看表格 -->
        <treeTable
          v-else
          :treeData="tableData"
          :group="where.group"
          :headerFields="info.showField"
          :info="info"
          @selectionChange="handleSelectionChange"
          @checkRow="checkRow"
          @openShare="openShare"
          @cancelShare="cancelShare"
          @handleDropdown="handleDropdown"
          @deleteRow="deleteRow"
          @handleLoadMore="handleLoadMore"
        ></treeTable>
        <div class="page-fixed" :class="where.group ? 'group-page' : ''">
          <el-pagination
            :page-size="where.limit"
            :current-page="where.page"
            :page-sizes="[15, 20, 30]"
            layout="total,sizes, prev, pager, next, jumper"
            :total="total"
            @size-change="handleSizeChange"
            @current-change="pageChange"
          />
        </div>
      </div>
      <div v-else>
        <default-page :index="18" />
      </div>
    </el-card>

    <!-- 表格多选批量操作 -->
    <BatchActionBar
      :listCount="tableData.length"
      :selectCount="multipleSelection.length"
      @change="handleBottomBatchSelectChange"
      @delete="handleDelete"
      @share="handleBatchShareData"
      @transfer="handleBatchTransferData"
    />

    <!-- 拖拽导入 -->
    <dragUpload ref="dragUpload" @getList="getList"></dragUpload>

    <!-- 新增 -->
    <add-drawer v-if="addDrawerShow" ref="addDrawer" :keyName="keyName" @getList="getList"></add-drawer>
    <!-- 查看 -->
    <check-drawer
      v-if="checkDrawerShow"
      ref="checkDrawer"
      :keyName="keyName"
      :info="info"
      @getList="getList"
      @getInfo="getInfo"
    ></check-drawer>
    <!-- 移交 -->
    <oa-dialog
      ref="oaDialog"
      :formConfig="formConfig"
      :formDataInit="formDataInit"
      :formRules="formRules"
      :fromData="fromData"
      @submit="submit"
    ></oa-dialog>
    <!-- 数据共享列表 -->
    <share ref="share"></share>
    <!-- 邀请填写弹窗 -->
    <fillInDialog ref="fillInDialog"></fillInDialog>
    <!-- 邀请记录 -->
    <fillIn ref="fillIn"></fillIn>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import { TABLE_HEIGHT } from '@/constants/tableSize'
import defaultPage from '@/components/common/defaultPage'
import Commnt from '@/components/develop/commonData'
import formBox from './components/formBox'
import dragUpload from './components/dragUpload'
import checkDrawer from './components/checkDrawer'
import fillInDialog from './components/fillInDialog'
import addDrawer from './components/addDrawer'
import fillIn from './components/fillIn'
import tableColumn from './components/tableColumn'
import share from '@/views/develop/module/components/share'
import oaDialog from '@/components/form-common/dialog-form'

import treeTable from './treeTable.vue'
import {
  crudModuleListApi,
  crudModuleInfoApi,
  crudModuleDelApi,
  crudModuleFindApi,
  crudModuleBatchDelApi,
  moduleTransferApi,
  moduleShareApi,
  delCancelShareApi
} from '@/api/develop'
import BatchActionBar from './components/batchActionBar.vue'
import batchActionHandler from './mixins/batchActionHandler'

export default {
  components: {
    formBox,
    addDrawer,
    checkDrawer,
    dragUpload,
    oaDialog,
    share,
    fillInDialog,
    fillIn,
    BatchActionBar,
    defaultPage,
    treeTable,
    tableColumn
  },
  mixins: [batchActionHandler],
  data() {
    return {
      TABLE_HEIGHT,
      loading: false,
      addDrawerShow: false,
      checkDrawerShow: false,
      dropdownType: '',
      formConfig: [],
      formDataInit: {
        user_id: ''
      },
      formRules: {
        user_id: [{ required: true, message: '请选择人员', trigger: 'blur' }],
        user_ids: [{ required: true, message: '请选择人员', trigger: 'blur' }],
        role_type: [{ required: true, message: '请选择共享权限', trigger: 'blur' }]
      },
      fromData: {
        width: '600px',
        title: '选择接手人',
        btnText: '确定',
        labelWidth: '100px',
        type: ''
      },

      keyName: '',
      info: {
        crudInfo: {
          id: 0
        }
      },
      total: 0,
      srcList: [],
      tableData: [],
      rowData: {},
      where: {
        page: 1,
        limit: 15
      },
      childrenWhere: {
        children_limit: 4,
        children_page: 1,
        children_id: ''
      },
      multipleSelection: [],
      searchTypeOptions: Commnt.searchTypeOptions
    }
  },
  watch: {},

  created() {
    const routeString = this.$route.path
    const routeArray = routeString.split('/').filter((item) => item !== '')
    this.keyName = routeArray[3]
    this.getInfo()
  },
  destroyed() {
    this.$store.commit('updateConditionDialog', false)
  },

  methods: {
    // 加载更多子级数据
    handleLoadMore(group, val) {
      // if (!val) return;
      // 提取分组 id
      const id = group.id.split('_')[1]
      // 如果切换了分组，重置子页码
      if (id != this.childrenWhere.children_id) {
        this.childrenWhere.children_page = 1
      }
      this.childrenWhere.children_id = id
      this.childrenWhere.children_page += 1

      this.loading = true
      crudModuleListApi(this.keyName, { ...this.where, ...this.childrenWhere })
        .then((res) => {
          // 追加新数据到对应分组
          const target = this.tableData.find((item) => item.id == id)
          if (target) {
            target.children = [...(target.children || []), ...(res.data || [])]
          }
          this.loading = false
        })
        .catch(() => {
          this.$message.error('加载更多失败')
          this.loading = false
        })
    },
    getList() {
      this.loading = true
      crudModuleListApi(this.keyName, { ...this.where, ...this.childrenWhere })
        .then((res) => {
          this.total = res.data.count
          this.tableData = res.data.list

          setTimeout(() => {
            this.$refs.table?.doLayout()
          }, 300)
          this.loading = false
        })
        .catch((err) => {
          this.loading = false
        })
    },
    doLayout() {
      let that = this
    },
    openShare(row) {
      this.$refs.share.openBox(this.keyName, row)
    },
    cancelShare(val) {
      modalSure('您确定要取消此数据的协作权限吗').then(() => {
        delCancelShareApi(this.keyName, val.id).then((res) => {
          this.getList()
        })
      })
    },
    handleDropdown(type, row) {
      this.dropdownType = type
      if (row) {
        this.rowData = row
      } else {
        this.rowData = {}
      }

      switch (type) {
        case 'transfer':
          // 移交
          if (this.multipleSelection.length == 0 && !row) return this.$message.error('请选择至少一项内容')
          this.fromData.title = '选择接手人'
          this.formConfig = [
            {
              type: 'user_id',
              label: '选择人员：',
              placeholder: '请选择人员（单选）',
              key: 'user_id',
              only_one: true,
              tips: '数据移交之后，以后由接手人负责；数据移交用于业务调整或者人员离职。'
            }
          ]
          this.$refs.oaDialog.openBox()
          break
        case 'share':
          // 共享
          if (this.multipleSelection.length == 0 && !row) return this.$message.error('请选择至少一项内容')
          this.fromData.title = '数据共享协作'
          this.formConfig = [
            {
              type: 'user_id',
              label: '选择人员：',
              placeholder: '请选择人员（多选）',
              key: 'user_ids',
              only_one: false
            },
            {
              type: 'select',
              label: '共享权限：',
              placeholder: '请选择共享权限',
              key: 'role_type',
              options: [
                { label: '仅可查看', value: '0' },
                { label: '可查看、编辑', value: '1' },
                { label: '可查看、编辑、删除', value: '2' }
              ]
            }
          ]
          this.$refs.oaDialog.openBox()
          break
        case 'fillIn':
          // 邀请填写
          this.$refs.fillInDialog.openBox(this.keyName)
          break
        case 'record':
          // 邀请记录
          this.$refs.fillIn.openBox(this.keyName)
          break
      }
    },
    submit(data) {
      let ids = []
      if (this.rowData.id) {
        ids.push(this.rowData.id)
      } else {
        this.multipleSelection.map((value) => {
          ids.push(value.id)
        })
      }

      if (this.dropdownType === 'transfer') {
        // 移交数据
        let obj = {
          ids,
          user_id: data.user_id[0]
        }
        moduleTransferApi(this.keyName, obj).then((res) => {
          if (res.status == 200) {
            this.$refs.oaDialog.handleClose()
            this.getList()
          }
        })
      } else if (this.dropdownType === 'share') {
        // 共享数据
        let obj = {
          ids,
          user_ids: data.user_ids,
          role_type: data.role_type
        }
        moduleShareApi(this.keyName, obj).then((res) => {
          if (res.status == 200) {
            this.$refs.oaDialog.handleClose()
            this.getList()
          }
        })
      }
    },

    handleDelete() {
      if (this.multipleSelection.length <= 0) {
        this.$message.error('至少选择一项内容')
      } else {
        modalSure('您确认要删除吗').then(() => {
          const ids = []
          this.multipleSelection.map((value) => {
            ids.push(value.id)
          })
          this.batchMessageDelete({ ids: ids })
        })
      }
    },

    // 批量删除
    batchMessageDelete(data) {
      crudModuleBatchDelApi(this.keyName, data).then((res) => {
        let totalPage = Math.ceil((this.total - data.ids.length) / this.where.limit)
        let currentPage = this.where.page > totalPage ? totalPage : this.where.page
        this.where.page = currentPage < 1 ? 1 : currentPage
        this.getList()
      })
    },

    handleSelectionChange(val) {
      this.multipleSelection = val
    },

    // 新增
    addData() {
      this.addDrawerShow = true
      this.$nextTick(() => {
        this.$refs.addDrawer.openBox()
      })
    },

    async editRow(item) {
      this.addDrawerShow = true
      const data = await crudModuleFindApi(this.keyName, item.id)
      this.$nextTick(() => {
        this.$refs.addDrawer.openBox(item.id, data.data)
      })
    },

    async checkRow(item) {
      this.checkDrawerShow = true
      const data = await crudModuleFindApi(this.keyName, item.id)
      let name = item[this.info.crudInfo.main_field_name] || '--'

      setTimeout(() => {
        this.$nextTick(() => {
          this.$refs.checkDrawer.openBox(item, data.data, this.info, name)
        })
      }, 300)
    },

    async deleteRow(item) {
      await modalSure('您确定要删除吗')
      await crudModuleDelApi(this.keyName, item.id)
      let totalPage = Math.ceil((this.total - 1) / this.where.limit)
      let currentPage = this.where.page > totalPage ? totalPage : this.where.page
      this.where.page = currentPage <= 1 ? 1 : currentPage
      await this.getList()
    },

    getInfo() {
      crudModuleInfoApi(this.keyName, 0).then((res) => {
        this.info = res.data
      })
    },

    confirmData(data) {
      if (data === 'import') {
        this.$refs.dragUpload.openBox(this.keyName, this.info)
      } else {
        this.where = { page: 1, limit: this.where.limit, ...data }
        this.getList()
      }
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getList()
    },
    pageChange(val) {
      this.where.page = val
      this.getList()
    }
  }
}
</script>
<style scoped lang="scss">
.img {
  cursor: pointer;
  display: block;
  width: 38px;
  height: 38px;
  margin-right: 4px;
  margin-bottom: 4px;
}
.share-tag {
  margin-left: 8px;
  display: inline-block;
  width: 36px;
  height: 22px;
  background: rgba(25, 190, 107, 0.05);
  color: #19be6b;
  border: 1px solid #19be6b;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 12px;
  border-radius: 3px 3px 3px 3px;
  line-height: 22px;
  text-align: center;
}
.flex_box {
  width: 100%;
  padding-right: 10px;
  display: flex;

  .tips {
    span {
      margin-right: 4px;
    }
  }
}
.img-box {
  display: flex;
  flex-wrap: wrap;
}
.dictionaries-tag {
  max-width: 100px;
  display: inline-block;
  margin: 0;
  box-sizing: border-box;
  height: 24px;
  padding: 0 8px;
  text-align: center;
  line-height: 24px;
  font-size: 12px;
  border-radius: 3px;
}
.mr10 {
  margin-right: 10px !important;
}

.batch-action-wrapper {
  position: absolute;
  bottom: -11px;
  left: 0;
  right: 0;
  height: 82px;
  background: rgba(255, 255, 255, 0.8);
  box-shadow: inset 0px 1px 0px 0px rgba(0, 0, 0, 0.05);

  display: flex;
  align-items: center;
  padding-left: 54px;

  .el-checkbox {
    margin-right: 10px;
  }

  .el-button {
    width: 74px;
    height: 32px;
    padding: 0;

    &:focus {
      background: #fff;
      border: 1px solid #dcdfe6;
      color: #606266;
    }

    &:hover {
      color: #1890ff;
      border-color: #badeff;
      background-color: #e8f4ff;
    }
  }
}

.table-box {
  ::v-deep .el-table__column-resize-proxy {
    border-left-color: #6fbaff;
  }

  ::v-deep .el-table--border {
    border: none;
    .el-table__cell {
      border-right: none;
    }
  }

  ::v-deep .el-table__fixed-right .el-table__fixed-body-wrapper {
    border-right: 1px solid #fff;
  }
}
.group-page {
  position: relative;
  margin-top: 20px;
}
</style>
