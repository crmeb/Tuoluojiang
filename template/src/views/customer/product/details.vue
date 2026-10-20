<!-- 查看客户信息侧滑页面 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-product-details-drawer"
      :append-to-body="true"
      :before-close="handleClose"
      :direction="direction"
      :show-close="true"
      :size="formData.width"
      :title="formData.title"
      v-model="drawer"
    >
      <template #header>
        <div class="invoice-title">
          <div class="invoice-header">
            <div class="invoice-left">
              <div class="invoice-logo"><i class="icon iconfont iconhetong"></i></div>
            </div>
            <div v-if="drawer" class="invoice-right">
              <div class="txt1 over-text">
                {{ dataInfo.name }}
              </div>
            </div>
          </div>
        </div>
      </template>

      <div class="contract-body table-box">
        <!--基本信息-->
        <div class="contract-info">
          <el-form class="invoice-body" label-width="auto">
            <div v-for="(item, index) in dataInfo.list" :key="index">
              <div class="from-item-title mb15">
                <span>{{ item.title }}</span>
              </div>
              <div class="form-box">
                <div
                  v-for="(value, key) in item.data"
                  :key="key"
                  :class="
                    value.type !== 'file' && value.type !== 'image' && value.type !== 'oaWangeditor' ? '' : 'oneline'
                  "
                  class="form-item"
                >
                  <el-form-item v-if="value.key == 'customer_status'">
                    <template #label>
                      <span>{{ value.key_name }} ：</span>
                    </template>
                    <span>{{ value.value[0] || '-' }}</span>
                  </el-form-item>
                  <el-form-item v-else-if="value.type == 'file' || value.type == 'images'">
                    <template #label>
                      <span>{{ value.key_name }}：</span>
                    </template>
                    <upload-file
                      v-if="value.files && value.files.length > 0"
                      v-model="value.files"
                      :only-image="false"
                      :onlyRead="true"
                    ></upload-file>

                    <span v-else>--</span>
                  </el-form-item>
                  <el-form-item v-else-if="value.type == 'oaWangeditor'">
                    <template #label>
                      <span>{{ value.key_name }}：</span>
                    </template>

                    <div class="content" v-html="value.value" />
                  </el-form-item>
                  <el-form-item v-else>
                    <template #label>
                      <span>{{ value.key_name }}：</span>
                    </template>
                    <p v-html="getValue(value.value)"></p>
                  </el-form-item>
                </div>
              </div>
            </div>

            <!-- 规格售价 -->
            <div class="from-item-title mb15">
              <span>规格售价</span>
            </div>

            <!-- 单规格 -->
            <el-table :data="dataInfo.attrValue" class="mb20" v-if="dataInfo.spec_type == 0">
              <el-table-column
                v-for="(item, iii) in formThead"
                :key="iii"
                :label="item.title"
                :prop="item.key"
                min-width="110"
              >
                <template #default="scope">
                  <img
                    :src="scope.row.image"
                    alt=""
                    v-if="item.key === 'image' && scope.row.image"
                    class="product-img"
                    @click="previewPicture(scope.row)"
                  />
                  <img
                    src="../../../assets/images/bjt.png"
                    alt=""
                    v-else-if="item.key === 'image' && !scope.row.image"
                    class="product-img"
                    @click="previewPicture(scope.row)"
                  />
                  <span v-else>{{ scope.row[item.key] }}</span>
                </template>
              </el-table-column>
            </el-table>
            <!-- 多规格 -->
            <el-table :data="dataInfo.attrValue" v-if="dataInfo.spec_type == 1">
              <template v-if="manyTabDate">
                <el-table-column
                  v-for="(item, iii) in manyTabDate"
                  :key="iii"
                  :label="manyTabTit[iii].title"
                  min-width="100"
                >
                  <template #default="scope">
                    <span class="priceBox" v-text="scope.row[iii]" />
                  </template>
                </el-table-column>
              </template>
              <el-table-column
                v-for="(item, iii) in formThead"
                :key="iii"
                :label="item.title"
                :prop="item.key"
                min-width="110"
              >
                <template #default="scope">
                  <img
                    :src="scope.row.image"
                    alt=""
                    v-if="item.key === 'image' && scope.row.image"
                    class="product-img"
                    @click="previewPicture(scope.row)"
                  />
                  <img
                    src="../../../assets/images/bjt.png"
                    alt=""
                    v-else-if="item.key === 'image' && !scope.row.image"
                    class="product-img"
                    @click="previewPicture(scope.row)"
                  />
                  <span v-else>{{ scope.row[item.key] }}</span>
                </template>
              </el-table-column>
            </el-table>
          </el-form>
        </div>
      </div>
    </el-drawer>
    <image-viewer ref="imageViewer" :src-list="srcList"></image-viewer>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { getProductInfoApi } from '@/api/client.ts'
