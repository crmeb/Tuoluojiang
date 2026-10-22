import { modalSure } from '@/libs/public'
import { useRouter } from "vue-router/composables";
import { roterPre } from "@/settings";
import { useTable } from "@/composables/useTable";
import {
  keeperListApi,
  keeperDeleteApi,
  keeperStatusUpdateApi
} from "@/api/inventory";
import { useWarehouse } from "../../composables/useWarehouse";

/**
 * 库管管理列表逻辑：组合通用列表（分页 + loading + 请求） + 增删改交互
 */
export const useKeeper = () => {
  const router = useRouter();

  // 删除 / 停用库管都会改变「谁能看哪个仓库」，需同步刷新可查看仓库缓存
  const { refreshWarehouses } = useWarehouse({ immediate: false });

  const searchFields = [
    {
      field_name: "库管角色/员工姓名",
      field_name_en: "name",
      form_value: "input"
    },
    {
      field_name: "状态",
      field_name_en: "status",
      form_value: "select",
      data_dict: [
        { value: "1", name: "启用" },
        { value: "0", name: "停用" }
      ]
    }
  ];

  const tableColumns = [
    { field: "name", name: "库管角色", minWidth: 100 },
    { field: "users", name: "库管人员", minWidth: 160, slot: "users" },
    { field: "manager_type", name: "权限类型", minWidth: 100, slot: "manager_type" },
    { field: "warehouses", name: "管理仓库", minWidth: 200, slot: "warehouses", showOverflowTooltip: true },
    { field: "can_view_other", name: "查看其他仓库库存权限", minWidth: 160, slot: "can_view_other" },
    { field: "remark", name: "备注", minWidth: 160 },
    { field: "status", name: "状态", minWidth: 90, slot: "status" },
    { field: "sort", name: "排序", minWidth: 60, },
    { field: "action", name: "操作", minWidth: 100, slot: "action" }
  ];

  const table = useTable(keeperListApi);

  const handleAdd = () => {
    router.push({
      path: roterPre + "/inventory/keeper/create"
    });
  };

  const handleDelete = (row) => {
    modalSure(`确认删除库管「${row.name}」吗`).then(async () => {
      await keeperDeleteApi(row.id);
      refreshWarehouses();
      table.fetchData();
    });
  };

  // el-switch 切换：失败时回滚视图状态
  const handleStatusChange = async (row, value) => {
    try {
      await keeperStatusUpdateApi(row.id, value);
      refreshWarehouses();
    } catch (error) {
      row.status = String(value ? 0 : 1);
    }
  };

  const handleToEditPage = row => {
    router.push({
      path: roterPre + "/inventory/keeper/create",
      query: {
        id: row.id
      }
    });
  }

  return {
    ...table,

    searchFields,
    tableColumns,

    handleAdd,
    handleDelete,
    handleStatusChange,
    handleToEditPage
  };
};
