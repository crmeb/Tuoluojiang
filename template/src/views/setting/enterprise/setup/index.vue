<template>
  <div class="divBox">
    <div class="box-height">
      <el-card :body-style="{ padding: 0 }">
        <el-tabs v-model="activeName" class="cr-header-tabs" @tab-change="handleClick">
          <el-tab-pane
            v-for="item in tabData"
            :key="'id' + item.key"
            :label="item.label"
            :name="String(item.key)"
          ></el-tab-pane>
        </el-tabs>
        <div class="content">
          <cloudfile :rule="rule" v-if="activeName === 'wps_config'" />
          <oneNumber v-else-if="activeName === 'yiht_config'" :fromData="rule" />
          <storage v-else-if="activeName === 'storage_config'" />
          <config-form v-else :config-type="activeName" :rule="rule" :from-data="fromData" @submit="onSubmit" />
        </div>
      </el-card>
    </div>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import cloudfile from './components/cloudfile'
import oneNumber from './components/oneNumber'
import storage from './components/storage.vue'
import configForm from './components/config-form.vue'
import formCreate from '@form-create/element-ui'
import request from '@/api/request.ts'
import { configCateApi, configUpdateDataApi } from '@/api/setting.ts'
import { site } from '@/api/user.ts'
import { aiFloatEntryController } from '@/libs/ai/index.ts'

export default {
  name: 'CompanyInfo',
  components: {
    cloudfile,
    oneNumber,
    storage,
    configForm,
    formCreate: formCreate.$form()
  },
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      // tab 的 key 为字符串，初值用空串：接口返回前 config-form 的 configType 传数字会报 prop 类型警告
      activeName: '',
      fromData: null,
      rule: [],
      tabData: [],
      option: {
        form: {
          labelWidth: '150px',
          labelSuffix: '：'
        },
        global: {
          upload: {
            props: {
              onSuccess(rep, file) {
                if (rep.status === 200) {
                  file.url = rep.data.src
                }
              }
            }
          }
        }
      }
    }
  },
  mounted() {
    this.getConfigCate()
  },
  unmounted() {},
  methods: {
    getSide() {
      site().then((res) => {
        localStorage.setItem('sitedata', JSON.stringify(res.data))
      })
    },
    async getConfigCate() {
      const res = await configCateApi()
      this.tabData = res.data
      this.activeName = res.data[0].key
      this.handleClick()
    },
    handleClick() {
      if (this.activeName) {
        this.getConfigData({ category: this.activeName })
      }
    },
    getConfigData(data) {
      configUpdateDataApi(data).then((res) => {
        this.fromData = res.data
        this.rule = res.data.rule
      })
    },
    onSubmit(formData) {
      request[this.fromData.method.toLowerCase()](this.fromData.action, formData).then(async () => {
        if (Object.prototype.hasOwnProperty.call(formData, 'ai_status')) {
          await aiFloatEntryController.refresh(this.userStore.token, this.$route.path)
        }
      })
      localStorage.setItem('isWebConfig', formData.global_watermark_status)
      setTimeout(() => {
        this.getSide()
      }, 3000)
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-scrollbar__wrap) {
  overflow-x: hidden;
}
.cr-header-tabs {
  // border-bottom: 1px solid #EEEEEE;
  // margin-bottom: 20px;
  // padding-left: 20px;
}
:deep(.el-tabs__header) {
  border-bottom: 1px solid #eeeeee;
}
:deep(.el-tabs__nav) {
  margin-left: 20px;
}
:deep(.el-tabs__item) {
  height: 60px;
  line-height: 60px;
}
.content {
  padding: 20px;
}
</style>
