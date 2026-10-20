<!-- @FileDescription:考核模板库弹窗页面 -->
<template>
  <div>
    <el-dialog
      :title="title"
      v-model="dialogFormVisible"
      :modal="true"
      width="846px"
      :before-close="closeDialog"
      :show-close="true"
    >
      <template #header>
        <div class="dialog-title">
          <div class="dialog-title-title">{{ title }}</div>
        </div>
      </template>
      <el-row>
        <el-col :span="4">
          <!-- 考核模板库与指标库导航 -->
          <div v-loading="isCateLoading" class="assess-left">
            <ul class="assess-left-ul">
              <li
                v-for="(item, index) in cateList"
                :key="item.id"
                :class="index === tabIndex ? 'active' : ''"
                @click="handleCateChange(index, item.id)"
              >
                <span>{{ item.name }}</span>
              </li>
            </ul>
          </div>
        </el-col>
        <el-col :span="13">
          <div class="ml15">
            <div v-loading="isListLoading" class="content-temp">
              <el-input
                v-model="searchForm.name"
                placeholder="请输入模板名称"
                class="input-with-select"
                @change="handleSearch"
              />
              <el-scrollbar>
                <ul class="content-temp-ul">
                  <li
                    v-for="(item, index) in tableData"
                    :key="item.id"
                    :class="[(index + 1) % 3 ? '' : 'mr0', index === activeIndex ? 'active' : '']"
                    @click="handleSelect(item, index)"
                    @mouseenter="hoverIndex = index"
                    @mouseleave="hoverIndex = -1"
                  >
                    <div class="temp-text" :style="{ color: item.color }">
                      <p class="title textover">{{ item.name }}</p>
                      <p class="caption">{{ item.info }}</p>
                      <p v-if="item.way === 1" class="icon-star">
                        <i
                          class="el-icon-star-on"
                          :class="isCollected(item) ? 'active' : ''"
                          @click.stop="handleCollect(item)"
                        />

                        <i class="icon-caret-star" />
                      </p>
                    </div>

                    <img :src="item.cover || defaultCover" alt="" />
                    <!-- 仅模板创建人可见的操作层，鼠标移入卡片时浮出 -->
                    <div v-if="hoverIndex === index && isOwner(item)" class="footer" @click.stop>
                      <div class="pointer"><span @click.stop="handleSetCover(item)">设置封面</span></div>
                      <div class="pointer"><span @click.stop="handleEdit(item)">编辑</span></div>
                      <div class="pointer" @click.stop="handleDelete(item)">
                        <span>删除</span>
                      </div>
                    </div>
                  </li>
                </ul>
              </el-scrollbar>
            </div>
            <el-pagination
              :page-size="pageSize"
              :current-page="page"
              layout="total, prev, pager, next"
              :total="total"
              @current-change="handlePageChange"
            />
          </div>
        </el-col>
        <el-col :span="7">
          <div class="ml15">
            <!-- 右侧预览选中模板的维度与指标 -->
            <div v-loading="isPreviewLoading" class="template-preview">
              <el-scrollbar style="height: 100%">
                <p class="title">{{ previewTitle }}</p>
                <div v-for="(item, index) in previewList" :key="index" class="template-preview-list">
                  <p class="caption">{{ item.name }}</p>
                  <ul class="template-preview-ul">
                    <li v-for="(target, targetIndex) in item.target" :key="targetIndex">{{ target.name }}</li>
                  </ul>
                </div>
              </el-scrollbar>
            </div>
          </div>
        </el-col>
      </el-row>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="closeDialog">取消</el-button>
          <el-button type="primary" :loading="isPreviewLoading" @click="handleConfirm">确定</el-button>
        </div>
      </template>
    </el-dialog>
    <preview ref="previewRef" title="设置封面图" :data="coverItem" @handlePreview="handlePreview" />
  </div>
</template>

<script>
import { modalSure } from '@/libs/public.ts'
import { getCurrentInstance, reactive, ref, defineAsyncComponent } from 'vue'
import { useUserStore } from '@/stores/user.ts'
import defaultCover from '@/assets/images/default.jpg'
import {
  assessTemplateCollectApi,
  assessTemplateEditApi,
  assessTemplateListApi,
  templateDeleteApi
} from '@/api/enterprise.ts'
import { useAssessCate } from '@/composables/useAssessCate.ts'
import { useLoading } from '@/composables/useLoading.ts'
import { usePage } from '@/composables/usePage.ts'

