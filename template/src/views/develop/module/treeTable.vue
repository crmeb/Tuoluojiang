<template>
  <div class="customer-management">
  

    <!-- 最终版树形表格（子项必显） -->
    <el-table
      :data="treeTableData"
      :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
      border
      ref="treeTable"
    
      style="width: 100%"
      row-key="id"
      default-expand-all
       @selection-change="handleSelectionChange"
       :span-method="objectSpanMethod"
        :row-class-name="tableRowClassName"
     
    >
      <!-- 1. 展开/收起列（第一列） -->
      <el-table-column width="30" align="center">
        <template #default="scope">
           <div v-if="scope.row.isLoadMore" class="load-more-row">
            <span class="load-more-text" @click="handleLoadMore(scope.row)">加载更多<span class="el-icon-arrow-down" /></span>
            <!-- <div class="divider"></div> -->
          </div>
        
        </template>
      </el-table-column>

      <!-- 2. 选择列 -->
      <el-table-column type="selection" width="55" :selectable="selectable" />

      <!-- 3. 客户名称列（核心列） -->
      <el-table-column :label="headerlist[0]&&headerlist[0].field_name"  width="200">
        <template #default="scope">
          <!-- 分组标题行 -->
          <div v-if="scope.row.isGroup" class="flex" >
           <tableColumn  :item="headerlist[0]" :scope="scope" :info="info" /> <span class="count">{{ scope.row.count }} </span>
          </div>
          
          <!-- 加载更多行 -->
          <!-- <div v-else-if="scope.row.isLoadMore" class="load-more-row">
            <span class="load-more-text" @click="handleLoadMore(scope.row)">加载更多<span class="el-icon-arrow-down" /></span>
            <div class="divider"></div>
          </div> -->
          <!-- 客户子项行 -->
          <div v-else class="customer-item">
              <tableColumn  :item="headerlist[0]" :scope="scope" :info="info" @checkRow="checkRow" />
          </div>
        </template>
      </el-table-column>

      <!-- 4. 其他业务列 -->
       <el-table-column
            v-for="(item, index) in headerlist.slice(1)"
            :prop="item.field_name_en"
            :label="item.field_name"
            :key="index"
            :width="
              [
                'input_percentage',
                'tag',
                'textarea',
                'date_picker',
                'date_time_picker',
                'cascader',
                'cascader_address'
              ].includes(item.form_value)
                ? 200
                : ''
            "
          >
            <template #default="scope">
             
              <!-- 客户子项行 -->
              <tableColumn v-if="!scope.row.isGroup" :item="item" :scope="scope" :info="info" @checkRow="checkRow"></tableColumn>
            </template>
          </el-table-column>
    
      
     <el-table-column prop="address" label="操作" fixed="right" width="130">
            <template #default="scope">
            
              <!-- 客户子项行 -->
              <template v-if="!scope.row.isGroup">
              <el-button class="mr10" type="primary" link @click="checkRow(scope.row)">查看</el-button>
              <el-dropdown>
                <el-button type="primary" link class="el-dropdown-link"> 更多 <i class="el-icon-arrow-down" /></el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item @click="openShare(scope.row)"> 共享协作 </el-dropdown-item>
                    <el-dropdown-item v-if="scope.row.is_share" @click="cancelShare(scope.row)">
                      取消协作
                    </el-dropdown-item>
                    <el-dropdown-item @click="handleDropdown('transfer', scope.row)">
                      移交负责人
                    </el-dropdown-item>
                    <el-dropdown-item @click="deleteRow(scope.row)">删除</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              </template>
            </template>
          </el-table-column>
    </el-table>
  </div>
</template>

