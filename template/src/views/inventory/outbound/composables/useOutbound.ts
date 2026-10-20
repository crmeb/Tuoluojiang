import { modalSure } from '@/libs/public'
import type { BillRow, SearchValue } from "../../types";
import { useTable } from "@/composables/useTable";
import { outboundListApi, outboundPickApi, outboundCancelApi } from "@/api/inventory";
import { ref, getCurrentInstance } from "vue";
import { useRouter } from "vue-router";
import { roterPre } from "@/settings";
import { OUTBOUND_TYPE_OPTIONS, OUTBOUND_STATUS_OPTIONS } from "../constants";
import { PAYMENT_STATUS, WAREHOUSE_PERMISSION } from "../../constants";

export const useOutbound = () => {
  const proxy = getCurrentInstance()!.proxy!;
  const detailRef = ref();
  const router = useRouter();

  // 仓库为必选查询条件，挂载时不立即请求，等 WareHouseSelect 自动选中首仓后触发首次查询
  const table = useTable(outboundListApi, { immediate: false });

  const searchFields = [
    {
      field_name: "出库单号",
      field_name_en: "bill_no",
      form_value: "input"
    },
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_id",
      form_value: "warehouse_select",
      default_first: true,
      // 出库列表只筛选当前用户有「出库」权限的仓库
      permission: [WAREHOUSE_PERMISSION.OUTBOUND]
    },
    {
      field_name: "出库状态",
      field_name_en: "status",
      form_value: "select",
      data_dict: OUTBOUND_STATUS_OPTIONS.map(item => ({
        value: item.value,
        name: item.label
      }))
    },
    {
      field_name: "出库类型",
      field_name_en: "outbound_type",
      form_value: "select",
      data_dict: OUTBOUND_TYPE_OPTIONS.map(item => ({
        value: item.value,
        name: item.label
      }))
    },
    {
      field_name: "开始时间",
      field_name_en: "created_at",
      form_value: "date_picker",
      field_name_end: "结束时间",
      data_dict: []
    }
  ];

  // 列顺序 / 名称 / 宽度对齐「库存-出库列表」设计稿表头（与入库列表同构）
  const tableColumns = [
    { field: "warehouse.name", name: "出库仓库", minWidth: 120 },
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "category_count", name: "品类数量", minWidth: 80, slot: "category_count" },
    { field: "goods_count", name: "商品数量", minWidth: 80, slot: "goods_count" },
    { field: "total_amount", name: "金额总计", minWidth: 90, slot: "total_amount" },
    { field: "outbound_type", name: "出库类型", minWidth: 100, slot: "outbound_type" },
    { field: "payment_status", name: "关联付款", minWidth: 100, slot: "payment_status" },
    { field: "status", name: "出库状态", minWidth: 100, slot: "status" },
    { field: "remark", name: "单据备注", minWidth: 160, slot: "remark", showOverflowTooltip: true },
    { field: "created_at", name: "操作时间", minWidth: 190, slot: "created_at" },
    { field: "action", name: "操作", minWidth: 200, slot: "action" }
  ];

  const handleAddOutbound = () => {
    router.push({
      path: roterPre + "/inventory/outbound/create"
    });
  };

  // 拣货出库（待出库 -> 已出库）
  const handlePick = (row: BillRow & { order?: unknown; payment_status?: number }) => {
    if (row.order && row.payment_status === PAYMENT_STATUS.UNPAID) {
      proxy.$message.error("订单未结清，不允许出库");
      return
    }
    modalSure(`确认对出库单「${row.bill_no}」执行拣货出库吗`).then(async () => {
      await outboundPickApi(row.id);
      table.fetchData();
    });
  };

  // 作废（待出库 -> 已作废）
  const handleCancel = (row: BillRow) => {
    modalSure(`确认作废出库单「${row.bill_no}」吗`).then(async () => {
      await outboundCancelApi(row.id);
      table.fetchData();
    });
  };

  const handleViewDetail = (row: BillRow) => {
    detailRef.value?.open(row.id);
  };

  // 无仓库 ID 时不发起查询（含重置），避免接口报错；
  // 重置后 WareHouseSelect 会自动重新选中首仓并再触发一次带仓库 ID 的查询
  const handleSearch = (value: SearchValue) => {
    if (value === "reset" || !value.warehouse_id) return;
    table.handleSearch(value);
  };

  return {
    ...table,
    handleSearch,

    searchFields,
    tableColumns,
    detailRef,

    handleViewDetail,
    handlePick,
    handleCancel,
    handleAddOutbound
  };
};
