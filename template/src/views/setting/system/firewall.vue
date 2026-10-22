<template>
  <div class="divBox firewall-box">
    <div class="box-height">
      <el-card class="card-head normal-page" shadow="never" :body-style="{ padding: '20px 20px 0 20px' }">
        <el-form :model="formData" :inline="true" class="form-box" size="small" label-width="84px">
          <el-form-item label="开关：">
            <el-radio-group v-model="formData.status">
              <el-radio :label="0" :value="0">关闭</el-radio>
              <el-radio :label="1" :value="1">拦截</el-radio>
              <el-radio :label="2" :value="2">过滤</el-radio>
            </el-radio-group>
            <div class="form-item-tips">
              关闭：不验证请求数据；拦截：若非发请求则返回错误；过滤：过滤掉非法参数，程序继续执行
            </div>
          </el-form-item>
          <template v-if="formData.status">
            <el-form-item label="常用规则：">
              <div class="firewall-preset-box">
                <div class="firewall-preset-item" v-for="preset in presetRules" :key="preset.label">
                  <el-checkbox
                    :value="isPresetChecked(preset)"
                    :indeterminate="isPresetIndeterminate(preset)"
                    @change="(checked) => handleTogglePreset(preset, checked)"
                    >{{ preset.label }}</el-checkbox
                  >
                  <el-tooltip placement="top" :content="preset.rules.join('；')">
                    <i class="el-icon-question preset-tips-icon" />
                  </el-tooltip>
                </div>
              </div>
              <div class="form-item-tips preset-tips">勾选常用防护规则后自动加入下方规则列表，也可手动添加自定义规则</div>
            </el-form-item>
            <el-form-item label="规则设置：">
              <div class="firewall-rule-item" v-for="(item, index) of formData.ruleList" :key="index">
                <el-tag v-if="ruleLabelMap[item]" size="mini" type="info" class="rule-tag">{{
                  ruleLabelMap[item]
                }}</el-tag>
                <el-input v-model="formData.ruleList[index]" placeholder="请输入正则规则，如 /select|union/i" clearable />

                <div class="delete-btn-box">
                  <el-button type="text" class="delete-btn" @click="handleDeleteRule(index)">删除</el-button>
                </div>
              </div>
              <el-button type="text" icon="el-icon-plus" @click="handleAddRule" style="margin: 10px 0"
                >添加规则</el-button
              >
            </el-form-item>
          </template>
          <el-form-item class="save-item">
            <el-button type="primary" @click="handleSaveRule">保存</el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script>
import { getFirewallConfigApi, saveFirewallConfigApi } from '@/api/setting'

