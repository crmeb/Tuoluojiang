<!-- @FileDescription: 公共-缺省页 -->
<template>
  <div class="default-page">
    <div v-if="showContent" class="content" :style="contentStyle">
      <div class="content-list" :style="contentListStyle">
        <img v-if="imgShow" :src="currentContent.url" alt="" class="img" />
        <p v-if="textShow">{{ displayText }}</p>
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script>
// 静态图片导入
import newdef1 from '@/assets/images/newdef1.png'
import none008 from '@/assets/images/none-008.png'
import newdef2 from '@/assets/images/newdef2.png'
import viewImg from '@/assets/images/view.png'
import def1 from '@/assets/images/none-009.png'
import none012 from '@/assets/images/none-012.png'
import dkSuccess from '@/assets/images/empty/dk-success.png'

// 动态图片导入
const imageContext = import.meta.glob('@/assets/images/none-*.png', {
  eager: true,
  query: '?url',
  import: 'default',
})
const imageModules = {}
Object.keys(imageContext).forEach((key) => {
  const match = key.match(/none-(.+)\.png$/)
  if (match) {
    imageModules[match[1]] = imageContext[key]
  }
})

function getImageUrl(type) {
  return imageModules[type] || ''
}

// 静态内容配置
// 按图片序号对应的缺省文案（原 public.message00 ~ message17）
const INDEXED_TEXTS = [
  '暂无考核记录',
  '暂无统计图',
  '暂无考核详情',
  '暂无考核计划',
  '暂无考核记录',
  '暂无考核模板',
  '暂无员工绩效',
  '暂无文件或文件夹',
  '暂无待办任务',
  '暂无记事本内容',
  '暂无收入统计图',
  '暂无支出统计图',
  '暂无职位说明详情',
  '暂无职位体系图',
  '暂无数据',
  '暂无工作分析数据',
  '暂无数据',
  '暂无数据',
]
const STATIC_CONTENTS = [
  { url: newdef1, text: '暂无表单数据' },
  { url: none008, text: '暂无评论' },
  { url: newdef2, text: '暂无数据' },
  { url: viewImg, text: '暂无视图' },
  { url: dkSuccess, text: '您的问卷已经提交，感谢您的参与！' },
  { url: def1, text: '暂无数据' },
  { url: newdef2, text: '暂无数据' },
  { url: none012, text: '暂无考核记录' }
]

export default {
  name: 'DefaultPage',
  props: {
    index: {
      type: Number,
      default: -1,
      validator: (value) => value >= -1
    },
    textShow: {
      type: Boolean,
      default: true
    },
    imgShow: {
      type: Boolean,
      default: true
    },
    minHeight: {
      type: [Number, String],
      default: 520
    },
    top: {
      type: String,
      default: '0px'
    },

    height: {
      type: String,
      default: ''
    },
    imgWidth: {
      type: String,
      default: '200px'
    }
  },
  computed: {
    showContent() {
      return this.index > -1 && this.currentContent
    },
    currentContent() {
      if (this.index < 0) return null
      return this.contentList[this.index] || null
    },
    displayText() {
      return this.currentContent?.text ? `${this.currentContent.text}~` : ''
    },
    contentStyle() {
      return {
        minHeight: this.height || this.getMinHeight(),
        '--imgWidth': this.imgWidth
      }
    },
    contentListStyle() {
      if (!this.top || this.top === '0' || this.top === '0px') return {}
      const offset = String(this.top).endsWith('px') ? this.top : `${this.top}px`
      return { transform: `translateY(-${offset})` }
    },
    // 按序号生成缺省内容
    indexedContents() {
      return Array.from({ length: 18 }, (_, i) => ({
        url: getImageUrl(this.getImageType(i)),
        text: INDEXED_TEXTS[i]
      }))
    },
    contentList() {
      return [...this.indexedContents, ...STATIC_CONTENTS]
    }
  },
  methods: {
    getMinHeight() {
      return String(this.minHeight).endsWith('px') ? this.minHeight : this.minHeight + 'px'
    },
    getImageType(index) {
      const types = [
        'assess',
        'statistics',
        '001',
        '002',
        '003',
        '004',
        '005',
        '006',
        '007',
        '007',
        'statistics',
        'statistics',
        '003',
        '003',
        'assess',
        '009',
        '010',
        '011'
      ]
      return types[index] || '003'
    }
  }
}
</script>

<style lang="scss" scoped>
.default-page {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;

  .content {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 400px;
    width: 100%;
    text-align: center;

    .content-list {
      .img {
        width: var(--imgWidth);
      }

      p {
        margin: 0;
        padding: 6px 0 0 0;
        font-size: 13px;
        color: #999999;
      }
    }
  }
}
</style>
