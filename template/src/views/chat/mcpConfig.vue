<template>
  <div class="divBox mcp-page">
    <el-card class="normal-page mcp-card" body-style="padding: 0">
      <div class="mcp-main">
        <!-- 页头：标题 + 账号权限说明 -->
        <header class="mcp-header">
          <h2 class="mcp-header__title">MCP 接入说明</h2>
          <div class="mcp-notice">
            <span class="mcp-notice__icon iconfont iconshuoming"></span>
            <span class="mcp-notice__text">
              连接后以你当前登录的账号身份访问，能看到的数据与你在系统中的权限完全一致。
            </span>
          </div>
        </header>

        <!-- 配置面板：标题栏 / 配置方式 / 代码区 / 说明栏 四段式 -->
        <section class="mcp-panel">
          <div class="mcp-panel__head">
            <div class="mcp-panel__head-main">
              <span class="mcp-panel__title">复制配置至你的 AI 助手</span>
              <span class="mcp-panel__sub">粘贴到 AI 助手的 MCP 配置中，保存后重启即可生效</span>
            </div>
            <div class="mcp-panel__actions">
              <button
                type="button"
                class="mcp-btn"
                :disabled="isRefreshing"
                @click="handleRefreshKey"
              >
                <span class="mcp-btn__icon el-icon-refresh"></span>
                <span>刷新 Key</span>
              </button>
              <button
                type="button"
                class="mcp-btn mcp-btn--primary"
                :disabled="!canCopy"
                @click="handleCopyCommand"
              >
                <span class="mcp-btn__icon iconfont iconfuzhi1"></span>
                <span>复制配置</span>
              </button>
            </div>
          </div>

          <!-- 配置方式切换：按模块 / 全量 -->
          <div class="mcp-config">
            <div class="mcp-config__row">
              <span class="mcp-config__label">配置方式</span>
              <el-radio-group v-model="configMode" size="small">
                <el-radio-button value="all">全量配置</el-radio-button>
                <el-radio-button value="module">按模块配置</el-radio-button>
              </el-radio-group>
              <span class="mcp-config__tip">{{ modeTip }}</span>
            </div>

            <!-- 模块多选：仅在按模块模式下出现 -->
            <div v-if="isModuleMode" class="mcp-config__row mcp-config__row--modules">
              <span class="mcp-config__label">业务模块</span>
              <div class="mcp-modules">
                <button
                  v-for="item in moduleList"
                  :key="item.key"
                  type="button"
                  class="mcp-module"
                  :class="{ 'mcp-module--active': isModuleSelected(item.key) }"
                  :title="item.desc"
                  @click="toggleModule(item.key)"
                >
                  <span v-if="isModuleSelected(item.key)" class="mcp-module__check el-icon-check"></span>
                  <span>{{ item.name }}</span>
                </button>
                <span class="mcp-modules__divider"></span>
                <button type="button" class="mcp-modules__action" @click="selectAllModules">全选</button>
                <button type="button" class="mcp-modules__action" @click="clearModules">清空</button>
              </div>
            </div>
          </div>

          <div class="mcp-code" v-loading="isLoading">
            <pre class="mcp-code__pre">{{ installCommandDisplay }}</pre>
          </div>

          <div class="mcp-panel__foot">
            <!-- 原 JSON 内的注释信息移到这里承载，保证 JSON 严格合法 -->
            <div class="mcp-meta">
              <span class="mcp-meta__label">当前生成</span>
              <span class="mcp-tag">{{ configSummary }}</span>
              <span class="mcp-meta__note">密钥已打码显示，复制得到的是完整值</span>
            </div>
            <p v-if="!hasMcpKey && !isLoading" class="mcp-meta__hint mcp-meta__hint--warn">
              <span class="el-icon-warning-outline"></span>
              暂未获取到 MCP Key，请先点击右上角「刷新 Key」后再复制配置。
            </p>
            <p v-else-if="isModuleMode && !selectedModuleKeys.length" class="mcp-meta__hint mcp-meta__hint--warn">
              <span class="el-icon-warning-outline"></span>
              请至少勾选一个业务模块，否则生成的配置为空。
            </p>
          </div>
        </section>

        <!-- 安全提示：独立警示块 -->
        <section class="mcp-warn">
          <span class="mcp-warn__icon el-icon-warning"></span>
          <div class="mcp-warn__body">
            <p class="mcp-warn__title">安全提示</p>
            <ul class="mcp-warn__list">
              <li>配置中的密钥等同于你的账号凭证，请勿分享给他人，也不要上传到代码仓库或公开网盘。</li>
              <li>密钥一旦泄露，点击上方「刷新 Key」可立即作废，旧配置随即失效。</li>
            </ul>
          </div>
        </section>

        <!-- 常见问题 -->
        <section class="mcp-panel">
          <div class="mcp-panel__head">
            <div class="mcp-panel__head-main">
              <span class="mcp-panel__title">常见问题</span>
            </div>
          </div>
          <ul class="mcp-faq">
            <li v-for="item in FAQ_LIST" :key="item.q" class="mcp-faq__item">
              <p class="mcp-faq__q">{{ item.q }}</p>
              <div class="mcp-faq__a">
                <p v-for="(line, index) in item.a" :key="index">{{ line }}</p>
              </div>
            </li>
          </ul>
        </section>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { copyToClipboard } from '@/utils/util'