export default {
  data() {
    return {
      formData: {
        status: 0, // 0 -> 关闭，1 -> 拦截, 2 -> 过滤
        ruleList: ['']
      },
      // 常用防护规则预设（取自安装默认规则），勾选后写入规则列表
      presetRules: [
        {
          label: 'SQL 注入',
          rules: [
            '/(union[\\s\\S]*?select)/i',
            '/(select[\\s\\S]*?)(from|limit)/i',
            '/(having|updatexml|extractvalue)/i',
            '/(?:from\\W+information_schema\\W)/i',
            '/group\\s+by.+\\(/i',
            '/into(\\s+)(?:dump|out)file\\s*/i',
            '/(?:current_|user|database|schema|connection_id)\\s*\\(/i',
            '/\\sand\\s+.*=.*/i',
            '/\\bor\\b.*=.*/i'
          ]
        },
        {
          label: 'SQL 盲注',
          rules: ['/sleep\\((\\s*)(\\d*)(\\s*)\\)/i', '/benchmark\\((.*)\\,(.*)\\)/i']
        },
        {
          label: 'XSS 跨站脚本',
          rules: [
            '/(onmouseover|onerror|onload|onclick)\\=/i',
            '/<(iframe|script|body|img|layer|div|meta|style|base|object|input)/i'
          ]
        },
        {
          label: '命令执行',
          rules: [
            '/\\|\\|.*?(?:ls|pwd|whoami|ll|ifconfig|ipconfig|&&|chmod|cd|mkdir|rmdir|cp|mv)/i'
          ]
        },
        {
          label: 'PHP 代码执行',
          rules: [
            '/(?:define|eval|file_get_contents|include|require|require_once|shell_exec|phpinfo|system|passthru|preg_\\w+|execute|echo|print|print_r|var_dump|(fp)open|alert|showmodaldialog)\\(/i',
            '/base64_decode\\(/i',
            '/<\\?/'
          ]
        },
        {
          label: 'PHP 超全局变量',
          rules: [
            '/\\$_(GET|POST|COOKIE|FILES|SESSION|ENV|GLOBALS|SERVER)/i',
            '/\\$_(GET|POST|COOKIE|FILES|SESSION|ENV|GLOBALS|SERVER)\\[/i'
          ]
        },
        {
          label: '伪协议 / 文件包含',
          rules: ['/(gopher|doc|php|glob|file|phar|zlib|ftp|ldap|dict|ogg|data):\\/\\//i']
        },
        {
          label: '路径穿越',
          rules: ['/\\.\\./', '/(?:etc\\/\\W*passwd)/i']
        }
      ]
    }
  },
  computed: {
    // 规则 -> 常用规则分组名，用于规则列表行展示标签
    ruleLabelMap() {
      const map = {}
      this.presetRules.forEach((preset) => {
        preset.rules.forEach((rule) => {
          map[rule] = preset.label
        })
      })
      return map
    }
  },
  created() {
    this.getFirewallConfig()
  },
  methods: {
    async getFirewallConfig() {
      const res = await getFirewallConfigApi()
      const { firewall_switch, firewall_content } = res.data
      this.formData = {
        status: firewall_switch,
        ruleList: firewall_content?.length ? firewall_content : ['']
      }
    },
    isPresetChecked(preset) {
      return preset.rules.every((rule) => this.formData.ruleList.includes(rule))
    },
    isPresetIndeterminate(preset) {
      return preset.rules.some((rule) => this.formData.ruleList.includes(rule)) && !this.isPresetChecked(preset)
    },
    handleTogglePreset(preset, checked) {
      if (checked) {
        preset.rules.forEach((rule) => {
          if (!this.formData.ruleList.includes(rule)) this.formData.ruleList.push(rule)
        })
      } else {
        this.formData.ruleList = this.formData.ruleList.filter((rule) => !preset.rules.includes(rule))
        if (!this.formData.ruleList.length) this.formData.ruleList.push('')
      }
    },
    handleAddRule() {
      this.formData.ruleList.push('')
    },
    handleSaveRule() {
      // 去空、去重后保存，保存格式与原来保持一致
      const content = [...new Set(this.formData.ruleList.map((rule) => rule.trim()))].filter((rule) => rule)
      this.formData.ruleList = content.length ? content : ['']
      const data = {
        firewall_switch: this.formData.status,
        firewall_content: content
      }
      saveFirewallConfigApi(data)
    },
    handleDeleteRule(index) {
      this.formData.ruleList.splice(index, 1)
      if (!this.formData.ruleList.length) this.formData.ruleList.push('')
    }
  }
}
</script>

<style scoped lang="scss">
.form-box {
  width: 650px;
  margin: 20px auto 0;
}

.form-item-tips {
  font-size: 13px;
  color: #909399;
}

.firewall-box {
  ::v-deep .el-form-item {
    width: 100%;
    display: flex;
  }

  ::v-deep .el-form-item__content {
    flex: 1;
  }

  ::v-deep .el-icon-circle-close::before {
    content: '\e79d';
  }

  ::v-deep .el-form-item__label {
    color: #606266;
  }

  // 保存按钮与表单内容列对齐
  .save-item ::v-deep .el-form-item__content {
    margin-left: 84px;
  }

  .firewall-preset-box {
    box-sizing: border-box;
    width: 100%;
    padding: 14px 16px 0;
    background: #f7f8fa;
    border-radius: 4px;
    display: flex;
    flex-wrap: wrap;

    .firewall-preset-item {
      display: flex;
      align-items: center;
      width: 50%;
      margin-bottom: 14px;
    }

    .preset-tips-icon {
      margin-left: 2px;
      color: #c0c4cc;
    }
  }

  .preset-tips {
    margin-top: 8px;
  }

  .firewall-rule-item {
    position: relative;
    display: flex;
    align-items: center;

    & + .firewall-rule-item {
      margin-top: 12px;
    }

    .rule-tag {
      flex-shrink: 0;
      margin-right: 8px;
    }

    ::v-deep .el-input {
      flex: 1;
    }

    .delete-btn-box {
      position: absolute;
      left: 100%;
      padding-left: 10px;
      top: 0;
      font-size: 14px;
      display: none;
      .delete-btn {
        color: red;
      }
    }

    &:hover {
      .delete-btn-box {
        display: block;
      }
    }
  }
}
</style>
