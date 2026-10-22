<template>
  <div class="product-list mb20">
    <!-- 表格区域 -->
    <el-table :data="tableData" ref="table" row-key="unique" show-summary :summary-method="getSummaries"
      style="width: 100%" v-loading="isLoading">
      <!-- 操作列 -->
      <el-table-column label="操作" width="98px" v-if="type != 'edit'">
        <template #default="scope">
          <div>
            <span class="iconfont icontuodong tuozhuai"></span>
            <span class="el-icon-circle-plus-outline" @click.stop="handleAdd(scope.$index)"></span>
            <span class="el-icon-delete" v-if="scope.row.unique" @click.stop="handleDelete(scope.$index)"></span>
          </div>
        </template>
      </el-table-column>
      <!-- 产品信息列 -->
      <el-table-column label="产品信息" prop="image" width="220px">
        <template #default="scope">
          <el-tooltip class="item" effect="dark" placement="top" :disabled="!scope.row.unique">
            <template #content>
              <div style="line-height: 24px" v-if="scope.row.unique">
                {{ scope.row.name || scope.row.product_name }} | {{ scope.row.sku || '单规格' }}
              </div>
            </template>
            <div class="flex lh-center">
              <img v-if="scope.row.unique && scope.row.image" :src="scope.row.image" class="product-img"
                @click="previewPicture(scope.row)" />
              <img v-if="scope.row.unique && !scope.row.image" src="../../../assets/images/bjt.png" class="product-img"
                @click="previewPicture(scope.row)" />
              <div v-if="!scope.row.unique && type != 'edit'" class="product-box"
                @click="openBox(scope.row, scope.$index)">
                <span class="el-icon-plus" />
              </div>
              <div v-if="scope.row.unique" class="over-text1">
                {{ scope.row.name || scope.row.product_name }}
              </div>
              <div v-if="scope.row.unique" class="over-text1">
                {{ scope.row.sku || '单规格' }}
              </div>
            </div>
          </el-tooltip>
        </template>
      </el-table-column>
      <!-- 售价列 -->
      <el-table-column prop="ot_price" label="售价" width="80px"></el-table-column>
      <!-- 剩余良品列（仅按仓库出库等场景展示，可配置；未选择出库仓库时不展示） -->
      <el-table-column v-if="showStockColumn" prop="good_stock" label="剩余良品" width="90px">
        <template #default="scope">
          <span v-if="!scope.row.unique">--</span>
          <span v-else :class="{ 'stock-empty': stockLimitEnabled && !Number(scope.row.good_stock) }">
            {{ scope.row.good_stock ?? 0 }}
          </span>
        </template>
      </el-table-column>
      <!-- 成交数量列 -->
      <el-table-column prop="count" label="成交数量" width="120">
        <template #default="scope">
          <span v-if="type === 'edit'">{{ scope.row.count }}</span>
          <el-input-number v-else v-model="scope.row.count" :min="0" :max="getCountMax(scope.row)" :precision="0"
            size="small" class="priceBox" @change="handleCount(scope.row)" controls-position="right"></el-input-number>
        </template>
      </el-table-column>
      <!-- 折扣列 -->
      <el-table-column prop="discount" label="折扣(%)" width="120">
        <template #default="scope">
          <span v-if="type === 'edit'">{{ scope.row.discount }}</span>
          <el-input-number v-else v-model="scope.row.discount" :min="0" :max="100" :precision="0" size="small"
            :controls="false" class="priceBox" @change="handleDiscountChange(scope.row)"
            controls-position="right"></el-input-number>
        </template>
      </el-table-column>
      <!-- 成交单价列 -->
      <el-table-column prop="price" label="成交单价" width="120">
        <template #default="scope">
          <span v-if="type === 'edit'">{{ scope.row.price }}</span>
          <el-input-number v-else v-model="scope.row.price" :min="0" :precision="2" size="small" :controls="false"
            class="priceBox" controls-position="right" @change="handlePrice(scope.row)"></el-input-number>
        </template>
      </el-table-column>
      <!-- 成交总价列 -->
      <el-table-column prop="total_price" label="成交总价" width="120">
        <template #default="scope">
          <span v-if="type === 'edit'">{{ scope.row.total_price }}</span>
          <el-input-number v-else :controls="false" v-model="scope.row.total_price" :min="0" :precision="2"
            controls-position="right" size="small" class="priceBox"
            @change="handleTotalPrice(scope.row)"></el-input-number>
        </template>
      </el-table-column>
      <el-table-column prop="remark" label="备注" :fixed="remarkFixed ? 'right' : false" :width="remarkFixed ? 200 : undefined">
        <template #default="scope">
          <span v-if="type === 'edit'">{{ scope.row.remark || '--' }}</span>
          <el-input v-else v-model="scope.row.remark" size="small" placeholder="请输入备注"></el-input>
        </template>
      </el-table-column>
    </el-table>

    <specifications ref="specifications" @getselectList="getselectList" :warehouse-id="warehouseId"></specifications>
    <image-viewer ref="imageViewer" :src-list="srcList"></image-viewer>
  </div>
