import { modalSure } from '@/libs/public'
import type { BillRow, SearchValue } from "../../types";
import { useTable } from "@/composables/useTable";
import { inboundDoApi, inboundListApi, inboundCancelApi } from "@/api/inventory";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { roterPre } from "@/settings";
import { INBOUND_TYPE_OPTIONS, INBOUND_STATUS_OPTIONS } from "../constants";
import { WAREHOUSE_PERMISSION } from "../../constants";

export const useInbound = () => {
  const detailRef = ref();
  const router = useRouter();
  
  // 仓库为必选查询条件，挂载时不立即请求，等 WareHouseSelect 自动选中首仓后触发首次查询
  const table = useTable(inboundListApi, { immediate: false });

  const searchFields = [
    {
      field_name: "入库单号",
      field_name_en: "bill_no",
      form_value: "input"
    },
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_id",
      form_value: "warehouse_select",
      default_first: true,
      // 入库列表只筛选当前用户有「入库」权限的仓库
      permission: [WAREHOUSE_PERMISSION.INBOUND]
    },
    {
      // 商品关键词：后端按商品名称 / 商品编号 / SKU 编码模糊匹配（对齐调拨列表）
      field_name: "商品名称搜索",
      field_name_en: "product_keyword",
      form_value: "input"
    },
    {
      field_name: "入库状态",
      field_name_en: "status",
      form_value: "select",
      data_dict: INBOUND_STATUS_OPTIONS.map(item => ({
        value: item.value,
        name: item.label
      }))
    },
    {
      field_name: "入库类型",
      field_name_en: "inbound_type",
      form_value: "select",
      data_dict: INBOUND_TYPE_OPTIONS.map(item => ({
        value: item.value,
        name: item.label
      }))
    },
    {
      field_name: '开始时间',
      field_name_en: 'created_at',
      form_value: 'date_picker',
      field_name_end: '结束时间',
      data_dict: []
    }
  ];

  // 列顺序 / 名称 / 宽度依据 MasterGo「库存-入库列表」设计稿表头
  const tableColumns = [
    { field: "warehouse.name", name: "入库仓库", minWidth: 120 },
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "category_count", name: "品类数量", minWidth: 80, slot: "category_count" },
    { field: "goods_count", name: "商品数量", minWidth: 80, slot: "goods_count" },
    { field: "total_amount", name: "金额总计", minWidth: 90 },
    { field: "inbound_type", name: "入库类型", minWidth: 100, slot: "inbound_type" },
    { field: "status", name: "入库状态", minWidth: 100, slot: "status" },
    { field: "remark", name: "单据备注", minWidth: 160, slot: "remark", showOverflowTooltip: true },
    { field: "created_at", name: "操作时间", minWidth: 190, slot: "created_at" },
    { field: "action", name: "操作", minWidth: 180, slot: "action" }
  ];

  const handleAddInbound = () => {
    router.push({
      path: roterPre + "/inventory/inbound/create"
    });
  }

  // 入库（待入库 -> 已完成）
  const handleInbound = (row: BillRow) => {
    modalSure(`确认对入库单「${row.bill_no}」执行入库吗`).then(async () => {
      await inboundDoApi(row.id);
      table.fetchData();
    });
  };

  // 作废（待入库 -> 已作废）
  const handleCancel = (row: BillRow) => {
    modalSure(`确认作废入库单「${row.bill_no}」吗`).then(async () => {
      await inboundCancelApi(row.id);
      table.fetchData();
    });
  };

  const handleViewDetail = (row: BillRow) => {
    detailRef.value?.open(row.id);
  }

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
    handleInbound,
    handleCancel,
    handleAddInbound
  };
};
