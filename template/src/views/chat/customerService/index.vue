<template>
  <applicationManagement title="智能客服" :app-type="APP_TYPE.EXTERNAL" />
</template>

<script setup lang="ts">
import applicationManagement from '../components/applicationManagement.vue'
import { APP_TYPE } from '../setting/constants'
</script>
