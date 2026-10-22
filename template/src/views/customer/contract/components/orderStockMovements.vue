<!-- 订单-出入库记录页面组件 -->
<template>
  <div class="station">
    <div class="mb10 btn-box1">
      <div class="title-16">出入库记录列表</div>
      <div>
        <el-button
          v-warehouse-permi="{ id: warehouseId, permission: WAREHOUSE_PERMISSION.INBOUND }"
          size="small"
          type="primary"
          @click="handleAddBill('inbound')"
        >
          添加入库单
        </el-button>
        <el-button
          v-warehouse-permi="{ id: warehouseId, permission: WAREHOUSE_PERMISSION.OUTBOUND }"
          size="small"
          type="primary"
          @click="handleAddBill('outbound')"
        >
          添加出库单
        </el-button>
      </div>
    </div>

    <BaseTable
      :table-columns="tableColumns"
      :data="tableData"
      :loading="isLoading"
      :total="total"
      :page.sync="page"
      :page-size.sync="pageSize"
    >
      <!-- 变动类型标签：按记录类型取退货入库 / 销售出库配色，仅描边不带背景色 -->
      <template #type="{ row }">
        <span class="cell-tag cell-tag--plain" :style="getTagStyle(getTypeConf(row))">
          {{ (getTypeConf(row) && getTypeConf(row).label) || row.type_label }}
        </span>
      </template>

      <!-- 商品信息：缩略图 + 角标数量，超出显示「…」占位块 -->
      <template #goods="{ row }">
        <GoodsInfoCell :details="row.products" />
      </template>

      <!-- 金额总计 -->
      <template #total_amount="{ row }">¥{{ row.total_amount }}</template>

      <!-- 出入库状态标签：按记录类型区分文案（待入库/已入库、待出库/已出库） -->
      <template #status="{ row }">
        <span class="cell-tag" :style="getTagStyle(getStatusConf(row))">
          {{ (getStatusConf(row) && getStatusConf(row).label) || row.status_label }}
        </span>
      </template>

      <!-- 操作列 -->
      <template #action="{ row }">
        <el-button type="text" @click="handleViewDetail(row)">查看详情</el-button>
      </template>
    </BaseTable>

    <!-- 复用库存模块入库 / 出库详情侧滑弹窗，详情内出入库操作完成后刷新记录列表 -->
    <InboundDetail ref="inboundDetailRef" @ok="fetchData" />
    <OutboundDetail ref="outboundDetailRef" @ok="fetchData" />
  </div>
</template>

<script>
import { ref } from "vue";
import { useRouter } from "vue-router/composables";
import BaseTable from "@/components/BaseTable/index.vue";
import GoodsInfoCell from "@/components/common/goodsInfoCell.vue";
import InboundDetail from "@/views/inventory/inbound/components/detail.vue";
import OutboundDetail from "@/views/inventory/outbound/components/detail.vue";
import { useTable } from "@/composables/useTable";
import { orderStockBillListApi } from "@/api/inventory";
import { roterPre } from "@/settings";
import { WAREHOUSE_PERMISSION } from "@/views/inventory/constants";
import { INBOUND_TYPE, INBOUND_TYPE_MAP, INBOUND_STATUS } from "@/views/inventory/inbound/constants";
import { OUTBOUND_TYPE, OUTBOUND_TYPE_MAP, OUTBOUND_STATUS } from "@/views/inventory/outbound/constants";

// 记录类型枚举值（接口 record_type）：区分一条记录是退货入库单还是销售出库单
const RECORD_TYPE = {
  INBOUND: "inbound",
  OUTBOUND: "outbound"
};

// 变动类型标签配置：接口只返回退货入库 / 销售出库两类记录，配色复用入库、出库各自类型枚举
const RECORD_TYPE_CONF = {
  [RECORD_TYPE.INBOUND]: INBOUND_TYPE_MAP[INBOUND_TYPE.RETURN],
  [RECORD_TYPE.OUTBOUND]: OUTBOUND_TYPE_MAP[OUTBOUND_TYPE.SALE]
};

// 出入库状态标签配置：按单据类型区分文案（依据设计稿：待入库/已入库、待出库/已出库，实底无边框标签）
const MOVEMENT_STATUS_STYLE = {
  pending: { color: "#FF9900", bgColor: "rgba(255,153,0,0.1)" },
  completed: { color: "#19BE6B", bgColor: "rgba(25,190,107,0.1)" },
  muted: { color: "#909399", bgColor: "rgba(96,98,102,0.06)" }
};

