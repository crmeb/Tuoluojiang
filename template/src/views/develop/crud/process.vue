<!-- 低代码-流程设计页面 -->
<template>
  <div>
    <el-card class="examineCard">
      <el-row>
        <el-col :span="9">
          <div class="title-16 mt23">流程设计</div>
        </el-col>
        <el-col :span="12">
          <el-tabs v-model="activeName" class="examineTabs">
            <el-tab-pane v-for="(item, index) in tabArray" :key="index" :name="item.value">
              <template #label>
                <div>
                  <span class="sp1">{{ item.number }}</span>
                  <span>{{ item.label }}</span>
                </div>
              </template>
            </el-tab-pane>
          </el-tabs>
        </el-col>
        <el-col :span="3">
          <div class="flex-end">
            <el-button class="mt14" @click="submit">仅保存</el-button>
            <el-button class="mt14" type="primary" @click="submit(1)">保存并关闭</el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>
    <div class="card-box">
      <!-- 基础设置 -->
      <div class="main" v-show="activeName === 'basicSetting'">
        <el-form ref="elForm" class="mt30" :model="baseConfig" :rules="rules" label-width="110px">
          <el-form-item label="应用实体：" prop="crud_id">
            <el-cascader
              v-model="baseConfig.crud_id"
              placeholder="请选择应用实体"
              :options="options"
              :show-all-levels="false"
              filterable
              :disabled="$route.query.crud_id ? true : false"
              :props="props"
              style="width: 100%"
              clearable
            >
            </el-cascader>
          </el-form-item>
          <el-form-item label="流程名称：" prop="name">
            <el-input v-model="baseConfig.name" placeholder="请输入流程名称"></el-input>
          </el-form-item>
          <el-form-item label="审批流图标：" prop="icon">
            <div
              v-if="baseConfig.icon"
              class="selIcon mr15"
              @click.stop="handleIcon"
              :style="{ backgroundColor: baseConfig.color }"
            >
              <i class="icon iconfont" :class="baseConfig.icon" style="color: #fff"></i>
            </div>
            <el-popover ref="iconPopover" placement="bottom" width="400" trigger="click">
              <div v-for="(i, index) in iconList" :key="index" class="icon-item" @click="itemChose(i)">
                <i class="icon iconfont" :class="i.icon" :style="{ color: i.color }"></i>
              </div>
              <template #reference>
                <el-button>选择图标</el-button>
              </template>
            </el-popover>
          </el-form-item>
          <el-form-item label="审批说明：" prop="flowRemark">
            <el-input
              v-model="baseConfig.info"
              type="textarea"
              placeholder="请输入审批类型说明"
              :maxlength="100"
              show-word-limit
              :autosize="{ minRows: 4, maxRows: 4 }"
              :style="{ width: '100%' }"
            ></el-input>
          </el-form-item>
          <el-form-item label="排序：" prop="flowRemark">
            <el-input
              v-model="baseConfig.sort"
              type="number"
              placeholder="请输入排序"
              :style="{ width: '100%' }"
            ></el-input>
          </el-form-item>
        </el-form>
      </div>
      <!-- 流程设计 -->
      <div class="center" v-show="activeName === 'processSetting'">
        <processSetting ref="processSetting" :conf="mockData.processConfig" :id="baseConfig.crud_id"></processSetting>
      </div>
      <!-- 规则设置 -->
      <div class="main" v-show="activeName === 'ruleSetting'">
        <el-form ref="elForm" class="mt30" :model="ruleConfig" :rules="rules" size="default" label-width="150px">
          <el-form-item label="异常处理：" prop="abnormal">
            <el-radio-group v-model="ruleConfig.abnormal">
              <el-radio :value="0">自动同意</el-radio>
              <el-radio :value="1">转交给指定人员处理</el-radio>
            </el-radio-group>
            <select-member
              v-if="ruleConfig.abnormal !== 0"
              :only-one="true"
              :value="userList || []"
              @getSelectList="getSelectList"
              style="width: 100%"
            ></select-member>
          </el-form-item>

          <el-form-item label="自动审批：">
            <div class="auto-text">当同一个审批人重复审批同一单据时</div>
            <el-radio-group v-model="ruleConfig.auto" class="shu">
              <el-radio :value="0">仅首个节点需审批，其余自动同意</el-radio>
              <el-radio :value="1">仅连续审批时自动同意</el-radio>
              <el-radio :value="2">每个节点都需要审批</el-radio>
            </el-radio-group>
          </el-form-item>

          <el-form-item label="修改权限：" class="shu">
            <el-checkbox-group v-model="ruleConfig.edit">
              <el-checkbox :value="1">提交申请时，员工不可修改固定人审批人</el-checkbox>
              <el-checkbox :value="2">提交申请时，员工不可删除固定抄送人</el-checkbox>
            </el-checkbox-group>
          </el-form-item>

          <el-form-item label="撤销权限：">
            <template #label>
              <div>撤销审批：</div>
            </template>

            <el-checkbox v-model="ruleConfig.recall">通过后允许撤销</el-checkbox>
            <span class="tips">审批通过后，经审批人同意可撤销</span>
            <div class="explain"></div>
          </el-form-item>
          <el-form-item label="加签权限：">
            <el-checkbox v-model="ruleConfig.is_sign">允许在审批单中增加临时审批人</el-checkbox>
          </el-form-item>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script>