import { modalSure } from '@/libs/public.ts'
import { userInfo as userInfoApi, updateMcpKeyApi } from '@/api/user.ts'
import SettingMer from '@/libs/settingMer.ts'
import { getMcpListApi } from '@/api/chatAi'
import { useLoading } from '@/composables/useLoading.ts'

interface McpModule {
  key: string
  name: string
  desc: string
}
interface McpService {
  name?: string
  info?: string
  config_json?: { module?: string }
}

// 内置兜底模块清单，与 server/config/mcp.php 的 tool_modules 对齐；
// 正常情况下会被 ai/mcp 列表接口返回的内置服务覆盖，保证与后台配置同步。
const FALLBACK_MODULES = [
  { key: 'customer', name: '客户', desc: '客户、线索、商机、订单、合同、发票、客户账目、联系人、跟进记录' },
  { key: 'attendance', name: '考勤', desc: '考勤打卡、排班、申请和统计' },
  { key: 'assess', name: '绩效', desc: '绩效列表、详情、统计和趋势' },
  { key: 'report', name: '汇报', desc: '工作汇报列表、详情和统计' },
  { key: 'schedule', name: '日程', desc: '日程列表和详情' },
  { key: 'approve', name: '审批', desc: '审批类型、假期类型、审批表单、流程与发起审批' }
]

const FAQ_LIST = [
  {
    q: 'Q：按模块配置和全量配置该怎么选？',
    a: [
      '按模块：按业务分别连接，AI 只看到该模块的能力，回答更聚焦，日常使用推荐这种方式。',
      '全量：一次连接全部业务能力，适合需要跨模块处理的任务（如先查客户、再发起审批、最后排日程），但可选能力多，AI 判断成本也更高。'
    ]
  },
  {
    q: 'Q：谁能看到哪些数据？',
    a: ['连接后以你当前登录的账号身份访问，可见范围与你在系统中的角色和数据权限完全一致；管理员与普通员工能看到的范围不同。']
  },
  {
    q: 'Q：AI 能改数据吗？',
    a: [
      '已开放部分写入操作：新建客户/联系人/线索/商机/订单、新增与修改跟进记录、新建与修改本人日程与汇报、发起审批（含报销）、绩效自评。',
      '回款/续费/订单支出/开票/作废发票按审批流发起；操作人固定为当前账号，写入前会先与你确认；客户资料、金额、合同与发票不可直接修改。'
    ]
  },
  {
    q: 'Q：连接失败怎么办？',
    a: [
      '确认当前网络能正常打开系统页面，再重新尝试连接。',
      '确认账号状态正常，未被停用或办理离职。',
      '点击上方「刷新 Key」重新生成密钥，并把新配置更新到 AI 助手中。',
      '仍无法连接时，请联系系统管理员协助排查。'
    ]
  },
  {
    q: 'Q：这里配置的和「AI 应用列表」有什么区别？',
    a: ['本页是让你自己的 AI 助手连接系统，直接用自然语言查询你权限内的业务数据；「AI 应用列表」是系统内置的 AI 应用，由管理员在后台配置和启用，两者使用场景不同。']
  }
]

const userInfo = ref<{ mcpKey?: string }>({})
const isRefreshing = ref(false)
const { isLoading, startLoading, stopLoading } = useLoading()

