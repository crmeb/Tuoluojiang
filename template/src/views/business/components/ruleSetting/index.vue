<!--审批设置-规则配置 -->
<template>
  <div class="ruleSetting">
    <el-card class="box-card">
      <div class="setting-container mt14">
        <el-row>
          <el-col v-bind="grid1">&nbsp;</el-col>
          <el-col v-bind="grid2">
            <el-form ref="elForm" :model="examineFrom" :rules="rules" size="default" label-width="150px">
              <el-form-item label="异常处理：">
                <el-radio-group v-model="examineFrom.abnormal">
                  <el-radio :label="0" :value="0">自动同意</el-radio>
                  <el-radio :label="1" :value="1">转交给指定人员处理</el-radio>
                </el-radio-group>
                <select-member
                  v-if="examineFrom.abnormal === 1"
                  :only-one="true"
                  :value="dataList || []"
                  @getSelectList="getSelectList"
                  style="width: 250px"
                ></select-member>
              </el-form-item>
              <el-form-item label="自动审批：">
                <div>当同一个审批人重复审批同一单据时</div>
                <el-radio-group v-model="examineFrom.auto" class="shu">
                  <el-radio :label="0" :value="0">仅首个节点需审批，其余自动同意</el-radio>
                  <el-radio :label="1" :value="1">仅连续审批时自动同意</el-radio>
                  <el-radio :label="2" :value="2">每个节点都需要审批</el-radio>
                </el-radio-group>
              </el-form-item>
              <el-form-item label="修改权限：" class="shu">
                <el-checkbox-group v-model="examineFrom.edit">
                  <el-checkbox :label="1" :value="1">提交申请时，员工不可修改固定人审批人</el-checkbox>
                  <el-checkbox :label="2" :value="2">提交申请时，员工不可删除固定抄送人</el-checkbox>
                </el-checkbox-group>
              </el-form-item>
              <el-form-item label="撤销权限：">
                <el-checkbox v-model="examineFrom.recall" :true-value="1" :false-value="0">通过后允许撤销</el-checkbox>
                <div class="explain">审批通过后，经审批人同意可撤销</div>
              </el-form-item>
              <el-form-item label="加签权限：">
                <el-checkbox v-model="examineFrom.is_sign" :true-value="1" :false-value="0">
                  允许在审批单中增加临时审批人
                </el-checkbox>
              </el-form-item>
            </el-form>
          </el-col>
          <el-col v-bind="grid1">&nbsp;</el-col>
        </el-row>
      </div>
    </el-card>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
export default {
  name: 'RuleSetting',
  components: {
    selectMember: defineAsyncComponent(() => import('@/components/form-common/select-member'))
  },
  props: {
    tabName: {
      type: String,
      default: ''
    },
    conf: {
      type: Object,
      default: () => {
        return null
      }
    }
  },
  data() {
    return {
      grid1: {
        xl: 8,
        lg: 5,
        md: 24,
        sm: 24,
        xs: 24
      },
      grid2: {
        xl: 8,
        lg: 14,
        md: 24,
        sm: 24,
        xs: 24
      },
      checkList: [],
      title: '',
      type: 0,
      examineFrom: {
        abnormal: 0,
        auto: 0,
        edit: [1, 2],
        recall: 0,
        is_sign: 0
      },
      rules: {},
      showAll: true,
      checkedName: '',
      userList: [],
      dataList: []
    }
  },
  created() {
    if (typeof this.conf === 'object' && this.conf !== null) {
      if (Number(this.conf.abnormal) > 0) this.conf.abnormal = 1
      this.conf.edit = this.conf.edit.map(Number)
      // 单个复选框按 1 / 0 绑定
      this.conf.recall = Number(this.conf.recall) > 0 ? 1 : 0
      this.conf.is_sign = Number(this.conf.is_sign) > 0 ? 1 : 0
      this.conf.auto = Number(this.conf.auto)
      if (this.conf.ab_card) {
        this.conf.ab_card.value = this.conf.ab_card.id
        this.dataList = [this.conf.ab_card]
      }
      if (this.conf.ab_card) this.checkedName = this.conf.ab_card.name
      Object.assign(this.examineFrom, this.conf)
    }
  },
  methods: {
    // 选择成员完成回调
    getSelectList(data) {
      this.dataList = data
      this.checkedName = data[0].name
      data.forEach((item) => {
        item.id = item.value
      })
      this.examineFrom.ab_card = data[0]
    },

    // 给父级页面提供得获取本页数据得方法
    getData() {
      return new Promise((resolve, reject) => {
        this.$refs['elForm'].validate((valid) => {
          if (!valid) {
            reject({ target: this.tabName })
            return
          }
          if (this.examineFrom.abnormal === 1) {
            if (!this.checkedName) {
              this.$message.warning('请选择指定人员')
              return
            }
            if (this.dataList.length > 0) {
              this.examineFrom.abnormal = this.dataList[0].value
            } else {
              this.examineFrom.abnormal = this.conf.ab_card.id
            }
          } else {
            this.examineFrom.ab_card = ''
          }
          resolve({ ruleFrom: this.examineFrom, target: this.tabName })
        })
      })
    }
  }
}
</script>

<style scoped lang="scss">
.shu {
  :deep(.el-radio),
  :deep(.el-checkbox) {
    display: block;
    height: 36px;
    line-height: 36px;
  }
}
// 撑满编辑页主体区域（原 calc(100vh - 130px) 比主体高，编辑页出现滚动条）
.ruleSetting {
  height: 100%;
  :deep(.el-card) {
    height: 100%;
  }
}
.explain {
  // 表单项内容区是 flex 换行容器，占满一行才会排到复选框下方
  width: 100%;
  font-size: 13px;
  line-height: 20px;
  color: #bbbbbb;
  // 与复选框文字中心相距 30px（同线上）
  margin-top: 4px;
}

.plan-footer-one {
  display: flex;
  justify-content: space-between;
  min-width: 277px;
  width: 277px;
}

.select-bar {
  display: flex;
}

:deep(.el-form-item__content) {
  flex: 1;
}

.flex-box {
  span {
    margin-right: 6px;
  }
  span:last-of-type {
    margin-right: 0;
  }
}

.el-icon-arrow-down {
  line-height: 38px;
}
</style>
