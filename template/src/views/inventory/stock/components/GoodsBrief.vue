<template>
  <div class="goods-brief flex">
    <el-image :src="sku && sku.image" :preview-src-list="sku && sku.image ? [sku.image] : []"
      class="goods-brief__img" />
    <div class="goods-brief__info">
      <div class="goods-brief__name">{{ goodsName }}</div>
      <div class="goods-brief__body flex">
        <div class="goods-brief__sub">规格: {{ specName }}</div>
        <div class="goods-brief__sub">编码: {{ productCode }}</div>
        <div class="goods-brief__sub">条码: {{ barcode }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from "vue";
import { getGoodsName, getSpecName, getProductCode, getBarcode } from "../utils";

export default {
  name: "GoodsBrief",
  props: {
    product: {
      type: Object,
      default: () => ({})
    },
    sku: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const row = computed(() => ({ product: props.product, sku: props.sku }));
    const goodsName = computed(() => getGoodsName(row.value));
    const specName = computed(() => getSpecName(row.value));
    const productCode = computed(() => getProductCode(row.value));
    const barcode = computed(() => getBarcode(row.value));

    return { goodsName, specName, productCode, barcode };
  }
};
</script>

<style scoped lang="scss">
.goods-brief {
  gap: 12px;

  &__img {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    border-radius: 4px;
  }

  &__info {
    display: flex;
    flex-flow: column nowrap;
    justify-content: center;
  }

  &__body {
    gap: 10px;
    margin-top: 5px;
  }

  &__name {
    font-size: 14px;
    font-weight: 500;
    color: #303133;
    line-height: 20px;
  }

  &__sub {
    font-size: 12px;
    color: #606266;
    line-height: 18px;
  }
}
</style>
