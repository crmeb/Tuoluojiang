<template>
  <div class="main">
    <!-- <div class="title-16 mt20">属性信息</div> -->
    <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="90px" class="mt20">
      <el-form-item label="显示名称:" prop="table_name">
        <el-input v-model="ruleForm.table_name"></el-input>
      </el-form-item>
      <el-form-item label="实体名称:" prop="table_name_en">
        <el-input disabled v-model="ruleForm.table_name_en"></el-input>
      </el-form-item>

      <el-form-item label="所属主实体:" prop="region" v-if="ruleForm.crud_id">
        <el-cascader
          disabled
          v-model="ruleForm.crud_id"
          :options="options"
          style="width: 100%"
          :show-all-levels="false"
          :props="{ checkStrictly: true, label: 'table_name', value: 'id', emitPath: false }"
        >
          <template #default="{ node, data }">
            <span>{{ data.table_name }}</span>
            <span> （{{ data.table_name_en }}）</span>
          </template>
        </el-cascader>
        <div class="tips">关联主实体后，则此实体为从表，无表单、审批流、触发器设置。</div>
      </el-form-item>
      <el-form-item label="操作日志:">
        <el-switch
          v-model="ruleForm.show_log"
          active-value="1"
          inactive-value="0"
          inactive-text="关闭"
          active-text="开启"
        ></el-switch>
      </el-form-item>

      <el-form-item label="评论功能:">
        <div :class="ruleForm.show_comment == 1 ? 'flex-col-center' : ''">
          <el-switch
            v-model="ruleForm.show_comment"
            active-value="1"
            inactive-value="0"
            inactive-text="关闭"
            active-text="开启"
          ></el-switch>
          <el-input
            v-if="ruleForm.show_comment == 1"
            class="ml10"
            v-model="ruleForm.comment_title"
            maxlength="5"
            placeholder="评论重命名"
          ></el-input>
        </div>
      </el-form-item>

      <el-form-item label="关联应用:" prop="region">
        <el-select
          style="width: 100%"
          v-model="ruleForm.cate_ids"
          multiple
          filterable
          placeholder="请搜索选择应用（多选）"
        >
          <el-option v-for="(v, index) in cateOptions" :key="v.id" :label="v.name" :value="v.id"> </el-option>
        </el-select>
      </el-form-item>
      <el-form-item label="上级菜单:" prop="region">
        <el-cascader
          v-model="ruleForm.path"
          :options="menuList"
          :props="{ checkStrictly: true, label: 'menu_name', value: 'id', emitPath: false }"
          clearable
          style="width: 100%"
        ></el-cascader>
      </el-form-item>
      <el-form-item label="菜单图标:" prop="region">
        <el-input
          placeholder="请选择图标"
          v-model="ruleForm.icon"
          readonly
          @click="showIconDialog = true"
          clearable
        >
          <template #suffix>
            <i v-if="!ruleForm.icon" class="el-icon-circle-plus-outline" style="cursor: pointer"></i>
            <i
              v-else
              class="el-icon-circle-close"
              style="cursor: pointer"
              @click.stop="handleClearIcon"
            ></i>
          </template>
        </el-input>
      </el-form-item>
      <el-form-item label="移动端图标:" prop="region">
        <oa-systemImage ref="systemImageRef" @getImage="getImage"></oa-systemImage>
      </el-form-item>
      <el-form-item label="实体说明:" prop="region">
        <el-input
          type="textarea"
          v-model="ruleForm.info"
          :autosize="{ minRows: 4, maxRows: 8 }"
        ></el-input>
      </el-form-item>
      <el-form-item>
        <el-button type="primary" :loading="loading" @click="submit">保存</el-button>
      </el-form-item>
    </el-form>
    <!-- 选择图标 -->
    <el-dialog title="选择菜单图标" v-model="showIconDialog" :append-to-body="true" width="50%">
      <div class="icon-box">
        <select-icon ref="selectIconRef" :isEmit="true" @select="handleSelectIcon"></select-icon>
      </div>
    </el-dialog>
  </div>
</template>
<script>
import oaSystemImage from '@/components/form-common/oa-systemImage'
import selectIcon from '@/components/form-common/select-icon'
import Commnt from '@/components/develop/commonData'
import { menuListApi } from '@/api/system.ts'
import { getcrudCateListApi, databaseListApi, databasePutApi } from '@/api/develop.ts'

export default {
  props: {
    infoData: {
      type: Object,
      default: () => {}
    }
  },
  components: {
    oaSystemImage,
    selectIcon
  },
  data() {
    return {
      ruleForm: Commnt.formDataInit,
      rules: Commnt.formRules,
      cateOptions: [],
      showIconDialog: false,
      options: [],
      menuList: [],
      loading: false
    }
  },
  created() {
    this.getList()
    this.getCateList()
    this.getMenuList()
  },
  watch: {
    infoData(val) {
      this.initForm(val)
    }
  },
  mounted() {
    this.initForm(this.infoData)
  },

  methods: {
    initForm(val) {
      this.ruleForm.table_name = val.table_name
      this.ruleForm.table_name_en = val.table_name_en
      this.ruleForm.info = val.info
      this.ruleForm.crud_id = val.crud_id
      this.ruleForm.uni_img = val.uni_img
      this.$refs.systemImageRef.url = val.uni_img
      this.ruleForm.show_log = val.show_log + ''
      this.ruleForm.comment_name = val.comment_title || '评论'
      this.ruleForm.show_comment = val.show_comment + ''
      if (val.cate_ids && val.cate_ids.length > 0) {
        this.ruleForm.cate_ids = val.cate_ids.map(Number)
      } else {
        this.ruleForm.cate_ids = []
      }
    },
    submit() {
      this.$refs.ruleForm.validate((valid) => {
        if (valid) {
          this.loading = true
          databasePutApi(this.infoData.id, this.ruleForm).then((res) => {
            this.loading = false
          })
        }
      })
    },
    handleSelectIcon(data) {
      this.ruleForm.icon = data
      this.showIconDialog = false
    },
    getImage(e) {
      this.ruleForm.uni_img = e.url
    },
    async getMenuList() {
      let obj = {
        menu_name: '顶级菜单',
        id: 0
      }
      const result = await menuListApi()
      this.menuList = result.data
      this.menuList.unshift(obj)
    },
    handleClearIcon() {
      this.ruleForm.icon = ''
    },
    async getList() {
      let obj = {
        cate_id: ''
      }
      const data = await databaseListApi(obj)
      this.options = data.data.list
    },
    async getCateList() {
      const result = await getcrudCateListApi()
      this.cateOptions = result.data.list
    }
  }
}
</script>
<style scoped lang="scss">
.main {
  width: 800px;
  margin: 0 auto;

  .title {
    font-size: 16px;
    font-weight: 500;
  }
}
.tips {
  font-size: 12px;
  color: #909399;
}
.flex-col-center {
  display: flex;
  align-items: center;
}
</style>
