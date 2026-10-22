<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" :total="total" :isAddBtn="false" :rightAreaVisible="false"
      @confirmData="handleSearch" title="库存设置">
      <template #rightBtn>
        <!-- 操作栏：批量设置库存 -->
        <el-button type="primary" size="small" :disabled="!hasSelection" @click="handleBatchSet">设置库存限制</el-button>
      </template>
    </oaFromBox>

    <BaseTable ref="tableRef" class="mt-10" selection row-key="id" :table-columns="tableColumns" :data="tableData"
      :loading="isLoading" :total="total" :page.sync="page" :page-size.sync="pageSize"
      @selection-change="handleSelectionChange">
      <!-- 商品信息：缩略图 + 名称 -->
      <template #goods="{ row }">
        <div class="goods-cell flex">
          <el-image :src="row.sku && row.sku.image" :preview-src-list="row.sku && row.sku.image ? [row.sku.image] : []"
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

      <!-- 剩余库存 -->
      <template #remain_stock="{ row }">{{ formatStock(row.good_stock) }}{{ getUnit(row) }}</template>

      <!-- 库存上限：行内编辑（点击切换 input，失焦自动保存） -->
      <template #high_limit="{ row }">
        <EditableLimitCell :row="row" field="high_limit" :unit="getUnit(row)" />
      </template>

      <!-- 库存下限：行内编辑（点击切换 input，失焦自动保存） -->
      <template #low_limit="{ row }">
        <EditableLimitCell :row="row" field="low_limit" :unit="getUnit(row)" />
      </template>
    </BaseTable>

    <StockLimitDialog ref="dialogRef" @ok="handleSetSuccess" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import StockLimitDialog from "./components/StockLimitDialog.vue";
import EditableLimitCell from "./components/EditableLimitCell.vue";
import { useSetting } from "./composables/useSetting";
import { getGoodsName, getSpecName, getProductCode, getBarcode, getUnit, formatStock } from "../utils";

export default {
  name: "InventorySetting",
  components: { BaseContainer, oaFromBox, BaseTable, StockLimitDialog, EditableLimitCell },
  setup() {
    const settingInfo = useSetting();

    return {
      ...settingInfo,
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
</style>
