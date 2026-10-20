<template>
  <div>
    <el-drawer title="列表属性" v-model="drawerVisible" direction="rtl" :size="500">
      <el-form label-width="auto" label-position="top" class="p20">
        <!-- 表格类型切换 -->
        <el-form-item>
          <div class="flex" style="gap: 20px">
            <div class="flex-column display-align">
              <div class="row-box" :class="{ active: tableStyle === 1 }" @click="tableStyle = 1">
                <el-row v-for="index in 4" :key="index">
                  <el-col :span="24" class="bg-purple" />
                </el-row>
              </div>
              <div>普通表格</div>
            </div>
            <div class="flex-column display-align">
              <div class="row-box" :class="{ active: tableStyle === 2 }" @click="tableStyle = 2">
                <el-row>
                  <el-col :span="24" class="bg-purple" />
                </el-row>
                <el-row v-for="index in 3" :key="index" justify="end">
                  <el-col :span="20" class="bg-purple" />
                </el-row>
              </div>
              <div>树形表格</div>
            </div>
          </div>
        </el-form-item>

        <!-- 按钮配置区域 -->
        <el-divider content-position="center">按钮配置</el-divider>
        <el-form-item>
          <el-checkbox-group v-model="checkedButtons" class="check-box">
            <el-checkbox value="批量共享协作">
              批量共享协作
              <el-input v-model="value" class="ml20" />
            </el-checkbox>
            <el-checkbox value="批量移交">批量移交</el-checkbox>
            <el-checkbox value="批量编辑">批量编辑</el-checkbox>
            <el-checkbox value="批量删除">批量删除</el-checkbox>
            <el-checkbox value="邀请填写">邀请填写</el-checkbox>
            <el-checkbox value="邀请链接填写">邀请链接填写</el-checkbox>
            <el-checkbox value="数据导入">数据导入</el-checkbox>
            <el-checkbox value="数据导出">数据导出</el-checkbox>
            <el-checkbox value="筛选条件设置">筛选条件设置</el-checkbox>
            <el-checkbox value="表头显示设置">表头显示设置</el-checkbox>
            <el-checkbox value="详情tab设置">详情tab设置</el-checkbox>
          </el-checkbox-group>
        </el-form-item>

        <!-- 自定义按钮区域 -->
        <el-form-item>
          <template #label>
            自定义按钮区
            <el-tooltip effect="dark" content="自定义添加按钮及按钮事件设置" placement="top-start">
              <span class="el-icon-question" />
            </el-tooltip>
          </template>
          <draggable
            v-model="customButtons"
            force-fallback="true"
            group="people"
            animation="1000"
            handle=".drag-handle"
            @end="onDragEnd"
            :item-key="$draggableKey"
          >
            <template #item="{ element: item, index }">
              <div>
                <div class="flex mb10">
                  <span class="drag-handle iconfont icontuodong" />
                  <span class="mr20">按钮名称</span>
                  <el-input
                    v-model="item.name"
                    placeholder="按钮名称"
                    class="refresh-input"
                    style="width: 313px; margin-right: 10px"
                  >
                    <template #suffix>
                      <div class="refresh" @click="openBottonFn">
                        <span class="iconfont iconhulianwang" />
                      </div>
                    </template>
                  </el-input>
                  <el-button
                    type="primary" link
                    icon="el-icon-delete"
                    @click="handleDeleteCustomBtn(index)"
                    style="margin-left: 8px"
                  />
                </div>
              </div>
            </template>
          </draggable>
          <el-button type="primary" link icon="el-icon-plus" @click="handleAddCustomBtn"> 添加按钮 </el-button>
        </el-form-item>

        <!-- 底部操作按钮 -->
        <div class="button from-foot-btn fix btn-shadow">
          <el-button @click="drawerVisible = false">取消</el-button>
          <el-button type="primary" @click="handleConfirm">确定</el-button>
        </div>
      </el-form>
    </el-drawer>
    <!-- 自定义按钮配置 -->
    <buttonDialog ref="buttonDialog"></buttonDialog>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import buttonDialog from './buttonDialog'
export default {
  name: 'DrawerDialog',
  components: {
    draggable,
    buttonDialog
  },
  data() {
    return {
      drawerVisible: false,
      value: '',
      checkedButtons: [],
      tableStyle: 1,
      customButtons: [{ name: '' }, { name: '' }]
    }
  },
  methods: {
    openBox(info) {
      this.drawerVisible = true
    },
    onDragEnd() {},
    handleAddCustomBtn() {
      this.customButtons.push({ name: '' })
    },
    handleDeleteCustomBtn(index) {
      this.customButtons.splice(index, 1)
    },
    openBottonFn() {
      this.$refs.buttonDialog.openBox()
    },
    handleConfirm() {
      this.drawerVisible = false
    }
  }
}
</script>

<style scoped lang="scss">
.check-box {
  display: flex;
  flex-direction: column;
}

.row-box {
  cursor: pointer;
  width: 126px;
  padding: 12px 14px;
  padding-bottom: 6px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
}

.p20 {
  padding: 20px;
}

.bg-purple {
  height: 6px;
  background: #dcdfe6;
  margin-bottom: 8px;
}

.active {
  border: 1px solid #1890ff;
}

:deep(.el-checkbox) {
}

.refresh {
  cursor: pointer;
  color: #ffffff;
  width: 46px;
  height: 32px;
  background: #1890ff;
  border-radius: 0 4px 4px 0;
  border: 1px solid #d9d9d9;
}

.refresh-input {
  :deep(.el-input__suffix) {
    position: absolute;
    right: 0;
  }
}

.icontuodong {
  cursor: move;
  font-size: 13px;
  color: #909399;
  margin-right: 14px;
}

.el-icon-question {
  color: #909399;
}
</style>
