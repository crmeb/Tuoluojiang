<template>
  <div class="option-items-pane">
  
    <el-tree :data="optionModel.customizeItems" node-key="value" default-expand-all @node-drop="handleDrop">
      <template #default="{ node, data }">
        <div class="custom-tree-node">
          <el-input
            size="small"
            v-model="data.name"
            @change="changeCard(data)"
            placeholder="数据值"
            style="width: 150px; margin-bottom: 5px"
          >
          </el-input>
          <template>
            <span class="iconfont icontianjia1 text-color" @click="addFn(node, data)" />
            <span
              class="iconfont icona-ziji1x text-color"
              @click="addChildFn(node, data)"
              v-if="selectedWidget.type == 'tag' && node.level !== 2"
            />
            <span
              class="iconfont icona-ziji1x text-color"
              @click="addChildFn(node, data)"
              v-if="selectedWidget.type !== 'tag' && node.level !== 4"
            />
            <span class="iconfont iconshanchujilu" @click="deleteFn(node, data)" />
          </template>
        </div>
      </template>
    </el-tree>
    <el-button type="text" class="mt10" @click="addRowFn()">新增选项</el-button>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import { delCrudSaveApi } from '@/api/develop'
import { getDictDataDeleteApi } from '@/api/form'
export default {
  name: 'OptionItemsSetting',
  props: {
    designer: Object,
    selectedWidget: Object,
    optionModel: Object
  },
  data() {
    return {}
  },
  methods: {
    handleDrop() {},
    addFn(node, data) {
      const maxValue = this.findMaxValueInTree(this.optionModel.customizeItems)
      const newValue = String(maxValue + 1)
      if (node.parent.data.children) {
        let obj = {
          name: '选项' + newValue,
          value: newValue, // value值
          crud_id: this.$route.query.id || 0, //实体id
          field_id: this.optionModel.fieldId, // 字段id
          data_id: 0,
          pid: node.parent.data.id
        }
        delCrudSaveApi(obj).then((res) => {
          node.parent.data.children.push({ value: newValue, id: res.data.id, name: '选项' + newValue })
        })
      } else {
        let obj = {
          name: '选项' + newValue,
          value: newValue, // value值
          crud_id: this.$route.query.id || 0, //实体id
          field_id: this.optionModel.fieldId, // 字段id
          data_id: 0,
          pid: data.id
        }
        delCrudSaveApi(obj).then((res) => {
          this.optionModel.customizeItems.push({ value: newValue, id: res.data.id, name: '选项' + newValue })
        })
      }
    },

    async changeCard(item) {
      let obj = {
        name: item.name,
        value: item.value, // value值
        crud_id: this.$route.query.id || 0, //实体id
        field_id: this.optionModel.fieldId, // 字段id
        data_id: item.id,
        color: item.color,
        data_id: item.id,
        pid: item.pid || 0
      }
      await delCrudSaveApi(obj)
    },

    addRowFn() {
      let index = this.optionModel.customizeItems.length
      if (index == 0) {
        let obj = {
          name: `选项1`,
          value: 1, // value值
          crud_id: this.$route.query.id || 0, //实体id
          field_id: this.optionModel.fieldId, // 字段id
          data_id: 0,
          pid: 0
        }
        delCrudSaveApi(obj).then((res) => {
          this.optionModel.customizeItems.push({ value: 1, id: res.data.id, name: '选项1', pid: 0 })
        })
      } else {
        const maxValue = this.findMaxValueInTree(this.optionModel.customizeItems)
        const newValue = String(maxValue + 1)

        let obj = {
          name: '选项' + newValue,
          value: newValue, // value值
          crud_id: this.$route.query.id || 0, //实体id
          field_id: this.optionModel.fieldId, // 字段id
          data_id: 0,
          pid: 0
        }
        delCrudSaveApi(obj).then((res) => {
          this.optionModel.customizeItems.push({ value: newValue, id: res.data.id, name: '选项' + newValue })
        })
      }
    },

    async deleteFn(node, data) {
      await modalSure('你确定要删除这条数据吗')
      await getDictDataDeleteApi(data.id)
      node.remove()
    },

    addChildFn(node, data) {
      const maxValue = this.findMaxValueInTree(this.optionModel.customizeItems)
      const newValue = String(maxValue + 1)
      if (!data.children) {
        this.$set(data, 'children', [])
      }
      let obj = {
        name: '选项' + newValue,
        value: newValue, // value值
        crud_id: this.$route.query.id || 0, //实体id
        field_id: this.optionModel.fieldId, // 字段id
        data_id: 0,
        pid: data.id
      }
      delCrudSaveApi(obj).then((res) => {
        data.children.push({ value: newValue, id: res.data.id, name: '选项' + newValue, pid: data.id })
      })
    },
    // 获取做大值
    findMaxValueInTree(nodes) {
      if (!nodes || nodes.length === 0) return 0
      let max = -Infinity
      function dfs(nodeList) {
        for (const node of nodeList) {
          // 转换 value 为数字并更新最大值
          const currentValue = Number(node.value)
          if (!isNaN(currentValue)) {
            max = Math.max(max, currentValue)
          }

          // 递归处理子节点
          if (node.children && node.children.length > 0) {
            dfs(node.children)
          }
        }
      }

      // 统一处理数组形式
      dfs(Array.isArray(nodes) ? nodes : [nodes])
      return max === -Infinity ? 0 : max // 空树返回 0
    }
  }
}
</script>

<style lang="scss" scoped>
.option-items-pane {
  width: 100%;
  height: 100%;
  overflow: auto;
}

::v-deep .el-tree-node__content {
  height: 40px;
}

::v-deep .el-tree-node > .el-tree-node__children {
  overflow: visible;
}

.iconshanchujilu {
  color: red;
}

.text-color {
  color: #1890ff !important;
}
</style>
@/utils/i18ns
