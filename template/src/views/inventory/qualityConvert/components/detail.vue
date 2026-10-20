<template>
  <el-drawer v-model="isOpen" :size="DRAWER_SIZE.MD" title="转换单详情" class="convert-detail-drawer">
    <div v-loading="isLoading" class="convert-detail">
      <!-- 基本信息：标题 + 字段网格 -->
      <InfoSelection title="基本信息" :fields="basicFields" />

      <!-- 转换商品信息：标题 + 汇总文案 + 商品表格 -->
      <InfoSelection title="转换商品信息" fill>
        <p v-if="detail" class="convert-detail__summary">
          商品共 <span class="highlight">{{ summary.category_count }}</span> 种，总共
          <span class="highlight">{{ summary.goods_count }}</span> 件，总金额
          <span class="highlight">¥{{ summary.total_amount }}</span>
        </p>

        <BaseTable :table-columns="goodsColumns" :data="goodsList" :show-pagination="false">
          <!-- 商品信息：缩略图 + 名称 -->
          <template #goods="{ row }">
            <div class="goods-cell flex">
              <el-image :src="row.image" :preview-src-list="[row.image]" preview-teleported class="goods-img" />
              <span class="goods-name">{{ row.name }}</span>
            </div>
          </template>

          <!-- 规格：规格 + 编码 + 条码 -->
          <template #spec="{ row }">
            <div class="goods-spec">
              <div>{{ getSpecName(row) }}</div>
              <div v-if="getProductCode(row) !== '--'" class="goods-spec__sub">编码：{{ getProductCode(row) }}</div>
              <div v-if="getBarcode(row) !== '--'" class="goods-spec__sub">条码：{{ getBarcode(row) }}</div>
            </div>
          </template>

          <!-- 凭证图片：仅展示首图 + 张数角标，点击可预览该明细全部图片 -->
          <template #files="{ row }">
            <div v-if="row.files.length" class="goods-files">
              <el-image :src="row.files[0]" :preview-src-list="row.files" preview-teleported class="goods-files__img" />
              <span v-if="row.files.length > 1" class="goods-files__count">{{ row.files.length }}</span>
            </div>
            <span v-else>--</span>
          </template>
        </BaseTable>
      </InfoSelection>
    </div>
  </el-drawer>
</template>

<script>
import InfoSelection from "../../components/InfoSelection.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import { DRAWER_SIZE } from "@/constants/popupSize.ts";
import { useConvertDetail } from "../composables/useConvertDetail.ts";
import { getSpecName, getProductCode, getBarcode } from "../../utils.ts";

export default {
  name: "ConvertDetail",
  components: { InfoSelection, BaseTable },
  setup(props, ctx) {
    const detailInfo = useConvertDetail();

    ctx.expose({
      open: detailInfo.open
    });

    return { ...detailInfo, DRAWER_SIZE, getSpecName, getProductCode, getBarcode };
  }
};
</script>

<style scoped lang="scss">
.convert-detail {
  // 撑满抽屉高度并转纵向 flex，剩余高度才能经 fill 区块传给内部表格
  height: 100%;
  display: flex;
  flex-flow: column nowrap;
  padding: 20px;

  &__summary {
    margin: 0 0 16px;
    font-size: 13px;
    color: #303133;

    .highlight {
      color: #1890ff;
    }
  }

}

// el-table 的 .cell 默认 overflow: hidden，会把溢出到缩略图外的角标切掉
:deep(.convert-files-cell .cell) {
  overflow: visible;
}

// 凭证图片单元格：参照 goodsInfoCell 的缩略图 + 角标样式
.goods-files {
  position: relative;
  display: inline-block;

  &__img {
    // 全局 styles.scss 对 table 内 el-image 强制 36px !important，此处需同级反制
    width: 42px !important;
    height: 42px !important;
    border-radius: 4px;
  }

  &__count {
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
}

.goods-cell {
  align-items: center;
  gap: 12px;

  .goods-img {
    flex-shrink: 0;
    // 全局 styles.scss 对 table 内 el-image 强制 36px !important，此处需同级反制
    width: 42px !important;
    height: 42px !important;
    border-radius: 4px;
  }

  .goods-name {
    line-height: 20px;
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
</style>

<style lang="scss">
.convert-detail-drawer {
  .el-drawer__body {
    padding: 0;
    overflow-y: auto;
  }
}
</style>
