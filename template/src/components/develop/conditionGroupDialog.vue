<template>
  <div>
    <el-dialog
      top="10%"
      :visible.sync="dialogVisible"
      width="80%"
      :show-close="false"
      :append-to-body="true"
      title="数据联动"
      :close-on-click-modal="false"
    >
      <div class="title">设置条件</div>
      <div class="condition-box">
        <ConditionGroup :item="rootCondition" :widgetList="widgetList" :on-remove="() => {}" />
      </div>
      <div class="title mt30">触发以下触动</div>
      <div class="box mt14">
        当前组件联动显示
        <el-select v-model="value" class="mlr6">
          <el-option label="隐藏" value="hide"></el-option>
          <el-option label="禁用" value="disable"></el-option>
        </el-select>
        的值
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button size="small" @click="handleCancel">取消</el-button>
          <el-button size="small" type="primary" @click="handleCreate">创建</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import ConditionGroup from './ConditionGroup'

export default {
  name: 'conditionGroupDialog',
  components: {
    ConditionGroup
  },
  props: {},
  data() {
    return {
      dialogVisible: false,
      options: [],
      widgetList: [],
      value: '0',
      rootCondition: {
        type: 'group',
        relation: 'AND',
        children: [{ type: 'condition', field: '', operator: '' }]
      }
    }
  },
  methods: {
    openBox(list) {
      this.widgetList = list
      this.dialogVisible = true
    },
    handleCancel() {
      this.dialogVisible = false
    },
    handleCreate() {
      // 这里添加创建逻辑
      this.dialogVisible = false
    }
  }
}
</script>

<style scoped lang="scss">
.title {
  position: relative;
  font-family: PingFang SC;
  font-weight: 500;
  font-size: 13px;
  color: #303133;
}

.title::before {
  content: '';
  background-color: #1890ff;
  width: 3px;
  height: 14px;
  position: absolute;
  left: -10px;
  top: 50%;
  margin-top: -7px;
}

.box {
  display: flex;
  align-items: center;
  font-family: PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
}

.mlr6 {
  margin: 0 6px;
}
.condition-box {
  width: 100%;
  height: 500px;
  overflow-y: auto;
}
</style>
