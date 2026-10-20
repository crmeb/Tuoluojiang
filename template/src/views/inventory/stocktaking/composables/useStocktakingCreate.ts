import { reactive, ref, computed, watch, getCurrentInstance } from "vue";
import type { FormInstance } from "../../types";
import { useRoute, useRouter } from "vue-router";
import { roterPre } from "@/settings";
import {
  checkCreateApi,
  checkContinueApi,
  checkDetailApi
} from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";
import { useStocktakingGoods } from "./useStocktakingGoods";

const toNumber = (val: unknown) => {
  const n = Number(val);
  return Number.isFinite(n) ? n : 0;
};

export const useStocktakingCreate = () => {
  const proxy = getCurrentInstance()!.proxy!;
  const route = useRoute();
  const router = useRouter();

  // 编辑态：路由携带 id 时进入「继续盘点」编辑，盘点单仓库固定不可改
  const checkId = Number(route.query.id) || 0;
  const isEdit = computed(() => !!checkId);
  const pageTitle = computed(() => (isEdit.value ? "编辑盘点单" : "新增盘点单"));
  const warehouseName = ref("");

  // 表单数据（提交字段对齐「新增盘点」接口，缺失字段以「新增入库」为准）
  const form = reactive({
    warehouse_id: "", // 盘点仓库 ID
    remark: "" // 单据备注
  });

  const rules = {
    warehouse_id: [{ required: true, message: "请选择盘点仓库", trigger: "change" }]
  };

  // 盘点商品明细（拆分到独立 composable）
  const goods = useStocktakingGoods();

  // 实盘两列用 headerSlot 承载表头「批量修改」入口
  const goodsColumns = [
    { field: "goods", name: "商品信息", minWidth: 220, slot: "goods" },
    { field: "spec", name: "规格", minWidth: 180, slot: "spec" },
    { field: "good_book_stock", name: "良品账面库存", minWidth: 150, slot: "good_book_stock" },
    {
      field: "real_good_stock",
      name: "良品实际数量",
      minWidth: 150,
      slot: "real_good_stock",
      headerSlot: "real_good_stock_header"
    },
    { field: "good_diff", name: "良品盈亏数量", minWidth: 110, slot: "good_diff" },
    { field: "defect_book_stock", name: "残次品账面库存", minWidth: 130 },
    {
      field: "real_defect_stock",
      name: "残次品实际数量",
      minWidth: 150,
      slot: "real_defect_stock",
      headerSlot: "real_defect_stock_header"
    },
    { field: "defect_diff", name: "残次品盈亏数量", minWidth: 120, slot: "defect_diff" },
    { field: "action", name: "操作", minWidth: 70, fixed: "right", slot: "action" }
  ];

  // 切换盘点仓库：可选商品与账面库存随仓库变化，清空已选商品。
  // 编辑态仓库不可改（详情回填赋值也会触发 watch），跳过避免误清回填明细
  watch(() => form.warehouse_id, () => {
    if (isEdit.value) return;
    goods.clearGoods();
  });

  // 编辑态回填：拉取盘点详情，回填基本信息与商品明细
  const { isLoading: detailLoading, startLoading: startDetail, stopLoading: stopDetail } = useLoading();
  const loadDetail = async () => {
    if (!checkId) return;
    startDetail();
    try {
      const res = await checkDetailApi(checkId);
      // http 库业务失败（status 400 等）仍会 resolve，需显式判定
      if (res.status !== 200) return;
      const data = res.data || {};
      form.warehouse_id = data.warehouse_id ?? (data.warehouse && data.warehouse.id) ?? "";
      form.remark = data.remark || "";
      warehouseName.value = (data.warehouse && data.warehouse.name) || "";
      goods.setGoodsFromDetail(data.details);
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopDetail();
    }
  };

  // 校验：表单必填 + 至少一条盘点商品
  const validate = async (formRef: FormInstance) => {
    try {
      await formRef.validate();
    } catch (e) {
      return false;
    }
    if (!goods.goodsList.value.length) {
      proxy.$message.warning("请先添加盘点商品");
      return false;
    }
    return true;
  };

  // 明细提交体（对齐「新增盘点 / 继续盘点」接口 details）
  const buildDetails = () =>
    goods.goodsList.value.map((row) => ({
      sku_id: row.sku_id,
      real_good_stock: toNumber(row.real_good_stock),
      real_defect_stock: toNumber(row.real_defect_stock),
      remark: row.remark || ""
    }));

  // 持久化：新建走「新增盘点」，编辑走「继续盘点」，两者请求体一致。
  // is_draft 为 true 保存草稿（盘点中，不处理库存）；为 false 直接完成盘点并处理库存差异。
  // http 库业务失败（status 400 等）仍会 resolve，故显式判定 status 返回是否成功供上层中止。
  const persist = async (isDraft: boolean) => {
    const data = {
      warehouse_id: form.warehouse_id,
      remark: form.remark,
      is_draft: isDraft,
      details: buildDetails()
    };
    const res = isEdit.value
      ? await checkContinueApi(checkId, data)
      : await checkCreateApi(data);
    return res.status === 200;
  };

  const goList = () => {
    router.push({ path: roterPre + "/inventory/stocktaking/index" });
  };

  // 保存草稿：is_draft = true，盘点单保持「盘点中」，可继续编辑
  const { isLoading: savingDraft, startLoading: startDraft, stopLoading: stopDraft } = useLoading();
  const saveDraft = async (formRef: FormInstance) => {
    if (!(await validate(formRef))) return;
    startDraft();
    try {
      if (await persist(true)) goList();
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopDraft();
    }
  };

  // 保存：is_draft = false，直接完成盘点，生成盈亏并结转库存
  const { isLoading: savingFinish, startLoading: startFinish, stopLoading: stopFinish } = useLoading();
  const saveFinish = async (formRef: FormInstance) => {
    if (!(await validate(formRef))) return;
    startFinish();
    try {
      if (await persist(false)) goList();
    } catch (e) {
      // 错误提示由 request 统一处理，这里静默
    } finally {
      stopFinish();
    }
  };

  const cancel = () => {
    router.back();
  };

  return {
    form,
    rules,
    isEdit,
    pageTitle,
    warehouseName,
    goodsColumns,
    detailLoading,
    savingDraft,
    savingFinish,

    loadDetail,
    saveDraft,
    saveFinish,
    cancel,
    ...goods
  };
};
