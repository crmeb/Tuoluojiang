import { useTable } from "@/composables/useTable";
import { convertListApi } from "@/api/inventory";
import { ref } from "vue";
import { useRouter } from "vue-router/composables";
import { roterPre } from "@/settings";
import { CONVERT_TYPE_OPTIONS } from "../constants";
import { WAREHOUSE_PERMISSION } from "../../constants";

export const useConvert = () => {
  const detailRef = ref();
  const router = useRouter();

  // 仓库为必选查询条件，挂载时不立即请求，等 WareHouseSelect 自动选中首仓后触发首次查询
  const table = useTable(convertListApi, { immediate: false });

  // 搜索字段对齐原型：仓库名称 + 正次转换单号 + 商品搜索 + 转换类型 + 转换日期
  const searchFields = [
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_id",
      form_value: "warehouse_select",
      default_first: true,
      // 正次转换列表只筛选当前用户有「正次转换」权限的仓库
      permission: [WAREHOUSE_PERMISSION.CONVERT]
    },
    {
      field_name: "正次转换单号",
      field_name_en: "bill_no",
      form_value: "input"
    },
    {
      field_name: "商品搜索",
      field_name_en: "product_keyword",
      form_value: "input"
    },
    {
      field_name: "转换类型",
      field_name_en: "convert_type",
      form_value: "select",
      data_dict: CONVERT_TYPE_OPTIONS.map((item) => ({
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

  // 列顺序 / 名称 / 宽度对齐「库存-正次转换列表」原型（无状态列，操作仅查看详情）
  const tableColumns = [
    { field: "warehouse.name", name: "正次转换仓库", minWidth: 160 },
    { field: "goods", name: "商品信息", minWidth: 200, slot: "goods" },
    { field: "category_count", name: "品类数量", minWidth: 80, slot: "category_count" },
    { field: "goods_count", name: "商品数量", minWidth: 80, slot: "goods_count" },
    { field: "convert_type", name: "转换类型", minWidth: 120, slot: "convert_type" },
    { field: "total_amount", name: "金额总计", minWidth: 90, slot: "total_amount" },
    { field: "remark", name: "单据备注", minWidth: 160, slot: "remark" },
    { field: "created_at", name: "操作时间", minWidth: 190, slot: "created_at" },
    { field: "action", name: "操作", minWidth: 100, slot: "action" }
  ];

  const handleAddConvert = () => {
    router.push({
      path: roterPre + "/inventory/qualityConvert/create"
    });
  };

  const handleViewDetail = (row) => {
    detailRef.value?.open(row.id);
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

    handleViewDetail,
    handleAddConvert
  };
};
