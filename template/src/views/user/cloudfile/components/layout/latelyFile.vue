<template>
  <!--  最近打开文件-->
  <div class="content">
    <div class="v-height-flag">
      <template v-if="fileData && fileData.length > 0">
        <!-- 列表布局 (fileStyle.style === 1) -->

        <el-table
          v-if="fileStyle.style === 1"
          :data="fileData"
          :show-header="true"
          :row-class-name="tableRowClassName"
          @row-contextmenu="rightClick"
          style="width: 100%"
          class="mt10"
        >
          <!-- 文件名称列 -->
          <el-table-column prop="name" label="文件名称" min-width="320">
            <template #default="scope">
              <div
                class="file-name-cell"
                @dblclick="getItemFile(scope.row)"
                @click="itemCheckChange(scope.row)"
                @contextmenu.prevent.stop="rightClick($event, scope.row, scope.$index)"
              >
                <i
                  v-if="scope.row.type === 0"
                  class="icon iconfont"
                  :class="getFileTypeIconfont(scope.row.type, scope.row.file_ext)"
                />
                <img
                  v-if="scope.row.type === 1"
                  alt=""
                  class="folder-icon"
                  src="../../../../../assets/images/cloud/file.png"
                />
                <span>{{ scope.row.name }}{{ scope.row.file_ext ? '.' + scope.row.file_ext : '' }}</span>
              </div>
            </template>
          </el-table-column>

          <!-- 文件大小列 -->
          <el-table-column prop="file_size" label="文件大小" min-width="120">
            <template #default="scope">
              {{ formatBytesFn(scope.row.file_size) }}
            </template>
          </el-table-column>

          <!-- 创建人列 -->
          <el-table-column label="创建人" min-width="150">
            <template #default="scope">
              <div class="creator-cell">
                <img :src="scope.row.user ? scope.row.user.avatar : ''" alt="" class="avatar" />
                {{ scope.row.user ? scope.row.user.name : '--' }}
              </div>
            </template>
          </el-table-column>

          <!-- 更新时间列 -->
          <el-table-column prop="updated_at" label="更新时间" min-width="180" />

          <!-- 操作列 -->
          <el-table-column label="操作" width="150">
            <template #default="scope">
              <div class="operation-cell">
                <el-popover
                  :ref="`pop-${scope.$index}`"
                  :offset="30"
                  :width="40"
                  placement="bottom-start"
                  trigger="click"
                  @after-enter="handleShow(scope.$index)"
                >
                  <div class="right-item-list">
                    <div
                      v-if="scope.row.type !== 1 && openTypes.includes(scope.row.file_ext)"
                      class="right-item"
                      @click="getItemFile(scope.row)"
                    >
                      打开
                    </div>
                    <div v-if="scope.row.type !== 1" class="right-item" @click="fileDownLoad(scope.row)">
                      下载
                    </div>
                    <div v-if="scope.row.type !== 1" class="right-item" @click="shareOther(scope.row)">分享</div>
                    <el-divider v-if="scope.row.type !== 1" />
                    <div class="right-item" @click="getMoveDialog(scope.row, 1)">移动至</div>
                    <div v-if="scope.row.type === 1" class="right-item" @click="addAuthor(scope.row)">
                      目录权限
                    </div>
                    <div v-if="scope.row.type !== 1" class="right-item" @click="getMoveDialog(scope.row, 2)">
                      复制到
                    </div>
                    <!-- <div class="right-item" @click="setRenameItem(scope.row, scope.$index)">重命名</div> -->
                    <el-divider />
                    <div class="right-item" @click="getFolderDelete(scope.row)">删除</div>
                    <div class="right-item" @click="getFileAttribute(scope.row)">属性</div>
                  </div>
                  <template #reference>
                    <i class="icon iconfont icongengduo1 pointer" />
                  </template>
                </el-popover>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <!-- 网格布局 (fileStyle.style === 2) - 完全还原原始样式 -->
        <div id="listview" class="v-height-flag" v-else>
          <div v-height>
            <el-scrollbar style="height: 100%">
              <ul class="public-ul infeed-ul">
                <li v-for="(item, index) in fileData" :key="index" :class="menuIds.includes(item.id) ? 'active' : ''">
                  <div class="content-left pointer">
                    <p
                      v-if="renameIndex !== index"
                      class="public-title"
                      @click="itemCheckChange(item)"
                      @dblclick="getItemFile(item)"
                      @contextmenu.prevent.stop="rightClick($event, item, index)"
                    >
                      <template v-if="fileStyle.style == 2">
                        <img
                          v-if="item.type == 1"
                          alt=""
                          class="folder"
                          src="../../../../../assets/images/cloud/file.png"
                        />
                        <span v-if="item.type !== 1" class="file">.{{ item.file_ext }}</span>
                      </template>

                      <span v-if="item.file_ext" class="over-text">{{ item.name }}.{{ item.file_ext }}</span>
                      <span v-else class="over-text">{{ item.name }}</span>
                    </p>
                    <p v-else class="public-title">
                      <img v-if="fileIsImage('image/jpegs')" :src="item.file_url" class="image" />
                      <i v-else :class="getFileType(item.type, item.file_url)" class="icon iconfont" />
                      <input
                        :id="'input' + index"
                        v-model="item.name"
                        placeholder="请输入文件名称"
                        autofocus="autofocus"
                        class="rename-input"
                        maxlength="30"
                        @blur="renameBlur(item)"
                      />
                    </p>
                  </div>
                  <div class="icon-star" v-if="fileStyle.style === 1">
                    <div class="icon-star-right pointer">
                      <el-popover
                        :ref="`pop-${index}`"
                        :offset="30"
                        :width="40"
                        placement="bottom-start"
                        trigger="click"
                        @after-enter="handleShow(index)"
                      >
                        <div class="right-item-list">
                          <div
                            v-if="item.type !== 1 && openTypes.includes(item.file_ext)"
                            class="right-item"
                            @click="getItemFile(item)"
                          >
                            打开
                          </div>

                          <div v-if="item.type !== 1" class="right-item" @click="fileDownLoad(item)">
                            下载
                          </div>
                          <div v-if="item.type !== 1" class="right-item" @click="shareOther(item)">分享</div>
                          <el-divider v-if="item.type !== 1" />
                          <div class="right-item" @click="getMoveDialog(item, 1)">移动至</div>
                          <div v-if="item.type === 1" class="right-item" @click="addAuthor(item)">
                            目录权限
                          </div>
                          <div v-if="item.type !== 1" class="right-item" @click="getMoveDialog(item, 2)">
                            复制到
                          </div>
                          <!-- <div class="right-item" @click="setRenameItem(item, index)">重命名</div> -->
                          <el-divider />
                          <div class="right-item" @click="getFolderDelete(item)">删除</div>
                          <div class="right-item" @click="getFileAttribute(item)">属性</div>
                        </div>
                        <template #reference>
                          <i class="icon iconfont icongengduo1" />
                        </template>
                      </el-popover>
                    </div>
                  </div>
                </li>
              </ul>
            </el-scrollbar>
          </div>
        </div>
        <el-pagination
          :current-page="where.page"
          :page-size="where.limit"
          :page-sizes="[10, 15, 20]"
          :total="total"
          class="page-fixed"
          layout="total, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="pageChange"
        />
      </template>
      <default-page v-else v-height :index="7" :min-height="510" />
    </div>
    <!-- 图片查看弹窗 -->
    <el-image-viewer v-if="isImage" @close="closeImageViewer" :url-list="srcList" />
    <image-viewer ref="imageViewer" :src-list="srcList"></image-viewer>
    <!-- 分享弹窗 -->
    <!--    <share-dialog :id="id" ref="shareDialog" @isOk="isOk" />-->
    <!-- 移动弹窗 -->
    <move-dialog ref="moveDialog" :move-data="moveData" @handlerMove="handlerMove" />
    <!-- 文件属性详情侧滑 -->
    <file-attribute ref="fileAttribute" :form-data="formBoxConfig" />
    <!-- 列表点击右键弹窗 -->
    <right-click ref="rightClick" :latelyFile="true" :config-data="rightData" @handleRightClick="handleRightClick" />
    <!-- 设置目录权限弹窗 -->
    <author-dialog ref="authorDialog" :from-data="fromData" />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
