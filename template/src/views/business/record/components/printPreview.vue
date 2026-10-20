<template>

  <el-dialog title="打印预览" top="8vh" v-model="visible" :append-to-body="true" width="800px">
    <div class="print-preview">
      <div class="print-content" ref="printContent">
        <div class="print-inner">
          <div class="header">
            <div class="title">{{ printData.approve ? printData.approve.name : '审批' }}</div>

          </div>
          <div class="flex flex-between mb10">
            <span>{{ enterpriseInfo.enterprise_name || '--' }}</span>
            <span>审批编号：{{ printData.node_id }}</span>
          </div>
          <div class="info-table">
            <table>
              <tbody>
                <tr>
                  <td class="label">申请人</td>
                  <td class="value">{{ printData.card ? printData.card.name : '--' }}</td>
                  <td class="label">申请人部门</td>
                  <td class="value">{{ printData.frame ? printData.frame.name : '--' }}</td>
                </tr>
                <tr>
                  <td class="label">提交时间</td>
                  <td class="value">{{ printData.created_at ? formatDate(printData.created_at) : '--' }}</td>
                  <td class="label">当前审批状态</td>
                  <td class="value">{{ getStatusText(printData.status) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="content-section">
            <div class="section-title">申请内容</div>
            <div class="content-table">
              <table>
                <tbody>
                  <tr v-for="i in Math.ceil(printData.content.length / 2)" :key="i">
                    <!-- 第一列 -->
                    <td class="label" v-if="printData.content[(i - 1) * 2]">{{ printData.content[(i - 1) * 2].label }}</td>
                    <td class="value" v-if="printData.content[(i - 1) * 2]">
                      <div v-if="printData.content[(i - 1) * 2].type === 'approvalBill'">
                        <div v-for="(subItem, subIndex) in printData.content[(i - 1) * 2].children" :key="subIndex"
                          class="approval-bill-item">
                          <span class="sub-label">{{ subItem.label }}:</span>
                          <span class="sub-value">{{ subItem.value || '--' }}</span>
                        </div>
                      </div>
                      <div v-else-if="Array.isArray(printData.content[(i - 1) * 2].value)">
                        <div v-for="(file, fileIndex) in printData.content[(i - 1) * 2].value" :key="fileIndex"
                          class="file-item">
                          {{ file.name || file.url }}
                        </div>
                      </div>
                      <div v-else-if="printData.content[(i - 1) * 2].type === 'rich_text'" class="rich-text"
                        v-html="printData.content[(i - 1) * 2].value"></div>
                      <span v-else>{{ printData.content[(i - 1) * 2].value || '--' }}</span>
                    </td>
                    <!-- 第二列 -->
                    <td class="label" v-if="printData.content[(i - 1) * 2 + 1]">{{ printData.content[(i - 1) * 2 + 1].label }}</td>
                    <td class="value" v-if="printData.content[(i - 1) * 2 + 1]">
                      <div v-if="printData.content[(i - 1) * 2 + 1].type === 'approvalBill'">
                        <div v-for="(subItem, subIndex) in printData.content[(i - 1) * 2 + 1].children" :key="subIndex"
                          class="approval-bill-item">
                          <span class="sub-label">{{ subItem.label }}:</span>
                          <span class="sub-value">{{ subItem.value || '--' }}</span>
                        </div>
                      </div>
                      <div v-else-if="Array.isArray(printData.content[(i - 1) * 2 + 1].value)">
                        <div v-for="(file, fileIndex) in printData.content[(i - 1) * 2 + 1].value" :key="fileIndex"
                          class="file-item">
                          {{ file.name || file.url }}
                        </div>
                      </div>
                      <div v-else-if="printData.content[(i - 1) * 2 + 1].type === 'rich_text'" class="rich-text"
                        v-html="printData.content[(i - 1) * 2 + 1].value"></div>
                      <span v-else>{{ printData.content[(i - 1) * 2 + 1].value || '--' }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="process-section">
            <div class="section-title">审批流程-{{ getStatusText(printData.status) }}</div>
            <div class="process-table">
              <table>
                <thead>
                  <tr>
                    <th class="label">审批节点</th>
                    <th class="label">处理人</th>
                    <th class="label">操作记录</th>
                  </tr>
                </thead>
                <tbody v-if="printData.users && printData.users.length > 0">

                  <tr v-for="(step, index) in printData.users" :key="index">
                    <td class="value">{{ step.title }}</td>
                    <td class="value">
                      <span v-for="(user, userIndex) in step.users" :key="userIndex" class="approver-item">{{ user.card.name || '--' }}</span>
                    </td>
                    <td class="value">
                      <!-- <div v-if="step.status === 1" class="operation-record">
                        已同意 {{ formatDate(step.update_time) }}
                      </div>
                      <div v-else-if="step.status === 2" class="operation-record">
                        已拒绝 {{ formatDate(step.update_time) }}
                      </div>
                      <div v-else-if="step.status === 0" class="operation-record">
                        审批中
                      </div> -->
                      <div class="operation-record">--</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="footer">
            <div class="print-info">
              <div class="mb10">打印日期: {{ formatDate(new Date()) }}</div>
              <div>打印人: {{ userinfo.name ? userinfo.name : '--' }}</div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" @click="handlePrint">打印</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { getStorageJson } from '@/utils/storage.ts'

export default {
  name: 'PrintPreview',
  props: {

  },
  data() {
    return {
      visible: false,
      printData: {
        content: []
      },
      userinfo: getStorageJson('userInfo', {}),
      enterpriseInfo: getStorageJson('enterprise', {}),
    }
  },

  methods: {
    formatDate(date) {
      if (!date) return ''
      const d = new Date(date)
      const year = d.getFullYear()
      const month = String(d.getMonth() + 1).padStart(2, '0')
      const day = String(d.getDate()).padStart(2, '0')
      const hours = String(d.getHours()).padStart(2, '0')
      const minutes = String(d.getMinutes()).padStart(2, '0')
      const seconds = String(d.getSeconds()).padStart(2, '0')
      return `${year}/${month}/${day} ${hours}:${minutes}:${seconds}`
    },

    openBox(data) {

      // 深拷贝并初始化 content 数组
      this.printData = { ...data, content: [] }

      // 后端部分场景下会把数组序列化成「数字键对象」（PHP 关联数组），这里统一转回数组
      const toArr = (v) => {
        if (Array.isArray(v)) return v
        if (v && typeof v === 'object') return Object.values(v)
        return []
      }
      const rawContent = toArr(data?.content)

      // 统一处理各类字段
      rawContent.forEach(item => {
        // 1. timeFrom 类型：直接展开子项
        if (item.type === 'timeFrom') {
          this.printData.content.push(...toArr(item.value))
          return
        }
        if (item.type === 'approvalBill') {
          toArr(item.value).forEach((row, idx) => {
            const rowArr = toArr(row)
            if (rowArr[0]) rowArr[0].label += `(${item.label}${idx + 1})`
            const timeFromChildren = rowArr
              .filter(cell => cell?.type === 'timeFrom')
              .flatMap(cell => toArr(cell.value))

            this.printData.content.push(...rowArr, ...timeFromChildren)
          })
          return
        }

        if (item.label) {
          this.printData.content.push(item)
        }

      })
      // 延迟显示弹窗，确保 DOM 更新完毕
      this.$nextTick(() => {
        this.visible = true
      })

    },

    getStatusText(status) {
      switch (status) {
        case -1:
          return '已撤销'
        case 0:
          return '审批中'
        case 1:
          return '已通过'
        case 2:
          return '已拒绝'
        default:
          return '--'
      }
    },

    handlePrint() {
      const printContent = this.$refs.printContent
      const originalContents = document.body.innerHTML
      const printCss = `
        <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
          }
          .print-content {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
          }
          .header {
            text-align: center;
            margin-bottom: 20px;
          }
          .title {
            font-size: 20px;
            margin: 0 0 10px 0;
          }
          .company {
            font-size: 16px;
            color: #666;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
          }
          th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 13px;
          }
          .label {
            background-color: #f5f7fa;
            font-weight: bold;
            width: 150px;
          }
          .value {
            width: 250px;
          }
          .section-title {
            font-size: 14px;
            margin: 20px 0 15px 0;
            text-align: center;
            padding-bottom: 5px;
          }
          .footer {
          display: flex;
justify-content: flex-end;
    text-align: left;
    font-size: 13px;
    color: #606266;
  
          }
          .operation-record {
            margin-bottom: 5px;
          }
          .approver-item + .approver-item {
            margin-left: 8px;
          }
          .approval-bill-item {
            margin-bottom: 5px;
          }
          .sub-label {
            font-weight: bold;
            margin-right: 5px;
          }
          .file-item {
            margin-bottom: 3px;
            color: #1890ff;
          }
          .rich-text {
            line-height: 1.6;
          }
        </style>
      `

      document.body.innerHTML = printCss + printContent.outerHTML
      window.print()
      document.body.innerHTML = originalContents
      window.location.reload()
    }
  }
}
</script>

<style scoped>
.print-preview {
  .print-content {
    width: 100%;
    max-height: 600px;
    overflow: auto;

    /* 滚动条样式优化 */
    &::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    &::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }
  }

  .print-inner {
    max-width: 700px;
    margin: 0 auto;
  }

  .el-dialog__body {
    padding-inline: 0;
  }

  .header {
    text-align: center;
    margin-bottom: 20px;

    .title {
      font-size: 20px;
      margin: 0 0 10px 0;
    }

    .company {
      font-size: 16px;
      color: #666;
    }
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }

  th,
  td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
    font-size: 13px;
  }

  .label {
    width: 130px;
  }

  .value {
    width: 200px;
  }

  .section-title {
    font-size: 14px;
    margin: 20px 0 15px 0;
    padding-bottom: 5px;
    text-align: center;
  }

  .footer {
    display: flex;
    justify-content: flex-end;
    text-align: left;
    font-size: 13px;
    color: #606266;
  }

  .operation-record {
    margin-bottom: 5px;
  }

  /* 一个节点有多个处理人时，相邻姓名之间留出间距，避免连成一串 */
  .approver-item + .approver-item {
    margin-left: 8px;
  }

  .approval-bill-item {
    margin-bottom: 5px;
  }

  .sub-label {
    font-weight: bold;
    margin-right: 5px;
  }

  .file-item {
    margin-bottom: 3px;
    color: #1890ff;
  }

  .rich-text {
    line-height: 1.6;
  }

  .print-info {
    font-size: 13px;
  }
}

.dialog-footer {
  display: flex;
  justify-content: center;
}
</style>
