<!-- 行政-企业动态页面 -->
<template>
  <div class="divBox">
    <div>
      <el-card :body-style="{ padding: '20px' }" class="card-box">
        <div>
          <el-row>
            <el-col v-bind="gridl">
              <left
                @eventOptionData="eventOptionData"
                @getTargetCate="getTargetCate"
                :leftList="leftList"
                ref="left"
              ></left>
            </el-col>
            <el-col v-bind="gridr" class="assess-right">
              <div class="ml14">
                <oaFromBox
                  :search="search"
                  :total="total"
                  :dropdownList="dropdownList"
                  :isViewSearch="false"
                  default-sort-field=""
                  title="快捷回复列表"
                  btnText="新增快捷回复"
                  @dropdownFn="dropdownFn"
                  @addDataFn="handleNews"
                  @confirmData="confirmData"
                ></oaFromBox>

                <div class="mt14">
                  <el-table :data="tableData" :height="TABLE_HEIGHT" style="width: 100%" row-key="id" default-expand-all v-loading="loading">
                    <el-table-column prop="name" label="素材内容" min-width="180">
                      <template #default="scope">
                        <div v-if="scope.row.types === 'image'" class="flex">
                          <img class="table-img" :src="scope.row.file_url" alt="" />
                          {{ scope.row.file ? scope.row.file.real_name : '--' }}
                        </div>
                        <div v-else-if="scope.row.types === 'text'" class="flex lh-center">
                             <img :src="getImageUrl('txt')" alt="" class="word-img"></img>
                          {{ scope.row.content || '--' }}
                        </div>
                        <div v-else-if="scope.row.types === 'link'">
                          <span class="iconfont iconlianjie1 mr10"></span>
                          {{ scope.row.title || '--' }}
                        </div>
                        <div v-else-if="scope.row.types === 'video'" class="flex lh-center">
                            <img :src="getImageUrl('mp4')" alt="" class="word-img"></img>
                          <!-- <i
                            :class="getFileTypeIconfont(0, scope.row.file ? scope.row.file.file_ext : '')"
                            class="icon iconfont mr10"
                          /> -->
                          {{ scope.row.file ? scope.row.file.real_name : '--' }}
                        </div>
                        <div v-else-if="scope.row.types === 'mini_program'">
                          <span class="iconfont iconxiaochengxu1 mr10"></span>
                          {{ scope.row.title || '--' }}
                        </div>
                        <div v-else-if="scope.row.types === 'file'" class="flex lh-center">

                          <img :src="getImageUrl(scope.row.file && scope.row.file.file_ext)" alt="" class="word-img">
                          <!-- <i
                            :class="getFileTypeIconfont(0, scope.row.file ? scope.row.file.file_ext : '')"
                            class="icon iconfont mr10"
                          /> -->
                          {{ scope.row.file ? scope.row.file.file_name : '--' }}
                        </div>
                      </template>
                    </el-table-column>
                    <el-table-column prop="title" label="内容分组" min-width="150">
                      <template #default="scope">
                        {{ scope.row.group?.name || '--' }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="sort" label="排序" min-width="80" />
                    <el-table-column prop="created_at" label="创建时间" min-width="120" />
                    <el-table-column prop="creator.name" label="创建人" min-width="80" />

                    <el-table-column prop="describe" :label="$t('public.operation')" fixed="right" width="120">
                      <template #default="scope">
                        <el-button type="text" @click="handleEdit(scope.row)">{{ $t('public.edit') }}</el-button>

                        <el-button type="text" @click="handleDelete(scope.row)">
                          {{ $t('public.delete') }}
                        </el-button>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </div>
            </el-col>
          </el-row>
          <div class="page-fixed">
            <el-pagination
              :page-size="where.limit"
              :current-page="where.page"
              :page-sizes="[15, 20, 30]"
              layout="total,sizes, prev, pager, next, jumper"
              :total="total"
              @size-change="handleSizeChange"
              @current-change="pageChange"
            />
          </div>
        </div>
      </el-card>
    </div>
    <!-- 新建公告页面 -->
    <add-reply
      ref="addReply"
      :group_id="where.group_id"
      :leftList="groupedData"
      :type="type"
      @getList="getTableData"
    ></add-reply>
    <!-- 导入 -->
    <importExcel ref="importExcel" :column-number="columnNumber" @importExcelData="importExcelData" />
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import { TABLE_HEIGHT } from '@/constants/tableSize'
import { fileLinkDownLoad, getFileTypeIconfont } from '@/utils/file'
import {
  getWorkReplyListApi,
  workReplyDelApi,
  getWorkReplyGroupApi,
  getWorkReplyImportApi,
  workReplyImportApi
} from '@/api/weCom'

// 动态加载 fileIcon 目录下的所有图片
const fileIconContext = import.meta.glob('@/assets/images/fileIcon/*.png', {
  eager: true,
  query: '?url',
  import: 'default',
});
const fileIconModules = {};
Object.keys(fileIconContext).forEach(key => {
  const name = key.slice(key.lastIndexOf('/') + 1);
  fileIconModules[name] = fileIconContext[key];
});

export default {
  name: 'IndexVue',
  components: {
    left: () => import('./components/left'),
    addReply: () => import('./components/addReply'),
    oaFromBox: () => import('@/components/common/oaFromBox'),
    importExcel: () => import('@/components/common/importExcel')
  },
  data() {
    return {
      TABLE_HEIGHT,
      gridl: {
        xl: 3,
        lg: 4,
        md: 5,
        sm: 6,
        xs: 24
      },

      columnNumber: 6,
      gridr: {
        xl: 21,
        lg: 20,
        md: 19,
        sm: 18,
        xs: 24
      },
      leftList: [],
      groupedData: [],
      dropdownList: [
        { label: '下载模板', value: 1 },
        { label: '导入快捷回复', value: 2 }
      ],
      where: {
        page: 1,
        limit: 15,
        group_id: '',
        name: '',
        time: ''
      },
      tabIndex: 0,
      total: 0,
      tableData: [],

      type: [
        {
          icon: 'iconwenben',
          label: '文本',
          value: 'text'
        },
        {
          icon: 'icontupian4',
          label: '图片',
          value: 'image'
        },
        {
          icon: 'iconshipin1',
          label: '视频',
          value: 'video'
        },
        {
          icon: 'iconwenjian4',
          label: '文件',
          value: 'file'
        },
        {
          icon: 'iconwangye-01',
          label: '网页',
          value: 'link'
        },
        {
          icon: 'iconxiaochengxu',
          label: '小程序',
          value: 'mini_program'
        }
      ],
      search: [
        {
          form_value: 'input',
          field_name_en: 'name',
          field_name: '素材内容'
        },
        {
          form_value: 'date_picker',
          field_name_en: 'time',
          field_name: '创建时间'
        }
      ],
      loading: false,
    }
  },
  mounted() {
    this.getTargetCate()
    this.getTableData()
  },
  methods: {
    getFileTypeIconfont,

    // 根据图片名称返回静态资源完整地址
    getImageUrl(imageName) {
      imageName = imageName+'.png'
      return fileIconModules[imageName] || ''
      // // 防御：若传入空值直接返回空字符串，避免后续拼接异常
      // if (!imageName) return ''

      // // 统一补全扩展名，避免重复添加
      // const ext = '.png'
      // const fullName = imageName.toLowerCase().endsWith(ext) ? imageName : imageName + ext

      // try {
      //   // 使用 Vite 支持的 new URL 语法解析静态资源
      //   return new URL(`/src/assets/images/fileIcon/${fullName}`, import.meta.url).href
      // } catch (e) {
      //   console.warn('图片路径解析失败:', e)
      //   return ''
      // }
    },
    handleSizeChange(val) {
      this.where.limit = val
      this.getTableData()
    },
    pageChange(page) {
      this.where.page = page
      this.getTableData()
    },
    // 获取表格数据
    getTableData() {
      if (this.loading) return
      this.loading = true
      getWorkReplyListApi(this.where).then((res) => {
        this.tableData = res.data.list || []
        this.total = res.data.count
      }).finally(() => {
        this.loading = false
      })
    },

    // 导入
    async importExcelData(value) {
      const res = []
      if (value.length <= 0) {
        this.$message.error('批量导入内容为空')
        return false
      }
      for (let i = 0; i <= value.length - 1; i++) {
        res.push({
          group_id: value[i][0],
          content: value[i][1]
        })
      }
      await workReplyImportApi({ data: res })
      await this.getTableData()
    },

    handleSearch() {
      this.where.page = 1
      this.getTableData()
    },
    handleNews() {
      this.$refs.addReply.openBox()
    },
    dropdownFn(val) {
      if (val.value == 2) {
        // 导入
        this.$refs.importExcel.btnClick()
      } else {
        getWorkReplyImportApi().then((res) => {
          fileLinkDownLoad(res.data.url, '快捷回复批量导入模板.xlsx')
        })
      }
    },

    handleEdit(row) {
      this.$refs.addReply.openBox(row.id)
    },
    async handleDelete(item) {
      await modalSure('你确定要删除这条内容吗')
      await workReplyDelApi(item.id)
      let totalPage = Math.ceil((this.total - 1) / this.where.limit)
      let currentPage = this.where.page > totalPage ? totalPage : this.where.page
      this.where.page = currentPage < 1 ? 1 : currentPage
      this.getTableData()
    },

    eventOptionData(data, index) {
      this.where.group_id = data.id

      this.tabIndex = JSON.parse(JSON.stringify(index))
      this.handleSearch()
    },

    getTargetCate() {
      this.leftList = []
      this.groupedData = []
      getWorkReplyGroupApi().then((res) => {
        this.groupedData = JSON.parse(JSON.stringify(res.data.list))
        res.data.list.unshift({
          id: '',
          name: '全部'
        })

        this.leftList = res.data.list
      })
    },
    confirmData(data, resetState) {
      if (data == 'reset') {
        this.where = {
          page: 1,
          limit: this.where.limit,
          group_id: this.where.group_id,
          name: '',
          time: '',
          // 排序等由搜索栏维护的默认参数，重置后需一并带回
          ...resetState
        }
      } else {
        this.where = { ...this.where, ...data }
      }
      this.handleSearch()
    }
  }
}
</script>

<style lang="scss" scoped>
.card-box {
  height: calc(100vh - 77px);
}
.assess-right {
  ::v-deep .el-card__header {
    border-bottom: none;
    padding: 0;
  }
}
.word-img {
  display: block;
  width: 22px;
  height: 22px;
  margin-right: 10px;
}
.icontxt1 {
  font-size: 18px;
  color: #ff9900;
}
.iconlianjie1 {
  font-size: 20px;
  color: #1890ff;
}
.iconxiaochengxu1 {
  font-size: 20px;
  color: #19be6b;
}

.right-con {
  display: flex;
  justify-content: flex-end;
}
.table-img {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  margin-right: 10px;
}
.select-bar {
  margin-bottom: 0;
  ::v-deep .el-input-group__append {
    top: 0;
    button {
      color: #fff;
      background-color: #1890ff;
      border-color: #1890ff;
      border-radius: 0 5px 5px 0;
    }
  }
}
::v-deep .el-textarea__inner,
.el-input__inner {
  font-size: 13px !important;
}
::v-deep .el-input__inner {
  font-size: 13px !important;
}
::v-deep .is-top .el-switch__core {
  width: 69px !important;
}
</style>
