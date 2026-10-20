<template>
  <div class="divBox">
    <el-card v-loading="loadingBox" class="card-box box-height">
      <template #header>
        <div class="acea-row row-between row-middle card-header">
          <div>
            <el-tabs v-model="activeName" @tab-change="handleClick">
              <el-tab-pane label="线索表单" name="4" />
              <el-tab-pane label="客户表单" name="1" />
              <el-tab-pane label="商机表单" name="5" />
              <el-tab-pane label="订单表单" name="2" />
              <el-tab-pane label="联系人表单" name="3" />
              <el-tab-pane label="产品表单" name="6" />
            </el-tabs>
          </div>
          <div class="flex">
            <div v-if="activeName == 4" class="sheBox mr10" @click="settingClue">
              <span class="el-icon-setting"></span>
            </div>

            <el-button @click="addGroup">新增分组</el-button>
            <el-button :loading="loading" type="primary" @click="submitFrom">保存</el-button>
          </div>
        </div>
      </template>

      <!-- 表单名称 -->
      <div v-for="(item, index1) in dataList" :key="index1" class="wrapper-item">
        <div class="flex-center bgcColor">
          <div class="headerLeft">
            <span>{{ item.title }}</span>
          </div>
          <div class="headerRight">
            <!-- <el-switch
              v-if="item.ident == 'product'"
              v-model="item.required"
              :active-value="1"
              :inactive-value="0"
              active-text="必填"
              inactive-text="选填"
            /> -->

            <el-tooltip content="显示隐藏分组" effect="dark" placement="top" v-if="item.ident != 'product'">
              <span v-if="item.status == 0" class="iconfont iconyincang pointer" @click="showCate(item)" />
              <span v-else class="iconfont icondakai pointer" @click="showCate(item)" />
            </el-tooltip>
            <el-tooltip content="编辑" effect="dark" placement="top">
              <span class="iconfont iconbianji1 pointer" @click="editCate(item)" />
            </el-tooltip>

            <template v-if="item.enable_delete == 1 && item.ident != 'product'">
              <el-tooltip content="删除" effect="dark" placement="top">
                <span class="iconfont iconshanchu pointer" @click="deleteCate(item.id)" />
              </el-tooltip>
            </template>
          </div>
        </div>

        <!-- 表格 -->
        <el-table ref="table" :data="item.data" class="table" row-key="id" style="width: 100%">
          <el-table-column fixed="left" label="字段名称" min-width="130px" prop="id">
            <template #default="scope">
              <el-input v-model="scope.row.key_name" class="input" placeholder="请输入"> </el-input>
            </template>
          </el-table-column>
          <!-- 字段类型 -->
          <el-table-column label="字段类型" min-width="130px" prop="type">
            <template #default="scope">
              <el-select
                v-model="scope.row.type"
                :disabled="scope.row.enable_delete !== 1"
                @change="scope.row.dict_ident = ''"
              >
                <el-option v-for="(item, index) in typeOptions" :key="index" :label="item.label" :value="item.value">
                </el-option>
              </el-select>
            </template>
          </el-table-column>
          <!-- 关联字典 -->
          <el-table-column label="关联字典" min-width="150px" prop="cate_id">
            <template #default="scope">
              <el-select
                v-model="scope.row.dict_ident"
                :disabled="scope.row.enable_delete !== 1 || typeDisabled.includes(scope.row.type)"
                clearable
                filterable
                placeholder="请选择关联字典"
                @change="getDictData(scope.row.dict_ident, index1, scope.$index)"
              >
                <el-option v-for="(item, index) in dictList" :key="index" :label="item.name" :value="item.ident">
                </el-option>
              </el-select>
            </template>
          </el-table-column>
          <!-- 是否必填 -->
          <el-table-column label="是否必填" prop="required">
            <template #default="scope">
              <el-switch
                v-model="scope.row.required"
                :active-value="1"
                :inactive-value="0"
                active-text="必填"
                inactive-text="选填"
              />
            </template>
          </el-table-column>
          <!-- 唯一校验 -->
          <el-table-column label="唯一校验" prop="uniqued">
            <template #default="{ row }">
              <el-switch
                v-model="row.uniqued"
                :active-value="1"
                :disabled="contractList.includes(row.key)"
                :inactive-value="0"
                active-text="唯一"
                inactive-text="重复"
              />
            </template>
          </el-table-column>
          <el-table-column label="提示信息" min-width="110px" prop="placeholder">
            <template #default="{ row }">
              <el-input v-model="row.placeholder" class="input" placeholder="请输入"> </el-input>
            </template>
          </el-table-column>
          <!-- 默认值 -->
          <el-table-column label="默认值" min-width="180px" prop="value">
            <template #default="{ row }">
              <el-input
                v-if="getTypes(row.type, 'select', 'radio', 'checked')"
                v-model="row.value"
                :disabled="row.type == 'file' || row.type == 'images'"
                class="input"
                placeholder="请输入默认值"
              >
              </el-input>

              <el-select v-else-if="row.type == 'radio'" v-model="row.value" placeholder="请选择默认值">
                <el-option v-for="el in row.optionItems" :key="el.value" :label="el.name" :value="el.value">
                </el-option>
              </el-select>
              <el-select
                v-else-if="row.type == 'checked'"
                multiple
                v-model="row.value"
                placeholder="请选择默认值"
              >
                <el-option v-for="el in row.optionItems" :key="el.value" :label="el.name" :value="el.value">
                </el-option>
              </el-select>

              <el-cascader
                v-else
                v-model="row.value"
                :disabled="row.key == 'customer_label'"
                :options="row.optionItems"
                :props="{
                  checkStrictly: false,
                  label: 'name',
                  value: 'value',
                  multiple: row.type == 'single' || row.type == 'radio' ? false : true
                }"
                clearable
                collapse-tags
                @change="changeCascader(row.value, index1, scope.$index)"
              ></el-cascader>
            </template>
          </el-table-column>
          <!-- 边界值 -->
          <el-table-column min-width="380">
            <template #header="scope">
              <el-popover placement="top" trigger="hover" width="300">
                <div class="tips-popover">
                  文本框、文本域的边界值为字数限制；<br />
                  数字的边界值为数值范围；<br />
                  下拉复选、复选按钮的边界值为选中数量；<br />日期控件的边界值为时间范围<br />
                  图片选择控件、附件控件的边界值为上传数量。
                </div>
                <template #reference>
                  <div>边界值 <span class="el-icon-info"></span></div>
                </template>
              </el-popover>
            </template>
            <template #default="scope">
              <div v-if="!['date', 'datetime', 'single'].includes(scope.row.type)" class="flex">
                <el-input-number
                  v-model="scope.row.min"
                  :disabled="maxDisabledList.includes(scope.row.key) || scope.row.type === 'oaWangeditor'"
                  :max="99999999"
                  :min="0"
                  controls-position="right"
                  style="width: 130px"
                ></el-input-number>
                <span class="m5"> - </span>

                <el-input-number
                  v-model="scope.row.max"
                  :disabled="maxDisabledList.includes(scope.row.key) || scope.row.type === 'oaWangeditor'"
                  :max="99999999"
                  :min="0"
                  controls-position="right"
                  style="width: 130px"
                ></el-input-number>
                <!-- 选择数字控制小数点 -->
                <el-input-number
                  v-if="scope.row.type == 'number'"
                  v-model="scope.row.decimal_place"
                  :max="10"
                  :min="0"
                  controls-position="right"
                  style="width: 100px; margin-left: 5px"
                ></el-input-number>
              </div>
              <div v-else class="flex">
                <span class="m5"> 不支持设置边界值 </span>
                <!-- <el-date-picker
                  v-model="scope.row.min"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  clearable
                  placeholder="请选择"
                  size="small"
                  style="width: 130px"
                  :type="scope.row.type == 'datetime' ? 'datetime' : 'date'"
                ></el-date-picker>
                <span class="m5"> - </span>
                <el-date-picker
                  v-model="scope.row.max"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  clearable
                  placeholder="请选择"
                  size="small"
                  style="width: 130px"
                  :type="scope.row.type == 'datetime' ? 'datetime' : 'date'"
                ></el-date-picker> -->
              </div>
            </template>
          </el-table-column>
          <!-- 状态 -->
          <el-table-column label="状态" prop="status">
            <template #default="{ row }">
              <el-switch
                v-model="row.status"
                :active-value="1"
                :disabled="row.enable_delete !== 1"
                :inactive-value="0"
                active-text="启用"
                inactive-text="停用"
              />
            </template>
          </el-table-column>
          <el-table-column fixed="right" label="操作" min-width="150px" prop="address">
            <template #default="scope">
              <el-button type="primary" link @click="moveFn(scope.row)">移动分组</el-button>
              <el-button v-if="scope.row.enable_delete == 1" type="primary" link @click="deleteFn(scope, index1)"
                >删除</el-button
              >
            </template>
          </el-table-column>
          <template #empty>
            <div>
              {{
                ['5', '2'].includes(activeName) && item.ident == 'product'
                  ? '产品清单的字段在添加产品信息中维护'
                  : '暂无数据'
              }}
            </div>
          </template>
        </el-table>
        <div class="add-row" v-if="item.ident != 'product'">
          <span class="pointer" @click.stop="addANewLine(index1)"><span class="el-icon-plus mr5"></span>添加字段</span>
        </div>
      </div>
      <!-- <div v-if="['5', '2'].includes(activeName)" style="border: 1px solid #e6ebf5">
        <div class="flex-center bgcColor">
          <div class="headerLeft">
            <span>产品清单</span>
          </div>
          <div class="headerRight">
            <el-tooltip content="编辑" effect="dark" placement="top">
              <span class="iconfont iconbianji1 pointer" @click="editCate(item)" />
            </el-tooltip>
          </div>
        </div>
        <div class="productTips">产品清单的字段在添加产品信息中维护</div>
      </div> -->
    </el-card>

    <!-- 新增分组弹窗组件 -->
    <oaDialog
      ref="oaDialog"
      :formConfig="formConfig"
      :formDataInit="formDataInit"
      :formRules="formRules"
      :fromData="fromData"
      @submit="submit"
    ></oaDialog>
    <!-- 线索转客户 -->
    <clueToCustomers
      ref="clueToCustomers"
      v-if="clueList.length > 0"
      :clueList="clueList"
      @gridData="gridData"
    ></clueToCustomers>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import Sortable from 'sortablejs'
