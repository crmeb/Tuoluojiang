<template>
  <div>
    <oaFromBox
      :isAddBtn="false"
      title="我的申请"
      :search="search"
      :total="total"
      :isViewSearch="false"
      @confirmData="confirmData"
    >
      <template #rightBtn>
        <el-dropdown
          ref="buildDropdown"
          trigger="click"
          size="small"
          placement="bottom-end"
          :hide-on-click="false"
          @command="handleBuild"
          @visible-change="handleBuildVisibleChange"
        >
          <el-button type="primary" size="small">发起申请</el-button>
          <template #dropdown>
            <el-dropdown-menu class="build-dropdown" style="overflow: visible; max-height: none">
              <!-- 顶部搜索框：固定在容器顶部，位于滚动区之外，滚动列表时不受影响 -->
              <div class="build-search" style="padding: 8px 12px; background: #fff" @click.stop>
                <el-input
                  v-model="buildKeyword"
                  size="small"
                  placeholder="请输入名称搜索"
                  prefix-icon="el-icon-search"
                  clearable
                  style="width: 170px"
                  @keydown.native.stop
                />
              </div>
              <!-- 可滚动列表区：与搜索框分离，因此列表滚动时搜索框保持固定 -->
              <div class="build-list" style="max-height: 260px; overflow-y: auto; overflow-x: hidden">
                <el-dropdown-item
                  v-for="item in filteredDropdownList"
                  :key="item.id"
                  class="over-text"
                  placement="top-end"
                  :command="item.id"
                >
                  <i class="iconfont" :class="item.icon" :style="{ color: item.color }"></i>
                  {{ item.name }}
                </el-dropdown-item>
                <div
                  v-if="!filteredDropdownList.length"
                  class="build-empty"
                  style="padding: 8px 12px; text-align: center; color: #909399; font-size: 13px"
                >
                  无匹配结果
                </div>
              </div>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </template>
    </oaFromBox>
    <div class="table-box mt10">
      <el-table
        :data="tableData"
        :height="TABLE_HEIGHT"
        style="width: 100%"
        v-loading="loading"
        row-key="id"
        default-expand-all
      >
        <el-table-column prop="name" label="审批类型" min-width="250">
          <template #default="scope">
            <el-row class="table-title">
              <el-col class="table-title-left">
                <div class="selIcon" :style="{ backgroundColor: scope.row.approve.color }">
                  <i class="icon iconfont" :class="scope.row.approve.icon"></i>
                </div>
              </el-col>
              <el-col class="table-title-right">
                <p class="title">{{ scope.row.approve.name }}</p>
                <p class="over-text">{{ getValue(scope.row.content) }}</p>
              </el-col>
            </el-row>
          </template>
        </el-table-column>
        <el-table-column prop="name" label="审批状态" min-width="80">
          <template #default="scope">
            <span class="status">
              <el-tag v-if="scope.row.status === -1" type="info" effect="plain" size="mini"> 已撤销 </el-tag>
              <el-tag v-if="scope.row.status === 1 && scope.row.recall" type="info" effect="plain" size="mini">
                撤销中
              </el-tag>
              <el-tag v-if="scope.row.status === 0" type="warning" effect="plain" size="mini"> 审核中 </el-tag>
              <el-tag v-if="scope.row.status === 1 && !scope.row.recall" type="info" effect="plain" size="mini">
                已通过
              </el-tag>
              <el-tag v-if="scope.row.status === 2" type="danger" effect="plain" size="mini"> 已拒绝 </el-tag>
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" min-width="150" />
        <el-table-column prop="name" :label="$t('public.operation')" width="200">
          <template #default="scope">
            <el-button type="text" @click="handleDetail(scope.row)">详情 </el-button>
            <el-button v-if="scope.row.status === 1 && scope.row.type < 6" type="text" @click="handleEdit(scope.row)"
              >再次提交</el-button
            >

            <el-button
              v-if="
                ((scope.row.status === 1 && scope.row.rules && scope.row.rules.recall && scope.row.rules.recall == 1) ||
                  scope.row.status === 0) &&
                !scope.row.recall
              "
              type="text"
              @click="handleRefuse(scope.row)"
            >
              撤销
            </el-button>

            <el-button
              v-if="
                (([2, -1].includes(scope.row.status) && scope.row.approve.types < 6) || scope.row.approve.types > 11) &&
                scope.row.crud_id == 0 &&
                scope.row.approve.types != 12
              "
              type="text"
              @click="handleEdit(scope.row)"
            >
              重新提交
            </el-button>
          </template>
        </el-table-column>
      </el-table>
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
    </div>

    <edit-examine ref="editExamine" @isOk="getTableData()" :type="type" />
    <detail-examine ref="detailExamine" @getList="getTableData" />
    <!-- 撤销 -->
    <oa-dialog
      ref="oaDialog"
      :fromData="fromData"
      :formConfig="formConfig"
      :formRules="formRules"
      :formDataInit="formDataInit"
      @submit="getApplyRevoke"
    ></oa-dialog>
  </div>
