<template>
  <div class="divBox mcp-page">
    <el-card class="normal-page el-card-flex mcp-card" body-style="padding: 0">
      <div class="mcp-main">
        <div class="mcp-title">MCP接入说明</div>

        <div class="mcp-notice">
          <span class="mcp-notice__icon iconfont iconshuoming"></span>
          <span class="mcp-notice__text">
            配置以当前登录账号身份访问，权限与后台一致，数据范围受企业数据权限约束。
            复制下方 JSON 到客户端或会话区域即可完成配置；支持的业务模块见配置内注释。
          </span>
        </div>

        <div class="mcp-step mcp-step--command">
          <div class="mcp-step__body">
            <div class="command-card">
              <div class="command-card__header">
                <span class="command-card__title">复制配置至 Agent 客户端</span>
                <div class="command-card__actions">
                  <button
                    type="button"
                    class="mcp-btn"
                    :disabled="isRefreshing"
                    @click="handleRefreshKey"
                  >
                    <span class="mcp-btn__icon el-icon-refresh"></span>
                    <span>刷新 Key</span>
                  </button>
                  <button type="button" class="mcp-btn mcp-btn--primary" @click="handleCopyCommand">
                    <span class="mcp-btn__icon iconfont iconfuzhi1"></span>
                    <span>复制</span>
                  </button>
                </div>
              </div>
              <div class="command-card__code" v-loading="isLoading">
                <pre>{{ installCommand }}</pre>
              </div>
            </div>
          </div>
        </div>

        <el-card class="mcp-tips">
          <el-descriptions title="安全与 FAQ" :column="1" :colon="false">
            <el-descriptions-item contentStyle="color: red;">X-Mcp-Key 等同于账号凭证，勿分享、勿提交到 Git</el-descriptions-item>
            <el-descriptions-item contentStyle="color: red;">Key 泄露后可点击上方「刷新 Key」立即作废旧密钥</el-descriptions-item>
            <el-descriptions-item>Q：谁能看到哪些数据？</el-descriptions-item>
            <el-descriptions-item>A：与后台登录账号一致，受 Casbin 角色 + 数据权限过滤；管理员与普通员工可见范围不同。</el-descriptions-item>

            <el-descriptions-item>Q：AI 能改数据吗？</el-descriptions-item>
            <el-descriptions-item>A：目前仅支持查看操作，后续将陆续开放其他操作。</el-descriptions-item>

            <el-descriptions-item>Q：连接失败怎么办？</el-descriptions-item>
            <el-descriptions-item>A：确认 URL 可从客户端机器访问（localhost 仅本机有效）</el-descriptions-item>
            <el-descriptions-item contentStyle="padding-left: 1.7em;">确认 HTTPS 证书有效</el-descriptions-item>
            <el-descriptions-item contentStyle="padding-left: 1.7em;">确认账号状态正常</el-descriptions-item>
            <el-descriptions-item contentStyle="padding-left: 1.7em;">重置 MCP Key 后更新客户端配置</el-descriptions-item>

            <el-descriptions-item>Q：和「AI 应用列表」里的 MCP 配置有什么区别？</el-descriptions-item>
            <el-descriptions-item>A：本页是外部 AI 客户端（Cursor 等）接入陀螺匠；应用列表是站内 AI 应用绑定 MCP 服务，面向不同场景。</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Message } from 'element-ui'
import { copyToClipboard } from '@/utils/util'
import { modalSure } from '@/libs/public'
import { userInfo as userInfoApi, updateMcpKeyApi } from '@/api/user'
import SettingMer from '@/libs/settingMer'
import { useLoading } from '@/composables/useLoading'

const userInfo = ref()
const isRefreshing = ref(false)
const { isLoading, startLoading, stopLoading } = useLoading()

const installCommand = computed(
  () => `{
  "mcpServers": {
    // 陀螺匠系统 MCP 工具，支持：客户、考勤、绩效、汇报、日程、审批
    "tuoluojiang": {
      "url": "${SettingMer.httpUrl}/mcp",
      "headers": {
        "X-Mcp-Key": "${userInfo.value?.mcpKey ?? ''}"
      }
    }
  }
}`
)

function handleCopyCommand(event) {
  copyToClipboard(installCommand.value, event, Message, '安装指令已复制', '复制失败')
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
</script>

<style scoped lang="scss">
// 页面整体不滚动：容器高度锁定在可视区内，滚动只发生在代码区内部
.mcp-page {
  // 64px 顶栏 + .divBox 的 margin(上 1px / 下 14px)，锁死高度后内部才能各自滚动
  height: calc(100vh - 79px);
  display: flex;
  flex-direction: column;
}

.mcp-card {
  flex: 1;
  min-height: 0;
  overflow: hidden;

  ::v-deep .el-card__body {
    height: 100%;
  }
}

.mcp-main {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  padding: 20px;
}

.mcp-title {
  font-size: 18px;
  font-weight: 500;
  line-height: 25px;
  color: #303133; // 文字/主要-303133
}

.mcp-notice {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 14px;
  padding: 11px 12px;
  border-radius: 6px;
  background: rgba(24, 144, 255, 0.1);
}

.mcp-notice__icon {
  display: flex;
  font-size: 14px;
  color: #1890ff;
  font-weight: 600;
}

.mcp-notice__text {
  font-size: 13px;
  line-height: 22px;
  color: #303133; // 文本/主要-303133
}

.mcp-step--command {
  margin-top: 20px;
}
.command-card {
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.command-card__header {
  box-sizing: border-box;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 74px;
  // 底部 12px 被代码区圆角覆盖，实际可视高度 62px
  padding: 0 20px 12px;
  border-radius: 12px 12px 0 0;
  background: #f5f5f5; // 背景色/01-F5F5F5
}

.command-card__title {
  font-size: 13px;
  font-weight: 500;
  line-height: 18px;
  color: #303133;
}

.command-card__actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.command-card__code {
  margin-top: -12px;
  padding: 20px;
  border: 1px solid #eeeeee;
  border-radius: 12px;
  background: #fff;
  overflow: auto;

  scrollbar-width: none; // firefox
  -ms-overflow-style: none; // IE 10+

  &::-webkit-scrollbar {
    display: none;
  }

  pre {
    margin: 0;
    font-family: inherit;
    font-size: 13px;
    line-height: 20px;
    color: #303133;
  }

  p + p {
    margin-top: 8px;
  }

  &.tips {
    border: none;
    margin-top: 0;
    border-radius: none;
  }
}

.mcp-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  height: 32px;
  padding: 0 16px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  background: #fff; // 背景色/白色-FFFFFF
  font-size: 13px;
  line-height: 18px;
  color: #303133;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;

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
  color: #1890ff;

  .mcp-btn__icon {
    color: #1890ff;
  }
}

.command-warn {
  color: red !important;
}

:deep(.mcp-tips) {
  border: 1px solid #eeeeee;
  border-radius: 12px;
  margin-top: 20px;
  flex: 1;

  .el-descriptions {
    height: 100%;
    overflow: auto;
    scrollbar-width: none;
  }

  .el-descriptions__header {
    position: sticky;
    top: 0;
    background-color: #fff;
  }
}
</style>
