<template>
  <el-form-item :label="i18nt('designer.setting.defaultValue')">
    <!-- 时间选择默认值 -->

    <el-radio-group v-model="optionModel.defaultType">
      <el-radio :value="1">此刻</el-radio>
      <el-radio :value="2">选择时间</el-radio>
    </el-radio-group>

    <el-date-picker
      v-if="optionModel.defaultType == 2"
      v-model="optionModel.defaultValue"
      @change="emitDefaultValueChange"
      :format="toDayjsFormat(optionModel.format)"
      :value-format="toDayjsFormat(optionModel.valueFormat)"
      type="datetime"
      placeholder="选择时间"
      class="mt14"
      style="width: 100%"
    >
    </el-date-picker>
  </el-form-item>
</template>

<script>
import { toDayjsFormat } from '@/utils/date-format.ts'
import i18n from '@/utils/i18n.ts'
import propertyMixin from '@/components/form-designer/setting-panel/property-editor/propertyMixin'

export default {
  name: 'date-time-picker-defaultValue-editor',
  mixins: [i18n, propertyMixin],
  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  },
  methods: {
    toDayjsFormat
  }
}
</script>

<style scoped lang="scss">
:deep(.el-radio) {
  font-weight: 400;
  font-size: 13px;
}
</style>
