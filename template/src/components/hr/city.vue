<!-- @FileDescription: 人事-审批设置-省市区组 -->
<template>
  <div class="timeFrom">
    <div class="el-form-item el-form-item--mini">
      <label class="el-form-item__label" style="width: 120px">
        <span
          v-if="
            formCreateInject && formCreateInject.rule
              ? formCreateInject.rule.effect.required
              : formCreateInject.effect.required
          "
          class="color-tab"
          >*</span
        >
        <span>省市区</span>
      </label>
      <div class="el-form-item__content" style="margin-left: 120px">
        <template v-if="!check">
          <el-cascader
            v-model="timeData.city"
            :options="cityData"
            style="width: 100%"
            clearable
            @change="changeDuration"
            :props="{ value: 'label', label: 'label' }"
          />
        </template>
        <span v-if="check">{{ timeData.city[0] }}-{{ timeData.city[1] }}-{{ timeData.city[2] }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { getStorageJson } from '@/utils/storage.ts'
import { getCityListApi } from '@/api/public.ts'
export default {
  emits: ['update:modelValue'],
  name: 'Index',
  props: {
    formCreateInject: Object,
    modelValue: {
      type: Object,
      default: () => ({})
    },
    disabled: Boolean,
    timeType: {
      type: String,
      default: () => 'day'
    },
    time: {
      type: String,
      default: () => ''
    },
    titleIpt: {
      type: String,
      default: () => '省市区'
    },
    check: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
  },
  mounted() {
    if (!localStorage.getItem('city')) {
      this.getCityList()
    }
  },
  data() {
    return {
      cityData: getStorageJson('city'),
      title: this.titleIpt,
      timeData: {
        city: this.modelValue.city
      }
    }
  },
  watch: {
    value(n) {
      this.timeData = n
    }
  },

  methods: {
    getCityList() {
      getCityListApi().then((res) => {
        localStorage.setItem('city', JSON.stringify(res.data))
      })
    },
    changeDuration() {
      this.$emit('update:modelValue', this.timeData)
    }
  }
}
</script>

<style scoped lang="scss">
.timeFrom + :deep(.el-form-item__error) {
  margin-left: 120px;
  position: absolute;
  top: 98%;
  left: 0;
}
.el-form--label-top .el-form-item__label {
  position: relative;
  float: none;
  display: inline-flex;
  justify-content: flex-start;
  padding: 0 0 10px 0;
}
.timeFrom {
  position: relative;
  padding-top: 10px;
}
.timeFrom {
  :deep(.el-date-editor),
  :deep(.el-date-editor--date),
  :deep(.el-select) {
    width: 220px;
  }
}
</style>
