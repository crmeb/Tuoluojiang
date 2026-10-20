<template>
  <div>
    <el-dialog v-model="show" :width="DIALOG_SIZE.XL" :show-close="false">
      <template #header>
        <div class="flex-between">
          <span>选择MCP</span>
          <span class="el-icon-close" @click="handleClose"></span>
        </div>
      </template>
      <div class="search-box">
        <div class="flex lh-center">
          <span class="shrink mr10">共{{ total }}项</span>
          <el-input
            v-model="where.name"
            placeholder="请输入MCP名称"
            prefix-icon="el-icon-search"
            @change="handleSearch"
          />
          <div class="reset ml10 shrink" @click="resetSearch()"><i class="iconfont iconqingchu"></i></div>
        </div>
        <el-button @click="handleCustom" type="primary" icon="el-icon-plus">自定义创建</el-button>
      </div>
      <div class="box-content">
        <div class="box-list">
          <div
            class="box"
            v-for="(item, index) in list"
            :key="index"
            :class="{ active: isActive === index }"
            @click="handleBoxClick(index)"
          >
            <div class="check-box">
              <span
                @click.stop="handleSelect(item, 0)"
                class="iconfont icontongyonggouxuan-01"
                v-if="selectIds.includes(item.id)"
              />
              <span @click.stop="handleSelect(item, 1)" class="iconfont iconweigouxuan" v-else />
            </div>
            <div style="line-height: 1.8; flex: 1">
              <span class="title"
                >{{ item.name }}
                <span class="tip1" v-if="item.is_default == 1">系统</span>
                <span class="tip2" v-if="item.is_default != 1">自定义</span>
              </span>
              <span class="over-text1 mr24">
                {{ item.info }}
              </span>
            </div>

            <!-- 系统内置MCP只读，编辑与删除都不提供入口 -->
            <div class="edit-box" v-if="item.is_default != 1">
              <span class="el-icon-edit-outline right" title="编辑" @click.stop="handleEdit(item)" />
              <span class="el-icon-delete right" title="删除" @click.stop="handleDelete(item.id)" />
            </div>
          </div>
        </div>
      </div>

      <div>
        <el-pagination
          :current-page="where.page"
          :page-size="where.limit"
          :page-sizes="[10, 20, 30]"
          :total="total"
          layout="total, sizes,prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="pageChange"
        >
        </el-pagination>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="handleClose">取消</el-button>
          <el-button type="primary" @click="submitFn">确定</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 自定义mcp：名称与简介自动从标准 MCP 配置 JSON 中提取 -->
    <oa-dialog :fromData="fromData" ref="oaDialog" @submit="submit">
      <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-position="top" class="demo-ruleForm">
        <el-form-item label="JSON配置" prop="config_json">
          <code-editor mode="json" :readonly="false" v-model="ruleForm.config_json"></code-editor>
          <div class="form-tip">
            <i class="el-icon-info"></i>
            采用标准 MCP 协议格式，名称与简介自动从 server 节点的 name / description 字段提取
          </div>
        </el-form-item>
      </el-form>
    </oa-dialog>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public.ts'
import { getMcpListApi, saveMcpServiceApi, getMcpServiceApi, putMcpServiceApi, delMcpServiceApi } from '@/api/chatAi.ts'
import oaDialog from '@/components/form-common/dialog-form'
import CodeEditor from '@/components/code-editor/index'
import { DIALOG_SIZE } from '@/constants/popupSize.ts'
import { APP_TYPE } from '../constants.ts'

