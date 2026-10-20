<!-- 自动建群配置新增 / 编辑页面（独立路由，编辑态由 query.id 驱动） -->
<template>
  <BaseContainer class="group-invote-config-form none-padding">
    <!-- 顶部：返回 + 标题 -->
    <FormPageHeader :title="title" />

    <!-- 表单内容（可滚动区域） -->
    <div class="group-invote-config-form__body" v-loading="detailLoading">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="140px">
        <div class="section-title">邀请加群设置</div>

        <!-- 邀请加入的群聊：表格随表单域正文排布，左侧与其他表单项内容对齐 -->
        <el-form-item label="邀请加入的群聊：" prop="chat_ids">
          <div class="group-chat-actions">
            <el-button type="primary" @click="openGroupChat">选择已有群聊</el-button>
          </div>
          <el-table :data="groupList" style="width: 100%">
            <el-table-column label="群聊名称" prop="name" min-width="120" :show-overflow-tooltip="true">
              <template #default="scope">
                {{ scope.row.name || '--' }}
              </template>
            </el-table-column>
            <el-table-column label="群主" min-width="100">
              <template #default="scope">
                {{ (scope.row.admin && scope.row.admin.name) || '--' }}
              </template>
            </el-table-column>
            <el-table-column label="群人数" prop="member_num" min-width="80" />
            <el-table-column label="退群人数" prop="retreat_group_num" min-width="80" />
            <el-table-column label="创建时间" prop="group_create_time" min-width="160" />
            <el-table-column label="操作" fixed="right" width="80">
              <template #default="scope">
                <el-button type="primary" link @click="removeGroup(scope.$index)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="tips mt10">最多可选择{{ MAX_GROUP_CHAT }}个群聊</div>
        </el-form-item>

        <el-form-item label="入群设置：">
          <el-checkbox v-model="form.auto_create_room" :true-value="1" :false-value="0">
            群人数达上限时，自动创建新群
          </el-checkbox>
          <div class="tips">选择群聊群人数达到上限后，将以原群主身份自动创建新群</div>
        </el-form-item>

        <!-- 群名与序号只服务于自动建群，未开启自动建群时无意义，一并隐藏（隐藏后不参与校验） -->
        <template v-if="form.auto_create_room === 1">
          <el-form-item label="群聊名称：" prop="room_base_name">
            <el-input v-model="form.room_base_name" class="form-control" placeholder="请输入群名称，例如：SVIP会员群" />
            <div class="tips mt10">群聊名称设置，例如：SVIP会员群</div>
          </el-form-item>

          <el-form-item label="群聊序号：">
            <div class="flex lh-center">
              <el-checkbox v-model="form.room_base_id" :true-value="1" :false-value="0">群序号</el-checkbox>
              <div class="ml14" v-if="form.room_base_id >= 1">
                将从序号
                <el-input style="width: 100px" type="number" :min="1" v-model="form.room_base_id" />
                开始生成
              </div>
            </div>
            <div class="tips mt10">自动创建的群聊将按照序号依次生成，如“SVIP会员群1”。</div>
          </el-form-item>
        </template>
      </el-form>
    </div>

    <!-- 底部操作栏：取消（返回） + 保存 -->
    <FormPageFooter :loading="submitting" @save="submit" />

    <!-- 群聊选择组件 -->
    <groupChat ref="groupChatRef" :max="MAX_GROUP_CHAT" @selectGroups="handleSelectGroups" />
  </BaseContainer>
</template>

<script>
import { ref, onMounted, getCurrentInstance } from 'vue'
import BaseContainer from '@/components/BaseContainer/index.vue'
import FormPageHeader from '@/components/FormPageHeader/index.vue'
import FormPageFooter from '@/components/FormPageFooter/index.vue'
import groupChat from './components/groupChat.vue'
import { useGroupInvoteConfigForm, MAX_GROUP_CHAT } from './composables/useGroupInvoteConfigForm.ts'

export default {
  name: 'GroupInvoteConfigForm',
  components: { BaseContainer, FormPageHeader, FormPageFooter, groupChat },
  setup() {
    const { proxy } = getCurrentInstance()
    const groupInvoteConfigForm = useGroupInvoteConfigForm()

    const groupChatRef = ref(null)

    onMounted(() => {
      groupInvoteConfigForm.loadDetail()
    })

    // 打开群聊选择弹窗，传入已选群聊用于回显勾选
    const openGroupChat = () => {
      if (!groupChatRef.value) {
        proxy.$message.error('群聊选择组件加载失败')
        return
      }
      groupChatRef.value.openBox('', 2, groupInvoteConfigForm.groupList.value)
    }

    return {
      MAX_GROUP_CHAT,
      groupChatRef,
      openGroupChat,
      ...groupInvoteConfigForm
    }
  }
}
</script>

<style scoped lang="scss">
.group-invote-config-form {
  &__body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 20px;
    // 按 element-ui 的块布局：说明文字位于控件下方
    :deep(.el-form-item__content) {
      display: block;
    }
  }
}

.form-control {
  width: 460px;
}

.section-title {
  font-family: PingFang SC, sans-serif;
  font-weight: 600;
  font-size: 14px;
  color: #333;
  margin: 0 0 20px 9px;
  position: relative;

  &:before {
    content: '';
    background-color: #1890ff;
    width: 3px;
    height: 14px;
    position: absolute;
    left: -9px;
    top: 50%;
    transform: translateY(-50%);
  }
}

// 操作按钮右对齐于表单域正文顶部，表格与正文同宽
.group-chat-actions {
  margin-bottom: 10px;
}

.tips {
  line-height: 14px;
  font-family: PingFang SC, sans-serif;
  font-size: 12px;
  color: #909399;
}

.flex {
  display: flex;
}

.lh-center {
  align-items: center;
}
</style>
