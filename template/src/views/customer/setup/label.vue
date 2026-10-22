<template>
  <div class="divBox">
    <el-card class="normal-page">
      <div class="flex-between">
        <div class="title-16">{{ $route.meta.title }}</div>
        <div>
          <el-button size="small" @click="synchronizeTags">同步标签</el-button>
          <el-button size="small" icon="el-icon-plus" type="primary" @click="addFinance">添加标签组</el-button>
        </div>
      </div>
      <div>
        <div>
          <el-table
            ref="table"
            :data="tableData"
            height="calc(100vh - 170px)"
            style="width: 100%"
            row-key="id"
            :tree-props="{ hasChildren: 'hasChildren', children: 'child' }"
            class="mt10"
          >
            <el-table-column prop="name" min-width="30">
              <template #default="scope">
                <span class="iconfont icontuodong tuozhuai" title="拖拽排序"></span>
              </template>
            </el-table-column>
            <el-table-column prop="name" :label="$t('customer.labelename')" min-width="100"></el-table-column>
            <el-table-column prop="group_type" label="标签组类型" min-width="100">
              <template #default="scope">
                <status-tag
                  :theme="groupTypeConf(scope.row.group_type).theme"
                  :variant="groupTypeConf(scope.row.group_type).variant"
                >
                  {{ groupTypeConf(scope.row.group_type).label }}
                </status-tag>
              </template>
            </el-table-column>
            <el-table-column prop="cate.name" :label="$t('customer.label')" min-width="440">
              <template #default="scope">
                <div class="label-list">
                  <div v-for="item in scope.row.children" :key="item.id" class="item">
                    {{ item.name }}
                  </div>
                </div>
              </template>
            </el-table-column>

            <el-table-column prop="address" :label="$t('public.operation')" fixed="right" width="120">
              <template #default="scope">
                <el-button type="text" v-hasPermi="['customer:setup:label:edit']" @click="handleEdit(scope.row)">{{
                  $t('public.edit')
                }}</el-button>

                <el-button
                  type="text"
                  v-hasPermi="['customer:setup:label:delete']"
                  @click="handleDelete(scope.row, 1)"
                  >{{ $t('public.delete') }}</el-button
                >
              </template>
            </el-table-column>
          </el-table>

          <!-- <div class="page-fixed">
            <el-pagination
              :page-size="where.limit"
              :current-page="where.page"
              :page-sizes="[15, 20, 30]"
              layout="total, sizes,prev, pager, next, jumper"
              :total="total"
              @size-change="handleSizeChange"
              @current-change="pageChange"
            />
          </div> -->
        </div>

        <!-- 替换标签 -->
        <el-dialog title="标签替换" :visible.sync="dialogTableVisible" width="560px" class="replaceDialog">
          <el-form :model="form" :rules="rules" label-width="90px" ref="dynamicValidateForm">
            <el-form-item label="是否替换：">
              <el-radio-group v-model="radio">
                <el-radio :label="1">替换标签</el-radio>
                <el-radio :label="2">不替换</el-radio>
              </el-radio-group>
              <div class="tips" v-if="radio == 1">标签替换之后陀螺匠系统与企业微信标签同步替换！</div>
              <div class="tips" v-if="radio == 2">不替换标签则直接删除该标签，请谨慎操作！</div>
            </el-form-item>
            <el-form-item label="替换标签：" v-if="radio == 1" prop="labelId">
              <el-cascader
                v-model="form.labelId"
                size="small"
                :options="tableData"
                placeholder="请选择替换标签"
                :props="{ label: 'name', value: 'id' }"
                style="width: 100%"
              ></el-cascader>
            </el-form-item>
          </el-form>
          <template #footer>
            <span class="dialog-footer">
              <el-button size="small" @click="dialogTableVisible = false">取消</el-button>
              <el-button size="small" type="primary" @click="deleteSubmit()">确定</el-button>
            </span>
          </template>
        </el-dialog>
      </div>
    </el-card>

    <!-- 添加标签组 -->
    <add-group-dialog ref="addGroupDialog" @isOk="getTableData()" />
    <!-- 编辑标签 -->
    <add-label
      ref="addLabel"
      @openLable="openLable"
      @getTableData="getTableData"
      @handleDelete="handleDelete"
    ></add-label>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import Sortable from 'sortablejs'
import addGroupDialog from './components/addGroupDialog'
import addLabel from './addLabel'
import StatusTag from '@/components/common/statusTag.vue'
import { LABEL_GROUP_TYPE_MAP } from '@/constants/labelGroupType'
import { clientWorkLabelApi, clientSortLabelsApi } from '@/api/client'
import {
  clientConfigLabelApi,
  clientConfigLabelDeleteApi,
  clientConfigLabelSaveApi,
  putcLientLabel
} from '@/api/enterprise'

// 未知标签组类型时退化为普通文本占位
const UNKNOWN_GROUP_TYPE = { label: '--', theme: '', variant: 'filled' }

