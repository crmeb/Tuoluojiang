<template>
  <!-- 公海池客户 -->
  <div>
    <index :types="`customer_seas`"></index>
  </div>
</template>

<script>
import index from './index'
export default {
  name: 'public',
  components: {
    index
  }
}
</script>

<style lang="scss" scoped></style>
