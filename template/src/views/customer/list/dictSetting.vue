<!--
  @FileDescription: 客户模块 - 字典选项设置页面（外壳页面）
-->
<template>
  <dict-option-setting dict-key="customer" />
</template>

<script>
import DictOptionSetting from '../components/DictOptionSetting.vue'

export default {
  name: 'CustomerDictSetting',
  components: { DictOptionSetting }
}
</script>
