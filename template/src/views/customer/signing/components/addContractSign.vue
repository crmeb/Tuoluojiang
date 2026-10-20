<template>
  <div class="contract-signing-slide">
    <el-drawer
      title="新增合同"
      v-model="drawer"
      direction="rtl"
      size="1071px"
      :append-to-body="true"
      :before-close="handleClose"
    >
      <div class="slide-content" v-loading="infoLoading">
        <!-- 表单内容 -->
        <el-form
          ref="contractForm"
          :model="contractForm"
          :rules="rules"
          label-width="auto"
          class="contract-form"
          label-position="left"
        >
          <!-- 客户信息 -->
          <div class="form-section customer-info">
            <div class="section-title">客户信息</div>
            <el-row :gutter="30">
              <el-col :span="8">
                <el-form-item label="客户名称：">
                  <span class="info-text">{{
                    customerInfo && customerInfo.customer_name ? customerInfo.customer_name : '--'
                  }}</span>
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="联系电话：">
                  <span class="info-text">{{
                    customerInfo && customerInfo.customer_tel ? customerInfo.customer_tel : '--'
                  }}</span>
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="省市区：">
                  <span class="info-text">
                    <span v-if="customerInfo && customerInfo.area_cascade">{{ customerInfo.area_cascade }}</span>
                    <span v-else>--</span>
                  </span>
                </el-form-item>
              </el-col>
            </el-row>
          </div>

          <!-- 步骤条 -->
          <div class="step">
            <div class="step-item" @click="prevStep(1)">
              <span class="public" :class="activeIndex == 1 ? 'active' : ''">1</span>
              <span class="step-text mr30 ml8" :class="activeIndex == 1 ? 'activeText' : ''">填写产品清单</span>
            </div>
            <span class="line-title" />
            <div class="step-item" @click="activeIndex = 2">
              <span class="public ml30" :class="activeIndex == 2 ? 'active' : ''">2</span>
              <span class="step-text ml8" :class="activeIndex == 2 ? 'activeText' : ''">填写签约信息</span>
            </div>
          </div>

          <!-- 产品清单 -->
          <template v-if="activeIndex == 1">
            <div class="form-section" v-if="isShow">
              <h4 class="section-title">关联数据</h4>

              <el-radio-group v-model="contractForm.link_type" @change="radioChange">
                <el-radio label="5" value="5">关联商机，转合同</el-radio>
                <el-radio label="2" value="2">关联订单，补签合同</el-radio>
              </el-radio-group>

              <signProduct
                class="mb20"
                ref="signProduct"
                :list="contractForm.link_type == 5 ? oddsList : contractList"
                :type="contractForm.link_type"
                @selectionChange="handleSelectionChange"
              >
              </signProduct>
            </div>

            <div class="form-section">
              <h4 class="section-title">产品清单</h4>

              <productList
                ref="productList"
                :product="contractForm.productInfo"
                @getProductList="getProductList"
              ></productList>
            </div>
          </template>

          <!-- 签约信息 -->

          <template v-if="activeIndex == 2">
            <div class="form-section">
              <h4 class="section-title">签约信息</h4>
              <el-form-item label="合同名称：" prop="doc_name">
                <el-input
                  v-model="contractForm.doc_name"
                  placeholder="请输入合同名称"
                  style="width: 370px"
                  @change="getProcess"
                ></el-input>
              </el-form-item>
              <!-- 签约方式 -->
              <el-form-item label="签约方式：" prop="sign_type">
                <el-radio-group v-model="contractForm.sign_type" @change="getProcess">
                  <el-radio label="2" value="2" :disabled="webConfig.e_signature == 0">电子签约</el-radio>
                  <el-radio label="1" value="1">线下签约</el-radio>
                </el-radio-group>
              </el-form-item>

              <!-- 合同期限 -->
              <el-form-item label="合同期限：" prop="term_type">
                <el-radio-group v-model="contractForm.term_type" @change="getProcess">
                  <el-radio label="2" value="2">签约日起算</el-radio>
                  <el-radio label="1" value="1">固定期限</el-radio>
                  <el-radio label="0" value="0">无期限</el-radio>
                </el-radio-group>
              </el-form-item>

              <!-- 合同时期 -->
              <el-form-item label="合同时长：" v-if="contractForm.term_type == 2" prop="date_count" class="mb10 mt10">
                <el-input
                  v-model="contractForm.date_count"
                  type="number"
                  min="0"
                  placeholder="请输入"
                  style="width: 370px"
                >
                  <template #append>天</template>
                </el-input>
              </el-form-item>

              <div class="flex" v-if="contractForm.term_type == 1">
                <el-form-item label="开始日期：" prop="start_date">
                  <el-date-picker
                    v-model="contractForm.start_date"
                    type="date"
                    placeholder="选择日期"
                    format="YYYY-MM-DD"
                    value-format="YYYY-MM-DD"
                  >
                  </el-date-picker>
                </el-form-item>
                <el-form-item label="结束日期：" prop="end_date">
                  <el-date-picker
                    v-model="contractForm.end_date"
                    type="date"
                    placeholder="选择日期"
                    format="YYYY-MM-DD"
                    value-format="YYYY-MM-DD"
                  >
                  </el-date-picker>
                </el-form-item>
              </div>

              <!-- 上传签约文件 -->

              <el-form-item class="mb10 mt10 upload-field">
                <template #label>
                  <div><span class="required">*</span>上传签署文件：</div>
                </template>

                <div class="flex lh-32" v-if="fileLoading">
                  <img src="../../../../assets/images/loading-ai.gif" alt="" style="width: 30px; height: 30px" />{{
                    tips
                  }}
                </div>
                <upload-file
                  class="mb20"
                  :model-value="fileList"
                  :isTwoColumnShow="true"
                  :maxLength="maxLength"
                  @getVal="getVal"
                />

                <!-- 签署人 -->
                <draggable
                  tag="div"
                  :list="contractForm.signatory"
                  v-bind="{ group: 'optionsGroup', ghostClass: 'ghost', handle: '.icontuodong' }"
                  @change="emitDefaultValueChange"
                  :item-key="$draggableKey"
                >
                  <!-- 动态校验规则：给每个 signatory 生成独立字段名，利用 el-form 内置校验 -->
                  <template #item="{ element: item, index: idx }">
                    <div>
                      <!-- 本企业 -->
                      <div class="signatory-item" v-if="item.types == '0'">
                        <span class="iconadd left" />
  
                        <div class="right">
                          <!-- 企业信息 -->
                          <div class="mb8">
                            <span class="company-name">{{ webConfig.enterprise_name || '--' }}</span>
                            <el-tag size="small" type="primary">本企业</el-tag>
                          </div>
  
                          <!-- 经办人 & 手机号 -->
                          <el-row :gutter="20" class="mt10">
                            <el-col :span="8">
                              <!-- 经办人必填 -->
                              <el-form-item label-width="70px" label="经办人：">
                                <select-member
                                  only-one
                                  @getSelectList="getSelectList($event, item)"
                                  :selectIdData="[item.user_id]"
                                  style="width: 100%"
                                />
                              </el-form-item>
                            </el-col>
  
                            <el-col :span="8">
                              <!-- 手机号必填 + 格式校验 -->
                              <el-form-item
                                label="手机号："
                                label-width="70px"
                                :prop="`signatory.${idx}.phone`"
                                :rules="[{ required: true, message: '请输入手机号', trigger: 'blur' }]"
                              >
                                <el-input v-model="item.phone" placeholder="请输入" />
                              </el-form-item>
                            </el-col>
                          </el-row>
                        </div>
                      </div>
  
                      <!-- 外部企业 / 个人 -->
                      <div class="signatory-item" v-else>
                        <span class="el-icon-error" v-if="contractForm.signatory.length > 2" @click="handleDelete(idx)" />
                        <span class="iconfont icontuodong iconadd left" title="拖拽排序" />
                        <div class="right">
                          <!-- 签署方类型 -->
                          <div class="mb8">
                            <el-form-item label-width="70px">
                              <template #label>
                                <div><span class="required">*</span>签署方：</div>
                              </template>
                              <el-radio-group v-model="item.types">
                                <el-radio :label="2" :value="2">企业</el-radio>
                                <el-radio :label="1" :value="1">个人</el-radio>
                              </el-radio-group>
                            </el-form-item>
                          </div>
  
                          <el-row :gutter="10">
                            <!-- 企业名称（仅企业类型时必填） -->
                            <el-col :span="8">
                              <el-form-item
                                v-if="item.types == 2"
                                label="企业名称："
                                label-width="86px"
                                :prop="`signatory.${idx}.company_name`"
                                :rules="[{ required: true, message: '请输入企业名称', trigger: 'blur' }]"
                              >
                                <el-input
                                  v-model="item.company_name"
                                  placeholder="请输入"
                                  style="width: 100%"
                                />
                              </el-form-item>
                            </el-col>
  
                            <!-- 经办人 / 姓名（企业类型为经办人，个人类型为姓名） -->
                            <el-col :span="8">
                              <el-form-item
                                label-width="70px"
                                :prop="`signatory.${idx}.name`"
                                label="经办人："
                                :rules="[{ required: true, message: '请输入经办人', trigger: 'blur' }]"
                              >
                                <el-input v-model="item.name" placeholder="请输入" style="width: 100%" />
                              </el-form-item>
                            </el-col>
  
                            <!-- 手机号（必填） -->
                            <el-col :span="8">
                              <el-form-item
                                label-width="70px"
                                :prop="`signatory.${idx}.phone`"
                                label="手机号："
                                :rules="[{ required: true, message: '请输入手机号', trigger: 'blur' }]"
                              >
                                <el-input v-model="item.phone" placeholder="请输入" style="width: 100%" />
                              </el-form-item>
                            </el-col>
                          </el-row>
                        </div>
                      </div>
                    </div>
                  </template>
                </draggable>
                <el-button type="primary" link icon="el-icon-plus" @click="addSignatory">添加签署方</el-button>
              </el-form-item>
            </div>

            <!-- 备注信息 -->
            <div class="form-section">
              <h4 class="section-title">备注</h4>
              <el-form-item label="备注：">
                <el-input
                  v-model="contractForm.mark"
                  type="textarea"
                  resize="none"
                  :rows="3"
                  placeholder="请输入备注信息"
                ></el-input>
              </el-form-item>
            </div>

            <!-- 审批流程 -->
            <div class="form-section mt30">
              <process-from :examine-data="examineData"></process-from>
            </div>
          </template>
        </el-form>
      </div>
      <div class="button from-foot-btn fix btn-shadow" v-if="!infoLoading">
        <el-button @click="handleClose" v-show="activeIndex == 1">取消</el-button>
        <el-button @click="prevStep(1)" v-show="activeIndex == 2">上一步</el-button>
        <el-button type="primary" @click="nextStep" v-show="activeIndex == 1">下一步</el-button>
        <el-button type="primary" :loading="loading" @click="handleSubmit" v-show="activeIndex == 2"
          >提交</el-button
        >
      </div>
    </el-drawer>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { oddsListApi, clientCustomerBaseApi, clientLiaisonListApi, oddsCreateEditApi } from '@/api/client.ts'
