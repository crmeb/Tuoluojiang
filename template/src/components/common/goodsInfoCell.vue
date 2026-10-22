<!-- 单据商品信息单元格：明细缩略图 + 数量角标，最多展示 max 个，超出显示「…」占位块，点击缩略图可预览全部商品图 -->
<template>
  <div class="goods-info-cell">
    <div v-for="(item, index) of visibleDetails" :key="index" class="goods-img-box">
      <el-image :src="getItemImage(item)" :preview-src-list="previewSrcList" class="goods-img" />
      <span class="goods-count">{{ getItemCount(item) }}</span>
    </div>
    <div v-if="restCount > 0" class="goods-more">
      <span v-for="dot of 3" :key="dot" class="goods-more__dot" />
    </div>
  </div>
</template>

<script>
import { computed } from "vue";

export default {
  name: "GoodsInfoCell",
  props: {
    // 单据明细数组，元素需包含 sku.image（或扁平 image）与 quantity（入库/出库/调拨/正次转换/出入库记录等单据明细通用结构）
    details: {
      type: Array,
      default: () => []
    },
    // 最多展示的缩略图个数
    max: {
      type: Number,
      default: 3
    },
    // 数量角标是否叠加赠品数量（gift_quantity）：入库等含赠品的单据开启，其余单据仍只展示 quantity
    includeGift: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const getItemImage = (item) => (item.sku && item.sku.image) || item.image;

    // 数量后端可能以字符串返回（如 "1.0000"），统一转数字后再相加
    const getItemCount = (item) => {
      const count = Number(item.quantity) || 0;
      return props.includeGift ? count + (Number(item.gift_quantity) || 0) : count;
    };

    const visibleDetails = computed(() => (props.details || []).slice(0, props.max));
    const previewSrcList = computed(() => (props.details || []).map(getItemImage));
    const restCount = computed(() => Math.max((props.details || []).length - props.max, 0));

    return { visibleDetails, previewSrcList, restCount, getItemImage, getItemCount };
  }
};
</script>

<style scoped lang="scss">
.goods-info-cell {
  display: flex;
  gap: 7px;
  padding: 15px 0 9px;

  .goods-img-box {
    position: relative;
  }

  .goods-count {
    position: absolute;
    top: -6px;
    right: -6px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 14px;
    height: 14px;
    padding-inline: 4px;
    font-size: 10px;
    color: #fff;
    background-color: #1890ff;
    border-radius: 7px;
  }

  .goods-img {
    width: 42px !important;
    height: 42px !important;
    border-radius: 4px;
  }

  // 超出 max 的「…」占位块
  .goods-more {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    width: 42px;
    height: 42px;
    background-color: #d8dce5;
    border-radius: 4px;

    &__dot {
      width: 5px;
      height: 5px;
      background-color: #fff;
      border-radius: 50%;
    }
  }
}
</style>
