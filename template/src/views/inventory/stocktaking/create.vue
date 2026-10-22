<template>
  <BaseContainer class="stocktaking-create none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader :title="pageTitle" />

    <!-- 表单内容（可滚动区域） -->
    <div v-loading="detailLoading" class="stocktaking-create__body">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="112px" class="stocktaking-form" size="small"
        label-suffix="：">
        <el-form-item label="盘点仓库" prop="warehouse_id">
          <!-- 编辑态仓库不可改（盘点单仓库固定），以只读文本展示 -->
          <el-input v-if="isEdit" :value="warehouseName" disabled class="form-control" />
          <WareHouseSelect v-else v-model="form.warehouse_id" placeholder="请输入选择盘点仓库" class="form-control"
            :permission="checkPermission" />
        </el-form-item>

        <el-form-item label="单据备注">
          <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="250" show-word-limit
            placeholder="请输入备注信息，最多可输入250字" class="form-control" />
        </el-form-item>

        <el-form-item label="盘点商品信息" required>
          <!-- 操作按钮：添加商品 + 批量删除（选中商品后才显示批量删除并激活） -->
          <div class="goods-actions">
            <el-button type="primary" @click="openProductDialog">添加商品</el-button>
            <el-button v-if="goodsList.length" :disabled="!selectedGoods.length" @click="batchRemove">批量删除</el-button>
            <span v-if="goodsList.length" class="goods-summary">
              商品共 <em>{{ summary.kind }}</em> 种，盘盈 <em class="is-profit">{{ summary.profit }}</em> 件，盘亏
              <em class="is-loss">{{ summary.loss }}</em> 件
            </span>
          </div>

          <!-- 商品明细表格 -->
          <BaseTable v-if="goodsList.length" ref="goodsTableRef" class="goods-table mt-12" :data="goodsList"
            :table-columns="goodsColumns" :show-pagination="false" selection row-key="unique" auto-height
            @selection-change="handleSelectionChange">
            <!-- 商品信息：缩略图 + 名称 -->
            <template #goods="{ row }">
              <div class="goods-cell">
                <el-image :src="row.image" :preview-src-list="[row.image]" class="goods-img" />
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

            <template #good_book_stock="{ row }">
              <span>{{ row.good_book_stock }}</span>
              <span class="goods-occupy">（其中占用：{{ row.good_occupy }}）</span>
            </template>

            <!-- 良品实际数量：表头挂批量修改入口，确定后覆盖全部商品行 -->
            <template #real_good_stock_header>
              良品实际数量
              <BatchEditPopover title="批量修改良品实际数量" placeholder="请输入良品实际数量"
                @confirm="(val) => batchFillStock('real_good_stock', val)" />
            </template>

            <template #real_good_stock="{ row }">
              <el-input-number v-model="row.real_good_stock" :min="0" :precision="0" :controls="false" size="small"
                class="cell-control" />
            </template>

            <template #good_diff="{ row }">
              <span :class="diffClass(getGoodDiff(row))">{{ formatDiff(getGoodDiff(row)) }}</span>
            </template>

            <template #real_defect_stock_header>
              残次品实际数量
              <BatchEditPopover title="批量修改残次品实际数量" placeholder="请输入残次品实际数量"
                @confirm="(val) => batchFillStock('real_defect_stock', val)" />
            </template>

            <template #real_defect_stock="{ row }">
              <el-input-number v-model="row.real_defect_stock" :min="0" :precision="0" :controls="false" size="small"
                class="cell-control" />
            </template>

            <template #defect_diff="{ row }">
              <span :class="diffClass(getDefectDiff(row))">{{ formatDiff(getDefectDiff(row)) }}</span>
            </template>

            <template #action="{ index }">
              <el-button type="text" @click="removeGoods(index)">删除</el-button>
            </template>
          </BaseTable>
        </el-form-item>
      </el-form>
    </div>

    <!-- 底部操作栏：保存草稿 + 保存（完成盘点） -->
    <FormPageFooter>
      <el-button size="small" :loading="savingDraft" @click="saveDraft(formRef)">保存草稿</el-button>
      <el-button size="small" type="primary" :loading="savingFinish" @click="saveFinish(formRef)">保存</el-button>
    </FormPageFooter>

    <!-- 选择商品弹窗（复用客户模块规格选择组件），按盘点仓库过滤库存 -->
    <specifications ref="productDialogRef" :warehouse-id="form.warehouse_id" @getselectList="handleProductSelect" />
  </BaseContainer>
</template>

<script>
import { ref, getCurrentInstance } from "vue";
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import specifications from "@/views/customer/components/specifications.vue";
import { useStocktakingCreate } from "./composables/useStocktakingCreate";
import WareHouseSelect from "../components/WareHouseSelect.vue";
import BatchEditPopover from "../components/BatchEditPopover.vue";
import FormPageHeader from "@/components/FormPageHeader/index.vue";
import FormPageFooter from "@/components/FormPageFooter/index.vue";
import { WAREHOUSE_PERMISSION } from "../constants";
import { getSpecName, getProductCode, getBarcode } from "../utils";

export default {
  name: "InventoryStocktakingCreate",
  components: {
    BaseContainer,
    BaseTable,
    specifications,
    WareHouseSelect,
    BatchEditPopover,
    FormPageHeader,
    FormPageFooter
  },
  setup() {
    const { proxy } = getCurrentInstance();
    const formRef = ref();
    const goodsTableRef = ref();
    const productDialogRef = ref();

    const create = useStocktakingCreate();
    // 编辑态：进入页面即拉取详情回填（无 id 时内部直接返回）
    create.loadDetail();

    // 选品弹窗按盘点仓库过滤库存，未选仓库前不允许选商品
    const openProductDialog = () => {
      if (!create.form.warehouse_id) {
        proxy.$message.warning("请先选择盘点仓库");
        return;
      }
      productDialogRef.value.openBox(null, create.goodsList.value);
    };

    // 盈亏展示：正数加「+」号，0 与负数原样展示
    const formatDiff = (diff) => (diff > 0 ? `+${diff}` : `${diff}`);
    // 盈亏样式：盈（正）绿色，亏（负）红色，持平默认色
    const diffClass = (diff) => (diff > 0 ? "is-profit" : diff < 0 ? "is-loss" : "");

    // 仓库下拉仅展示有「盘点」权限的仓库
    const checkPermission = [WAREHOUSE_PERMISSION.CHECK];

    return {
      ...create,
      formRef,
      goodsTableRef,
      productDialogRef,
      checkPermission,
      openProductDialog,
      formatDiff,
      diffClass,
      getSpecName,
      getProductCode,
      getBarcode
    };
  }
};
</script>

<style lang="scss" scoped>
.stocktaking-create {
  &__body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
  }
}

.form-control {
  width: 460px;
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

      &.is-profit {
        color: #19be6b;
      }

      &.is-loss {
        color: #f56c6c;
      }
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

  .goods-occupy {
    color: #909399;
  }

  // 盈亏数量：盈绿 / 亏红
  .is-profit {
    color: #19be6b;
  }

  .is-loss {
    color: #f56c6c;
  }

  .cell-control {
    width: 100%;
  }

  // el-input-number 左对齐，去除计数器后还原为普通输入框观感
  ::v-deep .el-input-number .el-input__inner {
    text-align: left;
  }
}

.mt-12 {
  margin-top: 12px;
}
</style>
