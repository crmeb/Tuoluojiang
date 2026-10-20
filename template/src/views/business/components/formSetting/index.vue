<!--审批设置-表单配置 -->
<template>
  <div class="formData">
    <el-card class="box-card">
      <form-create-designer ref="designer" :condition="flowDesignerStore.conditionsFields"></form-create-designer>
    </el-card>
  </div>
</template>
<script>
import { mapStores } from 'pinia'
import { useFlowDesignerStore } from '@/stores/flowDesigner.ts'
import formCreateDesigner from './components/form-create-designer/src/index'
export default {
  props: {
    tabName: {
      type: String,
      default: ''
    },
    conf: {
      type: Object,
      default: () => {
        return null
      }
    }
  },
  computed: {
    ...mapStores(useFlowDesignerStore)
  },
  data() {
    return {
      formData: {}
    }
  },
  components: {
    formCreateDesigner
  },
  mounted() {
    if (typeof this.conf === 'object' && this.conf !== null) {
      this.$refs.designer.setRule(this.conf.props)
    }
  },
  methods: {
    /**
     * 供父组件使用 获取表单JSON
     */
    getData() {
      return new Promise((resolve, reject) => {
        const FcDesignerRule = this.$refs.designer.getJson()
        if (JSON.parse(FcDesignerRule).length === 0) {
          reject({ msg: '您的行容器中没有组件,请添加组件', target: this.tabName })
          return
        }
        resolve({
          formData: {
            form: {},
            props: JSON.parse(FcDesignerRule)
          }
        })
      })
    }
  }
}
</script>
<style lang="scss" scoped>
// 撑满编辑页主体区域，设计器只让中间预览区滚动
.formData {
  height: 100%;
  :deep(.el-aside),
  :deep(._fc-l-group) {
    padding: 0;
  }
  :deep(._fc-l),
  :deep(._fc-m),
  :deep(._fc-r) {
    border-top: none;
    background: #fff;
  }
  :deep(.el-aside::-webkit-scrollbar) {
    display: none;
  }
  :deep(._fc-m-drag::-webkit-scrollbar-thumb) {
    -webkit-box-shadow: inset 0 0 6px #ccc;
  }
  :deep(._fc-m-drag::-webkit-scrollbar) {
    width: 4px !important; /*对垂直流动条有效*/
  }
  :deep(._fc-m) {
    border-left: 1px solid #eeeeee;
    border-right: 1px solid #eeeeee;
  }
}
.box-card {
  height: 100%;
  :deep(.el-card__body) {
    padding-top: 0;
  }
}
</style>