import selectMember from '@/components/form-common/select-member'
import processSetting from '@/components/develop/processSetting'
import iconfontList from '@/views/business/components/basicSetting/iconfontList.ts'
import basicSetting from '@/views/business/components/basicSetting/index'
import debounce from '@form-create/utils/lib/debounce'
import { getDatabaseApi, dataApproveSaveApi, dataApproveInfoApi, dataApprovePutApi } from '@/api/develop.ts'

export default {
  components: { basicSetting, processSetting, selectMember },
  data() {
    return {
      id: 0, // 流程id
      loading: false,
      type: '',
      iconList: iconfontList,
      options: [], // 实体数据
      userList: [], // 选择指定人员
      activeName: 'basicSetting',
      tabArray: [
        { label: '基础配置', value: 'basicSetting', number: 1 },
        { label: '流程配置', value: 'processSetting', number: 2 },
        { label: '规则配置', value: 'ruleSetting', number: 3 }
      ],
      baseConfig: {
        // 基础设置
        crud_id: '',
        name: '',
        icon: '',
        info: '',
        color: ''
      },
      props: {
        multiple: false,
        label: 'label',
        value: 'value',
        children: 'children',
        emitPath: false //绑定的内容只获取最后一级的value值。
      },
      ruleConfig: {
        abnormal: 0,
        auto: 0,
        edit: [1, 2],
        recall: false,
        is_sign: false
        // 规则设置
      },
      rules: {
        crud_id: [
          {
            required: true,
            message: '请选择应用实体',
            trigger: 'change'
          }
        ],
        name: [
          {
            required: true,
            message: '请选择输入流程名称',
            trigger: 'blur'
          }
        ],
        icon: [
          {
            required: true,
            message: '请选择模板图标',
            trigger: 'change'
          }
        ],
        abnormal: [
          {
            required: true,
            message: '请选择应用实体',
            trigger: 'change'
          }
        ],
        auto: [
          {
            required: true,
            message: '请选择应用实体',
            trigger: 'change'
          }
        ]
      },
      mockData: {}
    }
  },

  created() {
    if (this.$route.query.id > 0) {
      this.type = 'edit'
      this.id = Number(this.$route.query.id)
      this.getInfo()
    }
  },

  mounted() {
    if (this.$route.query.crud_id) {
      this.baseConfig.crud_id = Number(this.$route.query.crud_id)
    }
    this.getList()
  },

  methods: {
    // 获取实体数据
    async getList() {
      const data = await getDatabaseApi()
      // 解决一级value和二级value重复，导致选中不了问题
      data.data.forEach((item) => {
        item.value = item.value + 'res'
      })
      this.options = data.data
    },

    // 获取流程详情
    getInfo() {
      dataApproveInfoApi(this.id).then((res) => {
        this.baseConfig = res.data.baseConfig
        this.ruleConfig = res.data.ruleConfig
        if (this.ruleConfig.ab_card && this.ruleConfig.abnormal !== 0) {
          this.ruleConfig.ab_card.value = this.ruleConfig.ab_card.id
          this.userList = [this.ruleConfig.ab_card]
          this.ruleConfig.abnormal = 1
        }

        this.ruleConfig.auto = Number(this.ruleConfig.auto)
        this.ruleConfig.edit = this.ruleConfig.edit.map((str) => parseInt(str))
        this.ruleConfig.is_sign = this.ruleConfig.is_sign == 1 ? true : false
        this.ruleConfig.recall = this.ruleConfig.recall == 1 ? true : false
        this.mockData = res.data
      })
    },

    itemChose(row) {
      this.baseConfig.icon = row.icon
      this.baseConfig.color = row.color
      this.$refs.iconPopover.hide()
    },

    // 仅保存
    submit: debounce(function (val) {
      const p1 = this.$refs.processSetting.getData()
      this.ruleConfig.is_sign ? (this.ruleConfig.is_sign = 1) : 0
      this.ruleConfig.recall ? (this.ruleConfig.recall = 1) : 0
      Promise.all([p1])
        .then((res) => {
          const param = {
            baseConfig: this.baseConfig,
            ruleConfig: this.ruleConfig,
            processConfig: res[0]
          }

          if (this.userList && this.userList.length > 0) {
            this.ruleConfig.abnormal = this.userList[0].value ? this.userList[0].value : this.userList[0].id
          }

          if (!this.baseConfig.name) return this.$message('流程名称不能为空')
          if (!this.baseConfig.icon) return this.$message('流程图标不能为空')
          this.loading = true
          this.sendToServer(param, val)
        })
        .catch((err) => {
          this.activeName = err.target
          if (this.activeName === 'processSetting') {
            this.$refs.processSetting.tipVisible = true
          } else {
            this.$refs.processSetting.tipVisible = false
          }
          err.target && (this.activeStep = err.target)
          err.msg && this.$message.warning(err.msg)
        })
    }, 1000),

    sendToServer(data, val) {
      if (this.id > 0) {
        dataApprovePutApi(this.id, data).then((res) => {
          if (val == 1 && res.status == 200) {
            this.handleClose()
          }
          this.loading = false
          this.ruleConfig.is_sign = this.ruleConfig.is_sign == 1 ? true : false
          this.ruleConfig.recall = this.ruleConfig.recall == 1 ? true : false
        })
      } else {
        dataApproveSaveApi(data).then((res) => {
          this.id = res.data.id
          if (val == 1 && res.status == 200) {
            this.handleClose()
          }
          this.loading = false
          this.ruleConfig.is_sign = this.ruleConfig.is_sign == 1 ? true : false
          this.ruleConfig.recall = this.ruleConfig.recall == 1 ? true : false
        })
      }
    },

    // 选择成员完成回调
    getSelectList(data) {
      this.userList = data
    },
    handleClose() {
      window.opener = null
      window.open('about:blank', '_top').close()
    },

    getCmpData(name) {
      return this.$refs[name].getData()
    },

    crudChange(e) {
      this.baseConfig.crud_id = Number(e)
    },

    // 上一步
    previousStep() {
      if (this.activeName == 'basicSetting') {
        this.activeName = 'basicSetting'
      }

      if (this.activeName == 'processSetting') {
        this.activeName = 'basicSetting'
      }
      if (this.activeName == 'ruleSetting') {
        this.activeName = 'processSetting'
      }
    },

    // 下一步
    nextStep() {
      if (this.activeName == 'basicSetting') {
        this.activeName = 'processSetting'
      } else if (this.activeName == 'processSetting') {
        this.activeName = 'ruleSetting'
      } else if (this.activeName == 'ruleSetting') {
        this.$message.error('没有下一步')
        this.activeName = 'ruleSetting'
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.mt23 {
  margin-top: 23px;
}
.icon-item,
.selIcon {
  display: inline-block;
  width: 40px;
  height: 40px;
  line-height: 40px;
  text-align: center;
  cursor: pointer;
  border-radius: 3px;
}
.tips {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #909399;
}
.auto-text {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
}

:deep(.el-tabs__header) {
  margin: 0;
}
:deep(.el-page-header) {
  margin-top: 25px;
}

:deep(.el-table th) {
  background-color: #f7fbff;
}
.shu {
  :deep(.el-radio),
  :deep(.el-checkbox) {
    display: block;
    height: 32px;
    line-height: 32px;
  }
}

:deep(.el-page-header__content) {
  font-family: PingFang SC, PingFang SC;
  font-weight: 500;
  font-size: 18px;
  color: #303133;
}
.examineTabs {
  :deep(.el-tabs__nav-wrap::after) {
    background-color: #fff;
  }
  .sp1 {
    width: 16px;
    height: 16px;
    border: 1px solid #999999;
    border-radius: 50%;
    display: inline-block;
    text-align: center;
    line-height: 14px;
    font-size: 12px;
  }
  :deep(.el-tabs__nav-scroll) {
    display: flex;
    justify-content: left;
  }
  :deep(.el-tabs__item) {
    font-family: PingFang SC, PingFang SC;
    font-weight: 500;
    font-size: 14px;
    color: #909399;
    height: 63px;
    line-height: 63px;
    &.is-active {
      color: #1890ff;
      .sp1 {
        border-color: #1890ff;
        background-color: #1890ff;
        color: #fff;
      }
    }
  }
}
.card-box {
  height: calc(100vh - 69px);
  overflow-y: scroll;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
  background-color: #fff;
  .main {
    width: 500px;
    margin: 0 auto;
  }
}
.examineCard {
  height: 64px;
  border-bottom: 1px solid #dcdfe6;

  :deep(.el-card__body) {
    padding: 0px 20px 0 20px;
  }

  .title {
    display: block;
  }
}
.cr-bottom-button {
  position: fixed;
  left: -20px;
  right: 0;
  bottom: 0;
  width: calc(100% + 220px);
}
:deep(.el-form-item) {
  .el-form-item__label {
    font-family: PingFang SC, PingFang SC;
    font-weight: 500;
    font-size: 13px;
    color: rgba(0, 0, 0, 0.85);
  }
}
</style>
