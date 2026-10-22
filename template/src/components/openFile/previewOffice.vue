<template>
  <div class="box">
    <iframe :src="officeUrl" width="100%" height="100%" frameBorder="0"></iframe>
  </div>
</template>

<script>
import { resolveAbsoluteResourceUrl } from '@/utils/resourceUtil'
export default {
  name: '',
  props: {
    url: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      officeUrl: ''
    }
  },

  mounted() {
    // 微软在线预览要求传入可公网访问的绝对地址，相对路径需先补全域名
    const absoluteUrl = resolveAbsoluteResourceUrl(this.url || '')
    this.officeUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(absoluteUrl)}`
  }
}
</script>
<style scoped lang="scss">
.box {
  height: calc(100vh - 85px);
}
.Embed.NewEmbed .WACContainer {
  background: red;
}
</style>
