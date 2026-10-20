<template>
  <div>
    <oaDialog :fromData="fromData" ref="oaDialog" @submit="submit">
      <!-- 内容 -->
      <el-form :model="form" label-width="100px" :rules="rules">
        <el-form-item label="按钮位置：">
          <el-radio-group v-model="form.position">
            <el-radio :value="1">列表中</el-radio>
            <el-radio :value="2">列表头部</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="启用规则：">
          <el-button> 设置启用规则 </el-button>
        </el-form-item>
        <el-form-item label="选择表单：" prop="form">
          <el-select v-model="form.form" placeholder="请搜索选择表单（单选）" style="width: 100%">
            <el-option label="表单1" value="1"></el-option>
            <el-option label="表单2" value="2"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="弹窗标题：" prop="title">
          <el-input v-model="form.title" placeholder="请输入弹窗标题"></el-input>
        </el-form-item>
        <el-form-item label="执行动作：">
          <el-radio-group v-model="form.action">
            <el-radio :value="1">新增</el-radio>
            <el-radio :value="2">编辑</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="参数设置：">
          <el-table>
            <el-table-column label="序号" prop="name" width="80"></el-table-column>
            <el-table-column label="参数名称" prop="name"></el-table-column>
            <el-table-column label="参数来源" prop="value"></el-table-column>
          </el-table>
        </el-form-item>
      </el-form>
    </oaDialog>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
export default {
  name: 'buttonDialog',
  components: {
    oaDialog: defineAsyncComponent(() => import('@/components/form-common/dialog-form'))
  },
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      fromData: {
        width: '700px',
        title: '按钮事件配置',
        btnText: '确定',
        labelWidth: '90px',
        type: 'slot'
      },
      form: {
        position: 1,
        form: '',
        title: ''
      },
      rules: {
        form: [{ required: true, message: '请选择表单', trigger: 'blur' }],
        title: [{ required: true, message: '请输入弹窗标题', trigger: 'blur' }]
      }
    }
  },
  mounted() {},
  methods: {
    openBox(data) {
      this.$refs.oaDialog.openBox()
    },
    submit() {}
  }
}
</script>