</template>

<script>
import Sortable from 'sortablejs'
import { getProductsAttrsApi } from '@/api/client'
import { generateEmptyProduct } from "./helper";

export default {
  name: 'ProductList',
  components: {
    imageViewer: () => import('@/components/common/imageViewer'),
    specifications: () => import('./specifications')
  },
  props: {
    // 父组件传递的参数
    product: {
      type: Array,
      default: () => []
    },
    type: {
      type: String,
      default: 'add'
    },
    loading: {
      type: Boolean,
      default: false
    },
    remarkFixed: { // 备注栏是否固定
      type: Boolean,
      default: false
    },
    // 是否展示「剩余良品」列（默认关闭，避免影响其他引用场景；实际展示还需已选择出库仓库）
    showStock: {
      type: Boolean,
      default: false
    },
    // 是否将「成交数量」上限限制为剩余良品数量（实际限制还需已选择出库仓库）
    limitByStock: {
      type: Boolean,
      default: false
    },
    // 出库仓库 id：用于按仓库 + 产品 sku 查询剩余良品
    warehouseId: {
      type: [Number, String],
      default: ''
    }
  },
  data() {
    return {
      tableData: this.product,
      srcList: [],
      rowIndex: -1,
      stockMap: {},
      // 已完成库存查询对应的产品集合签名；为 null 表示尚未查询完成（库存未知）
      stockKeysLoaded: null,
      // 库存查询请求序号，用于丢弃过期响应（如快速切换仓库）
      stockReqId: 0,
      stockQueryLoading: false
    }
  },
  computed: {
    isLoading() {
      return this.loading || this.stockQueryLoading;
    },
    // 剩余良品只能按「仓库 + sku」维度查询，未选择出库仓库时无库存概念
    stockEnabled() {
      return !!this.warehouseId
    },
    // 是否展示「剩余良品」列：调用方开启 + 已选择出库仓库
    showStockColumn() {
      return this.showStock && this.stockEnabled
    },
    // 是否用剩余良品限制「成交数量」：调用方开启 + 已选择出库仓库
    stockLimitEnabled() {
      return this.limitByStock && this.stockEnabled
    },
    // 已选产品 sku 的签名，用于监听产品列表增删变化后重新查询剩余良品
    stockKeys() {
      return this.tableData
        .filter((item) => item.unique)
        .map((item) => item.unique)
        .join(',')
    }
  },
  mounted() {
    setTimeout(() => {
      this.rowDrop()
    }, 500)
  },
  watch: {
    // 仅在父组件整体替换产品列表（数组引用变化）时同步到 tableData。
    // 同一数组的内容级变化（行字段修改、$set 回填 good_stock、push）触发时 val === oldVal，
    // 此时 tableData 由本组件自行维护，若重置会丢弃 getselectList 新增的行
    product(val, oldVal) {
      if (val === oldVal) return
      this.tableData = val
      this.fillProductGoodStock()
    },
    tableData: {
      handler(val) {
        // 计算产品总价
        const total = val.reduce((sum, item) => {
          if (item.unique && item.total_price) {
            return sum + Number(item.total_price)
          }
          return sum
        }, 0)
        this.$emit('productChange', total)
      },
      deep: true
    },
    // 出库仓库变化后，剩余良品随之变化，重新查询
    warehouseId: {
      handler() {
        this.loadStock()
      },
      immediate: true
    },
    // 产品列表增删后重新查询剩余良品
    stockKeys: {
      handler() {
        this.loadStock()
      },
      immediate: true
    }
  },

  methods: {
    // 数量修改
    handleCount(row) {
      row.total_price = row.price * row.count
    },
    // 单价修改
    handlePrice(row) {
      row.discount = (row.price / row.ot_price) * 100
      row.total_price = row.price * row.count
    },
    // 折扣
    handleDiscountChange(row) {
      row.price = row.ot_price * (row.discount / 100)
      row.total_price = row.price * row.count
    },
    // 修改总价
    handleTotalPrice(row) {
      row.price = row.total_price / row.count
      row.discount = (row.price / row.ot_price) * 100
    },
    handleAdd(index) {
      if (this.tableData[index].unique == '') {
        return this.$message.error('请选择和填写产品信息')
      }

      this.tableData.splice(index + 1, 0, {
        unique: '',
        image: '',
        name: '',
        sku: '',
        price: 0,
        count: 1,
        total_price: 0,
        discount: 0,
        ot_price: 0,
        is_show: true
      })
    },
    getSummaries(param) {
      const { columns, data } = param
      // 按列 property 求和，避免因新增/隐藏列导致的列索引错位
      const sumProps = ['count', 'total_price']
      const count = this.tableData.filter((item) => {
        const id = item.unique
        return id !== null && id !== undefined && id !== ''
      }).length
      return columns.map((column, index) => {
        if (index === 0) {
          return `合计(${count})`
        }
        if (!sumProps.includes(column.property)) {
          return ''
        }
        const values = data.map((item) => Number(item[column.property]))
        if (values.every((value) => isNaN(value))) {
          return ''
        }
        const sum = values.reduce((prev, curr) => (isNaN(curr) ? prev : prev + curr), 0)
        // 成交数量取整，金额类保留两位小数
        return column.property === 'count' ? sum : sum.toFixed(2)
      })
    },
    // 成交数量上限（对应下方三个分支）：
    // - 未开启库存限制（含未选择出库仓库）：不限制（Infinity）
    // - 已开启但库存未知（尚未查到 / 查询未回）：上限为 0，即未确认有货前不允许成交（保守策略）
    // - 已开启且已查到剩余良品：以剩余良品数为上限
    getCountMax(row) {
      if (!this.stockLimitEnabled) return Infinity
      if (row.good_stock === undefined || row.good_stock === null) return 0
      return Number(row.good_stock) || 0
    },
    // 按出库仓库 + 产品 sku 查询剩余良品并回填
    async loadStock() {
      if (!this.showStock && !this.limitByStock) return
      const rows = this.tableData.filter((item) => item.unique)
      // 未选择出库仓库：不展示剩余良品也不限制成交数量，
      // 同时清掉上一个仓库的查询结果，避免残留库存把新增行的数量夹为 0 或误触发提交时的库存校验
      if (!this.warehouseId) {
        this.stockReqId++
        this.stockMap = {}
        this.stockKeysLoaded = null
        rows.forEach((row) => this.$delete(row, 'good_stock'))
        return
      }
      // 无产品时库存视为「未知」（非无货），不做夹断，避免过早清空商机带入的数量
      if (rows.length === 0) {
        this.stockReqId++
        this.stockKeysLoaded = null
        return
      }
      // 查询期间标记为未知，避免用旧仓库/旧集合的库存夹断当前数量
      const reqId = ++this.stockReqId
      const queriedKeys = this.stockKeys
      this.stockKeysLoaded = null
      const sku_id = rows.map((row) => row.sku_id || row.id)
      this.stockQueryLoading = true;
      try {
        const res = await getProductsAttrsApi({
          warehouse_id: this.warehouseId,
          sku_id,
          filter_stock: 1
        })
        if (reqId !== this.stockReqId) return // 已有更新的查询，丢弃本次过期响应
        const stockMap = {}
        ;(res?.data?.list || []).forEach((item) => {
          ;(item.attr_value || []).forEach((av) => {
            stockMap[av.unique] = Number(av.good_stock) || 0
          })
        })
        this.stockMap = stockMap
      } catch (e) {
        if (reqId !== this.stockReqId) return
        // 查询失败：按产品规则视为无货，库存全部按 0 处理
        console.error('查询剩余良品失败', e)
        this.stockMap = {}
      } finally {
        if (reqId === this.stockReqId) {
          this.stockQueryLoading = false;
          // 无论成功或失败均视为「已查询」：未命中的 sku（含失败）按 0 计并夹断成交数量
          this.stockKeysLoaded = queriedKeys
          this.fillProductGoodStock()
        }
      }
    },
    fillProductGoodStock() {
      // 仅当当前产品集合与已完成查询的集合一致时才回填/夹断；
      // 不一致（尚未查询、正在查询、或集合已变）说明库存未知，保持数量不动
      if (this.stockKeysLoaded === null || this.stockKeys !== this.stockKeysLoaded) return
      const rows = this.tableData.filter((item) => item.unique)
      rows.forEach((row) => {
        // 已完成查询后，未命中的 sku（无货或查询失败）按 0 计
        this.applyStock(row, this.stockMap[row.unique] ?? 0)
      })
    },
    // 回填剩余良品，并在开启限制且可编辑时把超过库存的成交数量夹到库存值（含 0）
    applyStock(row, stock) {
      const validStock = Number(stock) || 0
      this.$set(row, 'good_stock', validStock)
      // 查看态（type==='edit'）成交数量为只读展示，不做夹断，避免篡改历史合同数据
      if (this.stockLimitEnabled && this.type !== 'edit') {
        if (Number(row.count) > validStock) {
          row.count = validStock
        }
        // 无条件重算总价：:max 变小时 el-input-number 会静默把 count 夹到上限（只 emit input 不 emit change），
        // 上面的比较已不成立，total_price 会停留在夹断前的值。
        // total_price 恒等于 price * count，重算幂等，不会覆盖用户手填的总价
        this.handleCount(row)
      }
    },

    //预览图片
    previewPicture(row) {
      this.srcList = [row.image]
      this.$refs.imageViewer.openImageViewer(row.image)
    },
    getselectList(val, ids) {
      const uniqueSet = new Set()
      const filteredTableData = this.tableData.filter((item) => {
        if (item.unique) {
          uniqueSet.add(item.unique)
          return true
        }
        return false
      })

      // 处理新数据：过滤已存在项并转换格式
      const newItems = val
        .filter((item) => !uniqueSet.has(item.unique))
        .map((item) => {
          // 剩余良品优先取选择弹窗返回的值：该弹窗与 loadStock 调的是同一个接口、同一个
          // warehouse_id + filter_stock=1，数据一致。此时 stockMap 尚未包含新选中的 sku
          // （loadStock 按已在表里的行查询），只读 stockMap 会拿到 undefined，导致下面的
          // count 被 getCountMax 按「库存未知」夹为 0
          const good_stock = item.good_stock ?? this.stockMap[item.unique]
          // 初始成交数量不得超过剩余良品上限，并让 total_price 与 count 保持一致：
          // 否则 el-input-number 会因 :max 静默把 count 夹为 0（只 emit input 不 emit change），
          // handleCount 不触发，total_price 停留在旧值
          const count = Math.min(1, this.getCountMax({ good_stock }))
          return {
            id: item.id,
            unique: item.unique,
            image: item.image,
            name: item.name || item.product_name,
            sku: item.sku,
            price: item.price,
            good_stock,
            count,
            discount: 100,
            total_price: item.price * count,
            ot_price: item.price,
            remark: ''
          }
        })

      // 合并数据
      filteredTableData.splice(this.rowIndex, 0, ...newItems)
      this.tableData = filteredTableData
      // this.tableData =  [...filteredTableData, ...newItems]
      this.tableData = this.tableData.filter((item, i) => ids.includes(item.unique))
      this.$emit('getProductList', this.tableData)
    },
    // 处理删除操作
    handleDelete(index) {
      const onlyOneItem = this.tableData.length === 1;
      if (onlyOneItem) {
        this.tableData.splice(index, 1, generateEmptyProduct())
      } else {
        this.tableData.splice(index, 1)
      }
    },
    openBox(row, index) {
      this.rowIndex = index
      this.$refs.specifications.openBox(row, this.tableData)
    },

    // 表格拖拽排序
    rowDrop() {
      const tbody = this.$refs.table.$el.querySelectorAll('.el-table__body-wrapper > table > tbody')[0]

      Sortable.create(tbody, {
        animation: 200,
        handle: '.icontuodong',
        onEnd: (e) => {
          // 调整数据顺序
          const movedItem = this.tableData.splice(e.oldIndex, 1)[0]
          this.tableData.splice(e.newIndex, 0, movedItem)
          this.$refs.table.doLayout()
        }
      })
    }
  }
}
</script>

