<!--
  @FileDescription: 字典选项设置 - 基础可复用组件
  功能：提供完整的字典选项管理界面，包括：
    - 左侧字典类型列表切换
    - 右侧选项编辑（支持平铺列表模式和树形结构模式）
    - 拖拽排序（平铺列表模式）
    - 新增、编辑、删除选项
    - 批量保存
  使用方式：传入 dictKey 指定模块类型，组件内封装所有接口调用
-->
<template>
  <div class="divBox">
    <!-- 主卡片容器 -->
    <el-card class="dict-option-setting__card" :body-style="{ padding: 0 }">
      <!-- 顶部标题栏 -->
      <div class="dict-option-setting__header">
        <i class="el-icon-arrow-left dict-option-setting__back-icon" @click="handleBack" />
        <span>字典选项设置</span>
      </div>

      <!-- 主体内容区域：左右分栏布局 -->
      <div v-if="dictTypes.length > 0" class="dict-option-setting__body">
        <!-- 左侧：字典类型列表 -->
        <div class="dict-option-setting__sidebar">
          <div
            v-for="item in dictTypes"
            :key="item.id"
            class="dict-option-setting__sidebar-item"
            :class="{ 'is-active': currentTypeId === item.id }"
            @click="handleTypeSelect(item)"
          >
            {{ item.name }}
          </div>
        </div>

        <!-- 右侧：选项编辑区域 -->
        <div class="dict-option-setting__content">
          <!-- 当前选中类型的标题 -->
          <div class="dict-option-setting__content-title">{{ currentTypeName }}</div>

          <div v-loading="loading" class="dict-option-setting__content-body">
            <!-- 单层级模式（level === 1）：可拖拽排序的平铺列表 -->
            <template v-if="currentLevel === 1">
              <draggable
                tag="div"
                :list="optionItems"
                v-bind="{
                  group: 'dictOptions',
                  ghostClass: 'dict-option-setting__ghost',
                  handle: '.dict-option-setting__drag-handle'
                }"
                :item-key="$draggableKey"
              >
                <template #item="{ element: option, index: idx }">
                  <div class="dict-option-setting__option-row">
                    <!-- 选项名称输入框，右侧带颜色选择器 -->
                    <el-input v-model="option.name" class="dict-option-setting__option-input">
                      <template #suffix>
                        <span>
                          <el-color-picker v-model="option.color" />
                        </span>
                      </template>
                    </el-input>
                    <!-- 拖拽排序手柄 -->
                    <span class="iconfont icontuodong dict-option-setting__drag-handle" title="拖拽排序" />
                    <!-- 删除按钮 -->
                    <span
                      class="el-icon-delete dict-option-setting__action-icon"
                      @click.stop="handleDeleteFlatItem(option, idx)"
                    />
                  </div>
                </template>
              </draggable>
              <!-- 添加新选项按钮 -->
              <div class="dict-option-setting__add-btn" @click="handleAddFlatItem">+ 添加</div>
            </template>

            <!-- 多层级模式（level !== 1）：树形结构编辑 -->
            <el-tree v-else :data="optionItems" node-key="value" default-expand-all>
              <template #default="{ node, data }">
                <div class="dict-option-setting__tree-node">
                  <!-- 节点名称输入框 -->
                  <el-input
                    v-model="data.name"
                    placeholder="数据值"
                    class="dict-option-setting__tree-input"
                  />
                  <!-- 添加同级节点 -->
                  <span
                    class="iconfont icontianjia1 dict-option-setting__action-icon"
                    title="添加同级"
                    @click="handleAddTreeSibling(node, data)"
                  />
                  <!-- 添加子级节点 -->
                  <span
                    class="iconfont icona-ziji1x dict-option-setting__action-icon"
                    title="添加子项"
                    @click="handleAddTreeChild(node, data)"
                  />
                  <!-- 删除节点 -->
                  <span
                    class="el-icon-delete dict-option-setting__action-icon"
                    title="删除"
                    @click="handleDeleteTreeNode(node, data)"
                  />
                </div>
              </template>
            </el-tree>
          </div>
        </div>
      </div>

      <!-- 无数据时的空状态展示 -->
      <div v-else class="dict-option-setting__empty">
        <default-page :index="18" />
      </div>
    </el-card>

    <!-- 底部固定操作栏：取消 / 保存 -->
    <div class="dict-option-setting__footer">
      <el-button @click="handleBack">取消</el-button>
      <el-button type="primary" :loading="saving" @click="handleSave"> 保存 </el-button>
    </div>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
