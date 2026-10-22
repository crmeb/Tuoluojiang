<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="正次转换" :rightAreaVisible="false" :total="total"
      @confirmData="handleSearch" btnText="新增正次转换" @addDataFn="handleAddConvert" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      :page.sync="page" :page-size.sync="pageSize">
      <!-- 商品信息：缩略图 + 角标数量，最多展示 3 个 -->
      <template #goods="{ row }">
        <GoodsInfoCell :details="row.details" />
      </template>

      <!-- 品类数量 -->
      <template #category_count="{ row }">{{ (row.details || []).length }}</template>

      <!-- 商品数量 -->
      <template #goods_count="{ row }">{{ getGoodsCount(row.details) }}</template>

      <!-- 金额总计：接口无该字段，按明细 成本价 × 数量 累加 -->
      <template #total_amount="{ row }">¥{{ getTotalAmount(row.details) }}</template>

      <!-- 转换类型标签 -->
      <template #convert_type="{ row }">
        <InventoryStatusTag :value="row.convert_type" :options="CONVERT_TYPE_MAP" />
      </template>

      <!-- 单据备注 -->
      <template #remark="{ row }">
        {{ row.remark || '--' }}
      </template>

      <!-- 操作时间：创建人 + 创建时间两行 -->
      <template #created_at="{ row }">
        <div class="operate-time">
          <div>创建人：{{ row.creator_info?.name || '--' }}</div>
          <div>创建时间：{{ formatTime(row.created_at) }}</div>
        </div>
      </template>

      <!-- 操作列 -->
      <template #action="{ row }">
        <el-button type="text" @click="handleViewDetail(row)">查看详情</el-button>
      </template>
    </BaseTable>

    <ConvertDetail ref="detailRef" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import GoodsInfoCell from "@/components/common/goodsInfoCell.vue";
import InventoryStatusTag from "../components/InventoryStatusTag.vue";
import { useConvert } from "./composables/useConvert";
import ConvertDetail from "./components/detail.vue"; // 转换单详情抽屉
import { CONVERT_TYPE_MAP } from "./constants";
import moment from "moment";

export default {
  name: "InventoryQualityConvert",
  components: { BaseContainer, oaFromBox, BaseTable, GoodsInfoCell, InventoryStatusTag, ConvertDetail },
  setup() {
    const convertInfo = useConvert();

    const getGoodsCount = (goods) => {
      return (goods || []).reduce((total, item) => total + Number(item.quantity), 0);
    };

    // 金额总计 = Σ 成本价 × 数量（成本价取 detail.cost_price，缺失时兜底 sku.cost）
    const getTotalAmount = (goods) => {
      return (goods || [])
        .reduce((total, item) => {
          const costPrice = Number(item.cost_price ?? item.sku?.cost ?? 0) || 0;
          return total + costPrice * (Number(item.quantity) || 0);
        }, 0)
        .toFixed(2);
    };

    const formatTime = (time) => {
      return time ? moment(time).format("YYYY-MM-DD HH:mm") : "--";
    };

    return {
      ...convertInfo,

      CONVERT_TYPE_MAP,

      getGoodsCount,
      getTotalAmount,

      formatTime
    };
  }
};
</script>

<style scoped lang="scss">
// 操作时间两行
.operate-time {
  color: #303133;
  line-height: 22px;
}
</style>
