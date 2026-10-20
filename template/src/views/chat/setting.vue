<template>
  <div class="divBox">
    <el-card class="normal-page" body-style="padding: 0">
      <settingHeader
        :title="detail.name"
        :pic="form.pic"
        :saving="isSaving"
        @back="goBack"
        @save="handleSubmit(false)"
        @publish="handleSubmit(true)"
      />

      <el-row class="setting-body">
        <el-col :span="8">
          <div class="setting-column">
            <div class="column-title">应用信息</div>
            <applicationPanel ref="applicationPanelRef" />
          </div>
        </el-col>
        <el-col :span="8">
          <div class="setting-column is-plain">
            <modelPanel ref="modelPanelRef" @update-preview="handlePreview" />
          </div>
        </el-col>
        <el-col :span="8">
          <div class="setting-column">
            <div class="column-title">调试预览</div>
            <previewPanel
              v-if="isPreviewReady"
              :key="previewRevision"
              ref="previewPanelRef"
              :app-id="appId"
              :identity="previewIdentity"
              :share-key="detail.share_key"
            />
          </div>
        </el-col>
      </el-row>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import router from '@/router/index.ts'
import { roterPre } from '@/settings.ts'
import { AiEmbeddedManager } from '@/libs/ai/index.ts'
import settingHeader from './setting/components/settingHeader.vue'
import applicationPanel from './setting/components/applicationPanel.vue'
import modelPanel from './setting/components/modelPanel.vue'
import previewPanel from './setting/components/previewPanel.vue'
import { provideApplicationSetting } from './setting/composables/useApplicationSetting.ts'
import { APP_TYPE } from './setting/constants.ts'
import type { PreviewState } from './setting/types.ts'

const appId = String(router.currentRoute.value.query.id || '')

// 表单状态由 provide 下发，三块面板各自 inject，避免逐层透传几十个字段
const { detail, form, isSaving, fetchDetail, save, publish } = provideApplicationSetting(appId)

const applicationPanelRef = ref<any>(null)
const modelPanelRef = ref<any>(null)
const previewPanelRef = ref<any>(null)
const previewRevision = ref(0)

/**
 * 外部应用要按访客形态预览：它对外只经访客链路开放，用工作台的凭证与端点
 * 预览出来的就不是访客看到的东西了。
 *
 * 访客形态按 share_key 打开，而它随详情异步回来，因此预览面板要等详情就位
 * 才挂载 —— iframe 地址只在挂载那一刻拼一次，早挂就少了密钥。
 */
const previewIdentity = computed(() =>
  detail.value.app_type === APP_TYPE.EXTERNAL ? 'visitor' : 'workspace'
)

const isPreviewReady = computed(() => !!detail.value.id)

const goBack = () => {
  router.push({
    path: `${roterPre}/chat/${form.app_type === APP_TYPE.EXTERNAL ? 'customerService' : 'index'}`
  })
}

const handlePreview = (state: PreviewState) => {
  previewPanelRef.value?.update(state)
}

/**
 * 保存 / 发布
 *
 * 保存只写草稿，不校验必填；发布会对外可见，两侧表单都要校验通过。
 */
const handleSubmit = async (isPublish: boolean) => {
  if (isPublish) {
    const results = await Promise.all([applicationPanelRef.value.validate(), modelPanelRef.value.validate()])
    if (results.some((valid) => !valid)) return
  }

  const isOk = isPublish ? await publish() : await save()
  if (!isOk) return

  if (!isPublish) previewRevision.value += 1
  AiEmbeddedManager.refreshFloatEntryAppList()
  if (isPublish) goBack()
}

onMounted(fetchDetail)
</script>

<style scoped lang="scss">
.setting-body {
  height: 100%;
}

.setting-column {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 167px);
  padding: 20px;
  overflow-y: auto;
  border-right: 1px solid #eeeeee;
  font-family: PingFang SC, PingFang SC;
  scrollbar-width: none;
  -ms-overflow-style: none;

  &::-webkit-scrollbar {
    display: none;
  }

  &.is-plain {
    padding: 20px 0;
  }
}

.column-title {
  position: relative;
  margin-bottom: 20px;
  padding-left: 13px;
  font-weight: 500;
  font-size: 16px;
  color: #303133;

  &::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    width: 3px;
    height: 14px;
    margin-top: -7px;
    background-color: #1890ff;
  }
}
</style>
