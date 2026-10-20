<template>
  <el-dialog :title="config.title" v-model="dialogVisible" :width="config.width" :before-close="handleClose">
    <div class="current">
      <el-scrollbar style="height: 100%;">
        <div v-if="tableData.length > 0" class="current-list">
          <div class="total-score">
            <p>
              <span class="total-score-name">总分:</span>
              <span class="number">{{ assessInfo.max }}</span>
            </p>
            <p v-for="(item, index) in tableData" :key="index">
              <span class="total-score-name">{{ item.name }}:</span> <span class="number">{{ sum(index) }}</span>
            </p>
            <p>
              <span class="total-score-name">当前得分:</span>
              <span class="number">{{ totalSum() }}</span>
            </p>
          </div>
          <div v-for="(item, index) in tableData" :key="index" class="current-table oa-input-borderless">
            <div class="current-table-title">
              <div v-if="!testAccess">
                <span
                  ><input
                    v-model="item.name"
                    :style="{ width: text(item.name) }"
                    :autofocus="true"
                    class="current-title-input"
                    type="text"
                /></span>
                <span
                  >维度权重<el-input-number v-model="item.ratio" :controls="false" :min="0" :max="item.weight" />%</span
                >
              </div>
              <div v-else>
                <span>{{ item.name }}</span>
                <span>
                  {{ assessInfo.types == 0 ? '维度权重' : '维度分数' }}
                  {{ item.ratio }}
                  {{ assessInfo.types == 0 ? '%' : '' }}
                </span>
              </div>
            </div>
            <el-table :data="item.target" style="width: 100%;" row-key="id" default-expand-all>
              <el-table-column prop="name" label="指标名称" min-width="100">
                <template #default="scope">
                  <el-input v-if="!testAccess" v-model="scope.row.name" type="text" />
                  <span v-else>{{ scope.row.name }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="content" label="指标说明" min-width="200">
                <template #default="scope">
                  <el-input v-if="!testAccess" v-model="scope.row.content" type="text" />
                  <span v-else>{{ scope.row.content }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="finish_info" label="完成情况 (自评)" min-width="180">
                <template #default="scope">
                  <el-input v-if="testAccess" v-model="scope.row.finish_info" type="text" />
                  <span v-else>{{ scope.row.finish_info }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="check_info" label="上级评价" min-width="180">
                <template #default="scope">
                  <el-input v-if="!testAccess" v-model="scope.row.check_info" type="text" placeholder="上级评价" />
                  <span v-if="testAccess">{{ scope.row.check_info }}</span>
                </template>
              </el-table-column>
              <el-table-column
                prop="ratio"
                :label="assessInfo.types == 0 ? '权重' : '分数'"
                min-width="100"
              >
                <template #default="scope">
                  <div class="current-task">
                    <el-input v-if="!testAccess" v-model="scope.row.ratio" type="text" />
                    <span v-else>{{ scope.row.ratio }}</span>
                    <span v-if="assessInfo.types == 0">%</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column v-if="assessInfo.types == 0" prop="task" label="完成度 (自评)" min-width="100">
                <template #default="scope">
                  <div class="current-task">
                    <el-progress
                      type="circle"
                      :show-text="false"
                      :stroke-width="3"
                      :width="18"
                      :percentage="Number(scope.row.finish_ratio)"
                    />
                    <el-input v-if="!testAccess" v-model="scope.row.finish_ratio" type="text" />
                    <span v-else style="padding-left: 6px;">{{ scope.row.finish_ratio }}</span> %
                  </div>
                </template>
              </el-table-column>
              <el-table-column v-else prop="task" label="自评分" min-width="100">
                <template #default="scope">
                  <div class="current-task">
                    <el-input v-if="testAccess" v-model="scope.row.finish_ratio" type="text" />
                    <span v-else>{{ scope.row.finish_ratio }}</span>
                  </div>
                </template>
              </el-table-column>
              <el-table-column prop="score" label="上级评分" min-width="80">
                <template #default="scope">
                  <div class="current-task">
                    <el-input-number
                      v-if="!testAccess"
                      v-model="scope.row.score"
                      placeholder="分数"
                      :controls="false"
                      :min="0"
                      :max="100"
                    />
                    <span v-if="testAccess">{{ scope.row.score }}</span>
                  </div>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
      </el-scrollbar>
    </div>
  </el-dialog>
</template>

<script>
export default {
  name: 'DetailsDialog',
  props: {
    config: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  data() {
    return {
      dialogVisible: false,
      where: {
        page: 1,
        limit: 15
      },
      tableData: [],
      total: 0,
      testAccess: true,
      assessInfo: {}
    }
  },
  watch: {
    config: {
      handler(nVal) {
        this.tableData = nVal.data.info
        this.assessInfo = nVal.data
      },
      deep: true
    }
  },
  methods: {
    handleOpen() {
      this.dialogVisible = true
    },
    handleClose() {
      this.dialogVisible = false
      this.tableData = []
    },
    sum(index) {
      var number = 0
      if (this.assessInfo.types == 0) {
        this.tableData[index].target.forEach((val) => {
          number += Number((this.tableData[index].ratio * val.ratio * (val.score ? val.score : 0)) / 10000)
        })
        number = ((Number(this.assessInfo.total) * number) / 100).toFixed(2)
      } else {
        this.tableData[index].target.forEach((val) => {
          number += Number(val.score ? val.score : 0)
        })
      }
      this.tableData[index].score = number
      return number
    },
    totalSum() {
      var number = 0
      this.tableData.forEach((val) => {
        number += Number(val.score)
      })
      number = number.toFixed(2)
      this.total = number
      return number
    }
  }
}
</script>
<style lang="scss" scoped>
.text-right {
  text-align: right;
}
.p8 {
  padding: 8px 12px;
}
.el-scrollbar__wrap {
  overflow-x: hidden;
  margin-left: 30px;
}
.total-score {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 10px;
  p {
    margin: 0 10px 0 0;
    padding: 0;
  }
  p:last-of-type {
    margin-right: 0;
  }
  .total-score-name {
    color: #7f7f7f;
    font-size: 13px;
  }
  .number {
    font-size: 17px;
  }
}
.current {
  height: 520px;
  width: 100%;
  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }
}
.current-table {
  margin: 15px 0;
  .current-table-title {
    display: flex;
    align-items: center;
    .current-title-input {
      background-color: transparent;
      font-size: 13px;
      font-weight: bold;
      color: #000000;
      border: none;
    }
    .current-title-input:focus {
      outline-style: none;
    }
    p {
      margin: 0;
      padding: 0;
      width: 50%;
    }
    p:last-of-type {
      padding-right: 10px;
      i {
        cursor: pointer;
      }
    }
    padding: 10px 0;
    background-color: #f7f8fa;
    span {
      font-size: 13px;
    }
    span:first-of-type {
      padding-left: 10px;
      padding-right: 15px;
      font-size: 13px;
      font-weight: bold;
      color: #000000;
    }
  }
  :deep(.el-input-number) {
    width: 30px;
  }
  :deep(.el-table th > .cell) {
    font-weight: normal;
    font-size: 13px;
  }
  :deep(.el-table td) {
    padding: 3px 0;
  }
  :deep(.el-input__wrapper) {
    min-height: 28px;
    padding-left: 3px;
    background-color: transparent;
  }
  :deep(.el-input__inner) {
    font-size: 13px;
    text-align: left;
  }
  :deep(.el-table--enable-row-hover) {
    .el-table__body tr:hover > td {
      background: transparent;
    }
  }
  .current-delete {
    color: #000000;
  }
  .current-task {
    display: flex;
    align-items: center;
    :deep(.el-input),
    .el-input-number {
      width: 30px;
    }
  }
}
.comment-list {
  .comment-list-left {
    width: 32px;
    height: 32px;
    margin-right: 15px;
    border-radius: 50%;
    background-color: #eeeeee;
    text-align: center;
    line-height: 32px;
    img {
      width: 100%;
      height: auto;
      border-radius: 50%;
    }
    i {
      font-size: 16px;
    }
  }
  .comment-list-name {
    padding: 0;
    margin: 0 0 10px 0;
    font-size: 13px;
    font-weight: bold;
  }
}
.current-footer {
  padding-top: 20px;
  border-top: 1px solid #f0f2f5;
}
:deep(.el-scrollbar__bar.is-vertical) {
  width: 0;
}
</style>
