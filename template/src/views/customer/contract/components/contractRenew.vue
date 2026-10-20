<!-- 订单-订单续费页面组件 -->
<template>
  <div class="station">
    <div class="mb14">
      <el-button @click="handleContract(2, 1)" type="primary">添加续费</el-button>
    </div>
    <div class="renewal-content" v-if="renewCensusData.length > 0">
      <el-row>
        <el-col :span="20" class="over-text">
          <p v-for="item in renewCensusData" :key="item.cate_id">
            <span>{{ item.renew ? item.renew.title : '' }}</span>
            <span v-if="item.end_date !== '0000-00-00 00:00:00'" :class="getRenewDate(item.date)">{{
              dayjs(item.end_date).format('YYYY-MM-DD')
            }}</span>
            <span v-else>--</span>
            到期
          </p>
        </el-col>
        <el-col :span="4" class="text-right">
          <el-button type="primary" link @click="handleRenewCensus"
            >查看全部 <i class="el-icon-d-arrow-right"></i
          ></el-button>
        </el-col>
      </el-row>
    </div>
    <el-table :data="renewData" style="width: 100%; height: 90%">
      <el-table-column prop="end_date" label="续费结束日期" min-width="100">
        <template #default="scope">
          <span v-if="scope.row.end_date !== '0000-00-00 00:00:00'">{{
            dayjs(scope.row.end_date).format('YYYY-MM-DD')
          }}</span>
          <span v-else>--</span>
        </template>
      </el-table-column>
      <el-table-column prop="num" label="续费金额(元)" min-width="80"> </el-table-column>
      <el-table-column prop="renew.title" label="续费类型" min-width="110"> </el-table-column>
      <el-table-column prop="status" label="审核状态" min-width="80">
        <template #default="scope">
          <el-tag size="small" v-if="scope.row.status === 0" type="warning"> 待审核 </el-tag>
          <el-tag size="small" v-if="scope.row.status === 1" type="info"> 已通过 </el-tag>
          <el-tag size="small" v-if="scope.row.status == -1" type="info"> 撤回</el-tag>
          <el-popover v-if="scope.row.status === 2" trigger="hover" placement="top">
            <p>原因:</p>
            <p>{{ scope.row.fail_msg }}</p>
            <template #reference>
              <div>
                <el-tag size="small" type="danger"> 未通过 </el-tag>
              </div>
            </template>
          </el-popover>
        </template>
      </el-table-column>
      <el-table-column prop="card.name" label="创建人" min-width="80"> </el-table-column>
      <el-table-column prop="address" min-width="160" label="操作">
        <template #default="scope">
          <el-button v-if="scope.row.status !== 1" type="primary" link @click="handleContract(2, 2, scope.row)">
            {{ scope.row.status == 0 ? '编辑' : '重新提交' }}
          </el-button>
          <el-button @click="setRemarks(scope.row)" type="primary" link>备注</el-button>
          <el-button v-if="scope.row.status == 0" @click="handleDelete(scope.row)" type="primary" link>撤回</el-button>
        </template>
      </el-table-column>
    </el-table>
    <div class="block mt10 text-right" v-if="renewData.length > 0">
      <el-pagination
        :page-size="where.limit"
        :current-page="renewPage"
        layout="total, prev, pager, next, jumper"
        :total="renewTotal"
        @current-change="renewChange"
      />
    </div>
    <contract-dialog ref="contractDialog" :config="configContract" @isOk="isOk"></contract-dialog>
    <mark-dialog ref="markDialog" :config="configMark" @isMark="isOk"></mark-dialog>
    <renew-census ref="renewCensus" :config="configRenewCensus"></renew-census>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import { clientBillListApi } from '@/api/enterprise.ts'
import { clientBillPutApi } from '@/api/client.ts'
export default {
  name: 'ContractRecord',
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    contractDialog: defineAsyncComponent(() => import('@/views/customer/contract/components/contractDialog')),
    markDialog: defineAsyncComponent(() => import('@/views/customer/contract/components/markDialog')),
    renewCensus: defineAsyncComponent(() => import('@/views/customer/contract/components/renewCensus'))
  },
  computed: {
    dayjs: () => dayjs
  },
  data() {
    return {
      handleData: {},
      renewData: [],
      renewCensusData: [],
      renewTotal: 0,
      configContract: {},
      configRenewCensus: {},
      renewPage: 1,
      where: {
        page: 1,
        limit: 15,
        types: 1
      },
      mark: '',
      configMark: {},
      renewMaxDate: 30
    }
  },
  watch: {
    formInfo: {
      handler(nVal) {
        nVal.isClient === false ? (this.where.cid = nVal.data.id) : (this.where.eid = nVal.data.id)
      },
      immediate: true,
      deep: true
    }
  },
  methods: {
    getTableData() {
      clientBillListApi(this.where).then((res) => {
        this.renewData = res.data.list
        this.renewCensusData = res.data.renew_census
        this.renewTotal = res.data.count
      })
    },
    renewChange(val) {
      this.where.page = val
      this.where.types = 1
      this.getTableData()
    },
    handleContract(type, edit, row = []) {
      let str = ''
      if (type === 1 && edit === 1) {
        str = '添加回款'
      } else if (type === 1 && edit === 2) {
        str = '编辑回款'
      } else if (type === 2 && edit === 1) {
        str = '添加续费'
      } else if (type === 2 && edit === 2) {
        str = '编辑续费'
      }

      this.configContract = {
        title: str,
        width: '480px',
        edit,
        data: row,
        cid: this.formInfo.data.id,
        eid: this.formInfo.data.eid,
        type
      }
      this.$refs.contractDialog.handleOpen()
    },
    isOk() {
      this.where.types = 1
      this.getTableData()
    },
    setRemarks(row) {
      this.configMark = {
        title: '备注信息',
        width: '480px',
        id: row.id,
        type: 1,
        mark: row.mark
      }
      this.$refs.markDialog.handleOpen()
    },
    handleRenewCensus() {
      this.configRenewCensus = {
        title: '订单续费',
        width: '480px',
        data: this.renewCensusData
      }
      this.$refs.renewCensus.handleOpen()
    },
    getRenewDate(data) {
      let str = ''
      let days = dayjs(data).diff(dayjs(new Date()), 'days')
      if (days > this.renewMaxDate) {
        str = 'color-success'
      } else if (days >= 0 && days <= this.renewMaxDate) {
        str = 'color-warning'
      } else {
        str = 'color-danger'
      }
      return str
    },
    // 删除
    handleDelete(item) {
      modalSure('请确定要撤回该记录吗').then(() => {
        clientBillPutApi(item.id).then((res) => {
          if (this.where.page > 1 && this.renewData.length <= 1) {
            this.where.page--
            this.where.types = 1
          }

          this.getTableData()
        })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.station {
  height: 100%;
}
:deep(.el-table) {
  height: 100% !important;
}
:deep(.el-input__inner) {
  text-align: left;
}
.renewal-content {
  width: 100%;
  margin-bottom: 10px;
  p {
    margin: 12px 20px 0 0;
    padding: 0;
    font-size: 13px;
    display: inline-block;
    &:last-of-type {
      margin-right: 0;
    }
  }
}
.from-item-title {
  margin-top: 8px;
  border-left: 5px solid #1890ff;
  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}
</style>
