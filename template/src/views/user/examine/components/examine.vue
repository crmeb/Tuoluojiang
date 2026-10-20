<template>
  <div>
    <oaFromBox
      :search="search"
      :total="total"
      :isAddBtn="false"
      :isViewSearch="false"
      @dropdownFn="dropdownFn"
      @confirmData="confirmData"
    ></oaFromBox>
    <div class="mt-10" v-loading="loading" v-if="tableData.length > 0">
      <el-scrollbar style="height: 100%">
        <ul class="table-box" :style="{ height: TABLE_HEIGHT }">
          <li v-for="(item, index) in tableData" :key="index">
            <el-row class="table-header">
              <el-col :span="12">
                <div class="table-title">
                  <div class="table-title-left" v-if="item.approve">
                    <div class="selIcon" :style="{ backgroundColor: item.approve.color }">
                      <i class="icon iconfont" :class="item.approve.icon"></i>
                    </div>
                  </div>
                  <div class="table-title-right">
                    <div class="title" v-if="item.approve">{{ item.approve.name }}</div>
                    <div class="tag">
                      <el-tag size="small" v-if="item.status === -1" type="info"> 已撤销 </el-tag>
                      <el-tag size="small" v-if="item.status === 0" type="warning"> 审核中 </el-tag>
                      <el-tag size="small" v-if="item.status === 1 && !item.recall" type="info"> 已通过 </el-tag>
                      <el-tag size="small" v-if="item.status === 2" type="danger"> 已拒绝 </el-tag>
                      <el-tag size="small" v-if="item.status === 1 && item.recall" type="danger"> 撤销中 </el-tag>
                    </div>
                  </div>
                </div>
              </el-col>
              <el-col :span="12" class="text-right">
                <el-button @click="handleDetail(item)">查看详情</el-button>
                <template v-if="item.status == 0">
                  <el-button v-if="item.verify_status === 0" type="danger" @click="handleRefuse(item)">
                    拒绝
                  </el-button>
                  <el-button v-if="item.verify_status === 0" type="primary" @click="handleAgree(item)">
                    同意
                  </el-button>
                </template>
              </el-col>
            </el-row>
            <div class="table-body">
              <div class="table-body-title">
                <div class="table-body-left">
                  <img class="img-body" v-if="judge(item)" :src="item.card.avatar" alt="" />
                  <img class="img-body" v-else src="../../../../assets/images/portrait.png" alt="" />
                </div>
                <div class="table-body-right">
                  <div class="title">
                    <span>{{ item.card ? item.card.name : '' }}</span>
                    <span class="time">创建于{{ item.created_at }}</span>
                  </div>
                  <div class="mt15 table-body-body">
                    <div class="over-text" v-html="getValue(item.content)"></div>
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </el-scrollbar>
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
      <default-page :index="14" :min-height="420" />
    </div>
    <detail-examine ref="detailExamine" @getList="getTableData" :type="type" />
    <verify-opinion ref="verifyOpinion" @ok="getTableData" />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { TABLE_HEIGHT } from '@/constants/tableSize.ts'
