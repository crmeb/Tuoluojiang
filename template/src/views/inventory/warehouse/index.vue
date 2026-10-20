<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="仓库管理" :rightAreaVisible="false" :isTotal="false" btnText="新增仓库"
      @confirmData="handleSearch" @addDataFn="handleAdd" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      v-model:page="page" v-model:page-size="pageSize">

      <!-- 开启状态：开关切换 -->
      <template #status="{ row }">
        <el-switch v-model="row.status" :active-value="1" :inactive-value="0"
          @change="(val) => handleStatusChange(row, val)" active-text="开启" inactive-text="关闭" />
      </template>

      <!-- 操作列 -->
      <template #action="{ row }">
        <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
        <el-divider direction="vertical" />
        <el-button type="primary" link @click="handleDelete(row)">删除</el-button>
      </template>
    </BaseTable>

    <WarehouseForm ref="formRef" @ok="handleFormOk" />
  </BaseContainer>
</template>

<script>
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import oaFromBox from "@/components/common/oaFromBox.vue";
import WarehouseForm from "./components/form.vue";
import { useWarehouse } from "./composables/useWarehouse.ts";

export default {
  name: "InventoryWarehouse",
  components: { BaseContainer, BaseTable, oaFromBox, WarehouseForm },
  setup() {
    const warehouseInfo = useWarehouse();

    return { ...warehouseInfo };
  }
};
</script>
