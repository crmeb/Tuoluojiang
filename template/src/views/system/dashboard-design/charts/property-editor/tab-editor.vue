<template>
  <div class="tab-editor mt10">
     <draggable tag="div" :list="optionModel.tabList"
                v-bind="{ group: 'optionsGroup', ghostClass: 'ghost', handle: '.icontuodong' }"
                 @change="emitDefaultValueChange"
                 :item-key="$draggableKey"
               >
                  <template #item="{ element: item, index }">
                    <div class="tab-item">
                       <img src="@/assets/images/del.png" alt="" class="del-icon" @click="removeTab(index)"></img>
                      <div class="left">
                          <span class="iconfont icontuodong iconadd left" title="拖拽排序" />
                      </div>
                      <div class="right">
                          <div class="name mb14">
                     <span style="flex-shrink: 0;">标题</span>
                        <el-input v-model="item.name" placeholder="请输入" class="ml10"></el-input>
                      </div>
                          <div class="name">
                        状态
                       <el-switch v-model="item.status" 
                      class="ml10" :width="80" :active-value="1" :inactive-value="0" active-text="开启"
                    inactive-text="关闭"></el-switch>
                      </div>
                      </div>
                    </div>
                  </template>
                </draggable>
    
       <!-- 添加板块按钮 -->
        <div class="add-btn" @click="addTab"><span class="el-icon-plus"  />添加板块</div>
  </div>
</template>

<script>

import Draggable from 'vuedraggable'
export default {
  name: 'tab-editor',
  components: { Draggable },
  props: {
    designer: Object,
        selectedWidget: Object,
        optionModel: Object
  },
  data() {
    return {
   
    }
  },
  mounted() {
  },
  methods: {
    addTab() {

      this.optionModel.tabList.push({
        name: `选项${this.optionModel.tabList.length + 1}`,
        status: 1,
        value: `tab${this.optionModel.tabList.length + 1}`,
        widgetList: []
      })
    },
    emitDefaultValueChange() {
    },
    removeTab(index) {
      this.optionModel.tabList.splice(index, 1)
    }
  }
}
</script>

<style scoped lang="scss">

  .tab-item {
 width: calc(100% - 8px);
height: 98px;
padding: 15px;
background: #F9F9F9;
border-radius: 4px 4px 4px 4px;
margin-bottom: 15px;
font-family: PingFang SC, PingFang SC;
font-weight: 400;
font-size: 13px;
display: flex;
align-items: center;
position: relative;
    .del-icon {
        width: 18px;
        height: 18px;
        cursor: pointer;
        position: absolute;
        top: -5px;
        right: -5px;
    }
.left {
  width: 30px;
   .iconadd {
      color: #C0C4CC;
      font-size: 18px;
      cursor: move;
    }
}
.name {
  display: flex;
  align-items: center;
}
  
  }
  .add-btn {
    cursor: pointer;
    width: calc(100% - 0px);
    height: 34px;
    border: 1px solid #DCDFE6;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 13px;
    color: #606266;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 14px;
    .el-icon-plus {
        margin-right: 4px;
        line-height: 34px;
    }
   
    
}

</style>