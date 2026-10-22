<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="出库" :rightAreaVisible="false" :total="total"
      @confirmData="handleSearch" btnText="新增出库" @addDataFn="handleAddOutbound" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      :page.sync="page" :page-size.sync="pageSize">
      <!-- 商品信息：缩略图 + 角标数量，超出显示省略号 -->
      <template #goods="{ row }">
        <GoodsInfoCell :details="row.details" />
      </template>

      <!-- 品类数量 -->
      <template #category_count="{ row }">{{ row.details.length }}</template>

      <!-- 商品数量 -->
      <template #goods_count="{ row }">{{ getGoodsCount(row.details) }}</template>

      <!-- 金额总计 -->
      <template #total_amount="{ row }">￥{{ getTotalAmount(row.details) }}</template>

      <!-- 关联付款标签 -->
      <template #payment_status="{ row }">
        <InventoryStatusTag v-if="row.order" :value="row.payment_status" :options="PAYMENT_STATUS_MAP" />
        <span v-else>--</span>
      </template>

      <!-- 出库类型标签 -->
      <template #outbound_type="{ row }">
        <InventoryStatusTag :value="row.outbound_type" :options="OUTBOUND_TYPE_MAP" />
      </template>

      <!-- 出库状态标签 -->
      <template #status="{ row }">
        <InventoryStatusTag :value="row.status" :options="OUTBOUND_STATUS_MAP" />
      </template>

      <!-- 单据备注 -->
      <template #remark="{ row }">
        {{ row.remark || '--' }}
      </template>

      <!-- 操作时间：创建人 + 创建时间两行 -->
      <template #created_at="{ row }">
        <div class="operate-time">
          <div>创建人：{{ row.creator_info?.name }}</div>
          <div>创建时间：{{ formatTime(row.created_at) }}</div>
        </div>
      </template>

      <!-- 操作列 -->
      <template #action="{ row }">
        <el-button type="text" @click="handleViewDetail(row)">查看详情</el-button>
        <template v-if="row.status === OUTBOUND_STATUS.PENDING">
          <el-divider direction="vertical" />
          <el-button type="text" @click="handlePick(row)">拣货出库</el-button>
          <el-divider direction="vertical" />
          <el-button type="text" class="danger-text" @click="handleCancel(row)">作废</el-button>
        </template>
      </template>
    </BaseTable>

    <!-- 详情侧滑内可直接拣货出库，完成后刷新列表 -->
    <OutboundDetail ref="detailRef" @ok="fetchData" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import GoodsInfoCell from "@/components/common/goodsInfoCell.vue";
import InventoryStatusTag from "../components/InventoryStatusTag.vue";
import { useOutbound } from "./composables/useOutbound";
import OutboundDetail from "./components/detail.vue";
import { OUTBOUND_TYPE_MAP, OUTBOUND_STATUS_MAP, OUTBOUND_STATUS } from "./constants";
import { PAYMENT_STATUS_MAP } from "../constants";
import moment from "moment";
import { getTotalAmount } from "./helper";

export default {
  name: "InventoryOutbound",
  components: { BaseContainer, oaFromBox, BaseTable, GoodsInfoCell, InventoryStatusTag, OutboundDetail },
  setup() {
    const outboundInfo = useOutbound();

    const getGoodsCount = (goods) => {
      return goods.reduce((total, item) => total + Number(item.quantity), 0);
    }

    const formatTime = time => {
      return time ? moment(time).format("YYYY-MM-DD HH:mm") : "--";
    }

    return {
      ...outboundInfo,

      OUTBOUND_STATUS,
      OUTBOUND_TYPE_MAP,
      OUTBOUND_STATUS_MAP,
      PAYMENT_STATUS_MAP,

      getGoodsCount,
      getTotalAmount,

      formatTime
    };
  }
};
</script>

<style scoped lang="scss">
// 作废按钮：危险色
.danger-text {
  color: #F56C6C;
}

// 操作时间两行
.operate-time {
  color: #303133;
  line-height: 22px;
}
</style>
