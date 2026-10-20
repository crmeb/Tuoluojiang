<template>
  <div class="divBox">
    <el-card class="normal-page el-card-flex" body-style="padding: 0">
      <applicationList :title="title" :params="{ app_type: appType }" show-create @create="handleAdd" />
    </el-card>

    <!-- 应用创建，创建后进设置页继续编排 -->
    <applicationDialog ref="applicationDialogRef" :app-type="appType" @created="handleCreated" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import router from '@/router'
import applicationList from './applicationList.vue'
import applicationDialog from './applicationDialog.vue'
import { APP_TYPE } from '../setting/constants'
import { getApplicationSettingPath } from '../utils/application'

const props = withDefaults(defineProps<{
  appType?: number
  title?: string
}>(), {
  appType: APP_TYPE.INTERNAL,
  title: '应用列表'
})

const applicationDialogRef = ref<InstanceType<typeof applicationDialog> | null>(null)

const handleAdd = () => {
  applicationDialogRef.value?.openBox()
}

const handleCreated = (id: number) => {
  router.push({ path: getApplicationSettingPath(props.appType), query: { id: String(id) } })
}
</script>
