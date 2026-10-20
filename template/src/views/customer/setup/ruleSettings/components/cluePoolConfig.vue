<template>
  <div>
    <!-- 线索池配置 -->
    <div class="card-box">
      <el-card :body-style="{ padding: '20px 20px 0' }" class="box-card">
        <div class="setting-container">
          <el-form
            ref="elForm"
            :model="formData"
            :rules="rules"
            size="default"
            label-width="125px"
            label-position="right"
          >
            <div class="card-list">
              <div class="head">
                <span class="title">提醒规则</span>
              </div>
              <el-form-item label="线索跟进提醒：" prop="clue_follow_switch">
                <el-switch
                  v-model="formData.clue_follow_switch"
                  :active-value="1"
                  :inactive-value="0"
                  active-color="#1890ff"
                  active-text="开启"
                  inactive-color="#cccccc"
                  inactive-text="关闭"
                >
                </el-switch>
              </el-form-item>

              <el-form-item label="线索跟进提醒：" prop="clue_follow_date" v-if="formData.clue_follow_switch">
                <el-input
                  v-model="formData.clue_follow_date"
                  clearable
                  placeholder="请输入正整数"
                  show-word-limit
                >
                  <template #suffix>天</template>
                </el-input>
                <div class="info">用于设置正常状态的线索多少天需要跟进一次</div>
              </el-form-item>
              <!-- <div class="dash-line"></div> -->
              <div class="head mt20">
                <span class="title">退回线索池规则</span>
              </div>
              <el-form-item label="自动退回线索池规则：" prop="return_clue_switch" label-width="auto">
                <el-switch
                  v-model="formData.return_clue_switch"
                  :active-value="1"
                  :inactive-value="0"
                  active-color="#1890ff"
                  active-text="开启"
                  inactive-color="#cccccc"
                  inactive-text="关闭"
                >
                </el-switch>
              </el-form-item>
              <template v-if="formData.return_clue_switch">
                <el-form-item label="未转客户退回：" prop="return_clue_date">
                  <el-input
                    v-model="formData.return_clue_date"
                    clearable
                    placeholder="请输入正整数"
                    show-word-limit
                  >
                    <template #suffix>天</template>
                  </el-input>
                  <div class="info">用于设置线索多少天未转客户，自动退回线索池</div>
                </el-form-item>
                <el-form-item label="未跟进退回：" prop="return_clue_cycle">
                  <el-input
                    v-model="formData.return_clue_cycle"
                    clearable
                    placeholder="请输入正整数"
                    show-word-limit
                  >
                    <template #suffix>天</template>
                  </el-input>
                  <div class="info">用于设置线索多少天未跟进，自动退回线索池</div>
                </el-form-item>
                <el-form-item label="退回线索提醒：" prop="return_clue_remind">
                  <el-input
                    v-model="formData.return_clue_remind"
                    clearable
                    placeholder="请输入正整数"
                    show-word-limit
                  >
                    <template #suffix>天</template>
                  </el-input>
                  <div class="info">用于设置线索退回线索池，提前多少天进行提醒</div>
                </el-form-item>
              </template>
              <div class="head">
                <span class="title">线索保单规则</span>
              </div>
              <el-form-item label="线索保单规则：" prop="clue_policy_switch">
                <el-switch
                  v-model="formData.clue_policy_switch"
                  :active-value="1"
                  :inactive-value="0"
                  active-color="#1890ff"
                  active-text="开启"
                  inactive-color="#cccccc"
                  inactive-text="关闭"
                >
                </el-switch>
              </el-form-item>
              <el-form-item label="保单数量：" prop="clue_policy_count" v-if="formData.clue_policy_switch">
                <el-input
                  v-model="formData.clue_policy_count"
                  clearable
                  placeholder="请输入正整数"
                  show-word-limit
                >
                  <template #suffix>条</template>
                </el-input>
                <div class="info">用于设置每个销售人员最多可以拥有多少条线索</div>
              </el-form-item>
            </div>
          </el-form>
        </div>
      </el-card>
      <div class="cr-bottom-button">
        <el-button type="primary" @click="saveEvt">保存</el-button>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  emits: ['saveEvt'],
  name: 'CluePoolConfig',
  props: {
    formData: {
      default: () => {},
      type: Object
    }
  },
  data() {
    return {
      grid1: {
        xl: 2,
        lg: 4,
        md: 2,
        sm: 24,
        xs: 24
      },
      grid2: {
        xl: 20,
        lg: 18,
        md: 20,
        sm: 24,
        xs: 24
      },
      rules: {
        clue_follow_date: [{ required: true, message: '请输入线索根据提醒' }],
        return_clue_date: [{ required: true, message: '请输入未转客户退回' }],
        return_clue_cycle: [{ required: true, message: '请输入未跟进退回' }],
        return_clue_remind: [{ required: true, message: '请输入退回公海提醒' }],
        clue_policy_count: [{ required: true, message: '请输入保单数量' }]
      }
    }
  },

  methods: {
    saveEvt() {
      this.$emit('saveEvt')
    }
  }
}
</script>
<style lang="scss" scoped>
.cr-bottom-button {
  left: 14px;
  right: 14px;
  width: initial;
}
.card-box {
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;

  .box-card {
    height: calc(100vh - 180px);
    overflow-y: auto;

    .setting-container {
      width: 460px;
      margin: 0 auto;
      .head {
        display: flex;
        margin-bottom: 20px;
        margin-left: -8px;
        .title {
          padding-left: 6px;
          font-size: 14px;
          font-weight: 600;
          position: relative;
        }
        .title:before {
          content: '';
          background-color: #1890ff;
          width: 3px;
          height: 14px;
          position: absolute;
          left: -10px;
          top: 50%;
          margin-top: -7px;
        }
      }
      .card-list {
        // border-spacing: 2px;
        .info {
          margin-top: 10px;
          // margin-left: 10px;
          font-size: 13px;
          line-height: 18px;
          color: #909399;
        }
      }
      .dash-line {
        width: 100%;
        height: 1px;
        background-image: linear-gradient(to right, #dcdfe6 0%, #dcdfe6 50%, transparent 50%);
        background-size: 12px 0.5px; //第一个值（20px）越大线条越长间隙越大
        background-repeat: repeat-x;
        margin-top: 20px;
      }
    }
  }
}
:deep(.el-card.is-always-shadow) {
  border: none;
}
</style>
