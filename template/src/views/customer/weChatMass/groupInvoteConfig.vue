<!-- 自动建群配置列表：从渠道活码「邀请加群设置」拆分出的独立配置，供活码表单选用 -->
<template>
  <BaseContainer>
    <oaFromBox :search="searchFields" title="自动建群配置" :rightAreaVisible="false" btnText="新增配置" :total="total"
      @confirmData="handleSearch" @addDataFn="handleAdd" />

    <BaseTable class="mt-10" :table-columns="tableColumns" :data="tableData" :loading="isLoading" :total="total"
      v-model:page="page" v-model:page-size="pageSize">

      <!-- 二维码：点击缩略图调起大图预览 -->
      <template #qr_code="{ row }">
        <div class="qr-code-cell">
          <el-image v-if="row.qr_code" :src="row.qr_code" :preview-src-list="[row.qr_code]" preview-teleported fit="contain"
            class="qr-code-img" />
          <span v-else>--</span>
        </div>
      </template>

      <!-- 群聊列表：展示配置下已选群聊的群名，每个群一个标签 -->
      <template #group_chat="{ row }">
        <div v-if="row.group_chat && row.group_chat.length" class="tag-list">
          <el-tag v-for="chat in row.group_chat" :key="chat.chat_id">
            {{ chat.name || '--' }}
          </el-tag>
        </div>
        <span v-else>--</span>
      </template>

      <!-- 新群群聊名称：自动建群时使用的群名前缀，未开启自动建群时可能为空 -->
      <template #room_base_name="{ row }">
        <span>{{ row.room_base_name || '--' }}</span>
      </template>

      <!-- 群聊数量：接口返回已选群聊 ID 数组，数量由其长度得出 -->
      <template #chat_count="{ row }">
        <span>{{ (row.chat_ids || []).length }}</span>
      </template>

      <!-- 自动建群：接口数值字段返回字符串（"0" / "1"），需转数字判定，否则 "0" 会被当成真值 -->
      <template #auto_create_room="{ row }">
        <span>{{ Number(row.auto_create_room) ? '开启' : '关闭' }}</span>
      </template>

      <!-- 群聊序号：0 表示未开启序号 -->
      <template #room_base_id="{ row }">
        <span>{{ Number(row.room_base_id) ? `从 ${Number(row.room_base_id)} 开始` : '不启用' }}</span>
      </template>

      <!-- 操作列 -->
      <template #action="{ row }">
        <el-button type="primary" link @click="handlePreviewDownload(row)">二维码下载</el-button>
        <el-divider direction="vertical" />
        <el-button type="primary" link @click="handleToEditPage(row)">编辑</el-button>
        <el-divider direction="vertical" />
        <el-button type="primary" link @click="handleDelete(row)">删除</el-button>
      </template>
    </BaseTable>

    <!-- 群活码预览下载 -->
    <channelCodePreview ref="previewRef" />
  </BaseContainer>
</template>

<script>
import { ref, defineAsyncComponent } from 'vue'
import BaseContainer from '@/components/BaseContainer/index.vue'
import BaseTable from '@/components/BaseTable/index.vue'
import oaFromBox from '@/components/common/oaFromBox.vue'
import { useGroupInvoteConfig } from './composables/useGroupInvoteConfig.ts'

export default {
  name: 'groupInvoteConfig',
  components: {
    BaseContainer,
    BaseTable,
    oaFromBox,
    channelCodePreview: defineAsyncComponent(() => import('./components/channelCodePreview'))
  },
  setup() {
    const groupInvoteConfig = useGroupInvoteConfig()

    const previewRef = ref()

    // 复用渠道活码预览弹窗，文案与下载文件名切换为群活码维度（列表 name 恒为空，用群聊名称兜底）
    const handlePreviewDownload = (row) => {
      previewRef.value.openBox(row, {
        title: '群活码',
        fileName: row.room_base_name || row.group_name || '群活码'
      })
    }

    return {
      ...groupInvoteConfig,

      previewRef,
      handlePreviewDownload
    }
  }
}
</script>

<style scoped lang="scss">
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
