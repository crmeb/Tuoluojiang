<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="调拨" :rightAreaVisible="false" :total="total"
      @confirmData="handleSearch" btnText="新增调拨" @addDataFn="handleAddTransfer" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      :page.sync="page" :page-size.sync="pageSize">
      <!-- 调拨仓库：调出仓库 → 调入仓库 -->
      <template #warehouse="{ row }">
        <div class="warehouse-cell">
          <span>{{ (row.out_warehouse && row.out_warehouse.name) || '--' }}</span>
          <span class="warehouse-cell__arrow">→</span>
          <span>{{ (row.in_warehouse && row.in_warehouse.name) || '--' }}</span>
        </div>
      </template>

      <!-- 商品信息：缩略图 + 角标数量，超出显示省略号 -->
      <template #goods="{ row }">
        <GoodsInfoCell :details="row.details" />
      </template>

      <!-- 品类数量 -->
      <template #category_count="{ row }">{{ (row.details || []).length }}</template>

      <!-- 商品数量 -->
      <template #goods_count="{ row }">{{ getGoodsCount(row.details) }}</template>

      <!-- 调拨方式标签 -->
      <template #transfer_type="{ row }">
        <InventoryStatusTag :value="row.transfer_type" :options="TRANSFER_TYPE_MAP" />
      </template>

      <!-- 调拨状态标签 -->
      <template #status="{ row }">
        <InventoryStatusTag :value="row.status" :options="TRANSFER_STATUS_MAP" />
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

      <!-- 操作列：按调拨状态展示不同操作 -->
      <template #action="{ row }">
        <el-button type="text" @click="handleViewDetail(row)">查看详情</el-button>
        <!-- 待出库：出库 + 作废 -->
        <template v-if="row.status === TRANSFER_STATUS.PENDING_OUT">
          <el-divider direction="vertical" />
          <el-button type="text" @click="handleOut(row)">出库</el-button>
          <el-divider direction="vertical" />
          <el-button type="text" class="danger-text" @click="handleCancel(row)">作废</el-button>
        </template>
        <!-- 待入库：入库 -->
        <template v-else-if="row.status === TRANSFER_STATUS.PENDING_IN">
          <el-divider direction="vertical" />
          <el-button type="text" @click="handleIn(row)">入库</el-button>
        </template>
        <!-- 已作废：重新提交 -->
        <template v-else-if="row.status === TRANSFER_STATUS.VOIDED">
          <el-divider direction="vertical" />
          <el-button type="text" @click="handleResubmit(row)">重新提交</el-button>
        </template>
      </template>
    </BaseTable>

    <!-- 详情侧滑内可直接出库/入库，完成后刷新列表 -->
    <TransferDetail ref="detailRef" @ok="fetchData" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import GoodsInfoCell from "@/components/common/goodsInfoCell.vue";
import InventoryStatusTag from "../components/InventoryStatusTag.vue";
import { useTransfer } from "./composables/useTransfer";
import TransferDetail from "./components/detail.vue";
import { TRANSFER_TYPE_MAP, TRANSFER_STATUS_MAP, TRANSFER_STATUS } from "./constants";
import moment from "moment";

export default {
  name: "InventoryTransfer",
  components: { BaseContainer, oaFromBox, BaseTable, GoodsInfoCell, InventoryStatusTag, TransferDetail },
  setup() {
    const transferInfo = useTransfer();

    const getGoodsCount = (goods) => {
      return (goods || []).reduce((total, item) => total + Number(item.quantity), 0);
    };

    const formatTime = time => {
      return time ? moment(time).format("YYYY-MM-DD HH:mm") : "--";
    };

    return {
      ...transferInfo,

      TRANSFER_STATUS,
      TRANSFER_TYPE_MAP,
      TRANSFER_STATUS_MAP,

      getGoodsCount,

      formatTime
    };
  }
};
</script>

<style scoped lang="scss">
// 调拨仓库：调出 → 调入
.warehouse-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #303133;

  &__arrow {
    color: #909399;
  }
}

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
