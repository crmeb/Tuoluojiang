<template>
  <div class="divBox">
    <el-card class="normal-page el-card-flex" body-style="padding: 0">
      <applicationList show-create @create="handleAdd" />
    </el-card>

    <!-- 应用创建，创建后进设置页继续编排 -->
    <applicationDialog ref="applicationDialogRef" @created="handleCreated" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import router from '@/router'
import { roterPre } from '@/settings'
import applicationList from './components/applicationList.vue'
import applicationDialog from './components/applicationDialog.vue'

const applicationDialogRef = ref<any>(null)

const handleAdd = () => {
  applicationDialogRef.value.openBox()
}

const handleCreated = (id: number) => {
  router.push(`${roterPre}/chat/setting?id=${id}`)
}
</script>