// 配置方式：all=全量单端点（默认），module=按模块拆分（工具过多时用）
const configMode = ref<'all' | 'module'>('all')
const mcpModules = ref<McpModule[]>([])
const selectedModuleKeys = ref(FALLBACK_MODULES.map((item) => item.key))
// 用户手动改过选择后，接口回来不再覆盖他的选择
let moduleTouched = false

const mcpKey = computed(() => userInfo.value?.mcpKey || '')
const hasMcpKey = computed(() => !!mcpKey.value)
const isModuleMode = computed(() => configMode.value === 'module')

const moduleList = computed(() => (mcpModules.value.length ? mcpModules.value : FALLBACK_MODULES))

const modeTip = computed(() =>
  isModuleMode.value
    ? '按业务分别连接，AI 只看到该模块的能力，回答更聚焦'
    : '一次连接全部业务能力，适合需要跨模块处理的场景'
)

const canCopy = computed(
  () => hasMcpKey.value && !isLoading.value && (!isModuleMode.value || selectedModuleKeys.value.length > 0)
)

const configSummary = computed(() => {
  if (!isModuleMode.value) return '全量配置 · 全部业务能力'
  return `按模块配置 · 已选 ${selectedModuleKeys.value.length}/${moduleList.value.length} 个模块`
})

// 展示用脱敏 Key：只露首尾各 4 位便于辨认，短 Key 直接整体打码
const maskedMcpKey = computed(() => {
  const key = mcpKey.value
  if (!key) return ''
  if (key.length < 16) return '*'.repeat(8)
  return `${key.slice(0, 4)}****${key.slice(-4)}`
})

// 用 JSON.stringify 构造，从根上保证输出为严格合法 JSON（不再手写模板字符串拼注释）
const buildConfig = (keyValue: string) => {
  const baseUrl = String(SettingMer.httpUrl || '').replace(/\/+$/, '')
  const buildServer = (url: string) => ({
    url,
    headers: {
      'X-Mcp-Key': keyValue
    }
  })

  const servers: Record<string, { url: string; headers: { 'X-Mcp-Key': string } }> = {}

  if (isModuleMode.value) {
    moduleList.value.forEach((item) => {
      if (selectedModuleKeys.value.includes(item.key)) {
        servers[`tuoluojiang-${item.key}`] = buildServer(`${baseUrl}/mcp/${item.key}`)
      }
    })
  } else {
    servers.tuoluojiang = buildServer(`${baseUrl}/mcp`)
  }

  return JSON.stringify({ mcpServers: servers }, null, 2)
}

// 复制用：带真实 Key（仅存在于内存，不渲染到 DOM）
const installCommand = computed(() => buildConfig(mcpKey.value))
// 展示用：带脱敏 Key
const installCommandDisplay = computed(() => buildConfig(maskedMcpKey.value))

watch(
  moduleList,
  (list) => {
    if (!moduleTouched && list.length) {
      selectedModuleKeys.value = list.map((item) => item.key)
    }
  },
  { immediate: true }
)

function isModuleSelected(key: string) {
  return selectedModuleKeys.value.includes(key)
}

function toggleModule(key: string) {
  moduleTouched = true
  selectedModuleKeys.value = isModuleSelected(key)
    ? selectedModuleKeys.value.filter((item) => item !== key)
    : [...selectedModuleKeys.value, key]
}

function selectAllModules() {
  moduleTouched = true
  selectedModuleKeys.value = moduleList.value.map((item) => item.key)
}

function clearModules() {
  moduleTouched = true
  selectedModuleKeys.value = []
}

function handleCopyCommand(event: MouseEvent) {
  if (!hasMcpKey.value) {
    Message.warning('暂未获取到 MCP Key，请先点击「刷新 Key」')
    return
  }
  if (isModuleMode.value && !selectedModuleKeys.value.length) {
    Message.warning('请至少勾选一个业务模块')
    return
  }
  copyToClipboard(installCommand.value, event, Message, 'MCP 配置已复制', '复制失败')
}

function handleRefreshKey() {
  modalSure('更新后旧McpKey将失效，确定更新吗').then(async () => {
    if (isRefreshing.value) return
    isRefreshing.value = true
    try {
      const res = await updateMcpKeyApi()
      userInfo.value = {
        ...(userInfo.value || {}),
        mcpKey: res.data.mcpKey
      }
      Message.success('McpKey 已更新')
    } finally {
      isRefreshing.value = false
    }
  })
}

