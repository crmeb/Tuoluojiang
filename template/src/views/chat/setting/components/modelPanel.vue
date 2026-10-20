<template>
  <div class="model-panel">
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <div class="panel-block">
        <div class="panel-subtitle">模型设置</div>
        <el-form-item prop="models_id">
          <template #label>
            <fieldLabel label="AI模型" :tips="FIELD_TIPS.MODEL">
              <template #extra>
                <span class="text-btn" @click="openParams">参数设置</span>
              </template>
            </fieldLabel>
          </template>
          <el-select v-model="form.models_id" placeholder="请选择AI模型" class="full-width">
            <el-option v-for="item in modelOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>

        <el-form-item>
          <div class="switch-row">
            <fieldLabel label="启用深度思考" :tips="FIELD_TIPS.ENABLE_THINKING" />
            <el-switch
              v-model="form.enable_thinking"
              :active-value="1"
              :inactive-value="0"
              active-text="开启"
              inactive-text="关闭"
            />
          </div>
        </el-form-item>
      </div>

      <el-divider />

      <div class="panel-block">
        <div class="panel-subtitle">对话</div>

        <el-form-item>
          <template #label>
            <fieldLabel
              label="开场白"
              collapsible
              :expanded="expanded.prologue"
              :tips="FIELD_TIPS.PROLOGUE"
              :tips-width="250"
              @toggle="expanded.prologue = !expanded.prologue"
            />
          </template>
          <el-collapse-transition>
            <div v-show="expanded.prologue">
              <promptTextarea
                :value="form.prologue_text"
                title="开场白"
                :maxlength="TEXT_MAX.PROLOGUE"
                @input="form.prologue_text = $event"
              />
            </div>
          </el-collapse-transition>
        </el-form-item>

        <el-form-item>
          <template #label>
            <fieldLabel
              label="开场白问题"
              collapsible
              :expanded="expanded.prologueList"
              :tips="FIELD_TIPS.PROLOGUE_LIST"
              @toggle="expanded.prologueList = !expanded.prologueList"
            />
          </template>
          <el-collapse-transition>
            <div v-show="expanded.prologueList">
              <el-input
                v-for="(item, index) in form.prologue_list"
                :key="index"
                v-model="form.prologue_list[index]"
                class="line-input"
                :maxlength="TEXT_MAX.PROLOGUE_ITEM"
                placeholder="请输入开场白问题"
              >
                <template #suffix>
                  <span class="line-input-suffix">
                    <span class="count">{{ form.prologue_list[index].length }}/{{ TEXT_MAX.PROLOGUE_ITEM }}</span>
                    <i class="el-icon-delete" @click="form.prologue_list.splice(index, 1)" />
                  </span>
                </template>
              </el-input>
              <span class="text-btn small" @click="form.prologue_list.push('')">
                <i class="el-icon-circle-plus-outline" />新增问题
              </span>
            </div>
          </el-collapse-transition>
        </el-form-item>
      </div>

      <el-divider />

      <div class="panel-block">
        <pluginPanel />
      </div>

      <el-divider />

      <div class="panel-block">
        <el-form-item>
          <template #label>
            <fieldLabel
              label="参考对话轮数"
              collapsible
              :expanded="expanded.count"
              :tips="FIELD_TIPS.COUNT_NUMBER"
              :tips-width="270"
              @toggle="expanded.count = !expanded.count"
            />
          </template>
          <el-collapse-transition>
            <div v-show="expanded.count" class="count-row">
              <el-slider v-model="form.count_number" :max="MAX_COUNT_NUMBER" class="full-width" />
              <el-input-number
                v-model="form.count_number"
                :controls="false"
                :max="MAX_COUNT_NUMBER"
                :min="0"
                class="count-input"
              />
            </div>
          </el-collapse-transition>
        </el-form-item>
      </div>
    </el-form>

    <paramsDialog ref="paramsDialogRef" @submit="form.json = $event" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { getModelsSelectListApi } from '@/api/chatAi.ts'
import fieldLabel from './fieldLabel.vue'
import promptTextarea from './promptTextarea.vue'
import pluginPanel from './pluginPanel.vue'
import paramsDialog from './paramsDialog.vue'
import { FIELD_TIPS, MAX_COUNT_NUMBER, TEXT_MAX } from '../constants.ts'
import type { PreviewState } from '../types.ts'
import { useApplicationSetting } from '../composables/useApplicationSetting.ts'

const emit = defineEmits<{ (e: 'update-preview', state: PreviewState): void }>()

const { form } = useApplicationSetting()

const formRef = ref<any>(null)
const paramsDialogRef = ref<any>(null)
const modelOptions = ref<{ label: string; value: number }[]>([])
const expanded = reactive({ prologue: true, prologueList: true, count: true })

const rules = {
  models_id: [{ required: true, message: '请选择AI模型', trigger: 'change' }]
}

const previewState = computed<PreviewState>(() => ({
  prologueText: form.prologue_text,
  prologueList: form.prologue_list.filter((item) => item !== '')
}))

watch(previewState, (state) => emit('update-preview', state))

const openParams = () => {
  paramsDialogRef.value.openBox(form.json)
}

const validate = (): Promise<boolean> =>
  new Promise((resolve) => {
    formRef.value.validate((valid: boolean) => resolve(valid))
  })

onMounted(async () => {
  const res = await getModelsSelectListApi()
  modelOptions.value = res.data || []
})

defineExpose({ validate })
</script>

<style scoped lang="scss">
.model-panel {
  font-family: PingFang SC, PingFang SC;

  // 折叠区容器按 element-ui 的块布局占满整行
  :deep(.el-form-item__content) {
    display: block;
  }

  :deep(.el-form-item__label) {
    width: 100%;
    display: flex;
    align-items: center;
    height: 18px;
    margin-bottom: 10px;
    padding: 0;
  }

  :deep(.el-divider--horizontal) {
    margin: 20px 0;
  }

  :deep(.el-slider__button-wrapper) {
    z-index: 50;
  }
}

.panel-block {
  padding: 0 20px;
}

.panel-subtitle {
  margin-bottom: 10px;
  font-weight: 500;
  font-size: 12px;
  color: #606266;
}

.full-width {
  width: 100%;
}

.switch-row {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;

  :deep(.field-label) {
    width: auto;
  }
}

.count-row {
  display: flex;
  align-items: center;
}

.count-input {
  width: 61px;
  margin-left: 20px;
}

.line-input {
  margin-bottom: 8px;

  :deep(.el-input__inner) {
    padding-right: 70px;
  }
}

.line-input-suffix {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #606266;

  .count {
    color: #909399;
  }

  .el-icon-delete {
    margin-left: 8px;
    font-size: 14px;
    cursor: pointer;
  }
}

.text-btn {
  font-weight: 400;
  font-size: 13px;
  color: #1890ff;
  cursor: pointer;

  &.small {
    font-size: 12px;
  }

  .el-icon-circle-plus-outline {
    margin-right: 4px;
    font-size: 13px;
  }
}
</style>
