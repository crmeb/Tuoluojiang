<template>
  <div class="current-list" :class="{ 'is-fit-height': fitHeight }">
    <!-- 创建考核页面 -->
    <div class="height">
      <div class="flex-between h32 mt20">
        <div class="title-16">绩效考核</div>
        <div class="flex">
          <el-button @click="selectTemplate">套用考核模板</el-button>
          <el-button :disabled="dimensionMax <= 0" type="primary" @click="addAssess">
            添加考核维度
          </el-button>
        </div>
      </div>
      <!-- 筛选条件 -->
      <div class="flex-between mt20 mb10">
        <div v-if="tempButton < 2" class="flex">
          <el-select
            v-model="tableFrom.period"
            :disabled="isEdit && strType !== 'copy'"
            class="mr10"
            placeholder="考核类型"
            style="width: 160px"
            @change="changePeriod"
          >
            <el-option v-for="(item, indx) in periodOption" :key="indx" :label="item.label" :value="item.value" />
          </el-select>
          <div v-if="tableFrom.period !== ''" class="mr10" style="width: 250px">
            <el-date-picker
              v-if="tableFrom.period === 1 || tableFrom.period === 2"
              ref="getDateValue"
              v-model="tableFrom.time"
              :format="dateArray[tableFrom.period - 1].format"
              :placeholder="dateArray[tableFrom.period - 1].text"
              :type="dateArray[tableFrom.period - 1].type"
              style="flex: 1; width: 100%"
              @change="getDateValue"
            />

            <el-date-picker
              v-else-if="tableFrom.period === 3"
              ref="getDateValue"
              v-model="tableFrom.time"
              :format="dateArray[4].format"
              :placeholder="dateArray[4].text"
              :type="dateArray[4].type"
              @change="getDateValue"
            />
            <dateQuarter v-if="quarterBtn" ref="dateQuarter" :get-value="getQuarterDate" :half-year-btn="halfYearBtn" />
          </div>

          <div style="width: 200px">
            <selectMember
              :disabled="isEdit && strType !== 'copy'"
              :enable-ids="testUserEnableIds"
              :value="testUserList"
              placeholder="选择被考核人"
              is-avatar
              is-search
              @getSelectList="handleTestUserChange"
            />
          </div>
        </div>
        <div class="flex">
          <div v-if="enterprise.compute_mode === 0" class="current-button-left text-right">
            <span>总权重:</span><span> 100% </span> <span class="pl15"> 当前总权重: </span
            ><span :class="handleTotalWeight() !== 100 ? 'color-danger' : 'color-default'">{{
              handleTotalWeight()
            }}</span>
            %
          </div>

          <div v-else class="current-button-left text-right">
            <span>总分:</span><span> {{ enterprise.maxScore }}</span> <span class="pl15"> 当前总分: </span
            ><span :class="handleTotalWeight() !== enterprise.maxScore ? 'color-danger' : 'color-default'">{{
              handleTotalWeight()
            }}</span>
          </div>
        </div>
      </div>

      <div class="box">
        <el-scrollbar style="height: 100%">
          <div v-for="(itm, index) in tableData" :key="index" class="current-table">
            <div class="current-table-title">
              <p>
                <span>
                  <input
                    v-model="itm.name"
                    :autofocus="true"
                    :style="{ width: text(itm.name) }"
                    class="current-title-input"
                    type="text"
                  />
                </span>
                <span>
                  <span>
                    {{ enterprise.compute_mode ? '维度分数' : '维度权重' }}
                  </span>
                  <el-input-number v-model="itm.ratio" :controls="false" :max="itm.weight" :min="0" />
                  <span v-if="!enterprise.compute_mode">%</span>
                </span>
                <span v-if="!enterprise.compute_mode">
                  <span> 当前权重 </span>
                  <span :class="handleWeight(itm.target) !== 100 ? 'color-danger' : 'color-default'">{{
                    handleWeight(itm.target)
                  }}</span>
                  <span>%</span>
                </span>
              </p>
              <p class="text-right">
                <i class="el-icon-edit" @click="editRatioName(index)" />
                <i class="el-icon-delete ml6" @click="deleteTargetList(index)" />
              </p>
            </div>

            <el-table :data="itm.target" default-expand-all row-key="id" style="width: 100%">
              <el-table-column min-width="160" prop="name">
                <template #header> 指标名称 <span class="red">*</span></template>
                <template #default="scope">
                  <el-input
                    v-if="strType !== 'check'"
                    v-model="scope.row.name"
                    placeholder="输入指标名称"
                    autosize
                    maxlength="255"
                    type="textarea"
                    class="oa-input-borderless"
                  />
                  <span v-else>{{ scope.row.name }}</span>
                </template>
              </el-table-column>
              <el-table-column min-width="330" prop="content">
                <template #header> 指标说明 <span class="red">*</span></template>
                <template #default="scope">
                  <el-input
                    v-if="strType !== 'check'"
                    v-model="scope.row.content"
                    placeholder="输入指标说明"
                    autosize
                    maxlength="255"
                    type="textarea"
                    class="oa-input-borderless"
                  />
                  <span v-else>{{ scope.row.content }}</span>
                </template>
              </el-table-column>
              <el-table-column min-width="330" prop="info">
                <template #header> 评分标准 <span class="red">*</span></template>
                <template #default="scope">
                  <el-input
                    v-if="strType !== 'check'"
                    v-model="scope.row.info"
                    autosize
                    maxlength="255"
                    placeholder="输入评分标准"
                    type="textarea"
                    class="oa-input-borderless"
                  />
                  <span v-else>{{ scope.row.info }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="weight" width="180">
                <template #header
                  >{{ enterprise.compute_mode ? '分数' : '权重(%)' }}
                  <span class="red">*</span>
                </template>
                <template #default="scope">
                  <el-input-number
                    v-if="strType !== 'check'"
                    v-model="scope.row.ratio"
                    :max="1000"
                    :min="0"
                    :step="5"
                    class="current-task"
                    controls-position="right"
                    style="width: 150px"
                    @change="handleScore(index)"
                  ></el-input-number>
                  <span v-else>{{ scope.row.ratio }}</span>
                </template>
              </el-table-column>
              <el-table-column label="操作" fixed="right" prop="address" width="120">
                <template #default="scope">
                  <el-button
                    class="current-delete"
                    icon="el-icon-delete"
                    type="primary" link
                    @click="handleDelete(index, scope.$index)"
                  />
                </template>
              </el-table-column>
            </el-table>
            <div class="current-bottom">
              <el-row>
                <el-col :lg="12">
                  <el-button type="primary" link @click="addTarget(index)">添加指标</el-button>
                  <el-button type="primary" link @click="selectTarget(index)">选择指标库</el-button>
                </el-col>
              </el-row>
            </div>
          </div>
        </el-scrollbar>
      </div>

      <div v-if="strType !== 'check'" class="current-footer text-center">
        <el-button v-if="addButton" @click="addTemp">
          {{ tempButton === 1 ? '设置模板' : '编辑模板' }}
        </el-button>
        <el-button v-if="!addButton" @click="addTemplate">
          {{ tempButton === 1 ? '设置模板' : '编辑模板' }}
        </el-button>
        <el-button :loading="saveLoading" type="primary" @click="addPreserve()"> 保存 </el-button>
      </div>
    </div>
    <!--添加考核维度-->
    <el-dialog
      :before-close="handleClose"
      title="添加考核维度"
      v-model="dialogVisible"
      :close-on-click-modal="false"
      width="560px"
    >
      <div class="current-dialog mb15">
        <span>维度名称：</span>
        <el-input v-model="from.name" type="text" />
      </div>
      <div v-if="!enterprise.compute_mode" class="current-dialog">
        <span>维度权重：</span>
        <div>
          <el-input-number
            v-model="from.ratio"
            :controls="false"
            :max="editDimension == 1 ? dimensionMax : 100"
            :min="1"
            style="width: 100%"
          />
          <span v-if="!enterprise.compute_mode" class="current-dialog-icon">%</span>
        </div>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="clickCancel">取消</el-button>
          <el-button type="primary" @click="addOk">确定</el-button>
        </span>
      </template>
    </el-dialog>
    <!--设置模板内容-->
    <el-dialog
      :before-close="templateClose"
      title="设置模板"
      :close-on-click-modal="false"
      v-model="dialogTemplate"
      width="560px"
    >
      <div class="current-dialog mb15">
        <span>模板名称：</span>
        <el-input v-model="info.name" type="text" />
      </div>
      <div class="current-dialog mb15">
        <span>模板简介：</span>
        <el-input v-model="info.info" type="text" />
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="cancelTemplate">取消</el-button>
          <el-button :loading="tempLoading" type="primary" @click="templateOk">确定</el-button>
        </span>
      </template>
    </el-dialog>
    <selectTarget ref="selectTarget" title="指标模板库" @dialogChangeDada="dialogChangeDada" />
    <selectTemplate
      ref="selectTemplate"
      :remind-button="remindButton"
      title="考核模板库"
      @templateChange="templateChange"
    />
  </div>
</template>

<script>
import { useUserStore } from '@/stores/user.ts'
import { defineAsyncComponent } from 'vue'
import dayjs from 'dayjs'
import { modalSure } from '@/libs/public.ts'
import Common from '@/components/user/accessCommon.ts'
import { userAssessEditApi, userAssessInfo, userAssessCreateApi } from '@/api/user.ts'
import { assessPlanPeriodApi, assessTargetCateListApi, assessTemplatePutApi } from '@/api/enterprise.ts'

export default {
  emits: ['handleGoal'],
  name: 'GoalSetting',
  components: {
    selectTarget: defineAsyncComponent(() => import('@/components/form-common/dialog-target')),
    selectTemplate: defineAsyncComponent(() => import('@/components/form-common/dialog-template')),
    dateQuarter: defineAsyncComponent(() => import('@/components/form-common/select-dateQuarter')),
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member'))
  },
  props: {
    id: {
      type: [Number, String],
      default: 0
    },
    addButton: {
      // 添加模板判断
      type: Boolean,
      default: false
    },
    rowData: {
      type: Object,
      default: {}
    },
    strType: {
      type: String,
      default: ''
    },
    fitHeight: {
      // 由父容器提供确定高度时，内部改为撑满剩余高度 + 底部按钮吸底
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      tableData: [],
      dialogVisible: false,
      dialogTemplate: false,
      from: {
        name: '',
        ratio: 100
      },
      rowId: 0,
      info: {
        name: '',
        info: '',
        cate_id: ''
      },
      index: '',
      is_draft: 0,
      is_temp: 0,
      type: 1,
      department: [],
      options: [],
      temp: [],
      editDimension: 1,
      editDimensionIndex: -1,
      remindButton: false,
      periodOptions: Common.periodOptions,
      periodOption: [{ value: '', label: '选择考核类型' }],
      dateArray: [
        { value: 1, type: 'week', text: '选择周', format: 'YYYY 第 ww 周' },
        { value: 2, type: 'month', text: '选择月份', format: 'YYYY-MM' },
        { value: 4, type: '' },
        { value: 5, type: '' },
        { value: 3, type: 'year', text: '选择年份', format: 'YYYY' }
      ],
      quarterBtn: false,
      halfYearBtn: false,
      tableFrom: {
        period: '',
        time: '',
        date: '',
        testUid: []
      },
      accessInfo: [],
      pickerOptions0: {
        disabledDate(time) {
          return time.getTime() < Date.now() - 86400000
        },
        firstDayOfWeek: 1
      },
      frameUserOption: [],
      testUserList: [], // 已选被考核人（含姓名头像，供人员选择组件回显）
      assessPlan: [],
      isEdit: false,
      tempButton: 1,
      tempData: {},
      saveLoading: false,
      tempLoading: false,
      userInfo: useUserStore().userInfo,
      enterprise: useUserStore().enterprise
    }
  },
  computed: {
    // 当前考核类型下允许被考核的人员id，人员选择组件据此过滤组织架构
    testUserEnableIds() {
      return this.frameUserOption.map((item) => item.id)
    },
    dimensionMax() {
      var max = 100
      if (this.enterprise.compute_mode) {
        max = this.enterprise.maxScore
      }
      if (this.tableData && this.tableData.length > 0) {
        this.tableData.forEach((value) => {
          max -= value.ratio
        })
      }
      return max
    },
    text() {
      return function (value) {
        if (!value) {
          return '100%'
        } else {
          return String(value).length * 13 + 10 + 'px'
        }
      }
    }
  },
  watch: {
    rowData: {
      handler() {
        if(this.rowData.id){
          this.rowId = this.rowData.id || this.id
            this.getTableData()
        }else {
          this.isEdit = false
          this.tableData = []
        }
      
      },
      deep: true
    }
  },

  mounted() {
    // 获取考核类型
    this.assessPlanPeriod()
    this.rowId = this.rowData.id || this.id
    this.getTableData()
  },
  methods: {
    handleDelete(index, $index) {
      this.tableData[index].target.splice($index, 1)
      this.handleScore(index)
    },
    addAssess() {
      this.editDimension = 1
      this.dialogVisible = true
    },
    getTableData() {
      if (this.rowId > 0) {
        userAssessInfo(this.rowId).then((res) => {
          res.data === undefined ? (this.tableData = []) : (this.tableData = res.data.info)
          res.data === undefined ? (this.accessInfo = []) : (this.accessInfo = res.data.assessInfo)
          if (this.rowId !== 0) {
            this.tableFrom.period = this.accessInfo.period
            this.tableFrom.date = this.accessInfo.time
            this.tableFrom.time = this.accessInfo.time
            this.isEdit = true
            if (this.strType !== 'copy') {
              this.frameUserOption = [this.accessInfo.test]
              this.tableFrom.testUid = [this.accessInfo.test.id]
              this.testUserList = [{ ...this.accessInfo.test, value: this.accessInfo.test.id }]
            } else {
              this.tableFrom.testUid = []
              this.testUserList = []
              this.tableFrom.period = ''
            }
          }
        })
      }
    },
    assessPlanPeriod() {
      assessPlanPeriodApi().then((res) => {
        res.data == undefined ? (this.assessPlan = []) : (this.assessPlan = res.data)
        if (this.assessPlan.length === 0) {
          this.$message.error('请开启考核类型')
        } else {
          for (let i = 0; i < this.assessPlan.length; i++) {
            var index = this.periodOptions.findIndex((item) => {
              return item.value === this.assessPlan[i].period
            })
            this.periodOption.push(this.periodOptions[index])
          }
        }
      })
    },
    async getTargetCate() {
      const data = {
        types: this.type
      }
      this.options = []
      const result = await assessTargetCateListApi(data)
      result.data == undefined ? (this.department = []) : (this.department = result.data)
      this.department.forEach((value) => {
        this.options.push({ value: value.id, label: value.name })
      })
    },
    handleClose() {
      this.clickCancel()
    },
    addOk() {
      if (this.from.name == '') {
        this.$message.error('考核维度名称为空')
      } else if (this.from.ratio == '') {
        this.$message.error('考核维度权重为空')
      } else {
        if (this.editDimension == 1) {
          this.tableData.push({
            name: this.from.name,
            ratio: !this.enterprise.compute_mode ? this.from.ratio : 0,
            target: []
          })
        } else {
          this.tableData[this.editDimensionIndex].name = this.from.name
          this.tableData[this.editDimensionIndex].ratio = this.from.ratio
        }
        this.clickCancel()
      }
    },
    handleScore(index) {
      if (!this.enterprise.compute_mode) {
        return false
      } else {
        this.tableData[index].ratio = 0
        this.tableData[index].target.forEach((value) => {
          this.tableData[index].ratio += value.ratio ? value.ratio : 0
        })
      }
    },
    setTemplate() {
      if (this.tempButton === 2) {
        this.info.name = this.tempData.name
        this.info.info = this.tempData.info
      }
      this.dialogTemplate = true
    },
    addTemplate() {
      this.is_temp = 1
      const res = this.judge(1)
      if (res.length > 0) {
        this.setTemplate()
      }
    },
    cancelTemplate() {
      this.dialogTemplate = false
      this.info.name = ''
      this.info.info = ''
      this.info.cate_id = ''
    },
    templateClose() {
      this.cancelTemplate()
    },
    templateOk() {
      if (this.info.name === '') {
        this.$message.error('考核模板名称为空')
      } else if (this.info.info === '') {
        this.$message.error('考核模板内容为空')
      } else {
        if (this.tempButton === 1) {
          if (this.isEdit) {
            this.preserveTemp()
          } else {
            this.preserve()
          }
        } else {
          this.putTemplate()
        }
      }
    },
    clickCancel() {
      this.dialogVisible = false
      this.editDimension = 1
      this.editDimensionIndex = -1
      this.from.name = ''
      this.from.ratio = this.dimensionMax
    },
    addTarget(index) {
      const len = this.tableData[index].target.length - 1
      if (len < 0) {
        this.tableData[index].target = [{ name: '', content: '', ratio: 10 }]
        this.handleScore(index)
      } else {
        if (this.tableData[index].target[len].name == '') {
          this.$message.error('填写指标名称')
        } else if (this.tableData[index].target[len].content == '') {
          this.$message.error('填写指标内容')
        } else if (this.tableData[index].target[len].ratio == '') {
          this.$message.error('填写指标权重')
        } else {
          this.tableData[index].target.push({ name: '', content: '', ratio: 10 })
          this.handleScore(index)
        }
      }
    },
    selectTarget(index) {
      this.index = index
      this.$refs.selectTarget.openDialog()
    },
    selectTemplate() {
      this.tableData && this.tableData.length > 0 ? (this.remindButton = true) : (this.remindButton = false)
      this.$refs.selectTemplate.openDialog()
    },
    dialogChangeDada(data) {
      data.forEach((res) => {
        this.tableData[this.index].target.push({ name: res.name, content: res.content, info: res.info, ratio: '' })
      })
    },
    deleteTargetList(index) {
      this.tableData.splice(index, 1)
    },
    addTemp() {
      this.is_temp = 1
      const res = this.judge()
      if (res.length > 0) {
        this.setTemplate()
      }
    },

    addPreserve(type, status) {
      this.is_draft = 1
      this.is_temp = 0
      if (this.tempButton !== 1) {
        this.putTemplate()
      } else {
        this.preserve()
      }
    },
    editPreserve(type, status) {
      if (type === 1) {
        this.is_draft = 0
        modalSure('你确定要提交考核吗').then(() => {
          this.preserveTemp(status)
        })
      } else {
        this.is_draft = 1
        this.preserveTemp()
      }
    },
    // 添加保存绩效
    preserve(status) {
      if (this.handleTotalWeight() != this.enterprise.maxScore) {
        this.$message.error('当前总分和总分不一致')
        return false
      }
      const res = this.judge()
      if (res.length > 0) {
        const data = {
          is_submit: 0,
          is_draft: status == 'only' ? 1 : this.is_draft,
          is_temp: this.is_temp,
          period: this.tableFrom.period,
          name: this.info.name,
          info: this.info.info,
          time: this.tableFrom.date || this.tableFrom.time,
          test_uid: this.tableFrom.testUid,
          types: this.enterprise.compute_mode,
          data: res
        }
        this.is_temp === 0 ? (this.saveLoading = true) : (this.tempLoading = true)
        if (this.rowId > 0 && this.strType != 'copy' && this.tempButton !== 2) {
          this.saveLoading = true
          userAssessEditApi(this.rowId, data)
            .then((res) => {
              if (res.status == 200) {
                this.is_temp === 0 ? (this.saveLoading = false) : (this.tempLoading = false)
                if (this.is_temp !== 1) {
                  this.clickReturn()
                }
                this.is_draft = 0
                this.is_temp = 0
                this.cancelTemplate()
                this.saveLoading = false
              } else {
                this.saveLoading = false
              }
            })
            .catch((error) => {
              this.saveLoading = false
              this.is_temp === 0 ? (this.saveLoading = false) : (this.tempLoading = false)
            })
        } else {
          this.saveLoading = true
          userAssessCreateApi(data)
            .then((res) => {
              if (res.status == 200) {
                this.is_temp === 0 ? (this.saveLoading = false) : (this.tempLoading = false)
                if (this.is_temp !== 1) {
                  this.clickReturn()
                }
                this.is_draft = 0
                this.is_temp = 0
                this.cancelTemplate()
              } else {
                this.saveLoading = false
              }
            })
            .catch((error) => {
              this.saveLoading = false
              this.is_temp === 0 ? (this.saveLoading = false) : (this.tempLoading = false)
            })
        }
      }
    },
    // 保存
    preserveTemp(status) {
      const res = this.judge()

      if (res.length > 0) {
        const data = {
          is_temp: this.is_temp,
          is_draft: status == 'only' ? 1 : this.is_draft,
          name: this.info.name,
          info: this.info.info,
          types: this.enterprise.compute_mode,
          data: res
        }
        this.saveLoading = true
        userAssessCreateApi(data)
          .then((res) => {
            this.saveLoading = false
            if (this.is_temp !== 1) {
              this.clickReturn()
            }
            this.is_temp = 0
            this.cancelTemplate()
          })
          .catch((error) => {
            this.saveLoading = false
          })
      }
    },
    putTemplate() {
      const res = this.judge(2)
      if (res.length > 0) {
        const data = {
          is_temp: 1,
          is_draft: this.is_draft,
          name: this.tempData.name,
          info: this.tempData.info,
          types: this.enterprise.compute_mode,
          data: res,
          cate_id: this.tempData.cate_id
        }
        this.tempLoading = true
        assessTemplatePutApi(this.tempData.id, data)
          .then((res) => {
            this.tempLoading = false
            if (this.is_draft === 0 || this.is_temp !== 1) {
              this.clickReturn()
            }
            this.is_temp = 0
            this.cancelTemplate()
          })
          .catch((error) => {
            this.tempLoading = false
          })
      }
    },
    clickReturn() {
      this.$emit('handleGoal')
    },
    judge(type) {
      var res = []
      if (type !== 2) {
        if (this.tableData.length <= 0) {
          this.$message.error('考核维度不能为空')
        } else if (this.dimensionMax > 0 && this.is_draft === 0 && this.is_temp === 0) {
          this.$message.error('考核维度权重必须为100')
        } else if (this.tableFrom.period === '' && this.is_temp === 0) {
          this.$message.error('考核类型不能为空')
        } else if (this.tableFrom.date === '' && !this.isEdit && this.is_temp === 0) {
          this.$message.error('考核时间不能为空')
        } else if (this.tableFrom.testUid.length === 0 && this.is_temp === 0) {
          this.$message.error('被考核人不能为空')
        }
      }
      if (this.tableData.length > 0) {
        for (let i = 0; i < this.tableData.length; i++) {
          if (this.tableData[i].name == '') {
            this.$message.error('考核维度名称不能为空')
            break
          }
          if (this.tableData[i].ratio <= 0 && this.is_draft === 0) {
            this.$message.error('考核维度权重不能为零')
            break
          }
          if (this.tableData[i].target.length <= 0) {
            this.$message.error('考核指标不能空')
            break
          }
          const target = []
          for (let j = 0; j < this.tableData[i].target.length; j++) {
            if (this.tableData[i].target[j].name == '') {
              this.$message.error('指标名称不能为空')
              break
            }
            if (this.tableData[i].target[j].content == '') {
              this.$message.error('指标内容不能为空')
              break
            }
            const info = this.tableData[i].target[j].info
            if (info == '' || info == undefined) {
              this.$message.error('评分标准不能为空')
              break
            }
            if (this.tableData[i].target[j].ratio <= 0 && this.is_draft === 0) {
              this.$message.error('指标权重不能为零')
              break
            }

            target.push({
              name: this.tableData[i].target[j].name,
              content: this.tableData[i].target[j].content,
              info: this.tableData[i].target[j].info,
              ratio: this.tableData[i].target[j].ratio,
              id: this.tableData[i].target[j].id || 0
            })
          }
          if (target.length <= 0) {
            break
          }

          res.push({
            name: this.tableData[i].name,
            ratio: this.tableData[i].ratio,
            id: this.tableData[i].id,
            target: target
          })
          if (res[i].target.length !== this.tableData[i].target.length) {
            res = []
          }
        }
      }
      if (res.length > 0) {
        return res
      } else {
        return []
      }
    },

    sumTargetScore(list) {
      return list.reduce((sum, item) => {
        return sum + (item.target || []).reduce((total, target) => total + (Number(target.ratio) || 0), 0)
      }, 0)
    },
    sumDimensionScore(list) {
      return list.reduce((sum, item) => sum + (Number(item.ratio) || 0), 0)
    },
    scaleScoreTemplate(list, totalScore) {
      const sourceTotal = this.sumDimensionScore(list) || this.sumTargetScore(list)
      if (!sourceTotal || !totalScore) return list
      let dimensionTotal = 0
      list.forEach((item, index) => {
        const dimensionSource = Number(item.ratio) || this.sumTargetScore([item]) || 0
        const targetSource = this.sumTargetScore([item]) || dimensionSource
        item.ratio =
          index === list.length - 1 ? totalScore - dimensionTotal : Math.round((dimensionSource * totalScore) / sourceTotal)
        dimensionTotal += index === list.length - 1 ? 0 : item.ratio

        let targetTotal = 0
        const targetList = item.target || []
        targetList.forEach((target, targetIndex) => {
          target.ratio =
            !targetSource
              ? 0
              : targetIndex === targetList.length - 1
              ? item.ratio - targetTotal
              : Math.round(((Number(target.ratio) || 0) * item.ratio) / targetSource)
          targetTotal += targetIndex === targetList.length - 1 ? 0 : target.ratio
        })
      })
      return list
    },
    weightTemplateToScore(list) {
      const maxScore = Number(this.enterprise.maxScore) || 0
      let dimensionTotal = 0
      list.forEach((item, index) => {
        item.ratio =
          index === list.length - 1
            ? maxScore - dimensionTotal
            : Math.round(((Number(item.ratio) || 0) * maxScore) / 100)
        dimensionTotal += index === list.length - 1 ? 0 : item.ratio

        let targetTotal = 0
        const targetList = item.target || []
        targetList.forEach((target, targetIndex) => {
          target.ratio =
            targetIndex === targetList.length - 1
              ? item.ratio - targetTotal
              : Math.round((item.ratio * (Number(target.ratio) || 0)) / 100)
          targetTotal += targetIndex === targetList.length - 1 ? 0 : target.ratio
        })
      })
      return list
    },
    scoreTemplateToWeight(list) {
      const sourceTotal = this.sumDimensionScore(list) || this.sumTargetScore(list)
      if (!sourceTotal) return list
      let dimensionTotal = 0
      list.forEach((item, index) => {
        const dimensionSource = Number(item.ratio) || this.sumTargetScore([item]) || 0
        const targetSource = this.sumTargetScore([item]) || dimensionSource
        item.ratio =
          index === list.length - 1 ? 100 - dimensionTotal : Math.round((dimensionSource * 100) / sourceTotal)
        dimensionTotal += index === list.length - 1 ? 0 : item.ratio

        let targetTotal = 0
        const targetList = item.target || []
        targetList.forEach((target, targetIndex) => {
          target.ratio =
            !targetSource
              ? 0
              : targetIndex === targetList.length - 1
              ? 100 - targetTotal
              : Math.round(((Number(target.ratio) || 0) * 100) / targetSource)
          targetTotal += targetIndex === targetList.length - 1 ? 0 : target.ratio
        })
      })
      return list
    },
    formatTemplateScore(info, templateTypes) {
      const list = JSON.parse(JSON.stringify(info || []))
      const computeMode = Number(this.enterprise.compute_mode)
      const types = templateTypes === undefined || templateTypes === null ? computeMode : Number(templateTypes)
      const maxScore = Number(this.enterprise.maxScore) || 0

      if (computeMode === 1 && types === 1) {
        const templateScore = this.sumDimensionScore(list) || this.sumTargetScore(list)
        return templateScore === maxScore ? list : this.scaleScoreTemplate(list, maxScore)
      }
      if (computeMode === 1 && types === 0) return this.weightTemplateToScore(list)
      if (computeMode === 0 && types === 1) return this.scoreTemplateToWeight(list)
      return list
    },
    // 选用考核模板回调
    templateChange(data) {
      if (data.edit === 1) {
        const template = Array.isArray(data.data) ? { info: data.data } : data.data || {}
        this.tempButton = 1
        this.tableData = this.formatTemplateScore(template.info, template.temp && template.temp.types)
      } else {
        this.tempButton = 2
        this.tempData = data.data.temp
        this.tableData = data.data.info
      }
    },
    editRatioName(index) {
      this.editDimension = 2
      this.editDimensionIndex = index
      this.from.name = this.tableData[index].name
      this.from.ratio = this.tableData[index].ratio
      this.dialogVisible = true
    },
    changePeriod(e) {
      if (e == 1) {
        var selectedWeek = dayjs(new Date()).isoWeek()
        var selectedWeekFirst = dayjs().isoWeek(selectedWeek).weekday(1).format('YYYY/MM/DD')
        this.pickerOptions0 = {
          disabledDate(time) {
            return time.getTime() < new Date(selectedWeekFirst)
          },
          firstDayOfWeek: 1
        }
      } else {
        this.pickerOptions0 = {
          disabledDate(time) {
            return time.getTime() < Date.now() - 86400000
          },
          firstDayOfWeek: 1
        }
      }
      // 切换季度半年考核
      this.tableFrom.date = ''
      if (e == 4) {
        this.quarterBtn = true
        this.halfYearBtn = true
        this.$nextTick(() => {
          this.$refs.dateQuarter.showValue = ''
        })
      } else if (e == 5) {
        this.quarterBtn = true
        this.halfYearBtn = false
        this.$nextTick(() => {
          this.$refs.dateQuarter.showValue = ''
        })
      } else {
        this.quarterBtn = false
      }
      // 切换选择被考核人
      var index = this.assessPlan.findIndex((item) => {
        return item.period === e
      })
      this.tableFrom.testUid = []
      this.testUserList = []
      this.frameUserOption = index > -1 && this.assessPlan[index].test ? this.assessPlan[index].test : []
    },

    // 选择被考核人回调
    handleTestUserChange(list) {
      this.testUserList = list
      this.tableFrom.testUid = list.map((item) => (item.value !== undefined && item.value !== null ? item.value : item.id))
    },
    getDateValue() {
      if (this.tableFrom.period == 1) {
        this.tableFrom.date = dayjs(this.tableFrom.time).format('YYYY-WW')
      } else if (this.tableFrom.period == 2) {
        this.tableFrom.date = dayjs(this.tableFrom.time).format('YYYY-MM')
      } else if (this.tableFrom.period == 3) {
        this.tableFrom.date = dayjs(this.tableFrom.time).format('YYYY')
      }
    },
    getQuarterDate(data) {
      this.tableFrom.date = data
    },
    // 当前每一项得分计算
    handleWeight(target) {
      let max = 0
      if (target.length > 0) {
        target.forEach((value) => {
          max += value.ratio
        })
        return max
      } else {
        return 0
      }
    },
    // 当前总得分
    handleTotalWeight() {
      let max = 0
      if (this.tableData && this.tableData.length > 0) {
        this.tableData.forEach((value) => {
          max += value.ratio
        })
        return max
      } else {
        return 0
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.box {
  height: calc(100vh - 364px);
}
// 父容器已提供确定高度：表格区自适应占满剩余空间，底部按钮吸底
.current-list.is-fit-height {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;

  > .height {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;

    > .flex-between {
      flex-shrink: 0;
    }
  }

  .box {
    flex: 1;
    min-height: 0;
    height: auto;
  }

  .current-footer {
    flex-shrink: 0;
    background-color: #fff;
  }
}
.red {
  color: #ff0000;
}
.text-right {
  text-align: right;
}
:deep(.el-scrollbar__wrap) {
  overflow-x: hidden;
}
.form-list-con {
  :deep(.el-input__wrapper) {
    min-height: 36px;
  }
}
:deep(.el-textarea__inner) {
  padding-left: 0;
  padding-right: 0;
}
.current-button {
  margin-right: 12px;
  :deep(.el-row) {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .current-button-left {
    height: 32px;
    line-height: 32px;
  }
}
.current-table {
  :deep(.el-table__empty-block) {
    width: 0;
    height: 0;
    display: none;
  }
  margin: 0 12px 30px 0;
  .current-table-title {
    display: flex;
    align-items: center;

    .current-title-input {
      background-color: transparent;
      font-size: 13px;
      font-weight: bold;
      color: #000000;
      border: none;
    }
    .current-title-input:focus {
      outline-style: none;
    }
    p {
      margin: 0;
      padding: 0;
      width: 50%;
    }
    p:last-of-type {
      padding-right: 10px;
      i {
        cursor: pointer;
      }
    }
    padding: 10px 0;
    background-color: #f7fbff;
    span {
      font-size: 12px;
      font-family: PingFang SC;
      font-weight: 400;
      line-height: 17px;
      color: rgba(0, 0, 0, 0.5);
    }
    span:first-of-type {
      padding-left: 10px;
    }
  }
  :deep(.el-table th > .cell) {
    font-weight: normal;
    font-size: 13px;
  }
  :deep(.el-table td) {
    padding: 3px 0;
  }
  :deep(.el-input__wrapper) {
    background-color: transparent;
  }
  :deep(.el-input__inner) {
    font-size: 13px;
  }
  :deep(.el-textarea__inner) {
    resize: none;
    overflow-y: hidden;
    background-color: transparent;
  }
  :deep(.el-table--enable-row-hover) {
    .el-table__body tr:hover > td {
      background: transparent;
    }
  }
  .current-delete {
    color: #000000;
  }
  // .current-task {
  //   display: flex;
  //   align-items: center;
  // :deep(.el-input--medium),
  //   .el-input-number--medium {
  //     width: 30px;
  //   }
  // :deep(.el-input__inner) {
  //     width: 100%;
  //     border: 1px;
  //     // background-color: transparent;
  //     padding: 0 0 0 3px;
  //     text-align: left;
  //   }
  // }
}
.current-bottom {
  // border-bottom: 1px solid #dfe6ec;
  padding: 2px 10px;
  button {
    font-size: 13px;
  }
}
.current-footer {
  padding-top: 20px;
  border-top: 1px solid #f0f2f5;
  margin-inline: -20px;
}
.current-dialog {
  display: flex;
  align-items: center;
  position: relative;
  span {
    width: 20%;
    text-align: right;
  }
  div {
    width: 80%;
  }
  .current-dialog-icon {
    position: absolute;
    right: 10px;
    width: auto;
    top: 12px;
  }
  :deep(.el-input__inner) {
    text-align: left;
  }
}
:deep(.el-table th.is-leaf) {
  background-color: #fff !important;
}
:deep(.current-task .el-input-number) {
  border: 1px solid #636363;
  text-align: center;
}
</style>
