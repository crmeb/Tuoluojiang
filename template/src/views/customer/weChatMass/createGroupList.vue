<!-- 自动拉群-渠道活码列表 -->
<template>
  <div>
    <BaseContainer class="none-padding channel-code-page">
      <!-- 左侧活码分组管理 -->
      <channelCodeGroupSide @change="handleGroupChange" />

      <div class="channel-code-main">
        <oaFromBox
          :search="searchFields"
          title="渠道活码列表"
          :total="total"
          :treeData="treeData"
          :isViewSearch="false"
          :dropdownList="dropdownList"
          ref="fromBox"
          btnText="新增活码"
          @addDataFn="handleAdd"
          @treeChange="handleStatusChange"
          @confirmData="handleSearch"
          @dropdownFn="handleDropdown"
        >
          <template #title>
            <el-popover placement="bottom-start" popper-class="channel-code-tip-popover" trigger="hover" width="680">
              <div class="channel-code-tip">
                <p v-for="item in channelCodeTips" :key="item">{{ item }}</p>
              </div>
              <template #reference>
                <i class="el-icon-info channel-code-tip-icon"></i>
              </template>
            </el-popover>
          </template>
        </oaFromBox>

        <BaseTable
          ref="tableRef"
          class="mt-10"
          selection
          row-key="id"
          :table-columns="tableColumns"
          :data="tableData"
          :loading="isLoading"
          :total="total"
          :page.sync="page"
          :page-size.sync="pageSize"
          @selection-change="handleSelectionChange"
        >
          <!-- 渠道活码图片，点击预览下载 -->
          <template #qr_code="{ row }">
            <div class="qr-code-cell">
              <div class="cursor-pointer" v-if="row.qr_code" @click="handlePreviewDownload(row)">
                <img :src="row.qr_code" alt="" class="qr-code-img" />
              </div>
              <span v-else>--</span>
            </div>
          </template>

          <!-- 使用成员：超 3 人截断并悬浮展示全量 -->
          <template #users="{ row }">
            <template v-if="row.users && row.users.length">
              <el-tooltip :content="formatAllNames(row.users)" placement="top" :disabled="row.users.length <= 3">
                <span class="ellipsis-text">{{ formatDisplayNames(row.users) }}</span>
              </el-tooltip>
            </template>
            <span v-else>--</span>
          </template>

          <!-- 标签：展示前 3 个，悬浮展示全量 -->
          <template #tags="{ row }">
            <template v-if="row.tags && row.tags.length">
              <el-tooltip :content="formatAllNames(row.tags)" placement="top" :disabled="row.tags.length <= 3">
                <div class="tag-list">
                  <el-tag v-for="item in getDisplayList(row.tags)" :key="item.id" size="small">
                    {{ item.name }}
                  </el-tag>
                  <span v-if="row.tags.length > 3" class="tag-more">...</span>
                </div>
              </el-tooltip>
            </template>
            <span v-else>--</span>
          </template>

          <template #auto_create_room="{ row }">
            {{ row.auto_create_room === 1 ? '开启' : '关闭' }}
          </template>

          <!-- 开启状态快捷开关 -->
          <template #status="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              active-text="开启"
              inactive-text="关闭"
              @change="handleSwitchStatus(row)"
            />
          </template>

          <!-- 群聊：超 3 个截断并悬浮展示全量 -->
          <template #group_names="{ row }">
            <template v-if="row.group_names && row.group_names.length">
              <el-tooltip
                :content="formatAllNames(row.group_names)"
                placement="top"
                :disabled="row.group_names.length <= 3"
              >
                <span class="ellipsis-text">{{ formatDisplayNames(row.group_names) }}</span>
              </el-tooltip>
            </template>
            <span v-else>--</span>
          </template>

          <!-- 创建人：头像 + 姓名 -->
          <template #creator="{ row }">
            <div class="flex items-center">
              <img :src="row.creator && row.creator.avatar" alt="" class="creator-avatar" />
              <span>{{ row.creator && row.creator.name }}</span>
            </div>
          </template>

          <!-- 操作列 -->
          <template #action="{ row }">
            <el-button type="text" size="mini" @click="handleView(row)">查看</el-button>
            <el-divider direction="vertical" />
            <el-button type="text" size="mini" @click="handleStatistics(row)">统计分析</el-button>
            <el-divider direction="vertical" />
            <el-button type="text" size="mini" @click="handlePreviewDownload(row)">预览下载</el-button>
            <el-divider direction="vertical" />
            <el-button type="text" size="mini" @click="handleEdit(row)">编辑</el-button>
            <el-divider v-if="row.status != 2" direction="vertical" />
            <el-button v-if="row.status != 2" type="text" size="mini" @click="handleDelete(row)">删除</el-button>
          </template>
        </BaseTable>
      </div>
    </BaseContainer>

    <!-- 详情 -->
    <groupDetails ref="detailsRef" />
    <!-- 渠道活码预览下载 -->
    <channelCodePreview ref="previewRef" />
    <!-- 批量移动分组 -->
    <batchMoveGroupDialog ref="batchMoveRef" @ok="handleBatchMoved" />
  </div>
