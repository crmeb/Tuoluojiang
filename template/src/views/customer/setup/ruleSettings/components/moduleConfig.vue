<template>
  <div class="card-box">
    <el-card :body-style="{ padding: '24px 20px 0' }" class="box-card">
      <div class="setting-container">
        <ul class="process-list">
          <li v-for="(item, index) in processList" :key="item.key" class="process-item">
            <div class="step-col">
              <div class="index-badge">{{ index + 1 }}</div>
            </div>
            <div class="panel">
              <div class="content">
                <div class="name">{{ item.name }}</div>
                <div class="desc">{{ item.desc }}</div>
              </div>
              <el-switch
                v-model="formData[item.key]"
                :active-value="1"
                :inactive-value="0"
                :disabled="item.disabled"
                active-color="#1890ff"
                inactive-color="#dcdfe6"
                active-text="开启"
                inactive-text="关闭"
              />
            </div>
          </li>
        </ul>
      </div>
    </el-card>
    <div class="cr-bottom-button">
      <el-button type="primary" size="small" @click="saveEvt">保存</el-button>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  name: 'ModuleConfig',
  props: {
    formData: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props, { emit }) {
    const processList = ref([
      { key: 'lead_module_switch', name: '线索', desc: '管理潜在客户线索，支持分配与跟进转化', disabled: false },
      { key: 'customer_module_switch', name: '客户', desc: '维护正式客户档案，全生命周期客户管理', disabled: true },
      { key: 'opportunity_module_switch', name: '商机', desc: '跟踪销售机会，推进客户成交流程', disabled: false },
      { key: 'contract_module_switch', name: '合同', desc: '管理签约合同，支持审批、电子签与归档', disabled: false },

      { key: 'order_module_switch', name: '订单', desc: '记录客户订单，关联合同生成结算依据', disabled: true },

      { key: 'invoice_module_switch', name: '发票', desc: '管控开票流程，完成开票、审核与归档', disabled: false }
    ])

    function saveEvt() {
      emit('saveEvt', processList.value)
    }

    // processList 同时供模板渲染与父组件通过 ref 读取
    return { processList, saveEvt }
  }
}
</script>

<style lang="scss" scoped>
$badge-size: 18px;
$badge-gap: 24px;
$panel-width: 390px;
$panel-height: 71px;
$item-gap: 20px;

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
  }

  .setting-container {
    width: $badge-size + $badge-gap + $panel-width;
    margin: 0 auto;
  }

  .process-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .process-item {
    display: flex;
    align-items: stretch;
    margin-bottom: $item-gap;

    &:last-child {
      margin-bottom: 0;
    }

    .step-col {
      position: relative;
      flex: none;
      width: $badge-size;
      height: $panel-height;
      margin-right: $badge-gap;
      display: flex;
      align-items: center;
      justify-content: center;

      &::before,
      &::after {
        content: '';
        position: absolute;
        left: 50%;
        width: 0;
        border-left: 1px dashed #dcdfe6;
        transform: translateX(-0.5px);
      }

      &::before {
        top: 0;
        height: calc(50% - #{$badge-size / 2});
      }

      &::after {
        top: calc(50% + #{$badge-size / 2});
        bottom: -$item-gap;
      }
    }

    &:first-child .step-col::before {
      display: none;
    }

    &:last-child .step-col::after {
      display: none;
    }

    .index-badge {
      position: relative;
      z-index: 1;
      width: $badge-size;
      height: $badge-size;
      border-radius: 50%;
      background-color: #1890ff;
      color: #fff;
      font-size: 12px;
      font-weight: 500;
      line-height: $badge-size;
      text-align: center;
    }

    .panel {
      display: flex;
      align-items: center;
      width: $panel-width;
      height: $panel-height;
      padding: 16px 20px;
      border: 1px solid #dcdfe6;
      border-radius: 8px;
      background-color: #fff;
      box-sizing: border-box;

      .content {
        flex: 1;
        min-width: 0;

        .name {
          font-size: 13px;
          font-weight: bold;
          color: #303133;
          line-height: 18px;
        }

        .desc {
          margin-top: 4px;
          font-size: 12px;
          color: #909399;
          line-height: 17px;
        }
      }
    }
  }
}

::v-deep .el-card.is-always-shadow {
  border: none;
}
</style>
