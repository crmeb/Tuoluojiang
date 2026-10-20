import { nextTick, ref } from "vue";
import type { SearchValue, StockRow } from "../../types";
import { ElMessage as Message } from 'element-plus'
import { useTable } from "@/composables/useTable";
import { warehouseStockListApi, warehouseStockStatisticsApi } from "@/api/inventory";
import { productCateListApi } from "@/api/client";
import { STOCK_TAB_ALL, FLOW_TYPE } from "../constants";
import { WAREHOUSE_PERMISSION } from "../../constants";
import { getGoodsName, getProductCode, getBarcode, getSpecName, getUnit, formatStock } from "../utils";

export const useStock = () => {
  const flowRef = ref();
  const ledgerRef = ref();
  const exportLoading = ref(false);
  const exportSaveName = ref("");
  const exportData = ref({
    data: [],
    cols: [
      { wpx: 140 },
      { wpx: 180 },
      { wpx: 130 },
      { wpx: 130 },
      { wpx: 120 },
      { wpx: 120 },
      { wpx: 100 },
      { wpx: 110 },
      { wpx: 100 },
      { wpx: 100 }
    ],
    rows: []
  } as { data?: unknown[][]; [key: string]: unknown });

  // 状态页签 + 页签右侧「存在占用/在途库存」勾选，作为搜索表单以外的查询条件，
  // 经 extraParams 在每次请求时合并（需先于 useTable 声明，immediate 拉取时即会读取）
  const activeTab = ref(STOCK_TAB_ALL);
  const hasOccupy = ref(false);
  const hasTransit = ref(false);

  const buildExtraParams = () => {
    const extra: Record<string, number> = {};
    if (activeTab.value !== STOCK_TAB_ALL) extra.stock_status = Number(activeTab.value);
    if (hasOccupy.value) extra.has_occupy = 1;
    if (hasTransit.value) extra.has_transit = 1;
    return extra;
  };

  const table = useTable(warehouseStockListApi, {
    immediate: false,
    extraParams: buildExtraParams
  });

  // 各页签数量统计：单独接口，data 为 { all, low, high, sold_out }，按 STOCK_TABS.countKey 取值
  const tabCounts = ref<Record<string, number>>({});

  // 统计与列表共享筛选条件（仓库 / 关键词 / 分类 + 占用 / 在途勾选），但不含状态页签与分页——
  // 一次调用即返回全部状态的数量，故不带 stock_status
  const buildStatisticsParams = () => {
    const params: Record<string, unknown> = { ...table.searchForm.value };
    if (hasOccupy.value) params.has_occupy = 1;
    if (hasTransit.value) params.has_transit = 1;
    return params;
  };

  const fetchStatistics = async () => {
    try {
      const res = await warehouseStockStatisticsApi(buildStatisticsParams());
      tabCounts.value = res.data || {};
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    }
  };

  // 搜索字段对齐原型：商品搜索 + 商品分类 + 仓库名称
  const searchFields = ref([
    {
      field_name: "仓库名称",
      field_name_en: "warehouse_id",
      form_value: "warehouse_select",
      default_first: true,
      permission: [ WAREHOUSE_PERMISSION.STOCK ]
    },
    {
      field_name: "商品编码/条码/名称",
      field_name_en: "keyword",
      form_value: "input",
      width: "215px"
    },
    {
      field_name: "商品分类",
      field_name_en: "category_id",
      form_value: "cascaderSelect",
      placeholder: "商品分类",
      // 任意一级可选（单选），直接提交选中分类 id
      props: { checkStrictly: true, emitPath: false, label: "name", value: "id" },
      data_dict: []
    }
  ]);

  // 拉取商品分类树填充级联选项
  const loadCategoryOptions = async () => {
    try {
      const res = await productCateListApi();
      const categoryField = searchFields.value.find((item) => item.field_name_en === "category_id");
      if (categoryField) categoryField.data_dict = res.data;
    } catch (error) {
      // 错误提示由 request 统一处理，这里静默
    }
  };
  loadCategoryOptions();

  // 列顺序 / 名称对齐原型「库存查询」表头
  const tableColumns = [
    { field: "goods", name: "商品信息", minWidth: 220, slot: "goods" },
    { field: "spec", name: "规格", minWidth: 170, slot: "spec" },
    { field: "category", name: "商品分类", minWidth: 130, slot: "category" },
    { field: "warehouse.name", name: "仓库名称", minWidth: 150 },
    { field: "good_stock", name: "良品库存", minWidth: 90, slot: "good_stock" },
    { field: "defect_stock", name: "残次品库存", minWidth: 100, slot: "defect_stock" },
    { field: "occupy_stock", name: "占用库存", minWidth: 90, slot: "occupy_stock" },
    { field: "transit_stock", name: "在途库存", minWidth: 90, slot: "transit_stock" },
    { field: "action", name: "操作", minWidth: 90, slot: "action" }
  ];

  // 搜索表单变化：列表与统计一并重查（统计随筛选条件更新，但与当前页签无关）
  const handleSearch = (value: SearchValue) => {
    table.handleSearch(value);
    fetchStatistics();
  };

  // 页签切换只影响列表数据，各页签数量不随之变化，无需重新统计
  const handleTabChange = () => {
    table.handleSearch();
  };

  // 占用 / 在途勾选属于筛选条件，列表与统计都要重查
  const handleFilterChange = () => {
    table.handleSearch();
    fetchStatistics();
  };

  // 商品分类
  const getCategoryName = (row: StockRow) => {
    return row.product?.category?.name || "--";
  };

  const handleViewOccupy = (row: StockRow) => {
    flowRef.value?.openBox(row, FLOW_TYPE.OCCUPY);
  };

  const handleViewTransit = (row: StockRow) => {
    flowRef.value?.openBox(row, FLOW_TYPE.TRANSIT);
  };

  const handleViewLedger = (row: StockRow) => {
    ledgerRef.value?.openBox(row);
  };

  const getStockText = (row: StockRow, key: "good_stock" | "defect_stock" | "occupy_stock" | "transit_stock") => {
    return `${formatStock(row[key])}${getUnit(row)}`;
  };

  const formatExportTime = (date = new Date()) => {
    const pad = (value: number, length = 2) => String(value).padStart(length, "0");
    return `${date.getFullYear()}${pad(date.getMonth() + 1)}${pad(date.getDate())}_${pad(date.getHours())}${pad(date.getMinutes())}${pad(date.getSeconds())}${pad(date.getMilliseconds(), 3)}`;
  };

  const buildListParams = (page: number, limit: number) => ({
    ...table.searchForm.value,
    ...buildExtraParams(),
    page,
    limit
  });

  const handleExport = async (exportExcel?: { exportExcel?: () => void } | null) => {
    if (exportLoading.value) return;
    if (!exportExcel?.exportExcel) {
      Message.error("导出组件未加载");
      return;
    }
    exportLoading.value = true;
    try {
      const limit = 100;
      const firstRes = await warehouseStockListApi(buildListParams(1, limit));
      const firstData = firstRes.data || {};
      const list = firstData.list || [];
      const count = Number(firstData.count) || list.length;
      const pageCount = Math.ceil(count / limit);

      for (let page = 2; page <= pageCount; page += 1) {
        const res = await warehouseStockListApi(buildListParams(page, limit));
        list.push(...((res.data && res.data.list) || []));
      }

      if (!list.length) {
        Message.error("暂无可导出的数据");
        return;
      }

      exportSaveName.value = `库存管理_${formatExportTime()}.xlsx`;
      exportData.value.data = [
        ["仓库名称", "商品名称", "商品编码", "条码", "商品分类", "规格", "良品库存", "残次品库存", "占用库存", "在途库存"],
        ...list.map((row: StockRow) => [
          (row.warehouse && row.warehouse.name) || "--",
          getGoodsName(row),
          getProductCode(row),
          getBarcode(row),
          getCategoryName(row),
          getSpecName(row),
          getStockText(row, "good_stock"),
          getStockText(row, "defect_stock"),
          getStockText(row, "occupy_stock"),
          getStockText(row, "transit_stock")
        ])
      ];
      await nextTick();
      exportExcel.exportExcel!();
    } catch (error) {
      Message.error((error as Error)?.message || "导出失败");
    } finally {
      exportLoading.value = false;
    }
  };

  return {
    ...table,
    handleSearch,

    searchFields,
    tableColumns,
    tabCounts,
    activeTab,
    handleTabChange,
    hasOccupy,
    hasTransit,
    handleFilterChange,

    flowRef,
    ledgerRef,
    exportLoading,
    exportSaveName,
    exportData,

    getCategoryName,
    handleViewOccupy,
    handleViewTransit,
    handleViewLedger,
    handleExport
  };
};
