<template>
  <el-drawer :visible.sync="isOpen" :size="DRAWER_SIZE.MD" title="入库单详情" custom-class="inbound-detail-drawer" append-to-body>
    <!-- 头部操作按钮组：绝对定位到标题栏右侧、关闭按钮左侧。
      仅待入库单据可入库（与入库列表操作列同一状态判断） -->
    <div v-if="detail && detail.status === INBOUND_STATUS.PENDING" class="header-actions">
      <el-button type="primary" size="small" @click="handleInbound">入库</el-button>
    </div>

    <div v-loading="isLoading" class="inbound-detail">
      <!-- 基本信息：标题 + 字段网格 -->
      <InfoSelection title="基本信息" :fields="basicFields" />

      <!-- 入库商品信息：标题 + 汇总文案 + 商品表格 -->
      <InfoSelection title="入库商品信息" fill>
        <p v-if="detail" class="inbound-detail__summary">
          商品共 <span class="highlight">{{ summary.category_count }}</span> 种，总共
          <span class="highlight">{{ summary.goods_count }}</span> 件<template v-if="isPurchase">，总金额
            <span class="highlight">¥{{ summary.total_amount }}</span></template>
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

          <!-- 明细备注：列宽有限，单行省略，完整内容悬浮查看 -->
          <template #remark="{ row }">
            <el-tooltip v-if="row.remark" effect="light" placement="top" :content="row.remark"
              popper-class="goods-remark-tooltip">
              <span class="goods-remark">{{ row.remark }}</span>
            </el-tooltip>
            <span v-else>--</span>
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
import { inboundDoApi } from "@/api/inventory";
import { INBOUND_STATUS } from "../constants";
import { useInboundDetail } from "../composables/useInboundDetail";
import { getSpecName, getProductCode, getBarcode } from "../../utils";

export default {
  name: "InboundDetail",
  components: { InfoSelection, BaseTable },
  emits: ["ok"],
  setup(props, ctx) {
    const detailInfo = useInboundDetail();

    // 入库（待入库 -> 已完成）：成功后重拉详情（按钮随状态消失）并通知父组件刷新
    const handleInbound = () => {
      const data = detailInfo.detail.value;
      modalSure(`确认对入库单「${data.bill_no}」执行入库吗`).then(async () => {
        await inboundDoApi(data.id);
        ctx.emit("ok");
        detailInfo.open(data.id);
      });
    };

    ctx.expose({
      open: detailInfo.open
    });

    return { ...detailInfo, DRAWER_SIZE, INBOUND_STATUS, handleInbound, getSpecName, getProductCode, getBarcode };
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

.inbound-detail {
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

// 明细备注：单行截断，超出部分由 el-tooltip 展示
.goods-remark {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>

<style lang="scss">
.inbound-detail-drawer {
  .el-drawer__body {
    padding: 0;
    overflow-y: auto;
  }
}

// 明细备注气泡：允许换行，避免长备注拉成一条超宽气泡。
// 宽度不在此声明——styles/element-ui.scss 已给 .el-tooltip__popper 统一限宽，直接沿用
.goods-remark-tooltip {
  white-space: normal;
  word-break: break-all;
}
</style>
