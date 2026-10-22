<template>
  <div class="divBox">
    <el-card :body-style="{ padding: 0 }">
      <el-tabs v-model="activeName" @tab-click="handleClick">
        <el-tab-pane
          v-for="(item, index) in tabData"
          :id="item.key"
          :key="index"
          :label="item.label"
          :name="item.key"
        ></el-tab-pane>
      </el-tabs>

      <!-- 跟进规则 -->
      <followRules
        v-if="activeName === 'customer_follow_config'"
        ref="followDataRef"
        :formData="customer_follow_config"
        @followData="followData"
        @saveEvt="saveEvt"
      ></followRules>
      <returnRule
        v-if="activeName === 'customer_sea_config'"
        ref="returnDataRef"
        :formData="customer_sea_config"
        @returnData="returnData"
        @saveEvt="saveEvt"
      ></returnRule>
      <cluePoolConfig
        v-if="activeName === 'clue_pool_config'"
        ref="approvalDataRef"
        :formData="clue_pool_config"
        @saveEvt="saveEvt"
      ></cluePoolConfig>

      <approvalRules
        v-if="activeName === 'customer_approve_config'"
        ref="approvalDataRef"
        :fromData="tabRuleData"
        @approvalData="approvalData"
      ></approvalRules>

      <moduleConfig
        v-if="activeName === 'customer_module_config'"
        :formData="customer_module_config"
        @saveEvt="saveEvt"
      ></moduleConfig>

      <outboundConfig
        v-if="activeName === 'customer_outbound_config'"
        :formData="customer_outbound_config"
        @saveEvt="saveEvt"
      ></outboundConfig>
    </el-card>
  </div>
</template>
<script>
import { configRuleCatApi, saveClientRuleApi, clientRuleInfoApi, configRuleApproveApi } from '@/api/config'
import { getMenus } from '@/utils/menu'
import { broadcastMenuInvalidated } from '@/utils/menu-sync'
export default {
  name: 'RuleSettings',
  components: {
    followRules: () => import('./components/followRules'),
    returnRule: () => import('./components/returnRule'),
    cluePoolConfig: () => import('./components/cluePoolConfig'),
    approvalRules: () => import('./components/approvalRules'),
    moduleConfig: () => import('./components/moduleConfig'),
    outboundConfig: () => import('./components/outboundConfig')
  },
  props: {},
  data() {
    return {
      grid1: {
        xl: 2,
        lg: 4,
        md: 2,
        sm: 24,
        xs: 24
      },
      grid2: {
        xl: 20,
        lg: 18,
        md: 20,
        sm: 24,
        xs: 24
      },
      activeName: '',
      tabData: [],
      tabRuleData: [],
      cate_id: '',
      customer_follow_config: {
        follow_up_status: []
      },
      customer_sea_config: {},
      clue_pool_config: {
        clue_follow_date: 3,
        clue_follow_switch: 0,
        clue_policy_count: 0,
        clue_policy_switch: 0,
        return_clue_cycle: 30,
        return_clue_date: 7,
        return_clue_remind: 5,
        return_clue_switch: 0
      },
      customer_approve_config: {},
      customer_module_config: {},
      customer_outbound_config: {
        order_auto_outbound_rule: 0,
        order_delivery_rule: 2
      }
    }
  },
  created() {
    this.getconfigRuleCat()
    this.configRuleApprove()
  },
  mounted() {},
  methods: {
    handleClick(tab) {
      this.cate_id = tab.$attrs.id
      this.activeName = tab.$attrs.id
      this.getclientRuleInfo(this.cate_id)
    },
    async configRuleApprove() {
      const result = await configRuleApproveApi(1)
      this.tabRuleData = result.data
    },
    async getconfigRuleCat() {
      const result = await configRuleCatApi()
      this.tabData = result.data
      this.activeName = this.tabData[0].key
      await this.getclientRuleInfo(this.tabData[0].key)
    },
    async saveEvt() {
      if (this.activeName === 'customer_follow_config') {
        this.$refs.followDataRef.$refs.elForm.validate((valid) => {
          if (valid) {
            this.$refs.followDataRef.sendFollowData()
            this.saveData()
          } else {
            return false
          }
        })
      } else if (this.activeName === 'customer_sea_config') {
        this.$refs.returnDataRef.$refs.elForm.validate((valid) => {
          if (valid) {
            this.$refs.returnDataRef.sendReturnData()
            this.saveData()
          } else {
            return false
          }
        })
      } else if (this.activeName === 'customer_approve_config') {
        this.$refs.approvalDataRef.sendApprovalData()
        await this.saveData()
      } else if (this.activeName === 'clue_pool_config') {
        await this.saveData()
      } else if (this.activeName === 'customer_module_config') {
        await this.saveData()
      } else if (this.activeName === 'customer_outbound_config') {
        await this.saveData()
      }
    },
    async saveData() {
      await saveClientRuleApi(this.cate_id, this[this.activeName])
      if (this.activeName === 'customer_module_config') {
        // 模块开关变更后立即重建菜单与路由，并通知本浏览器的其他标签页
        // 菜单被清空时 getMenus 内部已完成登出跳转，这里不再向上抛出打断保存流程
        try {
          await getMenus({ checkCurrentRoute: true })
          broadcastMenuInvalidated()
        } catch (e) {
          console.warn('[ruleSettings] 模块开关保存后刷新菜单失败', e)
        }
      }
    },
    async getclientRuleInfo(id) {
      this.cate_id = id ? id : this.cate_id
      const result = await clientRuleInfoApi(this.cate_id)
      this[this.activeName] = result.data
    },
    followData(data) {
      this.followUpConfig = data
    },
    returnData(data) {
      this.highSeasConfig = data
    },
    approvalData(data) {
      this.approveConfig = data
    }
  }
}
</script>
<style lang="scss" scoped>
.divBox {
  min-height: calc(100vh - 92px);
}
.btn {
  display: block;
  text-align: left;
  // display: flex;
  // justify-content: center;
}
::v-deep .el-form-item__label {
  line-height: 38px;
}
::v-deep .el-tabs__header {
  margin: 0;
  .el-tabs__item {
    height: 60px;
    line-height: 60px;
  }
  .el-tabs__nav {
    margin-left: 20px;
  }
}
</style>