// 从内置 MCP 服务读取模块清单（config_json.module 即端点路径），失败则用内置兜底
const getMcpModules = async () => {
  try {
    const res = await getMcpListApi({ is_default: 1, page: 1, limit: 100 })
    const list = (res?.data?.list || [])
      .map((item: McpService) => {
        const key = item?.config_json?.module
        if (!key) return null
        return {
          key,
          name: String(item.name || key).replace(/MCP服务$/, ''),
          desc: item.info || ''
        }
      })
      .filter((item: McpModule | null): item is McpModule => item !== null)

    if (list.length) mcpModules.value = list
  } catch (e) {
    // 无权限或接口异常时静默降级，不影响页面可用
  }
}

const getUserInfo = async () => {
  if (isLoading.value) return
  startLoading()
  try {
    const res = await userInfoApi()
    userInfo.value = res.data
  } finally {
    stopLoading()
  }
}

getUserInfo()
getMcpModules()
</script>

<style scoped lang="scss">
// 页面滚动收敛在 .mcp-page 内部：内容少时卡片铺满，内容多时本页自行滚动，不会出现被裁切
.mcp-page {
  // 64px 顶栏 + .divBox 的 margin(上 1px / 下 14px)
  height: calc(100vh - 79px);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
}

.mcp-card {
  // 至少铺满可视区，内容超出时自然撑高（不再用 flex:1 拉伸导致内部留白）
  flex: 1 0 auto;
  min-height: 0;
}

.mcp-main {
  display: flex;
  flex-direction: column;
  padding: 20px 24px;
}

/* ---------- 页头 ---------- */
.mcp-header {
  flex-shrink: 0;
}

.mcp-header__title {
  margin: 0;
  font-size: 18px;
  font-weight: 500;
  line-height: 25px;
  color: #303133;
}

.mcp-notice {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 12px;
  padding: 10px 12px;
  border: 1px solid #d9ecff;
  border-radius: 8px;
  background: #ecf5ff;
}

.mcp-notice__icon {
  flex-shrink: 0;
  font-size: 14px;
  line-height: 22px;
  color: #1890ff;
}

.mcp-notice__text {
  font-size: 13px;
  line-height: 22px;
  color: #303133;
}

/* ---------- 通用面板 ---------- */
.mcp-panel {
  margin-top: 12px;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}

.mcp-panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 16px;
  border-bottom: 1px solid #ebeef5;
  background: #fafafa;
}

.mcp-panel__head-main {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 0;
}

.mcp-panel__title {
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  color: #303133;
}

.mcp-panel__sub {
  font-size: 12px;
  line-height: 20px;
  color: #909399;
}

.mcp-panel__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* ---------- 配置方式 ---------- */
.mcp-config {
  padding: 12px 16px;
  border-bottom: 1px dashed #ebeef5;
  background: #fff;
}

.mcp-config__row {
  display: flex;
  align-items: center;
  gap: 12px;

  & + .mcp-config__row {
    margin-top: 12px;
  }
}

.mcp-config__row--modules {
  align-items: flex-start;
}

.mcp-config__label {
  flex-shrink: 0;
  width: 56px;
  font-size: 13px;
  line-height: 28px;
  color: #606266;
}

.mcp-config__tip {
  font-size: 12px;
  line-height: 20px;
  color: #909399;
}

/* ---------- 模块多选 ---------- */
.mcp-modules {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.mcp-module {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 28px;
  padding: 0 12px;
  border: 1px solid #dcdfe6;
  border-radius: 14px;
  background: #fff;
  font-size: 13px;
  line-height: 26px;
  color: #606266;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s, background-color 0.2s;

  &:hover {
    border-color: #1890ff;
    color: #1890ff;
  }
}

.mcp-module--active {
  border-color: #1890ff;
  background: rgba(24, 144, 255, 0.08);
  color: #1890ff;

  &:hover {
    background: rgba(24, 144, 255, 0.14);
  }
}

.mcp-module__check {
  font-size: 12px;
  font-weight: 600;
}

.mcp-modules__divider {
  width: 1px;
  height: 14px;
  background: #ebeef5;
}

.mcp-modules__action {
  padding: 0 4px;
  border: none;
  background: transparent;
  font-size: 12px;
  line-height: 28px;
  color: #1890ff;
  cursor: pointer;

  &:hover {
    color: #409eff;
    text-decoration: underline;
  }
}

/* ---------- 代码区 ---------- */
.mcp-code {
  padding: 16px;
  // 上限压到 240px：按模块模式下 JSON 有 40 行，不设上限会把整页顶出滚动条
  max-height: 240px;
  overflow: auto;

  scrollbar-width: thin;

  &::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }

  &::-webkit-scrollbar-thumb {
    border-radius: 3px;
    background: #dcdfe6;
  }
}

