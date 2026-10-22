<template>
  <div>
    <div class="header">
      <div class="title"><span class="el-icon-arrow-left" @click="goBack"></span>实体列表</div>
      <div class="right lh-center">
        <el-button type="primary" icon="el-icon-plus" size="small" @click="createEntity('', 'add')">
          新建实体</el-button
        >
        <el-dropdown>
          <span class="iconfont icongengduo2 pointer ml10"></span>
          <el-dropdown-menu style="text-align: left">
            <el-dropdown-item @click.native="importEntity"> 导入实体</el-dropdown-item>

            <el-dropdown-item @click.native="downloadFile"> 下载模板</el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </div>

    <div class="flex h32 lh-center mb10">
      <div class="inTotal">共 {{ total }} 条</div>
      <div class="ml20">
        <el-input
          v-model="where.table_name"
          prefix-icon="el-icon-search"
          size="small"
          placeholder="请输入关键字"
          clearable
          style="width: 250px"
          @change="getList(1)"
          @keyup.native.stop.prevent.enter="getList(1)"
          class="input"
        ></el-input>
      </div>
    </div>

    <div class="table-box">
      <el-table
        v-loading="loading"
        row-key="id"
        :data="tableData"
        :height="height"
        style="width: 100%"
        :tree-props="{ children: 'children' }"
      >
        <el-table-column prop="table_name_en" label="实体名称" min-width="180">
          <template #default="scope">
            <span class="color-doc pointer" @click="designFn(scope.row)">{{ scope.row.table_name_en }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="table_name" label="显示名称" min-width="150">
          <template #default="scope">
            {{ scope.row.table_name }}
            <span :class="scope.row.crud_id == 0 ? 'zhu' : 'cong'">{{ scope.row.crud_id == 0 ? '(主)' : '(从)' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="info" label="关联应用" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            <span>{{ row.cate.join('、') || '--' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="menu_name_path" label="关联菜单" min-width="250" show-overflow-tooltip>
          <template #default="{ row }">
            <span>{{ row.menu_name_path.join(' - ') || '--' }}</span>
          </template>
        </el-table-column>

        <el-table-column prop="info" label="实体说明" min-width="250" show-overflow-tooltip>
          <template #default="scope">{{ scope.row.info || '--' }}</template>
        </el-table-column>
        <el-table-column prop="user" label="创建人" min-width="180">
          <template #default="{ row }">
            <div style="display: flex; align-items: center; gap: 8px">
              <el-avatar size="small" :src="row.user.avatar"></el-avatar>
              <span>{{ row.user.name || '--' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="230"> </el-table-column>
        <el-table-column prop="address" label="操作" fixed="right" width="150">
          <template #default="scope">
            <el-button type="text" @click="designFn(scope.row)">实体设计</el-button>
            <el-dropdown>
              <span class="el-dropdown-link el-button--text el-button more">
                更多
                <i class="el-icon-arrow-down" />
              </span>
              <el-dropdown-menu style="text-align: left">
                <el-dropdown-item @click.native="designFn(scope.row, 1)"> 实体属性 </el-dropdown-item>
                <el-dropdown-item @click.native="designFn(scope.row, 2)"> 字段设计 </el-dropdown-item>
                <template v-if="scope.row.crud_id == 0">
                  <el-dropdown-item @click.native="designFn(scope.row, 3)"> 表单设计 </el-dropdown-item>
                  <el-dropdown-item @click.native="designFn(scope.row, 4)"> 列表设计 </el-dropdown-item>
                  <el-dropdown-item @click.native="designFn(scope.row, 5)"> 流程设计 </el-dropdown-item>
                  <el-dropdown-item @click.native="designFn(scope.row, 6)"> 触发器设计 </el-dropdown-item>
                </template>
                <el-dropdown-item
                  divided
                  v-if="scope.row.children && scope.row.children.length == 0 && scope.row.crud_id == 0"
                  @click.native="createEntity(scope.row)"
                >
                  添加从实体
                </el-dropdown-item>
                <el-dropdown-item divided @click.native="deleteEntity(scope.row)"> 删除实体 </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>
    </div>
    <div class="page-fixed">
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

    <!-- 新建实体弹窗 -->
    <oa-dialog
      ref="oaDialog"
      v-if="dialogShow"
      :fromData="fromData"
      :formConfig="formConfig"
      :formRules="formRules"
      :formDataInit="formDataInit"
      @submit="submit"
    ></oa-dialog>
    <!-- 导入 -->
    <importExcel
      ref="importExcel"
      :distinguish="distinguish"
      :column-number="columnNumber"
      @importExcelData="importExcelData"
    />
    <!-- ai新建字段 -->
    <fieldsettingAi v-if="fieldsettingAiShow" ref="fieldsettingAi" :info="info" @getList="getList"></fieldsettingAi>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import { fileLinkDownLoad } from '@/utils/file'
import {
  databaseSaveApi,
  databaseListApi,
  databaseDelApi,
  databaseCopyApi,
  getDatabaseApi,
  getImportTempApi
} from '@/api/develop'
import { pinyin } from 'pinyin-pro'
import { roterPre } from '@/settings'
import { menuListApi } from '@/api/system'
import fieldsettingAi from './fieldsettingAi'
import oaDialog from '@/components/form-common/dialog-form'
import Commnt from '@/components/develop/commonData'
import importExcel from '@/components/common/importExcel'
export default {
  components: {
    fieldsettingAi,
    oaDialog,
    Commnt,
    importExcel
  },
  props: {
    cate_id: {
      type: [String, Number],
      default: ''
    },
    cateItem: {
      type: Object,
      default: () => {}
    },
    applicationTabData: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      tableData: [],
      height: window.innerHeight - 280 + 'px',
      where: {
        table_name: '',
        page: 1,
        limit: 15,
        cate_id: this.cate_id
      },
      dialogShow: false,
      fieldsettingAiShow: false,
      info: {
        crud_id: '',
        table_name: '',
        table_name_en: '',
        cate_ids: []
      },
      menuList: [],
      id: 0,
      total: 0,
      loading: false,
      fromData: {
        width: '663px',
        title: '新建实体',
        btnText: '确定',
        labelWidth: '100px',
        type: ''
      },
      formDataInit: Commnt.formDataInit,
      formRules: Commnt.formRules,
      formConfig: Commnt.formConfig,
      distinguish: 0,
      columnNumber: 0
    }
  },
  watch: {
    cateItem: {
      handler(newVal, oldVal) {},
      immediate: true
    }
  },

  mounted() {
    this.where.cate_id = this.cate_id
    this.getList()
  },
  methods: {
    goBack() {
      this.$emit('goBack')
    },

    importEntity() {
      this.$refs.importExcel.btnClick()
    },
    // 下载模板
    downloadFile() {
      getImportTempApi().then((res) => {
        fileLinkDownLoad(res.data.url, '实体导入模板.xlsx')
      })
    },

    // 导入
    importExcelData(data, name) {
      this.fieldsettingAiShow = true

      // 过滤掉值为 ID/id/"ID" 的列
      data.forEach((row, idx) => {
        data[idx] = row.filter((v) => !['id', 'ID', '"ID"'].includes(String(v).trim()))
      })

      // 解析实体名：去掉扩展名及括号内容
      const crudName = name.split('.')[0].replace(/\([^)]*\)/g, '')
      this.info = {
        ...this.info,
        table_name: crudName,
        table_name_en: this.refreshFn(crudName)
      }

      const header = data[0] || []
      if (!header.length || header.every((v) => !v)) {
        return this.$message.error('导入的数据不能为空')
      }

      // 生成字段列表
      const list = header.filter(Boolean).map((text) => {
        const fieldName = text.replace(/\([^)]*\)/g, '')
        return {
          field_name: fieldName,
          field_name_en: this.refreshFn(fieldName),
          value: 'input',
          comment: '',
          is_default_value_not_null: 1,
          is_table_show_row: 1,
          create_modify: 1,
          update_modify: 1,
          data_dict_id: '',
          association_crud_id: '',
          association_field_names: [],
          is_uniqid: 0,
          options: []
        }
      })
      setTimeout(() => {
        this.$refs.fieldsettingAi.openBox(list)
      }, 300)
    },
    // 刷新转拼音小写
    refreshFn(key) {
      const regex = /^[\u4e00-\u9fa5a-zA-Z][\u4e00-\u9fa5a-zA-Z_]{0,15}$/
      if (!regex.test(key)) {
        return false
      }
      let str = pinyin(key, { toneType: 'none' })
      const reg = /[\t\r\f\n\s]*/g
      if (typeof str === 'string') {
        str = str.replace(reg, '')
      }
      return str
    },

    // 新建实体
    async createEntity(row, type) {
      // 获取菜单列表并追加“顶级菜单”
      // 打开弹窗
      this.dialogShow = true
      const { data: menus } = await menuListApi()

      this.menuList = [{ menu_name: '顶级菜单', id: 0 }, ...menus]

      // 重置表单初始值
      this.formDataInit = {
        table_name: '',
        table_name_en: '',
        crud_id: '',
        cate_ids: this.where.cate_id > 0 && !row ? [this.where.cate_id] : [],
        crud_type: '0',
        info: '',
        show_log: '1',
        show_comment: 1,
        path: this.cateItem?.menu?.path || [],
        icon: '',
        uni_img: ''
      }

      // 复制模式或编辑模式
      if (type === 'copy') {
        this.fromData = { title: '复制实体', type: 'copy' }
        this.formDataInit.info = row.info
        this.formDataInit.cate_ids = row.cate_ids
      } else {
        this.fromData.type = ''
        if (row?.id) {
          this.id = row.id
          this.formDataInit.crud_id = row.id
        }
      }

      // 拉取下拉选项并赋值
      const [{ data: dbList }] = await Promise.all([getDatabaseApi()])
      this.formConfig[3].options = dbList
      this.formConfig[8].options = this.menuList

      // this.formConfig[7].options = this.applicationTabData.slice(1)
      this.formConfig[7].options = this.applicationTabData

      this.$refs.oaDialog.openBox()
    },

    // 新建实体回调
    submit(data, type) {
      if (data.crud_id && Array.isArray(data.crud_id)) {
        data.crud_id = data.crud_id[1]
      }

      if (type == 'copy') {
        databaseCopyApi(this.id, data)
          .then((res) => {
            if (res.status == 200) {
              this.$refs.oaDialog.handleClose()
              this.getList()
            }
          })
          .catch((err) => {
            this.$message.error(err.message)
          })
      } else {
        databaseSaveApi(data)
          .then((res) => {
            if (res.status == 200) {
              this.$refs.oaDialog.handleClose()
              this.getList()
            }
          })
          .catch((err) => {
            this.$message.error(err.message)
          })
      }
    },
    // 删除实体
    async deleteEntity(row) {
      await modalSure('你确定要删除这条实体吗')
      await databaseDelApi(row.id)
      const totalPage = Math.ceil((this.total - 1) / this.where.limit)
      this.where.page = this.where.page > totalPage ? totalPage : this.where.page
      this.where.page = this.where.page < 1 ? 1 : this.where.page
      await this.getList()
    },

    designFn(row, tabIndex) {
      this.$router.push({
        path: `${roterPre}/develop/crud/design`,
        query: {
          id: row.id,
          tabIndex: tabIndex || 4,
          tab: this.activeName,
          cate_id: this.where.cate_id
        }
      })
    },
    // 获取实体列表
    async getList() {
      this.loading = true
      const data = await databaseListApi(this.where)
      this.total = data.data.count
      this.tableData = data.data.list
      this.loading = false
    },

    pageChange(page) {
      this.where.page = page
      this.getList()
    },

    handleSizeChange(val) {
      this.where.limit = val
      this.getList()
    }
  }
}
</script>
<style lang="scss" scoped>
.header {
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.title {
  font-family: PingFang SC, PingFang SC;
  font-weight: 500;
  font-size: 18px;
  height: 18px;
  color: #303133;
  cursor: pointer;
  display: flex;
  align-items: center;

  .el-icon-arrow-left {
    color: #606266;
    font-size: 14px;
    margin-right: 5px;
    font-weight: 500;
  }
}
</style>
