<template>
  <BaseContainer class="none-bg none-padding">
    <div class="stock-card has-bg stock-search">
      <oaFromBox :search="searchFields" :rightAreaVisible="false" :isAddBtn="false" :total="total"
      @confirmData="handleSearch" />
      <el-button class="h32 stock-export" :loading="exportLoading" :disabled="exportLoading"
        @click="handleExport($refs.exportExcel)">导出</el-button>
    </div>

    <div class="stock-card stock-body">
      <!-- 库存状态页签：全部商品 / 库存过低 / 库存过高 / 已售罄 -->
      <div class="relative">
        <el-tabs v-model="activeTab" class="stock-tabs" @tab-change="handleTabChange">
          <el-tab-pane v-for="tab of STOCK_TABS" :key="tab.name" :name="tab.name"
            :label="`${tab.label}(${tabCounts[tab.countKey] || 0})`" />
        </el-tabs>
        <!-- 页签右上角：存在占用 / 在途库存筛选 -->
        <div class="action-tabs">
          <el-checkbox v-model="hasOccupy" @change="handleFilterChange">存在占用库存</el-checkbox>
          <el-checkbox v-model="hasTransit" @change="handleFilterChange">存在在途库存</el-checkbox>
        </div>
      </div>
        
      <BaseTable :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total" v-model:page="page"
        v-model:page-size="pageSize">
        <!-- 商品信息：缩略图 + 名称 -->
        <template #goods="{ row }">
          <div class="goods-cell flex">
            <el-image :src="row.sku && row.sku.image" :preview-src-list="row.sku && row.sku.image ? [row.sku.image] : []" preview-teleported
              class="goods-img" />
            <span class="goods-name">{{ getGoodsName(row) }}</span>
          </div>
        </template>

        <!-- 规格：规格名 / 编码 / 条码 -->
        <template #spec="{ row }">
          <div class="spec-cell">
            <div>{{ getSpecName(row) }}</div>
            <div class="spec-sub">编码：{{ getProductCode(row) }}</div>
            <div class="spec-sub">条码：{{ getBarcode(row) }}</div>
          </div>
        </template>

        <!-- 商品分类：按分类树映射名称 -->
        <template #category="{ row }">{{ getCategoryName(row) }}</template>

        <!-- 良品库存 -->
        <template #good_stock="{ row }">{{ formatStock(row.good_stock) }}{{ getUnit(row) }}</template>

        <!-- 残次品库存 -->
        <template #defect_stock="{ row }">{{ formatStock(row.defect_stock) }}{{ getUnit(row) }}</template>

        <!-- 占用库存：大于 0 时可点击查看占用明细 -->
        <template #occupy_stock="{ row }">
          <el-button v-if="formatStock(row.occupy_stock) > 0" type="primary" link class="stock-link"
            @click="handleViewOccupy(row)">
            {{ formatStock(row.occupy_stock) }}{{ getUnit(row) }}
          </el-button>
          <span v-else>{{ formatStock(row.occupy_stock) }}{{ getUnit(row) }}</span>
        </template>

        <!-- 在途库存：大于 0 时可点击查看在途明细 -->
        <template #transit_stock="{ row }">
          <el-button v-if="formatStock(row.transit_stock) > 0" type="primary" link class="stock-link"
            @click="handleViewTransit(row)">
            {{ formatStock(row.transit_stock) }}{{ getUnit(row) }}
          </el-button>
          <span v-else>{{ formatStock(row.transit_stock) }}{{ getUnit(row) }}</span>
        </template>

        <!-- 操作列 -->
        <template #action="{ row }">
          <el-button type="primary" link @click="handleViewLedger(row)">库存台账</el-button>
        </template>
      </BaseTable>
    </div>

    <!-- 占用 / 在途明细共用一个弹窗，来源单据详情侧滑已内敛在弹窗组件里 -->
    <FlowDialog ref="flowRef" @refresh="fetchData" />
    <LedgerDrawer ref="ledgerRef" />
    <ExportExcel ref="exportExcel" :template="false" :save-name="exportSaveName" :export-data="exportData" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import ExportExcel from "@/components/common/exportExcel.vue";
import FlowDialog from "./components/FlowDialog.vue";
import LedgerDrawer from "./components/LedgerDrawer.vue";
import { useStock } from "./composables/useStock.ts";
import { STOCK_TABS } from "./constants.ts";
import { getGoodsName, getSpecName, getProductCode, getBarcode, getUnit, formatStock } from "./utils.ts";

export default {
  name: "InventoryStock",
  components: { BaseContainer, oaFromBox, BaseTable, FlowDialog, LedgerDrawer, ExportExcel },
  setup() {
    const stockInfo = useStock();

    return {
      ...stockInfo,

      STOCK_TABS,

      getGoodsName,
      getSpecName,
      getProductCode,
      getBarcode,
      getUnit,
      formatStock
    };
  }
};
</script>

<style scoped lang="scss">
.relative {
  position: relative;
}

.stock-card {
  padding: 20px;
  border-radius: 8px;

  &.has-bg {
    background: var(--container-bg);
  }

  &.stock-body {
    background-color: #fff;
    flex: 1;
    display: flex;
    flex-flow: column;
    margin-top: 14px;
  }
}

.stock-search {
  position: relative;
  padding-right: 96px;
}

.stock-export {
  position: absolute;
  top: 20px;
  right: 20px;
}

.stock-tabs {

  :deep(.el-tabs__header) {
    margin-bottom: 14px;
  }

  :deep(.el-tabs__item) {
    height: 30px;
  }
}

// 页签右上角的「存在占用 / 在途库存」筛选，绝对定位与页签同行对齐
.action-tabs {
  position: absolute;
  top: 0;
  right: 0;
  display: flex;
  align-items: center;
}

// 商品信息：缩略图 + 名称
.goods-cell {
  gap: 10px;
  align-items: center;
  padding: 8px 0;

  .goods-img {
    flex-shrink: 0;
    width: 42px !important;
    height: 42px !important;
    border-radius: 4px;
  }

  .goods-name {
    color: #303133;
    line-height: 18px;
  }
}

// 规格：三行信息
.spec-cell {
  line-height: 20px;
  color: #303133;

  .spec-sub {
    color: #606266;
    font-size: 12px;
  }
}

// 占用 / 在途库存可点击数值
.stock-link {
  padding: 0;
}
</style>
