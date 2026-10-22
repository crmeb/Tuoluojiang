<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="入库" :rightAreaVisible="false" :total="total"
      @confirmData="handleSearch" btnText="新增入库" @addDataFn="handleAddInbound" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      :page.sync="page" :page-size.sync="pageSize">
      <!-- 商品信息：缩略图 + 角标数量（入库数量 + 赠送数量），超出显示省略号 -->
      <template #goods="{ row }">
        <GoodsInfoCell :details="row.details" include-gift />
      </template>

      <!-- 品类数量 -->
      <template #category_count="{ row }">{{ row.details.length }}</template>

      <!-- 商品数量 -->
      <template #goods_count="{ row }">{{ getGoodsCount(row.details) }}</template>

      <!-- 入库类型标签 -->
      <template #inbound_type="{ row }">
        <InventoryStatusTag :value="row.inbound_type" :options="INBOUND_TYPE_MAP" />
      </template>

      <!-- 入库状态标签 -->
      <template #status="{ row }">
        <InventoryStatusTag :value="row.status" :options="INBOUND_STATUS_MAP" />
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
        <template v-if="row.status === INBOUND_STATUS.PENDING">
          <el-divider direction="vertical" />
          <el-button type="text" @click="handleInbound(row)">入库</el-button>
          <el-divider direction="vertical" />
          <el-button type="text" class="danger-text" @click="handleCancel(row)">作废</el-button>
        </template>
      </template>
    </BaseTable>

    <!-- 详情侧滑内可直接入库，完成后刷新列表 -->
    <InboundDetail ref="detailRef" @ok="fetchData" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import GoodsInfoCell from "@/components/common/goodsInfoCell.vue";
import InventoryStatusTag from "../components/InventoryStatusTag.vue";
import { useInbound } from "./composables/useInbound";
import InboundDetail from "./components/detail.vue";
import { INBOUND_TYPE_MAP, INBOUND_STATUS_MAP, INBOUND_STATUS } from "./constants";
import moment from "moment";

export default {
  name: "InventoryInbound",
  components: { BaseContainer, oaFromBox, BaseTable, GoodsInfoCell, InventoryStatusTag, InboundDetail },
  setup() {
    const inboundInfo = useInbound();

    // 商品数量 = 入库数量 + 赠送数量（数量后端可能以字符串返回，统一转数字）
    const getGoodsCount = (goods) => {
      return goods.reduce(
        (total, item) => total + (Number(item.quantity) || 0) + (Number(item.gift_quantity) || 0),
        0
      );
    }

    const formatTime = time => {
      return time ? moment(time).format("YYYY-MM-DD HH:mm") : "--";
    }

    return {
      ...inboundInfo,

      INBOUND_STATUS,
      INBOUND_TYPE_MAP,
      INBOUND_STATUS_MAP,

      getGoodsCount,

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
