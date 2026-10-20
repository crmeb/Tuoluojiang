<!--
  @FileDescription: 低代码应用管理弹窗组件
  功能：提供应用分类的增删改查和拖拽排序功能
-->
<template>
  <div class="oa-dialog">
    <!-- 弹窗主体 -->
    <el-dialog :close-on-click-modal="false" :show-close="false" v-model="show" :width="`516px`">
      <!-- 弹窗标题 -->
      <template #header>
        <div class="header">
          <span class="title">{{ id ? '编辑' : '新建' }}应用管理</span>
          <span class="el-icon-close" @click="handleClose"></span>
        </div>
      </template>
      <div class="pl20 pr20 mt20">
        <el-form :model="fromItem" :rules="rules" ref="formRef" label-width="auto">
          <el-form-item label="应用名称：" prop="name">
            <el-input v-model="fromItem.name" placeholder="请填写应用名称" />
          </el-form-item>
          <el-form-item label="应用图标：" prop="icon">
            <el-input placeholder="请选择图标" v-model="fromItem.icon" readonly @click="showIconDialog = true"
              clearable>
              <template #suffix>
                <i v-if="!fromItem.icon" class="el-icon-circle-plus-outline" style="cursor: pointer;"></i>
                <i v-else class="el-icon-circle-close" style="cursor: pointer;"
                  @click.stop="handleClearIcon"></i>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="应用简介：" prop="info">
            <el-input v-model="fromItem.info" type="textarea" rows="4" resize="none" placeholder="请填写应用简介" />
          </el-form-item>
          <el-form-item label="排序：" prop="sort">
            <el-input v-model="fromItem.sort" placeholder="请填写排序" />
          </el-form-item>
          <el-form-item label="上级菜单：" prop="menu">
            <el-cascader v-model="fromItem.path" :options="menuList"
              :props="{ checkStrictly: true, label: 'menu_name', value: 'id', children: 'children' }" clearable
              style="width: 100%;"></el-cascader>
            <span class="tips">若不选择上级菜单，则不生成菜单</span>
          </el-form-item>
        </el-form>
      </div>


      <template #footer>
        <div>
          <div class="dialog-footer">
            <el-button @click="handleClose">取消</el-button>
            <el-button type="primary" @click="submit">确定</el-button>

          </div>
        </div>
      </template>
    </el-dialog>
    <!-- 选择图标 -->
    <el-dialog title="选择菜单图标" v-model="showIconDialog" width="50%">
      <div class="icon-box">
        <select-icon ref="selectIconRef" :isEmit="true" @select="handleSelectIcon"></select-icon>
      </div>

    </el-dialog>

  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { savecrudCateApi, delcrudCateApi, saveCrudApi, getCrudInfoApi } from '@/api/develop.ts'
import { menuListApi } from '@/api/system.ts'
export default {
  emits: ['getList'],
  name: 'ApplicatDialog',
  components: {
    oaSystemImage: defineAsyncComponent(() => import('@/components/form-common/oa-systemImage.vue')),
    selectIcon: defineAsyncComponent(() => import('@/components/form-common/select-icon.vue')),
  },

  props: {
    // 应用分类列表
    list: {
      type: Array,
      default: () => []
    }
  },

  data() {
    return {
      show: false, // 控制弹窗显示
      showIconDialog: false,
      fromItem: this.generateDefaultFormValues(),
      id: 0,

      menuList: [],
      rules: {
        name: [
          { required: true, message: '请填写应用名称', trigger: 'blur' }
        ],
        icon: [
          { required: true, message: '请选择应用图标', trigger: 'blur' }
        ],
        info: [
          { required: true, message: '请填写应用简介', trigger: 'blur' }
        ],

      }
    }
  },

  methods: {
    /**
     * 关闭弹窗
     */
    handleClose() {
      this.id = 0
      this.show = false
      this.fromItem = this.generateDefaultFormValues()
    },
    generateDefaultFormValues() {
      return {
        name: '',
        icon: '',
        info: '',
        sort: 0,
        path: [],
      };
    },
    handleSelectIcon(data) {
      this.fromItem.icon = data
      this.showIconDialog = false
      this.$refs.formRef.validateField('icon')
    },
    handleClearIcon() {
      this.fromItem.icon = ''
    },


    openBox(data) {
      if (data) {
        this.id = data.id
        getCrudInfoApi(this.id)
          .then((res) => {
            Object.keys(this.generateDefaultFormValues())
              .forEach(key => {
                if (['icon', 'path'].includes(key) && res.data.menu) {
                  this.fromItem[key] = res.data.menu[key];
                } else {
                  this.fromItem[key] = res.data[key];
                }
              });


          })

      }
      this.show = true
      this.getAllMenus()
    },
    async getAllMenus() {
      let obj = {
        menu_name: "顶级菜单", id: 0
      }
      const result = await menuListApi()
      this.menuList = result.data
      this.menuList.unshift(obj)
    },



    /**
     * 提交表单数据
     */
    submit() {
      this.$refs.formRef.validate((valid) => {
        if (valid) {
          const payload = Object.keys(this.generateDefaultFormValues())
            .reduce((acc, key) => {
              if (key === 'path') {
                acc.menu.path = this.fromItem.path
              } else {
                acc[key] = this.fromItem[key]
              }
              return acc
            }, {
              menu: {}
            });
          saveCrudApi(this.id, payload)
            .then(() => {
              this.handleClose()
              this.$emit('getList')
            })
            .catch((err) => {
              this.$message.error(err.message)
            })
        } else {
          this.$message.error('请填写完整信息')
        }
      })
    },



  }
}
</script>

<style lang="scss" scoped>
.oa-dialog {
  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .title {
      font-family: PingFang SC, PingFang SC;
      font-weight: 500;
      font-size: 14px;
      color: #303133;
    }

    .el-icon-close {
      color: #c0c4cc;
      font-weight: 500;
      font-size: 14px;
    }
  }

  .content {
    max-height: calc(100vh - 520px);
    overflow-y: scroll;
  }

  .content::-webkit-scrollbar {
    height: 0;
    width: 0;
  }

  .content:first-child {
    padding: 0 20px;
  }

  .vertical {
    display: flex;
    flex-direction: column;
  }

  .add-type {
    display: flex;
    justify-content: flex-start;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
  }

  :deep(.el-dialog) {
    border-radius: 6px;
  }

  :deep(.el-dialog__body) {
    margin-bottom: 0;
    padding: 0;
  }
}

.el-icon-circle-plus-outline {
  cursor: pointer;
}

.icon-box {
  height: 700px;
  padding: 20px;
  padding-bottom: 100px;
}


.pr20 {
  padding-right: 20px;
}

.tips {
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #909399;
}
</style>
