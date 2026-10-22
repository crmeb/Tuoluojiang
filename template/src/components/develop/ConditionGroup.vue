<!-- @FileDescription: 低代码-表单设计-数据联动组件-->
<template>
  <div class="condition-group-wrapper">
    <!-- 条件组头部（关系选择 + 操作按钮） -->

    <div class="condition-group">
      <div class="condition-group-header" v-if="item.type === 'group'">
        <div class="condition-group-left">
          <el-select v-model="item.relation" size="small" placeholder="请选择" class="center-select first-select">
            <el-option v-for="opt in relationOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
          </el-select>
        </div>

        <div class="condition-group-con">
          <div
            v-for="(child, index) in item.children"
            :key="index"
            class="condition-item"
            :data-group="child.type === 'group' ? 'group' : ''"
          >
            <span class="el-icon-circle-close" v-if="child.type === 'group'" @click="deleteFn(child, index)" />
            <!-- 普通条件 -->
            <div class="field-row mb14" v-if="child.type === 'condition'">
              <el-select
                v-model="child.field"
                placeholder="请选择字段类型"
                size="small"
                style="width: 80px"
                class="field-select"
              >
                <el-option v-for="opt in fieldOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
              </el-select>

              <template>
                <el-input
                  v-if="child.field == 1"
                  v-model="child.value"
                  placeholder="请输入值"
                  size="small"
                  style="width: 210px"
                />
                <el-select
                  v-if="child.field == 2"
                  v-model="child.dataKey"
                  placeholder="请选择"
                  size="small"
                  class="operator-select"
                >
                  <el-option v-for="opt in widgetList" :key="opt.id" :label="opt.name" :value="opt.id" />
                </el-select>
              </template>

              <!-- 条件 -->
              <el-select
                v-if="child.field == 2 && child.dataKey"
                v-model="child.operator"
                placeholder="请选择条件"
                size="small"
                style="width: 120px"
              >
                <el-option v-for="opt in operatorOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
              </el-select>

              <!-- 字段 -->

              <template v-if="child.operator">
                <el-input
                  v-if="!item.checked"
                  v-model="child.value"
                  placeholder="请输入值"
                  size="small"
                  style="width: 210px"
                />

                <el-select
                  v-if="item.checked"
                  v-model="child.value"
                  placeholder="请选择"
                  size="small"
                  class="operator-select"
                >
                  <el-option v-for="opt in widgetList" :key="opt.id" :label="opt.name" :value="opt.id" />
                </el-select>
                <el-checkbox v-model="item.checked">字段</el-checkbox>
              </template>

              <el-button type="text" icon="el-icon-delete" @click="removeItem(index)" class="delete-btn" />
            </div>

            <!-- 递归渲染子条件组 -->
            <ConditionGroup
              v-else-if="child.type === 'group'"
              :item="child"
              :widgetList="widgetList"
              @remove="removeItem(index)"
            />
          </div>
        </div>
      </div>
      <div class="attr-box">
        <el-button
          size="small"
          type="text"
          icon="el-icon-circle-plus-outline"
          @click="addItem('condition')"
          class="action-btn"
          >添加条件</el-button
        >
        <el-button
          size="small"
          type="text"
          icon="el-icon-circle-plus-outline"
          @click="addItem('group')"
          class="action-btn"
          >添加条件组</el-button
        >
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConditionGroup', // 递归组件必须声明name
  props: {
    item: {
      type: Object,
      required: true,
      default: () => ({
        type: 'group',
        relation: 'AND',
        children: []
      })
    },
    widgetList: {
      // 表单字段值列表
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      // 下拉选项配置
      relationOptions: [
        { label: 'AND', value: 'AND' },
        { label: 'OR', value: 'OR' }
      ],
      fieldOptions: [
        // { label: '字段', value: '1' },
        { label: '变量', value: '2' }
      ],
      operatorOptions: [
        {
          value: 'gt',
          label: '大于'
        },
        {
          value: 'lt',
          label: '小于'
        },
        {
          value: 'eq',
          label: '等于'
        },
        {
          value: 'not_eq',
          label: '不等于'
        },
        {
          value: 'in',
          label: '包含'
        },
        {
          value: 'not_in',
          label: '不包含'
        },

        {
          value: 'is_empty',
          label: '为空'
        },
        {
          value: 'not_empty',
          label: '不为空'
        }
      ]
    }
  },
  methods: {
    // 添加条件/条件组
    addItem(type) {
      const newItem =
        type === 'condition'
          ? { type: 'condition', field: 2, operator: '', dataKey: '' }
          : { type: 'group', relation: 'AND', children: [] }
      this.item.children.push(newItem)
    },
    deleteFn(item, index) {
      this.item.children.splice(index, 1)
      this.$emit('remove')
    },

    // 删除条件/条件组
    removeItem(index) {
      this.item.children.splice(index, 1)
      this.$emit('remove')
    }
  }
}
</script>

<style scoped lang="scss">
.condition-group-wrapper {
  position: relative;
  border: 1px dashed #dcdcdc;
  border-radius: 4px;
  padding: 16px;
  margin: 8px 0;
  display: flex;
}
// .condition-group-wrapper:nth-child(0){
//   border:none;
// }
.el-icon-circle-close {
  position: absolute;
  top: -4px;
  right: -6px;
  color: #909399;
  font-size: 16px;
  cursor: pointer;
}

.condition-group {
  width: 100%;
}

.condition-group-con {
  width: calc(100% - 200px);
}

.condition-group-left {
  width: 200px;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;

  &:after {
    content: '';
    position: absolute;
    width: 1px;
    left: 60px;
    background-color: #dcdcdc;
    top: 1px;
    bottom: 1px;
    margin: 17px 0;
  }
}

/* 条件组头部样式 */
.condition-group-header {
  display: flex;
}

.attr-box {
  display: flex;
}

.action-btn {
  margin-right: 12px;
  color: #409eff;
}

.field-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  position: relative;
  &:before {
    content: '';
    position: absolute;
    width: 166px;
    height: 1px;
    background-color: #dcdcdc;
    left: -140px;
    top: 50%;
    margin-top: -1px;
  }
}
.condition-item {
  position: relative;
  &:last-of-type {
    .field-row {
      margin-bottom: 0;
    }
  }

  // 这段代码的作用是为带有 data-group 属性且值为 "group" 的 .condition-item 元素添加一个伪元素。
  // 该伪元素会在元素的内容之前插入一条水平的分隔线，用于视觉上的分隔和布局。
  // 分隔线的样式为宽度 142px、高度 1px 的灰色线条，位置在元素左侧 -140px 处，垂直居中。
  &[data-group='group'] {
    &:before {
      content: '';
      position: absolute;
      width: 142px;
      height: 1px;
      background-color: #dcdcdc;
      left: -140px;
      top: 50%;
      margin-top: -1px;
    }
  }
}

.first-select {
  width: 100px;
  z-index: 2;
}

// .field-select,
// .operator-select {
//   width: 120px;
// }

.delete-btn {
  color: #909399;
}
</style>
