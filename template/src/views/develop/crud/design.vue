<template>
  <!-- 实体设计页面 -->
  <div class="divBox">
    <el-card class="card-box" :body-style="{ padding: '0 0 20px 0 ' }" v-loading="infoLoading">
      <div class="header">
        <div class="left">
          <i class="el-icon-arrow-left" @click="backFn"></i>
          <div class="invoice-logo"><i class="icon iconfont iconshitosheji"></i></div>
        </div>
        <div class="right">
          <span class="title">{{ info.table_name || '实体设计' }}</span>
          <el-tabs v-model="activeName" class="tab">
            <el-tab-pane v-for="(item, index) in tabArray" :key="index" :name="item.value">
              <template #label>
                <div>
                  <span>{{ item.label }}</span>
                </div>
              </template>
            </el-tab-pane>
          </el-tabs>
        </div>
      </div>
      <div class="ml20 mr20" v-if="info.id">
     
        <!-- 实体属性 -->
        <basic-setting v-if="activeName == 1" :infoData="info"></basic-setting>
        <!-- 字段设计 -->
        <field-setting class="mt20" v-if="activeName == 2" :infoData="info"></field-setting>
        <!-- 表单设计 -->
        <form-design class="mt20" v-if="activeName == 3"></form-design>
        <!-- 列表设计 -->
      
        <list-setting v-if="activeName == 4" ref="listSetting" :infoData="info"></list-setting>
        <!-- 流程设计 -->
        <process-setting class="mt20" v-if="activeName == 5" :infoData="info"></process-setting>
        <!-- 触发器设计 -->
        <event-setting class="mt20" v-if="activeName == 6" :infoData="info"></event-setting>
      </div>
    </el-card>
  </div>
</template>
<script>
import { roterPre } from '@/settings.ts'
import basicSetting from './components/basicSetting'
import fieldSetting from './components/fieldSetting'
import eventSetting from './components/eventSetting'
import processSetting from './components/processSetting'
import listSetting from './components/listSetting'
import { databaseInfoApi } from '@/api/develop.ts'
import formDesign from '@/components/form-designer'
export default {
  components: { basicSetting, fieldSetting, formDesign, processSetting, eventSetting, listSetting },
  data() {
    return {
      activeName: '4',
      infoLoading: false,
      info: {},
      tabArray: [
      { label: '列表设计', value: '4', number: 4 },
        { label: '字段设计', value: '2', number: 2 },
        { label: '表单设计', value: '3', number: 3 },
        
        { label: '流程设计', value: '5', number: 5 },
        { label: '触发器设计', value: '6', number: 6 },
          { label: '实体属性', value: '1', number: 1 },
      ]
    }
  },
  watch: {
    info(val) {
      if (val.crud_id !== 0) {
        this.tabArray.splice(2, 4)
      }
    }
  },
  created() {
    this.id = this.$route.query.id
    this.getInfo()
  },

  mounted() {
    if (this.$route.query.tabIndex) {
      this.activeName = this.$route.query.tabIndex + ''
   if(this.activeName==='4'){
    
   }
    }
  },

  methods: {
    // 返回页面
    backFn() {
      this.$router.push({
        path: `${roterPre}/develop/crud/entityList`,
        query: { cate_id: this.$route.query.cate_id }
      })
    },
    async getInfo() {
      this.infoLoading = true;
      try {
        const data = await databaseInfoApi(this.id || this.$route.query.id)
        this.info = data.data
      } catch (error) {
        this.$message.error(error.msg)
      } finally {
        this.infoLoading = false;
      }
    }
  }
}
</script>
<style scoped lang="scss">
.examineTabs {
  :deep(.el-tabs__nav-wrap::after) {
    background-color: #fff;
  }
  .sp1 {
    width: 16px;
    height: 16px;
    border: 1px solid #999999;
    border-radius: 50%;
    display: inline-block;
    text-align: center;
    line-height: 14px;
    font-size: 12px;
  }
  :deep(.el-tabs__nav-scroll) {
    display: flex;
    justify-content: left;
  }
  :deep(.el-tabs__item) {
    color: #909399;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 10px;
    &.is-active {
      color: #1890ff;
      .sp1 {
        border-color: #1890ff;
        background-color: #1890ff;
        color: #fff;
      }
    }
  }
}
:deep(.el-icon-back) {
  display: none;
}
:deep(.el-tabs__header) {
  margin: 0;
}
:deep(.el-page-header__content) {
  font-size: 16px;
  font-weight: 500;
}
:deep(.el-tabs__active-bar) {
  height: 3px;
}
:deep(.el-page-header) {
  margin-top: 10px;
}
.pt20 {
  padding-top: 20px;
}
.card-box {
  // margin-top: 14px;
  min-height: calc(100vh - 76px);
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
}
.header {
  width: 100%;
  height: 70px;
  display: flex;
  border-bottom: 1px solid #dcdfe6;
  .el-icon-arrow-left {
    color: #606266;
    font-size: 13px;
    margin-right: 10px;
    cursor: pointer;
  }
  .left {
    padding: 11px 0 11px 20px;
    display: flex;
    align-items: center;

    .invoice-logo {
      width: 48px;
      height: 48px;
      background: #1890ff;
      border-radius: 4px;
      line-height: 48px;
      text-align: center;
      .iconshitosheji {
        font-size: 24px;
        color: #fff;
      }
    }
  }
  .right {
    padding: 11px 0 11px 0px;
    margin-left: 13px;
    .title {
      font-family: PingFang SC, PingFang SC;
      font-weight: 500;
      font-size: 17px;
      color: #303133;
      text-align: left;
      font-style: normal;
      text-transform: none;
    }
    .tab {
      :deep(.el-tabs__nav-wrap::after) {
        height: 0;
      }
      :deep(.el-tabs__item) {
        line-height: 37px;
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 13px;
        color: #606266;
      }
      :deep(.el-tabs__item.is-active) {
        color: #1890ff;
      }
    }
  }
}
</style>
