<template>
  <el-select v-bind="$attrs" :value="innerValue" :multiple="multiple" :collapse-tags="multiple" :loading="isLoading"
    filterable :clearable="clearable" :placeholder="placeholder" @change="handleChange"
    @visible-change="handleVisibleChange" size="small">
    <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"
      :disabled="disabledIds.includes(item.value)" />
  </el-select>
</template>

<script>
import { computed, watch } from "vue";
import { useWarehouse } from "../composables/useWarehouse";
import { WAREHOUSE_SCOPE } from "../constants";

export default {
  name: 'WareHouseSelect',
  inheritAttrs: false,
  model: {
    prop: 'value',
    event: 'change'
  },
  props: {
    // 单选时为单个仓库 ID；多选时为仓库 ID 数组（如 [3, 4]），
    // 兼容接收逗号分隔字符串（如 "3,4"）作为回显值
    value: {
      type: [String, Number, Array],
      default: ''
    },
    // 是否多选，默认单选
    multiple: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: '请输入选择管理仓库'
    },
    // 需禁用的仓库 ID 列表（如调拨时禁止调入与调出为同一仓库）
    disabledIds: {
      type: Array,
      default: () => []
    },
    // 按业务动作权限过滤仓库（权限标识列表，取值见 constants 的 WAREHOUSE_PERMISSION）。
    // 为空数组时不校验权限，展示全部可查看仓库；非空时仅展示「同时具备列表内所有权限」的仓库。
    // 仅在 scope=accessible 时生效（全量仓库不携带权限信息）。
    permission: {
      type: Array,
      default: () => []
    },
    // 选项数据源范围（取值见 constants 的 WAREHOUSE_SCOPE）：
    // accessible（默认）=当前登录人可查看的仓库，单据类场景用它；
    // all=全量仓库（含已关闭、无权限的仓库），用于「给仓库配权限」的场景，
    // 否则待授权的仓库既选不到、编辑回显也匹配不到 option（只显示裸 ID）。
    scope: {
      type: String,
      default: WAREHOUSE_SCOPE.ACCESSIBLE
    },
    // 无选中值时自动选中第一个仓库（用于仓库为必选查询条件的列表页）。
    defaultFirst: {
      type: Boolean,
      default: false
    },
    clearable: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { emit }) {
    // 选项由 composable 按 scope 选择数据源、按 permission 内聚过滤；
    // filterable 提供本地关键字搜索
    const { options, warehouseLoading: isLoading, loadWarehouses } = useWarehouse({
      permission: props.permission,
      scope: props.scope
    });

    // 将外部值（逗号分隔字符串 / 数组 / 空）归一化为数字 ID 数组，供多选 el-select 使用
    const toIdArray = (val) => {
      if (Array.isArray(val)) return val.map(Number).filter((v) => !Number.isNaN(v));
      if (val === "" || val === null || val === undefined) return [];
      return String(val)
        .split(",")
        .map((v) => Number(v))
        .filter((v) => !Number.isNaN(v));
    };

    // el-select 绑定值：多选时用数字数组，单选时原样透传
    const innerValue = computed(() => (props.multiple ? toIdArray(props.value) : props.value));

    const isEmptyValue = (val) => {
      return Array.isArray(val) ? !val.length : val === "" || val === null || val === undefined;
    };

    const emitChange = (val) => {
      if (props.multiple) {
        const arr = Array.isArray(val) ? val : [];
        // 多选回传 ID 数组，作为 warehouse_ids 直接透传给接口（序列化为 warehouse_ids[]）
        emit('change', arr)
        emit('select', options.value.filter((item) => arr.includes(item.value)))
      } else {
        emit('change', val)
        // 同步回传选中的完整选项，便于父级取仓库名称等字段
        emit('select', options.value.find((item) => item.value === val) || null)
      }
    }

    const handleChange = (val) => {
      // defaultFirst 表示仓库是必选筛选条件：点清空时直接回落到首个仓库，不向父级派发空值。
      // 否则父级会先按「无仓库」查一次，紧接着才收到回填首仓的查询，两次查询并发下
      // 生效的往往是前一次，界面出现「筛选框显示首仓、列表却是全部仓库」的不一致
      if (props.defaultFirst && isEmptyValue(val) && options.value.length) {
        emitChange(props.multiple ? [options.value[0].value] : options.value[0].value)
        return
      }
      emitChange(val)
    }

    // 下拉展开时确保已加载（带缓存，重复展开不会重复请求）
    const handleVisibleChange = (visible) => {
      if (visible) loadWarehouses()
    }

    // defaultFirst：选项就绪且当前无选中值时自动选中第一个仓库，
    // 走 handleChange 让父级拿到与手动选择一致的 change/select 事件；
    // 同时监听 value，重置搜索清空后也会重新选中首仓
    watch([options, () => props.value], ([opts, val]) => {
      if (!props.defaultFirst || !opts.length) return;
      if (!isEmptyValue(val)) return;
      emitChange(props.multiple ? [opts[0].value] : opts[0].value);
    }, { immediate: true });

    return {
      isLoading,
      options,
      innerValue,
      handleChange,
      handleVisibleChange
    }
  }
}
</script>

<style scoped lang="scss">
// 多选（collapse-tags）时 tag 恒定单行。
// .el-select__tags 是绝对定位在输入框上的 flex 浮层，宽度被组件锁死为「输入框宽 - 32px」，
// 内容一旦超宽就会换行，把选择器整体撑高
:deep(.el-select__tags) {
  flex-wrap: nowrap;
  // tags 浮层贴着输入框左边框，左间距只能靠 tag 自身的 margin-left(6px) 撑出，
  // 与 el-input__inner 的 15px 内边距对不齐；改由容器统一给出起始间距
  padding-left: 15px;
}

:deep(.el-select__tags .el-tag) {
  // 左外边距一并清零、间隔改用 margin-right，避免首尾各多算一次间距把内容顶出容器
  margin-left: 0;
  margin-right: 6px;
  // 「+N」聚合 tag 必须完整可见，不参与收缩
  flex-shrink: 0;

  .el-tag__close {
    flex-shrink: 0;
  }
}

// 由首个 tag 独自承担收缩：宽度够时完整展示仓库名，不够时省略（省略样式 element 自带），
// 而不是换行。相比写死 max-width，能自适应输入框宽度，窄容器下也不会被顶到第二行
:deep(.el-select__tags .el-tag:first-child) {
  flex-shrink: 1;
  min-width: 0;
}

// filterable 的搜索输入框自带 15px 左外边距，跟在 tag 之后会额外占宽并触发上述换行
:deep(.el-select__input) {
  margin-left: 0;
}
</style>
