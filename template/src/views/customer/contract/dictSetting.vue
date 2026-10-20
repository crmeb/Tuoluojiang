<!--
  @FileDescription: 合同订单模块 - 字典选项设置页面（外壳页面）
-->
<template>
  <dict-option-setting dict-key="contract" />
</template>

<script>
import DictOptionSetting from '../components/DictOptionSetting.vue'

export default {
  name: 'ContractDictSetting',
  components: { DictOptionSetting }
}
</script>
