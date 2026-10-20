<template>
    <div v-if="optionModel">
        <!-- 1. 选择需要筛选的图表 -->
        <div class="section-title">选择需要筛选的图表</div>
        <el-checkbox-group v-model="optionModel.entityIds" >
            <el-checkbox v-for="(item, index) in chartList" :key="index" :value="item.value"
                :disabled="getDisabled(item)||!item.dataEntity" style="display: block; margin: 8px 0;">
                {{ item.label }} 
            </el-checkbox>
        </el-checkbox-group>
        <!-- 选择筛选字段 -->
        <div class="config-section mb14" v-for="(item, index) in optionModel.searchList" :key="index">
            <img src="@/assets/images/del.png" alt="" class="del-icon" @click="removeSearch(index)">
            <div class="section-title">选择筛选字段</div>
            <div class="mb10 mt10">未命名表单</div>
            <el-select v-model="item.field_name_en" placeholder="请选择" style="width: 100%;" @change="changeField($event,item)">
                <el-option v-for="value in options" :key="value.field" :label="value.title" :value="value.field"></el-option>
            </el-select>
            <div class="mb10 mt10">名称</div>
            <el-input v-model="item.field_name" placeholder="请输入"></el-input>

            <div class="mb10 mt10">默认值</div>
             <fieldComponent v-if="item.field_name_en" :type="`dashboard`" :item="item" :index="index" :list="optionModel.searchList" :noRule="false" ></fieldComponent>
        </div>
        <!-- 添加板块按钮 -->
        <div class="add-btn" @click="addSearch"><span class="el-icon-plus"  />添加板块</div>
    </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
    import { viewSearchApi } from '@/api/develop.ts'
export default {
    name: "ChartFilterConfig",
    components: {
        fieldComponent: defineAsyncComponent(() => import('@/components/develop/fieldComponent')),
    },
    props: {
        designer: Object,
        selectedWidget: Object,
        optionModel: Object,
      
    },
    data() {
        return {
            // 图表列表（模拟数据）
            chartList: [],
            options:[],
            id:'',
            member: [
        'user_id',
        'update_user_id',
        'owner_user_id',
        'check_uid',
        'card_id',
        'creator',
        'salesman',
        'before_salesman'
      ],
        };
    },
    watch: {
        'selectedWidget':{
            handler(newVal, oldVal) {
                if (newVal) {
                this.getChartList()
                }
            }
        },
        'optionModel.entityIds': {
            deep: !0,
            handler: function handler(newVal, oldVal) {
                // 仅当第一个值发生变化时再调接口
                if (newVal.length > 0 && newVal[0] !== (oldVal && oldVal[0])) {
                    // this.optionModel.searchList = []
                    const item = this.chartList.find(el => el.value === newVal[0]);
                    if (item) {
                        this.id = item.dataEntity;
                        this.getvalue(this.id);
                    }
                }
            }
        },
    },
    mounted() {
        // 清空并重置图表列表
     this.getChartList()
    },
    methods: {

getChartList() {
  this.chartList = [];
  // 统一收集非 search 组件
  const collectWidget = (widget,type) => {
    if (widget.type !== 'search') {
      this.chartList.push({
        type: widget.type,
        value: widget.id,
        dataEntity: widget.options.dataEntity,
        label: widget.options.label
      });
    }
  };
  // 遍历所有容器
  this.designer.widgetList.forEach(container =>
    container.widgetList.forEach(el => {
      // tab 类型：仅收集当前激活页下的组件

      if (el.type === 'tab') {
        
        const activeTab = el.options.tabList.find(t => t.value === el.options.activeValue);
        (activeTab?.widgetList || []).forEach(collectWidget);
      } 

      else if (el.type !== 'tab' && el.type !== 'search'&&!this.designer.isTabSelected) {
        collectWidget(el,1);
      }
    })
  );

  // 若已选中图表，自动拉取字段
  if (this.optionModel.entityIds.length && this.chartList.length) {
    const firstId = this.optionModel.entityIds[0];
    const target = this.chartList.find(c => c.value === firstId);
    if (target) this.getvalue(target.dataEntity);
  }
},
        // 添加板块
        addBlock() {
            this.$message.success("已添加新筛选板块");
            // 实际场景可在此处追加配置项
        },
        removeSearch(index){
            this.optionModel.searchList.splice(index, 1)
        },
        addSearch(){
            this.optionModel.searchList.push({
                name: '新筛选板块',
                entityIds: [],
                field: 'submitter',
                defaultValue: ''
            })
        },
        changeField(e,item){
            const val = this.options.find(el => el.field === e) || {};
         item.field_name = val.title
         if(val.type==='date_time_picker'){
            val.type='date_picker'
         }
         if(['frame_id', 'frame'].includes(val.field)){
            item.category=1
         }
         if(this.member.includes(val.field)){
            item.category=2
         }
          item.form_value = val.type
         let input = ['input','textarea','rich_text']
         let number =['input_number','input_float','input_percentage','input_price']
         if(input.includes(val.type)){
            item.form_value='input'
         }
         if(number.includes(val.type)){
            item.form_value='number'
         }
        
         item.type = val.type
         item.data_dict = val.options
         item.options = val.options
         item.id = val.id
         item.crud_id = val.crud_id
        },
    // 获取实体字段
    getvalue(id) {
      viewSearchApi(id).then((res) => {
        if (res.status === 200) {
          this.options = res.data
        }
      })
    },
        getDisabled(item) {
            // 已选图表的 dataEntity，用于限制后续只能选同实体
            let firstDataEntity = '';
            if (this.optionModel?.entityIds?.length) {
                const first = this.chartList.find(el => el.value === this.optionModel.entityIds[0]);
                firstDataEntity = first ? first.dataEntity : '';
            }
            // 当已有选中项时，不同 dataEntity 的项禁用
            return (
                this.optionModel?.entityIds?.length > 0 &&
                item.value &&
                item.dataEntity !== firstDataEntity
            );
        }
    }
};
</script>

<style scoped>
.config-section {
    width: calc(100% - 8px);
    background-color: #F9F9F9;
    padding: 15px;
    border-radius: 6px 6px 6px 6px;
    position: relative;
    .del-icon {
        width: 18px;
        height: 18px;
        cursor: pointer;
        position: absolute;
        top: -5px;
        right: -5px;
    }
}

.section-title {
    font-family: PingFang SC, PingFang SC;
    font-weight: 500;
    font-size: 12px;
    color: #303133;
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