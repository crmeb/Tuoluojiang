<!-- @FileDescription: 公共-空数据占位（展示图片 + 文案，类型/文案/尺寸动态传入） -->
<template>
  <div class="empty-state" :style="rootStyle">
    <div class="empty-state__inner">
      <!-- 图片：优先使用具名插槽，便于完全自定义 -->
      <slot name="image">
        <img v-if="imgUrl" :src="imgUrl" :style="imgStyle" alt="" class="empty-state__img" />
      </slot>

      <!-- 主文案 -->
      <slot name="text">
        <p v-if="text" class="empty-state__text" :style="textStyle">{{ text }}</p>
      </slot>

      <!-- 副文案（描述） -->
      <p v-if="description" class="empty-state__desc">{{ description }}</p>

      <!-- 默认插槽：放操作按钮等额外内容 -->
      <div v-if="$slots.default" class="empty-state__extra">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script>
// 动态加载 @/assets/images/none 下的全部图片，以文件名（去扩展名）作为类型 key
const imageContext = import.meta.glob('@/assets/images/none/*.{png,jpg,jpeg,svg,gif}', {
  eager: true,
  query: '?url',
  import: 'default',
})
const imageModules = {}
Object.keys(imageContext).forEach((key) => {
  const match = key.match(/\/([^/]+)\.(png|jpe?g|svg|gif)$/)
  if (match) {
    imageModules[match[1]] = imageContext[key]
  }
})

const toPx = (val) => {
  if (val === '' || val === null || val === undefined) return ''
  return typeof val === 'number' || /^\d+(\.\d+)?$/.test(String(val)) ? `${val}px` : String(val)
}

export default {
  name: 'EmptyState',
  props: {
    // 图片类型，对应 @/assets/images/none 下的文件名（不含扩展名）
    type: {
      type: String,
      default: ''
    },
    // 也可直接传入完整图片地址，优先级高于 type
    src: {
      type: String,
      default: ''
    },
    // 主文案
    text: {
      type: String,
      default: '暂无数据'
    },
    // 副文案（描述说明，可选）
    description: {
      type: String,
      default: ''
    },
    // 容器最小高度
    minHeight: {
      type: [Number, String],
      default: 220
    },
    // 图片宽度
    imgWidth: {
      type: [Number, String],
      default: 150
    },
    // 图片高度（默认自适应）
    imgHeight: {
      type: [Number, String],
      default: ''
    },
    // 文案颜色
    textColor: {
      type: String,
      default: '#999999'
    },
    // 文案字号
    fontSize: {
      type: [Number, String],
      default: 13
    }
  },
  computed: {
    imgUrl() {
      return this.src || imageModules[this.type] || ''
    },
    rootStyle() {
      return { minHeight: toPx(this.minHeight) }
    },
    imgStyle() {
      return {
        width: toPx(this.imgWidth),
        height: toPx(this.imgHeight)
      }
    },
    textStyle() {
      return {
        color: this.textColor,
        fontSize: toPx(this.fontSize)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;

  &__inner {
    text-align: center;
  }

  &__img {
    display: block;
    margin: 0 auto;
    object-fit: contain;
  }

  &__text {
    margin: 0;
    padding: 6px 0 0 0;
    line-height: 1.5;
  }

  &__desc {
    margin: 4px 0 0 0;
    font-size: 12px;
    color: #c0c4cc;
    line-height: 1.5;
  }

  &__extra {
    margin-top: 12px;
  }
}
</style>
