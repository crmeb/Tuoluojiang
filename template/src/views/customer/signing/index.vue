<template>
    <div class="divBox">

        <el-card :body-style="{ padding: '20px 20px 20px 20px' }" class="normal-page el-card-flex">
            <oaFromBox :title="$route.meta.title" btnText="添加合同" :isAddBtn="false" :treeData="treeDataGroup" :treeDefault="treeDefault"
                :search="search" :isViewSearch="false" :total="total" ref="fromBox" @addDataFn="addContract"
                @confirmData="confirmData" @treeChange="treeChange"></oaFromBox>
            <div class="flex-layout-table">
                <div class="mt10 table-box">
                    <div class="table-wrapper">
                        <div class="table-content">
                            <el-table :data="tableData" height="100%" style="width: 100%" v-loading="loading">
                                <el-table-column prop="doc_name" label="合同名称" width="180" fixed="left">
                                    <template #default="scope">
                                        <span class="point" @click="handleClick(1, scope.row)">
                                            {{ scope.row.doc_name || '--' }}
                                        </span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="doc_no" label="合同编号" width="180">
                                    <template #default="scope">
                                        {{ scope.row.doc_no || '--' }}
                                    </template>
                                </el-table-column>
                                <el-table-column prop="contract_no" label="经办人" width="130">
                                    <template #default="scope">
                                        <template v-if="scope.row.signatory && scope.row.signatory.length > 0">
                                            <div v-for="(value, index) in filterSignUser(scope.row.signatory, [0])" :key="index"
                                                class="flex lh-center">
                                                <span>{{ value.name }}</span>
                                                <span class="sign-status" :style="getStatusStyle(value.sign_status)">
                                                    [{{ signStatusList[value.sign_status].name }}]
                                                </span>
                                            </div>
                                        </template>
                                        <template v-else>
                                            --
                                        </template>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="signing_time" label="其他签署方" min-width="150">
                                    <template #default="scope">
                                        <template v-if="scope.row.signatory && scope.row.signatory.length > 0">
                                            <div v-for="(value, index) in filterSignUser(scope.row.signatory, [1, 2])" :key="index"
                                                class="flex lh-center" :class="{ mt10: index > 0 }">
                                                <span>{{ value.name || value.company_name }}</span>
                                                <span class="sign-status" :style="getStatusStyle(value.sign_status)">
                                                    [{{ signStatusList[value.sign_status].name }}]
                                                </span>
                                            </div>
                                        </template>
                                        <template v-else>
                                            --
                                        </template>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="signing_time" label="签约方式" width="130">
                                    <template #default="scope">
                                        {{ scope.row.sign_type == 1 ? '线下签约' : '电子签' }}
                                    </template>
                                </el-table-column>
                                <el-table-column prop="customer.customer_name" label="客户名称" width="130">
                                    <template #default="scope">
                                        <span class="point" @click="handleClickCustomer(scope.row)">
                                            {{ scope.row.customer?.customer_name || '--' }}
                                        </span>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="end_date" label="开始日期" width="180">
                                    <template #default="scope">
                                        {{ scope.row.start_date || '--' }}
                                    </template>
                                </el-table-column>
                                <el-table-column prop="end_date" label="结束日期" width="180">
                                    <template #default="scope">
                                        {{ scope.row.end_date || '--' }}
                                    </template>
                                </el-table-column>
                                <el-table-column prop="fail_status" label="到期状态" width="180">
                                    <template #default="scope">
                                        <div v-if="failStatus[scope.row.fail_status]" :style="{
                                            color: failStatus[scope.row.fail_status].color || '#1890ff',


                                        }">{{ failStatus[scope.row.fail_status].name }}</div>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="fail_days" label="到期时长" width="180">
                                    <template #default="scope">
                                        <span v-if="scope.row.fail_days">{{ scope.row.fail_days }}天 </span>
                                        <span v-else>--</span>

                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="签约状态" width="180">
                                    <template #default="scope">
                                        <div v-if="statusList[scope.row.status]" class="dictionaries-tag" :style="{
                                            color: statusList[scope.row.status].color || '#1890ff',
                                            background:
                                                getColorFn(statusList[scope.row.status].color, '0.1')

                                        }">{{ statusList[scope.row.status].name }}</div>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="sign_date" label="签约时间" width="180">
                                    <template #default="scope">
                                        {{ scope.row.sign_date || '--' }}
                                    </template>

                                </el-table-column>
                                <el-table-column label="操作" width="210" fixed="right">
                                    <template #default="scope">

                                        <el-button type="text" size="mini" @click="handleClick(1, scope.row)">查看</el-button>
                                        <template v-if="userId == scope.row.admin.id">


                                            <!-- 审核中 -->
                                            <template v-if="scope.row.status == 1">
                                                <el-button type="text" size="mini" @click="handleClick(4, scope.row)">撤销申请</el-button>
                                                <el-button type="text" size="mini" @click="handleClick(6, scope.row)">关联订单</el-button>
                                            </template>

                                            <!-- 待签约 -->
                                            <template v-if="scope.row.status == 2">
                                                <el-button v-if="scope.row.sign_type == 1" type="text" size="mini"
                                                    @click="handleClick(7, scope.row)">签约录入</el-button>
                                                <el-button v-else type="text" size="mini"
                                                    @click="handleClick(2, scope.row)">电子签</el-button>
                                            </template>

                                            <!-- 已签约 -->
                                            <template v-if="scope.row.status == 3">
                                                <el-button type="text" size="mini" @click="handleClick(6, scope.row)">关联订单</el-button>
                                                <el-button type="text" size="mini" @click="handleClick(9, scope.row)">删除</el-button>
                                            </template>
                                            <el-button v-if="scope.row.status >= 4" type="text" size="mini"
                                                @click="handleClick(3, scope.row)">重新签约</el-button>
                                            <el-dropdown v-if="scope.row.status != 1 && scope.row.status != 3">
                                                <span class="el-dropdown-link el-button--text el-button more ml10">
                                                    更多
                                                    <i class="el-icon-arrow-down" />
                                                </span>
                                                <el-dropdown-menu class="dropdown-menu-left" placement="top-start">
                                                    <!-- 待签约 -->
                                                    <el-dropdown-item
                                                        v-if="scope.row.status == 2 || scope.row.status == 6 || scope.row.status == 4"
                                                        @click.native="handleClick(8, scope.row)">
                                                        签约变更
                                                    </el-dropdown-item>
                                                    <el-dropdown-item v-if="scope.row.status != 6"
                                                        @click.native="handleClick(6, scope.row)">
                                                        关联订单
                                                    </el-dropdown-item>
                                                    <el-dropdown-item v-if="![6, 4, -1].includes(scope.row.status)"
                                                        @click.native="handleClick(4, scope.row)">
                                                        撤销签约
                                                    </el-dropdown-item>

                                                    <el-dropdown-item @click.native="handleClick(9, scope.row)">
                                                        删除
                                                    </el-dropdown-item>

                                                </el-dropdown-menu>

                                            </el-dropdown>
                                        </template>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </div>
                    </div>
                    <div class="page-fixed">
                        <el-pagination :current-page="where.page" :page-size="where.limit" :page-sizes="[15, 20, 30]"
                            :total="total" layout="total, sizes,prev, pager, next, jumper" @size-change="handleSizeChange"
                            @current-change="pageChange" />

                    </div>
                </div>
            </div>

        </el-card>
        <!-- 添加合同 -->
        <addContractSign ref="addContractSign" @getTableData="getTableData"></addContractSign>
        <!-- 查看合同 -->
        <checkContract ref="checkContract"></checkContract>
        <!-- 电子签 -->
        <eSignatureDialog ref="eSignatureDialog"></eSignatureDialog>
        <!-- 关联订单 -->
        <paymentTableDialog ref="paymentTableDialog" @getTableData="getTableData"></paymentTableDialog>
        <!-- 客户详情 -->
        <edit-customer ref="editCustomer" :form-data="fromCustomerData" @isOkEdit="getTableData"></edit-customer>
        <!-- 签约录入 -->
        <oa-dialog ref="dialogForm" :fromData="fromData" @submit="submit">
            <div class="file-box">
                <span class="box-label">上传签署后文件：</span>
                <upload-file class="clip-box" :maxLength="3" @input="handleUploadFile" />
            </div>

        </oa-dialog>
    </div>

