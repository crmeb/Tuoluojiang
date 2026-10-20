<template>
  <div class="ivu-global-footer-copyright">
    <div class="mb14">
      <span v-for="(item, index) in links" :key="index" @click="openFn(item)" class="mr14 pointer">{{
        item.title
      }}</span>
      </div>
    
    &copy;{{ dayjs().format('YYYY') }} 陀螺匠. All Rights Reserved. Version {{ title || 'v1.5' }}
  </div>
</template>

<script>
import dayjs from 'dayjs'
import { getStorageJson } from '@/utils/storage.ts'
export default {
  name: 'CrmebOaEntCopyright',
  computed: {
    dayjs: () => dayjs
  },
  data() {
    return {
      title: '',
      links: [
        {
          title: '官网',
          key: '官网',
          href: 'https://tuoluojiang.com',
          blankTarget: true
        },
        {
          title: '社区',
          key: '社区',
          href: 'https://www.crmeb.com/ask/thread/list/157',
          blankTarget: true
        },
        {
          title: '文档',
          key: '文档',
          href: 'https://tuoluojiang.com/doc/own/0',
          blankTarget: true
        }
      ]
    }
  },
  mounted() {
    const sitedata = getStorageJson('sitedata')
    this.title = sitedata ? sitedata.version_name : ''
  },

  methods: {
    openFn(data) {
      window.open(data.href, '_blank')
    }
  }
}
</script>

<style lang="scss" scoped>
.ivu-global-footer-copyright {
  text-align: center;
  color: #808695;
  font-size: 14px;
  width: 100%;
  height: 65px;
  margin-top: 15px;
}
</style>
