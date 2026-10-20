<template>
  <!-- 添加人员弹窗/批量导入 -->
  <el-dialog
    :title="personConfig.title"
    v-model="dialogFormVisible"
    width="500px"
    :modal="false"
    :close-on-click-modal="false"
    class="person"
    :before-close="handleClose"
  >
    <div v-if="personConfig.type == 2">
      <div class="updata">
        <div class="name mb15">{{ excelFail.name }}</div>
        <el-upload
          class="upload-demo mb10"
          :action="fileUrl"
          :on-success="handleSuccess"
          :headers="myHeaders"
          :show-file-list="false"
          :data="uploadData"
          multiple
          accept=".xls,.xlsx"
          :before-upload="beforeUpload"
        >
          <template #trigger>
            <el-button type="primary">选取文件</el-button>
          </template>
        </el-upload>
        <a
          ><el-button type="primary" link @click="handleDownLoad">点击下载模板</el-button></a
        >
      </div>
      <div class="dialog-footer">
        <el-button @click="close">取消</el-button>
        <el-button type="primary" @click="determine">确定</el-button>
      </div>
    </div>
  </el-dialog>
</template>

<script>
import { getToken } from '@/utils/auth.ts'
import SettingMer from '@/libs/settingMer.ts'
import { downloadUrlApi } from '@/api/public.ts'
import { userBatchCreate } from '@/api/user.ts'
export default {
  name: 'UpdataXls',
  props: {
    frameId: {
      type: [String, Number],
      default: 0
    },
    personConfig: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dialogFormVisible: false,
      myHeaders: {
        authorization: 'Bearer ' + getToken()
      },
      uploadData: {},
      excelFail: {},
      option: {
        form: {
          labelWidth: '75px'
        },
        submitBtn: true,
        global: {
          frame: {
            props: {
              closeBtn: false,
              okBtn: false,
              onLoad: (e) => {
                e.fApi = this.$refs.fc.fapi
              }
            }
          }
        },
        onSubmit: (formData) => {
          // 表单提交处理
        }
      }, // 表单配置
      FromData: null,
      rules: [
        {
          type: 'input',
          field: 'field1',
          title: 'field1',
          props: {
            placeholder: 'asdasd'
          },
          value: '',
          validate: [{ type: 'string', required: true, message: '请输入名称' }]
        },
        {
          type: 'div',
          class: 'asdasd',
          children: ['asdasd']
        }
      ]
    }
  },
  computed: {
    fileUrl() {
      return `${SettingMer.https}/common/upload`
    }
  },
  methods: {
    determine() {
      if (!this.excelFail.url) return this.$message.error('请选取文件')
      const data = {
        frame_id: this.frameId,
        file: this.excelFail.url
      }
      userBatchCreate(data).then((res) => {
        this.excelFail.name = ''
        this.dialogFormVisible = false
      })
    },
    open() {
      this.dialogFormVisible = true
    },
    handleClose(done) {
      this.excelFail = {}
      if (this.personConfig.type === 1) this.$refs.ruleForm.resetFields()
      done()
    },
    close() {
      this.excelFail.name = ''
      this.dialogFormVisible = false
    },
    // 上传成功
    handleSuccess(response) {
      if (response.status === 200) {
        this.$message.success('上传成功')
        this.excelFail = response.data
      } else {
        this.$message.error(response.message)
      }
    },
    beforeUpload(file) {
      const testmsg = file.name.substring(file.name.lastIndexOf('.') + 1)
      const extension = testmsg === 'xls'
      const extension2 = testmsg === 'xlsx'
      if (!extension && !extension2) {
        this.$message({
          message: '上传文件只能是 xls、xlsx格式!',
          type: 'warning'
        })
      }
      return extension || extension2
    },
    // 下载
    async handleDownLoad() {
      const result = await downloadUrlApi({
        type: 'apply'
      })
      this.dialogFormVisible = false
      window.open(result.data.download_url, '_blank')
    }
  }
}
</script>

<style lang="scss" scoped>
.model-title {
  border-bottom: 1px solid #dfe6ec;
}
.form-box {
  margin-top: 20px;
}
.tips {
  margin-top: 10px;
  font-size: 14px;
  color: #999;
  line-height: 1.5;
}

.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
.updata {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 150px;
}
</style>
