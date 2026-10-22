<template>
  <div class="divBox">
    <el-card class="normal-page">
      <oaFromBox
        :search="search"
        :title="`产品分类列表`"
        :isTotal="false"
        :isViewSearch="false"
        :btnIcon="true"
        :isAddBtn="true"
        :btnText="`添加分类`"
        :sortSearch="false"
        @addDataFn="addFinance"
        @confirmData="confirmData"
      ></oaFromBox>
      <!-- :load="load" -->
      <el-table
        :data="tableData"
        style="width: 100%"
        class="mt10"
        row-key="id"
        lazy
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
      >
        <el-table-column prop="name" label="分类名称"> </el-table-column>
        <el-table-column prop="status" label="开启状态">
          <template #default="scope">
            <el-tooltip
              :disabled="!scope.row.parentDisabled"
              content="上级分类已关闭，请先开启上级分类"
              placement="top"
            >
              <span>
                <el-switch
                  v-model="scope.row.status"
                  :active-value="1"
                  :inactive-value="0"
                  active-text="开启"
                  inactive-text="关闭"
                  :disabled="scope.row.parentDisabled"
                  @change="changeStatus(scope.row)"
                >
                </el-switch>
              </span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间"> </el-table-column>
        <el-table-column prop="sort" label="排序" width="180"> </el-table-column>
        <el-table-column prop="address" label="操作" fixed="right" width="220">
          <template #default="scope">
            <el-button type="text" @click="handleAdd(scope.row)" v-if="scope.row.level <= 5">添加子级</el-button>
            <el-button type="text" @click="handleEdit(scope.row)">{{ $t('public.edit') }}</el-button>
            <el-button type="text" @click="handleDelete(scope.row, 1)">{{ $t('public.delete') }}</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>
<script>
import { modalSure } from '@/libs/public'
import modalForm from '@/libs/modal-form'
import {
  productCateListApi,
  productCateCreateApi,
  productCateEditApi,
  productCateDelApi,
  productCateApi
} from '@/api/client'
import oaFromBox from '@/components/common/oaFromBox'

export default {
  name: 'Category',
  components: {
    oaFromBox
  },
  data() {
    return {
      total: 0,
      search: [
        {
          field_name: '产品分类名称',
          field_name_en: 'name',
          form_value: 'input'
        },
        {
          field_name: '创建时间',
          field_name_en: 'time',
          form_value: 'date_picker'
        }
      ],
      tableData: [],
      where: {}
    }
  },
  mounted() {
    this.getList()
  },
  methods: {
    getList() {
      productCateListApi(this.where).then((res) => {
        this.tableData = this.markParentDisabled(res.data)
      })
    },
    // 递归标记每个节点：当任一上级分类处于关闭状态时，其开启入口受控（禁用）
    markParentDisabled(list, parentDisabled = false) {
      if (!Array.isArray(list)) return list
      return list.map((item) => {
        item.parentDisabled = parentDisabled
        if (item.children && item.children.length) {
          item.children = this.markParentDisabled(item.children, parentDisabled || item.status === 0)
        }
        return item
      })
    },
    handleAdd(row) {
      modalForm(productCateCreateApi({ pid: row.id })).then(({ message }) => {
        this.getList()
      })
    },
    async changeStatus(row) {
      // 关闭父级前，提示将联动关闭的子级影响范围
      if (row.status === 0) {
        const affected = this.countChildren(row.children)
        if (affected > 0) {
          try {
            await modalSure(`关闭「${row.name}」后，其下 ${affected} 个子分类将一并关闭，确定关闭吗`)
          } catch (e) {
            row.status = 1 // 取消则回滚开关状态
            return
          }
        }
      }
      productCateApi(row.id, { status: row.status }).then((res) => {
        this.getList()
      })
    },
    // 递归统计后代分类数量，用于关闭父级时提示影响范围
    countChildren(list) {
      if (!Array.isArray(list)) return 0
      return list.reduce((sum, item) => sum + 1 + this.countChildren(item.children), 0)
    },
    handleEdit(row) {
      modalForm(productCateEditApi(row.id)).then(({ message }) => {
        this.getList()
      })
    },
    async handleDelete(row) {
      await modalSure('确定删除当前分类吗')
      await productCateDelApi(row.id)
      this.getList()
    },
    addFinance() {
      modalForm(productCateCreateApi()).then(({ message }) => {
        this.getList()
      })
    },
    confirmData(data, resetState) {
      if (data == 'reset') {
        // 排序等由搜索栏维护的默认参数，重置后需一并带回
        this.where = { ...resetState }
      } else {
        this.where = data
      }

      this.getList()
    }
  }
}
</script>
<style scoped lang="scss"></style>
