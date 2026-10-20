<!-- 人事-办公审批-新建 / 编辑审批流程：编辑时通过 query.id 传审批流程 id -->
<template>
  <div class="box">
    <el-card class="examineCard background-card">
      <el-row>
        <el-col :span="8">
          <el-page-header icon="el-icon-arrow-left" title="返回" :content="id > 0 ? '编辑审批流程' : '添加审批流程页面'" @back="goList" />
        </el-col>
        <el-col :span="16">
          <el-tabs v-model="activeName" class="examineTabs" @tab-change="handleTabChange">
            <el-tab-pane v-for="item in TABS" :key="item.value" :name="item.value">
              <template #label>
                <div>
                  <span class="sp1">{{ item.number }}</span>
                  <span>{{ item.label }}</span>
                </div>
              </template>
            </el-tab-pane>
          </el-tabs>
        </el-col>
      </el-row>
    </el-card>
    <!-- 各配置组件只在 created / mounted 读取 conf，编辑时等详情返回后再渲染 -->
    <div v-if="ready" class="mt14 card-box">
      <!-- 基础设置 -->
      <basic-setting
        v-show="activeName === 'basicSetting'"
        ref="basicSettingRef"
        tab-name="basicSetting"
        :conf="mockData.baseConfig"
      />
      <!-- 表单配置 -->
      <form-setting
        v-show="activeName === 'formSetting'"
        ref="formSettingRef"
        tab-name="formSetting"
        :conf="mockData.formConfig"
      />
      <!-- 规则配置 -->
      <rule-setting
        v-show="activeName === 'ruleSetting'"
        ref="ruleSettingRef"
        tab-name="ruleSetting"
        :conf="mockData.ruleConfig"
      />
      <!-- 流程配置 -->
      <process-setting
        v-show="activeName === 'processSetting'"
        ref="processSettingRef"
        tab-name="processSetting"
        :conf="mockData.processConfig"
      />
    </div>
    <div class="cr-bottom-button">
      <el-button v-if="activeName !== 'basicSetting'" @click="previousStep">上一步</el-button>
      <el-button v-if="activeName !== 'ruleSetting'" @click="nextStep">下一步</el-button>
      <el-button type="primary" :loading="loading" @click="publish">发布</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFlowDesignerStore } from '@/stores/flowDesigner.ts'
import { ElMessage } from 'element-plus'
import { roterPre } from '@/settings.ts'
import { entAddApi, entEditApi, entInfoApi } from '@/api/business.ts'

const BasicSetting = defineAsyncComponent(() => import('../components/basicSetting/index.vue'))
const FormSetting = defineAsyncComponent(() => import('../components/formSetting/index.vue'))
const ProcessSetting = defineAsyncComponent(() => import('../components/processSetting/index.vue'))
const RuleSetting = defineAsyncComponent(() => import('../components/ruleSetting/index.vue'))

defineOptions({ name: 'BusinessExamineEdit' })

type TabName = 'basicSetting' | 'formSetting' | 'processSetting' | 'ruleSetting'

/** 表单设计器里的单个字段（只列出本页用到的属性） */
interface FormProp {
  type: string
  title?: string
  field?: string
  disabled?: boolean
  timeType?: string
  props?: Record<string, any>
  children?: FormProp[]
}

/** 审批流程详情 / 提交参数 */
interface ExamineConfig {
  baseConfig?: Record<string, any>
  formConfig?: { props: FormProp[] } & Record<string, any>
  ruleConfig?: Record<string, any>
  processConfig?: Record<string, any>
}

/** 各配置组件 getData() 校验失败时 reject 的内容 */
interface StepError {
  target?: TabName
  msg?: string
}

const TABS: { label: string; value: TabName; number: number }[] = [
  { label: '基础配置', value: 'basicSetting', number: 1 },
  { label: '表单配置', value: 'formSetting', number: 2 },
  { label: '流程配置', value: 'processSetting', number: 3 },
  { label: '规则配置', value: 'ruleSetting', number: 4 }
]
const STEP_ORDER: TabName[] = ['basicSetting', 'formSetting', 'processSetting', 'ruleSetting']

/** 可作为流程条件的字段类型 */
const CONDITION_TYPES = ['inputNumber', 'radio', 'checkbox', 'select', 'departmentTree', 'timeFrom', 'moneyFrom']

/** 表单中包含这些业务组件时，审批流程归为对应的业务类型（baseConfig.types） */
const BUSINESS_TYPES: Record<string, string> = {
  refillFrom: '2',
  leaveFrom: '1',
  overtimeFrom: '3',
  outFrom: '4',
  tripFrom: '5',
  contractPayment: '6',
  contractRenewal: '7',
  contractExpenditure: '8',
  issueInvoice: '9',
  voidedInvoice: '10',
  contractSigning: '12'
}

const route = useRoute()
const router = useRouter()
const flowDesignerStore = useFlowDesignerStore()

const id = computed(() => Number(route.query.id) || 0)
const activeName = ref<TabName>('basicSetting')
const mockData = ref<ExamineConfig>({})
const ready = ref(false)
const loading = ref(false)

