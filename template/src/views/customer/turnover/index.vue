<template>
  <div class="box">
    <div class="box-height">
      <el-card class="mb12 background-card">
        <oaFromBox
          v-if="pageReady"
          :isAddBtn="false"
          :isTotal="false"
          :isViewSearch="false"
          :search="turnoverSearch"
          :sortSearch="false"
          class="from-box"
          @confirmData="confirmData"
        ></oaFromBox>
      </el-card>
      <div class="item-info-card">
        <el-row :gutter="24" class="elRow">
          <el-col :lg="8" :md="6" :sm="12" :xl="4">
            <div class="item-info-list">
              <el-card>
                <div class="item">
                  <div class="item-top">
                    <div class="tit over-text">总收入（元）</div>
                    <el-popover placement="top-start" trigger="hover" width="150">
                      <div class="text-align">
                        环比{{ income.ratio > 0 ? '增加' : '减少' }}
                        <span v-if="income.ratio > 0">{{ income.ratio }}%</span>
                        <span v-else-if="income.ratio < 0">{{ absNum(income.ratio) }}%</span>
                        <span v-else>0%</span>
                      </div>
                      <template #reference>
                        <div class="img-box">
                          <span v-if="income.ratio > 0" class="iconfont icontongji-shangzhang" />
                          <span v-if="income.ratio < 0" class="iconfont icontongji-xiajiang" />
                          <div :class="income.ratio > 0 ? 'per' : 'fper'">
                            {{ income.ratio > 0 ? '+' : '' }}{{ income.ratio || 0 }}%
                          </div>
                        </div>
                      </template>
                    </el-popover>
                  </div>
                  <div class="num">
                    {{ income.price || 0 }}
                  </div>
                </div>
              </el-card>
            </div>
          </el-col>
          <el-col :lg="8" :md="6" :sm="12" :xl="4">
            <div class="item-info-list">
              <el-card>
                <div class="item">
                  <div class="item-top">
                    <div class="tit over-text">新增客户(人)</div>
                    <el-popover placement="top-start" trigger="hover" width="150">
                      <div class="text-align">
                        环比{{ new_customer.ratio > 0 ? '增加' : '减少' }}
                        <span v-if="new_customer.ratio > 0">{{ new_customer.ratio }}%</span>
                        <span v-else-if="new_customer.ratio < 0">{{ absNum(new_customer.ratio) }}%</span>
                        <span v-else>0%</span>
                      </div>
                      <template #reference>
                        <div class="img-box">
                          <span v-if="new_customer.ratio > 0" class="iconfont icontongji-shangzhang" />
                          <span v-if="new_customer.ratio < 0" class="iconfont icontongji-xiajiang" />
                          <div :class="new_customer.ratio > 0 ? 'per' : 'fper'">
                            {{ new_customer.ratio > 0 ? '+' : '' }}{{ new_customer.ratio || 0 }}%
                          </div>
                        </div>
                      </template>
                    </el-popover>
                  </div>
                  <div class="num">
                    {{ new_customer.count || 0 }}
                  </div>
                </div>
              </el-card>
            </div>
          </el-col>
          <el-col :lg="8" :md="6" :sm="12" :xl="4">
            <div class="item-info-list">
              <el-card>
                <div class="item">
                  <div class="item-top">
                    <el-popover placement="top-start" trigger="hover" width="auto">
                      <div class="text-align">按开始日期统计</div>
                      <template #reference>
                        <div class="tit over-text hand">新增订单(个)</div>
                      </template>
                    </el-popover>
                    <el-popover placement="top-start" trigger="hover" width="150">
                      <div class="text-align">
                        环比{{ new_contract.ratio > 0 ? '增加' : '减少' }}
                        <span v-if="new_contract.ratio > 0">{{ new_contract.ratio }}%</span>
                        <span v-else-if="new_contract.ratio < 0">{{ absNum(new_contract.ratio) }}%</span>
                        <span v-else>0%</span>
                      </div>
                      <template #reference>
                        <div class="img-box">
                          <span v-if="new_contract.ratio > 0" class="iconfont icontongji-shangzhang" />
                          <span v-if="new_contract.ratio < 0" class="iconfont icontongji-xiajiang" />
                          <div :class="new_contract.ratio > 0 ? 'per' : 'fper'">
                            {{ new_contract.ratio > 0 ? '+' : '' }}{{ new_contract.ratio || 0 }}%
                          </div>
                        </div>
                      </template>
                    </el-popover>
                  </div>
                  <div class="num">
                    {{ new_contract.count || 0 }}
                  </div>
                </div>
              </el-card>
            </div>
          </el-col>
          <el-col :lg="8" :md="6" :sm="12" :xl="4" style="min-height: 1px">
            <div class="item-info-list">
              <el-card>
                <div class="item">
                  <div class="item-top">
                    <el-popover placement="top-start" trigger="hover" width="auto">
                      <div class="text-align">按开始日期统计</div>
                      <template #reference>
                        <div class="tit hand over-text">新增订单金额(元)</div>
                      </template>
                    </el-popover>
                    <el-popover placement="top-start" trigger="hover" width="150">
                      <div class="text-align">
                        环比{{ new_contract_price.ratio > 0 ? '增加' : '减少' }}

                        <span v-if="new_contract_price.ratio > 0">{{ new_contract_price.ratio }}%</span>
                        <span v-else-if="new_contract_price.ratio < 0">{{ absNum(new_contract_price.ratio) }}%</span>
                        <span v-else>0%</span>
                      </div>
                      <template #reference>
                        <div class="img-box">
                          <span v-if="new_contract_price.ratio > 0" class="iconfont icontongji-shangzhang" />
                          <span v-if="new_contract_price.ratio < 0" class="iconfont icontongji-xiajiang" />
                          <div :class="new_contract_price.ratio > 0 ? 'per' : 'fper'">
                            {{ new_contract_price.ratio > 0 ? '+' : '' }}{{ new_contract_price.ratio || 0 }}%
                          </div>
                        </div>
                      </template>
                    </el-popover>
                  </div>
                  <div class="num">
                    {{ new_contract_price.price || 0 }}
                  </div>
                </div>
              </el-card>
            </div>
          </el-col>
          <el-col :lg="8" :md="6" :sm="12" :xl="4">
            <div class="item-info-list">
              <el-card>
                <div class="item">
                  <div class="item-top">
                    <div class="tit over-text">订单续费(元)</div>
                    <el-popover placement="top-start" trigger="hover" width="150">
                      <div class="text-align">
                        环比{{ renew.ratio > 0 ? '增加' : '减少' }}
                        <span v-if="renew.ratio > 0">{{ renew.ratio }}%</span>
                        <span v-else-if="renew.ratio < 0">{{ absNum(renew.ratio) }}%</span>
                        <span v-else>0%</span>
                      </div>
                      <template #reference>
                        <div class="img-box">
                          <span :class="renew.ratio > 0 ? 'per' : 'fper'"></span>
                          <span v-if="renew.ratio > 0" class="iconfont icontongji-shangzhang" />
                          <span v-if="renew.ratio < 0" class="iconfont icontongji-xiajiang" />
                          <div :class="renew.ratio > 0 ? 'per' : 'fper'">
                            {{ renew.ratio > 0 ? '+' : '' }}{{ renew.ratio || 0 }}%
                          </div>
                        </div>
                      </template>
                    </el-popover>
                  </div>
                  <div class="num">
                    {{ renew.price || 0 }}
                  </div>
                </div>
              </el-card>
            </div>
          </el-col>
          <el-col :lg="8" :md="6" :sm="12" :xl="4">
            <div class="item-info-list">
              <el-card>
                <div class="item">
                  <div class="item-top">
                    <div class="tit over-text">至今未回款金额(元)</div>
                  </div>
                  <div class="num">
                    {{ uncollected_price.price || 0 }}
                  </div>
                </div>
              </el-card>
            </div>
          </el-col>
        </el-row>
      </div>
      <el-row :gutter="24" class="equal-height-row row-chart">
        <el-col :lg="12">
          <el-card class="stat-card">
            <div class="statistics-title">业绩趋势</div>
            <div class="card-body">
              <echartBox :option-data="optionData1" :styles="styles1" />
            </div>
          </el-card>
        </el-col>
        <el-col :lg="12">
          <el-card class="stat-card">
            <div class="statistics-title">
              部门业绩统计
            </div>
            <div class="card-body">
              <div v-if="frame_rank.length > 0" class="statistics-department">
                <div class="item"></div>
                <div class="item"></div>
              </div>
              <div v-else class="default">
                <!-- <img alt="" src="../../../assets/images/def1.png" /> -->
                <span>{{ '暂无数据' + '~' }}</span>
              </div>
              <echartBox v-if="frame_rank.length > 0" :option-data="optionData" :styles="styles1" />
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="24" class="equal-height-row row-pie">
        <el-col v-if="contractCategoryEnabled" :lg="12">
          <el-card class="stat-card">
            <div class="statistics-title">
              订单分类统计
            </div>
            <div class="card-body">
              <div v-if="contract_rank.length > 0" class="ml10">
                <el-breadcrumb separator-icon="el-icon-arrow-right">
                  <el-breadcrumb-item
                    v-for="(item, index) in breadcrumbList"
                    :key="index"
                    :class="{ breadcrumb: active == item.name }"
                    ><span @click="changeActive(item, index)">{{ item.name }}</span></el-breadcrumb-item
                  >
                </el-breadcrumb>
              </div>

              <div v-if="contract_rank.length > 0" ref="init" class="pie-wrapper">
                <echartBox :option-data="pieChartData" :styles="styles1" @pieChange="pieChange" />
              </div>
              <div v-else class="default">
                <!-- <img alt="" src="../../../assets/images/def1.png" style="width: 200px; height: 150px" /> -->
                <span>{{ '暂无数据' + '~' }}</span>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :lg="contractCategoryEnabled ? 12 : 24">
          <el-card class="stat-card">
            <div class="statistics-title">产品分类</div>
            <div class="card-body">
              <div v-if="product_rank.length > 0" class="ml10">
                <el-breadcrumb separator-icon="el-icon-arrow-right">
                  <el-breadcrumb-item
                    v-for="(item, index) in productBreadcrumbList"
                    :key="index"
                    :class="{ breadcrumb: productActive == item.name }"
                    ><span @click="productChangeActive(item, index)">{{ item.name }}</span></el-breadcrumb-item
                  >
                </el-breadcrumb>
              </div>
              <div v-if="product_rank.length > 0" ref="productCategory" class="pie-wrapper">
                <echartBox :option-data="productCategoryChart" :styles="styles1" @pieChange="productPieChange" />
              </div>
              <div v-else class="default">
                <!-- <img alt="" src="../../../assets/images/def1.png" style="width: 200px; height: 150px" /> -->
                <span>{{ '暂无数据' + '~' }}</span>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="24" class="equal-height-row row-table">
        <el-col :lg="12">
          <el-card class="stat-card">
            <el-tabs v-model="activeName" class="rank-tabs" @tab-change="handleClick">
              <el-tab-pane label="业务员排行榜" name="1" />
              <el-tab-pane label="部门排行榜" name="2" />
            </el-tabs>
            <div class="card-body">
              <div v-loading="rankLoading" class="table-box">
                <el-table ref="table" :data="tableData" height="340" row-key="id" style="width: 100%" :key="activeName">
                  <el-table-column type="index" label="排名" width="50" />
                  <el-table-column :label="activeName == 1 ? '姓名' : '部门'" min-width="80" prop="name" />
                  <el-table-column
                    v-if="activeName == 1"
                    label="部门"
                    min-width="90"
                    prop="frame_name"
                  >
                    <template #default="{ row }">
                      <el-tooltip :content="row.frame_name" placement="top">
                        <div class="over-text">{{ row.frame_name }}</div>
                      </el-tooltip>
                    </template>
                  </el-table-column>
                  <el-table-column label="完成占比" min-width="160" prop="ratio">
                    <template #default="scope">
                      <el-progress color="#1890ff" :percentage="scope.row.ratio" :show-text="true"></el-progress>
                    </template>
                  </el-table-column>
                  <el-table-column label="完成金额(元)" min-width="120" prop="price" />
                  <el-table-column label="支出金额（元）" min-width="120" prop="expend" />
                  <el-table-column label="净额（元）" min-width="120" prop="net_amount" v-if="activeName == 1" />
                </el-table>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :lg="12">
          <el-card class="stat-card">
            <div class="statistics-title">产品业绩排行</div>
            <div class="card-body">
              <div class="table-box">
                <el-table :data="productRankData" height="340" style="width: 100%">
                  <el-table-column type="index" label="排名" width="50" :index="indexMethod" />
                  <el-table-column label="产品" min-width="180">
                    <template #default="{ row }">
                      <div class="product-cell">
                        <el-image v-if="row.image" :src="row.image" fit="cover" class="product-image" />
                        <span class="product-name">{{ row.display_name }}</span>
                      </div>
                    </template>
                  </el-table-column>
                  <el-table-column label="规格" min-width="80" prop="order_count">
                    <template #default="{ row }">
                      <span>{{ row.sku || '--' }}</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="销量" min-width="80" prop="order_count" />
                  <el-table-column label="销售额（元）" min-width="120" prop="total_price" />
                </el-table>
              </div>
              <el-pagination
                v-if="productTotal > 0"
                class="mt12"
                size="small"
                :current-page="productPage"
                :page-size="productLimit"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, prev, pager, next, jumper"
                :total="productTotal"
                @size-change="handleProductSizeChange"
                @current-change="handleProductPageChange"
              />
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import {
  salesmanDataListApi,
  performanceStatisticsApi,
  contracRankApi,
  contractCategoryEnabledApi,
  trendStatisticsApi,
  frameDataListApi,
  productRankApi,
  productRankListApi
} from '@/api/enterprise.ts'
import { numberFormat } from '@/utils/numberFormat.ts'
import { getDictTreeListApi } from '@/api/form.ts'