/**
 * @description 字典选项设置基础组件
 * 使用 Vue 2.7 Composition API (setup) 编写
 * 外壳页面通过 dictKey 指定模块类型，组件内封装所有接口调用
 */
import { ref, onMounted, getCurrentInstance, defineAsyncComponent } from 'vue'
import Draggable from 'vuedraggable'
import { salesmanCustomApi } from '@/api/client.ts'
import { crudDictBatchApi } from '@/api/develop.ts'
import { getDictTreeListApi, getDictDataDeleteApi } from '@/api/form.ts'

const DefaultPage = defineAsyncComponent(() => import('@/components/common/defaultPage'))

export default {
  name: 'DictOptionSetting',
  components: { Draggable, DefaultPage },
  props: {
    /**
     * 字典模块标识，用于调用 salesmanCustomApi 获取字典分类
     * 可选值：customer, clue, odds, contract, liaison, product
     */
    dictKey: {
      type: String,
      required: true
    }
  },
  setup(props) {
    /** 获取组件实例，用于访问 $router、$message 等 */
    const instance = getCurrentInstance()
    const proxy = instance.proxy

    // ==================== 响应式状态 ====================

    /** 左侧字典类型列表 */
    const dictTypes = ref([])
    /** 当前选中的字典类型 ID */
    const currentTypeId = ref(null)
    /** 当前选中的字典类型名称 */
    const currentTypeName = ref('')
    /** 当前选中的字典类型层级（1=平铺列表，其他=树形结构） */
    const currentLevel = ref(1)
    /** 当前选中的完整字典类型对象 */
    const currentTypeObj = ref(null)
    /** 右侧编辑区域的选项数据列表 */
    const optionItems = ref([])
    /** 保存按钮的加载状态 */
    const saving = ref(false)
    /** 选项数据加载状态 */
    const loading = ref(false)

    // ==================== 工具函数 ====================

    /**
     * 递归遍历树结构，找出所有节点中 value 的最大数值
     * 用于生成新选项的唯一 value
     * @param {Array} nodes - 树节点数组
     * @returns {number} 最大 value 值，空树返回 0
     */
    const findMaxValueInTree = (nodes) => {
      if (!nodes || nodes.length === 0) return 0
      let maxVal = 0

      const traverse = (list) => {
        for (const node of list) {
          const num = Number(node.value)
          if (!isNaN(num) && num > maxVal) {
            maxVal = num
          }
          if (node.children && node.children.length > 0) {
            traverse(node.children)
          }
        }
      }

      traverse(Array.isArray(nodes) ? nodes : [nodes])
      return maxVal
    }

    /**
     * 在树结构中查找指定节点的父节点
     * 通过 $treeNodeId 内部属性进行匹配
     * @param {number} treeNodeId - el-tree 内部节点 ID
     * @returns {Object|null} 父节点对象，未找到则返回 null
     */
    const findParentNode = (treeNodeId) => {
      let parentNode = null
      const search = (node) => {
        if (!node.children) return
        for (const child of node.children) {
          if (child.$treeNodeId === treeNodeId) {
            parentNode = node
            return
          }
          if (child.children) {
            search(child)
          }
        }
      }
      search({ children: optionItems.value })
      return parentNode
    }

    /**
     * 创建新的选项节点并追加到目标数组
     * @param {Array} targetArr - 要追加到的目标数组
     * @param {number} [parentId] - 父节点 ID，用于树形结构
     */
    const appendNewNode = (targetArr, parentId) => {
      const maxVal = findMaxValueInTree(optionItems.value)
      const newValue = String(maxVal + 1)
      const newNode = {
        name: '选项' + newValue,
        value: newValue,
        status: 1
      }
      if (parentId !== undefined) {
        newNode.pid = parentId
      }
      targetArr.push(newNode)
    }

    // ==================== 数据加载 ====================

    /**
     * 加载字典类型列表
     * 获取成功后自动选中第一项
     */
    const loadDictTypes = async () => {
      try {
        const res = await salesmanCustomApi(props.dictKey)
        dictTypes.value = res.data?.dict_cate || []
        if (dictTypes.value.length > 0) {
          handleTypeSelect(dictTypes.value[0])
        }
      } catch (err) {
        proxy.$message.error('获取字典类型列表失败')
      }
    }

    /**
     * 加载指定字典类型的选项数据
     * @param {Object} typeItem - 字典类型对象
     */
    const loadOptionData = async (typeItem) => {
      loading.value = true
      try {
        const res = await getDictTreeListApi({ types: typeItem.ident })
        optionItems.value = res.data || []
      } catch (err) {
        proxy.$message.error('获取选项数据失败')
      } finally {
        loading.value = false
      }
    }

    // ==================== 事件处理 ====================

    /**
     * 切换左侧字典类型
     * @param {Object} item - 被点击的字典类型对象
     */
    const handleTypeSelect = (item) => {
      currentTypeId.value = item.id
      currentTypeName.value = item.name
      currentLevel.value = item.level
      currentTypeObj.value = item
      loadOptionData(item)
    }

    /** 返回上一页 */
    const handleBack = () => {
      proxy.$router.back()
    }

    // ---------- 平铺列表模式操作 ----------

    /**
     * 添加新的平铺选项
     * 自动计算 value 值并追加到列表末尾
     */
    const handleAddFlatItem = () => {
      const items = optionItems.value
      if (items && items.length > 0) {
        const maxVal = Math.max(...items.map((item) => Number(item.value)))
        const newValue = maxVal + 1
        items.push({
          name: `选项${newValue}`,
          value: String(newValue),
          color: '#1890ff',
          status: 1,
          sort: 0
        })
      } else {
        optionItems.value = [
          {
            name: '选项1',
            value: '1',
            color: '#1890ff',
            status: 1,
            sort: 0
          }
        ]
      }
    }

    /**
     * 删除平铺列表中的选项
     * 需要用户确认，若选项已保存到服务端则同时调用删除接口
     * @param {Object} option - 要删除的选项对象
     * @param {number} idx - 选项在列表中的索引
     */
    const handleDeleteFlatItem = async (option, idx) => {
      try {
        await modalSure('确定要删除该选项吗？')
        if (option.id) {
          await getDictDataDeleteApi(option.id)
        }
        optionItems.value.splice(idx, 1)
      } catch {
        // 用户取消删除，不做任何操作
      }
    }

    // ---------- 树形结构模式操作 ----------

    /**
     * 在当前节点的同级位置添加新节点
     * @param {Object} node - el-tree 的节点对象
     * @param {Object} data - 节点绑定的数据
     */
    const handleAddTreeSibling = (node, data) => {
      const parent = findParentNode(data.$treeNodeId)
      if (parent) {
        if (!parent.children) {
          parent.children = []
        }
        appendNewNode(parent.children, data.pid)
      }
    }

    /**
     * 在当前节点下添加子节点
     * @param {Object} node - el-tree 的节点对象
     * @param {Object} data - 节点绑定的数据
     */
    const handleAddTreeChild = (node, data) => {
      if (!data.children) {
        data.children = []
      }
      appendNewNode(data.children, data.id)
    }

    /**
     * 删除树形结构中的节点
     * 需要用户确认后执行
     * @param {Object} node - el-tree 的节点对象
     * @param {Object} data - 节点绑定的数据
     */
    const handleDeleteTreeNode = async (node, data) => {
      try {
        await modalSure('确定要删除该节点吗？')
        node.remove()
      } catch {
        // 用户取消删除
      }
    }

    // ---------- 保存操作 ----------

    /**
     * 批量保存当前选中字典类型的所有选项数据
     * 保存成功后返回上一页
     */
    const handleSave = async () => {
      if (saving.value) return
      saving.value = true
      try {
        await crudDictBatchApi({
          dict_data: optionItems.value,
          dict_type_id: currentTypeObj.value.id
        })
      } catch (err) {
        proxy.$message.error(err.message || '保存失败')
      } finally {
        saving.value = false
      }
    }

    // ==================== 生命周期 ====================

    /** 组件挂载后加载字典类型数据 */
    onMounted(() => {
      loadDictTypes()
    })

    return {
      dictTypes,
      currentTypeId,
      currentTypeName,
      currentLevel,
      optionItems,
      saving,
      loading,
      handleTypeSelect,
      handleBack,
      handleAddFlatItem,
      handleDeleteFlatItem,
      handleAddTreeSibling,
      handleAddTreeChild,
      handleDeleteTreeNode,
      handleSave
    }
  }
}
</script>

