<template>
  <div class="divBox">
    <div class="box-height">
      <el-card :body-style="{ padding: 0 }">
        <div class="content">
          <el-form ref="formRef" :model="form" label-width="150px">
            <el-row>
              <el-col :span="14">
                <el-form-item label="一号通AppId：" prop="yihaotong_appid">
                  <el-input
                    v-model="form.yihaotong_appid"
                    placeholder="请输入APPID（从一号通平台申请）"
                    show-password
                  ></el-input>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row>
              <el-col :span="14">
                <el-form-item label="一号通AppSecret：" prop="yihaotong_appsecret">
                  <el-input
                    v-model="form.yihaotong_appsecret"
                    placeholder="请输入APPKEY（从一号通平台申请）"
                    show-password
                  ></el-input>
                </el-form-item>
              </el-col>
            </el-row>
            <el-form-item>
              <el-button :loading="loading" type="primary" @click="submitForm">立即提交</el-button>
              <el-button @click="dialogVisible = true">一号通登录</el-button>
              <span class="login-tip">登录一号通平台后，凭证将自动写入并回填</span>
            </el-form-item>
          </el-form>
        </div>
      </el-card>
      <el-dialog
        title="一号通登录"
        v-model="dialogVisible"
        :width="DIALOG_SIZE.XL"
        append-to-body
        :close-on-click-modal="false"
        destroy-on-close
      >
        <iframe v-if="dialogVisible" :src="PLATFORM_URL" frameborder="0" class="login-frame"></iframe>
      </el-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { ElMessage as Message } from 'element-plus'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { cloudFileSetupApi } from '@/api/config.ts'
import { configUpdateDataApi } from '@/api/setting.ts'

/** 基础设置接口返回的单条配置项 */
interface ConfigRule {
  field: string
  value: string
}

/** 一号通配置归属的配置分类 key */
const CONFIG_CATEGORY = 'yiht_config'

/** 一号通平台登录页（登录成功后 postMessage 下发凭证） */
const PLATFORM_URL = 'https://api.crmeb.com?token=AF37D4579721672220B08CA872586943'

const formRef = ref<any>(null)
const loading = ref(false)
const form = ref({ yihaotong_appid: '', yihaotong_appsecret: '' })
const dialogVisible = ref(false)
const saving = ref(false) // 弹窗登录凭证保存中（防重复提交）

onMounted(() => {
  getConfigData()
  window.addEventListener('message', handleConfig)
})

onBeforeUnmount(() => {
  window.removeEventListener('message', handleConfig)
})

// 从配置分类接口取 rule 列表，按 field 回填表单
function getConfigData() {
  configUpdateDataApi({ category: CONFIG_CATEGORY }).then((res: any) => {
    const rule: ConfigRule[] = (res.data && res.data.rule) || []
    rule.forEach((item) => {
      if (item.field === 'yihaotong_appid') {
        form.value.yihaotong_appid = item.value
      } else if (item.field === 'yihaotong_appsecret') {
        form.value.yihaotong_appsecret = item.value
      }
    })
  })
}

// 接收 iframe（api.crmeb.com）登录成功后 postMessage 传出的凭证
// accessKey/secretKey 即一号通签发的 AppId/AppSecret，自动保存后回填表单
function handleConfig(event: MessageEvent) {
  const payload = event && (event.data as any)
  if (!payload || !payload.accessKey || !payload.secretKey) return
  if (saving.value) return
  saving.value = true
  cloudFileSetupApi(CONFIG_CATEGORY, {
    yihaotong_appid: payload.accessKey,
    yihaotong_appsecret: payload.secretKey
  })
    .then(() => {
      saving.value = false
      dialogVisible.value = false
      form.value.yihaotong_appid = payload.accessKey
      form.value.yihaotong_appsecret = payload.secretKey
      Message.success('一号通登录成功，凭证已自动写入系统配置')
    })
    .catch((res: any) => {
      saving.value = false
      Message.error((res && res.msg) || '一号通配置保存失败')
    })
}

function submitForm() {
  formRef.value?.validate((valid: boolean) => {
    if (!valid) return
    loading.value = true
    cloudFileSetupApi(CONFIG_CATEGORY, form.value)
      .then(() => {
        loading.value = false
      })
      .catch(() => {
        loading.value = false
      })
  })
}
</script>

<style lang="scss" scoped>
.content {
  padding: 20px;
  // 81(布局) + 40(内容区上下 padding)
  min-height: calc(100vh - 121px);
}

.login-tip {
  margin-left: 12px;
  font-size: 12px;
  color: #909399;
}

.login-frame {
  display: block;
  width: 100%;
  height: 560px;
}
</style>