.mcp-code__pre {
  margin: 0;
  // 等宽字体：JSON 缩进与层级对齐才看得出结构
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace;
  font-size: 13px;
  line-height: 20px;
  color: #303133;
  white-space: pre;
}

/* ---------- 面板底部说明 ---------- */
.mcp-panel__foot {
  padding: 12px 16px;
  border-top: 1px dashed #ebeef5;
  background: #fcfcfc;
}

.mcp-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.mcp-meta__label {
  font-size: 12px;
  line-height: 20px;
  color: #909399;
}

.mcp-meta__note {
  font-size: 12px;
  line-height: 20px;
  color: #909399;
}

.mcp-tag {
  display: inline-flex;
  align-items: center;
  height: 22px;
  padding: 0 8px;
  border-radius: 4px;
  background: rgba(24, 144, 255, 0.1);
  font-size: 12px;
  line-height: 20px;
  color: #1890ff;
}

.mcp-meta__hint {
  margin: 8px 0 0;
  font-size: 12px;
  line-height: 20px;
  color: #909399;

  .el-icon-warning-outline {
    margin-right: 4px;
    color: #e6a23c;
  }
}

.mcp-meta__hint--warn {
  color: #e6a23c;
}

/* ---------- 安全提示 ---------- */
.mcp-warn {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 12px;
  padding: 12px 16px;
  border: 1px solid #fde2e2;
  border-radius: 8px;
  background: #fef0f0;
}

.mcp-warn__icon {
  flex-shrink: 0;
  font-size: 16px;
  line-height: 22px;
  color: #f56c6c;
}

.mcp-warn__body {
  min-width: 0;
}

.mcp-warn__title {
  margin: 0 0 4px;
  font-size: 13px;
  font-weight: 500;
  line-height: 22px;
  color: #f56c6c;
}

.mcp-warn__list {
  margin: 0;
  padding: 0;
  list-style: none;

  li {
    position: relative;
    padding-left: 12px;
    font-size: 13px;
    line-height: 22px;
    color: #606266;

    &::before {
      content: '';
      position: absolute;
      top: 9px;
      left: 0;
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background: #f56c6c;
    }
  }
}

/* ---------- 常见问题 ---------- */
.mcp-faq {
  margin: 0;
  padding: 4px 16px 10px;
  list-style: none;
}

.mcp-faq__item + .mcp-faq__item {
  margin-top: 10px;
}

.mcp-faq__q {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: 13px;
  font-weight: 500;
  line-height: 22px;
  color: #303133;

  &::before {
    content: '';
    flex-shrink: 0;
    width: 3px;
    height: 13px;
    border-radius: 2px;
    background: #1890ff;
  }
}

.mcp-faq__a {
  margin: 4px 0 0;
  padding-left: 11px;

  p {
    margin: 0;
    font-size: 13px;
    line-height: 22px;
    color: #606266;
  }

  p + p {
    margin-top: 4px;
  }
}

/* ---------- 按钮 ---------- */
.mcp-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  height: 32px;
  padding: 0 14px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  background: #fff;
  font-size: 13px;
  line-height: 18px;
  color: #303133;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s, background-color 0.2s;

  &:hover:not(:disabled) {
    border-color: #1890ff;
    color: #1890ff;
  }

  &:disabled {
    cursor: not-allowed;
    opacity: 0.6;
  }

  .mcp-btn__icon {
    display: flex;
    font-size: 12px;
    color: #606266;
  }

  &:hover:not(:disabled) .mcp-btn__icon {
    color: #1890ff;
  }
}

.mcp-btn--primary {
  border-color: #1890ff;
  color: #1890ff;

  .mcp-btn__icon {
    color: #1890ff;
  }

  &:hover:not(:disabled) {
    background: rgba(24, 144, 255, 0.06);
  }

  &:disabled {
    border-color: #dcdfe6;
    color: #c0c4cc;

    .mcp-btn__icon {
      color: #c0c4cc;
    }
  }
}
</style>