</template>

<script>
import { TABLE_HEIGHT } from '@/constants/tableSize'
import { approveApplyApi, approveApplyRevokeApi, approveConfigSearchApi } from '@/api/business'
import func from '@/utils/preload'
export default {
  name: 'Submission',
  components: {
    detailExamine: () => import('@/views/user/examine/components/detailExamine'),
    editExamine: () => import('@/views/user/examine/components/editExamine'),
    oaDialog: () => import('@/components/form-common/dialog-form'),
    defaultPage: () => import('@/components/common/defaultPage'),
    oaFromBox: () => import('@/components/common/oaFromBox')
  },
  props: {
    // 进入页面时预置的审批状态（0 审核中 / 1 已通过 / 2 已拒绝 / -1 已撤销），空字符串表示不预置
    defaultStatus: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    // 预置状态既要写进查询参数，也要回显到筛选下拉，避免界面与请求参数不一致
    const defaultStatus =
      this.defaultStatus === '' || this.defaultStatus === null || this.defaultStatus === undefined
        ? ''
        : this.defaultStatus
    return {
      TABLE_HEIGHT,
      tableData: [],
      formDataInit: {
        info: ''
      },
      formConfig: [
        {
          type: 'textarea',
          label: '撤销理由：',
          placeholder: '请输入撤销理由',
          key: 'info'
        }
      ],
      formRules: {
        info: [{ required: true, message: '请输入撤销理由', trigger: 'blur' }]
      },
      fromData: {
        width: '600px',
        title: '撤销',
        btnText: '确定',
        labelWidth: 'auto',
        type: ''
      },
      rowData: {},
      loading: false,
      where: {
        page: 1,
        limit: 15,
        types: 0,
        time: '',
        status: defaultStatus,
        approve_id: ''
      },
      total: 0,
      search: [
        {
          field_name: '审批状态',
          field_name_en: 'status',
          form_value: 'select',
          search_default: defaultStatus,
          data_dict: [
            // { name: '全部', id: '' },
            { name: '审核中', id: 0 },
            { name: '已通过', id: 1 },
            { name: '已拒绝', id: 2 },
            { name: '已撤销', id: -1 }
          ]
        },
        {
          field_name: '审批类型',
          field_name_en: 'approve_id',
          form_value: 'select',
          data_dict: []
        },
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker'
        }
      ],
      buildData: [],
      type: 0,
      dropdownList: [],
      // 发起申请下拉的搜索关键词
      buildKeyword: ''
    }
  },
  computed: {
    // 发起申请下拉：按名称过滤后的审批类型列表
    filteredDropdownList() {
      const kw = this.buildKeyword.trim().toLowerCase()
      if (!kw) return this.dropdownList
      return this.dropdownList.filter((item) => String(item.name || '').toLowerCase().includes(kw))
    }
  },
  async created() {
    // 挂载全局工具函数
    if (!this.$root.func) {
      this.$root.func = func
    }
    // 并行请求数据以提高性能
    try {
      await Promise.all([this.getTableData(), this.getConfigSearch(0), this.getConfigSearch(3)])
    } catch (error) {
      console.error('初始化数据加载失败:', error)
      this.$message.error('数据加载失败，请刷新页面重试')
    }
  },
  methods: {
    handleBuild(command) {
      // 检查 editExamine 引用是否存在，避免潜在的错误
      if (this.$refs.editExamine) {
        this.$refs.editExamine.isEdit = false
        this.$refs.editExamine.openBox(command)
      }
      // hide-on-click 已关闭自动收起，这里手动收起下拉并清空搜索关键词
      if (this.$refs.buildDropdown) {
        this.$refs.buildDropdown.hide()
      }
      this.buildKeyword = ''
    },

    // 发起申请下拉收起时清空搜索关键词，避免下次展开残留过滤
    handleBuildVisibleChange(visible) {
      if (!visible) {
        this.buildKeyword = ''
      }
    },

    async getConfigSearch(id) {
      try {
        const result = await approveConfigSearchApi(id)
        const data = result && result.data && Array.isArray(result.data) ? result.data : []
        if (id === 0) {
          // name转label id转value
          if (Array.isArray(data)) {
            const formattedData = data.map((item) => ({
              ...item,
              label: item.name,
              value: item.id
            }))
            this.dropdownList = formattedData
          }
        }
        // 1、下级审批；3、我提交过的所有类型；
        if (id === 3 || id === 1) {
          if (Array.isArray(data)) {
            const selectData = [...data]
            // selectData.unshift({ name: '全部', id: '' })
            this.search[1].data_dict = selectData
          }
        }
      } catch (error) {
        console.error('获取配置数据失败:', error)
        if (id === 0) {
          this.dropdownList = []
        } else if (id === 3 || id === 1) {
          // this.search[1].data_dict = [{ name: '全部', id: '' }]
        }
      }
    },

    confirmData(data) {
      // 当数据为 'reset' 时，重置查询条件
      if (data === 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          types: 0,
          time: '',
          status: '',
          approve_id: ''
        }
      } else {
        // 合并查询条件
        this.where = { ...this.where, ...data }
      }
      this.getTableData()
    },

    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },

    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },

    async getTableData() {
      this.loading = true
      try {
        const data = this.where
        this.where.verify_status = ''
        const result = await approveApplyApi(data)
        this.tableData = result && result.data && Array.isArray(result.data.list) ? result.data.list : []
        this.total = result && result.data && typeof result.data.count === 'number' ? result.data.count : 0
      } catch (error) {
        console.error('获取审批数据失败:', error)
        this.tableData = []
        this.total = 0
      } finally {
        this.loading = false
      }
    },
    // 详情
    handleDetail(row) {
      this.type = 1
      this.$refs.detailExamine.openBox(row)
    },
    getApproveIcon(icon) {
      let str = ''
      // 确保 icon 存在
      if (!icon || typeof icon !== 'string') {
        return str
      }
      if (icon.indexOf('iconjine') > -1 || icon.indexOf('iconwenjian') > -1) {
        str = icon + '2'
      } else if (icon.indexOf('icontupian2') > -1) {
        str = 'icontupian3'
      } else if (icon.indexOf('icona-xingzhuang2') > -1) {
        str = 'iconxingzhuang21'
      } else if (icon === 'iconwendang2') {
        str = 'iconxingzhuang21'
      } else if (icon === 'iconwendang1') {
        str = 'icona-xingzhuang12'
      } else if (icon === 'iconrili1') {
        str = 'iconrili2'
      } else {
        str = icon
      }

      return str
    },
    // 重新提交
    handleEdit(row) {
      this.$refs.editExamine.isEdit = true
      this.$refs.editExamine.openBox(row)
    },
    handleRefuse(row) {
      this.rowData = row
      if (row.status === 0) {
        this.$confirm(this.$t('你确定要撤销申请吗'), '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            this.getApplyRevoke({})
          })
          .catch(() => {
            // 用户取消操作
          })
      } else {
        this.formDataInit.info = ''
        this.$refs.oaDialog.openBox()
      }
    },
    async getApplyRevoke(data) {
      try {
        await approveApplyRevokeApi(this.rowData.id, data)
        if (this.$refs.oaDialog && typeof this.$refs.oaDialog.handleClose === 'function') {
          // this.$refs.oaDialog.handleClose()
        }
        this.$message.success('撤销申请成功')
      } catch (error) {
        console.error('撤销申请失败:', error)
        this.$message.error(error.message || '撤销申请失败')
      }
      this.getTableData()
    },
    getValue(row) {
      let arr = []
      let items = []
      if (Array.isArray(row)) {
        items = row
      } else if (row && typeof row === 'object') {
        items = Object.values(row)
      }
      for (let i = 0; i < items.length; i++) {
        const item = items[i]
        if (item && item.value && item.type !== 'rich_text') {
          if (typeof item.value === 'string') {
            // 处理简单字符串值
            let str = item.label + '：' + item.value
            arr.push(str)
          } else if (Array.isArray(item.value)) {
            // 处理值为数组的情况，如时间范围等
            const subValues = []
            for (const subItem of item.value) {
              if (subItem && subItem.label && subItem.value) {
                subValues.push(subItem.label + '：' + subItem.value)
              }
            }
            if (subValues.length > 0) {
              const str = item.label + '：[' + subValues.join(', ') + ']'
              arr.push(str)
            }
          }
          // 只取前3个项目
          if (arr.length >= 3) {
            break
          }
        }
      }
      return arr.join(' | ')
    }
  }
}
</script>

