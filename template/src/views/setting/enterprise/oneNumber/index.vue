<template>
  <div class="divBox">
    <el-card class="normal-page">
      <oneNumber v-if="rule.length" :from-data="rule" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import oneNumber from '../setup/components/oneNumber.vue'
import { configUpdateDataApi } from '@/api/setting'

interface RuleItem {
  field: string
  value: string
  [key: string]: unknown
}

const rule = ref<RuleItem[]>([])

onMounted(async () => {
  const res = await configUpdateDataApi({ category: 'yiht_config' })
  rule.value = res?.data?.rule ?? []
})
</script>
