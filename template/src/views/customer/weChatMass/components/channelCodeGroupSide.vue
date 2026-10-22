<!-- 渠道活码-左侧活码分组管理侧栏 -->
<template>
  <div class="group-side">
    <div class="group-side-header">
      <span class="title-16">活码分组</span>
      <el-popover placement="top" trigger="hover" content="添加分组" popper-class="popoverStyle">
        <template #reference>
          <span class="iconfont icontianjia pointer" @click="handleAdd"></span>
        </template>
      </el-popover>
    </div>

    <el-scrollbar class="group-side-scroll reset-scroll-bar">
      <ul class="group-side-list">
        <li
          v-for="(item, index) in groupList"
          :key="item.id || 'all'"
          :class="{ active: index === activeIndex }"
          @click="handleSelect(index, item)"
        >
          <span class="line1 group-side-name">{{ item.name }}</span>
          <el-dropdown
            class="group-side-dropdown"
            :class="{ 'is-hidden': !(activeIndex === index && item.id) }"
            trigger="click"
            placement="bottom-end"
            @command="handleCommand"
          >
            <span class="icon iconfont icongengduo pointer group-side-more" @click.stop></span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item :command="{ action: 'edit', id: item.id }">{{ $t('public.edit') }}</el-dropdown-item>
                <el-dropdown-item :command="{ action: 'delete', id: item.id }">{{
                  $t('public.delete')
                }}</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </li>
      </ul>
    </el-scrollbar>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import modalForm from '@/libs/modal-form'
import { ref, computed, onMounted } from 'vue'
import { createChannelCodeGroupApi, editChannelCodeGroupApi, delChannelCodeGroupApi } from '@/api/weCom'
import { useChannelCodeGroup } from '../composables/useChannelCodeGroup'

export default {
  name: 'ChannelCodeGroupSide',
  emits: ['change'],
  setup(props, { emit }) {

    const { groupList: rawGroupList, fetchGroupList } = useChannelCodeGroup()
    // 侧栏在原始分组前追加「全部」项
    const groupList = computed(() => [{ id: '', name: '全部' }, ...rawGroupList.value])
    const activeIndex = ref(0)

    function getGroupList() {
      return fetchGroupList()
    }

    function handleSelect(index, item) {
      if (activeIndex.value === index) return
      activeIndex.value = index
      emit('change', item.id)
    }

    // 新增 / 编辑均为后端动态表单，提交由 modalForm 内部完成
    function handleAdd() {
      modalForm(createChannelCodeGroupApi()).then(() => {
        getGroupList()
      })
    }

    function handleEdit(id) {
      modalForm(editChannelCodeGroupApi(id)).then(() => {
        getGroupList()
      })
    }

    async function handleDelete(id) {
      try {
        await modalSure('你确定要删除该分组吗')
      } catch (e) {
        return
      }
      const activeItem = groupList.value[activeIndex.value]
      const activeId = activeItem ? activeItem.id : ''
      await delChannelCodeGroupApi(id)
      await getGroupList()
      // 按 id 重新定位选中项，避免删除前面的分组导致下标错位；
      // 选中项被删时回到「全部」并通知父组件重查
      const newIndex = groupList.value.findIndex((g) => g.id === activeId)
      if (newIndex === -1) {
        activeIndex.value = 0
        emit('change', '')
      } else {
        activeIndex.value = newIndex
      }
    }

    function handleCommand({ action, id }) {
      if (action === 'edit') handleEdit(id)
      else if (action === 'delete') handleDelete(id)
    }

    onMounted(getGroupList)

    return { groupList, activeIndex, handleSelect, handleAdd, handleCommand }
  }
}
</script>

<style lang="scss">
// popper 挂载在 body 下，需非 scoped 才能命中（与 left.vue 中同名样式保持一致）
.popoverStyle {
  min-width: 100px;
  text-align: center;
  padding: 10px;
  font-size: 13px;
  color: #ffffff;
  background: #303133;
}
</style>
<style lang="scss" scoped>
.group-side {
  width: 210px;
  flex: none;
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 20px 0 14px;
  border-right: 1px solid #eeeeee;

  .group-side-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px 15px 20px;

    .icontianjia {
      font-size: 14px;
      color: #1890ff;
    }
  }

  .group-side-scroll {
    flex: 1;
    min-height: 0;

    ::v-deep .el-scrollbar__wrap {
      overflow-x: hidden;
    }
  }

  .group-side-list {
    list-style: none;
    margin: 0;
    padding: 0;

    li {
      height: 40px;
      padding-left: 20px;
      padding-right: 15px;
      line-height: 40px;
      font-size: 14px;
      color: #303133;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      // 兜底裁切：任一子项意外撑开时不至于压到相邻元素上
      overflow: hidden;

      &:hover {
        background: #f3f5f9;
      }

      // 显式声明收缩能力，不依赖「flex 子项 overflow:hidden 时自动最小尺寸归零」这条隐式规则，
      // 否则部分内核下名称会退回内容宽度，省略号失效并与右侧「更多」图标贴死
      .group-side-name {
        flex: 1 1 auto;
        min-width: 0;
      }

      // 图标用 visibility 恒定占位（而非 v-show），避免选中/未选中时名称可用宽度跳变 14px
      .group-side-dropdown {
        flex: none;
        margin-left: 4px;

        &.is-hidden {
          visibility: hidden;
          pointer-events: none;
        }
      }

      .group-side-more {
        font-size: 14px;
      }

      &.active {
        background-color: rgba(24, 144, 255, 0.08);
        color: #1890ff;
        font-weight: 600;

        .group-side-more {
          color: #1890ff;
        }
      }
    }
  }
}
</style>