const MOVEMENT_STATUS_MAP = {
  [RECORD_TYPE.INBOUND]: {
    [INBOUND_STATUS.PENDING]: { label: "待入库", ...MOVEMENT_STATUS_STYLE.pending },
    [INBOUND_STATUS.COMPLETED]: { label: "已入库", ...MOVEMENT_STATUS_STYLE.completed },
    [INBOUND_STATUS.VOIDED]: { label: "已作废", ...MOVEMENT_STATUS_STYLE.muted }
  },
  [RECORD_TYPE.OUTBOUND]: {
    [OUTBOUND_STATUS.PENDING]: { label: "待出库", ...MOVEMENT_STATUS_STYLE.pending },
    [OUTBOUND_STATUS.COMPLETED]: { label: "已出库", ...MOVEMENT_STATUS_STYLE.completed },
    [OUTBOUND_STATUS.VOIDED]: { label: "已作废", ...MOVEMENT_STATUS_STYLE.muted }
  }
};

export default {
  name: "OrderStockMovements",
  components: { BaseTable, GoodsInfoCell, InboundDetail, OutboundDetail },
  props: {
    // 订单详情抽屉透传的表单信息，data 为订单列表行数据（id / contract_no 等）
    formInfo: {
      type: Object,
      default: () => ({})
    },
    // 订单出库仓库 ID，用于按仓库权限显隐添加入库单 / 出库单按钮
    warehouseId: {
      type: [Number, String],
      default: null
    }
  },
  setup(props) {
    const router = useRouter();
    const inboundDetailRef = ref();
    const outboundDetailRef = ref();

    // 挂载时不立即请求，由父组件在切到本 Tab 时调用 getTableData
    const table = useTable(orderStockBillListApi, {
      immediate: false,
      extraParams: () => ({ source_id: props.formInfo.data && props.formInfo.data.id })
    });

    // 切换订单后重置到第 1 页按当前订单重查
    const getTableData = () => table.handleSearch();

    // 列顺序 / 名称依据「订单-出入库记录」设计稿表头
    const tableColumns = [
      { field: "warehouse_name", name: "仓库", minWidth: 120 },
      { field: "bill_no", name: "单据编号", minWidth: 150 },
      { field: "type", name: "变动类型", minWidth: 100, slot: "type" },
      { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
      { field: "total_amount", name: "金额总计", minWidth: 100, slot: "total_amount" },
      { field: "status", name: "出入库状态", minWidth: 100, slot: "status" },
      { field: "action", name: "操作", minWidth: 90, slot: "action" }
    ];

    // 变动类型 / 出入库状态按记录类型分别取退货入库、销售出库各自的配置
    const getTypeConf = (row) => RECORD_TYPE_CONF[row.record_type];

    const getStatusConf = (row) =>
      (MOVEMENT_STATUS_MAP[row.record_type] || MOVEMENT_STATUS_MAP[RECORD_TYPE.INBOUND])[row.status];

    const getTagStyle = (styleObj) => {
      if (!styleObj) return {};
      const { color, bgColor, borderColor } = styleObj;
      return {
        "--color": color,
        "--bg-color": bgColor,
        "--border-color": borderColor
      };
    };

    // 添加入库单 / 出库单：跳转新建单据页，携带订单仓库与订单信息用于回显仓库、订单及关联商品（对齐订单列表下拉入口）
    const handleAddBill = (type) => {
      const row = props.formInfo.data || {};
      const routeData = router.resolve({
        path: `${roterPre}/inventory/${type}/create`,
        query: {
          warehouse_id: props.warehouseId,
          order_id: row.id,
          contract_no: row.contract_no
        }
      });
      window.open(routeData.href, '_blank');
    };

    // 查看详情：按记录类型打开库存模块对应的详情侧滑弹窗（bill_id 为出入库单据 ID）
    const handleViewDetail = (row) => {
      if (row.record_type === RECORD_TYPE.OUTBOUND) {
        outboundDetailRef.value && outboundDetailRef.value.open(row.bill_id);
      } else {
        inboundDetailRef.value && inboundDetailRef.value.open(row.bill_id);
      }
    };

    return {
      ...table,

      WAREHOUSE_PERMISSION,
      tableColumns,
      inboundDetailRef,
      outboundDetailRef,

      getTableData,
      getTypeConf,
      getStatusConf,
      getTagStyle,
      handleAddBill,
      handleViewDetail
    };
  }
};
</script>

<style scoped lang="scss">
.station {
  height: 100%;
  display: flex;
  flex-flow: column nowrap;
}

.btn-box1 {
  height: 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

// 类型 / 状态标签
.cell-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding-inline: 10px;
  height: 24px;

  border-radius: 4px;
  font-size: 12px;
  line-height: 1;
  color: var(--color);
  background-color: var(--bg-color);
  border: 1px solid var(--border-color, transparent);

  // 仅描边样式：变动类型列不带背景色
  &--plain {
    background-color: transparent;
  }
}
</style>