<style lang="scss" scoped>
/* 整体容器 */

/* 主卡片：撑满可用高度并支持滚动 */
.dict-option-setting__card {
  height: calc(100vh - 140px);
  overflow-y: auto;
  background: #fff;
}

/* 顶部标题栏 */
.dict-option-setting__header {
  height: 60px;
  line-height: 60px;
  padding: 0 20px;
  font-size: 16px;
  font-weight: 500;
  border-bottom: 1px solid #eee;
}

/* 返回箭头图标 */
.dict-option-setting__back-icon {
  cursor: pointer;
  margin-right: 4px;
}

/* 主体内容区域：左右分栏 */
.dict-option-setting__body {
  display: flex;
}

/* 左侧字典类型列表 */
.dict-option-setting__sidebar {
  width: 270px;
  min-width: 270px;
  padding-top: 12px;
  height: calc(100vh - 210px);
  border-right: 1px solid #eee;
  overflow-y: auto;
}

/* 左侧列表项 */
.dict-option-setting__sidebar-item {
  height: 40px;
  line-height: 40px;
  padding-left: 20px;
  font-size: 14px;
  color: #303133;
  cursor: pointer;
  transition: all 0.2s;

  /* 选中状态高亮 */
  &.is-active {
    background: #f1f9ff;
    color: #1890ff;
    font-weight: 500;
    border-right: 2px solid #1890ff;
  }

  &:hover:not(.is-active) {
    background: #f5f7fa;
  }
}

