import { ref, reactive, computed, nextTick, getCurrentInstance } from "vue";
import { useRoute, useRouter } from "vue-router/composables";
import { roterPre } from "@/settings";
import {
  keeperCreateApi,
  keeperUpdateApi,
  keeperDetailApi
} from "@/api/inventory";
import { useLoading } from "@/composables/useLoading";
import { useWarehouse } from "../../composables/useWarehouse";
import { MANAGER_TYPE, MANAGER_TYPES, PERMISSION_LIST } from "../constants.js";

/**
 * 库管表单逻辑：同时支持新增与编辑
 * - 编辑态由 URL query.id 判定，进入页面时拉取详情回填
 * - 提交时按新增 / 编辑分流调用对应接口
 */
export const useKeeperForm = () => {
  const { proxy } = getCurrentInstance();
  const route = useRoute();
  const router = useRouter();

  const formRef = ref();
  const memberRef = ref();

  // 库管 ID 来源于 URL，存在即为编辑态
  const keeperId = computed(() => route.query.id || null);
  const isEdit = computed(() => !!keeperId.value);
  const title = computed(() => (isEdit.value ? "编辑库管" : "新增库管"));

  const managerTypes = MANAGER_TYPES;
  const permissionList = PERMISSION_LIST;

    // 创建一条空的仓库权限行
  const createPermissionRow = (row = {}) => ({
    warehouse_id: row.warehouse_id ?? null,
    ...permissionList.reduce((acc, item) => {
      acc[item.key] = row[item.key] ?? 0;
      return acc;
    }, {})
  });

  const form = reactive({
    name: "", // 库管名称
    manager_type: MANAGER_TYPE.NORMAL, // 库管类型
    can_view_other: 0, // 是否可查看其它库存
    sort: 0, // 排序
    status: 1, // 状态
    remark: "", // 备注
    user_ids: [], // 库管人员用户 ID
    warehouse_ids: [], // 关联仓库 ID
    warehouse_permissions: [
      createPermissionRow()
    ] // 仓库权限配置
  });

  const rules = {
    name: [{ required: true, message: "请输入库管角色", trigger: "blur" }]
  };

  const tableColumns = [
    { field: "warehouse_name", name: "管理仓库", minWidth: 160, slot: "warehouse_name" },
    { field: "permission", name: "权限", minWidth: 400, slot: "permission" },
    { field: "action", name: "操作", minWidth: 120, slot: "action" },
  ];

  // 库管配置改的就是「谁能看哪个仓库」，提交成功后必须强制刷新可查看仓库缓存，
  // 否则改到当前登录人所属的库管角色时，本地缓存仍是旧的权限（immediate: false 避免多余请求）
  const { refreshWarehouses: refreshAccessibleWarehouses } = useWarehouse({ immediate: false });

  const { isLoading: submitting, startLoading, stopLoading } = useLoading();
  const { isLoading: detailLoading, startLoading: startDetailLoading, stopLoading: stopDetailLoading } = useLoading();

  // 添加仓库到权限列表
  const onAddWarehouse = () => {
    form.warehouse_permissions.push(createPermissionRow());
  };

  // 移除仓库
  const removeWarehouse = (index) => {
    form.warehouse_permissions.splice(index, 1);
  };

  // 库管人员选择回调
  const onMemberChange = (list) => {
    form.user_ids = list.map((u) => (u.value !== undefined ? u.value : u.id));
  };

  // 仓库去重校验
  const onWarehouseChange = (index) => {
    const warehouseIdList = form.warehouse_permissions.map(item => item.warehouse_id);
    const warehouseId = warehouseIdList[index];
    const isUnique = warehouseIdList.filter(id => id === warehouseId).length <= 1;

    if (!isUnique) {
      proxy.$message.warning("不能选择已存在仓库!");
      nextTick(() => {
        form.warehouse_permissions[index].warehouse_id = null;
      });
    }
  };

  // 拉取详情并回填表单
  const loadDetail = async () => {
    if (!isEdit.value) return;
    startDetailLoading();
    try {
      const res = await keeperDetailApi(keeperId.value);
      if (res.status !== 200 || !res.data) return;
      const data = res?.data || {};
      form.name = data.name ?? "";
      form.manager_type = data.manager_type ?? MANAGER_TYPE.NORMAL;
      form.can_view_other = data.can_view_other ?? 0;
      form.sort = data.sort ?? 0;
      form.status = data.status ?? 1;
      form.remark = data.remark ?? "";
      form.user_ids = data.user_ids || [];
      form.warehouse_permissions = (data.warehouses || []).map((w) => createPermissionRow(w));
    } catch (e) {
      proxy.$message.error(e?.message || "获取库管详情失败");
    } finally {
      stopDetailLoading();
    }
  };

  // 提交（新增 / 编辑分流）
  const submit = () => {
    formRef.value.validate(async (valid) => {
      if (!valid) return;
      if (form.manager_type === MANAGER_TYPE.NORMAL) {
        // 默认存在一行空数据，仅判断行数不够，需确保每行都已选择仓库
        const hasEmptyWarehouse = form.warehouse_permissions.some(item => !item.warehouse_id);
        if (!form.warehouse_permissions.length || hasEmptyWarehouse) {
          proxy.$message.warning("管理仓库不能为空");
          return;
        }
      }
      if (!form.user_ids.length) {
        proxy.$message.warning("请选择库管人员");
        return;
      }

      const payload = {
        ...form,
        warehouse_ids: form.warehouse_permissions.map(item => item.warehouse_id)
      };

      startLoading();
      try {
        const res = isEdit.value
          ? await keeperUpdateApi(keeperId.value, payload)
          : await keeperCreateApi(payload);
        if (res.status === 200) {
          // 不阻塞跳转：目标页可能先读到旧缓存，刷新完成后 getters 会响应式更新
          refreshAccessibleWarehouses();
          router.push({ path: roterPre + "/inventory/keeper/index" });
        }
      } catch (e) {
        proxy.$message.error(e?.message || (isEdit.value ? "编辑库管失败" : "新增库管失败"));
      } finally {
        stopLoading();
      }
    });
  };

  const cancel = () => {
    router.back();
  };

  return {
    formRef,
    memberRef,
    form,
    rules,
    tableColumns,
    permissionList,
    managerTypes,
    submitting,
    detailLoading,
    isEdit,
    title,
    onAddWarehouse,
    removeWarehouse,
    onMemberChange,
    onWarehouseChange,
    loadDetail,
    submit,
    cancel
  };
};