// 分类类型：1 = 考核模板（0 为指标）
const CATE_TYPE = 1
// 每页 9 条，正好铺满卡片区的 3 列 × 3 行
const PAGE_SIZE = 9
// 「我的模板」「我的收藏」是前端虚拟分类，接口不返回，固定置顶
const MY_TEMPLATE_CATE_ID = 'template'
const COLLECT_CATE_ID = 'collect'
const VIRTUAL_CATES = [
  { name: '我的模板', id: MY_TEMPLATE_CATE_ID },
  { name: '我的收藏', id: COLLECT_CATE_ID }
]
// 回传给父级的操作类型：1 = 选用模板内容，2 = 编辑该模板
const CONFIG_EDIT = { USE: 1, EDIT: 2 }

export default {
  name: 'SelectTemplate',
  components: {
    preview: defineAsyncComponent(() => import('@/views/hr/assess/check/components/preview'))
  },
  props: {
    title: {
      type: String,
      default: ''
    },
    // 已存在考核内容时，确定前需二次确认（选用模板会覆盖原内容）
    remindButton: {
      type: Boolean,
      default: false
    }
  },
  emits: ['templateChange'],
  setup(props, { emit, expose }) {
    const userStore = useUserStore()
    const { proxy } = getCurrentInstance()

    const dialogFormVisible = ref(false)
    const previewRef = ref(null)

    // 分类在本实例内只加载一次，弹窗反复开关不会重复请求；组件重新挂载则重新拉取
    const { cateList, isCateLoading, fetchCate } = useAssessCate(CATE_TYPE, {
      decorate: (list) => [...VIRTUAL_CATES, ...list]
    })
    const tabIndex = ref(0)
    const cateId = ref(MY_TEMPLATE_CATE_ID)

    const { page, pageSize, total } = usePage()
    pageSize.value = PAGE_SIZE

    const searchForm = reactive({ name: '' })
    const tableData = ref([])
    // 当前用户已收藏的模板 id，用于卡片右上角星标高亮
    const collects = ref([])
    const activeIndex = ref(-1)
    const hoverIndex = ref(-1)
    const { isLoading: isListLoading, startLoading: startListLoading, stopLoading: stopListLoading } = useLoading()

    const previewTitle = ref('')
    const previewList = ref([])
    const selectedTemplate = ref(null)
    const {
      isLoading: isPreviewLoading,
      startLoading: startPreviewLoading,
      stopLoading: stopPreviewLoading
    } = useLoading()
    // 预览请求序号：连续点击卡片时只认最后一次请求，避免旧响应覆盖新预览
    let previewToken = 0

    // 待设置封面的模板，传给 preview 子组件
    const coverItem = ref({})

    const resetPreview = () => {
      previewToken += 1
      activeIndex.value = -1
      previewTitle.value = ''
      previewList.value = []
      selectedTemplate.value = null
    }

    // 选中模板：右侧展示其维度与指标明细
    const handleSelect = async (row, index) => {
      activeIndex.value = index
      previewTitle.value = row.name
      const token = (previewToken += 1)
      startPreviewLoading()
      try {
        const res = await assessTemplateEditApi(row.id, { way: row.way })
        if (token !== previewToken) return
        selectedTemplate.value = res.data || null
        previewList.value = res.data ? res.data.info : []
      } catch (error) {
        // 错误提示由 request 统一处理，这里保证预览区可渲染
        if (token === previewToken) previewList.value = []
      } finally {
        if (token === previewToken) stopPreviewLoading()
      }
    }

    const fetchTableData = async () => {
      startListLoading()
      try {
        const res = await assessTemplateListApi({
          page: page.value,
          limit: pageSize.value,
          name: searchForm.name,
          cate_id: cateId.value
        })
        tableData.value = res.data.list || []
        collects.value = res.data.collects || []
        total.value = res.data.count
        // 列表刷新后默认选中第一条；结果为空时清掉右侧，避免残留上一次的预览
        tableData.value.length > 0 ? handleSelect(tableData.value[0], 0) : resetPreview()
      } catch (error) {
        // 错误提示由 request 统一处理
      } finally {
        stopListLoading()
      }
    }

    // 切换左侧分类：重置分页与选中态后重新拉取
    const handleCateChange = (index, id) => {
      if (tabIndex.value === index) return
      tabIndex.value = index
      cateId.value = id
      page.value = 1
      resetPreview()
      fetchTableData()
    }

    const handleSearch = () => {
      page.value = 1
      fetchTableData()
    }

    const handlePageChange = (val) => {
      page.value = val
      fetchTableData()
    }

    // 「我的收藏」分类下的模板必然已收藏，无需再比对 collects
    const isCollected = (item) => cateId.value === COLLECT_CATE_ID || collects.value.includes(item.id)

    const isOwner = (item) => item.user !== undefined && userStore.userInfo.uid === item.user.uid

    const handleCollect = async (item) => {
      try {
        await assessTemplateCollectApi(item.id)
        // 在「我的收藏」下取消收藏后该模板会从列表消失，预览需一并清空
        if (cateId.value === COLLECT_CATE_ID) resetPreview()
        fetchTableData()
      } catch (error) {
        // 错误提示由 request 统一处理
      }
    }

    const handleSetCover = (item) => {
      coverItem.value = item
      previewRef.value.openDialog()
    }

    // 封面图设置 / 删除后回调
    const handlePreview = () => {
      fetchTableData()
    }

    const handleDelete = (item) => {
      modalSure('考核模板删除后，将不可修改，你确定要删除吗').then(async () => {
        await templateDeleteApi(item.id)
        fetchTableData()
      })
    }

    // 编辑模板：把模板完整内容回传给父级，由父级进入编辑态
    const handleEdit = async (item) => {
      startListLoading()
      try {
        const res = await assessTemplateEditApi(item.id)
        if (res.data === undefined) {
          proxy.$message.error('内容为空，不能编辑')
          return
        }
        emit('templateChange', { edit: CONFIG_EDIT.EDIT, data: res.data })
        closeDialog()
      } catch (error) {
        // 错误提示由 request 统一处理
      } finally {
        stopListLoading()
      }
    }

    const closeDialog = () => {
      resetPreview()
      dialogFormVisible.value = false
    }

    const handleConfirm = () => {
      if (activeIndex.value < 0) {
        proxy.$message.error('选择为空')
        return
      }
      // 先取出引用：closeDialog 只是把 previewList 指向新数组，已回传的数据不受影响
      const configData = { edit: CONFIG_EDIT.USE, data: selectedTemplate.value || { info: previewList.value } }
      if (props.remindButton) {
        modalSure('确定后将替换原来的内容').then(() => {
          emit('templateChange', configData)
        })
      } else {
        emit('templateChange', configData)
      }
      closeDialog()
    }

    const openDialog = () => {
      dialogFormVisible.value = true
      // 分类仅首次打开时请求一次；列表则每次打开都取最新
      fetchCate()
      fetchTableData()
    }

    expose({ openDialog })

    return {
      defaultCover,
      dialogFormVisible,
      previewRef,
      cateList,
      isCateLoading,
      tabIndex,
      page,
      pageSize,
      total,
      searchForm,
      tableData,
      activeIndex,
      hoverIndex,
      isListLoading,
      previewTitle,
      previewList,
      isPreviewLoading,
      coverItem,

      handleCateChange,
      handleSearch,
      handlePageChange,
      handleSelect,
      isCollected,
      isOwner,
      handleCollect,
      handleSetCover,
      handlePreview,
      handleDelete,
      handleEdit,
      handleConfirm,
      closeDialog,
      openDialog
    }
  }
}
</script>
<style lang="scss" scoped>
:deep(.el-dialog__body) {
  padding: 10px !important;
}
.assess-left {
  min-height: 460px;
  max-height: 550px;
  margin-left: -10px;
  overflow: auto;
  border-right: 1px solid #f3f3f3;
  .assess-left-ul {
    list-style: none;
    margin: 0;
    padding: 0;
    //position: relative;
    li {
      font-size: 13px;
      padding: 14px 10px 14px 40px;
      color: #000000;
      cursor: pointer;
      .assess-left-icon {
        color: #aaaaaa;
        font-size: 13px;
      }
      .assess-left-more {
        color: #333333;
        text-align: right;
        position: absolute;
        right: 10px;
        transform: rotate(90deg);
      }
    }
    li.active {
      background-color: #f0fafe;
      border-right: 2px solid #1890ff;
      color: #1890ff;
      .assess-left-icon {
        color: #1890ff;
      }
    }
  }
}
.assess-left::-webkit-scrollbar {
  width: 5px;
  height: 1px;
}
.assess-left::-webkit-scrollbar-thumb {
  /*滚动条里面小方块*/
  border-radius: 5px;
  -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
  background: #f5f5f5;
}
.assess-left::-webkit-scrollbar-track {
  /*滚动条里面轨道*/
  -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
  border-radius: 5px;
  background: #f0f2f5;
}
.right-item {
  line-height: 25px;
  cursor: pointer;
  margin-top: 10px;
  &:first-child {
    margin-top: 0;
  }
}
.ml15 {
  margin-left: 15px;
}
.mr0 {
  margin-right: 0 !important;
}
.textover {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
:deep(.el-dialog) {
  border-radius: 10px;
}
.dialog-title {
  font-size: 14px;
  display: flex;
  align-items: center;
  .dialog-title-title {
    width: 30%;
    font-size: 16px;
    font-family: PingFang SC;
    font-weight: 500;
  }
}
.content-temp {
  height: 550px;
  padding: 0 8px;
  .input-with-select {
    padding-right: 14px;
    margin-bottom: 10px;
  }
  .content-temp-ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    li {
      width: 132px;
      height: 160px;
      background-color: #f5f5f5;
      display: flex;
      flex-wrap: wrap;
      margin-right: 10px;
      margin-bottom: 10px;
      position: relative;
      border: 2px solid transparent;
      p {
        margin: 0;
        padding: 0;
      }
      img {
        width: 100%;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        z-index: 0;
      }
      .temp-text {
        padding: 20px 10px 10px 10px;
        position: absolute;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        z-index: 1;
        .title {
          font-size: 16px;
          width: 80%;
          margin-bottom: 10px;
        }
        .caption {
          font-size: 14px;
          font-family: PingFang SC;
          font-weight: 400;
        }
        .icon-star {
          width: 20px;
          position: absolute;
          border: 10px solid #fff;
          border-bottom: 8px solid transparent;
          height: 32px;
          text-align: center;

          // position: absolute;
          right: 17px;
          top: 0;
          i {
            position: absolute;
            top: -5px;
            left: -9px;
            color: #000000;
            font-size: 18px;

            cursor: pointer;
          }

          i.active {
            color: #f5c327;
          }
        }
      }
      .footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 12px 6px;
        background-color: rgba(0, 0, 0, 0.6);
        display: flex;
        justify-content: space-between;
        color: #ffffff;
        font-size: 13px;
        z-index: 9999;
      }
    }
    li.active {
      border: 2px solid #1890ff;
    }
  }
}
.template-preview {
  height: 545px;
  p {
    margin: 0;
    padding: 0;
  }
  .title {
    padding: 14px 0;
    font-size: 16px;
    color: #333333;
    font-weight: 500;
  }
  .template-preview-list {
    margin: 0 10px 0 6px;
    .caption {
      margin: 10px 0 10px 10px;
      font-size: 14px;
      color: #333333;
      position: relative;
    }
    .caption:before {
      content: '';
      position: absolute;
      left: -14px;
      top: 4px;
      width: 5px;
      height: 5px;
      background-color: #333333;
      border-radius: 50%;
    }
    .template-preview-ul {
      border-left: 1px solid #cccccc;
      padding: 10px 0;
      margin: 0;
      list-style: none;
      li {
        font-size: 12px;
        margin: 0 0 14px 20px;
        position: relative;
      }
      li:before {
        content: '';
        position: absolute;
        left: -10px;
        top: 5px;
        width: 5px;
        height: 5px;
        background-color: #333333;
        border-radius: 50%;
      }
      li:last-of-type {
        margin-bottom: 0;
      }
    }
  }
}
.divBox .el-pagination {
  margin-top: 0;
  justify-content: center;
}
</style>
