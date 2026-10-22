<template>
  <div class="card-box">
    <el-card :body-style="{ padding: '20px 20px 0' }" class="box-card">
      <div class="setting-container">
        <el-form ref="elForm" :model="formData" size="medium" label-width="150px">
          <el-form-item label="自动生成出库单规则：" prop="order_auto_outbound_rule" style="margin-bottom: 22px;">
            <el-radio-group v-model="formData.order_auto_outbound_rule" class="rule-radio-group">
              <div v-for="item in autoOutboundOptions" :key="item.value" class="rule-radio-item">
                <el-radio :label="item.value">{{ item.label }}</el-radio>
                <div class="info">{{ item.desc }}</div>
              </div>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="订单发货规则：" prop="order_delivery_rule">
            <el-radio-group v-model="formData.order_delivery_rule" class="rule-radio-group">
              <div v-for="item in deliveryRuleOptions" :key="item.value" class="rule-radio-item">
                <el-radio :label="item.value">{{ item.label }}</el-radio>
                <div class="info">{{ item.desc }}</div>
              </div>
            </el-radio-group>
          </el-form-item>
        </el-form>
      </div>
    </el-card>
    <div class="cr-bottom-button">
      <el-button size="small" type="primary" @click="saveEvt">保存</el-button>
    </div>
  </div>
</template>
<script>
export default {
  name: 'OutboundConfig',
  props: {
    formData: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props, { emit }) {
    const autoOutboundOptions = [
      {
        value: 1,
        label: '添加订单时自动生成出库单',
        desc: '添加订单后，系统自动根据订单中的产品清单，生成出库单，状态为待出库'
      },
      {
        value: 0,
        label: '不自动生成出库单',
        desc: '系统不自动生成出库单，但添加订单后，支持手动关联此订单创建出库单'
      }
    ]
    const deliveryRuleOptions = [
      {
        value: 0,
        label: '订单首次回款后，才能拣货出库',
        desc: '只要回过一次款，无论订单是否结清，也支持库管员进行拣货出库'
      },
      {
        value: 1,
        label: '订单结清后，才能拣货出库',
        desc: '订单结清后，才支持库管员进行拣货出库'
      },
      {
        value: 2,
        label: '订单未回款时，仍支持拣货出库',
        desc: '订单没有回过款，仍支持库管员进行拣货出库'
      }
    ]

    const saveEvt = () => {
      emit('saveEvt')
    }

    return {
      autoOutboundOptions,
      deliveryRuleOptions,
      saveEvt
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
      width: 900px;
      margin: 0 auto;
      .rule-radio-group {
        display: block;
      }
      .rule-radio-item {
        line-height: 20px;
        margin-bottom: 16px;
        &:last-child {
          margin-bottom: 0;
        }
        ::v-deep .el-radio {
          margin-right: 0;
          // 覆盖 global.scss 中 .el-form-item--medium .el-radio 的 36px 行高
          line-height: 20px !important;
        }
        .info {
          margin-top: 4px;
          padding-left: 24px;
          font-size: 13px;
          line-height: 18px;
          color: #909399;
        }
      }
    }
  }
}
::v-deep .el-card.is-always-shadow {
  border: none;
}
// 与 .rule-radio-item 内单选项的 20px 行高对齐，特异性需高于父级 scoped 的 38px
::v-deep .el-form-item .el-form-item__label {
  line-height: 20px;
}
::v-deep .el-radio__label {
  font-size: 13px;
  color: #303133;
}
</style>