<style scoped lang="scss">
.product-list {
  padding-bottom: 10px;
}

.product-box {
  cursor: pointer;
  width: 38px;
  height: 38px;
  border-radius: 8px;
  border: 1px solid #cccccc;
  background: #fbfbfb;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #909399;

  .el-icon-camera {
    font-size: 28px;
    color: #cccccc;
  }
}

.product-img {
  cursor: pointer;
  width: 38px;
  height: 38px;
  margin-right: 10px;
  float: left;
}

.product-desc {
  overflow: hidden;
}

.product-upload {
  margin-top: 10px;
}

.icontuodong {
  font-size: 14px;
  cursor: pointer;
  color: #909399;
}

.el-icon-circle-plus-outline {
  font-size: 14px;
  cursor: pointer;
  color: #909399;
  font-weight: 500;
  margin: 0 10px;
}

.el-icon-plus {
  font-size: 14px;
  cursor: pointer;
  color: #909399;
  font-weight: 500;
  margin: 0 10px;
}

.el-icon-delete {
  font-size: 14px;
  cursor: pointer;
  color: #909399;
  font-weight: 500;
}

.priceBox {
  width: 100%;
}

.stock-empty {
  color: #f56c6c;
}

::v-deep .has-gutter {
  background: #fff;
}

.tuozhuai {
  cursor: move;
  display: inline-block;
}
</style>