/* 右侧内容区域 */
.dict-option-setting__content {
  flex: 1;
  padding: 12px 20px;
  overflow-y: auto;
  height: calc(100vh - 210px);
}

/* 右侧标题 */
.dict-option-setting__content-title {
  font-size: 14px;
  font-weight: 500;
  color: #303133;
  margin-bottom: 16px;
}

/* 内容区域主体 */
.dict-option-setting__content-body {
  padding-top: 4px;
}

/* 平铺列表模式 - 每行选项 */
.dict-option-setting__option-row {
  display: flex;
  align-items: center;
  margin-bottom: 14px;
}

/* 选项输入框宽度 */
.dict-option-setting__option-input {
  width: 300px;
}

/* 拖拽手柄样式 */
.dict-option-setting__drag-handle {
  cursor: move;
  color: #909399;
  font-size: 14px;
  margin-left: 6px;
}

/* 操作图标通用样式（删除、添加等） */
.dict-option-setting__action-icon {
  cursor: pointer;
  color: #909399;
  font-size: 14px;
  margin-left: 6px;

  &:hover {
    color: #409eff;
  }
}

/* 拖拽时的占位样式 */
.dict-option-setting__ghost {
  background: #fff;
  border: 2px dotted #409eff;
}

/* 添加按钮 */
.dict-option-setting__add-btn {
  cursor: pointer;
  width: 74px;
  height: 32px;
  line-height: 32px;
  text-align: center;
  font-size: 13px;
  color: #1890ff;
  background: rgba(24, 144, 255, 0.06);
  border-radius: 2px;

  &:hover {
    background: rgba(24, 144, 255, 0.12);
  }
}

/* 树形节点自定义样式 */
.dict-option-setting__tree-node {
  display: flex;
  align-items: center;
  flex: 1;
}

/* 树形节点输入框宽度 */
.dict-option-setting__tree-input {
  width: 300px;
}

/* 空状态容器 */
.dict-option-setting__empty {
  padding: 40px 0;
  text-align: center;
}

/* 底部固定操作栏 */
.dict-option-setting__footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-top: 1px solid #e8e8e8;
  z-index: 10;
}

/* 覆盖 el-tree 默认样式 */
:deep(.el-tree-node__content) {
  height: 32px;
  margin-bottom: 14px;
}

:deep(.el-tree-node__content > .el-tree-node__expand-icon) {
  padding-left: 0;
}

:deep(.el-tree-node__indent) {
  width: 10px;
}

:deep(.el-tree-node:hover) {
  background: transparent;
}

/* 隐藏叶子节点的展开图标 */
:deep(.el-tree-node__expand-icon.is-leaf) {
  display: none;
}

/* 颜色选择器边框优化 */
:deep(.el-color-picker__trigger) {
  display: flex;
  border: none;
  margin-bottom: 2px;
}

:deep(.el-color-picker__color) {
  border-color: #ddd;
}
</style>
