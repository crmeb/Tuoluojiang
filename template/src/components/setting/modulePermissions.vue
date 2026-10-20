<template>
  <div>
    <el-table :data="treeData">
      <el-table-column label="模块" width="200">
        <template #default="scope">
          <span @click="getData()"> {{ scope.row.module_name }} </span></template
        >
      </el-table-column>
      <el-table-column label="数据权限" width="auto">
        <template #default="scope">
          <div class="flex">
            <el-select
              v-model="scope.row.data_level"
              @change="dataLevelFn($event, scope.row)"
              placeholder="请选择"
            >
              <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"> </el-option>
            </el-select>
            <select-department
              v-if="scope.row.data_level == '4'"
              :selectId="scope.row.frame_id || []"
              :placeholder="`请选择`"
              @changeMastart="changeMastart($event, scope.row)"
              style="width: 400px; margin-left: 20px"
            ></select-department>
          </div>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>
<script>
import { defineAsyncComponent } from 'vue'
export default {
  components: {
    selectDepartment: defineAsyncComponent(() => import('@/components/form-common/select-department'))
  },
  props: {
    treeData: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      options: [
        {
          value: 1,
          label: '仅本人'
        },
        {
          value: 2,
          label: '直属下级'
        },
        {
          value: 3,
          label: '本部门'
        },
        {
          value: 4,
          label: '自定义部门'
        },
        {
          value: 5,
          label: '全部数据'
        }
      ]
    }
  },
  methods: {
    dataLevelFn(e, item) {
      if (e == 1) {
        item.directly = 0
      } else {
        item.directly = 1
      }
    },
    getData() {
      let obj = {}
      this.treeData.map((item) => {
        obj[item.key] = {
          directly: item.data_level == 1 ? 0 : 1,
          data_level: item.data_level,
          frame_id: item.frame_id
        }
      })
      return obj
    },
    changeMastart(e, row) {
      let ids = []
      e.forEach((item) => {
        ids.push(item.id)
      })
      row.frame_id = ids
    }
  }
}
</script>
<style lang="scss" scoped></style>
