<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="盘点" :rightAreaVisible="false" :total="total"
      @confirmData="handleSearch" btnText="新增盘点" @addDataFn="handleAddStocktaking">
        <!-- 盘点过程提示 -->
        <template #title>
          <div class="stocktaking-tip">
            <i class="el-icon-warning-outline" />
            <span>提示：在盘点过程中，请不要进行出库/入库相关操作！</span>
          </div>
        </template>
    </oaFromBox>

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      v-model:page="page" v-model:page-size="pageSize">
      <!-- 仓库名称 -->
      <template #warehouse="{ row }">
        {{ (row.warehouse && row.warehouse.name) || '--' }}
      </template>

      <!-- 盘点状态标签 -->
      <template #status="{ row }">
        <InventoryStatusTag :value="row.status" :options="CHECK_STATUS_MAP" />
      </template>

      <!-- 良品盘盈数量 -->
      <template #good_profit="{ row }">
        <span class="is-profit">{{ getCheckStat(row).goodProfit }}</span>
      </template>

      <!-- 良品盘亏数量 -->
      <template #good_loss="{ row }">
        <span class="is-loss">{{ getCheckStat(row).goodLoss }}</span>
      </template>

      <!-- 残次品盘盈数量 -->
      <template #defect_profit="{ row }">
        <span class="is-profit">{{ getCheckStat(row).defectProfit }}</span>
      </template>

      <!-- 残次品盘亏数量 -->
      <template #defect_loss="{ row }">
        <span class="is-loss">{{ getCheckStat(row).defectLoss }}</span>
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

      <!-- 操作列：按盘点状态展示不同操作 -->
      <template #action="{ row }">
        <!-- 盘点中：继续盘点 + 作废 -->
        <template v-if="row.status === CHECK_STATUS.CHECKING">
          <el-button type="primary" link @click="handleContinue(row)">继续盘点</el-button>
          <el-divider direction="vertical" />
          <el-button type="primary" link class="danger-text" @click="handleCancel(row)">作废</el-button>
        </template>
        <!-- 其他状态：查看详情（已完成额外提供备注） -->
        <template v-else>
          <el-button type="primary" link @click="handleViewDetail(row)">查看详情</el-button>
          <template v-if="row.status === CHECK_STATUS.COMPLETED">
            <el-divider direction="vertical" />
            <el-button type="primary" link @click="handleRemark(row)">备注</el-button>
          </template>
        </template>
      </template>
    </BaseTable>

    <StocktakingDetail ref="detailRef" />
    <RemarkDialog ref="remarkRef" @ok="refresh" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import InventoryStatusTag from "../components/InventoryStatusTag.vue";
import { useStocktaking } from "./composables/useStocktaking.ts";
import StocktakingDetail from "./components/detail.vue";
import RemarkDialog from "./components/remarkDialog.vue";
import { CHECK_STATUS_MAP, CHECK_STATUS } from "./constants.ts";
import dayjs from "dayjs";

export default {
  name: "InventoryStocktaking",
  components: { BaseContainer, oaFromBox, BaseTable, InventoryStatusTag, StocktakingDetail, RemarkDialog },
  setup() {
    const stocktakingInfo = useStocktaking();

    const formatTime = (time) => {
      return time ? dayjs(time).format("YYYY-MM-DD HH:mm") : "--";
    };

    return {
      ...stocktakingInfo,

      CHECK_STATUS,
      CHECK_STATUS_MAP,

      formatTime
    };
  }
};
</script>

<style scoped lang="scss">
// 盘点过程提示条
.stocktaking-tip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #909399;
  margin-left: 10px;

  .el-icon-warning-outline {
    color: #ff9900;
    font-size: 14px;
  }
}

// 盘盈 / 盘亏数量：盈绿 / 亏红
.is-profit {
  color: #19be6b;
}

.is-loss {
  color: #f56c6c;
}

// 操作时间两行
.operate-time {
  color: #303133;
  line-height: 22px;
}

// 作废操作红色文案
.danger-text {
  color: #f56c6c;
}
</style>
