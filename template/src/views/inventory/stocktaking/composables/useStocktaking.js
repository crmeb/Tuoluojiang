import { modalSure } from '@/libs/public'
import { useTable } from "@/composables/useTable";
import { checkListApi, checkCancelApi } from "@/api/inventory";
import { ref } from "vue";
import { useRouter } from "vue-router/composables";
import { roterPre } from "@/settings";
import { CHECK_STATUS_OPTIONS } from "../constants";
import { WAREHOUSE_PERMISSION } from "../../constants";

const toNumber = (val) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

/**
 * 单行盘盈 / 盘亏统计（良品 / 残次品各拆盈亏四项）。
 *
 * 注：apifox「盘点列表」未定义响应 schema，聚合字段名未知。优先读取后端可能返回的
 * 聚合字段（good_profit_stock 等），缺失时退回用明细「实盘 - 账面」逐行累加兜底，
 * 明细账面字段名以「盘点详情」实际返回（book_good_stock / book_defect_stock）为准。
 */
const getCheckStat = (row) => {
  const direct = {
    goodProfit: row.good_profit_stock,
    goodLoss: row.good_loss_stock,
    defectProfit: row.defect_profit_stock,
    defectLoss: row.defect_loss_stock
  };
  const hasDirect = Object.values(direct).some((v) => v !== undefined && v !== null);
  if (hasDirect) {
    return {
      goodProfit: toNumber(direct.goodProfit),
      goodLoss: toNumber(direct.goodLoss),
      defectProfit: toNumber(direct.defectProfit),
      defectLoss: toNumber(direct.defectLoss)
    };
  }

  const stat = { goodProfit: 0, goodLoss: 0, defectProfit: 0, defectLoss: 0 };
  (row.details || []).forEach((item) => {
    const goodDiff = toNumber(item.real_good_stock) - toNumber(item.book_good_stock);
    const defectDiff = toNumber(item.real_defect_stock) - toNumber(item.book_defect_stock);
    if (goodDiff > 0) stat.goodProfit += goodDiff;
    else if (goodDiff < 0) stat.goodLoss += -goodDiff;
    if (defectDiff > 0) stat.defectProfit += defectDiff;
    else if (defectDiff < 0) stat.defectLoss += -defectDiff;
  });
  return stat;
};

export const useStocktaking = () => {
  const detailRef = ref();
  const remarkRef = ref();
  const router = useRouter();

  // 仓库为必选查询条件，挂载时不立即请求，等 WareHouseSelect 自动选中首仓后触发首次查询
  const table = useTable(checkListApi, { immediate: false });

  // 搜索字段对齐原型：仓库名称 + 盘点单号 + 商品搜索 + 盘点状态 + 盘点日期
  const searchFields = [
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_id",
      form_value: "warehouse_select",
      default_first: true,
      // 盘点列表只筛选当前用户有「盘点」权限的仓库
      permission: [WAREHOUSE_PERMISSION.CHECK]
    },
    {
      field_name: "盘点单号",
      field_name_en: "bill_no",
      form_value: "input"
    },
    {
      field_name: "商品搜索",
      field_name_en: "product_keyword",
      form_value: "input"
    },
    {
      field_name: "盘点状态",
      field_name_en: "status",
      form_value: "select",
      data_dict: CHECK_STATUS_OPTIONS.map((item) => ({
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

  // 列顺序 / 名称 / 宽度对齐「库存-盘点列表」原型（无商品缩略图列，盘盈 / 盘亏为派生统计）
  const tableColumns = [
    { field: "warehouse", name: "仓库名称", minWidth: 140, slot: "warehouse" },
    { field: "bill_no", name: "盘点单号", minWidth: 170 },
    { field: "status", name: "盘点状态", minWidth: 100, slot: "status" },
    { field: "good_profit", name: "良品盘盈数量", minWidth: 110, slot: "good_profit" },
    { field: "good_loss", name: "良品盘亏数量", minWidth: 110, slot: "good_loss" },
    { field: "defect_profit", name: "残次品盘盈数量", minWidth: 120, slot: "defect_profit" },
    { field: "defect_loss", name: "残次品盘亏数量", minWidth: 120, slot: "defect_loss" },
    { field: "remark", name: "单据备注", minWidth: 160, slot: "remark" },
    { field: "created_at", name: "操作时间", minWidth: 190, slot: "created_at" },
    { field: "action", name: "操作", minWidth: 140, slot: "action" }
  ];

  const handleAddStocktaking = () => {
    router.push({
      path: roterPre + "/inventory/stocktaking/create"
    });
  };

  // 继续盘点（盘点中）：跳转新增页并携带 id，进入继续编辑 / 盘点流程
  const handleContinue = (row) => {
    router.push({
      path: roterPre + "/inventory/stocktaking/create",
      query: { id: row.id }
    });
  };

  const handleViewDetail = (row) => {
    detailRef.value?.open(row.id);
  };

  // 备注：打开备注编辑弹窗，保存后刷新列表
  const handleRemark = (row) => {
    remarkRef.value?.open(row);
  };

  // 作废（盘点中 -> 已作废）：已完成盘点已处理库存差异，接口不允许作废
  const handleCancel = (row) => {
    modalSure(`确认作废盘点单「${row.bill_no}」吗`).then(async () => {
      await checkCancelApi(row.id);
      table.fetchData();
    });
  };

  const refresh = () => {
    table.fetchData();
  };

  // 无仓库 ID 时不发起查询（含重置），避免接口报错；
  // 重置后 WareHouseSelect 会自动重新选中首仓并再触发一次带仓库 ID 的查询
  const handleSearch = (value) => {
    if (value === "reset" || !value.warehouse_id) return;
    table.handleSearch(value);
  };

  return {
    ...table,
    handleSearch,

    searchFields,
    tableColumns,
    detailRef,
    remarkRef,

    getCheckStat,

    handleViewDetail,
    handleContinue,
    handleRemark,
    handleCancel,
    handleAddStocktaking,
    refresh
  };
};