import { clientContractListApi } from '@/api/enterprise.ts'
import {
  contractDocSaveApi,
  contractDocPutApi,
  contractDocTaskApi,
  contractProcessApi,
  contractDocDetailApi
} from '@/api/contractSign.ts'
import Draggable from 'vuedraggable'
import { getStorageJson } from '@/utils/storage.ts'
export default {
  emits: ['getTableData', 'isOk'],
  name: 'AddContractSign',
  components: {
    Draggable,
    signProduct: defineAsyncComponent(() => import('./signProduct')),
    productList: defineAsyncComponent(() => import('@/views/customer/components/productList')),
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member')),
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
    paymentTable: defineAsyncComponent(() => import('./paymentTable')),
    processFrom: defineAsyncComponent(() => import('@/views/user/examine/components/process'))
  },

  data() {
    const webConfig = getStorageJson('webConfig', {})
    return {
      drawer: false,
      activeIndex: 1,
      maxLength: 1,
      isShow: true,
      customerInfo: {},
      contractList: [], // 订单列表

      oddsList: [], // 商机列表
      oddWhere: {
        eid: 0,
        page: 1,
        limit: 10
      },
      contractWhere: {
        eid: 0,
        page: 1,
        limit: 10
      },
      eid: 0,

      loading: false,
      webConfig,
      contractForm: {
        eid: '',
        cid: [],
        link_type: '5',
        productInfo: [
          {
            unique: '',
            image: '',
            name: '',
            sku: '',
            price: 0,
            count: 0,
            discount: 100,
            total_price: 0,
            ot_price: 0.0,
            remark: ''
          }
        ],

        doc_name: '',
        sign_type: '2',
        term_type: '2', //
        date_count: '', // 合同时期（天）
        start_date: '',
        end_date: '',
        sign_file: {},
        file_id: '',
        mark: '', // 备注信息
        processInfo: [], // 审批流程节点
        signatory: [
          {
            types: 0,
            user_id: '',
            company_name: webConfig.enterprise_name || '',
            phone: ''
          },
          {
            types: 1,
            user_id: 0,
            name: '',
            company_name: '',
            phone: ''
          }
        ]
      },
      tips: '文件正在处理中',
      fileLoading: false,
      timer: null,
      examineData: {},
      type: '',
      id: '',
      eid: 0,
      initData: {
        unique: '',
        image: '',
        name: '',
        sku: '',
        price: 0,
        count: 0,
        discount: 100,
        total_price: 0,
        ot_price: 0.0,
        remark: ''
      },
      rules: {
        doc_name: [{ required: true, message: '请输入合同名称', trigger: 'blur' }],
        sign_type: [{ required: true, message: '请选择签约方式', trigger: 'blur' }],
        term_type: [{ required: true, message: '请选择合同时期类型', trigger: 'blur' }],
        date_count: [{ required: true, message: '请输入合同时期（天）', trigger: 'blur' }],
        enableQuery: [{ required: true, message: '请选择是否开启查询', trigger: 'change' }],
        start_date: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
        end_date: [{ required: true, message: '请选择结束日期', trigger: 'change' }]
      },
      fileList: [],

      infoLoading: false
    }
  },

  beforeUnmount() {
    if (this.timer) {
      clearInterval(this.timer)
      this.timer = null
    }
  },
  methods: {
    openBox(row, type, eid, data) {
      this.drawer = true

      if (row) {
        this.getInfo(row.id)
        if (type == 'edit') {
          this.id = row.id
        }
      } else {
        this.type = 'add'
      }
      if (eid && !data) {
        this.eid = eid
        this.oddWhere.eid = eid
        this.contractWhere.eid = eid
        this.contractForm.eid = eid
        this.getOddsList()
        this.getCustomerInfo()
      }
      if (this.webConfig.e_signature == 0) {
        this.contractForm.sign_type = '1'
      }

      // 从商机列表+订单列表进入的
      if (data) {
        this.eid = data.eid
        this.oddWhere.eid = data.eid
        this.contractWhere.eid = data.eid
        this.contractForm.eid = data.eid

        this.getCustomerInfo()
        this.contractForm.cid = []
        this.isShow = false
        this.contractForm.link_type = data.link_type + ''
        this.contractForm.cid = [data.id]
        if (data?.product?.length > 0) {
          this.contractForm.productInfo = data.product
        } else {
          this.getOpportunityProduct(data.id);
        }
      }

      if (!row && type == 'add') {
        // 取缓存的合同签约信息赋值
        // let info = JSON.parse(localStorage.getItem('contractSignForm'))
        // if (info) {
        //   this.contractForm = info
        //   this.contractForm.cid = []
        //   this.contractForm.eid = ''
        //   this.contractForm.file_id = ''
        // }
        const userInfo = getStorageJson('userInfo', {})
        this.contractForm.signatory[0].user_id = userInfo.id || 0
        this.contractForm.signatory[0].phone = userInfo.phone || ''
      }
      this.getProcess()
    },

    async getOpportunityProduct(opportunityId) {
      try {
        const result = await oddsCreateEditApi(opportunityId);
        if (result?.data?.product?.length) {
          this.contractForm.productInfo = result.data.product;
        }
      } catch (error) {
      }
    },

    getInfo(id) {
      contractDocDetailApi(id).then((result) => {
        const data = JSON.parse(JSON.stringify(result.data))
        Object.keys(this.contractForm).forEach((key) => {
          this.contractForm[key] = data[key] ?? ''
        })

        this.radioChange()
        this.fileList = []
        this.contractForm.sign_file = []
        this.contractForm.productInfo = data.products || [this.initData]
        this.contractForm.link_type = data.link_type + ''
        this.contractForm.sign_type = data.sign_type + ''
        this.contractForm.term_type = data.term_type + ''

        setTimeout(() => {
          this.setChecked()
        }, 200)
        this.contractForm.file_id = ''
        this.examineData.list = result.data.approve
        this.examineData.rules = result.data.rules || {}
        if (data.cid.length > 0) {
          this.setChecked(data.cid)
        }
      })
    },

    radioChange() {
      if (this.contractForm.link_type == 5 && this.oddsList.length == 0) {
        this.getOddsList()
      }
      if (this.contractForm.link_type == 2 && this.contractList.length == 0) {
        this.getContractList()
      }
      this.contractForm.productInfo = [this.initData]
    },

    handleSelectionChange(val) {
      this.contractForm.productInfo = [this.initData]
      this.contractForm.cid = []
      val.map((item) => {
        this.contractForm.cid.push(item.id)
        this.contractForm.productInfo.push(...item.product)
      })
    },

    getOddsList() {
      oddsListApi(this.oddWhere).then((res) => {
        this.oddsList = res.data.list || []
        this.oddsList = this.oddsList.filter((item) => !item.is_sign)
        if (this.type == 'add' && this.oddsList.length > 0) {
          this.contractForm.productInfo = [this.initData]
          this.contractForm.cid = []
          if (this.oddsList.length > 0) {
            this.oddsList.map((item) => {
              this.contractForm.cid.push(item.id)
              this.contractForm.productInfo.push(...item.product)
            })

            setTimeout(() => {
              this.setChecked(this.contractForm.cid)
            }, 200)
          }
        }
      })
    },

    // 获取订单列表
    getContractList() {
      clientContractListApi(this.contractWhere).then((res) => {
        this.contractList = res.data.list || []
        this.contractList = this.contractList.filter((item) => !item.is_sign)
      })
    },

    getProductList(val) {
      this.contractForm.productInfo = val
    },

    async getCustomerInfo() {
      const [res, liaisonRes] = await Promise.all([
        clientCustomerBaseApi(this.eid),
        this.type == 'add' ? clientLiaisonListApi({ eid: this.eid, page: 1, limit: 15 }).catch(() => null) : null
      ])
      this.customerInfo = res.data || {}
      if (this.type == 'add') {
        // 签署方默认带出客户名称及其关联联系人，均可修改
        const liaisons = liaisonRes?.data?.list || []
        const liaison = liaisons.find((item) => item.liaison_name) || liaisons[0] || {}
        const signatory = this.contractForm.signatory[1]
        signatory.company_name = this.customerInfo.customer_name || ''
        signatory.name = liaison.liaison_name || ''
        signatory.phone = liaison.liaison_tel || this.customerInfo.customer_tel || ''
      }
    },

    prevStep(index) {
      this.activeIndex = index
      if (index == 1) {
        setTimeout(() => {
          this.setChecked()
        }, 200)
      }
    },
    nextStep() {
      this.activeIndex = 2
    },
    setChecked() {
      this.$refs.signProduct.setChecked(this.contractForm.cid)
    },
    // 删除签署方
    handleDelete(idx) {
      this.contractForm.signatory.splice(idx, 1)
    },
    handleClose() {
      // 清除定时器
      if (this.$refs.paymentTable) {
        this.$refs.paymentTable.eid = ''
        this.$refs.paymentTable.table = []
      }

      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
      this.fileLoading = false
      this.tips = ''
      this.drawer = false
      this.id = ''
      this.activeIndex = 1
      // 重置表单内容
      this.$refs.contractForm && this.$refs.contractForm.resetFields()
      this.contractForm = {
        eid: '',
        cid: [],
        link_type: '5',
        productInfo: [this.initData],
        doc_name: '',
        sign_type: '2',
        term_type: '2',
        date_count: '',
        start_date: '',
        end_date: '',
        sign_file: {},
        file_id: '',
        mark: '',
        processInfo: [],
        signatory: [
          {
            types: 0,
            user_id: 0,
            company_name: this.webConfig.enterprise_name || '',
            phone: ''
          },
          {
            types: 1,
            user_id: 0,
            name: '',
            company_name: '',
            phone: ''
          }
        ]
      }
      this.fileList = []
      this.customerInfo = {}
    },
    getVal(data) {
      if (data.length) {
        this.contractForm.sign_file = data[0]
      } else {
        this.contractForm.sign_file = {}
        this.contractForm.file_id = ''
      }
      setTimeout(() => {
        this.getProcess()
      }, 200)
    },
    emitDefaultValueChange() {},
    getSelectList(data, item) {
      item.user_id = data[0].value
    },
    nextStep() {
      this.activeIndex = 2
    },

    // 获取审批流程节点
    getProcess() {
      contractProcessApi(this.contractForm).then((res) => {
        if (res.status != 200) {
          this.$message.error(res.message)
          return false
        }
        this.contractForm.processInfo = res.data.list
        this.examineData = res.data
        if (
          this.contractForm.sign_type == '2' &&
          this.contractForm.sign_file &&
          res.data.file &&
          !res.data.file.file_id
        ) {
          if (!res.data.file.task_id || !this.fileList.length) {
            return false
          }
          this.fileLoading = true
          clearInterval(this.timer)
          this.timer = setInterval(() => {
            contractDocTaskApi(res.data.file.task_id).then((data) => {
              this.tips = data.data.message
              if (data.data.convert_file_id) {
                this.contractForm.file_id = data.data.convert_file_id
                clearInterval(this.timer)
                this.fileLoading = false
              }
            })
          }, 2000)
        }
      })
    },
    handleSubmit() {
      // 提交表单

      if (Object.keys(this.contractForm.sign_file).length === 0) {
        this.$message.error('请上传签署文件')
        return
      }
      if (this.contractForm.sign_type == 2 && !this.contractForm.file_id) {
        this.$message.error('请上传合同文件')
        return
      }
      // 使用 find 提前中断循环，避免无效遍历
      if (this.contractForm.processInfo && this.contractForm.processInfo.length > 0) {
        const missingUserNode = this.contractForm.processInfo.find(
          (item) => item.types === 1 && item.settype === 4 && !item.users.length
        )
        if (missingUserNode) {
          this.$message.error(`请选择${missingUserNode.title}`)
          return
        }
      }
      this.$refs.contractForm.validate((valid) => {
        if (valid) {
          this.loading = true
          localStorage.setItem('contractSignForm', JSON.stringify(this.contractForm))
          this.contractForm.productInfo = this.contractForm.productInfo.filter((item) => item.unique)
          if (this.id) {
            contractDocPutApi(this.id, this.contractForm)
              .then((res) => {
                if (res.status == 200) {
                  this.handleClose()
                }
                this.loading = false
              })
              .catch((error) => {
                this.loading = false
                this.$message.error(error.message)
              })
          } else {
            contractDocSaveApi(this.contractForm)
              .then((res) => {
                if (res.status == 200) {
                  this.handleClose()
                  this.$emit('getTableData')
                  this.$emit('isOk')
                }
                this.loading = false
              })
              .catch((error) => {
                this.loading = false
                this.$message.error(error.message)
              })
          }
        }
      })
    },

    addSignatory() {
      // 添加签约方
      if (this.contractForm.signatory.length >= 4) {
        this.$message({
          message: '最多只能添加3个签署方',
          type: 'warning'
        })
        return
      }
      this.contractForm.signatory.push({
        types: 1,
        user_id: 0,
        name: '',
        company_name: '',
        phone: ''
      })
    }
  }
}
</script>

