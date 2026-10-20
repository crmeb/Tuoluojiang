<template>
  <el-dialog
      class="user-workbench-quick-manage-dialog"
    :append-to-body="true"
    :before-close="handleClose"
    :close-on-click-modal="false"
    :title="config.title"
    v-model="dialogVisible"
    :width="config.width"
  >
    <div class="quick-content">
      <div class="quick-list">
        <el-scrollbar style="height: 50vh">
          <div class="quick-list-item">
            <div class="quick-list-item-name">已添加</div>

            <ul
              v-if="config.data && config.data.length > 0"
              :class="config.type == 'statistics' ? 'statistics' : 'quick-list-item-ul'"
            >
              <draggable
                v-model="config.data"
                animation="800"
                chosen-class="chosen"
                force-fallback="true"
                ghost-class="ghost"
                group="people"
                @end="onEnd"
                @start="onStart"
                tag="transition-group"
                item-key="id"
              >
                <template #item="{ element: item, index }">
                  <li v-if="config.type == 'statistics'" class="statistics-item">
                    <div class="name">{{ item.title }}</div>
                    <div class="num">0.00</div>
                    <i class="el-icon-remove quick-icon remove" @click="handleRemove(index)"></i>
                  </li>
                  <li v-else>
                    <el-image :src="item.image" class="image"></el-image>
                    <div class="name">{{ item.name }}</div>
                    <i class="el-icon-remove quick-icon remove" @click="handleRemove(index)"></i>
                  </li>
                </template>
              </draggable>
            </ul>
            <div v-else class="quick-list-empty">陀螺匠会更努力了解你的需求</div>
          </div>
          <template v-for="(other, index) in fastEntryData" :key="index">
            <div class="quick-list-item">
              <div class="quick-list-item-name">{{ other.cate_name }}</div>
              <!-- 业绩统计 -->
              <ul v-if="config.type == 'statistics'" class="statistics">
                <li v-for="(item1, indexs) in other.fast_entry" :key="'item1' + indexs" class="statistics-item">
                  <div class="name">{{ item1.title }}</div>
                  <div class="num">0.00</div>

                  <i
                    v-if="!selectIds.includes(item1.id)"
                    class="el-icon-circle-plus quick-icon default-color"
                    @click="handlePush(item1)"
                  ></i>
                </li>
              </ul>
              <!-- 菜单管理 -->
              <ul v-else class="quick-list-item-ul">
                <li v-for="(item1, indexs) in other.fast_entry" :key="'item1' + indexs">
                  <el-image :src="item1.image" class="image"></el-image>
                  <div class="name">{{ item1.name }}</div>

                  <i
                    v-if="!selectIds.includes(item1.id)"
                    class="el-icon-circle-plus quick-icon default-color"
                    @click="handlePush(item1)"
                  ></i>
                </li>
              </ul>
            </div>
          </template>
        </el-scrollbar>
      </div>
    </div>
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button :loading="loading" type="primary" @click="handleConfirm">确定</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import draggable from 'vuedraggable'
import { userWorkFastMenusApi, putStatisticsApiAll } from '@/api/user.ts'
export default {
  emits: ['isSuccess'],
  name: 'MarkDialog',
  props: {
    config: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  components: {
    draggable
  },
  watch: {
    config: {
      handler(nVal) {
        if (nVal.data && nVal.data.length > 0) {
          this.selectIds = []
          nVal.data.forEach((value) => {
            if (this.config.type == 'statistics') {
              this.selectIds.push(value.id)
            } else {
              this.selectIds.push(Number(value.id))
            }
          })
        } else {
          this.selectIds = []
        }
      },
      deep: true
    }
  },
  data() {
    return {
      dialogVisible: false,
      loading: false,
      selectMax: 12,
      selectIds: [],
      fastEntryData: [],
      drag: false
    }
  },

  methods: {
    handleOpen(data) {
      this.fastEntryData = []
      this.fastEntryData = data.otherArr
      this.dialogVisible = true
    },
    handleClose() {
      this.dialogVisible = false
    },

    handleConfirm() {
      let data = []
      if (this.config.data.length > 0) {
        this.config.data.forEach((value) => {
          data.push(value.id)
        })
      }
      if (this.config.data.length > 8) {
        return this.$message.error('最多添加8个快捷应用')
      }
      if (this.config.data.length < 4) {
        return this.$message.error('最少添加4个快捷应用')
      }

      if (this.config.type == 'statistics') {
        this.statistics(data)
      } else {
        this.fastMenus(data)
      }
    },

    async statistics(val) {
      let data = {
        data: val
      }
      this.loading = true
      await putStatisticsApiAll(data)
      await this.$emit('isSuccess')
      await this.handleClose()
      this.loading = false
    },

    fastMenus(data) {
      this.loading = true
      userWorkFastMenusApi({ data })
        .then((res) => {
          this.$emit('isSuccess')
          this.handleClose()
          this.loading = false
        })
        .catch((error) => {
          this.loading = false
        })
    },
    handleRemove(index) {
      this.config.data.splice(index, 1)
    },
    handlePush(item) {
      if (this.config.type == 'statistics') {
        this.selectMax = 5
      }
      if (this.config.data.length >= this.selectMax) {
        this.$message.error('最多只能添加' + this.selectMax + '个应用')
        return false
      }

      this.config.data.push(item)
    },
    onStart() {
      this.drag = true
    },
    onEnd() {
      this.drag = false
    }
  }
}
</script>

<style lang="scss" scoped>
.ghost {
  opacity: 0.1;
  background: #fff;
}
.statistics {
  padding: 0;
  margin: 0;
  list-style: none;
  display: flex;
  align-content: center;
  flex-wrap: wrap;
  width: 100%;
  > div {
    width: 100%;
  }
  .statistics-item {
    width: calc((100% - 40px) / 3);
    margin-right: 20px;
    margin-bottom: 20px;
    padding: 20px;
    background: #f2f6fc;
    border-radius: 6px;
    font-size: 14px;
    font-family: PingFang SC-Regular, PingFang SC;
    font-weight: 400;
    color: #909399;
    position: relative;
    float: left;
    cursor: pointer;
    .num {
      margin-top: 10px;
      font-size: 26px;
      font-family: PingFang SC-Semibold, PingFang SC;
      font-weight: 700;
      color: #000000;
    }
    .quick-icon {
      position: absolute;
      right: -6px;
      top: -6px;
      cursor: pointer;
      font-size: 18px;
      border: 1px solid #fff;
      border-radius: 50%;
    }
  }
  .statistics-item:nth-of-type(3n) {
    margin-right: 0;
  }
}
.quick-content {
  width: 100%;
  .quick-list {
    width: 100%;
    :deep(.el-scrollbar__wrap) {
      overflow-x: hidden;
    }
    .quick-list-item {
      padding-right: 24px;
      margin-bottom: 10px;
      &:last-of-type {
        margin-bottom: 0;
      }
      .quick-list-item-name {
        font-size: 15px;
        color: #303133;
        font-weight: 800;
        margin-bottom: 20px;
      }
      .quick-list-item-ul {
        padding: 0;
        margin: 0;
        list-style: none;
        display: flex;
        align-content: center;
        flex-wrap: wrap;
        width: 100%;
        > div {
          width: 100%;
        }

        li {
          background: #f2f5fc;
          border-radius: 6px;
          width: calc((100% - 70px) / 6);
          margin-right: 14px;
          margin-bottom: 14px;
          padding: 16px 0;
          text-align: center;
          position: relative;
          float: left;
          cursor: pointer;
          &:nth-of-type(6n) {
            margin-right: 0;
          }
          .image {
            width: 40px;
            height: 40px;
            display: inline-block;
          }
          .name {
            padding-top: 8px;
            font-size: 13px;
            color: #303133;
          }
          .quick-icon {
            position: absolute;
            right: -6px;
            top: -6px;
            cursor: pointer;
            font-size: 18px;
            border: 1px solid #fff;
            border-radius: 50%;
          }
          .remove {
            color: #c0c4cc;
          }
        }
      }
      .quick-list-empty {
        width: 100%;
        height: 102px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #c0c4cc;
        font-size: 13px;
        border: 1px dashed #c0c4cc;
      }
    }
  }
}
</style>

<style lang="scss">
// 弹框挂到 body 或 class 落在 .el-dialog 上时，scoped 样式选不到，按 class 定位
.el-dialog.user-workbench-quick-manage-dialog .el-dialog__body {
  padding-top: 15px;
  padding-right: 0;
  border-top: 1px solid #dcdfe6;
}
</style>