import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'Turnover',
  components: {
    oaFromBox,
    echartBox: defineAsyncComponent(() => import('@/components/common/echarts'))
  },
  data() {
    return {
      optionData: {},
      optionData1: {},
      pieChartData: {},
      productCategoryChart: {},
      productRankData: [],
      product_rank: [],
      productPage: 1,
      productLimit: 15,
      productTotal: 0,
      pageReady: false,
      contractCategoryEnabled: false,
      productActive: '产品分类',
      productActiveId: '',
      productBreadcrumbList: [
        {
          name: '产品分类',
          id: ''
        }
      ],
      income: {},
      renew: {},
      activeName: '1',
      rankLoading: false,
      active: '订单分类',
      breadcrumbList: [
        {
          name: '订单分类',
          id: ''
        }
      ],
      category_id: '',
      contract_rank: [],
      frame_rank: [],
      new_customer: {},
      new_contract: {},
      new_contract_price: {},
      uncollected_price: {},
      styles1: {
        height: '300px',
        width: '100%',
        margin: 'auto'
      },
      tableData: [],
      where: {
        page: 1,
        limit: 15,
        scope_frame: 'all',
        time: dayjs().subtract(1, 'months').format('YYYY/MM/DD') + '-' + dayjs().format('YYYY/MM/DD')
      },
      activeId: '',
      total: 0,
      search: [
        {
          field_name: '管理范围',
          field_name_en: 'scope_frame',
          form_value: 'manage'
        },
        {
          field_name: '订单分类',
          field_name_en: 'category_id',
          form_value: 'cascader',
          data_dict: []
        },
        {
          field_name: '开始时间',
          field_name_end: '结束时间',
          field_name_en: 'time',
          form_value: 'date_picker',
          data_dict: [
            dayjs().startOf('months').format('YYYY/MM/DD'),
            dayjs().endOf('months').format('YYYY/MM/DD')
          ]
        }
      ],
      timeVal: [
        dayjs().startOf('months').format('YYYY/MM/DD'),
        dayjs().endOf('months').format('YYYY/MM/DD')
      ],
      time: ''
    }
  },
  computed: {
    turnoverSearch() {
      if (this.contractCategoryEnabled) {
        return this.search
      }
      return this.search.filter((item) => item.field_name_en !== 'category_id')
    }
  },
  async mounted() {
    this.where.time = `${this.timeVal[0]}-${this.timeVal[1]}`
    await this.initContractCategoryStatus()
    this.pageReady = true
    if (this.contractCategoryEnabled) {
      this.contractList()
    }
    this.getChartList()
    this.trendStatistics()
    this.getTableData()
    if (this.contractCategoryEnabled) {
      this.getActive()
    }
    this.getProductActive()
    this.getProductRankList()
  },
  methods: {
    absNum(a) {
      if (a < 0) {
        a = a * -1
      }
      return a
    },
    async initContractCategoryStatus() {
      try {
        const res = await contractCategoryEnabledApi()
        this.contractCategoryEnabled = !!(res.data && res.data.enabled)
      } catch (e) {
        this.contractCategoryEnabled = true
      }
      if (!this.contractCategoryEnabled) {
        this.clearContractCategoryState()
      }
    },
    clearContractCategoryState() {
      delete this.where.category_id
      this.contract_rank = []
      this.pieChartData = {}
      this.active = '订单分类'
      this.activeId = ''
      this.breadcrumbList = [
        {
          name: '订单分类',
          id: ''
        }
      ]
    },
    getContractCategoryIds() {
      if (!this.contractCategoryEnabled || !Array.isArray(this.where.category_id)) {
        return []
      }
      return this.where.category_id
    },
    // 获取订单分类列表
    contractList() {
      if (!this.contractCategoryEnabled) {
        return
      }
      let data = {
        level: '',
        types: 'contract_type'
      }
      getDictTreeListApi(data).then((res) => {
        setTimeout(() => {
          const list = res.data
          if (list) {
            const categoryIndex = this.search.findIndex((item) => item.field_name_en === 'category_id')
            if (categoryIndex !== -1) {
              this.search[categoryIndex].data_dict = list
            }
          }
        }, 300)
      })
    },
    format(percentage) {
      if (percentage == 100) {
        return '完成'
      } else {
        return `${percentage}%`
      }
    },
    confirmData(data, resetState) {
      if (data == 'reset') {
        const timeIndex = this.search.findIndex((item) => item.field_name_en === 'time')
        if (timeIndex !== -1) {
          this.search[timeIndex].data_dict = this.timeVal
        }
        this.where = {
          time: this.timeVal[0] + '-' + this.timeVal[1],
          scope_frame: 'all',
          // 排序等由搜索栏维护的默认参数，重置后需一并带回
          ...resetState
        }
        if (this.contractCategoryEnabled) {
          this.where.category_id = ''
        }
      } else {
        const searchData = { ...data }
        if (!this.contractCategoryEnabled) {
          delete searchData.category_id
        }
        this.where = { ...this.where, ...searchData }
      }
      if (!this.contractCategoryEnabled) {
        delete this.where.category_id
      }
      this.breadcrumbList = []
      this.productPage = 1
      this.getChartList(data.id)
      this.handleClick()
      // this.getTableData(data.id)
      let newName = {
        name: '订单分类',
        id: ''
      }
      this.activeId = ''
      this.breadcrumbList.push(newName)
      this.productBreadcrumbList = [{ name: '产品分类', id: '' }]
      this.productActive = '产品分类'
      this.productActiveId = ''
      this.trendStatistics()
      if (this.contractCategoryEnabled) {
        this.getActive()
      } else {
        this.clearContractCategoryState()
      }
      this.getProductActive()
      this.getProductRankList()
      if (this.contractCategoryEnabled) {
        this.contractList()
      }
    },
    // 获取业绩趋势图数据
    trendStatistics() {
      trendStatisticsApi(this.where).then((res) => {
        this.xianchart(res.data)
      })
    },

    handleClick() {
      this.rankLoading = true
      if (this.activeName == 1) {
        this.getTableData().finally(() => {
          this.rankLoading = false
        })
      } else {
        let obj = {}
        for (let key in this.where) {
          obj[key] = this.where[key]
        }
        obj.limit = 0
        frameDataListApi(obj)
          .then((res) => {
            this.tableData = res.data.map((item) => {
              item.price = numberFormat(Number(item.price))
              return item
            })
            this.total = res.data.count
          })
          .finally(() => {
            this.rankLoading = false
          })
      }
    },
    // 获取表格数据
    getTableData(id) {
      let obj = {}
      for (let key in this.where) {
        obj[key] = this.where[key]
      }
      obj.limit = 0
      return salesmanDataListApi(obj).then((res) => {
        this.tableData = res.data.list.map((item) => {
          item.price = numberFormat(Number(item.price))
          return item
        })
        this.total = res.data.count
      })
    },

    getChartList(id) {
      performanceStatisticsApi(this.where).then((res) => {
        const data = res.data
        this.income = data.income
        this.income.price = numberFormat(data.income.price)
        this.renew = data.renew
        this.renew.price = numberFormat(data.renew.price)
        this.new_customer = data.new_customer
        this.new_contract = data.new_contract
        this.new_contract_price = data.new_contract_price
        this.new_contract_price.price = numberFormat(data.new_contract_price.price)
        this.uncollected_price = data.uncollected_price
        this.uncollected_price.price = numberFormat(data.uncollected_price.price)
        this.frame_rank = res.data.frame_rank

        this.optionData = {
          tooltip: {
            trigger: 'axis',
            axisPointer: {
              type: 'line',
              lineStyle: {
                color: '#CCCCCC'
              }
            }
          },
          legend: {
            data: [],
            show: true,
            right: 10,
            top: 0
          },
          grid: {
            left: 60,
            top: 40,
            right: 20,
            bottom: 40
          },
          toolbox: {},

          color: ['#1890FF'],
          xAxis: [
            {
              type: 'category',
              nameTextStyle: {
                color: '#CCCCCC'
              },
              axisLine: {
                lineStyle: {
                  color: '#CCCCCC'
                }
              },
              axisLabel: {
                color: '#666666'
              },
              data: []
            }
          ],
          yAxis: [
            {
              type: 'value',
              position: 'left',
              axisTick: {
                show: true,
                alignWithLabel: true
              },
              min: 0,
              nameTextStyle: {
                color: '#CCCCCC'
              },
              axisLine: {
                lineStyle: {
                  color: '#CCCCCC'
                }
              },
              axisLabel: {
                color: '#666666'
              },
              splitLine: {
                lineStyle: {
                  type: 'dashed'
                }
              }
            }
          ],
          series: [
            {
              name: '',
              type: 'bar',
              smooth: true,
              barWidth: 25,
              itemStyle: {
                normal: {
                  label: {
                    show: true,
                    color: '#fff',
                    fontSize: 14,
                    lineHeight: 20,
                    backgroundColor: '#666',
                    padding: [3, 5, 3, 5],
                    borderRadius: 5,
                    position: 'top',
                    formatter: '{c}'
                  }
                }
              },
              data: []
            }
          ]
        }
        if (data.frame_rank.length > 0) {
          this.optionData.series[0].data = data.frame_rank.map((item) => item.price)
          this.optionData.xAxis[0].data = data.frame_rank.map((item) => item.name)
        }
      })
    },
    // 趋势图
    xianchart(data) {
      // 趋势图
      this.optionData1 = {
        color: ['#19BE6B', '#FF9900'],

        tooltip: {
          trigger: 'axis',
          formatter: (option) => {
            return `${dayjs(option[0].axisValue).format('YYYY年MM月DD日')}<br/>${option[0].seriesName}：${
              option[0].value
            }元<br/>${option[1].seriesName}：${option[1].value}元`
          }
        },
        grid: {
          left: 0,
          top: 40,
          right: 20,
          bottom: 0,
          containLabel: true
        },
        xAxis: {
          type: 'category',
          boundaryGap: true,
          nameTextStyle: {
            color: '#86909C'
          },
          axisTick: {
            show: false
          },
          axisLine: {
            lineStyle: {
              color: '#C9CDD4'
            }
          },
          axisLabel: {
            color: '#666666'
          },
          data: data.xAxis
        },
        yAxis: {
          axisLine: {
            lineStyle: {
              color: '#fff'
            }
          },
          axisLabel: {
            color: '#86909C'
          },
          splitLine: {
            lineStyle: {
              type: 'dashed'
            }
          }
        },
        series: [
          {
            name: '流入',
            type: 'line',
            itemStyle: {
              normal: {
                color: '#19BE6B'
              }
            },
            smooth: true,
            data: data.series[0].data
          },
          {
            name: '流出',
            type: 'line',
            itemStyle: {
              normal: {
                color: '#FF9900'
              }
            },
            smooth: true,
            data: data.series[1].data
          },
          {
            name: '总支出',
            type: 'line',
            symbol: 'circle',
            // symbolSize: 5, // 圆点大小
            smooth: true,
            lineStyle: {
              width: 1,
              shadowColor: 'rgba(0, 0, 0, 0.5)',
              shadowBlur: 10,
              color: '#19BE6B'
            },
            areaStyle: {
              color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  {
                    offset: 0,
                    color: '#19BE6B' // 0% 处的颜色
                  },
                  {
                    offset: 0.9,
                    color: '#fff' // 100% 处的颜色
                  }
                ],
                global: false
              }
            },
            data: data.series[0].data
          },
          {
            name: '总支出',
            type: 'line',
            symbol: 'circle',
            // symbolSize: 5, // 圆点大小
            smooth: true,
            lineStyle: {
              width: 1,
              shadowColor: 'rgba(0, 0, 0, 0.5)',
              shadowBlur: 10,
              color: '#FF9900'
            },
            areaStyle: {
              color: {
                type: 'linear',
                x: 0,
                y: 0,
                x2: 0,
                y2: 1,
                colorStops: [
                  {
                    offset: 0,
                    color: '#FF9900' // 0% 处的颜色
                  },
                  {
                    offset: 0.9,
                    color: '#FFF' // 100% 处的颜色
                  }
                ],
                global: false
              }
            },
            data: data.series[1].data
          }
        ]
      }
    },
    // 产品分类业绩统计
    getProductActive(category) {
      const data = {
        time: this.where.time,
        scope_frame: this.where.scope_frame,
        category_id: this.getContractCategoryIds(),
        category: category || ''
      }
      productRankApi(data).then((res) => {
        this.product_rank = res.data || []
        this.deconstructionProduct(this.product_rank)
      })
    },
    // 产品业绩排行列表
    getProductRankList() {
      const params = {
        page: this.productPage,
        limit: this.productLimit,
        time: this.where.time,
        scope_frame: this.where.scope_frame,
        category_id: this.getContractCategoryIds()
      }
      productRankListApi(params).then((res) => {
        const list = (res.data && (res.data.list || res.data)) || []
        this.productTotal = res.data?.count || 0
        this.productRankData = (Array.isArray(list) ? list : []).map((item) => {
          const specType = item.spec_type
          const productName = item.product_name || item.name || ''
          let displayName = productName
          if (specType == 2) {
            displayName = `[多规格]${productName}${item.sku ? ' ' + item.sku : ''}`
          }
          return {
            ...item,
            display_name: displayName,
            total_price: numberFormat(Number(item.total_price || item.price || 0)),
            order_count: item.order_count || item.count || 0
          }
        })
      })
    },
    // 产品业绩排行分页
    handleProductSizeChange(val) {
      this.productLimit = val
      this.productPage = 1
      this.getProductRankList()
    },
    handleProductPageChange(val) {
      this.productPage = val
      this.getProductRankList()
    },
    // 排名序号计算
    indexMethod(index) {
      return (this.productPage - 1) * this.productLimit + index + 1
    },
    // 处理产品分类圆环数据
    deconstructionProduct(data) {
      const list = Array.isArray(data) ? data : []
      const boxData = list.map((item) => ({
        value: item.price,
        name: item.category_name,
        id: item.category_id,
        count: item.count
      }))
      const total = list.reduce((sum, item) => sum + Number(item.price || 0), 0)
      const totalText = numberFormat(parseFloat(total).toFixed(2))

      this.productCategoryChart = {
        color: [
          '#1890FF',
          '#19BE6B',
          '#FF9900',
          '#A277FF',
          '#4BCAD5',
          'tomato',
          'deepskyblue',
          'khaki',
          'salmon',
          'darkslategray'
        ],
        title: [
          {
            text: totalText,
            subtext: '销售总金额(元)',
            left: '24%',
            top: '42%',
            textAlign: 'center',
            textStyle: {
              fontSize: 22,
              fontFamily: 'D-DIN-PRO',
              fontWeight: 600,
              color: '#1D2129'
            },
            subtextStyle: {
              fontSize: 12,
              fontFamily: 'PingFang SC',
              fontWeight: 400,
              color: '#909399'
            }
          }
        ],
        tooltip: {
          trigger: 'item',
          enterable: true,
          formatter: (option) => {
            const count = (list[option.dataIndex] && list[option.dataIndex].count) || 0
            return `${option.seriesName}<br/>${option.name}(${count}): ${numberFormat(option.value)}元 ${
              option.percent
            }%`
          }
        },
        legend: [
          {
            type: 'scroll',
            orient: 'vertical',
            right: '0%',
            itemGap: 18,
            itemWidth: 12,
            itemHeight: 12,
            icon: 'path://M6,0 A6,6 0 1,1 6,12 A6,6 0 1,1 6,0 Z M6,3 A3,3 0 1,0 6,9 A3,3 0 1,0 6,3 Z',
            y: 'center',
            pageIconSize: 12,
            textStyle: {
              fontSize: 12,
              color: '#303133',
              lineHeight: 20,
              rich: {
                a: {
                  padding: [0, 10, 0, 10],
                  fontWeight: 600
                },
                b: {
                  color: '#999'
                }
              }
            },
            formatter: (name) => {
              let target = ''
              let ratio = 0
              let count = 0
              const sum = list.reduce((s, i) => s + Number(i.price || 0), 0)
              for (const item of list) {
                if (item.category_name === name) {
                  target = numberFormat(item.price)
                  ratio = sum > 0 ? ((Number(item.price) / sum) * 100).toFixed(2) : 0
                  count = item.count || 0
                }
              }
              return `${name}{a|${target}元} {b|${ratio}%}`
            }
          }
        ],
        series: [
          {
            type: 'pie',
            name: '产品分类',
            data: boxData,
            label: {
              show: true,
              position: 'outer',
              formatter: '{d}%',
              color: '#1D2129',
              fontSize: 12,
              fontFamily: 'D-DIN-PRO',
              fontWeight: 500
            },
            labelLine: {
              show: true,
              length: 12,
              length2: 14,
              lineStyle: {
                color: '#C9CDD4'
              }
            },
            right: '40%',
            radius: ['55%', '70%'],
            center: ['40%', '50%']
          }
        ]
      }
    },
    async productChangeActive(row, index) {
      this.productActive = row.name
      this.productActiveId = row.id
      this.productBreadcrumbList.splice(index + 1)
      const data = {
        time: this.where.time,
        scope_frame: this.where.scope_frame,
        category_id: this.getContractCategoryIds(),
        category: row.id || ''
      }
      const result = await productRankApi(data)
      this.product_rank = result.data || []
      this.deconstructionProduct(this.product_rank)
      if (row.name == '产品分类') {
        this.productBreadcrumbList = [{ name: '产品分类', id: '' }]
      }
    },
    async productPieChange(row) {
      if (!row || this.productActiveId === row.id) {
        return
      }
      const data = {
        time: this.where.time,
        scope_frame: this.where.scope_frame,
        category_id: this.getContractCategoryIds(),
        category: row.id
      }
      const result = await productRankApi(data)
      if (result.data && result.data.length) {
        this.productActive = row.name
        this.productActiveId = row.id
        this.product_rank = result.data
        this.deconstructionProduct(result.data)
        this.productBreadcrumbList.push(row)
      }
    },
    // 处理圆环显示数据
    deconstruction(data) {
      let boxData = []
      let totalData = ''
      this.contract_rank.map((item) => {
        let itemData = {
          value: item.price,
          name: item.category_name,
          id: item.category_id
        }
        boxData.push(itemData)
      })
      totalData = this.contract_rank.reduce((totalData, obj) => (totalData += Number(obj.price)), 0)
      totalData = numberFormat(parseFloat(totalData).toFixed(2))

      this.pieChartData = {
        color: [
          'tomato',
          'deepskyblue',
          'orange',
          'mediumseagreen',
          'violet',
          'dodgerblue',
          'khaki',
          'turquoise',
          'salmon',
          'darkslategray'
        ],
        // 鼠标移动展示
        tooltip: {
          trigger: 'item',
          enterable: true,
          formatter: (option) => {
            return `${option.seriesName} <br/> ${option.name}(${data[option.dataIndex].count}): ${option.value}元
            ${option.percent}%`
          }
        },

        // 分类
        legend: [
          {
            type: 'scroll',
            orient: 'vertical',
            right: '0%', // 距离右侧位置
            itemGap: 18,
            itemWidth: 12,
            itemHeight: 12,
            icon: 'path://M6,0 A6,6 0 1,1 6,12 A6,6 0 1,1 6,0 Z M6,3 A3,3 0 1,0 6,9 A3,3 0 1,0 6,3 Z',
            data: '',

            y: 'center',
            pageIconSize: 12,
            textStyle: {
              fontSize: 12,
              color: '#303133',
              lineHeight: 20, // 解决提示语字显示不全
              rich: {
                a: {
                  padding: [0, 10, 0, 10],
                  fontWeight: 600
                },
                b: {
                  color: '#999'
                }
              }
            },
            formatter: function (name) {
              let target
              let ratio
              let count
              let expend
              let total = 0
              // 首先计算总数
              data.forEach((item) => {
                total += Number(item.price)
              })

              // 然后找到每个项目，并计算其百分比
              for (let i = 0; i < data.length; i++) {
                if (data[i].category_name === name) {
                  target = numberFormat(data[i].price)
                  ratio = ((Number(data[i].price) / total) * 100).toFixed(2) // 计算百分比
                  count = data[i].count
                  expend = data[i].expend
                  if (data[i].expend != 0) {
                    expend = '-' + data[i].expend
                  }
                }
              }
              let arr = [`${name}{a|${target}元} {b|${ratio}%}`]
              return arr.join('\n')
            }
          }
        ],
        title: [
          {
            text: totalData,
            subtext: '订单总金额(元)',
            left: '24%',
            top: '42%',
            textAlign: 'center',
            textStyle: {
              fontSize: 22,
              fontFamily: 'D-DIN-PRO',
              fontWeight: 600,
              color: '#1D2129'
            },
            subtextStyle: {
              fontSize: 12,
              fontFamily: 'PingFang SC',
              fontWeight: 400,
              color: '#909399'
            }
          }
        ],
        series: [
          {
            type: 'pie',
            data: boxData,
            label: {
              show: true,
              position: 'outer',
              formatter: '{d}%',
              color: '#1D2129',
              fontSize: 12,
              fontFamily: 'D-DIN-PRO',
              fontWeight: 500
            },
            labelLine: {
              show: true,
              length: 12,
              length2: 14,
              lineStyle: {
                color: '#C9CDD4'
              }
            },
            right: '40%',
            name: '订单分类统计',
            radius: ['55%', '70%'],
            center: ['40%', '50%']
          }
        ]
      }
    },
    indexAdd(index) {
      const page = Number(this.where.page) // 当前页码
      const pagesize = Number(this.where.limit) // 每页条数
      index = Number(index)
      return index + 1 + (page - 1) * pagesize
    },
    customColorMethod(row) {
      return 'rgb(' + (255 - row.ratio * 12) + ',' + (255 - row.ratio * 12) + ',255)'
    },
    getActive() {
      if (!this.contractCategoryEnabled) {
        return
      }
      let data = {
        time: this.where.time,
        scope_frame: this.where.scope_frame,
        category_id: this.getContractCategoryIds()
      }
      contracRankApi(data).then((res) => {
        this.contract_rank = res.data
        this.deconstruction(this.contract_rank)
      })
    },
    async changeActive(row, index) {
      if (!this.contractCategoryEnabled) {
        return
      }
      this.active = row.name
      this.activeId = row.id
      this.breadcrumbList.splice(index + 1)

      if (row.name == '订单分类') {
        let item = {
          time: this.where.time,
          scope_frame: this.where.scope_frame,
          category_id: this.getContractCategoryIds()
        }
        const result = await contracRankApi(item)
        this.contract_rank = result.data
        this.deconstruction(result.data)
        this.breadcrumbList = [
          {
            name: '订单分类',
            id: ''
          }
        ]
      } else {
        let item = {
          time: this.where.time,
          scope_frame: this.where.scope_frame,
          category_id: this.getContractCategoryIds(),
          category: row.id
        }
        const result = await contracRankApi(item)
        this.contract_rank = result.data
        this.deconstruction(result.data)
      }
    },
    // 点击圆环图
    async pieChange(row) {
      if (!this.contractCategoryEnabled) {
        return
      }
      if (this.activeId === row.id) {
        return
      } else {
        let item = {
          time: this.where.time,
          scope_frame: this.where.scope_frame,
          category_id: this.getContractCategoryIds(),
          category: row.id
        }

        const result = await contracRankApi(item)
        if (result.data.length) {
          this.active = row.name
          this.activeId = row.id
          this.contract_rank = result.data
          this.deconstruction(result.data)
          this.breadcrumbList.push(row)
        }
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.el-breadcrumb :deep(.el-breadcrumb__inner) {
  color: #1890ff;
  span {
    font-size: 14px;
    cursor: pointer;
  }
}
.box {
  margin: 14px;
  margin-top: 1px;
  box-sizing: border-box;
  :deep(.divBox) {
    // margin: 0;
    padding: 0;
  }
}
.hand {
  cursor: pointer;
}
.text-align {
  width: 100%;
  text-align: center;
  font-size: #606266;
  font-size: 13px;
}
:deep(.el-tabs__nav-wrap::after) {
  height: 0;
}
.breadcrumb :deep(.el-breadcrumb__inner) {
  color: #909399;
  span {
    font-size: 14px;
    cursor: pointer;
  }
}

.mt50 {
  margin: 0 auto;
  margin-top: 40px;
  min-width: 400px;
  max-width: 700px;
  height: 300px;
}

.divBox {
  height: auto !important;
}

.el-row {
  margin-right: 0px !important;
}

.equal-height-row {
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
  > .el-col {
    display: flex;
    flex-direction: column;
  }
  .stat-card {
    flex: 1;
    width: 100%;
    min-height: 440px;
    display: flex;
    flex-direction: column;
    :deep(.el-card__body) {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
      padding: 20px;
    }
  }
  .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  .table-box {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    :deep(.el-table__empty-block) {
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }
}

.stat-card .pie-wrapper {
  flex: 1;
  min-height: 260px;
  margin: 0 auto;
  margin-top: 10px;
  min-width: 400px;
  max-width: 700px;
  width: 100%;
}

.stat-card .default {
  flex: 1;
  justify-content: center;
  height: auto;
}

.rank-tabs {
  :deep(.el-tabs__header) {
    margin-bottom: 12px;
  }
  :deep(.el-tabs__item) {
    font-weight: 600;
    height: initial;
    padding-bottom: 8px;
    color: #909399;
    &.is-active {
      color: #1d2129;
    }
  }
}
.elRow {
  display: flex;
  flex-wrap: wrap;
}

.el-col {
  padding-right: 0px !important;
  padding-bottom: 12px;
}
.item-info-card {
  width: 100%;
  .item-info-list {
    width: 100%;
    &:last-of-type {
      margin-right: 0;
    }
  }
  :deep(.el-card) {
    min-width: 220px;
  }
}
.per {
  font-size: 13px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: #19be6b;
}
.fper {
  font-size: 13px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: #ff9900;
}
.icontongji-shangzhang {
  color: #19be6b;
  margin-right: 4px;
  margin-left: 4px;
}
.icontongji-xiajiang {
  color: #ff9900;
  margin-right: 4px;
  margin-left: 4px;
}
.item {
  display: flex;
  flex-direction: column;
  padding-right: 10px;
  .item-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    height: 18px;
    .img-box {
      display: flex;
      align-items: center;
      cursor: pointer;
      line-height: 1;
      .iconfont {
        font-size: 12px;
      }
      img {
        width: 15px;
        height: 7px;
        margin-right: 8px;
      }
      .tit {
        font-size: 13px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: #909399;
      }
      .per {
        font-size: 13px;
        font-family: PingFangSC-Medium, PingFang SC;
        font-weight: 500;
        color: #19be6b;
      }
      .fper {
        font-size: 13px;
        font-family: PingFangSC-Medium, PingFang SC;
        font-weight: 500;
        color: #ff9900;
      }
    }
    .tit {
      font-size: 13px;
      font-weight: 400;
      color: #909399;
    }
  }
  .num {
    font-size: 24px;
    font-family: D-DIN-PRO, D-DIN-PRO;
    font-weight: 600;
    color: #303133;
    line-height: 1;
  }
}
.statistics-title {
  font-weight: 600;
  color: #333333;
  padding-left: 10px;
  line-height: 20px;
  margin-bottom: 19px;
  border-left: 2px solid #1890ff;
}
:deep(.el-progress-bar) {
  width: 90% !important;
}
.statistics-department {
  display: flex;
  .item {
    width: 50%;
    display: flex;
    .item-top {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      img {
        width: 24px;
        height: 24px;
        margin-right: 6px;
      }
      .tit {
        font-size: 16px;
        margin-left: 6px;
        margin-top: 2px;
      }
    }
  }
}
.default {
  height: 369px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  img {
    width: 200px;
    height: 150px;
  }
}
.from-box :deep(.search) {
  margin-top: 0;
}
.product-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  .product-image {
    width: 36px;
    height: 36px;
    border-radius: 4px;
    flex-shrink: 0;
  }
  .product-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
.mt12 {
  margin-top: 12px;
  justify-content: flex-end;
}
</style>
