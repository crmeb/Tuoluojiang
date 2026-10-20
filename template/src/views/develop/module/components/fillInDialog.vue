<template>
  <div class="oa-dialog">
    <el-dialog title="邀请填写" v-model="show" width="600px" :before-close="handleClose">
      <div>
        <div class="mb20">通过链接邀请：</div>
        <div class="flex">
          <el-select v-model="form.role_type" style="width: 100%">
            <el-option v-for="item in roleOptions" :key="item.value" :label="item.label" :value="item.value">
            </el-option>
          </el-select>
          <el-button class="ml10" type="primary" @click="submitFn">复制链接</el-button>
        </div>
        <div class="mt20 day">
          链接有效期：<el-select style="width: 64px" v-model="form.invalid_type">
            <el-option v-for="item in invalidOptions" :key="item.value" :label="item.label" :value="item.value">
            </el-option>
          </el-select>
          到期后通过此链接无法进行表单提交。
        </div>
      </div>
    </el-dialog>
  </div>
</template>
<script>
import { moduleQuestionnaireApi } from '@/api/develop.ts'
import { roterPre } from '@/settings.ts'
import { setShareUnique } from '@/utils/auth.ts'
import SettingMer from '@/libs/settingMer.ts'
import { getStorageJson } from '@/utils/storage.ts'
export default {
  emits: ['getList'],
  data() {
    return {
      show: false,
      form: {
        role_type: '0',
        invalid_type: '30'
      },
      roleOptions: [
        {
          value: '0',
          label: '仅企业员工可见'
        },
        {
          value: '1',
          label: '所有人'
        }
      ],
      invalidOptions: [
        {
          value: '1',
          label: '1天'
        },
        {
          value: '7',
          label: '7天'
        },
        {
          value: '30',
          label: '30天'
        },
        {
          value: '0',
          label: '永久'
        }
      ],
      keyName: ''
    }
  },

  methods: {
    handleClose() {
      this.show = false
    },
    openBox(name) {
      this.keyName = name
      this.show = true
    },
    submitFn() {
      const oInput = document.createElement('textarea')
      const userInfo = getStorageJson('userInfo', {})
      const enterprise = getStorageJson('enterprise', {})
      moduleQuestionnaireApi(this.keyName, this.form).then((res) => {
        if (res.status == 200) {
          setShareUnique(res.data.unique)
          res.data.url = res.data.url + '?role_type=' + res.data.role_type
          const value = `${res.data.url}
          分享人:${userInfo.name || ''}
          企业名称:${enterprise.enterprise_name || ''}
          链接有效期:${this.form.invalid_type}天
          `
          oInput.value = value
          document.body.appendChild(oInput)
          oInput.select()
          document.execCommand('Copy')
          oInput.style.display = 'none'
          document.body.removeChild(oInput)
          this.$message.success('复制成功')
          setTimeout(() => {
            this.handleClose()
          }, 300)
          this.$emit('getList')
        }
      })
    }
  }
}
</script>
<style scoped lang="scss">
:deep(.el-dialog__body) {
  padding: 30px 20px;
}
.day {
  width: 100%;
  height: 42px;
  padding-left: 12px;
  line-height: 42px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
  border: 1px solid #dcdfe6;
  background: #f7f7f7;
  border-radius: 6px 6px 6px 6px;
  :deep(.el-select__wrapper) {
    padding: 0;
    background: #f7f7f7;
    box-shadow: none;
  }
  :deep(.el-select__selected-item),
  :deep(.el-select__caret) {
    color: #1890ff;
  }
}
</style>
