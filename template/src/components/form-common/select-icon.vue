<!-- @FileDescription: 选择图标组件 例：菜单管理选择图标 -->
<template>
  <div class="iconBox">
    <div class="toolbar">
      <span class="toolbar__count">共 {{ displayList.length }} 项</span>
      <el-input
        v-model="keyword"
        class="toolbar__search"
        placeholder="请输入图标名称"
        prefix-icon="el-icon-search"
        clearable
      />
      <el-select v-model="source" class="toolbar__category">
        <el-option v-for="opt in sourceOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
      </el-select>
      <el-button class="toolbar__clear" :disabled="!hasFilter" @click="resetFilter">
        <i class="iconfont iconqingchu" />
      </el-button>
      <el-radio-group v-if="source === 'crmeb'" v-model="iconType" class="toolbar__segmented">
        <el-radio-button label="outlined">线性</el-radio-button>
        <el-radio-button label="filled">面性</el-radio-button>
      </el-radio-group>
    </div>

    <div class="icons-container">
      <div class="grid">
        <div
          v-for="(item, index) in displayList"
          :key="item.value + index"
          class="icon-item"
          @click="selectIcon(item.value)"
        >
          <i :class="item.iconClass" />
          <span class="line1" :title="item.name">{{ item.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'
import iconfontIcons from '../../libs/iconfont-icons'

export default {
  name: 'SelectIcon',
  props: {
    // 为 true 时通过 select 事件向外抛出选中值，否则写入 form_create
    isEmit: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance()

    const sourceOptions = [
      { label: '官方图标库', value: 'crmeb' },
      { label: 'ELEMENT', value: 'element' }
    ]

    const keyword = ref('')
    const source = ref('crmeb') // crmeb | element
    const iconType = ref('outlined') // outlined(线性) | filled(面性)，仅官方图标库生效

    const isFilled = computed(() => iconType.value === 'filled')

    // 官方图标：归一化为统一模型 { value, iconClass, name }
    const crmebIcons = iconfontIcons.map((i) => ({
      value: i.icon,
      iconClass: `iconfont ${i.icon}`,
      name: i.name
    }))

    // ELEMENT 图标：组件挂载后从 element-ui 的 icon.scss 中解析类名
    const elementIcons = ref([])
    onMounted(async () => {
      // `?raw` 是 Vite 读取文件原文的写法，等价于 webpack 的 `!!raw-loader!`
      const cssString = (await import('element-ui/packages/theme-chalk/src/icon.scss?raw')).default
      const matches = cssString.matchAll(/(el-icon-[a-zA-Z0-9-]+):before\s*{/g)
      elementIcons.value = Array.from(matches, ([, cls]) => ({
        value: cls,
        iconClass: cls,
        name: cls.slice(8) // 去掉 'el-icon-' 前缀
      }))
    })

    // 当前来源 + 线性/面性 + 关键词，全部由计算属性派生
    const displayList = computed(() => {
      let pool
      if (source.value === 'crmeb') {
        const typeKey = isFilled.value ? '面性' : '线性'
        pool = crmebIcons.filter((i) => i.name.includes(typeKey))
      } else {
        pool = elementIcons.value
      }
      const kw = keyword.value.trim()
      return kw ? pool.filter((i) => i.name.includes(kw)) : pool
    })

    const hasFilter = computed(
      () => !!keyword.value || source.value !== 'crmeb' || iconType.value !== 'outlined'
    )

    function resetFilter() {
      keyword.value = ''
      source.value = 'crmeb'
      iconType.value = 'outlined'
    }

    function selectIcon(value) {
      if (props.isEmit) {
        emit('select', value)
        return
      }
      /* eslint-disable no-undef */
      form_create_helper.set(proxy.$route.query.field, value)
      form_create_helper.close('icon')
      /* eslint-enable no-undef */
    }

    return {
      sourceOptions,
      keyword,
      source,
      iconType,
      displayList,
      hasFilter,
      resetFilter,
      selectIcon
    }
  }
}
</script>

<style lang="scss" scoped>
.iconBox {
  width: 100%;
  height: 100%;
  background-color: #fff;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 36px;

  &__count {
    font-size: 12px;
    color: #909399;
    white-space: nowrap;
    min-width: 60px;
  }

  &__search {
    width: 250px;
  }

  &__category {
    width: 160px;
  }

  &__clear {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    background: #fff;
    color: #909399;
    font-size: 14px;

    &:hover:not(.is-disabled) {
      color: #303133;
      border-color: #c0c4cc;
    }
  }

  // 线性/面性分段切换，居右
  &__segmented {
    margin-left: auto;
    display: inline-flex;
    padding: 2px;
    background: #f5f5f5;
    border-radius: 5px;
    line-height: normal;
  }
}

// 用 el-radio-button 重置出 segmented 分段控制器样式
::v-deep .toolbar__segmented {
  .el-radio-button__inner {
    width: 42px;
    height: 28px;
    padding: 0;
    line-height: 28px;
    text-align: center;
    border: none;
    border-radius: 4px;
    background: transparent;
    box-shadow: none;
    font-size: 13px;
    font-weight: 400;
    color: #303133;
    transition: background-color 0.2s ease;
  }

  .el-radio-button:not(:first-child) .el-radio-button__inner {
    margin-left: 0;
  }

  .el-radio-button.is-active .el-radio-button__inner {
    background: #fff;
    color: #303133;
    box-shadow: none;
  }

  .el-radio-button__inner:hover {
    color: #303133;
  }
}

::v-deep .toolbar__search .el-input__inner {
  height: 32px;
  line-height: 32px;
}
::v-deep .toolbar__search .el-input__icon {
  line-height: 32px;
}
::v-deep .toolbar__category .el-input__inner {
  height: 32px;
  line-height: 32px;
}
::v-deep .toolbar__category .el-input__icon {
  line-height: 32px;
}

.icons-container {
  width: 100%;
  height: calc(100% - 36px);
  padding-top: 10px;
  overflow: auto;

  .grid {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(auto-fill, 82px);
    gap: 10px 26px;
  }

  .icon-item {
    height: 81px;
    display: flex;
    flex-flow: column;
    text-align: center;
    justify-content: center;
    align-items: center;
    font-size: 20px;
    color: #606266;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.15s ease;
    overflow: hidden;

    &:hover {
      background-color: #f3f5f9;
    }

    [class^='el-icon-'],
    .iconfont {
      font-size: 30px;
    }

    span {
      font-size: 12px;
      color: #303133;
      margin-top: 6px;
      width: 100%;
      padding-inline: 3px;
    }
  }
}
</style>