export default {
  name: 'ProductDetailsDrawer',
  components: {
    uploadFile: defineAsyncComponent(() => import('@/components/form-common/oa-upload')),
    imageViewer: defineAsyncComponent(() => import('@/components/common/imageViewer')),
    file: defineAsyncComponent(() => import('@/views/customer/list/components/file'))
  },
  props: {
    formData: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dataInfo: [],
      srcList: [],
      rowInfo: {},
      manyTabDate: {},
      manyTabTit: {},

      formThead: [
        { title: '图片', key: 'image' },
        {
          title: '产品售价(元)',
          key: 'price'
        },
        {
          title: '产品成本价（元）',
          key: 'cost'
        },
        {
          title: '产品规格编码',
          key: 'bar_code'
        },
        {
          title: '商品条码',
          key: 'product_bar_code'
        }
      ],
      id: 0,
      types: '',
      drawer: false,
      direction: 'rtl'
    }
  },
  mounted() {},

  methods: {
    async getDetails(id) {
      const result = await getProductInfoApi(id)
      this.dataInfo = result.data

      const tmp = {}
      const tmpTab = {}
      this.dataInfo.attr.forEach((o, i) => {
        tmp['value' + i] = { title: o.value }
        tmpTab['value' + i] = ''
      })
      this.manyTabDate = tmpTab
      this.manyTabTit = tmp
    },
    handleClose() {
      this.drawer = false
    },

    //预览图片
    previewPicture(row) {
      this.srcList = [row.image]
      this.$refs.imageViewer.openImageViewer(row.image)
    },
    // 数组转成字符串
    getValue(val) {
    
      let str = ''
      if (val == '') {
        str = '--'
      } else if (Array.isArray(val)) {
        str = val.toString()
      }else if(typeof val === 'object'){
        str =val.name
      }
       else {
        str = val
      }
      return str || '--'
    },

    openBox(id, type) {
      this.id = id
      this.types = type
      this.getDetails(id)
      this.drawer = true
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  padding: 0 14px;
  width: 100%;
  max-height: 400px;
  // border: 1px solid #d7dbe0;
  overflow: auto;
  :deep(p) {
    img {
      width: 50%;
    }
  }
}

:deep(.el-form--inline .el-form-item) {
  display: flex;
}
.product-img {
  cursor: pointer;
  width: 60px;
  height: 60px;
  margin-right: 10px;
  float: left;
}

.invoice-title {
  .invoice-header {
    display: flex;
    align-items: center;

    .invoice-left {
      width: 48px;
      margin-right: 10px;
      .invoice-logo {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #1890ff;
        border-radius: 4px;
        i {
          color: #ffffff;
          font-size: 30px;
          // margin-top: 12px;
        }
      }
    }
    .invoice-right {
      width: calc(100% - 55px);
    }
    .txt1 {
      font-size: 16px;
      font-weight: bold;
      color: rgba(0, 0, 0, 0.85);
    }
    .txt3 {
      font-size: 14px;
    }
    .txt2 {
      margin-top: 10px;
      font-size: 13px;
      color: #000;
      .title {
        font-size: 14px;
        color: #999999;
        padding-left: 20px;
        font-weight: 400;
      }
      .title:first-of-type {
        padding-left: 0;
      }
      .info1 {
        color: #19be6b;
      }
      .info2 {
        color: rgba(245, 34, 45, 1);
      }
      .info3 {
        color: #1890ff;
      }
    }
  }
}
.from-item-title {
  border-left: 5px solid #1890ff;
  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}
.contract-body {
  margin-bottom: 30px;
  padding: 20px;
  display: flex;
  height: 100%;
  justify-content: center;
  .contract-info {
    width: 100%;
    .form-box {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      .form-item {
        width: 49%;
        :deep(.el-form-item__content) {
          width: calc(100% - 110px);
        }
        :deep(.el-select) {
          width: 100%;
        }
        :deep(.el-form-item) {
          margin-bottom: 15px;
        }
        :deep(.el-textarea__inner) {
          resize: none;
        }
      }
    }
  }
  .contract-record {
    width: 100%;
  }
  .contract-remind {
    height: calc(100% - 120px);
  }
  .contract-list {
    width: 100%;
    height: calc(100% - 44px);
    :deep(.el-button) {
      font-size: 13px;
    }
  }
}
.dialog-footer {
  padding-top: 20px;
  border-top: 1px solid #e6ebf5;
  text-align: right;
}
.from-foot-btn {
  button {
    height: auto;
  }
}
.from-item-title {
  border-left: 3px solid #1890ff;
  span {
    padding-left: 10px;
    font-weight: bold;
    font-size: 14px;
  }
}
:deep(.el-form-item__label) {
  color: #606266;
}
.form-box {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  .form-item {
    width: 49%;

    :deep(.el-form-item__content) {
      width: calc(100% - 110px);
    }
    :deep(.el-select) {
      width: 100%;
    }
    :deep(.el-form-item) {
      margin-bottom: 0;
    }
    :deep(.el-textarea__inner) {
      resize: none;
    }

    p {
      margin: 0;
      padding: 0;

      font-weight: 400 !important;
      color: #303133;
      font-size: 13px !important;
      margin-top: 10px;
      line-height: 18px;
    }
  }
}
.oneline {
  width: 100% !important;
}
:deep(.btn-box) {
  display: flex;
  justify-content: flex-end;
  .upload-box {
    text-align: right;
  }
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-product-details-drawer .el-drawer__body {
  padding-bottom: 50px;
}
.customer-product-details-drawer .el-drawer__header {
  height: 80px !important;
  border: none;
  padding: 14px 18px;
}
.customer-product-details-drawer .el-drawer__header {
  border-bottom: 1px solid #d7dbe0;
}
</style>
