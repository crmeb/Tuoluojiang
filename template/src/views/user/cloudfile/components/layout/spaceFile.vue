<template>
  <div
    v-box-selection="{
      itemSelector: '.file-item',
      itemIdField: 'data-id',
      itemChangeCallback: handleFileItemSelectChange
    }"
    class="content"
    @dragenter="handleContentDragEnter"
    @dragover="handleContentDragOver"
    @drop="handleContentDrop"
  >
    <div class="v-height-flag mt6">
      <template v-if="fileData && fileData.length > 0">
        <div v-if="fileStyle.style === 1" class="first-header">
          <p>
            <el-checkbox :key="0" v-model="checked" :disabled="fileData.length <= 0" :label="0" :value="0"
              @change="checkChange(1)">
              {{ checked ? '反选' : '全选' }}
            </el-checkbox>
          </p>
          <p>文件大小</p>
          <p>创建人</p>
          <p>更新时间</p>
          <p>操作</p>
        </div>
        <div v-else class="first-header">
          <p>
            <el-checkbox :key="0" v-model="checked" :disabled="fileData.length <= 0" :label="0" :value="0"
              @change="checkChange(1)">
              {{ checked ? '反选' : '全选' }}
            </el-checkbox>
          </p>
        </div>
        <div id="listview" class="v-height-flag" v-loading="loading">
          <div v-height>
            <el-scrollbar style="height: 100%">
              <ul :class="fileStyle.style === 1 ? 'content-ul' : 'infeed-ul'" class="public-ul">
                <li
                  v-for="(item, index) in fileData"
                  :key="index"
                  :class="{
                    active: menuIds.includes(item.id),
                    'drag-source': draggingItem && draggingItem.id === item.id,
                    'drop-target': isDropTarget(item)
                  }"
                  :data-id="item.id"
                  class="file-item"
                  @dragover="handleFolderDragOver(item, $event)"
                  @drop="handleFolderDrop(item, $event)"
                >
                  <div class="content-left pointer public-title">
                    <el-checkbox
                      :key="item.id"
                      v-model="menuIds"
                      :label="item.id" :value="item.id"
                      :style="{ display: menuIds.includes(item.id) ? 'block' : 'none' }"
                      @change="checkChange(2)"
                    />
                    <p
                      v-if="renameIndex !== index"
                      class="file-drag-handle"
                      draggable="true"
                      @mousedown.stop
                      @click="itemCheckChange(item)"
                      @dblclick="getItemFile(item, '1')"
                      @contextmenu.prevent.stop="rightClick($event, item, index)"
                      @dragstart.stop="handleItemDragStart(item, $event)"
                      @dragend="handleItemDragEnd"
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
                      <!-- <template v-if="fileStyle.style == 1">
                          <i class="icon iconfont" :class="getFileTypeIconfont(item.type, item.file_ext)" />
                        </template> -->

                        <span v-if="item.file_ext" :class="fileStyle.style == 1 ? 'public-title' : 'over-text'">
                          <i v-if="fileStyle.style == 1 && item.type == 0"
                            :class="getFileTypeIconfont(item.type, item.file_ext)" class="icon iconfont" />{{ item.name
                            }}.{{ item.file_ext }}</span>
                        <!-- 文件夹 -->
                        <span v-else :class="fileStyle.style == 1 ? 'public-title' : 'over-text'">
                          <img v-if="fileStyle.style == 1 && item.type == 1" alt="" class="mr14"
                            src="../../../../../assets/images/cloud/file.png" style="width: 20px; height: 20px" />{{
                              item.name }}</span>
                      </p>
                      <p v-else class="public-title">
                        <img v-if="fileIsImage('image/jpegs')" :src="item.file_url" class="image" />

                        <i v-else-if="fileStyle.style == 1" :class="getFileTypeIconfont(item.type, item.file_ext)"
                          class="icon iconfont" />
                        <input :id="'input' + index" v-model="item.name" placeholder="请输入文件名称"
                          autofocus="autofocus" class="rename-input" maxlength="30" @blur="renameBlur(item)" />
                      </p>
                    </div>
                    <div v-if="fileStyle.style === 1">{{ formatBytesFn(item.file_size) }}</div>
                    <div v-if="fileStyle.style === 1" style="display: flex; align-items: center;">
                      <img :src="item.user ? item.user.avatar : ''" alt="" class="img" />
                      {{
                        item.user ? item.user.name : '--'
                      }}
                    </div>

                    <div v-if="fileStyle.style === 1">
                      {{ item.updated_at }}
                    </div>
                    <div v-if="fileStyle.style === 1" class="icon-star">
                      <div class="icon-star-right pointer">
                        <el-popover :ref="`pop-${index}`" :offset="30" :width="40" placement="bottom-start"
                          trigger="click" @after-enter="handleShow(index)">
                          <div class="right-item-list">
                            <div v-if="item.type !== 1 && openTypes.includes(item.file_ext)" class="right-item"
                              @click="getItemFile(item)">
                              打开
                            </div>
                            <div v-if="item.type !== 1" class="right-item" @click="fileDownLoad(item)">
                              下载
                            </div>
                            <div v-if="item.type !== 1" class="right-item" @click="shareOther(item)">分享</div>
                            <el-divider v-if="item.type !== 1" />
                            <div class="right-item" @click="getMoveDialog(item, 1)">移动至</div>
                            <div v-if="item.type === 1 && item.user_id === userStore.userInfo.id"
                              class="right-item" @click="addAuthor(item)">
                              目录权限
                            </div>
                            <div v-if="item.type !== 1" class="right-item" @click="getMoveDialog(item, 2)">
                              复制到
                            </div>
                            <div class="right-item" @click="setRenameItem(item, index)">重命名</div>
                            <el-divider />
                            <div class="right-item" @click="getFolderDelete(item.id)">删除</div>
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
              <el-pagination :current-page="where.page" :page-size="where.limit" :page-sizes="[10, 15, 20]"
                :total="total" class="page-fixed" layout="total, prev, pager, next, jumper"
                @size-change="handleSizeChange" @current-change="pageChange" />
            </el-scrollbar>
          </div>
        </div>
      </template>
      <default-page v-else v-height :index="7" :min-height="510" />
    </div>
    <!-- 图片查看弹窗 -->
    <el-image-viewer v-if="isImage" @close="closeImageViewer" :url-list="srcList" />
    <image-viewer ref="imageViewer" :src-list="srcList"></image-viewer>
    <!-- 移动弹窗 -->
    <move-dialog ref="moveDialog" :move-data="moveData" @handlerMove="handlerMove" />
    <!-- 文件属性详情侧滑 -->
    <file-attribute ref="fileAttribute" :form-data="formBoxConfig" />
    <!-- 列表点击右键弹窗 -->
    <right-click ref="rightClick" :config-data="rightData" @handleRightClick="handleRightClick" />
    <!-- 设置目录权限弹窗 -->
    <author-dialog ref="authorDialog" :from-data="fromData" />
  </div>
