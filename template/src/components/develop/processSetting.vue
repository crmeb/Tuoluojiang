<!-- @FileDescription: 低代码-流程设置组件-->
<template>
  <div class="processData">
    <div class="fd-nav-content">
      <section class="dingflow-design">
        <div class="zoom">
          <div :class="'zoom-out' + (nowVal == 50 ? ' disabled' : '')" @click="zoomSize(1)"></div>
          <span>{{ nowVal }}%</span>
          <div :class="'zoom-in' + (nowVal == 300 ? ' disabled' : '')" @click="zoomSize(2)"></div>
        </div>
        <div
          id="box-scale"
          class="box-scale"
          :style="'transform: scale(' + nowVal / 100 + '); transform-origin: 50% 0px 0px;'"
        >
          <nodeWrap
            v-model:node-config="nodeConfig"
            v-model:flow-permission="flowPermission"
            v-model:is-tried="isTried"
            :table-id="tableId"
            :type-str="lowCode"
          ></nodeWrap>
          <div class="end-node">
            <div class="end-node-circle"></div>
            <div class="end-node-text">流程结束</div>
          </div>
        </div>
      </section>
    </div>
    <errorDialog v-model:visible="tipVisible" :list="tipList" />
    <promoter-drawer />
    <approver-drawer :type-str="lowCode" :director-max-level="directorMaxLevel" />
    <copyer-drawer :type-str="lowCode" :director-max-level="directorMaxLevel" />
    <!-- 条件设置 -->
    <el-drawer
      :append-to-body="true"
      title="条件设置"
      v-model="conditionDialogStore.visible"
      :close-on-click-modal="false"
      direction="rtl"
      class="condition_copyer"
      size="650px"
    >
      <condition-dialog :id="curd_id" v-if="conditionDialogStore.visible" />
    </el-drawer>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useConditionDialogStore } from '@/stores/conditionDialog.ts'
import { defineAsyncComponent } from 'vue'
import func from '@/utils/preload.ts'
export default {
  name: 'ProcessData',
  components: {
    promoterDrawer: defineAsyncComponent(() => import('@/components/workFlow/drawer/promoterDrawer')),
    approverDrawer: defineAsyncComponent(() => import('@/components/workFlow/drawer/approverDrawer')),
    copyerDrawer: defineAsyncComponent(() => import('@/components/workFlow/drawer/copyerDrawer')),
    conditionDialog: defineAsyncComponent(() => import('@/components/develop/conditionDialog')),
    errorDialog: defineAsyncComponent(() => import('@/components/workFlow/dialog/errorDialog'))
  },
  props: {
    tabName: {
      type: String,
      default: ''
    },
    id: {
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
    ...mapStores(useConditionDialogStore)
  },
  data() {
    return {
      tipList: [],
      curd_id: '', // 实体id
      tipVisible: false,
      nowVal: 100,
      lowCode: 'lowCode',
      directorMaxLevel: 10,
      nodeConfig: {
        nodeName: '申请人',
        type: 0,
        onlyValue: func.onlyValue(),
        priorityLevel: '',
        settype: '',
        selectMode: '',
        selectRange: '',
        directorLevel: '',
        examineMode: '',
        noHanderAction: '',
        examineEndDirectorLevel: '',
        ccSelfSelectFlag: '',
        conditionList: [],
        nodeUserList: {
          depList: [],
          userList: []
        },
        childNode: {
          nodeName: '审核人',
          error: false,
          type: '1',
          onlyValue: func.onlyValue(),
          settype: '2',
          selectMode: '0',
          selectRange: '0',
          directorLevel: '1',
          directorOrder: '1',
          examineMode: '3',
          noHanderAction: '2',
          examineEndDirectorLevel: '0',
          childNode: {
            nodeName: '抄送人',
            type: 2,
            onlyValue: func.onlyValue(),
            ccSelfSelectFlag: '1',
            childNode: null,
            departmentHead: [],
            nodeUserList: [],
            error: false
          },
          nodeUserList: []
        },
        conditionNodes: []
      },
      flowPermission: {
        depList: [],
        userList: []
      },
      processConfig: {},
      isTried: false,
      tableId: '999'
    }
  },

  watch: {
    conf: {
      handler(val) {
        this.nodeConfig = val
        this.flowPermission = this.nodeConfig.nodeUserList
        this.getData()
      },
      deep: true
    },
    id: {
      handler(val) {
        if (val) {
          this.curd_id = val
        }
      }
    }
  },
  methods: {
    reErr({ childNode }) {
      if (childNode) {
        const { type, error, nodeName, conditionNodes } = childNode
        if (type == 1 || type == 2) {
          if (error) {
            this.tipList.push({ name: nodeName, types: 1, type: ['', '审核人', '抄送人'][type] })
          }
          this.reErr(childNode)
        } else if (type == 3) {
          this.reErr(childNode)
        } else if (type == 4) {
          this.reErr(childNode)
          for (var i = 0; i < conditionNodes.length; i++) {
            if (conditionNodes[i].error) {
              this.tipList.push({ name: conditionNodes[i].nodeName, types: 1, type: ' 条件' })
            }
            if (!conditionNodes[i].childNode) {
              this.tipList.push({ name: conditionNodes[i].nodeName, types: 2, type: ' 审批人或抄送人' })
            }
            this.reErr(conditionNodes[i])
          }
        }
      } else {
        childNode = null
      }
    },
    getData() {
      return new Promise((resolve, reject) => {
        this.isTried = true
        this.tipList = []
        if (!this.nodeConfig.childNode) {
          this.tipList.push({ name: '', types: 2, type: ' 审批人或抄送人' })
        }
        this.reErr(this.nodeConfig)
        this.nodeConfig.nodeUserList = this.flowPermission

        resolve(this.nodeConfig)
      })
    },
    zoomSize(type) {
      if (type == 1) {
        if (this.nowVal === 50) {
          return
        }
        this.nowVal -= 10
      } else {
        if (this.nowVal === 300) {
          return
        }
        this.nowVal += 10
      }
    }
  }
}
</script>
<style src="@/styles/workflow.css"></style>
<style lang="scss" scoped>
.processData {
  height: 100%;
  .fd-nav-content {
    height: calc(100vh - 130px);
    overflow: auto;

    &::-webkit-scrollbar-thumb {
      -webkit-box-shadow: inset 0 0 6px #ccc;
    }
    &::-webkit-scrollbar {
      width: 4px !important; /*对垂直流动条有效*/
    }
  }
}
.condition_copyer {
  .el-drawer__body {
    .priority_level {
      background: rgba(255, 255, 255, 1);
      border-radius: 4px;
      border: 1px solid rgba(217, 217, 217, 1);
    }
  }
  .condition_content {
    padding: 20px 20px 0;
    :deep(.el-input-number),
    :deep(.el-select) {
      width: 100%;
    }
    :deep(.el-input__inner) {
      text-align: left;
    }
  }
}
.box-scale {
  padding-top: 30px;
}
</style>
