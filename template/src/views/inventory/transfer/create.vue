<template>
  <BaseContainer class="transfer-create none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader :title="pageTitle" />

    <!-- 表单内容（可滚动区域） -->
    <div v-loading="detailLoading" class="transfer-create__body">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="85px" class="transfer-form"
        label-suffix="：">
        <p class="section-title">基本信息</p>

        <el-form-item label="调拨方式" prop="transfer_type">
          <el-radio-group v-model="form.transfer_type">
            <el-radio v-for="item of transferTypes" :key="item.value" :label="item.value" :value="item.value">{{ item.label }}</el-radio>
          </el-radio-group>
          <div class="form-tip">
            <p v-for="item of transferTypes" :key="item.value">{{ item.desc }}</p>
          </div>
        </el-form-item>

        <el-form-item label="调拨仓库" required class="warehouse-item">
          <div class="warehouse-pair">
            <el-form-item prop="out_warehouse_id" class="warehouse-pair__col">
              <WareHouseSelect v-model="form.out_warehouse_id" placeholder="请输入选择调出仓库"
                :disabled-ids="form.in_warehouse_id ? [form.in_warehouse_id] : []" class="warehouse-select" :permission="transferPermission" />
            </el-form-item>
            <span class="warehouse-pair__arrow">→</span>
            <el-form-item prop="in_warehouse_id" class="warehouse-pair__col">
              <WareHouseSelect v-model="form.in_warehouse_id" placeholder="请输入选择调入仓库"
                :disabled-ids="form.out_warehouse_id ? [form.out_warehouse_id] : []" class="warehouse-select" :permission="transferPermission" />
            </el-form-item>
          </div>
        </el-form-item>

        <el-form-item label="单据备注">
          <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="250" show-word-limit
            placeholder="请输入备注信息，最多可输入250字" class="form-control" />
        </el-form-item>

        <el-form-item label="调拨商品" required>
          <!-- 操作按钮：添加商品 + 批量删除（选中商品后才显示批量删除并激活） -->
          <div class="goods-actions">
            <el-button type="primary" @click="openProductDialog">添加商品</el-button>
            <el-button v-if="goodsList.length" :disabled="!selectedGoods.length" @click="batchRemove">批量删除</el-button>
            <span v-if="goodsList.length" class="goods-summary">
              商品共 <em>{{ summary.kind }}</em> 种，总共 <em>{{ summary.count }}</em> 件，总售价 <em>¥{{ summary.amount }}</em>
            </span>
          </div>

          <!-- 商品明细表格 -->
          <BaseTable v-if="goodsList.length" ref="goodsTableRef" class="goods-table mt-12" :data="goodsList"
            :table-columns="goodsColumns" :show-pagination="false" selection row-key="unique" auto-height
            @selection-change="handleSelectionChange">
            <!-- 商品信息：缩略图 + 名称 -->
            <template #goods="{ row }">
              <div class="goods-cell">
                <el-image :src="row.image" :preview-src-list="[row.image]" preview-teleported class="goods-img" />
                <span class="goods-name">{{ row.name }}</span>
              </div>
            </template>

            <!-- 规格：规格名 + 编码 + 条码 -->
            <template #spec="{ row }">
              <div class="goods-spec">
                <div>{{ getSpecName(row) }}</div>
                <div v-if="getProductCode(row) !== '--'" class="goods-spec__sub">编码：{{ getProductCode(row) }}</div>
                <div v-if="getBarcode(row) !== '--'" class="goods-spec__sub">条码：{{ getBarcode(row) }}</div>
              </div>
            </template>

            <template #stock_quality="{ row }">
              <el-select v-model="row.stock_quality" placeholder="请选择" class="cell-control">
                <el-option v-for="item of stockQualities" :key="item.value" :label="item.label" :value="item.value" />
              </el-select>
            </template>

            <!-- 剩余库存：随库存属性在良品可用库存 / 次品库存间切换 -->
            <template #stock="{ row }">{{ getRowStockText(row) }}</template>

            <template #quantity="{ row }">
              <el-input-number v-model="row.quantity" :min="1" :precision="0" :controls="false"
                :max="getRowMax(row)" class="cell-control" />
            </template>

            <template #price="{ row }">¥{{ row.price }}</template>

            <template #subtotal="{ row }">
              <el-input :model-value="`¥${getRowSubtotal(row)}`" disabled class="cell-control" />
            </template>

            <template #action="{ index }">
              <el-button type="primary" link @click="removeGoods(index)">删除</el-button>
            </template>
          </BaseTable>
        </el-form-item>
      </el-form>
    </div>

    <!-- 底部操作栏 -->
    <FormPageFooter :loading="submitting" @save="submit(formRef)" />

    <!-- 选择商品弹窗（复用客户模块规格选择组件） -->
    <specifications ref="productDialogRef" :warehouse-id="form.out_warehouse_id" @getselectList="handleProductSelect" />
  </BaseContainer>
</template>

<script>
import { ref, getCurrentInstance } from "vue";
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import specifications from "@/views/customer/components/specifications.vue";
import { useTransferCreate } from "./composables/useTransferCreate.ts";
import WareHouseSelect from "../components/WareHouseSelect.vue";
import FormPageHeader from "@/components/FormPageHeader/index.vue";
import FormPageFooter from "@/components/FormPageFooter/index.vue";
import { WAREHOUSE_PERMISSION } from "../constants.ts";
import { getSpecName, getProductCode, getBarcode } from "../utils.ts";

export default {
  name: "InventoryTransferCreate",
  components: { BaseContainer, BaseTable, specifications, WareHouseSelect, FormPageHeader, FormPageFooter },
  setup() {
    const { proxy } = getCurrentInstance();
    const formRef = ref();
    const goodsTableRef = ref();
    const productDialogRef = ref();

    const create = useTransferCreate();
    // 编辑态：进入页面即拉取详情回填（无 id 时内部直接返回）
    create.loadDetail();

    // 选品弹窗按调出仓库过滤库存，未选调出仓库前不允许选商品
    const openProductDialog = () => {
      if (!create.form.out_warehouse_id) {
        proxy.$message.warning("请先选择调出仓库");
        return;
      }
      productDialogRef.value.openBox(null, create.goodsList.value);
    };

    const transferPermission = [
      WAREHOUSE_PERMISSION.TRANSFER
    ];

    return {
      ...create,
      formRef,
      goodsTableRef,
      productDialogRef,
      transferPermission,
      openProductDialog,
      getSpecName,
      getProductCode,
      getBarcode
    };
  }
};
</script>

<style lang="scss" scoped>
.transfer-create {
  &__body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
  }
}

.section-title {
  margin: 0 0 16px;
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}

.form-control {
  width: 460px;
}

.form-tip {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 17px;
  color: #909399;

  p {
    margin: 0;
  }
}

// 调拨仓库：调出 → 调入
.warehouse-pair {
  display: flex;
  align-items: center;
  gap: 12px;

  &__col {
    margin-bottom: 0;

    .warehouse-select {
      width: 220px;
    }
  }

  &__arrow {
    color: #909399;
  }
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
