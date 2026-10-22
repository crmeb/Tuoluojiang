<template>
  <BaseContainer class="keeper-create none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader :title="title" />

    <!-- 表单内容（可滚动区域） -->
    <div class="keeper-create__body" v-loading="detailLoading">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" class="keeper-form" size="small">
        <el-form-item label="库管角色：" prop="name">
          <el-input v-model="form.name" placeholder="请输入库管角色" maxlength="100" show-word-limit class="form-control" />
        </el-form-item>

        <el-form-item label="库管人员：" required>
          <div class="form-control">
            <select-member ref="memberRef" :selectIdData="form.user_ids" @getSelectList="onMemberChange" />
          </div>
        </el-form-item>

        <el-form-item label="备注：">
          <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="200" show-word-limit placeholder="请输入备注"
            class="form-control" />
        </el-form-item>

        <el-form-item label="排序：" prop="sort">
          <el-input v-model="form.sort" placeholder="请输入排序" class="form-control" />
          <div class="form-tip">数字越大越靠前</div>
        </el-form-item>

        <el-form-item label="开启状态：" prop="status">
          <el-switch v-model="form.status" class="form-control" :active-value="1" :inactive-value="0" active-text="开启"
            inactive-text="关闭" />
        </el-form-item>

        <el-form-item label="权限类型：">
          <el-radio-group v-model="form.manager_type">
            <el-radio v-for="item of managerTypes" :key="item.value" :label="item.value">{{ item.label }}</el-radio>
          </el-radio-group>
          <div class="form-tip">超级管理员拥有最高库管权限，可以查看并操作所有仓库数据</div>
        </el-form-item>

        <template v-if="form.manager_type === 0">
          <el-form-item label="管理仓库：">
            <BaseTable v-if="form.warehouse_permissions.length" :data="form.warehouse_permissions"
              :table-columns="tableColumns" :show-pagination="false" auto-height>
              <template #warehouse_name="{ row, index }">
                <!-- 此处是给库管员分配仓库权限，数据源必须是全量仓库（scope=all）：
              accessible 接口只返回当前登录人已有权限的仓库，用它做选项会导致
              ①待授权的仓库根本选不到 ②编辑回显时匹配不到 option，只显示裸 ID。
              同理刻意不传 permission（按业务动作过滤同样会漏掉待授权的仓库） -->
                <WareHouseSelect v-model="row.warehouse_id" :scope="WAREHOUSE_SCOPE.ALL" size="small"
                  @change="() => onWarehouseChange(index)" />
              </template>
              <template #permission="{ row }">
                <el-checkbox v-for="item of permissionList" :key="item.name" :label="item.name" :true-label="1"
                  :false-label="0" v-model="row[item.key]" />
              </template>
              <template #action="{ index }">
                <el-button type="text" @click="removeWarehouse(index)">删除</el-button>
              </template>
            </BaseTable>
            <el-button size="small" @click="onAddWarehouse" class="mt-10 ml-20">添加管理仓库</el-button>
          </el-form-item>

          <el-form-item label="查看其他仓库库存权限" prop="can_view_other" class="view-other-item">
            <el-switch v-model="form.can_view_other" class="form-control" :active-value="1" :inactive-value="0"
              active-text="开启" inactive-text="关闭" />
          </el-form-item>
        </template>

      </el-form>
    </div>

    <!-- 底部操作栏 -->
    <FormPageFooter :loading="submitting" @save="submit" />
  </BaseContainer>
</template>

<script>
import { onMounted } from "vue";
import BaseContainer from "@/components/BaseContainer/index.vue";
import SelectMember from "@/components/form-common/select-member.vue";
import WareHouseSelect from "../components/WareHouseSelect.vue";
import FormPageHeader from "@/components/FormPageHeader/index.vue";
import FormPageFooter from "@/components/FormPageFooter/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import { useKeeperForm } from "./composables/useKeeperForm";
import { WAREHOUSE_SCOPE } from "../constants";

export default {
  name: "InventoryKeeperCreate",
  components: { BaseContainer, SelectMember, BaseTable, WareHouseSelect, FormPageHeader, FormPageFooter },
  setup() {
    const keeperForm = useKeeperForm();

    onMounted(() => {
      keeperForm.loadDetail();
    });

    return {
      WAREHOUSE_SCOPE,
      ...keeperForm
    };
  }
};
</script>

<style lang="scss" scoped>
.keeper-create {
  &__body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
  }
}

.form-control {
  width: 460px;
}

.mt-12 {
  margin-top: 12px;
}

.warehouse-section {
  .warehouse-table {
    width: 100%;

    ::v-deep .warehouse-table__header th {
      font-size: 12px;
      padding: 8px 0;
    }

    ::v-deep .el-checkbox {
      margin-right: 0;
    }
  }
}

.form-tip {
  margin-top: 4px;
  font-size: 12px;
  line-height: 18px;
  color: #909399;
}

.view-other-item {
  :deep(.el-form-item__label[for=can_view_other]) {
    position: relative;
    line-height: 20px;
    padding-right: 18px;

    &::after {
      content: "：";
      position: absolute;
      bottom: 0;
      right: 5px;
    }
  }
}
</style>