</template>

<script>
import { ref, getCurrentInstance } from 'vue'
import { useRouter } from 'vue-router/composables'
import { roterPre } from '@/settings'
import BaseContainer from '@/components/BaseContainer/index.vue'
import BaseTable from '@/components/BaseTable/index.vue'
import oaFromBox from '@/components/common/oaFromBox.vue'
import channelCodeGroupSide from './components/channelCodeGroupSide.vue'
import batchMoveGroupDialog from './components/batchMoveGroupDialog.vue'
import { useChannelCodeList } from './composables/useChannelCodeList'

export default {
  name: 'CreateGroupList',
  components: {
    BaseContainer,
    BaseTable,
    oaFromBox,
    channelCodeGroupSide,
    batchMoveGroupDialog,
    channelCodePreview: () => import('./components/channelCodePreview'),
    groupDetails: () => import('./components/groupDetails')
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const router = useRouter()
    const listInfo = useChannelCodeList()

    const detailsRef = ref()
    const previewRef = ref()
    const tableRef = ref()
    const batchMoveRef = ref()
    const channelCodeTips = [
      '渠道活码，不仅可以完全客服活码的功能，还能够实现不需要员工手工拉客户进群，而且完全打破企业微信官方只能选择5个群聊的局限，流程如下：',
      '1. 在本系统添加多个客服，添加完成后会生成一个渠道活码，一个渠道活码对应一个或多个使用成员（相当于客服/销售）。',
      '2. 当客户扫该渠道活码之后就会先添加使用成员，同时客户会收到管理员提前设置的添加客服欢迎语（支持文字、图片、视频、文件、网页、小程序）。',
      '3. 若改渠道活码开启了邀请加群，则会给客户发送的消息中包括入群二维码，然后客户扫码进群。',
      '4. 当第一个群达到人数上限（最多200人）后，客户扫码添加使用成员后，推送第二群的入群二维码，客户扫码进入第二个群聊，依次往后进行。',
      '5. 勾选自动建群，当已有群聊均已满员后，会根据建群规则自动创建新的群聊，实现无需手动建群的需求。',
      '在整个流程中，即使在客户不入群或当客户退群时，该客户其实就已添加过客服，可以保持单独联系，完善客户画像，提供一对一的精准服务。',
      '自动拉群业务场景说明，即客户通过群渠道活码添加公司员工（相当于客服/销售）之后，自动给该客户发欢迎语、加群邀请二维码、以及自动打标签；若群聊人数达到上限之后，系统会根据设置的建群规则自动建群。'
    ]

    // 头部「更多」下拉菜单
    const dropdownList = [{ label: '批量移动分组', value: 1 }]
    const selectedIds = ref([])

    const handleSelectionChange = (rows) => {
      selectedIds.value = rows.map((row) => row.id)
    }

    const handleDropdown = (item) => {
      if (item.value === 1) {
        if (!selectedIds.value.length) {
          proxy.$message.warning('请先选择要移动分组的渠道活码')
          return
        }
        batchMoveRef.value.openBox(selectedIds.value)
      }
    }

    const handleBatchMoved = () => {
      tableRef.value && tableRef.value.clearSelection()
      listInfo.fetchData()
    }

    // 新增 / 编辑跳转独立表单页（编辑携带 query.id）
    const createGroupPath = roterPre + '/customer/weChatMass/createGroup'

    // 新增时若左侧选中的不是「全部」，把当前分组带给表单页回显
    const handleAdd = () => {
      const currentGroupId = listInfo.groupId.value
      router.push({
        path: createGroupPath,
        query: currentGroupId ? { group_id: currentGroupId } : undefined
      })
    }

    const handleEdit = (row) => {
      router.push({ path: createGroupPath, query: { id: row.id } })
    }

    const handleView = (row) => {
      detailsRef.value.openBox(row.id, '0')
    }

    // 跳转渠道码数据分析页，携带渠道码 ID 与名称（名称用于目标页下拉回显兜底）
    const handleStatistics = (row) => {
      router.push({
        path: roterPre + '/customer/weChatMass/groupCodeStatistics',
        query: { id: row.id, name: row.name }
      })
    }

    const handlePreviewDownload = (row) => {
      previewRef.value.openBox(row)
    }

    // 名称类集合展示：前 3 个 + 悬浮全量
    const getDisplayList = (list, limit = 3) => {
      if (!list || !list.length) return []
      return list.slice(0, limit)
    }

    const formatAllNames = (list) => {
      if (!list || !list.length) return '--'
      return list
        .map((item) => (typeof item === 'string' ? item : item.name))
        .filter(Boolean)
        .join('、')
    }

    const formatDisplayNames = (list) => {
      if (!list || !list.length) return '--'
      const names = list.map((item) => (typeof item === 'string' ? item : item.name)).filter(Boolean)
      if (names.length <= 3) return names.join('、')
      return `${names.slice(0, 3).join('、')}...`
    }

    return {
      ...listInfo,

      detailsRef,
      previewRef,
      tableRef,
      batchMoveRef,
      channelCodeTips,
      dropdownList,

      handleAdd,
      handleEdit,
      handleView,
      handleStatistics,
      handlePreviewDownload,
      handleSelectionChange,
      handleDropdown,
      handleBatchMoved,

      getDisplayList,
      formatAllNames,
      formatDisplayNames
    }
  }
}
</script>

<style lang="scss" scoped>
// 左右布局：左侧分组侧栏 + 右侧列表区
.channel-code-page {
  flex-direction: row;
}

.channel-code-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 20px 20px 15px;
}

.channel-code-tip {
  font-size: 13px;
  line-height: 22px;
  color: #606266;

  p {
    margin: 0 0 8px;

    &:last-child {
      margin-bottom: 0;
    }
  }
}

.channel-code-tip-icon {
  margin-left: 4px;
  color: #909399;
}

.qr-code-cell {
  display: flex;
  // 与表头文字保持左对齐
  justify-content: flex-start;
}

.qr-code-img {
  display: block;
  width: 60px;
  height: 60px;
  object-fit: contain;
}

.ellipsis-text {
  cursor: default;
}

.tag-list {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  cursor: default;

  .tag-more {
    color: #909399;
    font-size: 12px;
    line-height: 24px;
  }
}

.creator-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 4px;
  vertical-align: bottom;
}
</style>

<style lang="scss">
.channel-code-tip-popover {
  background: #ffffff;
  padding: 12px 14px;
}
</style>
