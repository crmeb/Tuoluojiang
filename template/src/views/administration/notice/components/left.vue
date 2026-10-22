<!-- 行政-企业动态-左侧公告类型组件 -->
<template>
  <div class="assess-left">
    <div class="el-card__header">
      <span class="pull-left">公告类型</span>
      <el-tooltip content="添加公告类型" effect="dark" placement="top">
        <span class="iconfont icontianjia pull-right pointer" @click="addCategory"></span>
      </el-tooltip>
    </div>

    <div v-height>
      <el-scrollbar style="height: 100%" class="reset-scroll-bar">
        <ul class="assess-left-ul">
          <li
            v-for="(item, index) in department"
            :key="index"
            :class="index == tabIndex ? 'active' : ''"
            @click="clickDepart(index, item.id)"
          >
            <span class="line1">{{ item.cate_name }}</span>
            <el-popover
              :ref="`pop-${item.id}`"
              :offset="10"
              placement="bottom-end"
              trigger="click"
              @hide="handleHide"
              @after-enter="handleShow(item.id)"
            >
              <div class="right-item-list">
                <div class="right-item" @click.stop="addDivsion(item.id)">{{ $t('public.edit') }}</div>
                <div class="right-item" @click.stop="handleDelete(item.id)">{{ $t('public.delete') }}</div>
              </div>
              <template #reference>
                <div
                  v-show="tabIndex == index && item.id"
                  class="icon iconfont icongengduo pointer assess-left-more"
                ></div>
              </template>
            </el-popover>
          </li>
        </ul>
      </el-scrollbar>
    </div>
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import modalForm from '@/libs/modal-form'
import {
  noticeCategoryApi,
  noticeCategoryCreateApi,
  noticeCategoryDeleteApi,
  noticeCategoryEditApi
} from '@/api/administration'
export default {
  name: 'DepartmentNot',
  data() {
    return {
      department: [],
      tabIndex: 0,
      activeValue: '',
      optionValue: {
        id: 0
      }
    }
  },
  mounted() {
    this.getTargetCate()
  },
  methods: {
    clickDepart(index, id) {
      this.tabIndex = index
      this.optionValue.id = id
      this.handleEmit()
    },
    handleEmit() {
      this.$emit('eventOptionData', this.optionValue, this.tabIndex)
    },
    // 编辑窗口显示
    handleShow(value) {
      this.activeValue = value
    },
    // 编辑窗口隐藏
    handleHide() {
      this.activeValue = ''
    },
    getList() {
      noticeCategoryApi().then((res) => {
        let obj = {
          cate_name: '全部',
          id: ''
        }
        // 请求去重会让多个组件共享同一份 res.data，必须拷贝后再插入「全部」，否则会污染新建页的分类下拉
        this.department = [obj, ...(res.data || [])]
        if (this.department.length > 0) {
          this.optionValue.id = this.department[0].id
        }
      })
    },
    getTargetCate() {
      noticeCategoryApi().then((res) => {
        let obj = {
          cate_name: '全部',
          id: ''
        }
        // 请求去重会让多个组件共享同一份 res.data，必须拷贝后再插入「全部」，否则会污染新建页的分类下拉
        this.department = [obj, ...(res.data || [])]
        if (this.department.length > 0) {
          this.optionValue.id = this.department[0].id
        }
        this.handleEmit()
      })
    },
    addCategory() {
      modalForm(noticeCategoryCreateApi()).then(({ message }) => {
        this.getTargetCate()
      })
    },
    addDivsion(id) {
      modalForm(noticeCategoryEditApi(id)).then(({ message }) => {
        this.getList()
      })
    },
    async handleDelete(id) {
      await modalSure('你确定要删除该分类吗')
      const res = await noticeCategoryDeleteApi(id)
      if (res.status === 200) {
        this.getTargetCate()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.pull-left {
  font-weight: 500;
}
.icontianjia {
  font-size: 14px;
  color: #1890ff;
}
.assess-left {
  height: 100%;
  margin: -15px 0 -20px -20px;
  padding: 14px 0;
  border-right: 1px solid #eeeeee;
  ::v-deep .el-card__header {
    border-bottom: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0;
    padding-bottom: 15px;
    button {
      justify-content: flex-end;
    }
  }
  ::v-deep .el-scrollbar__wrap {
    overflow-x: hidden;
  }
  overflow: auto;
  .assess-left-ul {
    list-style: none;
    margin: 0;
    padding: 0;
    position: relative;
    li {
      height: 40px;
      padding-left: 20px;
      padding-right: 15px;
      line-height: 40px;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: #303133;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      .assess-left-more {
        font-size: 14px;
      }
      &:hover {
        background: #f3f5f9 !important;
      }
      &.active {
        background-color: rgba(24, 144, 255, 0.08);
        color: #1890ff;
        font-weight: 600;
        .assess-left-more {
          color: #1890ff;
        }
      }
    }
  }
}


.assess-left::-webkit-scrollbar {
  width: 5px;
  height: 1px;
}
.assess-left::-webkit-scrollbar-thumb {
  /*滚动条里面小方块*/
  border-radius: 5px;
  -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
  background: #f5f5f5;
}
.assess-left::-webkit-scrollbar-track {
  /*滚动条里面轨道*/
  -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
  border-radius: 5px;
  background: #f0f2f5;
}
</style>
