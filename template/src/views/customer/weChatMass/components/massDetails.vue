<!-- 查看客户信息侧滑页面 -->
<template>
  <div class="station">
    <el-drawer
      class="customer-we-chat-mass-mass-details-drawer"
      :append-to-body="true"
      :direction="direction"
      :show-close="false"
      :size="DRAWER_SIZE.LG"
      title="查看"
      v-model="drawer"
    >
      <template #header>
        <div class="invoice-title">
          <div class="invoice-header">
            <div class="invoice-left">
              <div class="invoice-logo"><i class="icon iconfont iconhetong"></i></div>
            </div>
            <div class="invoice-right">
              <div class="txt1 over-text">{{ title }}</div>

              <div class="txt2">
                <span class="title">
                  任务状态：
                  <span v-if="dataInfo.status == 1" class="info3">定时中</span>
                  <span v-else-if="dataInfo.status == 2" class="info2">发送中</span>
                  <span v-else-if="dataInfo.status == 0" class="info1">已停止</span>
                  <span v-else-if="dataInfo.status == 3" class="info4">已完成</span>
                </span>
                <template v-if="types == '0'">
                  <span class="title"
                    >已发送客户：<span class="weight">{{ dataInfo.is_send || 0 }}</span></span
                  >
                  <span class="title"
                    >送达客户：<span class="weight">{{ dataInfo.is_sent || 0 }}</span></span
                  >
                  <span class="title"
                    >未送达客户：<span class="weight">{{ dataInfo.not_sent || 0 }}</span></span
                  >
                </template>
                <template v-if="types == '1'">
                  <span class="title"
                    >送达客户群：<span class="weight">{{ dataInfo.is_send || 0 }}</span></span
                  >

                  <span class="title"
                    >未送达客户群：<span class="weight">{{ dataInfo.not_sent || 0 }}</span></span
                  >
                </template>
              </div>
            </div>
          </div>
          <div @click="handleClose" class="el-icon-close"></div>
        </div>
      </template>

      <el-tabs
        v-if="tabData.length"
        v-model="tabIndex"
        :tab-position="tabPosition"
        type="border-card"
        @tab-click="handleClick"
      >
        <el-tab-pane v-for="item in tabData" :key="item.value" :label="item.label" :name="item.value">
          <div v-if="item.value == 1" style="width: 950px">
            <el-form class="invoice-body" label-width="auto">
              <div class="title-box">{{ types == 2 ? '员工发表情况' : '员工发送情况' }}</div>
              <el-form-item :label="types == 1 ? '群主：' : '群发员工：'">
                <div class="send-box">
                  <div v-for="(item, index) in dataInfo.send_user" class="user-box">
                    <img :src="item.avatar" alt="" class="img" />
                    {{ item.name }}
                    <span
                      class="el-icon-success"
                      v-if="dataInfo.sent_uid.length > 0 && dataInfo.sent_uid.includes(item.id)"
                    ></span>
                  </div>
                </div>
              </el-form-item>
              <el-form-item label="发送范围：">
                <span>{{ dataInfo.is_modify == 1 ? '允许员工调整' : '不允许员工调整' }}</span>
              </el-form-item>

              <div class="title-box">群发内容</div>
              <el-form-item label="群发内容：">
                <span>{{ dataInfo.temp ? dataInfo.temp.content : '--' }}</span>

                <div class="box mt10" v-if="dataInfo.temp && dataInfo.temp.attach && dataInfo.temp.attach.length > 0">
                  <div v-for="(file, index) in dataInfo.temp.attach" :key="index" class="item">
                    <div class="flex lh-center">
                      <span class="iconfont iconxiaochengxu1" v-if="file.types === 'mini_program'"></span>
                      <span class="iconfont iconlianjie1" v-else-if="file.types === 'link'"></span>
                      <template v-else>
                        <span class="file" v-if="file.file && toSrcIcon(file.file.name) !== 'img'">{{
                          getFileTypeFn(file.file.name || '')
                        }}</span>
                        <img
                          v-if="file.file && toSrcIcon(file.file.name) == 'img' && file.file.url"
                          :src="file.file.url"
                          alt=""
                          class="img"
                          @click.stop="openFile(file.file)"
                        />
                      </template>
                      <span v-if="['mini_program', 'link'].includes(file.types)" style="width: 230px" class="over-text"
                        >{{ file.title }}
                      </span>
                      <span v-else style="width: 200px" class="over-text">
                        {{ file.file ? file.file.name : '--' }}</span
                      >
                    </div>
                    <div class="file-actions" style="margin-left: auto" v-if="file.types !== 'mini_program'">
                      <i class="iconfont iconyulan" @click.stop="openFile(file.file, file)"></i>
                      <i class="iconfont iconxiazai" @click.stop="downLoad(file.file)" v-if="file.types !== 'link'"></i>
                    </div>
                  </div>
                </div>
              </el-form-item>
              <el-form-item label="发送时间：">
                <span>{{ dataInfo.send_time || '--' }}</span>
              </el-form-item>
            </el-form>
          </div>
          <div v-if="item.value == 2" style="width: 960px">
            <el-table :data="tableData" fit style="width: 100%" v-if="types == 1">
              <el-table-column label="群聊名称">
                <template #default="scope">{{ scope.row.chat_group.name || '--' }}</template>
              </el-table-column>
              <el-table-column label="接收情况">
                <template #default="scope">
                  <el-tag v-if="scope.row.status == 0">未发送</el-tag>
                  <el-tag v-else-if="scope.row.status == 2 || scope.row.status == 3" type="info">未送达</el-tag>
                  <el-tag v-else-if="scope.row.status == 1" type="success">已完成</el-tag>
                </template>
              </el-table-column>
              <!-- <el-table-column prop="is_comment" label="已读人数"> </el-table-column> -->
              <el-table-column prop="admin.name" label="推送人"> </el-table-column>
              <el-table-column prop="send_time" label="推送时间">
                <template #default="scope">{{ scope.row.send_time || '--' }}</template>
              </el-table-column>
            </el-table>
            <el-table :data="tableData" fit style="width: 100%" v-if="types != 1">
              <el-table-column label="客户信息">
                <template #default="scope">
                  <div class="work-user">
                    <img
                      v-if="scope.row.customer && scope.row.customer.avatar"
                      :src="scope.row.customer.avatar"
                      alt=""
                      class="img"
                    />
                    {{ scope.row.customer.name || '--' }}
                    <span v-if="scope.row.customer.type == 1" class="info4 ml4">@微信</span>
                    <span v-if="scope.row.customer.type == 2" class="info2 ml4"
                      >@{{ scope.row.customer.corp_name || '--' }}</span
                    >
                  </div>
                </template>
              </el-table-column>
              <!-- <el-table-column label="接收情况">
                <template #default="scope">
                  <el-tag v-if="scope.row.status == 0">未发送</el-tag>
                  <el-tag v-else-if="scope.row.status == 2 || scope.row.status == 3" type="info">未送达</el-tag>
                  <el-tag v-else-if="scope.row.status == 1" type="success">已送达</el-tag>
                </template>
              </el-table-column> -->

              <el-table-column v-if="types == '2'" label="客户行为">
                <template #default="scope">
                  <span v-if="scope.row.is_like || scope.row.is_comment">
                    {{ scope.row.is_like ? '点赞' : '' }} {{ scope.row.is_comment ? '评论' : '' }}
                  </span>
                  <span v-else>--</span>
                </template>
              </el-table-column>

              <el-table-column prop="admin.name" label="推送人"> </el-table-column>
              <el-table-column prop="send_time" label="推送时间" v-if="types == 0">
                <template #default="scope">{{ scope.row.send_time || '--' }}</template>
              </el-table-column>
            </el-table>
            <div class="pagination">
              <el-pagination
                :page-size="where.limit"
                :current-page="where.page"
                layout="total, prev, pager, next, jumper"
                :total="total"
                @current-change="pageChange"
              />
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-drawer>
  </div>