import { approveApplyApi, approveConfigSearchApi } from '@/api/business.ts'
import oaFromBox from '@/components/common/oaFromBox'
export default {
  name: 'Examine',
  components: {
    oaFromBox,
    detailExamine: defineAsyncComponent(() => import('@/views/user/examine/components/detailExamine')),
    verifyOpinion: defineAsyncComponent(() => import('@/views/user/examine/components/verifyOpinion')),
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage'))
  },
  props: ['examineActive'],

  data() {
    return {
      TABLE_HEIGHT,
      type: 1,
      tableData: [],
      listType: [],
      loading: false,
      search: [
        {
          field_name: '人员',
          field_name_en: 'name',
          form_value: 'input'
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
          form_value: 'date_picker',
          data_dict: []
        }
      ],

      where: {
        page: 1,
        limit: 15,
        types: 1,
        time: '',
        status: '',
        approve_id: '',
        name: '',
        verify_status: ''
      },
      total: 0
    }
  },
  watch: {
    examineActive(val) {
      this.where.verify_status = val
      this.where.page = 1
      this.getTableData()
      this.getConfigSearch(1)
      if (![1, 4].includes(val)) {
        if (this.search.length == 3) {
          this.search.splice(2, 0, {
            field_name: '审批状态',
            field_name_en: 'status',
            form_value: 'select',
            data_dict: [
              { name: '全部', id: '' },
              { name: '待审核', id: 0 },
              { name: '已通过', id: 1 },
              { name: '已拒绝', id: 2 },
              { name: '已撤销', id: -1 }
            ]
          })
        }
      }
      if (val == 1 || val == 4) {
        this.search.splice(2, 1)
      }
    }
  },
  created() {
    this.where.verify_status = this.examineActive
    this.getTableData()
    this.getConfigSearch(1)
  },
  methods: {
    confirmData(data) {
      if (data === 'reset') {
        this.where = {
          page: 1,
          limit: 15,
          types: 1,
          time: '',
          status: '',
          approve_id: '',
          name: '',
          verify_status: this.examineActive
        }
      } else {
        this.where = { ...this.where, ...data }
      }
      this.getTableData()
    },
    dropdownFn(item) {},
    restFn() {
      this.where = {
        page: 1,
        limit: 15,
        types: 1,
        time: '',
        status: '',
        approve_id: '',
        name: '',
        verify_status: this.examineActive
      }
      this.getTableData()
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    async getConfigSearch(id) {
      const result = await approveConfigSearchApi(id)
      this.listType = result.data
      this.search[1].data_dict = this.listType
      // this.search[1].data_dict.unshift({ name: '全部', id: '' })
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    async getTableData() {
      this.loading = true
      try {
        const data = { ...this.where }
        if (data.verify_status == '5') {
          data.verify_status = ''
        }
        const result = await approveApplyApi(data)
        this.tableData = Array.isArray(result?.data?.list) ? result.data.list : []
        this.total = result?.data?.count ?? 0
      } catch {
        console.error('获取审批数据失败')
        this.tableData = []
        this.total = 0
      } finally {
        this.loading = false
      }
    },
    getApproveIcon(icon) {
      let str = ''
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
    // 详情
    handleDetail(row) {
      this.$refs.detailExamine.openBox(row, 'revoke')
    },
    judge(row) {
      if (row.card) {
        return row.card.avatar.includes('https')
      }
    },
    // 同意
    handleAgree(row) {
      this.$refs.verifyOpinion.openBox(row.id, 1)
    },
    // 拒绝
    handleRefuse(row) {
      this.$refs.verifyOpinion.openBox(row.id, 0)
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
:deep(.el-scrollbar__wrap) {
  overflow-x: hidden;
}
:deep(.el-card__body) {
  padding-right: 0;
}
.img-body {
  width: 30px;
  height: 30px;
  border-radius: 50%;
}
.selIcon {
  width: 25px;
  height: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  // line-height: 25px;
  // display: inline-block;
  // text-align: center;
  border-radius: 3px;
}
.iconfont {
  font-size: 13px;
  color: #fff;
}
.table-box {
  list-style: none;
  margin: 0px 0px 0 0;
  padding: 0;
  overflow: scroll;
  li {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #eeeeee;
    border: 1px solid #f0f2f5;
    padding: 14px;
    border-radius: 3px;
    .table-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      .table-title {
        display: flex;
        align-items: center;
        .table-title-left {
          width: 38px;
          // i {
          //   color: #fff;
          //   font-size: 28px;
          // }
        }
        .table-title-right {
          width: calc(100% - 38px);
          display: flex;
          .title {
            font-weight: bold;
            font-size: 13px;
            line-height: 20px;
          }
          .tag {
            margin-left: 5px;
            :deep(.el-tag) {
              background: #fff;
            }
          }
        }
      }
    }
    .table-body {
      p {
        margin: 0;
        padding: 0;
      }
      padding: 16px 15px;
      background-color: rgba(24, 144, 255, 0.04);
      .table-body-title {
        display: flex;
      }
      .table-body-left {
        width: 40px;
      }
      .table-body-right {
        width: calc(100% - 40px);
        font-size: 13px;
        color: rgba(119, 119, 119, 0.85);
        .title {
          color: #333;
          font-size: 14px;
          margin-top: 6px;
          margin-bottom: 10px;
          .time {
            padding-left: 10px;
            font-size: 13px;
            color: rgba(119, 119, 119, 0.85);
          }
        }
        .table-body-body {
          div {
            width: 100%;
          }
          span {
            padding-right: 15px;
          }
        }
      }
    }
  }
}
</style>
