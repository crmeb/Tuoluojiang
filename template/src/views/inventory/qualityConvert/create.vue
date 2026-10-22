<template>
  <BaseContainer class="convert-create none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader title="新增转换单" />

    <!-- 表单内容（可滚动区域） -->
    <div class="convert-create__body">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="112px" class="convert-form" size="small"
        label-suffix="：">
        <el-form-item label="转换仓库" prop="warehouse_id">
          <WareHouseSelect v-model="form.warehouse_id" placeholder="请输入选择仓库" class="form-control"
            :permission="convertPermission" />
        </el-form-item>

        <el-form-item label="转换类型" prop="convert_type">
          <el-radio-group v-model="form.convert_type">
            <el-radio v-for="item of convertTypes" :key="item.value" :label="item.value">{{ item.label }}</el-radio>
          </el-radio-group>
          <p class="form-tip">{{ convertTypeDesc }}</p>
        </el-form-item>

        <el-form-item label="单据备注">
          <el-input v-model="form.remark" type="textarea" :rows="3" maxlength="250" show-word-limit
            placeholder="请输入备注信息，最多可输入250字" class="form-control" />
        </el-form-item>

        <el-form-item label="转换商品信息" required>
          <!-- 操作按钮：添加商品 + 批量删除（选中商品后才显示批量删除并激活） -->
          <div class="goods-actions">
            <el-button type="primary" @click="openProductDialog">添加商品</el-button>
            <el-button v-if="goodsList.length" :disabled="!selectedGoods.length" @click="batchRemove">批量删除</el-button>
            <span v-if="goodsList.length" class="goods-summary">
              商品共 <em>{{ summary.kind }}</em> 种，总共 <em>{{ summary.count }}</em> 件，总金额 <em>¥{{ summary.amount }}</em>
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

            <template #cost_price="{ row }">¥{{ row.cost_price }}</template>

            <!-- 剩余库存：随转换类型在良品 / 残次库存间切换 -->
            <template #stock="{ row }">{{ getRowStock(row, form.convert_type) }}</template>

            <template #quantity="{ row }">
              <el-input-number v-model="row.quantity" :min="1" :precision="0" :controls="false" size="small"
                :max="getRowStock(row, form.convert_type) || 1" class="cell-control" />
            </template>

            <template #subtotal="{ row }">
              <el-input :value="`¥${getRowSubtotal(row)}`" size="small" disabled class="cell-control" />
            </template>

            <template #images="{ row }">
              <div class="goods-attach">
                <ImageAttachUpload :value="row.images" :max-length="attachMax"
                  @getVal="(files) => (row.images = files)" />
              </div>
            </template>

            <template #action="{ index }">
              <el-button type="text" @click="removeGoods(index)">删除</el-button>
            </template>
          </BaseTable>
        </el-form-item>
      </el-form>
    </div>

    <!-- 底部操作栏 -->
    <FormPageFooter :loading="submitting" @save="submit(formRef)" />

    <!-- 选择商品弹窗（复用客户模块规格选择组件） -->
    <specifications ref="productDialogRef" :warehouse-id="form.warehouse_id" @getselectList="handleProductSelect" />
  </BaseContainer>
</template>

<script>
import { ref, getCurrentInstance } from "vue";
import BaseContainer from "@/components/BaseContainer/index.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import specifications from "@/views/customer/components/specifications.vue";
import { useConvertCreate } from "./composables/useConvertCreate";
import WareHouseSelect from "../components/WareHouseSelect.vue";
import FormPageHeader from "@/components/FormPageHeader/index.vue";
import FormPageFooter from "@/components/FormPageFooter/index.vue";
import ImageAttachUpload from "./components/ImageAttachUpload.vue";
import { CONVERT_ATTACH_MAX } from "./constants";
import { WAREHOUSE_PERMISSION } from "../constants";
import { getSpecName, getProductCode, getBarcode } from "../utils";

export default {
  name: "InventoryQualityConvertCreate",
  components: {
    BaseContainer,
    BaseTable,
    specifications,
    WareHouseSelect,
    FormPageHeader,
    FormPageFooter,
    ImageAttachUpload
  },
  setup() {
    const formRef = ref();
    const goodsTableRef = ref();
    const productDialogRef = ref();

    const create = useConvertCreate();

    const { proxy } = getCurrentInstance();

    // 仅可选择具备「正次转换」权限的仓库
    const convertPermission = [WAREHOUSE_PERMISSION.CONVERT];

    // 选品弹窗按仓库过滤库存，未选仓库前不允许选商品
    const openProductDialog = () => {
      if (!create.form.warehouse_id) {
        proxy.$message.warning("请先选择转换仓库");
        return;
      }
      productDialogRef.value.openBox(null, create.goodsList.value);
    };

    return {
      ...create,
      attachMax: CONVERT_ATTACH_MAX,
      formRef,
      goodsTableRef,
      productDialogRef,
      convertPermission,
      openProductDialog,
      getSpecName,
      getProductCode,
      getBarcode
    };
  }
};
</script>

<style lang="scss" scoped>
.convert-create {
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

  // 图片附件为 58px 方块，撑高行时上下留出呼吸空间
  .goods-attach {
    margin: 10px 0;
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
