<template>
  <el-drawer :visible.sync="visible" :size="DRAWER_SIZE.LG" custom-class="stock-ledger-drawer" @close="reset">
    <!-- 抽屉头部：商品缩略图 + 名称 + 规格 + 编码 -->
    <template #title>
      <GoodsBrief :product="row.product" :sku="row.sku" />
    </template>

    <!-- 库存属性页签：良品台账 / 残次品台账，紧贴抽屉边框与头部，不受表格区内边距影响 -->
    <el-tabs type="border-card" v-model="activeQuality" class="ledger-tabs" @tab-click="handleQualityChange">
      <el-tab-pane v-for="tab of LEDGER_QUALITY_TABS" :key="tab.name" :name="tab.name" :label="tab.label" />
    </el-tabs>

    <div class="ledger-body">
      <BaseTable :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
        :page.sync="page" :page-size.sync="pageSize">
        <!-- 库存变化：变动前 -> 变动后 + 差值角标 -->
        <template #change="{ row: item }">
          <div class="stock-change flex">
            <span>{{ formatStock(item.before_num) }}</span>
            <span class="stock-change__arrow" :class="item.change_num >= 0 ? 'is-plus' : 'is-minus'">
              {{ item.change_num >= 0 ? '+' : '' }}{{ formatStock(item.change_num) }}
            </span>
            <span>{{ formatStock(item.after_num) }}</span>
          </div>
        </template>

        <!-- 变动原因：调拨方向补充对方仓库名 -->
        <template #reason="{ row: item }">{{ getReason(item) }}</template>

        <!-- 变动情况：操作人 / 变动时间 / 来源单号 -->
        <template #detail="{ row: item }">
          <div class="change-detail">
            <div>操作人：{{ item.operator_info?.name || '--' }}</div>
            <div>变动时间：{{ item.created_at || '--' }}</div>
            <div v-if="item.source_no">{{ getBillLabel(item) }}：{{ item.source_no }}</div>
          </div>
        </template>
      </BaseTable>
    </div>
  </el-drawer>
</template>

<script>
import { DRAWER_SIZE } from "@/constants/popupSize";
import BaseTable from "@/components/BaseTable/index.vue";
import GoodsBrief from "./GoodsBrief.vue";
import { useLedger } from "../composables/useLedger";
import { LEDGER_QUALITY_TABS } from "../constants";
import { formatStock } from "../utils";

export default {
  name: "LedgerDrawer",
  components: { BaseTable, GoodsBrief },
  setup(props, { expose }) {
    const ledgerInfo = useLedger();

    expose({ openBox: ledgerInfo.openBox });

    return {
      ...ledgerInfo,

      DRAWER_SIZE,
      LEDGER_QUALITY_TABS,
      formatStock
    };
  }
};
</script>

<style scoped lang="scss">
:deep(.stock-ledger-drawer) {
  .el-drawer__header {
    height: initial;
  }
}

// 库存属性页签：整条通栏紧贴抽屉边框，仅页签文字留出左侧视觉间距
.ledger-tabs {
  border: none;
  box-shadow: none;
  :deep(.el-tabs__content) {
    display: none;
  }

  :deep(.el-tabs__header) {
    background-color: #f7fbff;
    border: none;
  }

  :deep(.el-tabs__item) {
    height: 40px;
    line-height: 40px;
    border-left: none !important;
    border-right: none !important;
    border-top: 2px solid transparent;
    margin-top: 0;
    &.is-active {
      border-top-color: #1890cc;
    }
  }
}

// 表格区：内边距只作用于表格，不影响上方页签
.ledger-body {
  display: flex;
  flex-flow: column nowrap;
  flex: 1;
  padding: 20px;
}

// 库存变化：变动前 -> 变动后 + 差值角标
.stock-change {
  align-items: center;
  gap: 8px;
  color: #303133;

  &__arrow {
    position: relative;
    min-width: 32px;
    padding: 0 2px 2px;
    font-size: 12px;
    text-align: center;
    border-bottom: 1px solid #dcdfe6;

    &::after {
      content: "";
      position: absolute;
      right: -1px;
      bottom: -3px;
      border: 3px solid transparent;
      border-left-color: #dcdfe6;
    }

    &.is-plus {
      color: #19be6b;
    }

    &.is-minus {
      color: #ed4014;
    }
  }
}

// 变动情况：三行信息
.change-detail {
  padding: 8px 0;
  line-height: 22px;
  color: #303133;
}
</style>

<style lang="scss">
.stock-ledger-drawer {
  .el-drawer__header {
    border-bottom: none;
  }
  // 页签与表格区上下排布，表格区占满剩余高度
  .el-drawer__body {
    display: flex;
    flex-flow: column nowrap;
    overflow-y: auto;
  }
}
</style>
