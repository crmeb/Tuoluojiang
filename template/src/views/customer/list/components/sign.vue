<template>
  <div class="station">
    <div class="btn-box1 mb10">
      <div class="title-16">合同列表</div>
      <el-button type="primary" @click="addContract">添加合同</el-button>
    </div>
    <el-table :data="contractData" fit style="width: 100%">
      <el-table-column prop="doc_name" label="合同名称"> </el-table-column>
      <el-table-column prop="doc_no" label="合同编号"> </el-table-column>
      <el-table-column label="所有签署方" min-width="200">
        <template #default="scope">
          <div v-for="item in scope.row.signatory" :key="item.id">
            <span v-if="item.types == 0">{{ item.company_name }}
              <template v-if="item.name">({{ item.name }})</template>
              <span class="company-icon">本企业</span>
            </span>
            <span v-else-if="item.types == 2">{{ item.company_name }}
              <span class="company-icon">企业</span>
            </span>
            <span  v-else>{{ item.name }} <span class="company-icon individual">个人</span></span>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="status" label="签约状态">
        <template #default="scope">
          <div v-if="statusList[scope.row.status]" class="dictionaries-tag" :style="{
            color: statusList[scope.row.status].color || '#1890ff',
            background:
              getColorFn(statusList[scope.row.status].color, '0.1')

          }">{{ statusList[scope.row.status].name }}</div>
        </template>
      </el-table-column>
      <el-table-column label="签约时间">
        <template #default="scope">
          <div>{{ scope.row.sign_date || '--' }}</div>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="230" fixed="right">
        <template #default="scope">
          <el-button type="primary" link @click="handleClick(1, scope.row)">查看</el-button>
          <!-- 审核中 -->
          <template v-if="scope.row.status == 1">
            <el-button type="primary" link @click="handleClick(4, scope.row)">撤销申请</el-button>
            <el-button type="primary" link @click="handleClick(6, scope.row)">关联订单</el-button>
          </template>

          <!-- 待签约 -->
          <template v-if="scope.row.status == 2">
            <el-button v-if="scope.row.sign_type == 1" type="primary" link
              @click="handleClick(7, scope.row)">签约录入</el-button>
            <el-button v-else type="primary" link @click="handleClick(2, scope.row)">电子签</el-button>
          </template>

          <!-- 已签约 -->
          <template v-if="scope.row.status == 3">
            <el-button type="primary" link @click="handleClick(6, scope.row)">关联订单</el-button>
            <el-button type="primary" link @click="handleClick(9, scope.row)">删除</el-button>
          </template>
          <el-button v-if="scope.row.status >= 4" type="primary" link
            @click="handleClick(3, scope.row)">重新签约</el-button>
          <el-dropdown v-if="scope.row.status != 1 && scope.row.status != 3" class="ml10">
            <el-button type="primary" link class="el-dropdown-link more">
              更多
              <i class="el-icon-arrow-down" />
            </el-button>
            <template #dropdown>
              <el-dropdown-menu class="dropdown-menu-left" placement="top-start">
                <!-- 待签约 -->
                <el-dropdown-item v-if="scope.row.status == 2 || scope.row.status == 6"
                  @click="handleClick(8, scope.row)">
                  签约变更
                </el-dropdown-item>
                <el-dropdown-item v-if="scope.row.status != 6" @click="handleClick(6, scope.row)">
                  关联订单
                </el-dropdown-item>
  
                <el-dropdown-item @click="handleClick(9, scope.row)">
                  删除
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>

        </template>
      </el-table-column>
    </el-table>
    <div class="pagination">
      <el-pagination :page-size="where.limit" :current-page="where.page" layout="total, prev, pager, next, jumper"
        :total="contractTotal" @current-change="pageChange" />
    </div>
    <!-- 添加合同 -->
    <addContractSign ref="addContractSign" :eid="formInfo.data.eid" @getTableData="handleSignChange"></addContractSign>
    <!-- 查看合同 -->
    <checkContract ref="checkContract"></checkContract>
    <!-- 电子签 -->
    <eSignatureDialog ref="eSignatureDialog"></eSignatureDialog>
    <!-- 关联订单 -->
    <paymentTableDialog ref="paymentTableDialog" @getTableData="getTableData"></paymentTableDialog>
    <!-- 客户详情 -->
    <!-- <edit-customer ref="editCustomer" :form-data="fromCustomerData" @isOkEdit="getTableData"></edit-customer> -->
    <!-- 签约录入 -->
    <oa-dialog ref="dialogForm" :fromData="fromData" @submit="submit">
      <div class="file-box">
        <span class="box-label">上传签署后文件：</span>
        <upload-file :maxLength="1" @getVal="getVal" />
      </div>

    </oa-dialog>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'