import helper from '@/libs/helper.ts'
import {
  folderSpaceEntDeleteApi,
  folderSpaceEntRenameApi,
  folderSpaceEntViewApi,
  folderRecentlyListApi
} from '@/api/cloud.ts'
import { fileDownLoad, fileIsImage, fileTypeName, formatBytes, getFileTypeIconfont, trim } from '@/utils/file.ts'
export default {
  emits: ['totalFn', 'shareItemFile', 'openItemFile', 'handlerMyFile'],
  name: 'LatelyFile',
  components: {
    imageViewer: defineAsyncComponent(() => import('@/components/common/imageViewer')),
    moveDialog: defineAsyncComponent(() => import('../moveDialog')),
    fileAttribute: defineAsyncComponent(() => import('../fileAttribute')),
    rightClick: defineAsyncComponent(() => import('../rightClick')),
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    authorDialog: defineAsyncComponent(() => import('../authorDialog')),
    oaDialog: defineAsyncComponent(() => import('@/components/form-common/dialog-form'))
  },
  props: {
    fileStyle: {
      type: Object,
      default: () => {
        return {}
      }
    },
    switch: {
      type: Number,
      default: 4
    },
    spaceId: {
      type: [Number, String],
      default: 0
    },
    pageLimit: {
      type: Number,
      default: 0
    },
    wps_type: {
      type: [String, Number],
      default: '0'
    },
    spaceType: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      checked: false,
      fileData: [],
      activeValue: '',
      renameIndex: null,
      renameName: '',
      where: {
        page: 1,
        limit: this.pageLimit,
        pid: '',
        is_del: 0
      },
      total: 0,
      openTypes: helper.openType,
      breadcrumbArray: [{ name: '全部文件', pid: '' }],
      id: 0,
      isImage: false,
      srcList: [],
      formBoxConfig: {},
      menuIds: [],
      handleData: {
        id: 0,
        ids: []
      },
      moveData: {
        id: 0,
        type: 3
      },
      rightData: {
        type: 6,
        data: {}
      },
      rightClickIndex: -1,
      fromData: {},
      pageHeight: 0,
      prevHeight: 0,
      isLayoutChanging: false // 标记是否正在进行布局切换
    }
  },
  mounted() {},
  watch: {
    pageLimit(val) {
      // 只有当limit发生实质性变化且不是布局切换时才重新获取数据
      if (val !== this.where.limit && !this.isLayoutChanging) {
        this.where.limit = val
        this.getTreeData()
      } else {
        this.where.limit = val
      }
    },
    'fileStyle.style'(newVal, oldVal) {
      // 标记正在进行布局切换
      this.isLayoutChanging = true
      if (newVal !== oldVal) {
        this.$nextTick(() => {
          // 布局切换完成后取消标记
          this.isLayoutChanging = false
        })
      }
    }
  },
  methods: {
    fileDownLoad,
    fileIsImage,
    getFileTypeIconfont,
    pageChange(page) {
      this.where.page = page
      this.getTreeData()
    },
    handleSizeChange(val) {
      this.getTreeData()
    },
    formatBytesFn(size) {
      if (size) {
        size = Number(size)
        return formatBytes(size)
      } else {
        return '--'
      }
    },
    //获取数据
    getTreeData(val, keyword) {
      if (val == 1) {
        this.where.page = val
      }

      var data = {
        page: this.where.page,
        limit: this.pageLimit,
        pid: this.where.pid,
        file_type: fileTypeName(this.fileStyle.type),
        keyword: keyword || this.fileStyle.keyword,
        is_del: this.where.is_del
      }

      folderRecentlyListApi(data).then((res) => {
        this.fileData = res.data.list
        this.total = res.data.count
        this.$emit('totalFn', this.total)
      })
    },
    // 查看与下载附件
    async lookViewer(item) {
      this.isImage = true
      let url = item.file_url
      if (this.fileData.length > 0) {
        this.fileData.map((value, index) => {
          if (value === url) {
            this.fileData.splice(index, 1)
          }
        })
        this.srcList.unshift(url)
      }
      this.$refs.imageViewer.openImageViewer(url)
    },

    // 分享云文件
    shareOther(item) {
      this.$emit('shareItemFile', item)
    },

    // 表格行样式类名
    tableRowClassName({ row, rowIndex }) {
      return this.menuIds.includes(row.id) ? 'selected-row' : ''
    },

    // 点击item项
    getItemFile(item) {
      if (item.type == 1) {
        this.where.pid = item.id
        this.where.is_shortcut = 0
        if (this.breadcrumbArray.findIndex((n) => n.pid === item.id) < 0) {
          this.breadcrumbArray.push({ name: item.name, pid: item.id })
          this.getTreeData(1)
          this.handlerEmit({ name: item.name, pid: item.id })
        }
      } else {
        this.$emit('openItemFile', item)
      }
    },
    closeImageViewer() {
      this.isImage = false
    },
    // 点击面包屑
    getBreadcrumb(id, index) {
      if (id != this.where.pid) {
        if (this.switch === 5 && id == '') {
          this.where.is_shortcut = 1
        }
        this.where.page = 1
        this.where.pid = id
        this.getTreeData()
      }
    },
    handlerEmit(item) {
      this.handleData.id = this.where.pid === '' ? 0 : this.where.pid
      this.handleData.ids = this.menuIds
      this.$emit('handlerMyFile', this.handleData, item)
    },
    handleShow(index) {
      this.activeValue = index
    },
    checkChange(type) {
      if (type === 1) {
        if (this.fileData && this.fileData.length > 0) {
          this.fileData.map((item) => {
            if (this.menuIds.findIndex((n) => n === item.id) < 0) {
              this.checked = true
              this.menuIds.push(item.id)
            } else {
              this.checked = false
              if (this.menuIds.findIndex((n) => n == item.id) > -1) {
                this.menuIds.splice(
                  this.menuIds.findIndex((n) => n == item.id),
                  1
                )
              }
            }
          })
        }
      } else {
        if (this.menuIds.length === 1 && this.menuIds[0] === 0) {
          this.checked = false
          this.menuIds = []
        }
      }
      this.handlerEmit()
    },
    itemCheckChange(row) {
      if (this.fileStyle.style === 1) return false
      if (this.menuIds.includes(row.id)) {
        this.menuIds.splice(
          this.menuIds.findIndex((n) => n === row.id),
          1
        )
      } else {
        this.menuIds.push(row.id)
      }
      this.handlerEmit()
    },
    addAuthor(row) {
      this.fromData = {
        title: '设置目录权限',
        fid: this.spaceId,
        id: row.id,
        edit: 1
      }
      this.$refs.authorDialog.handleOpen()
    },
    // 浏览文件
    handleSeeFil(item) {
      folderSpaceEntViewApi(item.pid, item.id).then((res) => {
        if (!res.data.url) return this.$message.error('您暂时没有权限查看此文件')
        window.open(res.data.url, '_blank')
      })
    },
    getStartCollect(item) {
      this.getCollect(item)
      this.closePopover()
    },
    // 删除
    getFolderDelete(item) {
      modalSure('您确定要加入回收站').then(() => {
        let spaceId = this.spaceType == 'recently' ? item.pid : this.spaceId
        folderSpaceEntDeleteApi(spaceId, item.id).then((res) => {
          if (this.where.page > 1 && this.fileData.length <= 1) {
            this.where.page--
          }
          this.getTreeData()
          this.closePopover()
        })
      })
    },
    // 重命名
    setRenameItem(item, index) {
      this.renameIndex = index
      this.renameName = item.name
      this.closePopover()
      this.$nextTick(() => {
        document.getElementById('input' + index).focus()
      })
    },
    // 失去焦点
    renameBlur(item) {
      this.renameIndex = null
      if (item.name === '') {
        item.name = this.renameName
        return false
      }
      if (this.renameName !== trim(item.name)) {
        this.getFolderRename(item, { name: item.name })
      }
    },
    // 重命名
    getFolderRename(item, data) {
      let id = this.spaceType == 'recently' ? item.pid : this.spaceId
      folderSpaceEntRenameApi(id, item.id, data)
        .then((res) => {
          this.getTreeData()
        })
        .catch((error) => {
          this.getTreeData()
        })
    },
    getFileAttribute(item) {
      this.formBoxConfig = {
        title: '文件属性',
        is_file: false,
        fid: item.pid,
        id: item.id,
        width: '450px'
      }
      this.$refs.fileAttribute.openBox()
    },
    // 移动或复制
    getMoveDialog(item, type) {
      if (type === 1) {
        this.moveData.type = 3
      } else {
        this.moveData.type = 6
      }
      this.moveData.id = item.id
      this.moveData.fid = item.pid
      this.$refs.moveDialog.handleOpen()
    },
    // 移动回调
    handlerMove(data) {
      if (data.type === 3 || data.type === 6) {
        this.getTreeData()
      }
    },
    closePopover() {
      if (this.rightClickIndex > -1) {
        this.rightClickIndex = -1
      } else {
        const pop = this.$refs[`pop-${this.activeValue}`]
        const popover = Array.isArray(pop) ? pop[0] : pop
        popover?.hide()
      }
    },
    rightClick(event, item, index) {
      this.rightData.data = item
      this.rightClickIndex = index
      this.$refs.rightClick.menuVisible = true
      this.$refs.rightClick.rightClick(event)
    },
    // 右键
    handleRightClick(data) {
      if (data.index === 2) {
        // 移动
        this.getMoveDialog(data.row, 1)
      } else if (data.index === 1) {
        this.$emit('openItemFile', data.row)
      } else if (data.index === 3) {
        // 复制
        this.getMoveDialog(data.row, 2)
      } else if (data.index === 4) {
        // 重命名
        this.setRenameItem(data.row, this.rightClickIndex)
      } else if (data.index === 5) {
        // 属性
        this.getFileAttribute(data.row)
      } else if (data.index === 6) {
        // 删除
        this.getFolderDelete(data.row)
      } else if (data.index === 9) {
        // 设置权限
        this.addAuthor(data.row)
      } else if (data.index == 10) {
        // 分享
        this.shareOther(data.row)
      }
    },
    isOk() {
      this.getTreeData()
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-dialog) {
  border-radius: 8px;
}
.radio {
  margin-left: 15px;
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  .el-radio {
    margin-bottom: 10px;
  }
}
.box {
  :deep(.el-dialog__header) {
    padding: 0 !important;
  }
}

:deep(.el-dialog__body) {
  padding-left: 10px;
}
.img {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  margin-right: 6px;
  vertical-align: middle;
}

.content {
  .header-caption {
    :deep(.el-breadcrumb__inner) {
      cursor: pointer;
    }
  }
  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }

  // Element UI Table 样式覆盖
  :deep(.el-table) {
    // 调整行高与原来一致
    th,
    td {
      padding: 15px 0;
    }

    .el-table__header-wrapper,
    .el-table__header-wrapper table,
    .el-table__header-wrapper tr,
    .el-table__header-wrapper th,
    .el-table__header-wrapper .cell {
      cursor: default;
    }

    .el-table__body-wrapper tr,
    .el-table__body-wrapper td,
    .el-table__body-wrapper .cell {
      cursor: pointer;
    }

    .file-name-cell {
      display: flex;
      align-items: center;
      height: 24px;
      line-height: 24px;
      cursor: pointer;

      i {
        margin-right: 15px;
        font-size: 20px;
        width: 20px;
        height: 20px;
        line-height: 20px;
        flex-shrink: 0;
      }

      .folder-icon {
        width: 20px;
        height: 20px;
        margin-right: 15px;
        flex-shrink: 0;
      }

      span {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }

    .creator-cell {
      display: flex;
      align-items: center;
      height: 24px;
      line-height: 24px;

      .avatar {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        margin-right: 6px;
        vertical-align: middle;
        flex-shrink: 0;
      }
    }

    .operation-cell {
      text-align: center;
      height: 24px;
      line-height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;

      .icongengduo1 {
        font-size: 18px;
        cursor: pointer;
        color: #909399;

        &:hover {
          color: #409eff;
        }
      }
    }

    // 其他列的垂直居中
    .cell {
      height: 24px;
      line-height: 24px;
      display: flex;
      align-items: center;
    }

    // 选中行样式
    .selected-row {
      background-color: #ecf5ff !important;
    }

    // 鼠标悬停效果
    tr:hover {
      background-color: #f5f7fa !important;
    }
  }

  .public-ul {
    padding: 0;
    margin: 0;
    list-style: none;
    li {
      display: flex;
      align-items: center;
    }
    :deep(.el-checkbox__label) {
      display: none;
    }
  }

  .content-ul {
    li {
      display: flex;
      justify-content: center;
      padding: 15px;
      border-bottom: 1px solid #eeeeee;
      font-size: 13px;
      &:hover {
        background-color: #f5f5f5;
      }
      p {
        margin: 0;
        padding: 0;
        i {
          font-size: 20px;
        }
      }
      .content-left {
        width: 50%;
        display: flex;
        align-items: center;
        i {
          margin-right: 15px;
        }
        :deep(.el-checkbox) {
          margin-right: 15px;
          display: block !important;
        }
      }
      div:nth-of-type(2) {
        width: 15%;
      }
      div:nth-of-type(3) {
        width: 15%;
      }
      div:nth-of-type(4) {
        width: 25%;
      }
      .icon-star {
        width: 20%;
        padding-right: 15px;
        .collect {
          font-size: 20px;
        }
      }
      .icon-star-right {
        float: right;
        i {
          margin-right: 15px;
        }
      }
      .public-title {
        display: flex;
        align-items: center;
        line-height: 24px;
        .image {
          width: 30px;
          max-height: 25px;
          margin-right: 15px;
        }
        .rename-input {
          width: 100%;
          border: none;
          outline: none;
        }
        .rename-input:focus {
          border: transparent;
        }
      }
    }
  }

  .infeed-ul {
    display: flex;
    flex-wrap: wrap;
    li {
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      width: 110px;
      height: 134px;
      margin: 10px;
      flex-wrap: wrap;
      span,
      i {
        display: block;
      }
      p {
        margin: 0;
      }
      :deep(.el-checkbox) {
        padding-left: 10px;
        display: none;
      }
      .content-left {
        width: 100%;
        text-align: center;
        :deep(.el-checkbox) {
          position: absolute;
          left: 0;
          top: 10px;
        }
        i {
          margin-top: 14px;
          margin-left: 26px;
          text-align: center;
          font-size: 60px;
          height: 60px;
          line-height: 60px;
        }
        span {
          margin-top: 20px;
          text-align: center;
          font-family: PingFang SC, PingFang SC;
          font-weight: 400;
          font-size: 13px;
          color: #303133;
        }
        .image {
          max-height: 60px;
          max-width: 60px;
          margin-top: 10px;
        }

        .rename-input {
          width: 100%;
          border: none;
          outline: none;
          margin-top: 14px;
        }
        .rename-input:focus {
          border: transparent;
        }
      }
      .icon-star {
        position: absolute;
        left: 0;
        bottom: 20px;
      }
      &:hover {
        background-color: #f5f5f5;
        :deep(.el-checkbox) {
          display: block !important;
        }
      }
      &.active {
        background-color: #f5f5f5;
      }
    }
  }

  .folder {
    margin-top: 10px;
    width: 70px;
    height: 65px;
  }

  .file {
    width: 110px;
    margin: 0 auto;
    display: inline-block;
    display: flex;
    width: 55px;
    height: 66px;
    background: url('../../../../../assets/images/cloud/file-box.png') no-repeat;
    background-size: 55px 66px;
    color: #fff !important;
    text-align: center;
    line-height: 66px;
  }
  .collect {
    cursor: pointer;
  }
}

.page-fixed {
  position: fixed;
  bottom: 40px;
  right: 40px;
  z-index: 99;
  background: transparent;
  padding: 0;
  border: none;
  box-shadow: none;
}
</style>
