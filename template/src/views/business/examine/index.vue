<!-- 人事-办公审批-审批设置：新建 / 编辑审批流程在独立路由（business/examine/create、edit?id=） -->
<template>
  <div class="divBox">
    <div>
      <el-card class="normal-page p20" style="padding-bottom: 0">
        <div class="flex-between">
          <div class="title-16">审批列表</div>
          <el-button type="primary" icon="el-icon-plus" @click="handleCreate">
            新建审批流程
          </el-button>
        </div>
        <div class="total-16 mb10 mt20">共 {{ total }} 条</div>
        <div v-if="tableData.length > 0">
          <el-table :data="tableData" v-loading="loading" :height="tableHeight1">
            <el-table-column prop="name" label="审批类型" min-width="230" show-overflow-tooltip>
              <template #default="scope">
                <div class="flex">
                  <div class="selIcon" :style="{ backgroundColor: scope.row.color }">
                    <i class="icon iconfont" :class="scope.row.icon"></i>
                  </div>
                  <div class="ml10">{{ scope.row.name || '--' }}</div>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="refuse" label="发起人范围" min-width="200" show-overflow-tooltip>
              <template #default="scope">
                <span
                  v-if="
                    scope.row.process &&
                    scope.row.process.info.depList.length <= 0 &&
                    scope.row.process.info.userList.length <= 0
                  "
                >
                  所有人
                </span>
                <div v-else class="refuse-info">
                  <div v-if="scope.row.process">
                    <span v-for="item in scope.row.process.info.depList" :key="item.id">{{ item.name }}</span>
                    <span v-for="item in scope.row.process.info.userList" :key="item.id">{{
                      item.card ? item.card.name : item.name
                    }}</span>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="sort" label="排序" min-width="110" show-overflow-tooltip />

            <el-table-column
              prop="updated_at"
              label="更新时间"
              min-width="110"
              show-overflow-tooltip
            />
            <el-table-column label="状态" width="160" show-overflow-tooltip>
              <template #default="scope">
                <el-switch
                  v-model="scope.row.status"
                  active-text="开启"
                  inactive-text="关闭"
                  :active-value="1"
                  :inactive-value="0"
                  @change="handleStatus(scope.row)"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="200" show-overflow-tooltip>
              <template #default="scope">
                <el-button type="primary" link @click="handleEdit(scope.row)" v-hasPermi="['business:examine:index:edit']">编辑</el-button>

                <el-button
                  type="primary" link
                  @click="handleDelete(scope.row)"
                  v-hasPermi="['business:examine:index:delete']"
                  >删除</el-button
                >
              </template>
            </el-table-column>
          </el-table>

          <div class="page-fixed">
            <el-pagination
              :page-size="where.limit"
              :current-page="where.page"
              :page-sizes="[15, 20, 30]"
              layout="total, sizes,prev, pager, next, jumper"
              :total="total"
              @size-change="handleSizeChange"
              @current-change="pageChange"
            />
          </div>
        </div>
        <div v-else>
          <default-page v-height :index="14" :min-height="520" />
        </div>
      </el-card>
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { roterPre } from '@/settings.ts'
import { entListApi, entDeleteApi, entChangeApi } from '@/api/business.ts'
export default {
  name: 'BusinessExamine',
  components: {
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage'))
  },
  data() {
    return {
      tableData: [],
      where: {
        page: 1,
        limit: 15
      },
      tableHeight1: window.innerHeight - 244 + 'px',
      total: 0,
      loading: false
    }
  },
  mounted() {
    this.getTableData()
  },
  methods: {
    handleCreate() {
      this.$router.push(`${roterPre}/business/examine/create`)
    },
    pageChange(page) {
      this.where.page = page
      this.loading = true
      this.getTableData()
      this.loading = false
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    // 获取表格数据
    async getTableData() {
      const result = await entListApi(this.where)
      this.tableData = result.data.list
      this.total = result.data.count
    },
    // 编辑状态
    async handleStatus(item) {
      await entChangeApi(item.id, { status: item.status })
      this.getTableData()
    },
    // 编辑
    handleEdit(item) {
      this.$router.push({ path: `${roterPre}/business/examine/edit`, query: { id: item.id } })
    },
    // 删除
    handleDelete(item) {
      modalSure('你确定要删除该审批流程吗').then(() => {
        entDeleteApi(item.id)
          .then((res) => {
            if (this.where.page > 1 && this.tableData.length <= 1) {
              this.where.page--
            }
            this.getTableData()
          })
          .catch((error) => {})
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.p20 {
  padding: 20px;
}
:deep(.el-card) {
  transition: 0s;
}
:deep(.el-table th) {
  background-color: #f7fbff;
}
.normal-page {
  transition: 0s;
}
.refuse-info {
  span {
    padding-right: 6px;
    &:last-of-type {
      padding-right: 0;
    }
  }
}
.selIcon {
  width: 25px;
  height: 25px;
  line-height: 25px;
  display: inline-block;
  text-align: center;
  border-radius: 3px;
  .iconfont {
    font-size: 13px;
    color: #fff;
  }
}

:deep(.el-card__body) {
  padding: 0;
}
</style>
