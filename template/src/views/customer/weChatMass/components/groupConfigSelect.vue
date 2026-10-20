<!-- 加群配置选择弹窗：渠道活码表单中选用已维护好的加群配置（单选） -->
<template>
  <el-dialog title="选择加群配置" v-model="visible" :width="DIALOG_SIZE.XL" class="group-config-select-dialog"
    :close-on-click-modal="false">
    <div class="group-config-select">
      <div class="group-config-select__search">
        <el-input v-model="searchForm.name" placeholder="请输入群聊名称" clearable style="width: 250px"
          @change="handleSearch(searchForm)" />
      </div>

      <BaseTable :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total" v-model:page="page"
        v-model:page-size="pageSize">
        <!-- 单选列：以配置 ID 作为选中标识，翻页 / 搜索后仍能保持勾选 -->
        <template #radio="{ row }">
          <el-radio v-model="selectedId" :label="row.id" :value="row.id" @change="handleSelect(row)">
            <span></span>
          </el-radio>
        </template>

        <!-- 二维码：点击缩略图调起大图预览，预览层由 el-image 挂到 body，不受弹窗层级影响 -->
        <template #qr_code="{ row }">
          <div class="qr-code-cell">
            <el-image v-if="row.qr_code" :src="row.qr_code" :preview-src-list="[row.qr_code]" preview-teleported fit="contain"
              class="qr-code-img" />
            <span v-else>--</span>
          </div>
        </template>

        <!-- 群聊列表：展示配置下已选群聊的群名，每个群一个标签（与加群配置列表一致） -->
        <template #group_chat="{ row }">
          <div v-if="row.group_chat && row.group_chat.length" class="tag-list">
            <el-tag v-for="chat in row.group_chat" :key="chat.chat_id">
              {{ chat.name || '--' }}
            </el-tag>
          </div>
          <span v-else>--</span>
        </template>

        <!-- 名称：配置名称（name）目前多为空，回退到自动建群群名前缀 -->
        <template #name="{ row }">
          <span>{{ row.room_base_name || '--' }}</span>
        </template>

        <template #chat_count="{ row }">
          <span>{{ (row.chat_ids || []).length }}</span>
        </template>
      </BaseTable>
    </div>

    <template #footer>
      <span>
        <el-button @click="visible = false">取 消</el-button>
        <el-button type="primary" @click="handleConfirm">确 定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { ref, getCurrentInstance } from 'vue'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import BaseTable from '@/components/BaseTable/index.vue'
import { useTable } from '@/composables/useTable.ts'
import { groupInvoteConfigListApi } from '@/api/weCom.ts'

export default {
  name: 'GroupConfigSelect',
  components: { BaseTable },
  emits: ['select'],
  setup(props, { emit, expose }) {
    const { proxy } = getCurrentInstance()

    const visible = ref(false)
    // 选中项：id 驱动 radio 勾选，row 保留完整配置供父级展示名称
    const selectedId = ref(null)
    const selectedRow = ref(null)

    const tableColumns = [
      { field: 'radio', name: '', width: 50, slot: 'radio' },
      { field: 'qr_code', name: '二维码', width: 100, slot: 'qr_code' },
      { field: 'group_chat', name: '群聊列表', minWidth: 200, slot: 'group_chat' },
      { field: 'name', name: '新群群聊名称', minWidth: 160, slot: 'name' },
      { field: 'chat_ids', name: '群聊数量', minWidth: 90, slot: 'chat_count' },
      { field: 'created_at', name: '创建时间', minWidth: 160 }
    ]

    // 弹窗内容懒渲染，打开时才拉取列表
    const table = useTable(groupInvoteConfigListApi, {
      generateSearchForm: () => ({ name: '' }),
      immediate: false
    })

    const handleSelect = (row) => {
      selectedRow.value = row
    }

    // 列表数据只在首次打开时拉取，之后复用已有数据，避免每次打开都重复请求
    const loaded = ref(false)

    // current：父级已选中的配置，用于打开时回显；
    // 选中态每次打开都按 current 重置，关闭时不清空，避免关闭动画结束后的清理覆盖掉新的回显
    const openBox = (current) => {
      selectedId.value = current?.id ?? null
      selectedRow.value = current || null
      visible.value = true
      if (loaded.value) return
      loaded.value = true
      table.fetchData()
    }

    const handleConfirm = () => {
      if (!selectedRow.value) {
        proxy.$message.warning('请选择加群配置')
        return
      }
      // 回显传入的配置来自活码详情，不含 group_chat 群聊明细；命中当前页列表行时用完整行回传，
      // 否则父级按群聊名称展示会取空
      const row = table.tableData.value.find((item) => item.id === selectedId.value)
      emit('select', { ...(row || selectedRow.value) })
      visible.value = false
    }

    expose({ openBox })

    return {
      ...table,

      DIALOG_SIZE,
      visible,
      selectedId,
      tableColumns,

      handleSelect,
      openBox,
      handleConfirm
    }
  }
}
</script>

<style scoped lang="scss">
.group-config-select {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-flow: column nowrap;

  &__search {
    margin-bottom: 10px;
  }

  // 单选列只保留圆点，去掉 el-radio 自带的 label 占位
  :deep(.el-radio__label) {
    padding-left: 0;
  }
}

.qr-code-cell {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

// 尺寸约束在 el-image 根节点上，内部图片由 fit="contain" 完整缩放；
// 全局 styles.scss 的 `table .el-image` 用 !important 把表格内图片压到 36px，此处需同样以 !important 覆盖回二维码尺寸
.qr-code-img {
  display: block !important;
  width: 60px !important;
  height: 60px !important;
}

// 群聊标签换行排布，行高较大的单元格由 el-table 自适应
.tag-list {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
}
</style>

<style lang="scss">
.group-config-select-dialog {
  .el-dialog__body {
    display: flex;
    height: 50vh;
  }
}
</style>