</template>
<script>
import { getWorkMassEdit, getWorkMassResult } from '@/api/weCom.ts'
import { getFileExtension, getFileType } from '@/utils/file.ts'
import { DRAWER_SIZE } from '@/constants/popupSize.ts'
import { filePreview } from '@/utils/filePreview.ts'
export default {
  name: 'detailsDrawer',
  props: {},
  data() {
    return {
      DRAWER_SIZE,
      dataInfo: {},
      tableData: [],
      id: 0,
      types: '',
      drawer: false,
      direction: 'rtl',
      tabPosition: 'top',
      tabIndex: '1',
      tabNumber: 1,
      where: {
        page: 1,
        limit: 10
      },
      total: 0,
      tabData: [
        { value: '1', label: '基础信息' },
        { value: '2', label: '接收情况' }
      ]
    }
  },
  computed: {
    title() {
      let str = '客户群发'
      if (this.types == '1') {
        str = '客户群群发'
      } else if (this.types == '2') {
        str = '朋友圈群发'
      } else if (this.types == '0') {
        str = '客户群发'
      }
      return str
    }
  },

  methods: {
    async getDetails(id) {
      const result = await getWorkMassEdit(id)
      this.dataInfo = result.data
    },
    toSrcIcon(name) {
      return getFileType(name)
    },
    getFileTypeFn(name) {
      return getFileExtension(name)
    },
    pageChange(val) {
      this.where.page = val
      this.getTableData()
    },
    openFile(item, rowData) {
      if (rowData.types === 'link') {
        window.open(rowData.link, '_blank')
      } else {
        if (this.toSrcIcon(item.name) == 'img' || item.name.toLowerCase().endsWith('.mp4')) {
          filePreview(item)
        } else {
          window.open(`https://view.officeapps.live.com/op/embed.aspx?src=${item.url}`, '_blank')
        }
      }
    },
    getTableData() {
      getWorkMassResult(this.id, this.where).then((res) => {
        this.tableData = res.data.list
        this.total = res.data.count
      })
    },

    handleClose() {
      this.$nextTick(() => {
        this.drawer = false
      })
    },
    downLoad(fileItem) {
      const url = fileItem.url || fileItem.src
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', fileItem.name)
      // 隐藏链接，避免影响页面显示
      link.style.display = 'none'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    },

    openBox(id, type) {
      this.tabIndex = '1'
      this.tabNumber = 1
      this.id = id
      this.types = type
      this.getDetails(id)
      this.drawer = true
    },

    // 点击tab切换
    handleClick(event) {
      this.tabNumber = Number(event.paneName)
      if (this.tabNumber == 2) {
        this.getTableData()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
:deep(.el-tabs__item.is-active) {
  border-right-color: transparent !important;
  border-left-color: transparent !important;
  &::after {
    content: '';
    height: 2px;
    width: 100%;
    background-color: #1890ff;
    position: absolute;
    left: 0;
    top: 0;
  }
}
.send-box {
  display: flex;
  flex-wrap: wrap;
}
.el-icon-close {
  width: 20px;
  height: 20px;
  cursor: pointer;
  font-size: 13px;
  z-index: 99;
}
:deep(.el-tabs__item) {
  line-height: 40px !important;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 13px;
  color: #303133;
}
:deep(.el-tabs__header) {
  background-color: #f7fbff;
  border-bottom: none;
}
:deep(.el-tabs__nav-wrap::after) {
  height: 0;
}
:deep(.el-tabs__active-bar) {
  top: 0;
}
.el-tabs--border-card {
  height: 39px;
  position: fixed;
  top: 80px;
  width: 100%;
  z-index: 4;
  background-color: transparent;
  border: none;
  box-shadow: none;

  // Element Plus 的 tabs 是 flex 列布局，内容区默认会被压进 39px 的容器里
  :deep(.el-tabs__content) {
    flex-shrink: 0;
  }
}
.invoice-title {
  display: flex;
  justify-content: space-between;
  .invoice-header {
    display: flex;
    align-items: center;
    .invoice-left {
      width: 48px;
      margin-right: 10px;
      .invoice-logo {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #1890ff;
        border-radius: 4px;
        i {
          color: #ffffff;
          font-size: 30px;
        }
      }
    }
    .invoice-right {
      width: calc(100% - 55px);
      height: 48px;
    }
    .txt1 {
      font-size: 16px;
      font-weight: bold;
      color: rgba(0, 0, 0, 0.85);
    }
    .weight {
      font-family: PingFang SC, PingFang SC;
      font-weight: 400;
      font-size: 14px;
      color: #1a1a1a;
    }
    .txt3 {
      font-size: 14px;
    }
    .txt2 {
      margin-top: 10px;
      font-size: 13px;
      color: #000;
      .title {
        font-family: PingFang SC, PingFang SC;
        font-weight: 400;
        font-size: 14px;
        color: #606266;
        margin-right: 30px;
      }
      .title:first-of-type {
        padding-left: 0;
      }
      .info1 {
        color: #909399;
      }

      .info3 {
        color: #1890ff;
      }
    }
  }
}
.info4 {
  color: #19be6b;
}
.info2 {
  color: #ff9900;
}
.title-box {
  font-family: PingFang SC, sans-serif;
  font-weight: 500;
  font-size: 14px;
  color: #303133;
  margin: 0 0 15px 9px;
  position: relative;
  &:before {
    content: '';
    background-color: #1890ff;
    width: 3px;
    height: 14px;
    position: absolute;
    left: -9px;
    top: 50%;
    transform: translateY(-50%); // 垂直居中更可靠
  }
}
.contract-body {
  margin-top: 39px;
  padding: 20px;
  display: flex;
  height: 100%;
  justify-content: center;

  .contract-record {
    width: 100%;
  }
  .contract-remind {
    height: calc(100% - 120px);
  }
}

.line {
  width: 100% !important;
  height: 3px;
  border-bottom: 1px dashed #dcdfe6;
}
.user-box {
  height: 30px;
  padding: 0 8px;
  display: flex;
  align-items: center;
  background: #f0f2f5;
  margin-top: 5px;
  margin-right: 10px;
  border-radius: 4px;
  position: relative;
  .img {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 4px;
    margin-left: 4px;
  }
  .el-icon-success {
    position: absolute;
    right: -4px;
    top: -8px;
    color: #19be6b;
    font-size: 14px;
  }
}
.box {
  // display: flex;
  // flex-wrap: wrap;
  .item {
    width: 300px;
    height: 30px;
    background: #f7f7f7;
    border-radius: 4px 4px 4px 4px;
    font-family: PingFang SC, PingFang SC;
    font-weight: 400;
    font-size: 12px;
    color: #606266;
    margin-bottom: 10px;
    line-height: 30px;
    padding-right: 12px;
    display: flex;
    margin-right: 10px;
    justify-content: space-between;
    align-items: center;
  }
  .iconlianjie1 {
    font-size: 18px;
    color: #1890ff;
    margin-right: 4px;
    margin-left: 4px;
  }
  .iconxiaochengxu1 {
    font-size: 18px;
    color: #19be6b;
    margin-right: 6px;
    margin-left: 4px;
  }
  .required {
    color: #f56c6c;
    margin-right: 6px;
    margin-left: 4px;
  }
  .img {
    width: 23px;
    height: 22px;
    border-radius: 4px;
    margin-right: 4px;
    margin-left: 4px;
  }
  .file {
    display: flex;
    width: 22px;
    height: 23px;
    background: url('../../../../assets/images/cloud/file-box.png') no-repeat;
    background-size: 22px 23px;
    color: #fff !important;
    justify-content: center;
    line-height: 22px;
    font-size: 11px;
    margin-right: 4px;
    margin-left: 4px;
  }
}
.file-actions {
  cursor: pointer;

  color: #303033;
  .iconyulan {
    margin-right: 10px;
  }
}

.work-user {
  display: flex;
  align-items: center;
  .img {
    display: block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 4px;
    margin-left: 4px;
  }
  .ml4 {
    margin-left: 4px;
  }
}
:deep(.el-form-item) {
  margin-bottom: 10px;
}
</style>

<style lang="scss">
// 抽屉挂到 body 上，scoped 样式选不到，按 class 定位
.customer-we-chat-mass-mass-details-drawer .el-drawer__body {
  padding-bottom: 50px;
}
.customer-we-chat-mass-mass-details-drawer .el-drawer__header {
  height: 80px !important;
  border: none;
  padding: 14px 18px;
}
</style>