const basicSettingRef = ref<any>(null)
const formSettingRef = ref<any>(null)
const ruleSettingRef = ref<any>(null)
const processSettingRef = ref<any>(null)

onMounted(() => {
  if (id.value > 0) {
    getInfo()
  } else {
    ready.value = true
  }
})

// 编辑：取审批流程详情，数字类字段的限制值转成数字后交给表单设计器
function getInfo() {
  entInfoApi(id.value).then((res: any) => {
    const data: ExamineConfig = res.data || {}
    const props = data.formConfig && data.formConfig.props
    if (props) {
      props.forEach((item) => {
        if ((item.type === 'moneyFrom' || item.type === 'inputNumber') && item.props) {
          ;['max', 'min', 'precision'].forEach((key) => {
            if (item.props![key]) item.props![key] = Number(item.props![key])
          })
        }
      })
    }
    mockData.value = data
    ready.value = true
  })
}

function goList() {
  router.push(`${roterPre}/business/examine/index`)
}

function previousStep() {
  const index = STEP_ORDER.indexOf(activeName.value)
  if (index > 0) handleTabChange(STEP_ORDER[index - 1])
}

function nextStep() {
  const index = STEP_ORDER.indexOf(activeName.value)
  if (index < STEP_ORDER.length - 1) handleTabChange(STEP_ORDER[index + 1])
}

// 切到流程配置时，把表单里可作为条件的字段同步到流程设计器，供条件节点选择
function handleTabChange(name: string | number) {
  activeName.value = name as TabName
  if (name !== 'processSetting') return
  formSettingRef.value
    .getData()
    .then((res: { formData: { props: FormProp[] } }) => {
      const formConfig: FormProp[] = []
      res.formData.props.forEach((value) => {
        // 明细组件的子字段不作为条件
        if (value.children && value.type !== 'approvalBill') {
          value.children.forEach((child) => formConfig.push(child))
        }
        if (CONDITION_TYPES.includes(value.type)) {
          value.disabled = false
          if (value.type === 'timeFrom' && value.props) {
            value.title = value.props.titleIpt
            value.timeType = value.props.timeType
          }
          formConfig.push(value)
        }
      })
      formConfig.unshift({
        disabled: false,
        field: '',
        props: { member: false },
        title: '申请人',
        type: 'departmentTree'
      })
      flowDesignerStore.formSettingProps = formConfig
    })
    .catch((err: StepError) => {
      err.msg && ElMessage.warning(err.msg)
    })
}

function publish() {
  Promise.all([
    basicSettingRef.value.getData(),
    formSettingRef.value.getData(),
    ruleSettingRef.value.getData(),
    processSettingRef.value.getData()
  ])
    .then(([basic, form, rule, process]) => {
      const param: Required<ExamineConfig> = {
        baseConfig: basic.examineFrom,
        formConfig: form.formData,
        ruleConfig: rule.ruleFrom,
        processConfig: process
      }
      const emptyBill = param.formConfig.props.find((item) => item.type === 'approvalBill' && !item.children)
      if (emptyBill) {
        ElMessage.error(`${emptyBill.title}中至少添加一项内容`)
        return
      }
      save(param)
    })
    .catch((err: StepError) => {
      if (err.target) activeName.value = err.target
      processSettingRef.value.tipVisible = err.target === 'processSetting'
      err.msg && ElMessage.warning(err.msg)
    })
}

function save(param: Required<ExamineConfig>) {
  param.baseConfig.types = 0
  param.formConfig.props.forEach((item) => {
    if (Object.prototype.hasOwnProperty.call(BUSINESS_TYPES, item.type)) {
      param.baseConfig.types = BUSINESS_TYPES[item.type]
    }
  })
  loading.value = true
  const request = id.value > 0 ? entEditApi(id.value, param) : entAddApi(param)
  request
    .then(() => goList())
    .finally(() => {
      loading.value = false
    })
}
</script>

<style lang="scss" scoped>
// 纵向 flex：标题卡片、底部按钮栏定高，主体占剩余高度。
// app-main 高度由内容撑开，百分比高度不生效；按 BaseContainer 的约定扣掉顶栏 64px
.box {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 64px);
  padding: 1px 14px 14px;
  box-sizing: border-box;
}

:deep(.el-card) {
  transition: 0s;
}

:deep(.el-card__body) {
  padding: 0;
}

.examineCard {
  flex: none;
  margin: 0;

  :deep(.el-card__body) {
    padding: 10px 20px 0 20px;
  }
}

:deep(.el-page-header) {
  margin-top: 4px;
}

.examineTabs {
  :deep(.el-tabs__header) {
    margin: 0;
    margin-top: 8px;
  }
  :deep(.el-tabs__nav-wrap::after) {
    background-color: #fff;
  }
  :deep(.el-tabs__nav-scroll) {
    display: flex;
    justify-content: left;
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

// 内容超出时才滚动（基础 / 流程 / 规则配置）；表单配置撑满这里，由设计器中间区自己滚动
.card-box {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
}

:deep(.from-foot-btn) {
  width: 100%;
}

// 全局 .cr-bottom-button 是绝对定位（bottom: -14px），这里放回文档流，不遮挡主体
.cr-bottom-button {
  position: static;
  flex: none;
}
</style>