<script>
import tableColumn from './components/tableColumn'
export default {
  emits: ['selectionChange', 'checkRow', 'openShare', 'cancelShare', 'handleDropdown', 'deleteRow', 'handleLoadMore'],
  name: 'CustomerTreeTable',
  props: {
    treeData: {
      type: Array,
      default: () => []
    },
    headerFields: {
      type: Array,
      default: () => []
    },
    group: {
      type: String,
      default: ''
    },
    info: {
      type: Object,
      default: () => ({})
    }
  },
  components: {
    tableColumn
  },
  data() {
    return {
      activeData: {},
      headerlist: [],
      treeTableData: []
    }
  },
  watch: {
    treeData: {
      handler(newVal, oldVal) {
      this.formatData(newVal)
      },
      deep: true
    },
    group: {
      handler(newVal) {
        // 将 field_name_en 等于 group 的字段排到第一个
         this.getGroupField()
      },
      deep: true
    }
  },
  mounted() {
    if(this.group){
  this.getGroupField()
    }
  
  },

  methods: {
     // 单元格合并方法
    objectSpanMethod({ row, column, rowIndex, columnIndex }) {
      if (row.isLoadMore) {
        if (columnIndex === 0) {
          // 对于第一列，合并所有列
          return {
            rowspan: 1,
            colspan: 999 // 使用一个较大的值，确保合并所有列
          };
        }
        else {
          // 对于其他列，返回false，确保它们被隐藏
          return {
            rowspan: 0,
            colspan: 0
          };
        }
      }
    },
    tableRowClassName({ row, rowIndex }) {
      if (row.isLoadMore) {
        return 'load-more-row'
      }
      return ''
    },
    getGroupField(){
   const groupField = this.headerFields.find(item => item.field_name_en === this.group);
        if (groupField) {
          const others = this.headerFields.filter(item => item.field_name_en !== this.group);
          this.headerlist = [groupField, ...others]
        }
    },
    
    formatData(data) {
      this.treeTableData = data.map(item => {
        const children = (item.children || []).map(child => ({
          ...child,
          isGroup: false
        }))
        
        // 当子级数据长度不等于父级 count 时，在子级数据末尾添加加载更多行
        if (item.count && children.length < item.count) {
          children.push({
            id: `load_more_${item.id}`,
            isGroup: false,
            isLoadMore: true,
            parentId: item.id
          })
        }
        
        return {
          ...item,
          id: `group_${item.id}`, 
          isGroup: true,
          children
        }
      })
    
    },
     // 控制哪些行可以被选择
    selectable(row, index) {
      // 加载更多行不可被选择
      if (row.isLoadMore) {
        return false
      }
      // 允许其他行都可以被选择
      return true
    },

    // 处理选择变化
    handleSelectionChange(selection) {
      // 使用防抖锁，避免循环触发
      if (this._updatingSelection) return
      this._updatingSelection = true

      // 获取当前选中的父级行
      const selectedParents = selection.filter(item => item.isGroup)
      // 获取当前选中的子级行
      const selectedChildren = selection.filter(item => !item.isGroup)

      // 处理父级勾选：如果父级被勾选，自动勾选所有子级
      const allRowsToSelect = [...selectedChildren]
      selectedParents.forEach(parent => {
        if (parent.children && parent.children.length > 0) {
          parent.children.forEach(child => {
            if (!allRowsToSelect.find(row => row.id === child.id)) {
              allRowsToSelect.push(child)
            }
          })
        }
      })

      // 处理子级勾选：更新父级勾选状态
      this.treeTableData.forEach(parent => {
        if (parent.children && parent.children.length > 0) {
          // 检查该父级的子级是否全部被选中
          const allChildrenSelected = parent.children.every(child => 
            allRowsToSelect.find(row => row.id === child.id)
          )
          // 检查该父级的子级是否有被选中的
          const hasSelectedChildren = parent.children.some(child => 
            allRowsToSelect.find(row => row.id === child.id)
          )
          
          // 如果有子级被选中，则父级也应该被选中
          if (hasSelectedChildren) {
            if (!allRowsToSelect.find(row => row.id === parent.id)) {
              allRowsToSelect.push(parent)
            }
          } else {
            // 如果没有子级被选中，则父级也不应该被选中
            const parentIndex = allRowsToSelect.findIndex(row => row.id === parent.id)
            if (parentIndex > -1) {
              allRowsToSelect.splice(parentIndex, 1)
            }
          }
        }
      })

      // 去重
      const uniqueRows = allRowsToSelect.filter(
        (row, idx, arr) => arr.findIndex(r => r.id === row.id) === idx
      )

      // 通知父组件（只传递子级行）
      const childrenToEmit = uniqueRows.filter(item => !item.isGroup)
      this.$emit('selectionChange', childrenToEmit)

      // 同步表格勾选状态
      this.$nextTick(() => {
        this.$refs.treeTable.clearSelection()
        uniqueRows.forEach(row => {
          this.$refs.treeTable.toggleRowSelection(row, true)
        })
        // 解锁
        this._updatingSelection = false
      })
    },

  checkRow(row) {
   this.$emit('checkRow', row)
  },
  openShare(row) {
    this.$emit('openShare', row)
  },
  cancelShare(row) {
    this.$emit('cancelShare', row)
  },
  handleDropdown(action, row) {
    this.$emit('handleDropdown', action, row)
  },
  deleteRow(row) {
    this.$emit('deleteRow', row)
  },

   
    // 加载更多
    handleLoadMore(row) {
      // 如果点击的是加载更多行，则找到对应的父级数据
        const parent = this.treeTableData.find(item => item.id === `group_${row.parentId}`)
        if (parent) {
          let val = ''
          if(typeof parent[this.group] === 'object' && parent[this.group] !== null){
            val = parent[this.group].name

          }else {
            val = parent[this.group]
          }
         
          this.$emit('handleLoadMore', parent,val)
        }
      
    },
    
 
  
  }
}
</script>

<style scoped lang="scss">
.count {
 font-weight: 400;
font-size: 13px;
color: #909399;
margin-left: 20px;
}
:deep(.load-more-row) {
  .el-table__cell{
    padding: 0 !important;
  }
   .el-table__cell:first-child .cell {
    padding-left: 0 !important;
   }
 .cell {
    line-height: 8px !important;
   }

}



/* 加载更多行样式 */
.load-more-row {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  /* 分割线样式 */
// .divider {
//   width: 100% !important;
//   height: 15px;
//   background-color: #EBEEF5;
//   margin-top: 10px;
// }

 .load-more-text {
font-family: PingFang SC, PingFang SC;
font-weight: 400;
font-size: 13px;
color:#9E9E9E;
cursor: pointer;
display: flex;
align-items: center;
}
}



</style>