</template>
<script>
import { modalSure } from '@/libs/public'
import { getStorageJson } from '@/utils/storage'
import { getColor } from '@/utils/format'
import { getContractDocListApi, contractDocDelApi, contractDocCancelApi, contractSignatoryApi, contractDocSignApi } from '@/api/contractSign'
export default {
    name: 'contractSigning',
    components: {
        oaFromBox: () => import('@/components/common/oaFromBox'),
        addContractSign: () => import('./components/addContractSign'),
        checkContract: () => import('./components/checkContract'),
        eSignatureDialog: () => import('./components/eSignatureDialog'),
        paymentTableDialog: () => import('./components/paymentTableDialog'),
        oaDialog: () => import('@/components/form-common/dialog-form'),
        uploadFile: () => import('@/components/form-common/oa-upload'),
        editCustomer: () => import('@/views/customer/list/components/editCustomer'),
    },
    data() {
        return {
            loading: false,
            userId: getStorageJson('userInfo', {}).id,
            fromData: {
                width: '500px',
                title: '签约录入',
                btnText: '确定',
                labelWidth: '100px',
                type: 'slot'
            },
            tableData: [],
            fromCustomerData: {},
            signStatusList: {
                '1': {
                    name: '已签约',
                    color: '#409EFF',
                },
                '0': {
                    name: '待签约',
                    color: '#19BE6B',
                },
                '2': {
                    name: '已拒绝',
                    color: '#ED4014',
                },
            },
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
                    color: '#19BE6B',
                },
                '3': {
                    name: '已签约',
                    color: '#409EFF',
                },
                '4': {
                    name: '已拒绝',
                    color: '#ED4014',
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
            failStatus: {
                '0': {
                    name: '进行中',
                    color: '#19BE6B',
                },
                '1': {
                    name: '未开始',
                    color: '#1890FF',
                },
                '2': {
                    name: '已过期',
                    color: '#FFC107',
                },
            },

            statusListExpire: {
                '0': {
                    name: '未开始',
                    color: '#ED4014',
                },
                '1': {
                    name: '进行中',
                    color: '#909399',
                },
                '2': {
                    name: '已到期',
                    color: '#67C23A',
                },
            },
            file: null,
            // 与 treeDataGroup 中 default:true 的选项保持一致
            treeDefault: 2,
            total: 0,
            where: {
                page: 1,
                limit: 15,
                view_search: 2,
            },
            rowData: {},
            // dropdownList: [{ label: '批量删除', value: 1 }],
            search: [
                {
                    form_value: 'input',
                    field_name: '合同/产品/客户名称',
                    field_name_en: 'name',
                    width: '212px'
                },
                {
                    form_value: 'date_picker',
                    field_name: '签约时间',
                    field_name_end: '签约时间',
                    field_name_en: 'time',
                    data_dict: []
                },
                {
                    form_value: 'select',
                    field_name: '到期状态',
                    field: 'repeat',
                    field_name_en: 'fail_status',
                    data_dict: [{
                        label: '未开始',
                        value: '1',
                    }, {
                        label: '进行中',
                        value: '0',
                    }, {
                        label: '已过期',
                        value: '2',
                    }
                    ]
                },
                {
                    form_value: 'select',
                    field_name: '签约状态',
                    field_name_en: 'status',
                    field: 'repeat',
                    data_dict: [{
                        label: '审批驳回',
                        value: '-1',
                    },
                    {
                        label: '待审核',
                        value: '1',
                    }, {
                        label: '待签约',
                        value: '2',
                    }, {
                        label: '已签约',
                        value: '3',
                    }, {
                        label: '已拒绝',
                        value: '4',
                    }, {
                        label: '已过期',
                        value: '5',
                    }, {
                        label: '已撤销',
                        value: '6',
                    }
                    ],
                },

            ],
            treeDataGroup: [
                {
                    options: [
                        {
                            value: 1,
                            label: '我负责的'
                        },
                        {
                            value: 2,
                            label: '下属负责的',
                            default: true
                        }],
                }
            ]
        }
    },
    mounted() {
        this.getTableData()
    },
    methods: {
        // 筛选指定类型签约人
        filterSignUser(list, types) {
            return list.filter(item => types.includes(item.types))
        },
        // 生成签约状态样式
        getStatusStyle(status) {
            return {
                color: this.signStatusList[status].color || '#1890ff'
            };
        },
        checkContract() {

        },
        getColorFn(color, opacity) {
            return getColor(color, opacity)
        },

        treeChange(val) {
            this.where.view_search = val.value
            this.getTableData()
        },

        confirmData(data, resetState) {
            if (data == 'reset') {
                this.where = {
                    page: 1,
                    limit: 15,
                    view_search: this.where.view_search,
                    // 排序、视图等由搜索栏维护的默认参数，重置后需一并带回，
                    // 避免界面显示与请求参数不一致
                    ...resetState
                }
                this.getTableData()
            } else {
                this.where = {
                    page: 1,
                    limit: 15,
                    view_search: this.where.view_search
                }

                for (let key in data) {
                    this.where[key] = data[key] || ''
                }

                setTimeout(() => {
                    this.getTableData()
                }, 100)
            }

        },

        handleUploadFile(data) {
            if (data.length) {
                this.file = data[0]
            } else {
                this.file = null
            }
        },
        // 查看客户详情
        handleClickCustomer(row) {
            let item = row.customer
            item.eid = row.customer.id
            item.cid = 0
            this.fromCustomerData = {
                title: this.$t('customer.editcustomer'),
                width: '1100px',
                data: item,
                link_type: 'customer',
                types: 'customer',
                type: 'add'
            }

            this.$refs.editCustomer.tabIndex = '1'
            this.$refs.editCustomer.tabNumber = 1
            this.$refs.editCustomer.openBox(item.id, 'customer')
        },

        // 添加合同
        addContract() {
            this.$refs.addContractSign.openBox()
        },

        // 撤销申请
        cancelApply(row) {
            modalSure('您确定要撤销此合同申请吗').then(() => {
                contractDocCancelApi(row.id).then((res) => {
                    if (this.where.page > 1 && this.tableData.length <= 1) {
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
                    if (this.where.page > 1 && this.tableData.length <= 1) {
                        this.where.page--
                    }
                    this.getTableData()
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
                3: () => this.$refs.addContractSign.openBox(row, 'add', row.eid),   // 重新签约 - 新增
                4: () => this.cancelApply(row),     // 撤销申请
                6: () => this.$refs.paymentTableDialog.openBox(row),    // 关联订单
                7: () => this.$refs.dialogForm.openBox(),    // 签约录入
                8: () => this.$refs.addContractSign.openBox(row, 'edit', row.eid),  // 签约变更-编辑
                9: () => this.deleteContract(row),    // 删除
            };
            actionMap[type]?.();
        },
        getTableData() {
            this.loading = true
            getContractDocListApi(this.where).then((res) => {

                this.tableData = res.data.list
                this.total = res.data.count
                this.loading = false

            })
        },
        handleSizeChange(val) {
            this.where.limit = val
            this.getTableData()
        },
        pageChange(val) {
            this.where.page = val
            this.getTableData()
        },
    }

}
</script>
<style>
.clip-box {
    flex: 1;
    overflow: hidden;
}
</style>
<style lang="scss" scoped>
.file-box {
    display: flex;

    .box-label {
        min-width: 8em;
        padding-top: 7px;
    }
}

.point {
    cursor: pointer;
    color: #1890ff;
}

.status {
    display: flex;
    position: absolute;
    top: 12px;
    right: 12px;
    height: 19px;
    line-height: 19px;
    padding: 0 4px;
    justify-content: center;
    align-items: center;

    background: rgba(24, 144, 255, 0.05);
    border-radius: 4px;
    font-size: 11px;
    color: #1890FF;
    border: 0.5px solid #1890FF;

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

.sign-status {
    display: inline-block;
    margin-top: 0 !important;
    color: #1890ff;
    font-size: 12px;
    margin-left: 4px;
    min-width: 4em;
}
</style>