import { configConvertApi } from '@/api/client.ts'
import common from './components/customCommon'
import { createDictTreeCache } from '@/utils/dictTreeCache.ts'
import {
  getFormListApi,
  formCateSaveApi,
  formCateDeleteApi,
  formCatePutSaveApi,
  formCatePutApi,
  getDictListApi,
  formCateSaveDataApi,
  formCateMoveApi
} from '@/api/form.ts'
export default {
  name: '',
  components: {
    oaDialog: defineAsyncComponent(() => import('@/components/form-common/dialog-form')),
    clueToCustomers: defineAsyncComponent(() => import('././components/clueToCustomers'))
  },
  data() {
    return {
      // 弹窗样式
      loadingBox: false,
      loading: false,
      fromData: {
        title: '新增分组',
        type: 'add',
        width: '500px',
        labelWidth: '80px',
        btnText: '确定'
      },
      clueList: [],
      formConfig: [],
      formDataInit: {},
      label: '1',
      formRules: {
        title: [
          {
            required: true,
            message: '请输入分组名称',
            trigger: 'blur'
          }
        ]
      },
      id: '', // 表单rowid
      cate_id: '', // 分组id
      move: false, //判断当前是移动分组/新增分组
      dataList: [], // 自定义表单列表
      typeOptions: common.typeOptions, // 字段类型选项
      typeDisabled: common.typeDisabled, // 字段类型选项
      dictList: [], // 字典选项
      groupList: [], // 分组列表
      activeName: '4',
      contractList: common.contractList,
      maxDisabledList: common.maxDisabledList
    }
  },

  created() {
    // 本页内复用的字典树缓存，离开页面随实例释放
    this.dictTreeCache = createDictTreeCache()
  },

  mounted() {
    this.getDictList()
    this.getList()
  },


  methods: {
    gridData(val) {
      configConvertApi('customer', { data: val }).then((res) => {})
    },
    // 组合新增分组表单内容
    addGroupingData() {
      this.formConfig = [
        {
          key: 'title',
          label: '分组名称:',
          type: 'input',
          maxlength: 20,
          placeholder: '请输入分组名称'
        },
        {
          key: 'sort',
          label: '排序:',
          type: 'inputNumber',
          placeholder: '请输入排序，数字越大越靠前'
        }
      ]
      this.formDataInit = {
        title: '',
        sort: ''
      }
    },

    // 获取字典列表
    async getDictList() {
      let data = {
        page: 1,
        limit: ''
      }
      const result = await getDictListApi(data)
      this.dictList = result.data.list.filter((item) => {
        return item.status == 1
      })
    },

    // 获取默认值的列表
    changeCascader(val, index1, index) {
      let arr = val.value.map((str) => parseInt(str))
      this.dataList[index1].data[index].value = arr
    },

    // 获取下拉框的默认值
    async getDictData(dict_ident, index1, index) {
      let data = {
        level: '',
        types: dict_ident
      }
      this.dictTreeCache
        .load(data)
        .then((list) => {
          setTimeout(() => {
            this.dataList[index1].data[index].optionItems = list
          }, 300)
        })
        .catch(() => undefined)
    },

    // 获取分组集合
    getGroupList() {
      this.groupList = []
      if (this.dataList.length > 0) {
        this.dataList.map((item) => {
          let data = {
            id: item.id,
            name: item.title
          }
          this.groupList.push(data)
        })
      }
    },

    // 获取表单分组列表
    async getList() {
      this.loadingBox = true
      const result = await getFormListApi({ types: this.activeName })
      await result.data.forEach((item, index) => {
        item.enable_delete = 1
        item.data.forEach((val, index1) => {
          val.optionItems = []
          if (val.enable_delete == 0) {
            result.data[index].enable_delete = 0
          }
          if (val.dict_type_id == 0) {
            val.dict_type_id = ''
          }
          if (
            val.type == 'date' &&
            val.type == 'datetime' &&
            typeof val.max !== 'string' &&
            typeof val.min !== 'string'
          ) {
            val.max = ''
            val.min = ''
          }
          if (val.type === 'radio') {
            // val.value = Number(val.value)
            val.value = val.value + ''
          }
          if (!this.getTypes(val.type, 'radio', 'checked', 'select')) {
            let data = {
              level: '',
              types: val.dict_ident
            }
            if (val.dict_ident) {
              this.dictTreeCache.load(data).then((list) => {
                setTimeout(() => {
                  if (list) {
                    val.optionItems = list
                  }
                }, 300)
              })
            }
          }
        })
      })
      if (this.activeName == 4) {
        this.clueList = result.data
      }
      this.dataList = result.data
      this.getGroupList()
      this.loadingBox = false
      setTimeout(() => {
        this.rowDrop()
      }, 300)
    },

    // 设置线索客户关联
    settingClue() {
      this.$refs.clueToCustomers.openBox()
    },

    // 移动分组
    moveFn(row) {
      this.id = row.id
      this.move = true
      this.fromData.title = '移动分组'
      this.fromData.type = 'add'
      this.formConfig = [
        {
          key: 'itemId',
          label: '分组名称:',
          type: 'select',
          maxlength: 20,
          placeholder: '请选择分组',
          options: this.groupList
        }
      ]
      this.formDataInit = {
        itemId: ''
      }
      this.$refs.oaDialog.openBox()
    },

    // 编辑分组
    editCate(row) {
      this.addGroupingData()
      this.cate_id = row.id
      this.fromData.title = '编辑分组'
      this.fromData.type = 'edit'
      this.formDataInit.title = row.title
      this.formDataInit.sort = row.sort
      this.$refs.oaDialog.openBox()
    },

    // 删除
    deleteFn(row, index) {
      modalSure('你确定要删除这条数据吗').then(() => {
        this.dataList[index].data.splice(row.$index, 1)
      })
    },

    // 修改分组状态
    async showCate(row) {
      this.cate_id = row.id
      let data = {
        status: row.status === 1 ? 0 : 1
      }
      await formCatePutApi(this.cate_id, data)
      row.status = row.status === 1 ? 0 : 1
      // await this.getList()
    },

    // 保存表单
    async submitFrom() {
      let result = true
      if (!result) {
        return this.$message.error('请选择职位')
      }

      this.dataList.forEach((item) => {
        item.cate_id = item.id
        item.data.forEach((val) => {
          if (!val.key_name || !val.type) {
            result = false
          }
          this.typeOptions.map((type) => {
            if (type.value === val.type) {
              val.input_type = type.type
            }
          })
        })
      })
      if (!result) {
        return this.$message.error('字段名称和字段类型不能为空，请重新输入')
      }
      let list = this.dataList
      list.map((item) => {
        item.data.map((val) => {
          delete val.optionItems
        })
      })
      this.loading = true
      formCateSaveDataApi(this.activeName, { data: list })
        .then((res) => {
          if (res.status === 200) {
            this.loading = false
          } else {
            this.loading = false
          }
        })
        .catch((err) => {
          this.loading = false
        })
    },

    // 保存分组
    async submit(val) {
      if (this.move) {
        // 移动分组弹窗提交
        this.cate_id = val.itemId
        let data = {
          id: this.id,
          cate_id: this.cate_id
        }
        await formCateMoveApi(this.activeName, data)
      } else {
        // 新增分组弹窗提交
        if (this.cate_id) {
          await formCatePutSaveApi(this.cate_id, val)
        } else {
          await formCateSaveApi(this.activeName, val)
        }
      }
      await this.$refs.oaDialog.handleClose()
      await this.getList()
      this.move = false
      this.cate_id = ''
      this.id = ''
      this.formDataInit = {}
    },

    // 删除分组
    async deleteCate(id) {
      await modalSure('确认删除此数据吗')
      await formCateDeleteApi(id)
      await this.getList()
    },

    // 新增分组
    addGroup() {
      this.addGroupingData()
      this.fromData.title = '新增分组'
      this.fromData.type = 'add'
      this.$refs.oaDialog.openBox()
    },

    // 自动新增行
    addANewLine(index) {
      const list = {
        key_name: '',
        type: '',
        dictionary: '',
        optionItems: [],
        enable_delete: 1,
        value: '',
        status: 1,
        min: 1,
        max: 10
      }
      this.dataList[index].data.push(list)
    },

    handleClick(tab, event) {
      this.getList()
    },

    // 判断是否是下拉选择
    getTypes(row, type1, type2, type3) {
      const result = this.typeOptions.find((item) => row == item.value)
      if (result) {
        return result.type !== type1 && result.type !== type2 && result.type !== type3
      }
    },

    // 表格拖拽排序
    rowDrop() {
      let list = this.$refs.table
      if (!Array.isArray(list) || list.length === 0) return
      list.map((item, index) => {
        const tbody = item?.$el?.querySelector('.el-table__body-wrapper tbody')
        if (!tbody) return
        Sortable.create(tbody, {
          animation: 300,
          onEnd: (e) => {
            const targetRow = this.dataList[index].data.splice(e.oldIndex, 1)[0]
            this.dataList[index].data.splice(e.newIndex, 0, targetRow)
            let data = []
            this.dataList[index].data.map((item) => {
              data.push(item.id)
            })
          }
        })
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.flex-center {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.sheBox {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  line-height: 32px;
  text-align: center;
  margin-left: 10px;
  cursor: pointer;
  .el-icon-setting {
    cursor: pointer;
    margin-bottom: 12px;
    font-size: 14px;
    color: #909399;
  }
}
.bgcColor {
  padding: 0 14px;
  width: 100%;
  height: 44px;
  background: #f5f7fa;
}
.tips {
  font-size: 12px;
  color: #909399;
}
.headerLeft {
  position: relative;
  font-size: 14px;
  font-family: PingFangSC, PingFang SC;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.85);
  span {
    margin-left: 10px;
  }
}
.headerLeft:before {
  content: '';
  background-color: #1890ff;
  width: 2px;
  height: 14px;
  position: absolute;
  left: 0;
  top: 50%;
  margin-top: -7px;
  -webkit-border-radius: 3px;
  -moz-border-radius: 3px;
  border-radius: 3px;
}
.headerRight {
  color: #1890ff;
  span {
    margin-left: 10px;
  }
}
.m5 {
  margin: 5px;
  color: #909399;
}
.add-row {
  height: 44px;
  font-size: 12px;
  color: #1890ff;
  font-weight: 500;
  line-height: 44px;
  border-bottom: 1px solid #f5f7fa;
}
.tips-popover {
  font-size: 13px;
}
.card-box {
  min-height: calc(100vh - 77px);
  overflow-y: scroll;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #303133;
  :deep(.el-card__header) {
    padding: 0 20px;
  }
}
.el-icon-info {
  color: #1890ff;
}
.cr-bottom-button {
  position: fixed;
  left: 0px;
  right: 0;
  bottom: 0;
  width: calc(100% + 220px);
}
:deep(.card-header) {
  .el-tabs__header {
    margin: 0;
  }
  .el-tabs__nav-wrap::after {
    display: none;
  }
  .el-tabs__item {
    height: 60px;
    line-height: 60px;

    &:not(.is-active) {
      font-weight: 500;
    }
  }
}
.wrapper-item {
  margin-bottom: 30px;
  &:last-child {
    margin-bottom: 0;
  }
}
.productTips {
  font-size: 14px;
  height: 44px;
  line-height: 44px;
  text-align: center;
}
:deep(.el-table__inner-wrapper::before) {
  background-color: #fff;
}
</style>
