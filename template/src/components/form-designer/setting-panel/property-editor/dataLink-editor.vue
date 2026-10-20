<template>
  <div>
    <el-form-item label-width="0">
        <div class="display-center mb14">
            <el-button  @click="openFormCssEditor()">设置数据联动</el-button>
            <el-button  @click="openFormCssEditor()">设置隐藏条件</el-button>
        </div>
    </el-form-item>
    <conditionGroupDialog ref="conditionGroup"></conditionGroupDialog>
  </div>
  </template>
  
  <script>
    import i18n from "@/utils/i18n.ts";
    import {deepClone} from "@/utils/util";
  import conditionGroupDialog from '@/components/develop/conditionGroupDialog'
    export default {
      name: "dataLink-editor",
      components: {conditionGroupDialog},
      mixins: [i18n],
      props: {
        designer: Object,
        selectedWidget: Object,
        optionModel: Object,

      },
      data() {
        return {
          widgetList:[],  // 表单字段值列表
        }
      },
      created() {
      this.getWidgetList()
      },
      
      methods: {
        getWidgetList() {
          this.widgetList = []
         if(this.designer.widgetList.length>0){
         this.designer.widgetList.map((item,index)=>{
          if(item.type==='grid'){
            if(item.cols.length>0){
              item.cols.map((item2)=>{
                this.widgetList=[...this.widgetList,...item2.widgetList]
              })
            }
          } else if(item.type==='grid-col'){
            this.widgetList=[...this.widgetList,...item.widgetList]
          }else if(item.type==='tab'){
            if(item.tabs.length>0){
              item.tabs.map((item2)=>{
                this.widgetList=[...this.widgetList,...item2.widgetList]
              })
            }
          } else if(item.type==='card'){
            this.widgetList=[...this.widgetList,...item.widgetList]
          } else {
            this.widgetList=[...this.widgetList,item]
          }
           
         })
         }
        },
openFormCssEditor() {
   this.getWidgetList()
  if (this.$refs.conditionGroup) {
    // 检查 $refs.conditionGroup 是否为数组
    if (Array.isArray(this.$refs.conditionGroup)) {
      this.$refs.conditionGroup[0].openBox(this.widgetList);
    } else {
      // 若不是数组，直接调用 openBox 方法
      this.$refs.conditionGroup.openBox(this.widgetList);
    }
  }
}
      }
    }
  </script>
  
  <style scoped>
  
  </style>
