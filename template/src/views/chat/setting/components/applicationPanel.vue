<template>
  <div class="application-panel">
    <div class="logo-row">
      <div class="upload-logo" @click="pictureVisible = true">
        <img :src="form.pic" alt="" class="logo" />
        <div class="logo-mask">
          <i class="iconfont iconbianji3" />
        </div>
      </div>
    </div>

    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-form-item prop="name">
        <template #label>
          <fieldLabel label="应用名称" :tips="FIELD_TIPS.NAME" :tips-width="150" />
        </template>
        <el-input v-model="form.name" size="small" maxlength="10" show-word-limit placeholder="请输入应用名称" />
      </el-form-item>

      <el-form-item prop="info">
        <template #label>
          <fieldLabel label="应用简介" :tips="FIELD_TIPS.INFO" />
        </template>
        <el-input
          v-model="form.info"
          type="textarea"
          size="small"
          maxlength="100"
          show-word-limit
          placeholder="请输入应用简介"
        />
      </el-form-item>

      <el-form-item prop="tooltip_text">
        <template #label>
          <fieldLabel label="人设与回复逻辑" :tips="FIELD_TIPS.TOOLTIP">
            <template #extra>
              <generatePromptButton
                :models-id="form.models_id"
                :content="personaContext"
                :type="PROMPT_TYPE.PERSONA_REPLY"
                title="人设与回复逻辑"
                :maxlength="TEXT_MAX.TOOLTIP"
                :height="420"
                :value="form.tooltip_text"
                @confirm="form.tooltip_text = $event"
              />
            </template>
          </fieldLabel>
        </template>
        <promptTextarea
          :value="form.tooltip_text"
          title="人设与回复逻辑"
          :maxlength="TEXT_MAX.TOOLTIP"
          :height="395"
          :placeholder="PERSONA_PLACEHOLDER"
          clearable
          @input="form.tooltip_text = $event"
        />
      </el-form-item>

      <el-form-item prop="edit_ids">
        <template #label>
          <fieldLabel label="编辑权限" :tips="FIELD_TIPS.EDIT" />
        </template>
        <selectMember
          :value="form.edit_ids"
          placeholder="请选择拥有编辑权限的人员(多选)"
          @getSelectList="handleSelect($event, 'edit_ids')"
        />
      </el-form-item>

      <el-form-item prop="auth_ids">
        <template #label>
          <fieldLabel label="应用权限" :tips="FIELD_TIPS.AUTH" />
        </template>
        <el-radio-group v-model="form.app_type" class="app-type" @change="handleAppTypeChange">
          <el-radio v-for="item in APP_TYPE_OPTIONS" :key="item.value" :label="item.value">{{ item.label }}</el-radio>
        </el-radio-group>
        <selectMember
          v-if="form.app_type === APP_TYPE.INTERNAL"
          :value="form.auth_ids"
          placeholder="请选择应用成员(多选)"
          @getSelectList="handleSelect($event, 'auth_ids')"
        />
      </el-form-item>

      <el-form-item prop="use_limit">
        <template #label>
          <fieldLabel label="使用频次" :tips="FIELD_TIPS.USE_LIMIT" />
        </template>
        <el-input v-model="form.use_limit" size="small" type="number" min="0" placeholder="请输入使用频次限制">
          <template #suffix>
            <span class="suffix-text">次/天</span>
          </template>
        </el-input>
      </el-form-item>

      <el-form-item prop="sort">
        <template #label>
          <fieldLabel label="应用排序" :tips="FIELD_TIPS.SORT" />
        </template>
        <el-input
          v-model="form.sort"
          size="small"
          type="number"
          min="0"
          placeholder="请输入应用排序"
          oninput="value=value.replace(/^0+(\d)|[^\d]+/g,'')"
        />
      </el-form-item>
    </el-form>

    <el-dialog title="选择图片" :visible.sync="pictureVisible" :width="DIALOG_SIZE.LG">
      <uploadPicture :check-button="true" @getImage="handlePicture" />
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize'
import uploadPicture from '@/components/uploadPicture/index.vue'
import selectMember from '@/components/form-common/select-member.vue'
import fieldLabel from './fieldLabel.vue'
import promptTextarea from './promptTextarea.vue'
import generatePromptButton from './generatePromptButton.vue'
import { APP_TYPE, APP_TYPE_OPTIONS, FIELD_TIPS, PERSONA_PLACEHOLDER, PROMPT_TYPE, TEXT_MAX } from '../constants'
import { useApplicationSetting } from '../composables/useApplicationSetting'

const { form } = useApplicationSetting()

const formRef = ref<any>(null)
const pictureVisible = ref(false)

const validateMembers = (message: string) => (rule: any, value: number[], callback: (error?: Error) => void) => {
  if (!value || !value.length) return callback(new Error(message))
  callback()
}

const rules = {
  name: [{ required: true, message: '请输入应用名称', trigger: 'blur' }],
  edit_ids: [{ required: true, validator: validateMembers('请选择拥有编辑权限的人员'), trigger: 'change' }],
  auth_ids: [
    {
      required: true,
      // 外部应用不限制人员，只有内部应用需要校验
      validator: (rule: any, value: number[], callback: (error?: Error) => void) => {
        if (form.app_type === APP_TYPE.EXTERNAL) return callback()
        validateMembers('请选择可使用该应用的人员')(rule, value, callback)
      },
      trigger: 'change'
    }
  ]
}

const handleSelect = (list: { value: number }[], key: 'edit_ids' | 'auth_ids') => {
  form[key] = list.map((item) => item.value)
  formRef.value?.clearValidate(key)
}

// 外部应用不校验成员，但已选成员保留在表单里，误切回内部应用时不用重选；
// 提交体由 buildApplicationPayload 负责剔除
const handleAppTypeChange = () => {
  formRef.value?.clearValidate('auth_ids')
}

const handlePicture = (data: { att_dir: string }) => {
  form.pic = data.att_dir
  pictureVisible.value = false
}

/** 生成人设与回复逻辑的上下文：应用名称 + 应用简介 */
const personaContext = computed(() =>
  [form.name && `应用名称：${form.name}`, form.info && `应用简介：${form.info}`].filter(Boolean).join('\n')
)

const validate = (): Promise<boolean> =>
  new Promise((resolve) => {
    formRef.value.validate((valid: boolean) => resolve(valid))
  })

defineExpose({ validate })
</script>

<style scoped lang="scss">
.application-panel {
  font-family: PingFang SC, PingFang SC;

  :deep(.el-form-item__label) {
    width: 100%;
    display: flex;
    align-items: center;
    height: 18px;
    margin-bottom: 10px;
    padding: 0;
  }

  :deep(.el-textarea__inner) {
    resize: none;
  }
}

.logo-row {
  display: flex;
  justify-content: center;
}

.upload-logo {
  position: relative;
  margin: 20px 0;
  cursor: pointer;

  &:hover .logo-mask {
    display: flex;
  }

  .logo {
    display: block;
    width: 68px;
    height: 68px;
    border-radius: 50%;
    object-fit: cover;
  }
}

.logo-mask {
  position: absolute;
  top: 0;
  left: 0;
  display: none;
  align-items: center;
  justify-content: center;
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: rgba(43, 42, 42, 0.4);

  .iconbianji3 {
    font-size: 20px;
    color: #fff;
  }
}

.app-type {
  display: block;
  margin-bottom: 10px;

  :deep(.el-radio__label) {
    font-size: 13px;
  }
}

.suffix-text {
  font-weight: 400;
  font-size: 12px;
  color: #303133;
}
</style>
