import { modalSure } from '@/libs/public'
import { useTable } from "@/composables/useTable";
import {
  transferListApi,
  transferOutApi,
  transferInApi,
  transferCancelApi
} from "@/api/inventory";
import { ref } from "vue";
import { useRouter } from "vue-router/composables";
import { roterPre } from "@/settings";
import { TRANSFER_STATUS_OPTIONS } from "../constants";
import { WAREHOUSE_PERMISSION } from "../../constants";

export const useTransfer = () => {
  const detailRef = ref();
  const router = useRouter();

  const table = useTable(transferListApi, { immediate: false });

  // 搜索字段对齐原型：仓库名称（左侧内嵌调出/调入类型切换）+ 调拨单号 + 商品搜索 + 调拨状态 + 调拨日期
  const searchFields = [
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_ids",
      form_value: "warehouse_select",
      default_first: true,
      with_type: true, // 内嵌调出/调入切换，类型值以 warehouse_type（out/in）随查询透传列表接口
      // 调出 / 调入两侧共用一套仓库选项，只筛选当前用户有「调拨」权限的仓库
      permission: [WAREHOUSE_PERMISSION.TRANSFER]
    },
    {
      field_name: "调拨单号",
      field_name_en: "bill_no",
      form_value: "input"
    },
    {
      field_name: "商品搜索",
      field_name_en: "product_keyword",
      form_value: "input"
    },
    {
      field_name: "调拨状态",
      field_name_en: "status",
      form_value: "select",
      data_dict: TRANSFER_STATUS_OPTIONS.map(item => ({
        value: item.value,
        name: item.label
      }))
    },
    {
      field_name: "开始时间",
      field_name_en: "time",
      form_value: "date_picker",
      field_name_end: "结束时间",
      data_dict: []
    }
  ];

  // 列顺序 / 名称 / 宽度对齐「库存-调拨列表」原型（调拨仓库为「调出 → 调入」，无金额总计列）
  const tableColumns = [
    { field: "warehouse", name: "调拨仓库", minWidth: 240, slot: "warehouse" },
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "category_count", name: "品类数量", minWidth: 80, slot: "category_count" },
    { field: "goods_count", name: "商品数量", minWidth: 80, slot: "goods_count" },
    { field: "transfer_type", name: "调拨方式", minWidth: 100, slot: "transfer_type" },
    { field: "status", name: "调拨状态", minWidth: 100, slot: "status" },
    { field: "remark", name: "单据备注", minWidth: 160, slot: "remark" },
    { field: "created_at", name: "操作时间", minWidth: 190, slot: "created_at" },
    { field: "action", name: "操作", minWidth: 160, slot: "action" }
  ];

  const handleAddTransfer = () => {
    router.push({
      path: roterPre + "/inventory/transfer/create"
    });
  };

  // 调拨出库（物流调拨：待出库 -> 待入库）
  const handleOut = (row) => {
    modalSure(`确认对调拨单「${row.bill_no}」执行调拨出库吗`).then(async () => {
      await transferOutApi(row.id);
      table.fetchData();
    });
  };

  // 调拨入库（物流调拨：待入库 -> 已调拨）
  const handleIn = (row) => {
    modalSure(`确认对调拨单「${row.bill_no}」执行调拨入库吗`).then(async () => {
      await transferInApi(row.id);
      table.fetchData();
    });
  };

  // 作废（待出库 -> 已作废）
  const handleCancel = (row) => {
    modalSure(`确认作废调拨单「${row.bill_no}」吗`).then(async () => {
      await transferCancelApi(row.id);
      table.fetchData();
    });
  };

  // 重新提交（已作废 -> 跳转编辑页回填原单据，修改后调重新提交接口）
  const handleResubmit = (row) => {
    router.push({
      path: roterPre + "/inventory/transfer/create",
      query: {
        id: row.id
      }
    });
  };

  const handleViewDetail = (row) => {
    detailRef.value?.open(row.id);
  };

  return {
    ...table,

    searchFields,
    tableColumns,
    detailRef,

    handleViewDetail,
    handleOut,
    handleIn,
    handleCancel,
    handleResubmit,
    handleAddTransfer
  };
};
