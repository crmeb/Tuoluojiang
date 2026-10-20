<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="库管管理" :rightAreaVisible="false" btnText="新增库管" @confirmData="handleSearch"
      @addDataFn="handleAdd" :total="total" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      v-model:page="page" v-model:page-size="pageSize">

      <!-- 库管人员 -->
      <template #users="{ row }">
        <span>{{ formatUserNames(row.users) }}</span>
      </template>

      <!-- 库管类型 -->
      <template #manager_type="{ row }">
        <span>{{ formatManagerType(row.manager_type) }}</span>
      </template>

      <!-- 管理仓库 -->
      <template #warehouses="{ row }">
        <span>{{ formatWarehouseNames(row) }}</span>
      </template>

      <!-- 查看其他仓库库存权限 -->
      <template #can_view_other="{ row }">
        <span>{{ row.can_view_other ? '可查看' : '不可查看' }}</span>
      </template>

      <!-- 状态：开关切换 -->
      <template #status="{ row }">
        <el-switch v-model="row.status" :active-value="1" :inactive-value="0"
          @change="(val) => handleStatusChange(row, val)" active-text="开启" inactive-text="关闭" />
      </template>

      <!-- 操作列 -->
      <template #action="{ row }">
        <el-button type="primary" link @click="handleToEditPage(row)">编辑</el-button>
        <el-divider direction="vertical" />
        <el-button type="primary" link @click="handleDelete(row)">删除</el-button>
      </template>
    </BaseTable>
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import { useKeeper } from "./composables/useKeeper.ts";
import { MANAGER_TYPES, MANAGER_TYPE } from "./constants.ts";

export default {
  name: "InventoryKeeper",
  components: { BaseContainer, BaseTable, oaFromBox },
  setup() {
    const keeperInfo = useKeeper();

    const formatWarehouseNames = (row) => {
      if (row.manager_type === MANAGER_TYPE.SUPER) return "全部";
        const warehouses = row.warehouse_names;
      if (!warehouses || !warehouses.length) return "--";
      return warehouses.map((w) => w).join("、");
    };

    const formatUserNames = (users) => {
      if (!users || !users.length) return "--";
      return users.map((u) => u.name).join("、");
    };

    const formatManagerType = (type) => {
      const managerType = MANAGER_TYPES.find((item) => item.value === type);
      return managerType ? managerType.label : "--";
    };

    return { ...keeperInfo, formatWarehouseNames, formatUserNames, formatManagerType };
  }
};
</script>

<style scoped lang="scss">
.remark-cell {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.operate-time {
  color: #303133;
  line-height: 22px;
}
</style>