export default {
  emits: ['mcpSubmit'],
  name: 'AddMcp',
  components: {
    oaDialog,
    CodeEditor
  },
  data() {
    return {
      DIALOG_SIZE,
      show: false,
      selectIds: [],
      selectList: [],
      isActive: -1,
      ruleForm: {
        config_json: '',
        status: 1,
        sort: 0
      },
      id: 0,
      total: 0,
      // 外部应用不能绑定系统内置MCP，列表要按 is_default 过滤
      appType: APP_TYPE.INTERNAL,
      where: {
        page: 1,
        limit: 10,
        name: ''
      },
      rules: {
        config_json: [{ required: true, message: '请输入JSON配置', trigger: 'blur' }]
      },
      fromData: {
        width: DIALOG_SIZE.MD,
        title: '自定义MCP',
        btnText: '确定',
        type: 'slot'
      },
      list: []
    }
  },
  mounted() {},
  methods: {
    resetSearch() {
      this.where.name = ''
      this.where.page = 1
      this.getList()
    },
    handleSearch() {
      this.where.page = 1
      this.getList()
    },
    openBox(appType, data) {
      if (data) {
        this.selectIds = data.map((item) => item.id)
        this.selectList = data.slice()
      }
      this.appType = Number(appType) || APP_TYPE.INTERNAL
      this.where.page = 1
      this.show = true
      this.isActive = -1
      this.getList()
    },

    getList() {
      const params = { ...this.where }
      // 外部应用只能绑定自定义MCP
      if (this.appType === APP_TYPE.EXTERNAL) params.is_default = 0

      getMcpListApi(params).then((res) => {
        this.list = res.data.list
        this.total = res.data.count
      })
    },

    handleSelect(item, type) {
      if (type == 1) {
        this.selectIds.push(item.id)
        this.selectList.push(item)
      } else {
        this.selectIds = this.selectIds.filter((id) => id !== item.id)
        this.selectList = this.selectList.filter((s) => s.id !== item.id)
      }
    },
    handleClose() {
      this.show = false
    },
    handleBoxClick(index) {
      this.isActive = index
    },
    submit() {
      if (!this.$refs.ruleForm) return

      this.$refs.ruleForm.validate(async (valid) => {
        if (!valid) return

        let configJson = this.ruleForm.config_json
        if (configJson) {
          try {
            configJson = typeof configJson === 'string' ? JSON.parse(configJson) : configJson
          } catch (error) {
            this.$message.error('JSON配置格式不正确，请检查后重试')
            return
          }
        }

        // 名称/简介由后端从标准 MCP 配置 JSON 自动提取，提交体只传 config_json / status / sort
        const params = {
          config_json: configJson,
          status: Number(this.ruleForm.status) === 0 ? 0 : 1,
          sort: Number(this.ruleForm.sort) || 0
        }

        const api = this.id ? putMcpServiceApi(this.id, params) : saveMcpServiceApi(params)
        const res = await api

        if (res.status == 200) {
          this.getList()
          this.$refs.oaDialog.handleClose()
        }
      })
    },

    submitFn() {
      if (this.selectList.length == 0) {
        this.$message.error('请选择要添加的MCP')
        return false
      }
      this.show = false
      this.$emit('mcpSubmit', this.selectList)
    },

    handleSizeChange(val) {
      this.where.limit = val
      this.where.page = 1
      this.getList()
    },
    pageChange(page) {
      this.where.page = page
      this.getList()
    },

    handleEdit(data) {
      // 弹窗里没有状态与排序，回填原值提交，避免被接口默认值覆盖；名称/简介由后端保留
      this.ruleForm.status = data.status
      this.ruleForm.sort = data.sort
      let json = JSON.stringify(data.config_json, null, 2)
      this.ruleForm.config_json = json
      this.id = data.id
      this.$refs.oaDialog.openBox()
    },

    // 删除
    handleDelete(id) {
      modalSure('确定删除此MCP服务吗').then(async () => {
        delMcpServiceApi(id).then((res) => {
          if (res.status == 200) {
            this.getList()
          }
        })
      })
    },
    handleCustom() {
      // 标准 MCP 协议模板：name/description 用于自动提取名称与简介
      let json = JSON.stringify(
        {
          mcpServers: {
            'my-mcp': {
              name: '我的MCP服务',
              url: 'http://127.0.0.1:8080/mcp',
              headers: {
                Authorization: 'Bearer your-token-123456'
              },
              timeout: 15,
              description: 'MCP服务简介，会展示在列表中',
              disabled: false
            }
          }
        },
        null,
        2
      )
      this.ruleForm.config_json = json
      this.ruleForm.status = 1
      this.ruleForm.sort = 0
      this.id = ''
      this.$refs.oaDialog.openBox()
    }
  }
}
</script>
<style lang="scss" scoped>
.search-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.check-box {
  cursor: pointer;
  color: #cccccc;
  font-size: 13px;
  margin-right: 14px;
  .icontongyonggouxuan-01 {
    color: #1890ff;
  }
}
.box-content {
  height: 400px;
  overflow-y: auto;
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
}
.box-list {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;

  .box {
    position: relative;
    // 两列自适应，弹窗宽度调整后不用再改死值
    width: calc(50% - 7px);
    height: 63px;
    padding: 12px;
    padding-right: 24px;
    border-radius: 4px;
    border: 1px solid #dcdfe6;
    display: flex;
    align-items: center;
    font-size: 12px;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    color: #909399 !important;
    .tip1 {
      height: 16px;
      line-height: 16px;
      padding: 0 4px;
      margin-left: 4px;
      border-radius: 2px 2px 2px 2px;
      background: rgba(24, 144, 255, 0.15);
      font-size: 10px;
      color: #1890ff;
    }
    .tip2 {
      height: 16px;
      line-height: 16px;
      padding: 0 4px;
      margin-left: 4px;
      border-radius: 2px 2px 2px 2px;
      background: rgba(255, 153, 0, 0.15);
      font-size: 10px;
      color: #ff9900;
    }
    .title {
      font-weight: 500;
      font-size: 13px;
      color: #303133;
    }
    .right {
      display: inline-block;
      cursor: pointer;
      font-size: 17px;
      color: #909399;
      width: 17px;
    }
  }
}

.edit-box {
  position: absolute;
  right: 0;
  width: 79px;
  height: 61px;
  display: flex;
  align-items: center;
  padding: 0 12px;
  justify-content: space-between;
  background: linear-gradient(270deg, #e1f0ff 0%, rgba(255, 255, 255, 0.7) 100%);
  opacity: 0;
  transition: opacity 0.2s;
}
.box:hover {
  .edit-box {
    opacity: 1;
  }
}

.shrink {
  flex-shrink: 0;
}

/* JSON 编辑器：加边框圆角与背景区隔，压缩多余留白 */
:deep(.ace-container) {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;

  .ace-editor {
    background-color: #f9fafb;
    min-height: 240px;
  }
}

/* JSON配置辅助说明 */
.form-tip {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.6;
  color: #909399;

  i {
    margin-right: 4px;
    color: #1890ff;
  }
}
</style>