export default {
  name: 'CustomerLabel',
  components: {
    addGroupDialog,
    addLabel,
    StatusTag
  },
  directives: {
    // 注册一个局部的自定义指令v-focus
    focus: {
      // 指令的定义
      inserted: function (el) {
        // 聚焦元素
        el.querySelector('input').focus()
      }
    }
  },
  data() {
    const validCascader = (rule, value, callback) => {
      if (this.form.labelId.length == 0) {
        callback(new Error('请选择替换标签'))
      } else {
        callback()
      }
    }

    return {
      dialogTableVisible: false,
      tableData: [],
      where: {
        page: 1,
        limit: 999
      },
      form: {
        labelId: []
      },
      rules: {
        labelId: [{ required: true, validator: validCascader, trigger: 'change' }]
      },
      radio: 1,

      rowData: {},
      editData: {},
      editType: 'add',
      total: 0,
      label: '',
      tabIndex: -1,
      search: []
    }
  },
  created() {
    this.getTableData()
  },
  mounted() {
    setTimeout(() => {
      this.rowDrop()
    }, 500)
  },
  methods: {
    // 标签组类型 -> 标签展示配置
    groupTypeConf(value) {
      return LABEL_GROUP_TYPE_MAP[value] || UNKNOWN_GROUP_TYPE
    },
    synchronizeTags() {
      clientWorkLabelApi().then((res) => {})
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    // 打开替换弹窗
    openLable(data) {
      this.tableData.forEach((item) => {
        item.children.forEach((val) => {
          if (val.id == data.id) {
            val.disabled = true
          }
        })
      })
      this.dialogTableVisible = true
      this.form.labelId = []
      this.rowData = data
    },

    deleteSubmit() {
      let data = {
        label_id: this.form.labelId[1]
      }
      this.$refs.dynamicValidateForm.validate((valid) => {
        if (valid) {
          clientConfigLabelDeleteApi(this.rowData.id, data).then((res) => {
            this.dialogTableVisible = false
            this.$refs.addLabel.delFn()
            this.getTableData()
          })
        }
      })
    },
    // 获取表格数据
    getTableData(val) {
      this.where.page = val ? val : this.where.page
      let data = {
        page: this.where.page,
        limit: this.where.limit
      }
      clientConfigLabelApi(data).then((res) => {
        this.tableData = res.data.list.reverse();
        this.total = res.data.count
      })
    },
    // 添加标签组
    addFinance() {
      this.$refs.addGroupDialog.openBox()
    },
    // 编辑分类
    handleEdit(item) {
      this.$refs.addLabel.openBox(item)
    },
    // 删除
    handleDelete(item, type) {
      const mes = type === 1 ? this.$t('customer.message03') : this.$t('customer.message04')
      modalSure(mes).then(() => {
        clientConfigLabelDeleteApi(item.id).then((res) => {
          if (this.where.page > 1 && this.tableData.length <= 1) {
            this.where.page--
          }
          if (this.tableData.length == 1) {
            this.getTableData(1)
          } else {
            this.getTableData()
          }
        })
      })
    },
    // 客户标签保存
    labelSave(data, type) {
      if (this.editType == 'edit') {
        this.putcLientLabel(this.editData)
      } else {
        clientConfigLabelSaveApi(data).then((res) => {
          this.label = ''
          if (type === 2) {
            this.tabIndex = -1
          }
          this.getTableData()
        })
      }
    },
    handlePlus(index, row, type) {
      if (type === 'edit') {
        this.tabIndex = row.id
        this.label = row.name
        this.editType = type
        this.editData = row
      } else {
        this.tabIndex = index + 'add'
        if (this.label !== '') {
          this.labelSave({ name: this.label, pid: row.id }, 1)
        }
      }
    },
    putcLientLabel(row) {
      let data = {
        name: this.label,
        pid: row.pid
      }
      putcLientLabel(row.id, data).then((res) => {
        this.label = ''
        this.tabIndex = -1
        this.getTableData()
        this.editType = 'add'
      })
    },
    handleSubmit(index, row) {
      if (this.label === '') {
        this.tabIndex = -1
      } else {
        this.labelSave({ name: this.label, pid: row.id }, 2)
      }
    },
    // 表格拖拽排序
    rowDrop() {
      const tbody = this.$refs.table.$el.querySelectorAll('.el-table__body-wrapper > table > tbody')[0]

      Sortable.create(tbody, {
        animation: 200,
        handle: '.icontuodong',
        onEnd: (e) => {
          // 调整数据顺序
          const movedItem = this.tableData.splice(e.oldIndex, 1)[0]
          this.tableData.splice(e.newIndex, 0, movedItem)
          this.$refs.table.doLayout()
          let ids = []
          this.tableData.forEach((item) => {
            ids.push(item.id)
          })
          clientSortLabelsApi({ label: ids }).then((res) => {})
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
::v-deep .el-scrollbar__wrap {
  overflow-x: hidden;
}
.label-list {
  display: flex;
  align-items: center;
  width: 100%;
  overflow-x: auto;
}
.tips {
  line-height: 18px;
  flex-shrink: 0;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 12px;
  color: red;
}
.replaceDialog {
  ::v-deep .el-dialog__body {
    padding-bottom: 0px;
  }
}
.tuozhuai {
  cursor: move;
  color: #909399;
}
.item {
  // width: 100%;
  padding: 3px 8px;
  white-space: nowrap;
  border-radius: 4px 4px 4px 4px;
  border: 1px solid #dddddd;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
  margin-right: 10px;
}
</style>
