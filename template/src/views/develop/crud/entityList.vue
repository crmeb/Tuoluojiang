<template>
  <div class="divBox">
    <el-card class="normal-page">
      <entityTable
        :cate_id="cateId"
        :cateItem="cateItem"
        :applicationTabData="applicationTabData"
        @goBack="goBack"
      />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getcrudCateListApi } from '@/api/develop.ts'
import { roterPre } from '@/settings.ts'
import entityTable from './components/entityTable.vue'

interface Application {
  id: number
  menu?: { path?: number[] }
  [key: string]: unknown
}

const route = useRoute()
const router = useRouter()
const cateId = computed(() => String(route.query.cate_id || ''))
const cateItem = ref<Application>({ id: 0 })
const applicationTabData = ref<Application[]>([])

onMounted(async () => {
  const { data } = await getcrudCateListApi({ page: 1, limit: 1000 })
  applicationTabData.value = data.list || []
  cateItem.value = applicationTabData.value.find((item) => String(item.id) === cateId.value) || { id: 0 }
})

function goBack() {
  router.push(`${roterPre}/develop/crud`)
}
</script>
