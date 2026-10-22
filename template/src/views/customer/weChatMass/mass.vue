<!-- 群发素材页面 -->
<template>
  <div class="divBox">
    <div>
      <el-card :body-style="{ padding: '20px' }" class="card-box">
        <div>
          <el-row>
            <el-col v-bind="gridl">
              <left
                @eventOptionData="eventOptionData"
                @getTargetCate="getTargetCate"
                :leftList="leftList"
                ref="left"
              ></left>
            </el-col>
            <el-col v-bind="gridr" class="assess-right">
              <div class="ml14">
                <oaFromBox
                  :search="search"
                  :total="total"
                  :isViewSearch="false"
                  title="群发素材列表"
                  btnText="新增素材"
                  @addDataFn="handleNews"
                  @confirmData="confirmData"
                ></oaFromBox>

                <div class="mt14">
                  <el-table :data="tableData" :height="TABLE_HEIGHT" style="width: 100%" row-key="id" default-expand-all>
                    <el-table-column prop="name" label="素材内容" min-width="180" :show-overflow-tooltip="true">
                      <template #default="scope">
                        {{ scope.row.content }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="title" label="素材分类" min-width="150">
                      <template #default="scope">
                        {{ scope.row.group.name || '--' }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="created_at" label="创建时间" min-width="120" />
                    <el-table-column prop="creator.name" label="创建人" min-width="80" />
                    <el-table-column prop="describe" :label="$t('public.operation')" fixed="right" width="120">
                      <template #default="scope">
                        <el-button type="text" @click="handleEdit(scope.row)">{{ $t('public.edit') }}</el-button>
                        <el-button type="text" @click="handleDelete(scope.row)">
                          {{ $t('public.delete') }}
                        </el-button>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </div>
            </el-col>
          </el-row>
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
      </el-card>
    </div>

    <!-- 添加素材 -->
    <addmass ref="addmass" :leftList="groupedData" :group_id="where.group_id" @getTableData="getTableData"></addmass>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import { TABLE_HEIGHT } from '@/constants/tableSize'
import { workMassTempListApi, workMassTempGroupApi, workMassTempDelApi } from '@/api/weCom'
export default {
  name: 'IndexVue',
  components: {
    left: () => import('./components/left'),
    addmass: () => import('./components/addmass'),
    materialContent: () => import('./components/materialContent'),
    oaFromBox: () => import('@/components/common/oaFromBox')
  },
  data() {
    return {
      TABLE_HEIGHT,
      gridl: {
        xl: 3,
        lg: 4,
        md: 5,
        sm: 6,
        xs: 24
      },

      gridr: {
        xl: 21,
        lg: 20,
        md: 19,
        sm: 18,
        xs: 24
      },
      leftList: [],
      groupedData: [],

      where: {
        page: 1,
        limit: 15,
        group_id: '',
        name: '',
        time: ''
      },
      tabIndex: 0,
      total: 0,
      tableData: [],

      search: [
        {
          form_value: 'input',
          field_name_en: 'name',
          field_name: '素材内容'
        },
        {
          form_value: 'date_picker',
          field_name_en: 'time',
          field_name: '创建时间'
        }
      ]
    }
  },
  mounted() {
    this.getTargetCate()
    this.getTableData()
  },
  methods: {
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    // 获取表格数据
    getTableData() {
      workMassTempListApi(this.where).then((res) => {
        this.tableData = res.data.list || []
        this.total = res.data.count
      })
    },

    handleSearch() {
      this.where.page = 1
      this.getTableData()
    },
    handleNews() {
      this.$refs.addmass.openBox()
    },

    handleEdit(row) {
      this.$refs.addmass.openBox(row.id)
    },
    async handleDelete(item) {
      await modalSure('你确定要删除这条内容吗')

      await workMassTempDelApi(item.id)
      if (this.where.page > 1 && this.tableData.length <= 1) {
        this.where.page--
      }
      this.getTableData()
    },

    eventOptionData(data, index) {
      this.where.group_id = data.id

      this.tabIndex = JSON.parse(JSON.stringify(index))
      this.handleSearch()
    },

    getTargetCate() {
      this.leftList = []
      this.groupedData = []
      workMassTempGroupApi().then((res) => {
        this.groupedData = JSON.parse(JSON.stringify(res.data.list))
        res.data.list.unshift({
          id: '',
          name: '全部'
        })

        this.leftList = res.data.list
      })
    },
    confirmData(data, resetState) {
      if (data == 'reset') {
        this.where = {
          page: 1,
          group_id: this.where.group_id,
          title: '',
          limit: this.where.limit,
          status: '',
          // 排序等由搜索栏维护的默认参数，重置后需一并带回
          ...resetState
        }
      } else {
        this.where = { ...this.where, ...data }
      }
      this.handleSearch()
    }
  }
}
</script>

<style lang="scss" scoped>
.card-box {
  height: calc(100vh - 77px);
}
.assess-right {
  ::v-deep .el-card__header {
    border-bottom: none;
    padding: 0;
  }
}
.icontxt1 {
  font-size: 18px;
  color: #ff9900;
}
.iconlianjie1 {
  font-size: 20px;
  color: #1890ff;
}
.iconxiaochengxu1 {
  font-size: 20px;
  color: #19be6b;
}

.right-con {
  display: flex;
  justify-content: flex-end;
}
.table-img {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  margin-right: 10px;
}
.select-bar {
  margin-bottom: 0;
  ::v-deep .el-input-group__append {
    top: 0;
    button {
      color: #fff;
      background-color: #1890ff;
      border-color: #1890ff;
      border-radius: 0 5px 5px 0;
    }
  }
}
::v-deep .el-textarea__inner,
.el-input__inner {
  font-size: 13px !important;
}
::v-deep .el-input__inner {
  font-size: 13px !important;
}
::v-deep .is-top .el-switch__core {
  width: 69px !important;
}
</style>
