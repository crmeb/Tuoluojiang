<template>
  <BaseContainer class="inbound-create none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader title="新增入库单" />

    <!-- 表单内容（可滚动区域） -->
    <div class="inbound-create__body">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="85px" class="inbound-form"
        label-suffix="：">
        <el-form-item label="入库仓库" prop="warehouse_id">
          <WareHouseSelect v-model="form.warehouse_id" placeholder="请选择" class="form-control"
            :permission="warehousePermission" />
        </el-form-item>

        <el-form-item label="入库类型" prop="inbound_type">
          <el-radio-group v-model="form.inbound_type">
            <el-radio v-for="item of inboundTypes" :key="item.value" :label="item.value" :value="item.value">{{ item.label }}</el-radio>
          </el-radio-group>
          <p class="form-tip">{{ currentTypeDesc }}</p>
        </el-form-item>

        <!-- 关联订单：仅退货入库展示，选中后回填订单商品 -->
        <el-form-item v-if="isReturn" label="订单编号">
          <OrderSelect v-model="form.source_id" :default-keyword="presetContractNo" class="form-control"
            @select="onOrderChange" />
        </el-form-item>

        <el-form-item label="备注">
          <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="250" show-word-limit placeholder="请输入备注"
            class="form-control" />
        </el-form-item>

        <el-form-item label="入库商品" required>
          <!-- 操作按钮：选择商品 + 批量删除（选中商品后才显示批量删除并激活） -->
          <div class="goods-actions">
            <el-button type="primary" @click="openProductDialog">添加商品</el-button>
            <el-button v-if="goodsList.length" :disabled="!selectedGoods.length" @click="batchRemove">批量删除</el-button>
            <span v-if="goodsList.length" class="goods-summary">
              商品共 <em>{{ summary.kind }}</em> 种，总共 <em>{{ summary.count }}</em> 件<template
                v-if="isPurchase">，总金额 <em>¥{{ summary.amount }}</em></template>
            </span>
          </div>

          <!-- 商品明细表格 -->
          <el-table v-if="goodsList.length" v-loading="orderLoading" ref="goodsTableRef" :data="goodsList"
            class="goods-table mt-12" header-row-class-name="goods-table__header"
            @selection-change="handleSelectionChange">
            <el-table-column type="selection" width="44" />
            <el-table-column label="商品信息" min-width="220">
              <template #default="{ row }">
                <div class="goods-cell">
                  <el-image :src="row.image" :preview-src-list="[row.image]" preview-teleported class="goods-img" />
                  <span class="goods-name">{{ row.name }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="规格名称" min-width="160">
              <template #default="{ row }">
                <div class="goods-spec">
                  <div>{{ getSpecName(row) }}</div>
                  <div v-if="getProductCode(row) !== '--'" class="goods-spec__sub">编码：{{ getProductCode(row) }}</div>
                  <div v-if="getBarcode(row) !== '--'" class="goods-spec__sub">条码：{{ getBarcode(row) }}</div>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="售价" min-width="90">
              <template #default="{ row }">¥{{ row.price }}</template>
            </el-table-column>
            <el-table-column v-if="isReturn" label="关联订单销量" min-width="110">
              <template #default="{ row }">{{ getOrderCount(row) }}</template>
            </el-table-column>
            <el-table-column v-if="isPurchase" label="库存属性" min-width="130">
              <template #default="{ row }">
                <el-select v-model="row.stock_quality" placeholder="请选择" class="cell-control">
                  <el-option v-for="item of stockQualities" :key="item.value" :label="item.label" :value="item.value" />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column v-if="isPurchase" label="入库单价" min-width="140">
              <template #default="{ row }">
                <el-input-number v-model="row.unit_price" :min="0" :precision="2" :controls="false"
                  class="cell-control" />
              </template>
            </el-table-column>
            <el-table-column label="入库数量" min-width="140">
              <template #default="{ row }">
                <el-input-number v-model="row.quantity" :min="1" :precision="0" :controls="false"
                  class="cell-control" />
              </template>
            </el-table-column>
            <el-table-column v-if="isPurchase" label="赠送数量" min-width="140">
              <template #default="{ row }">
                <el-input-number v-model="row.gift_quantity" :min="0" :precision="0" :controls="false"
                  class="cell-control" />
              </template>
            </el-table-column>
            <el-table-column v-if="isPurchase" label="金额小计" min-width="150">
              <template #default="{ row }">
                <el-input :model-value="`¥${getRowSubtotal(row)}`" disabled class="cell-control" />
              </template>
            </el-table-column>
            <el-table-column v-if="isPurchase" label="明细备注" min-width="160">
              <template #default="{ row }">
                <el-input v-model="row.remark" maxlength="100" placeholder="请输入备注" class="cell-control" />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="80" fixed="right">
              <template #default="{ $index }">
                <el-button type="primary" link @click="removeGoods($index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-form-item>
      </el-form>
    </div>

    <!-- 底部操作栏 -->
    <FormPageFooter :loading="submitting" @save="submit(formRef)" />

    <!-- 选择商品弹窗（复用客户模块规格选择组件） -->
    <specifications ref="productDialogRef" @getselectList="handleProductSelect" />
  </BaseContainer>
</template>

<script>
import { ref } from "vue";
import BaseContainer from "@/components/BaseContainer/index.vue";
import specifications from "@/views/customer/components/specifications.vue";
import { useInboundCreate } from "./composables/useInboundCreate.ts";
import OrderSelect from "../components/OrderSelect.vue";
import WareHouseSelect from "../components/WareHouseSelect.vue";
import FormPageHeader from "@/components/FormPageHeader/index.vue";
import FormPageFooter from "@/components/FormPageFooter/index.vue";
import { WAREHOUSE_PERMISSION } from "../constants.ts";
import { getSpecName, getProductCode, getBarcode } from "../utils.ts";

export default {
  name: "InventoryInboundCreate",
  components: { BaseContainer, specifications, OrderSelect, WareHouseSelect, FormPageHeader, FormPageFooter },
  setup() {
    const formRef = ref();
    const goodsTableRef = ref();
    const productDialogRef = ref();

    const create = useInboundCreate();

    // 入库仓库仅展示有「入库」权限的可查看仓库
    const warehousePermission = [WAREHOUSE_PERMISSION.INBOUND];

    const openProductDialog = () => {
      productDialogRef.value.openBox(null, create.goodsList.value);
    };

    return {
      ...create,
      warehousePermission,
      formRef,
      goodsTableRef,
      productDialogRef,
      openProductDialog,
      getSpecName,
      getProductCode,
      getBarcode
    };
  }
};
</script>

<style lang="scss" scoped>
.inbound-create {
  &__body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
  }
}

.form-control {
  width: 460px;
}

.form-tip {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 17px;
  color: #909399;
}

.goods-actions {
  display: flex;
  align-items: center;

  .goods-summary {
    margin-left: auto;
    font-size: 13px;
    color: #606266;

    em {
      font-style: normal;
      color: #1890ff;
    }
  }
}

.goods-table {
  width: 100%;

  .goods-cell {
    display: flex;
    align-items: center;
    gap: 10px;

    .goods-img {
      flex-shrink: 0;
      // 全局 styles.scss 对 table 内 el-image 强制 36px !important，此处需同级反制
      width: 42px !important;
      height: 42px !important;
      border-radius: 4px;
    }

    .goods-name {
      line-height: 18px;
    }
  }

  .goods-spec {
    line-height: 18px;
    color: #303133;

    &__sub {
      margin-top: 2px;
      color: #606266;
    }
  }

  .cell-control {
    width: 100%;
  }

  // el-input-number 左对齐，去除计数器后还原为普通输入框观感
  :deep(.el-input-number .el-input__inner) {
    text-align: left;
  }
}

.mt-12 {
  margin-top: 12px;
}
</style>