<style lang="scss" scoped>
.build-dropdown {
  // max-height: fit-content;
  max-height: 300px;
  overflow: auto;
  overflow-x: hidden;

  .iconfont {
    font-size: 14px !important;
  }
}

.status {
  ::v-deep .el-tag {
    background: #fff;
  }
}

.build-dropdown::-webkit-scrollbar {
  /*width: 0;宽度为0隐藏*/
  width: 8px;
  height: 4px;
}

.build-dropdown::-webkit-scrollbar-thumb {
  border-radius: 5px;
  height: 8px;
  background: rgba(0, 0, 0, 0.2); //滚动条颜色
}

.build-dropdown::-webkit-scrollbar-track {
  box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
  border-radius: 5px;
  background: #eee; //滚动条背景色
}

.table-box {
  .table-title {
    display: flex;
    align-items: center;

    .table-title-left {
      width: 56px;

      i {
        color: #fff;
        font-size: 46px;
      }
    }

    .table-title-right {
      width: calc(100% - 56px);

      p {
        margin: 0;
        font-size: 13px;
      }

      .title {
        font-weight: bold;
        font-size: 13px;
      }

      .over-text {
        margin-top: 8px;
      }
    }
  }

  ::v-deep .el-table .cell {
    line-height: 1;
  }
}

.selIcon {
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 3px;
}

.iconfont {
  font-size: 13px !important;
  color: #fff;
}
</style>