<style scoped lang="scss">
.slide-content {
  height: calc(100% - 50px);
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.public {
  width: 20px;
  height: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
  font-size: 14px;
  color: #606266;
  border-radius: 50%;
  border: 1px solid #d8d8d8;

  cursor: pointer;
}

.step {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 30px;
  margin-bottom: 30px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;

  .step-item {
    display: flex;

    align-items: center;
    cursor: pointer;
  }

  .step-text {
    font-size: 13px;
    color: #606266;
  }

  .active {
    background: #0091ff;
    border: none;
    color: #ffffff;
  }

  .line-title {
    display: inline-block;
    width: 130px;
    height: 4px;
    border-bottom: 1px dashed #dddddd;
  }

  .activeText {
    font-size: 13px;
    color: #303133;
  }
}

.signing-steps {
  margin-bottom: 30px;
}

.contract-form {
  font-family: PingFang SC, PingFang SC;

  .form-section {
    margin-bottom: 10px;

    .section-title {
      margin: 0 0 20px 0;

      font-weight: 500;
      font-size: 14px;
      color: #303133;
      border-left: 3px solid #1890ff;
      padding-left: 10px;
    }
  }

  .info-text {
    font-weight: 400;
    font-size: 13px;
    color: #303133;
  }
}

.signatory-item {
  background-color: #f7f7f7;
  padding: 20px;
  border-radius: 10px 10px 10px 10px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  position: relative;
  font-family: PingFang SC, PingFang SC;

  .el-icon-error {
    position: absolute;
    top: -4px;
    right: -4px;
    font-size: 18px;
    color: #909399;
    cursor: pointer;
  }

  .iconadd {
    color: #c0c4cc;
    font-size: 18px;
    cursor: move;
  }

  .left {
    width: 30px;
  }

  .right {
    flex: 1;

    .company-icon {
      padding: 0 4px;
      height: 19px;
      background: #1890ff;
      line-height: 19px;
      border-radius: 4px 4px 4px 4px;
      font-weight: 400;
      font-size: 11px;
      color: #ffffff;
    }

    .company-name {
      font-weight: 500;
      font-size: 14px;
      color: #303133;
      margin-left: 0px;
      margin-right: 6px;
    }
  }
}

.mr30 {
  margin-right: 30px;
}

.ml30 {
  margin-left: 30px;
}

.ml8 {
  margin-left: 8px;
}

.required {
  color: #f56c6c;
  margin-right: 4px;
}

:deep(.el-form-item__label) {
  font-weight: 400;
  font-size: 13px;
  color: #606266;
}

:deep(.el-form-item__content) {
  font-weight: 400;
  font-size: 13px;
  color: #303133;
}

:deep(.el-form-item) {
  margin-bottom: 10px;
}

:deep(.is-error + .upload-field) {
  margin-top: 20px;
}

.form-section.customer-info {
  padding-bottom: 10px;
  border-bottom: 1px dashed #dcdfe6;
}
</style>