import { getStorageJson } from '@/utils/storage.ts'
import { getColor } from '@/utils/format.ts'
import {
  getContractDocListApi,
  contractDocCancelApi,
  contractDocDelApi,
  contractSignatoryApi,
  contractDocSignApi
} from '@/api/contractSign.ts'
export default {
  emits: ['refresh-detail'],
  name: 'Sign',
  components: {

    checkContract: defineAsyncComponent(() => import('@/views/customer/signing/components/checkContract')),
    addContractSign: defineAsyncComponent(() => import('@/views/customer/signing/components/addContractSign')),
    eSignatureDialog: defineAsyncComponent(() => import('@/views/customer/signing/components/eSignatureDialog')),
    paymentTableDialog: defineAsyncComponent(() => import('@/views/customer/signing/components/paymentTableDialog')),
    oaDialog: defineAsyncComponent(() => import('@/components/form-common/dialog-form')),
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
  },
  props: {
    formInfo: {
      type: Object,
      default: () => {
        return {}
      }
    },
    product: {
      type: Array,
      default: () => {
        return []
      }
    }
  },

  data() {
    return {
      where: {
        eid: 0,
        page: 1,
        limit: 15,

      },
      userId: getStorageJson('userInfo', {}).id,
      contractTotal: 0,
      contractData: [],
      fromData: {
        width: '500px',
        title: '签约录入',
        btnText: '确定',
        labelWidth: '100px',
        type: 'slot'
      },
      fromCustomerData: {},
      contractFromData: {},
      statusList: {
        '-1': {
          name: '审批驳回',
          color: '#ED4014',
        },
        '0': {
          name: '待处理',
          color: '#FFC107',
        },
        '1': {
          name: '待审核',
          color: '#409EFF',
        },
        '2': {
          name: '待签约',
          color: '#67C23A',
        },
        '3': {
          name: '已签约',
          color: '#409EFF',
        },
        '4': {
          name: '已拒绝',
          color: '#909399',
        },
        '5': {
          name: '已过期',
          color: '#909399',
        },
        '6': {
          name: '已撤销',
          color: '#909399',
        },
      },
    }
  },
  computed: {

  },
  mounted() {
    this.getTableData()
  },
  methods: {
    handleSignChange() {
      this.getTableData()
      this.$emit('refresh-detail')
    },

    getVal(data) {
      this.file = data[0]
    },
    getTableData() {
      this.where.eid = this.formInfo.data.eid
      getContractDocListApi(this.where).then((res) => {
        this.contractData = res.data.list
        this.contractTotal = res.data.count
      })
    },

    getColorFn(color, opacity) {
      return getColor(color, opacity)
    },
    // 撤销申请
    cancelApply(row) {
      modalSure('您确定要撤销此合同申请吗').then(() => {
        contractDocCancelApi(row.id).then((res) => {
          if (this.where.page > 1 && this.contractData.length <= 1) {
            this.where.page--
          }
          this.getTableData()
        })
      })
    },
    // 删除
    deleteContract(row) {
      modalSure('您确定要删除此合同吗').then(() => {
        contractDocDelApi(row.id).then((res) => {
          if (this.where.page > 1 && this.contractData.length <= 1) {
            this.where.page--
          }
          this.getTableData()
          this.$emit('refresh-detail')
        })
      })
    },

    submit() {
      if (!this.file) {
        this.$message.error('请上传文件')
        return false
      }
      let obj = {
        file_id: this.file.id,
      }
      contractDocSignApi(this.rowData.id, obj).then((res) => {
        this.$refs.dialogForm.handleClose()
        this.getTableData()

      })
    },
    // 电子签
    openEsignatureDialog(row) {
      contractSignatoryApi(row.id).then(res => {
        this.$refs.eSignatureDialog.openBox(res.data)

      })

    },
    handleClick(type, row) {
      this.rowData = row
      const actionMap = {
        1: () => this.$refs.checkContract.openBox(row),      // 查看
        2: () => this.openEsignatureDialog(row),  // 电子签
        3: () => this.$refs.addContractSign.openBox(row, 'add',row.eid),   // 重新签约 - 新增
        4: () => this.cancelApply(row),     // 撤销申请
        6: () => this.$refs.paymentTableDialog.openBox(row),    // 关联订单
        7: () => this.$refs.dialogForm.openBox(),    // 签约录入
        8: () => this.$refs.addContractSign.openBox(row, 'edit',row.eid),  // 签约变更-编辑
        9: () => this.deleteContract(row),    // 删除
      };
      actionMap[type]?.();
    },

    // 添加订单
    addContract() {
        this.$refs.addContractSign.openBox('','add',this.formInfo.data.eid)
     

    },

    async handleCheck(item) {
      // 解构赋值获取id，避免直接修改传入的item对象
      const { id } = item
      this.fromData = {
        title: '查看客户',
        width: '1000px',
        data: { ...item, cid: id }, // 使用展开运算符创建新对象并添加cid属性
        isClient: false,
        edit: true
      }

      // 等待DOM更新
      await this.$nextTick()
      // 可以考虑封装成一个方法，避免重复设置属性
      const editContractRef = this.$refs.editContract
      editContractRef.tabIndex = '1'
      editContractRef.tabNumber = 1
      editContractRef.openBox(this.fromData.data) // 传递新对象
    },

    pageChange(val) {
      this.where.page = val
      this.getTableData()
    }
  }
}
</script>

<style lang="scss" scoped>
.btn-box1 {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 32px;
}

.dictionaries-tag {
  display: inline-block;
  margin: 0;
  box-sizing: border-box;
  height: 24px;
  padding: 0 8px;
  text-align: center;
  font-size: 12px;
  margin-top: 8px;
  border-radius: 3px;
}

.company-icon {
  display: inline-block;
  padding: 2px 4px;
  background: rgba(24, 144, 255, 0.08);
  border-radius: 4px;
  font-size: 11px;
  color: #1890FF;
  margin-left: 2px;
}

.individual {
  color: #FF9900;
  font-size: 11px;
  background-color: rgba(255, 153, 0, 0.08);
}

.file-box {
  display: flex;

  .box-label {
    min-width: 8em;
    padding-top: 7px;
  }
}
</style>
