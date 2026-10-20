<template>
  <div :class="checkBtn ? 'divBox' : ''">
    <!-- 假期类型表格 -->
    <div v-if="checkBtn">
      <el-card class="normal-page p20">
        <div>
          <div class="flex-between">
            <div class="title-16">假期类型</div>
            <el-button type="primary" icon="el-icon-plus" @click="addType()">新增</el-button>
          </div>
          <div class="total-16 mt20">共有 {{ total }} 条</div>
          <div class="mt14" v-if="tableData.length > 0">
            <el-table :data="tableData" :height="TABLE_HEIGHT">
              <el-table-column prop="name" label="假期类型" min-width="120">
                <template #default="scope">
                  <div class="flex">
                    <div class="ml10">{{ scope.row.name }}</div>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="refuse" label="请假单位" min-width="100">
                <template #default="scope">
                  <span>{{ scope.row.duration_type == 1 ? '按小时' : '按天' }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="sort" label="排序"></el-table-column>
              <el-table-column label="操作" width="200">
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
                layout="total,sizes, prev, pager, next, jumper"
                :total="total"
                @size-change="handleSizeChange"
                @current-change="pageChange"
              />
            </div>
          </div>
          <div v-else>
            <default-page v-height :index="14" :min-height="520" />
          </div>
        </div>
      </el-card>
    </div>

    <!-- 假期类型配置 -->
    <BaseContainer v-if="!checkBtn" class="holiday-form none-padding">
      <!-- 顶部：返回 + 标题 -->
      <FormPageHeader :title="id > 0 ? '编辑假期类型' : '添加假期类型'" @back="back" />

      <div class="holiday-form__body">
        <div class="setting-container">
          <el-row>
            <el-col v-bind="grid1">&nbsp;</el-col>
            <el-col v-bind="grid2">
              <el-form ref="elForm" :model="form" :rules="rules" label-width="100px" size="default">
                <div class="card-list">
                  <div class="head">
                    <span class="title">基础信息设置</span>
                  </div>
                  <el-form-item label="假期类型：" prop="name">
                    <el-input
                      style="width: 350px"
                      v-model="form.name"
                      show-word-limit
                      placeholder="请输入假期类型"
                      clearable
                    >
                    </el-input>
                  </el-form-item>
                  <el-form-item style="margin-bottom: 0" label="新员工请假：" prop="new_employee_limit">
                    <el-select v-model="form.new_employee_limit" style="width: 350px">
                      <el-option label="限制" :value="1"></el-option>
                      <el-option label="不限制" :value="0"></el-option>
                    </el-select>
                  </el-form-item>
                  <div class="infos">
                    <span v-if="form.new_employee_limit == 0" class="info">入职即可请假</span>
                    <div v-else class="info">
                      入职时长小于
                      <el-select v-model="form.new_employee_limit_month" style="width: 100px">
                        <el-option v-for="month in months" :key="month" :label="month" :value="month"></el-option>
                      </el-select>
                      个月时，不可申请此假期
                    </div>
                  </div>
                </div>

                <div class="card-list mt20">
                  <div class="head">
                    <span class="title">请假时长核算规则</span>
                  </div>
                  <el-form-item label="请假时长：" prop="duration_type">
                    <el-select v-model="form.duration_type" style="width: 350px">
                      <el-option label="按天" :value="0"></el-option>
                      <el-option label="按小时" :value="1"></el-option>
                    </el-select>
                    <div class="infos">
                      <span v-if="form.duration_type == 1" class="info">审批申请按分钟请假，打卡月报按小时统计</span>
                      <span v-else class="info">审批申请按半天请假，打卡月报按天统计</span>
                    </div>
                  </el-form-item>

                  <el-form-item label="排序：" prop="sort">
                    <el-input-number
                      v-model="form.sort"
                      :precision="0"
                      :min="0"
                      controls-position="right"
                      style="width: 350px"
                    ></el-input-number>
                  </el-form-item>
                </div>
              </el-form>
            </el-col>
          </el-row>
        </div>
      </div>

      <!-- 底部按钮 -->
      <FormPageFooter>
        <el-button @click="checkBtn = true">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </FormPageFooter>
    </BaseContainer>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import {
  saveHolidayTypeApi,
  approveHolidayTypeApi,
  putHolidayTypeApi,
  approveHolidayTypeInfoApi,
  holidayTypeDeleteApi
} from '@/api/business.ts'
import BaseContainer from '@/components/BaseContainer/index.vue'
import FormPageHeader from '@/components/FormPageHeader/index.vue'
import FormPageFooter from '@/components/FormPageFooter/index.vue'
export default {
  name: 'HolidayType',
  components: {
    BaseContainer,
    FormPageHeader,
    FormPageFooter,
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage'))
  },
  data() {
    return {
      TABLE_HEIGHT,
      checkBtn: true,
      tableData: [],
      where: {
        page: 1,
        limit: 15
      },
      months: [],
      rules: {
        name: [{ required: true, message: '请输入假期类型' }]
      },
      form: {
        name: '',
        new_employee_limit: 0,
        new_employee_limit_month: 1,
        duration_type: 1,
        sort: 0
        // duration_calc_type: 1
      },
      total: 0,
      repeatData: {},
      key: 'cate',
      tabArray: [
        { label: '表单配置', value: 'formSetting', number: 2 },
        { label: '流程配置', value: 'processSetting', number: 3 },
        { label: '规则配置', value: 'ruleSetting', number: 4 }
      ],
      id: 0,
      grid1: {
        xl: 2,
        lg: 4,
        md: 2,
        sm: 24,
        xs: 24
      },
      grid2: {
        xl: 20,
        lg: 18,
        md: 20,
        sm: 24,
        xs: 24
      },
      conditions: ['inputNumber', 'radio', 'checkbox', 'select', 'departmentTree', 'timeFrom', 'moneyFrom'],
      loading: false
    }
  },
  created() {
    this.getTableData()
    for (let i = 1; i <= 12; i++) {
      this.months.push(i)
    }
  },
  methods: {
    goBack() {
      this.getTableData()
      this.checkBtn = !this.checkBtn
    },
    addType() {
      this.form = {
        name: '',
        new_employee_limit: 0,
        new_employee_limit_month: 1,
        duration_type: 1,
        sort: 0
        // duration_calc_type: 1
      }
      this.id = ''
      this.checkBtn = !this.checkBtn
    },
    getCmpData(name) {
      return this.$refs[name].getData()
    },
    // 保存
    save() {
      if (this.id) {
        putHolidayTypeApi(this.id, this.form).then((res) => {
          if (res.status == 200) {
            this.checkBtn = true
            this.getTableData()
          }
        })
      } else {
        saveHolidayTypeApi(this.form).then((res) => {
          if (res.status == 200) {
            this.checkBtn = true
            this.getTableData()
          }
        })
      }
    },
    // 返回
    back() {
      this.checkBtn = true
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
    // 获取假期类型列表
    async getTableData() {
      const data = {
        page: this.where.page,
        limit: this.where.limit
      }
      const result = await approveHolidayTypeApi(data)
      this.tableData = result.data.list
      this.total = result.data.count
    },
    // 编辑
    async handleEdit(item) {
      this.id = item.id
      approveHolidayTypeInfoApi(item.id).then((res) => {
        this.checkBtn = !this.checkBtn
        this.form = res.data
      })
    },
    // 删除
    handleDelete(item) {
      modalSure('你确定要删除该假期类型吗').then(() => {
        holidayTypeDeleteApi(item.id)
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
@media screen and (max-width: 995px) {
  .btn {
    float: left !important;
    margin-bottom: 20px;
  }
}
.holiday-form__body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 20px;
}

.p20 {
  padding: 20px;
}
:deep(.el-card) {
  transition: 0s;
}
:deep(.from-foot-btn) {
  // width: calc(100% - 29px);
  width: 100%;
}
:deep(.el-tabs__header) {
  margin: 0;
}

:deep(.el-table th) {
  background-color: #f7fbff;
}
.examineTabs {
  :deep(.el-tabs__nav-wrap::after) {
    background-color: #fff;
  }
  .sp1 {
    width: 16px;
    height: 16px;
    border: 1px solid #999999;
    border-radius: 50%;
    display: inline-block;
    text-align: center;
    line-height: 14px;
    font-size: 12px;
  }
  :deep(.el-tabs__nav-scroll) {
    display: flex;
    justify-content: left;
  }
  :deep(.el-tabs__item) {
    color: #999999;
    font-weight: 600;
    &.is-active {
      color: #1890ff;
      .sp1 {
        border-color: #1890ff;
        background-color: #1890ff;
        color: #fff;
      }
    }
  }
}
.card-box {
  height: calc(100vh - 100px);
  // overflow-y: scroll;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
  .main {
    width: 800px;
    margin: 0 auto;
  }
}
.examineCont {
  min-width: 798px;
}
.normal-page {
  transition: 0s;
}
.examineCard {
  padding-left: 20px;
  height: 70px;
  border-bottom: 1px solid #dcdfe6;
  font-family: PingFang SC, PingFang SC;
  font-weight: 500;
  font-size: 17px;
  color: #303133;
  line-height: 70px;
  .el-icon-arrow-left {
    cursor: pointer;
    margin-right: 10px;
  }
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
  cursor: pointer;
  border-radius: 3px;
}
.iconfont {
  font-size: 13px;
  color: #fff;
}
:deep(.el-card__body) {
  padding: 0;
}
:deep(.el-form-item) {
  margin: 0;
}
.divBox {
  position: relative;
  height: 100%;
}
.setting-container {
  padding-bottom: 20px;
  .head {
    display: flex;
    margin-bottom: 20px;

    .title {
      padding-left: 6px;
      font-size: 14px;
      font-weight: 600;
      position: relative;
    }
    .title:before {
      content: '';
      background-color: #1890ff;
      width: 3px;
      height: 14px;
      position: absolute;
      left: -6px;
      top: 50%;
      margin-top: -7px;
    }
    .line {
      color: #1890ff;
    }
  }
}
.card-list {
  padding-left: 68px;
  // border-spacing: 2px;
}
.dash-line {
  width: 100%;
  height: 1px;
  background-image: linear-gradient(to right, #dcdfe6 0%, #dcdfe6 50%, transparent 50%);
  background-size: 12px 0.5px; //第一个值（20px）越大线条越长间隙越大
  background-repeat: repeat-x;
  margin-top: 20px;
}
.info {
  font-size: 12px;
  color: #909399;
  margin-left: 8px;
}

:deep(.el-form-item) {
  // display: flex;
  // align-items: center;
  // justify-content: flex-start;
  margin-bottom: 20px;
}
:deep(.el-form-item__content) {
  margin-left: 0 !important;
}
.infos {
  height: 14px;
  line-height: 14px;
  margin-left: 100px;
  margin-top: 14px;
}
</style>
