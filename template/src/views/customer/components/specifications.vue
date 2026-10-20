<template>
  <div>
    <el-dialog
      title="选择产品弹窗"
      v-model="dialogTableVisible"
      :before-close="handleClose"
      :append-to-body="true"
      :width="DIALOG_SIZE.XL"
    >
      <div class="flex mb10">
        <el-cascader
          v-model="where.pid"
          :options="options"
          @change="getList(1)"
          @keydown="getList(1)"
          :props="{ label: 'name', value: 'id', emitPath: false }"
          class="mr10"
          placeholder="产品分类"
          style="width: 200px"
          clearable
        ></el-cascader>
        <el-input
          placeholder="请输入名称"
          v-model="where.name"
          style="width: 200px"
          class="mr10"
          @change="getList(1)"
          @keydown="getList(1)"
          clearable
        ></el-input>
        <el-input
          placeholder="请输入规格值"
          v-model="where.attr"
          @change="getList(1)"
          @keydown="getList(1)"
          style="width: 200px"
          clearable
        ></el-input>
      </div>

      <el-table
        :data="gridData"
        height="400px"
        row-key="unique"
        ref="multipleTable"
        class="select-table"
        :tree-props="{ children: 'children' }"
        @row-click="handleRowClick"
      >
        <el-table-column property="date" label="产品信息">
          <template #header>
            <span class="iconfont icona-tongyongweigouxuanbiankuang ml40" v-if="!allId" @click="selectAll()"></span>

            <span class="iconfont icontongyonggouxuan-01 ml40" v-if="allId" @click="selectAll()"></span>
            产品信息
          </template>
          <template #default="scope">
            <div class="flex">
              <div>
                <span
                  v-if="getInclude4(scope.row)"
                  class="iconfont icontongyonggouxuan-01"
                  :class="getCheckboxClass(scope.row)"
                  @click.stop="selectFn(true, scope.row)"
                ></span>
                <span
                  class="iconfont icona-tongyongweigouxuanbiankuang"
                  v-else
                  :class="getCheckboxClass(scope.row)"
                  @click.stop="selectFn(false, scope.row)"
                ></span>
              </div>

              <img
                v-if="scope.row.name && scope.row.attr_value.length == 1"
                :src="scope.row.attr_value[0].image"
                alt=""
                class="img"
              />
              <span v-if="scope.row.name" style="width: 360px" class="over-text2">
                {{ scope.row.name }}
              </span>
              <img v-if="scope.row.image" :src="scope.row.image" alt="" class="img" />
              <span v-if="scope.row.sku">{{ scope.row.sku }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="分类" width="110">
          <template #default="scope">
            {{ getCate(scope.row) || '--' }}
          </template>
        </el-table-column>
        <el-table-column label="编码" width="120">
          <template #default="scope">
            {{ getSkuField(scope.row, 'bar_code') || '--' }}
          </template>
        </el-table-column>
        <el-table-column label="条码" width="130">
          <template #default="scope">
            {{ getSkuField(scope.row, 'product_bar_code') || '--' }}
          </template>
        </el-table-column>
        <!-- 剩余库存：仅在父组件传入仓库 id（出入库等按仓库过滤库存的场景）时展示 -->
        <el-table-column v-if="warehouseId" label="剩余库存" width="100">
          <template #default="scope">
            {{ getStock(scope.row) }}
          </template>
        </el-table-column>
        <!-- 不设 prop：多规格父行插槽为空时，Element Plus 会回退显示 prop 字段 -->
        <el-table-column label="产品售价" width="120">
          <template #default="scope">
            <div v-if="scope.row.attr_value && scope.row.attr_value.length == 1" class="ml10">
              {{ scope.row.attr_value[0].price || '0.00' }}
            </div>
            <div v-if="!scope.row.attr_value" class="ml10">
              {{ scope.row.price || '0.00' }}
            </div>
          </template>
        </el-table-column>
      </el-table>
      <div class="page">
        <el-pagination
          :current-page="where.page"
          :page-size="where.limit"
          :total="total"
          layout="total,prev, pager, next"
          @current-change="pageChange"
        />
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="handleClose()">取消</el-button>
          <el-button type="primary" @click="submit">确定</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>
<script>
import { getProductsAttrsApi, productCateListApi } from '@/api/client.ts'
import { DIALOG_SIZE } from '@/constants/popupSize.ts';
export default {
  emits: ['getselectList'],
  props: {
    // 仓库 id：出入库等场景传入，随列表接口透传，按仓库维度过滤库存
    warehouseId: {
      type: [Number, String],
      default: ''
    }
  },
  data() {
    return {
      DIALOG_SIZE,
      dialogTableVisible: false,
      where: {
        page: 1,
        limit: 10,
        pid: '',
        name: '',
        attr: ''
      },
      ids: [],
      allId: false,
      selectedList: [],
      options: [],
      list: [],
      total: 0,
      gridData: []
    }
  },
  watch: {
    // deep: 1 对齐 Vue 2 语义：push 等原地修改也要触发，以便重算多规格父行的勾选
    selectedList: {
      handler(val) {
        this.setChecked(val)
      },
      deep: 1
    },
    // 仓库变化后商品库存随之变化，重置到第一页重新请求
    warehouseId() {
      this.getList(1)
    }
  },

  mounted() {
    this.getOptions()
    this.getList()
  },
  methods: {
    getList(type) {
      if (type == 1) {
        this.where.page = 1
        this.allId = false
      }
      // filter_stock 默认 0 不过滤，仅传入仓库 id 时按仓库过滤库存
      const params = { ...this.where, filter_stock: 0 }
      if (this.warehouseId) {
        params.filter_stock = 1
        params.warehouse_id = this.warehouseId
      }
      getProductsAttrsApi(params).then((res) => {
        this.gridData = res.data.list
        this.gridData.forEach((item) => {
          item.unique = item.id + ''
          // 仅多规格商品作为树节点展开；element-ui 靠 hasChildren 隐藏单规格箭头，Element Plus 非 lazy 时不认该字段
          if (item.attr_value.length > 1) {
            item.children = item.attr_value
          }
          // 将父级商品名 / 单位挂到每个规格项上，方便选中后回填展示（增量字段，不影响原有消费方）
          ;(item.attr_value || []).forEach((el) => {
            el.product_name = item.name
            el.unit_name = item.unit_name
          })
        })
        this.total = res.data.count
      })
    },
    // 勾选全部
    selectAll() {
      this.allId = !this.allId
      this.ids = []
      if (this.allId) {
        this.gridData.map((item) => {
          this.ids.push(item.unique)
          item.attr_value.map((el) => {
            this.ids.push(el.unique)
            this.selectedList.push(el)
          })
        })
      } else {
        this.ids = []
        this.selectedList = []
      }
    },
    // 点击整行等价于点击该行勾选框；树形展开箭头在 element 内部已 stopPropagation，不会触发本回调
    handleRowClick(row) {
      this.selectFn(this.getInclude4(row), row)
    },
    getInclude4(row) {
      if (!row.attr_value) {
        return this.ids.includes(row.unique)
      }
      const targetId =
        row.attr_value.length === 1
          ? row.attr_value[0].unique // 单规格
          : row.unique // 多规格

      return this.ids.includes(targetId)
    },

    // 勾选和反选
    selectFn(type, row) {
      // 1. 提取当前行相关的所有唯一标识（统一处理不同规格）
      const rowIds = this.getRowIds(row)

      if (type) {
        // 2. 取消选中：从ids和selectedList中移除相关项
        this.ids = this.ids.filter((id) => !rowIds.includes(id))
        this.selectedList = this.selectedList.filter((item) => !rowIds.includes(item.unique))
      } else {
        // 3. 选中：添加相关项到ids和selectedList
        this.ids.push(...rowIds)
        this.selectedList.push(...this.getItemsToAdd(row))
      }
    },

    // 辅助函数：获取当前行所有需要处理的唯一标识
    getRowIds(row) {
      // 基础ID（当前行自身的unique）
      const ids = [row.unique]
      // 如有子项，添加子项的unique
      if (row.attr_value && row.attr_value.length) {
        row.attr_value.forEach((el) => ids.push(el.unique))
      }
      return ids
    },

    // 辅助函数：获取需要添加到selectedList的项
    getItemsToAdd(row) {
      if (!row.attr_value || !row.attr_value.length) {
        return [row]
      }

      return row.attr_value
    },
    openBox(row, data) {
      this.selectedList = JSON.parse(JSON.stringify(data))
      this.dialogTableVisible = true
    },
    getCheckboxClass(row) {
      if (!row.name) return ''
      const { attr_value = [] } = row
      return attr_value.length === 1 ? 'ml40' : attr_value.length > 1 ? 'ml18' : ''
    },
    // 分类为商品级字段，仅商品父行展示（子规格行无 name）
    getCate(row) {
      return row.name ? row.category ?? '' : ''
    },
    // 规格层字段（编码 / 条码 / 剩余库存）：单规格商品取其唯一规格，规格子行取自身，多规格父行留空
    getSkuField(row, key) {
      if (row.attr_value) {
        return row.attr_value.length === 1 ? row.attr_value[0][key] ?? '' : ''
      }
      return row[key] ?? ''
    },
    // 剩余库存展示：0 正常显示，仅多规格父行 / 字段缺失时占位
    getStock(row) {
      const stock = this.getSkuField(row, 'available_stock')
      return stock === '' || stock === null || stock === undefined ? '--' : stock
    },

    handleClose() {
      this.ids = []
      this.selectedList = []
      this.dialogTableVisible = false
    },

    setChecked(data) {
      this.ids = []
      if (data.length == 0) return
      data.map((item) => {
        if (item.unique) {
          this.ids.push(item.unique)
        }
      })
      let allId = []
      this.gridData.map((item) => {
        allId.push(item.unique)
        const idsToCheckone = item.attr_value.map((item) => item.unique)
        if (this.isArrayContained(this.ids, idsToCheckone)) {
          this.ids.push(item.unique)
        } else {
          this.ids = this.ids.filter((item) => item != item.unique)
        }
      })
      if (this.isArrayContained(this.ids, allId)) {
        this.allId = true
      } else {
        this.allId = false
      }
    },

    isArrayContained(arr1, arr2) {
      return arr2.every((element) => arr1.includes(element))
    },
    submit() {
      this.list = JSON.parse(JSON.stringify(this.selectedList))

      this.$emit('getselectList', this.selectedList, this.ids)
      this.dialogTableVisible = false
    },
    getOptions() {
      productCateListApi().then((res) => {
        this.options = res.data
      })
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getList()
    },
    pageChange(val) {
      this.where.page = val
      this.allId = false
      this.getList()
    }
  }
}
</script>
<style scoped lang="scss">
.img {
  width: 34px;
  height: 34px;
  margin-right: 8px;
}
.page {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}
.icontongyonggouxuan-01 {
  cursor: pointer;
  color: #1890ff;
  font-size: 13px;
  margin-right: 12px;
}
.icona-tongyongweigouxuanbiankuang {
  cursor: pointer;
  font-size: 13px;
  font-weight: 400;
  color: #909399;
  margin-right: 12px;
}
.pl50 {
  padding-left: 50px;
  padding-right: 40px;
}

// 第一列勾选框的统一左缩进：与树形子行的 indent(16px) + placeholder(20px) 对齐
$checkbox-indent: 36px;
// element 展开箭头的占位：width 20px + margin-right 3px
$expand-icon-space: 23px;

.ml40 {
  margin-left: $checkbox-indent !important;
}
// 多规格父行：箭头已占 $expand-icon-space，勾选框补齐剩余缩进
.ml18 {
  margin-left: $checkbox-indent - $expand-icon-space;
}
// 下方的 `td div { display: flex }` 顺带把 .cell 也变成了 flex 容器，第一列内容
// 溢出时展开箭头会被 flex-shrink 压窄（20px → 12px），多规格父行的勾选框随之左移 8px。
// 出库 / 盘点 / 调拨等页比入库页多一列「剩余库存」，第一列更窄，正是触发条件。
:deep(.el-table__expand-icon) {
  flex: none;
  // element-ui 箭头自带 3px 右边距，Element Plus 没有
  margin-right: 3px;
  // 箭头自身保持默认指针，避免整行 pointer 时误以为点箭头也是选中
  cursor: pointer;
}
// Element Plus 给顶层叶子行（单规格）也加了 20px 占位，element-ui 没有，勾选框缩进由 .ml40 负责
// 子规格行前面有 el-table__indent，占位不是首个子元素，不受影响
.select-table :deep(.cell > .el-table__placeholder:first-child) {
  display: none;
}
// 整行可点选：行内容给出可点击态，展开箭头列不参与选中
.select-table :deep(.el-table__body tr) {
  cursor: pointer;
}
// 弹框挂到 body，:deep 前缀需落在 el-table 根节点上才能命中
.select-table :deep(td.el-table__cell div) {
  display: flex;
  align-items: center;
}
</style>
