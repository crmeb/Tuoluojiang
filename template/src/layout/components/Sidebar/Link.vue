<template>
  <!-- Vue 3 不再从 v-bind 对象里读取 is，需单独绑定 -->
  <component :is="linkTag(to)" v-bind="linkProps(to)">
    <slot />
  </component>
</template>

<script>
import { isExternal } from '@/utils/validate.ts'
export default {
  props: {
    to: {
      type: String,
      required: true
    }
  },
  methods: {
    linkTag(url) {
      return isExternal(url) ? 'a' : 'router-link'
    },
    linkProps(url) {
      if (isExternal(url)) {
        return {
          href: url,
          target: '_blank',
          rel: 'noopener'
        }
      }
      return {
        to: url
      }
    }
  }
}
</script>
