<template>
  <el-dialog class="nodeHyperlinkDialog" title="超链接" v-model="dialogVisible" :width="isMobile ? '90%' : '50%'"
    :top="isMobile ? '20px' : '15vh'">
    <div class="item">
      <span class="name">链接</span>
      <el-input v-model="link" placeholder="请在此输入链接地址" @keyup.stop @keydown.stop
        @blur="handleUrl()">
        <template #prepend>
          <el-select v-model="protocol" style="width: 80px;">
            <el-option label="https" value="https"></el-option>
            <el-option label="http" value="http"></el-option>
            <el-option label="无" value="none"></el-option>
          </el-select>
        </template>
      </el-input>
    </div>
    <div class="item">
      <span class="name">名称</span>
      <el-input v-model="linkTitle" @keyup.stop @keydown.stop></el-input>
    </div>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="cancel">取 消</el-button>
        <el-button type="primary" @click="confirm">确 定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { EventBus } from '@/libs/bus.ts'
import { isMobile } from 'simple-mind-map/src/utils/index'
import { NODE_ACTIVE, SHOW_NODE_LINK } from '../event-constant.ts';


export default {
  name: 'NodeHyperlink',
  data() {
    return {
      dialogVisible: false,
      link: '',
      linkTitle: '',
      activeNodes: [],
      protocol: 'https',
      isMobile: isMobile()
    }
  },
  created() {
    EventBus.$on(NODE_ACTIVE, this.handleNodeActive)
    EventBus.$on(SHOW_NODE_LINK, this.handleShowNodeLink)
  },
  beforeUnmount() {
    EventBus.$off(NODE_ACTIVE, this.handleNodeActive)
    EventBus.$off(SHOW_NODE_LINK, this.handleShowNodeLink)
  },
  methods: {
    handleNodeActive(...args) {
      this.activeNodes = [...args[1]]
      if (this.activeNodes.length > 0) {
        let firstNode = this.activeNodes[0]
        this.link = firstNode.getData('hyperlink') || ''
        this.handleUrl(true)
        this.linkTitle = firstNode.getData('hyperlinkTitle') || ''
      } else {
        this.link = ''
        this.linkTitle = ''
      }
    },

    removeProtocol(url) {
      return url.replace(/^https?:\/\//, '')
    },

    handleUrl(setProtocolNoneIfNotExist) {
      const res = this.link.match(/^(https?):\/\//)
      if (res && res[1]) {
        this.protocol = res[1]
      } else if (!this.link) {
        this.protocol = 'https'
      } else if (setProtocolNoneIfNotExist) {
        this.protocol = 'none'
      }
      this.link = this.removeProtocol(this.link)
    },

    handleShowNodeLink() {
      this.dialogVisible = true
    },


    cancel() {
      this.dialogVisible = false
    },

    confirm() {
      this.activeNodes.forEach(node => {
        node.setHyperlink(
          (this.protocol === 'none' ? '' : this.protocol + '://') + this.link,
          this.linkTitle
        )
        this.cancel()
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.nodeHyperlinkDialog {
  .item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;

    .name {
      display: block;
      width: 50px;
    }
  }
}
</style>
