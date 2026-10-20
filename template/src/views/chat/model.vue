<template>
  <div class="divBox">
    <el-card class="normal-page el-card-flex" body-style="padding: 0">
      <div class="model-main">
        <oaFromBox
          title="全部模型"
          :total="total"
          :search="searchFields"
          :isViewSearch="false"
          :sortSearch="false"
          btnText="添加模型"
          @confirmData="handleSearch"
          @addDataFn="handleAdd"
        />

        <div class="model-list-wrap" v-loading="isLoading">
          <div ref="containerRef" class="model-list-scroll">
            <defaultPage v-if="isEmpty" :index="14" :min-height="520" />
            <div v-else class="model-list" :style="gridStyle">
              <modelCard
                v-for="item in tableData"
                :key="item.id"
                :data="item"
                :provider="providerMap[item.provider]"
                @setting="handleSetting"
                @delete="handleDelete"
              />
            </div>
          </div>
        </div>

        <div class="model-pagination el-pagination-reset">
          <el-pagination
            :current-page="page"
            :page-size="pageSize"
            :total="total"
            layout="total, prev, pager, next, jumper"
            @current-change="handlePageChange"
          />
        </div>
      </div>
    </el-card>

    <!-- 模型新增 / 设置 -->
    <modelDialog ref="modelDialog" :optionList="providerOptions" @isOk="fetchData" />
  </div>
</template>

<script>
import { getCurrentInstance } from 'vue'
import oaFromBox from '@/components/common/oaFromBox'
import defaultPage from '@/components/common/defaultPage'
import modelDialog from './components/modelDialog'
import modelCard from './components/modelCard'
import { useGridPageSize } from '@/composables/useGridPageSize.ts'
import { useModelList } from './composables/useModelList.ts'

export default {
  name: 'ChatModel',
  components: { oaFromBox, defaultPage, modelDialog, modelCard },
  setup() {
    const { proxy } = getCurrentInstance()

    // 每页条数由容器尺寸算出，首屏等测量完再拉，避免先按初值请求一次
    const modelList = useModelList({ immediate: false })

    const {
      page,
      pageSize,
      total,
      tableData,
      isLoading,
      isEmpty,
      providerOptions,
      providerMap,
      searchFields,
      fetchData,
      getProviderOptions,
      handleSearch,
      handleDelete
    } = modelList

    // 卡片按 482 × 178 排布（较设计稿多一行「模型类别」），容器放得下几行几列就取几条
    const { containerRef, gridStyle } = useGridPageSize(modelList, {
      minColumnWidth: 482,
      rowHeight: 178,
      gap: 15
    })

    const handlePageChange = (value) => {
      page.value = value
    }

    // 打开弹窗前刷新供应商 options，确保一号通 enabled 反映最新连通性
    const handleAdd = async () => {
      await getProviderOptions()
      proxy.$refs.modelDialog.openBox()
    }

    // 编辑只传 id，弹窗内走详情接口取最新配置
    const handleSetting = async (item) => {
      await getProviderOptions()
      proxy.$refs.modelDialog.openBox({ id: item.id })
    }

    return {
      page,
      pageSize,
      total,
      tableData,
      isLoading,
      isEmpty,
      providerOptions,
      providerMap,
      searchFields,
      containerRef,
      gridStyle,

      fetchData,
      handleSearch,
      handlePageChange,
      handleAdd,
      handleSetting,
      handleDelete
    }
  }
}
</script>

<style scoped lang="scss">
.model-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 20px 20px 10px;
}

// 与 .flex-layout-table 同一套思路：外层只负责占满剩余空间，内容绝对定位撑不高它，
// 否则卡片多排一行会把 .normal-page 顶高，测出的容器高度跟着变大
.model-list-wrap {
  position: relative;
  flex: 1;
  min-height: 0;
  margin-top: 20px;
}

.model-list-scroll {
  position: absolute;
  inset: 0;
  overflow-y: auto;
  // 滚动条出现 / 消失会改变可用宽度，留白常驻避免列数来回抖动
  scrollbar-gutter: stable;
}

.model-list {
  display: grid;
  // 列数与行高由 useGridPageSize 按容器尺寸下发，minmax(0, 1fr) 让整行等分排满
  grid-template-columns: repeat(var(--grid-cols, 3), minmax(0, 1fr));
  grid-auto-rows: var(--grid-row-h, 150px);
  gap: var(--grid-gap, 15px);
  align-content: start;
}

.model-pagination {
  display: flex;
  justify-content: flex-end;
}

.divBox .el-pagination {
  margin-top: 0px;
}
</style>
