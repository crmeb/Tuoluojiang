<template>
  <div>
    <el-drawer
      :title="isBatch ? '批量设置推送渠道' : '推送渠道设置'"
      :visible.sync="drawer"
      direction="rtl"
      size="600px"
      :before-close="handleClose"
    >
      <div class="content">
        <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="80px" class="mb50">
          <div v-for="(item, index) in list" :key="index" class="mb20">
            <div class="from-item-title">{{ item.title }}</div>
            <el-form-item :label="item.statusName">
              <el-switch
                v-model="ruleForm[item.status]"
                active-value="1"
                inactive-value="0"
                active-text="开启"
                inactive-text="关闭"
              >
              </el-switch>
            </el-form-item>
            <el-form-item :label="item.keyName" v-if="item.keyName">
              <el-input v-model="ruleForm[item.key]" :placeholder="item.placeholder" size="small"></el-input>
            </el-form-item>
          </div>
        </el-form>
        <div class="button from-foot-btn fix btn-shadow">
          <el-button class="el-btn" size="small" @click="handleClose">取消</el-button>
          <el-button :loading="saveLoading" size="small" type="primary" @click="handleConfirm()">保存</el-button>
        </div>
      </div>
    </el-drawer>
  </div>
</template>
<script>
import { getMessageDetailsApi, upDateMessageApi, batchUpdateMessageApi } from '@/api/setting'

const defaultForm = () => ({
  status: '0',
  sms_status: '0',
  template_id: '',
  wework_status: '0',
  work_status: '0',
  work_webhook_url: '',
  ding_status: '0',
  ding_webhook_url: '',
  other_status: '0',
  other_webhook_url: ''
})

export default {
  name: '',
  components: {},
  props: {},
  data() {
    return {
      drawer: false,
      saveLoading: false,
      id: 0,
      ids: [],
      isBatch: false,
      ruleForm: defaultForm(),
      list: [
        {
          title: '系统通知',
          statusName: '通知状态：',
          status: 'status'
        },
        {
          title: '短信',
          statusName: '通知状态：',
          status: 'sms_status',
          keyName: '模板编号：',
          key: 'template_id',
          placeholder: '请输入一号通中短信模板编号'
        },
         {
          title: '企微消息',
          statusName: '通知状态：',
          status: 'wework_status',
        },
        {
          title: '企微BOT',
          statusName: '通知状态：',
          status: 'work_status',
          keyName: '推送地址：',
          key: 'work_webhook_url',
          placeholder: '请输入企业微信机器人生成的Webhook地址'
        },
        {
          title: '钉钉BOT',
          statusName: '通知状态：',
          status: 'ding_status',
          keyName: '推送地址：',
          key: 'ding_webhook_url',
          placeholder: '请输入钉钉机器人生成的Webhook地址'
        },
        {
          title: '其他BOT',
          statusName: '通知状态：',
          status: 'other_status',
          keyName: '推送地址：',
          key: 'other_webhook_url',
          placeholder: '请输入其他第三方机器人生成的Webhook地址'
        }
      ],
      rules: {}
    }
  },

  methods: {
    getData(id) {
      getMessageDetailsApi(id).then((res) => {
        if (res.data.system_template.status) {
          this.$set(this.ruleForm, 'status', res.data.system_template.status.toString())
        }
        if (res.data.sms_template) {
          this.$set(this.ruleForm, 'sms_status', res.data.sms_template.status.toString())
          this.$set(this.ruleForm, 'template_id', res.data.sms_template.template_id)
        }
        if (res.data.wework_template) {
          this.$set(this.ruleForm, 'wework_status', res.data.wework_template.status.toString())
        }
        if (res.data.work_template) {
          this.$set(this.ruleForm, 'work_status', res.data.work_template.status.toString())
          this.$set(this.ruleForm, 'work_webhook_url', res.data.work_template.webhook_url)
        }
        if (res.data.ding_template) {
          this.$set(this.ruleForm, 'ding_status', res.data.ding_template.status.toString())
          this.$set(this.ruleForm, 'ding_webhook_url', res.data.ding_template.webhook_url)
        }
        if (res.data.other_template) {
          this.$set(this.ruleForm, 'other_status', res.data.other_template.status.toString())
          this.$set(this.ruleForm, 'other_webhook_url', res.data.other_template.webhook_url)
        }
      })
    },
    openBox(val) {
      this.isBatch = false
      this.ids = []
      this.id = val.id
      this.ruleForm = defaultForm()
      this.getData(val.id)
      this.drawer = true
    },
    openBatchBox(ids) {
      this.isBatch = true
      this.ids = ids
      this.id = 0
      this.ruleForm = defaultForm()
      this.drawer = true
    },
    handleClose() {
      this.ruleForm = defaultForm()
      this.isBatch = false
      this.ids = []
      this.id = 0
      this.drawer = false
    },
    handleConfirm() {
      this.saveLoading = true
      const request = this.isBatch
        ? batchUpdateMessageApi({ ...this.ruleForm, id: this.ids })
        : upDateMessageApi(this.id, this.ruleForm)
      request
        .then((res) => {
          if (res.status == 200) {
            this.handleClose()
            this.$emit('isOk')
          }
        })
        .finally(() => {
          this.saveLoading = false
        })
    }
  }
}
</script>
<style scoped lang="scss">
.content {
  padding: 20px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;

  .from-item-title {
    height: 14px;
    line-height: 14px;
    font-size: 14px;
    margin-bottom: 20px;
    padding-left: 10px;
    align-items: center;
    font-weight: 500;
    border-left: 3px solid #1890ff;
  }

  .mb50 {
    margin-bottom: 50px;
  }

  ::v-deep .el-form-item__label {
    font-size: 13px;
    color: #606266;
  }
}
</style>
