<template>
  <el-drawer :visible.sync="isOpen" :size="DRAWER_SIZE.MD" title="调拨单详情" custom-class="transfer-detail-drawer">
    <!-- 头部操作按钮组：绝对定位到标题栏右侧、关闭按钮左侧。
      待出库可出库、待入库可入库（与调拨列表操作列同一状态判断） -->
    <div v-if="detail" class="header-actions">
      <el-button v-if="detail.status === TRANSFER_STATUS.PENDING_OUT" type="primary" size="small" @click="handleOut">出库</el-button>
      <el-button v-else-if="detail.status === TRANSFER_STATUS.PENDING_IN" type="primary" size="small" @click="handleIn">入库</el-button>
    </div>

    <div v-loading="isLoading" class="transfer-detail">
      <!-- 基本信息：标题 + 字段网格 -->
      <InfoSelection title="基本信息" :fields="basicFields" />

      <!-- 调拨商品信息：标题 + 汇总文案 + 商品表格 -->
      <InfoSelection title="调拨商品信息" fill>
        <p v-if="detail" class="transfer-detail__summary">
          商品共 <span class="highlight">{{ summary.category_count }}</span> 种，总共
          <span class="highlight">{{ summary.goods_count }}</span> 件，总成本
          <span class="highlight">¥{{ summary.total_amount }}</span>
        </p>

        <BaseTable :table-columns="goodsColumns" :data="goodsList" :show-pagination="false">
          <!-- 商品信息：缩略图 + 名称 -->
          <template #goods="{ row }">
            <div class="goods-cell flex">
              <el-image :src="row.image" :preview-src-list="[row.image]" class="goods-img" />
              <span class="goods-name">{{ row.name }}</span>
            </div>
          </template>

          <!-- 规格名称：规格 + 编码 + 条码 -->
          <template #spec="{ row }">
            <div class="goods-spec">
              <div>{{ getSpecName(row) }}</div>
              <div v-if="getProductCode(row) !== '--'" class="goods-spec__sub">编码：{{ getProductCode(row) }}</div>
              <div v-if="getBarcode(row) !== '--'" class="goods-spec__sub">条码：{{ getBarcode(row) }}</div>
            </div>
          </template>
        </BaseTable>
      </InfoSelection>
    </div>
  </el-drawer>
</template>

<script>
import { modalSure } from '@/libs/public'
import InfoSelection from "../../components/InfoSelection.vue";
import BaseTable from "@/components/BaseTable/index.vue";
import { DRAWER_SIZE } from "@/constants/popupSize";
import { transferOutApi, transferInApi } from "@/api/inventory";
import { TRANSFER_STATUS } from "../constants";
import { useTransferDetail } from "../composables/useTransferDetail";
import { getSpecName, getProductCode, getBarcode } from "../../utils";

export default {
  name: "TransferDetail",
  components: { InfoSelection, BaseTable },
  emits: ["ok"],
  setup(props, ctx) {
    const detailInfo = useTransferDetail();

    // 单据操作成功后统一重拉详情（按钮随状态切换）并通知父组件刷新
    const handleAction = (tip, api) => {
      const data = detailInfo.detail.value;
      modalSure(`确认对调拨单「${data.bill_no}」执行${tip}吗`).then(async () => {
        await api(data.id);
        ctx.emit("ok");
        detailInfo.open(data.id);
      });
    };

    // 调拨出库（物流调拨：待出库 -> 待入库）
    const handleOut = () => handleAction("调拨出库", transferOutApi);

    // 调拨入库（物流调拨：待入库 -> 已调拨）
    const handleIn = () => handleAction("调拨入库", transferInApi);

    ctx.expose({
      open: detailInfo.open
    });

    return { ...detailInfo, DRAWER_SIZE, TRANSFER_STATUS, handleOut, handleIn, getSpecName, getProductCode, getBarcode };
  }
};
</script>

<style scoped lang="scss">
// 头部操作按钮组：定位到抽屉标题栏右侧、关闭按钮（right: 20px）左侧
.header-actions {
  position: absolute;
  top: 14px;
  right: 56px;
  z-index: 1;
}

.transfer-detail {
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
.transfer-detail-drawer {
  .el-drawer__body {
    padding: 0;
    overflow-y: auto;
  }
}
</style>
