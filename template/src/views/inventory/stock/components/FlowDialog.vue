<template>
  <!-- 详情侧滑与 el-dialog 保持 DOM 平级（Element 不建议弹层嵌套），故用 div 包一层 -->
  <div>
    <el-dialog v-model="visible" :width="DIALOG_SIZE.XL" class="stock-detail-dialog"
      :close-on-click-modal="false" @close="reset">
      <!-- 弹窗头部：商品缩略图 + 名称 + 规格 + 编码 -->
      <template #header>
        <GoodsBrief :product="row.product" :sku="row.sku" />
      </template>

      <BaseTable :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total" v-model:page="page"
        v-model:page-size="pageSize">
        <!-- 状态：取流水关联单据（入库 / 出库 / 调拨）的状态，文案与样式复用对应业务列表 -->
        <template #status="{ row: item }">
          <InventoryStatusTag v-if="getSourceStatusOptions(item)" :value="item.source_status"
            :options="getSourceStatusOptions(item)">
          </InventoryStatusTag>
          <span v-else>--</span>
        </template>

        <!-- 占用 / 在途数量：变动数量 + 单位 -->
        <template #quantity="{ row: item }">{{ formatStock(item.change_num) }}{{ unitName }}</template>

        <!-- 调出仓库（仅在途）：流水只返回 target_warehouse_id，按全量仓库映射补名称 -->
        <template #out_warehouse="{ row: item }">{{ getWarehouseName(item.warehouse_id) }}</template>

        <!-- 操作：按流水来源单据类型打开对应详情侧滑 -->
        <template #action="{ row: item }">
          <el-button v-if="hasSourceDetail(item)" type="primary" link @click="handleViewSource(item)">查看详情</el-button>
          <span v-else>--</span>
        </template>
      </BaseTable>
    </el-dialog>

    <!-- 来源单据详情侧滑：入库 / 出库 / 调拨；各详情头部按单据状态自带出入库操作按钮 -->
    <InboundDetail ref="inboundDetailRef" @ok="handleSourceChange" />
    <OutboundDetail ref="outboundDetailRef" @ok="handleSourceChange" />
    <TransferDetail ref="transferDetailRef" @ok="handleSourceChange" />
  </div>
</template>

<script>
import { ref, computed } from "vue";
import { DIALOG_SIZE } from "@/constants/popupSize.ts";
import BaseTable from "@/components/BaseTable/index.vue";
import InventoryStatusTag from "../../components/InventoryStatusTag.vue";
import GoodsBrief from "./GoodsBrief.vue";
import InboundDetail from "../../inbound/components/detail.vue";
import OutboundDetail from "../../outbound/components/detail.vue";
import TransferDetail from "../../transfer/components/detail.vue";
import { stockLedgerApi } from "@/api/inventory.ts";
import { FLOW_TYPE, SOURCE_TYPE, SOURCE_STATUS_MAP_BY_TYPE, STOCK_FLOW_DIALOG_CONFIG } from "../constants.ts";
import { getUnit, formatStock } from "../utils.ts";
import { useTable } from "@/composables/useTable.ts";
import { useWarehouseMap } from "../../composables/useWarehouseMap.ts";

// 可打开详情侧滑的来源单据类型（占用 / 在途流水的来源实际只有出库单与调拨单，
const VIEWABLE_SOURCE_TYPES = [SOURCE_TYPE.INBOUND, SOURCE_TYPE.OUTBOUND, SOURCE_TYPE.TRANSFER];

export default {
  name: "FlowDialog",
  components: { BaseTable, InventoryStatusTag, GoodsBrief, InboundDetail, OutboundDetail, TransferDetail },
  emits: ["refresh"],
  setup(props, { emit, expose }) {
    const visible = ref(false);
    const row = ref({});
    const flowType = ref(FLOW_TYPE.OCCUPY);

    const inboundDetailRef = ref();
    const outboundDetailRef = ref();
    const transferDetailRef = ref();

    // 流水来源单据类型 -> 详情侧滑 ref
    const sourceDetailRefs = {
      [SOURCE_TYPE.INBOUND]: inboundDetailRef,
      [SOURCE_TYPE.OUTBOUND]: outboundDetailRef,
      [SOURCE_TYPE.TRANSFER]: transferDetailRef
    };

    // 占用 / 在途明细共用库存台账接口，仅 flow_type 与列配置不同；打开弹窗时才查询
    const table = useTable(stockLedgerApi, { immediate: false });

    // 「调出仓库」列按仓库 id 补名称，仅在途弹窗用到
    const { loadWarehouseMap, getWarehouseName } = useWarehouseMap();

    const config = computed(() => STOCK_FLOW_DIALOG_CONFIG[flowType.value]);
    const tableColumns = computed(() => config.value.columns);
    const unitName = computed(() => getUnit(row.value));

    const openBox = (stockRow, type) => {
      row.value = stockRow;
      flowType.value = type;
      visible.value = true;
      if (type === FLOW_TYPE.TRANSIT) loadWarehouseMap();
      table.handleSearch({
        warehouse_id: stockRow.warehouse_id,
        sku_id: stockRow.sku_id,
        flow_type: type
      });
    };

    // 关闭即清空，避免下次打开残留上一个 SKU 的流水
    const reset = () => {
      table.tableData.value = [];
      table.total.value = 0;
    };

    const hasSourceDetail = (item) => VIEWABLE_SOURCE_TYPES.includes(item.source_type);

    // 状态列的枚举配置表：按来源单据类型取，未登记的来源类型返回 null，由模板兜底为「--」
    const getSourceStatusOptions = (item) => SOURCE_STATUS_MAP_BY_TYPE[item.source_type] || null;

    const handleViewSource = (item) => {
      sourceDetailRefs[item.source_type]?.value?.open(item.source_id);
    };

    // 详情侧滑里的单据操作（如拣货出库）完成后：重拉弹窗流水并通知列表页刷新库存
    const handleSourceChange = () => {
      table.fetchData();
      emit("refresh");
    };

    expose({ openBox });

    return {
      ...table,

      DIALOG_SIZE,
      visible,
      row,
      tableColumns,
      unitName,
      inboundDetailRef,
      outboundDetailRef,
      transferDetailRef,

      formatStock,
      getWarehouseName,
      reset,
      hasSourceDetail,
      getSourceStatusOptions,
      handleViewSource,
      handleSourceChange
    };
  }
};
</script>

<style lang="scss">
.stock-detail-dialog {
  .el-dialog__body {
    display: flex;
    height: 60vh;
  }
}
</style>
