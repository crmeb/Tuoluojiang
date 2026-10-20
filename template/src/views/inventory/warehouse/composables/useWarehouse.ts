import { modalSure } from '@/libs/public'
import type { NamedRow } from "../../types";
import { ref } from "vue";
import { useTable } from "@/composables/useTable";
import {
  warehouseListApi,
  warehouseDeleteApi,
  warehouseStatusUpdateApi
} from "@/api/inventory";
// 同名的全局仓库缓存 composable（views/inventory/composables），此处按用途起别名区分
import { useWarehouse as useWarehouseCache } from "../../composables/useWarehouse";
import { WAREHOUSE_SCOPE } from "../../constants";

/**
 * 仓库管理列表逻辑：组合通用列表（分页 + loading + 请求） + 增删改交互
 *
 * 列顺序 / 字段依据「库存-仓库管理」原型：仓库编码 / 名称 / 位置 / 备注 / 开启状态 / 排序 / 操作。
 */
export const useWarehouse = () => {

  // 新增 / 编辑弹窗实例（与模板 ref="formRef" 自动绑定）
  const formRef = ref();

  const searchFields = [
    {
      // field_name 同时用于生成 input 占位符：请输入${field_name}
      field_name: "仓库名称",
      field_name_en: "name",
      form_value: "input"
    },
    {
      field_name: "仓库编码",
      field_name_en: "code",
      form_value: "input"
    },
    {
      field_name: "开启状态",
      field_name_en: "status",
      form_value: "select",
      // value 用字符串：规避 formList 中 `items.value || items.id` 对 0 的 falsy 退化，
      // 否则空值会被误匹配为「关闭」；后端用 Number(status) 兼容
      data_dict: [
        { value: "1", name: "开启" },
        { value: "0", name: "关闭" }
      ]
    }
  ];

  const tableColumns = [
    { field: "code", name: "仓库编码", minWidth: 140 },
    { field: "name", name: "仓库名称", minWidth: 160 },
    { field: "address", name: "仓库地址", minWidth: 200, showOverflowTooltip: true },
    { field: "remark", name: "备注", minWidth: 200, showOverflowTooltip: true },
    { field: "status", name: "开启状态", minWidth: 110, slot: "status" },
    { field: "sort", name: "排序", minWidth: 80 },
    { field: "action", name: "操作", minWidth: 120, slot: "action" }
  ];

  const table = useTable(warehouseListApi);

  // 仓库增删改会让两份全局仓库缓存同时过期：全量列表（下拉选项、按 ID 取仓库名）
  // 与可查看仓库列表（仓库名称、启停状态）。immediate: false 避免列表页多余请求。
  const { refreshWarehouses: refreshAllCache } = useWarehouseCache({
    immediate: false,
    scope: WAREHOUSE_SCOPE.ALL
  });
  const { refreshWarehouses: refreshAccessibleCache } = useWarehouseCache({ immediate: false });

  const refreshWarehouseCache = () => {
    refreshAllCache();
    refreshAccessibleCache();
  };

  const handleAdd = () => {
    formRef.value?.openBox();
  };

  const handleEdit = (row: NamedRow) => {
    formRef.value?.openBox(row);
  };

  const handleDelete = (row: NamedRow) => {
    modalSure(`确认删除仓库「${row.name}」吗`).then(async () => {
      await warehouseDeleteApi(row.id);
      refreshWarehouseCache();
      table.fetchData();
    });
  };

  // el-switch 切换：失败时回滚视图状态
  const handleStatusChange = async (row: NamedRow, value: number) => {
    try {
      await warehouseStatusUpdateApi(row.id, value);
      refreshWarehouseCache();
    } catch (error) {
      row.status = value ? 0 : 1;
    }
  };

  // 新增 / 编辑弹窗提交成功
  const handleFormOk = () => {
    refreshWarehouseCache();
    table.fetchData();
  };

  return {
    ...table,

    searchFields,
    tableColumns,
    formRef,

    handleAdd,
    handleEdit,
    handleDelete,
    handleStatusChange,
    handleFormOk
  };
};