</template>

<script>
import { mapStores } from 'pinia'
import { useUserStore } from '@/stores/user.ts'
import { defineAsyncComponent } from 'vue'
import {
  folderSpaceEntAllMoveApi,
  folderSpaceEntDeleteApi,
  folderSpaceEntListApi,
  folderSpaceEntMoveApi,
  folderSpaceEntRenameApi,
} from "@/api/cloud.ts";
import {
  fileDownLoad,
  fileIsImage,
  fileSortName,
  fileTypeName,
  formatBytes,
  getFileTypeIconfont,
  trim
} from '@/utils/file.ts'
import helper from "@/libs/helper.ts";
import { modalSure } from '@/libs/public.ts';
export default {
  emits: ['drag-move-state-change', 'totalFn', 'shareItemFile', 'openItemFile', 'handlerMyFile'],
  name: 'SpaceFile',
  components: {
    imageViewer: defineAsyncComponent(() => import('@/components/common/imageViewer')),
    moveDialog: defineAsyncComponent(() => import('../moveDialog')),
    fileAttribute: defineAsyncComponent(() => import('../fileAttribute')),
    rightClick: defineAsyncComponent(() => import('../rightClick')),
    defaultPage: defineAsyncComponent(() => import('@/components/common/defaultPage')),
    authorDialog: defineAsyncComponent(() => import('../authorDialog'))
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
  computed: {
    ...mapStores(useUserStore)
  },
  data() {
    return {
      checked: false,
      fileData: [],
      activeValue: '',
      renameIndex: null,
      renameName: '',
      shareItem: {},
      openTypes: helper.openType,
      where: {
        page: 1,
        limit: this.pageLimit,
        pid: '',
        is_del: 0
      },
      total: 0,
      breadcrumbArray: [],
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
      draggingItem: null,
      dragOverFolderId: null,
      dragMoving: false,
      globalDragGuardActive: false,
      loading: false,
    };
  },
  mounted() { },
  watch: {
    pageLimit(val) {
      this.where.limit = val;
    },
  },
  mounted() {
    this.boundWindowDragOver = this.handleWindowDragOver.bind(this);
    this.boundWindowDrop = this.handleWindowDrop.bind(this);
  },
  beforeUnmount() {
    this.unregisterGlobalDragGuards();
    this.notifyDragMoveStateChange(false);
  },
  methods: {
    fileDownLoad,
    fileIsImage,
    getFileTypeIconfont,
    handleFileItemSelectChange(e) {
      this.menuIds = e
      this.handlerEmit()
    },
    pageChange(page) {
      this.where.page = page;
      this.getTreeData();
    },
    handleSizeChange() {
      this.getTreeData();
    },
    notifyDragMoveStateChange(isDragging) {
      this.$emit("drag-move-state-change", isDragging);
    },
    registerGlobalDragGuards() {
      if (this.globalDragGuardActive) {
        return;
      }
      window.addEventListener("dragover", this.boundWindowDragOver);
      window.addEventListener("drop", this.boundWindowDrop);
      this.globalDragGuardActive = true;
    },
    unregisterGlobalDragGuards() {
      if (!this.globalDragGuardActive) {
        return;
      }
      window.removeEventListener("dragover", this.boundWindowDragOver);
      window.removeEventListener("drop", this.boundWindowDrop);
      this.globalDragGuardActive = false;
    },
    getMoveSourceFid(item) {
      return item.pid || this.spaceId;
    },
    getDragMoveIds(dragItem, targetFolderId = null) {
      const selectedIds =
        this.menuIds.includes(dragItem.id) && this.menuIds.length > 1
          ? [...new Set(this.menuIds)]
          : [dragItem.id];
      if (targetFolderId === null) {
        return selectedIds;
      }
      return selectedIds.filter((id) => String(id) !== String(targetFolderId));
    },
    getDropTargetFromEvent(event) {
      if (!event.target || typeof event.target.closest !== "function") {
        return null;
      }
      const fileItem = event.target.closest(".file-item");
      if (!fileItem) {
        return null;
      }
      if (!Array.isArray(this.fileData)) {
        return null;
      }
      const targetId = fileItem.getAttribute("data-id");
      return this.fileData.find((item) => String(item.id) === String(targetId)) || null;
    },
    handleItemDragStart(item, event) {
      if (this.dragMoving || this.loading || !event.dataTransfer) {
        event.preventDefault();
        return;
      }
      this.draggingItem = {
        id: item.id,
        pid: item.pid,
        fid: this.getMoveSourceFid(item),
      };
      this.dragOverFolderId = null;
      this.registerGlobalDragGuards();
      this.notifyDragMoveStateChange(true);
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.dropEffect = "move";
      event.dataTransfer.setData("text/plain", String(item.id));
      event.dataTransfer.setData("application/x-cloudfile-move", String(item.id));
    },
    handleItemDragEnd() {
      if (this.dragMoving) {
        return;
      }
      this.resetDragState();
    },
    canDropToFolder(item) {
      if (!this.draggingItem || item.type !== 1) {
        return false;
      }
      return item.id !== this.draggingItem.id;
    },
    isDropTarget(item) {
      return this.canDropToFolder(item) && this.dragOverFolderId === item.id;
    },
    handleContentDragEnter(event) {
      if (!this.draggingItem) {
        return;
      }
      event.stopPropagation();
    },
    handleContentDragOver(event) {
      if (!this.draggingItem) {
        return;
      }
      const targetItem = this.getDropTargetFromEvent(event);
      event.preventDefault();
      event.stopPropagation();
      if (!targetItem || !this.canDropToFolder(targetItem)) {
        this.dragOverFolderId = null;
        if (event.dataTransfer) {
          event.dataTransfer.dropEffect = "none";
        }
      }
    },
    handleContentDrop(event) {
      if (!this.draggingItem) {
        return;
      }
      const targetItem = this.getDropTargetFromEvent(event);
      event.preventDefault();
      event.stopPropagation();
      if (!targetItem || !this.canDropToFolder(targetItem)) {
        this.resetDragState();
      }
    },
    handleFolderDragOver(item, event) {
      if (!this.canDropToFolder(item)) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = "move";
      }
      this.dragOverFolderId = item.id;
    },
    handleWindowDragOver(event) {
      if (!this.draggingItem || this.$el.contains(event.target)) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      this.dragOverFolderId = null;
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = "none";
      }
    },
    handleWindowDrop(event) {
      if (!this.draggingItem || this.$el.contains(event.target)) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      this.resetDragState();
    },
    handleFolderDrop(item, event) {
      const dragItem = this.draggingItem;
      if (!dragItem) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (!this.canDropToFolder(item) || this.dragMoving) {
        this.resetDragState();
        return;
      }
      const moveIds = this.getDragMoveIds(dragItem, item.id);
      if (!moveIds.length) {
        this.resetDragState();
        return;
      }
      if (moveIds.length === 1 && String(dragItem.pid || "") === String(item.id)) {
        this.$message.warning("该文件已在当前文件夹中");
        this.resetDragState();
        return;
      }
      this.dragMoving = true;
      const moveRequest =
        moveIds.length > 1
          ? folderSpaceEntAllMoveApi(dragItem.fid, {
            id: moveIds,
            to_id: item.id,
          })
          : folderSpaceEntMoveApi(dragItem.fid, dragItem.id, { to_id: item.id });
      moveRequest
        .then(() => {
          this.menuIds = [];
          this.checked = false;
          this.handlerEmit();
          this.getTreeData();
        })
        .finally(() => {
          this.dragMoving = false;
          this.resetDragState();
        });
    },
    resetDragState() {
      this.draggingItem = null;
      this.dragOverFolderId = null;
      this.unregisterGlobalDragGuards();
      this.notifyDragMoveStateChange(false);
    },
    //获取数据
    getTreeData(val) {
      if (this.loading) return
      this.loading = true
      if (val == 1) {
        this.where.page = val
      }
      var data = {
        page: this.where.page,
        limit: this.pageLimit,
        pid: this.where.pid,
        file_type: fileTypeName(this.fileStyle.type),
        keyword: this.fileStyle.keyword,
        is_del: this.where.is_del
      }
      data.sort_by = fileSortName(this.fileStyle.sortBy)
      data.sort_type = fileSortName(this.fileStyle.sort)

      folderSpaceEntListApi(this.spaceId, data)
        .then((res) => {
          this.fileData = res.data.list
          this.total = res.data.count
          this.$emit('totalFn', this.total)
        })
        .finally(() => {
          this.loading = false
        })
      // }
    },
    // 查看与下载附件
    lookViewer(item) {
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
    formatBytesFn(size) {
      if (size) {
        size = Number(size)
        return formatBytes(size)
      } else {
        return '--'
      }
    },

    // 分享云文件
    shareOther(item) {
      this.closePopover()
      this.$emit('shareItemFile', item)
    },
    // 点击item项
    getItemFile(item, val) {
      if (!val) {
        this.closePopover()
      }

      if (item.type == 1) {
        this.where.pid = item.id
        this.where.is_shortcut = 0
        if (this.breadcrumbArray.findIndex((n) => n.pid == item.id) < 0) {
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
        // 空数据直接返回，避免无效计算
        if (!this.fileData || this.fileData.length === 0) {
          return
        }

        const menuIdSet = new Set(this.menuIds)
        const allIds = this.fileData.map((item) => item.id)
        const checkedIds = allIds.filter((id) => menuIdSet.has(id))
        const isAllChecked = checkedIds.length === allIds.length

        // 全选/取消全选状态切换
        this.menuIds = isAllChecked ? [] : allIds
        this.checked = !isAllChecked
      }
      // 处理单个选项逻辑
      else {
        // 特殊场景：仅选中了0且要取消的情况
        if (this.menuIds.length === 1 && this.menuIds[0] === 0) {
          this.checked = false
          this.menuIds = []
        } else {
          // 自动更新全选状态（当所有选项都选中时）
          if (this.fileData && this.fileData.length > 0) {
            const allIds = new Set(this.fileData.map((item) => item.id))
            const currentIds = new Set(this.menuIds)
            // 检查是否所有文件ID都已选中
            this.checked = this.fileData.every((item) => currentIds.has(item.id))
          }
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
      this.closePopover()
      this.$refs.authorDialog.handleOpen()
    },
    getStartCollect(item) {
      this.getCollect(item)
      this.closePopover()
    },
    // 删除
    getFolderDelete(id) {
      modalSure('您确定要加入回收站').then(() => {
        folderSpaceEntDeleteApi(this.spaceId, id).then((res) => {
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
        fid: this.spaceType == 'recently' ? item.pid : this.spaceId,
        id: item.id,
        width: '450px'
      }
      this.closePopover()
      this.$refs.fileAttribute.openBox()
    },
    // 移动或复制
    getMoveDialog(item, type) {
      if (type === 1) {
        this.moveData.type = 3
      } else {
        this.moveData.type = 6
      }
      this.moveData.id = item.id;
      this.moveData.fid = this.getMoveSourceFid(item);
      this.closePopover();
      this.$refs.moveDialog.handleOpen();
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
        this.$refs[`pop-${this.activeValue}`][0].hide()
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
      if (data.index === 1) {
        // 预览

        this.getItemFile(data.row)
      }
      if (data.index === 2) {
        // 移动
        this.getMoveDialog(data.row, 1)
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
        this.getFolderDelete(data.row.id)
      } else if (data.index === 9) {
        // 设置权限
        this.addAuthor(data.row)
      } else if (data.index == 10) {
        // 分享
        this.shareOther(data.row)
      }
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
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;

  .header-caption {
    :deep(.el-breadcrumb__inner) {
      cursor: pointer;
    }
  }

  :deep(.el-scrollbar__wrap) {
    overflow-x: hidden;
  }

  .space-file-layout {
    height: 100%;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
  }

  .first-header {
    background-color: #f7fbff;
    border-bottom: none;
    margin-bottom: 0;
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) 0.7fr 0.8fr 1fr 0.5fr;
    align-items: center;
    padding: 10px 15px;

    p {
      margin: 0;
      padding: 0;
      min-width: 0;
      text-align: left;
      font-family: PingFang SC, PingFang SC;
      font-weight: 500 !important;
      font-size: 13px;
      color: #303133;
    }

    p:nth-of-type(1) {
      display: flex;
      align-items: center;

      i {
        margin-right: 15px;
      }

      :deep(.el-checkbox) {
        margin-right: 15px;
      }
    }

    p:nth-of-type(2) {
      width: auto;
    }

    p:nth-of-type(3) {
      width: auto;
    }

    p:nth-of-type(4) {
      width: auto;
    }

    p:nth-of-type(5) {
      width: auto;
      text-align: left;
    }
  }

  .collect {
    cursor: pointer;
  }

  .file-drag-handle {
    cursor: grab;
    user-select: none;
  }

  .mt6 {
    margin-top: 6px;
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
      display: grid;
      grid-template-columns: minmax(0, 1.8fr) 0.7fr 0.8fr 1fr 0.5fr;
      align-items: center;
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

      &.drag-source {
        opacity: 0.7;
      }

      &.drop-target {
        background-color: #ecf5ff;
      }

      .content-left {
        width: auto;
        min-width: 0;
        display: flex;
        align-items: center;

        i {
          margin-top: -3px;
          margin-right: 15px;
        }

        :deep(.el-checkbox) {
          margin-right: 15px;
          display: block !important;
        }
      }

      div:nth-of-type(2) {
        width: auto;
        text-align: left;
      }

      div:nth-of-type(3) {
        width: auto;
        text-align: left;
      }

      div:nth-of-type(4) {
        width: auto;
        text-align: left;
      }

      .icon-star {
        width: auto;
        padding-right: 0;
        display: flex;
        justify-content: flex-start;

        .collect {
          font-size: 20px;
        }
      }

      .icon-star-right {
        display: flex;
        justify-content: flex-start;

        i {
          margin-right: 15px;
        }
      }

      .public-title {
        display: flex;
        align-items: center;
        line-height: 24px;
        min-width: 0;

        // width: calc(100% - 150px);
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
      height: 144px;
      margin: 9px;
      padding: 0 10px 10px 10px;
      flex-wrap: wrap;

      span,
      i {
        display: block;
      }

      &.drag-source {
        opacity: 0.7;
      }

      &.drop-target {
        background-color: #ecf5ff;
        box-shadow: inset 0 0 0 1px #409eff;
        border-radius: 4px;
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
}

.folder {
  margin: 21px auto 0;
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

.page-fixed {
  position: fixed;
  bottom: 40px;
  right: 40px;
  background: transparent;
  padding: 0;
  border: none;
  box-shadow: none;
}
</style>
