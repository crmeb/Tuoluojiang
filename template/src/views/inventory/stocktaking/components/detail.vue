<template>
  <el-drawer :visible.sync="isOpen" :size="DRAWER_SIZE.LG" title="盘点单详情" custom-class="stocktaking-detail-drawer">
    <div v-loading="isLoading" class="stocktaking-detail">
      <!-- 基本信息：标题 + 字段网格 -->
      <InfoSelection title="基本信息" :fields="basicFields" />

      <!-- 盘点商品信息：标题 + 汇总文案 + 商品表格 -->
      <InfoSelection title="盘点商品信息" fill>
        <p v-if="detail" class="stocktaking-detail__summary">
          <span class="summary-item">盘亏金额 <span class="is-loss">{{ formatAmount(summary.lossAmount) }}</span></span>
          <span class="summary-item">盘盈金额 <span class="is-profit">{{ formatAmount(summary.profitAmount) }}</span></span>
          <span class="summary-item">总金额 <span :class="diffClass(summary.totalAmount)">{{ formatAmount(summary.totalAmount) }}</span></span>
        </p>

        <BaseTable :table-columns="goodsColumns" :data="goodsList" :show-pagination="false">
          <!-- 商品信息：缩略图 + 名称 -->
          <template #goods="{ row }">
            <div class="goods-cell flex">
              <el-image :src="row.image" :preview-src-list="[row.image]" class="goods-img" />
              <span class="goods-name">{{ row.name }}</span>
            </div>
          </template>

          <!-- 规格名称：规格 + 编码 + 条码 -->
          <template #spec="{ row }">
            <div class="goods-spec">
              <div>{{ getSpecName(row) }}</div>
              <div v-if="getProductCode(row) !== '--'" class="goods-spec__sub">编码：{{ getProductCode(row) }}</div>
              <div v-if="getBarcode(row) !== '--'" class="goods-spec__sub">条码：{{ getBarcode(row) }}</div>
            </div>
          </template>

          <!-- 良品盈亏：盈绿 / 亏红 -->
          <template #good_diff="{ row }">
            <span :class="diffClass(row.good_diff)">{{ formatDiff(row.good_diff) }}</span>
          </template>

          <!-- 残次品盈亏：盈绿 / 亏红 -->
          <template #defect_diff="{ row }">
            <span :class="diffClass(row.defect_diff)">{{ formatDiff(row.defect_diff) }}</span>
          </template>
        </BaseTable>
      </InfoSelection>
    </div>
  </el-drawer>
</template>

<script>
import InfoSelection from "../../components/InfoSelection.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import { DRAWER_SIZE } from "@/constants/popupSize";
import { useStocktakingDetail } from "../composables/useStocktakingDetail";
import { getSpecName, getProductCode, getBarcode } from "../../utils";

export default {
  name: "StocktakingDetail",
  components: { InfoSelection, BaseTable },
  setup(props, ctx) {
    const detailInfo = useStocktakingDetail();

    // 盈亏展示：正数加「+」号，0 与负数原样展示
    const formatDiff = (diff) => (diff > 0 ? `+${diff}` : `${diff}`);
    // 盈亏样式：盈（正）绿色，亏（负）红色，持平默认色
    const diffClass = (diff) => (diff > 0 ? "is-profit" : diff < 0 ? "is-loss" : "");
    // 金额展示：¥ 前缀，两位小数修正浮点误差后去掉多余尾零（如 ¥-24、¥12.5）
    const formatAmount = (amount) => `¥${parseFloat(amount.toFixed(2))}`;

    ctx.expose({
      open: detailInfo.open
    });

    return { ...detailInfo, DRAWER_SIZE, formatDiff, diffClass, formatAmount, getSpecName, getProductCode, getBarcode };
  }
};
</script>

<style scoped lang="scss">
.stocktaking-detail {
  // 撑满抽屉高度并转纵向 flex，剩余高度才能经 fill 区块传给内部表格
  height: 100%;
  display: flex;
  flex-flow: column nowrap;
  padding: 20px;

  &__summary {
    display: flex;
    gap: 24px;
    margin: 0 0 16px;
    font-size: 13px;
    color: #303133;
  }
}

.goods-cell {
  align-items: center;
  gap: 12px;

  .goods-img {
    flex-shrink: 0;
    // 全局 styles.scss 对 table 内 el-image 强制 36px !important，此处需同级反制
    width: 42px !important;
    height: 42px !important;
    border-radius: 4px;
  }

  .goods-name {
    line-height: 20px;
  }
}

.goods-spec {
  line-height: 18px;
  color: #303133;

  &__sub {
    margin-top: 2px;
    color: #606266;
  }
}

// 盈亏数量：盈绿 / 亏红
.is-profit {
  color: #19be6b;
}

.is-loss {
  color: #f56c6c;
}
</style>

<style lang="scss">
.stocktaking-detail-drawer {
  .el-drawer__body {
    padding: 0;
    overflow-y: auto;
  }
}
</style>
