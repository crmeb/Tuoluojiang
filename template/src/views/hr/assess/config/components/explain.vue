<template>
  <div>
    <el-row>
      <el-col>
        <div class="main">
          <div class="title-16 mb20">考核评分</div>
          <div class="v-height-flag">
            <div class="input-suffix mb-14 pt-20" style="border: none">
              <label for="">评分方式：</label>
              <el-radio v-model="radio" :label="1" :value="1">加和评分</el-radio>
              <el-radio v-model="radio" :label="0" :value="0">加权评分</el-radio>
            </div>
            <div class="input-suffix">
              <label for="">评分说明：</label>
              <el-input
                v-model="scoreMark"
                type="textarea"
                resize="none"
                autosize
                placeholder="请输入内容"
              />
            </div>
            <div class="suffix-content v-height-flag">
              <el-table :data="tableData" style="width: 100%">
                <el-table-column prop="name" label="等级名称" width="130">
                  <template #default="scope">
                    <el-input
                      style="width: 130px"
                      v-model="scope.row.name"
                      type="text"
                      placeholder="输入等级"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="分数区间范围" width="260">
                  <template #default="scope">
                    <div class="number-section">
                      <span class="number-box">
                        <span>{{ scope.row.min }}</span> <span>{{ scope.$index > 0 ? '&lt;' : '≤' }}</span>
                        <span>分数</span>
                        <span>≤</span>
                      </span>
                      <el-input-number
                        class="ml14"
                        v-model="scope.row.max"
                        placeholder="输入分数"
                        :controls="false"
                        :min="scope.row.min + 1"
                        :max="10000"
                        @change="maxChange(scope.$index)"
                      />
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="mark" label="等级说明">
                  <template #default="scope">
                    <div class="number-explain">
                      <el-input
                        v-model="scope.row.mark"
                        style="width: 367px"
                        clearable
                        type="text"
                        placeholder="等级说明"
                      />
                      <div class="text-delete" @click="handleDelete(scope.$index)">删除</div>
                    </div>
                  </template>
                </el-table-column>
              </el-table>
            </div>
            <div class="color189 add-btn">
              <span @click="addAssess">+添加等级</span>
            </div>
            <!-- <div v-hasPermi="['hr:assessConfig:assessmentScore:save']">
              <el-button type="primary" size="small">保存 </el-button>
            </div> -->
          </div>
        </div>
      </el-col>
    </el-row>
    <div class="cr-bottom-button">
      <el-button type="primary" :loading="loading" @click="handlePreserve">保存</el-button>
    </div>
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { modalSure } from '@/libs/public.ts'
import { assessScoreApi, assessScoreUpdateApi } from '@/api/enterprise.ts'
export default {
  name: 'AssessExplain',
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      tableData: [],
      scoreMark: '',
      radio: 0,
      loading: false
    }
  },
  created() {
    this.getAssessList()
  },
  methods: {
    async getAssessList() {
      const result = await assessScoreApi()
      this.scoreMark = result.data.score_mark
      this.radio = result.data.compute_mode
      this.tableData = result.data.score_list.list ? result.data.score_list.list : []
    },
    handleDelete(index) {
      modalSure('您确定要删除这条记录吗').then(() => {
        this.tableData.splice(index, 1)
      })
    },
    async getInfo() {
      await this.userStore.fetchUserInfo()
    },
    handlePreserve() {
      const t = this.checkAssessList()
      if (t) {
        const data = {
          compute_mode: this.radio,
          score_mark: this.scoreMark,
          score_list: this.tableData
        }
        this.loading = true
        assessScoreUpdateApi(data)
          .then((res) => {
            if (res.status == 200) {
              this.loading = false
              this.getInfo()
            }
            this.getAssessList()
          })
          .catch((error) => {
            this.loading = false
          })
      }
    },
    maxChange(index) {
      this.tableData[index + 1].min = this.tableData[index].max
    },
    addAssess() {
      if (this.tableData && this.tableData.length > 0) {
        const lastIndex = this.tableData.length - 1
        const t = this.checkAssessList()
        if (t) {
          this.tableData.push({
            name: '',
            min: this.tableData[lastIndex].max,
            mark: '',
            max: ''
          })
        }
      } else {
        this.tableData.push({
          name: '',
          min: 0,
          mark: '',
          max: ''
        })
      }
    },
    checkAssessList() {
      if (this.tableData.length === 0) {
        this.$message.error('请添加内容')
        return false
      }
      const lastIndex = this.tableData.length - 1
      if (this.tableData[lastIndex].max == '') {
        this.$message.error('分数区间值不能空')
      } else if (this.tableData[lastIndex].min >= this.tableData[lastIndex].max) {
        this.$message.error('分数区间值不能小于等于最小值')
      } else if (this.tableData[lastIndex].name == '') {
        this.$message.error('等级名称不能为空')
      } else if (this.tableData[lastIndex].mark == '') {
        this.$message.error('等级说明不能为空')
      } else {
        return true
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.main {
  max-width: 800px;
}

.pl50 {
  padding-left: 250px;
}
.color189 {
  color: #1890ff;
}
.add-btn {
  margin-top: 20px;
  margin-bottom: 10px;
  font-size: 13px;
  span {
    cursor: pointer;
  }
}
.input-suffix {
  display: flex;
  padding-bottom: 20px;
  margin-bottom: 15px;

  label {
    width: 80px;
    font-weight: normal;
  }
  :deep(.el-textarea__inner) {
    height: 80px !important;
  }
}
.suffix-content {
  :deep(.el-table:before) {
    height: 0;
  }

  :deep(.el-table__row > td) {
    border: none;
  }
  :deep(.el-table__inner-wrapper::before) {
    height: 0px;
  }
  :deep(.el-table th) {
    border: none !important;
    padding: 0px 0;
    background: #ffffff;

    .cell {
      background: #ffffff !important;
      font-weight: normal;
      font-size: 13px;
    }
  }
  :deep(.el-table td) {
    border-bottom: none;
    // padding-top: 20px;
  }
  :deep(td.el-table__cell) {
    border: none !important;
  }
  :deep(.el-table th) {
    background: #ffffff !important;
  }
  // 旧版 80px 的规则只匹配 medium 尺寸，此处实际为 small 默认宽 130px
  :deep(.el-input-number) {
    width: 130px;
  }
  // 旧版边框画在 input 上并缩到 80%，Element Plus 边框在 wrapper 上，改为缩 wrapper
  :deep(.el-input__wrapper) {
    flex-grow: 0;
    width: 80%;
  }
  :deep(.el-input__inner) {
    text-align: left;
    font-size: 14px;
  }
  .number-section {
    .number-box {
      display: inline-block;
      width: 80px;
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 13px;
      color: rgba(0, 0, 0, 0.851);
    }
    :deep(.el-input) {
      width: 140px;
    }
    // 旧版数字框内层 el-input 为 140px（超出外框），80% 后为 112px
    :deep(.el-input-number .el-input__wrapper) {
      width: 112px;
    }
  }
  .number-explain {
    display: flex;
    align-items: center;
    :deep(.el-input) {
      width: 80%;
      .el-input__wrapper {
        width: 100%;
      }
    }
    .text-delete {
      cursor: pointer;
      width: 10%;

      text-align: right;
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 13px;
      color: rgba(0, 0, 0, 0.1922);
    }
    :deep(.disable) {
      pointer-events: none;
    }
  }
}
.cr-bottom-button {
  position: absolute;
  left: 0px;
  right: 0;
  bottom: -56px;
  width: 100%;
  border-radius: 0 0 8px 8px;
}

:deep(.el-table .cell) {
  padding-right: 0;
}
</style>
