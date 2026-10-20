<template>
  <div class="database-plugin">
    <fieldLabel
      label="数据库"
      collapsible
      :expanded="expanded.content"
      :tips="FIELD_TIPS.DATABASE"
      @toggle="expanded.content = !expanded.content"
    >
      <template #extra>
        <span class="text-btn" @click="openDatabase">数据库设置</span>
      </template>
    </fieldLabel>
    <el-collapse-transition>
      <div v-show="expanded.content" class="plugin-field">
        <el-input v-model="form.content" type="textarea" class="content-textarea" placeholder="请输入内容" />
        <span class="iconfont iconzhankai2 expand-btn" title="展开" @click="openDatabase" />
      </div>
    </el-collapse-transition>

    <fieldLabel
      label="整理数据规则"
      collapsible
      :expanded="expanded.arrange"
      :tips="FIELD_TIPS.DATA_ARRANGE"
      @toggle="expanded.arrange = !expanded.arrange"
    />
    <el-collapse-transition>
      <div v-show="expanded.arrange" class="plugin-field">
        <promptTextarea
          :value="form.data_arrange_text"
          title="整理数据规则"
          :maxlength="TEXT_MAX.DATA_ARRANGE"
          @input="form.data_arrange_text = $event"
        />
      </div>
    </el-collapse-transition>

    <fieldLabel
      label="关键字"
      collapsible
      :expanded="expanded.keyword"
      :tips="FIELD_TIPS.KEYWORD"
      @toggle="expanded.keyword = !expanded.keyword"
    />
    <el-collapse-transition>
      <div v-show="expanded.keyword" class="plugin-field">
        <el-input
          v-for="(item, index) in form.keyword"
          :key="index"
          v-model="form.keyword[index]"
          class="line-input"
          :maxlength="TEXT_MAX.KEYWORD"
          placeholder="请输入关键词"
        >
          <template #suffix>
            <span class="line-input-suffix">
              <span class="count">{{ form.keyword[index].length }}/{{ TEXT_MAX.KEYWORD }}</span>
              <i class="el-icon-delete" @click="form.keyword.splice(index, 1)" />
            </span>
          </template>
        </el-input>
        <span class="text-btn" @click="form.keyword.push('')">
          <i class="el-icon-circle-plus-outline" />新增关键字
        </span>
      </div>
    </el-collapse-transition>

    <jsonDialog ref="jsonDialogRef" :content="form.content" :list="form.tables" @submit="handleDatabase" />
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import fieldLabel from './fieldLabel.vue'
import promptTextarea from './promptTextarea.vue'
import jsonDialog from './jsonDialog.vue'
import { FIELD_TIPS, TEXT_MAX } from '../constants.ts'
import { useApplicationSetting } from '../composables/useApplicationSetting.ts'

const { form } = useApplicationSetting()

const expanded = reactive({ content: true, arrange: true, keyword: true })
const jsonDialogRef = ref<any>(null)

const openDatabase = () => {
  jsonDialogRef.value.openBox(form.content, form.tables)
}

const handleDatabase = (tables: string[], content: string) => {
  form.tables = tables
  form.content = content
}
</script>

<style scoped lang="scss">
.plugin-field {
  position: relative;
  margin: 10px 0 14px;
}

.content-textarea {
  :deep(.el-textarea__inner) {
    height: 90px;
    resize: none;
  }
}

.expand-btn {
  position: absolute;
  right: 10px;
  bottom: 4px;
  font-size: 14px;
  color: #606266;
  cursor: pointer;
}

.line-input {
  margin-bottom: 8px;

  :deep(.el-input__inner) {
    padding-right: 70px;
  }
}

.line-input-suffix {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #606266;

  .count {
    color: #909399;
  }

  .el-icon-delete {
    margin-left: 8px;
    font-size: 14px;
    cursor: pointer;
  }
}

.text-btn {
  font-weight: 400;
  font-size: 12px;
  color: #1890ff;
  cursor: pointer;

  .el-icon-circle-plus-outline {
    margin-right: 4px;
    font-size: 13px;
  }
}
</style>
