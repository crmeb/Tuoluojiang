<!--记事本-->
<template>
  <div class="divBox">
    <div class="flex">
      <!--左侧菜单：sidebarVisible 控制收起/展开，分隔条可拖拽调宽-->
      <div
        class="left-box"
        :class="{ 'is-collapsed': !sidebarVisible, 'is-resizing': resizing }"
        :style="leftBoxStyle"
      >
        <menuTree
          ref="menuTree"
          @createNote="addCreate"
          @frameId="getFrameId"
          @onRecently="getRecentlyList"
          @selectNote="selectNote"
          @noteAction="handleNoteAction"
        />
      </div>
      <!--拖拽调宽分隔条-->
      <div
        v-show="sidebarVisible"
        class="sidebar-resizer"
        :class="{ 'is-resizing': resizing }"
        @mousedown="startResize"
      />
      <!--右侧内容-->
      <div class="right-box">
        <el-card v-loading="loadingPage">
          <div v-if="!content" class="noneDefault">
            <el-tooltip :content="fullscreen ? '退出全屏' : '全屏展示'" effect="dark" placement="left">
              <span class="fullscreen-btn btn2" @click="handleFullscreen">
                <svg
                  viewBox="0 0 24 24"
                  width="16"
                  height="16"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path :d="fullscreenPath" />
                </svg>
              </span>
            </el-tooltip>
            <el-tooltip content="关闭" effect="dark" placement="left">
              <span class="el-icon-close btn1" @click="handleClose"></span>
            </el-tooltip>

            <img alt="" class="img" src="../../../assets/images/nodedef1.png" />
            <div>暂无笔记~</div>
          </div>

          <div v-else class="content">
            <div class="article-top">
              <!-- 左侧信息区：路径行 + 时间/倒计时行 -->
              <div class="head-main">
                <div class="top-row">
                  <!-- 左侧树形菜单收起/展开开关：展开中显示收起图标，收起后显示展开图标 -->
                  <el-tooltip :content="sidebarVisible ? '收起菜单' : '展开菜单'" effect="dark" placement="bottom">
                    <span class="fold-icon" @click="sidebarVisible = !sidebarVisible">
                    <!-- 菜单展开中：点击收起 -->
                    <svg v-if="sidebarVisible" class="fold-svg" viewBox="0 0 16 16" width="16" height="16">
                      <rect x="1.75" y="2.75" width="12.5" height="10.5" rx="1.75" fill="none" stroke="currentColor" stroke-width="1.5" />
                      <line x1="5.75" y1="2.75" x2="5.75" y2="13.25" stroke="currentColor" stroke-width="1.5" />
                      <path d="M11 6.25 9.25 8l1.75 1.75" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <!-- 菜单已收起：点击展开 -->
                    <svg v-else class="fold-svg" viewBox="0 0 16 16" width="16" height="16">
                      <rect x="1.75" y="2.75" width="12.5" height="10.5" rx="1.75" fill="none" stroke="currentColor" stroke-width="1.5" />
                      <rect x="3.4" y="4.4" width="3.9" height="7.2" rx="0.8" fill="currentColor" />
                    </svg>
                    </span>
                  </el-tooltip>
                  <el-breadcrumb separator=">" class="crumb">
                    <el-breadcrumb-item>全部笔记</el-breadcrumb-item>
                    <el-breadcrumb-item v-for="(name, i) in breadcrumbs" :key="i">{{ name }}</el-breadcrumb-item>
                    <el-breadcrumb-item v-if="title">{{ title }}</el-breadcrumb-item>
                  </el-breadcrumb>
                </div>
                <!-- 第二行：最后修改时间 + 自动保存倒计时（悬停说明保存方式） -->
                <div class="meta-row">
                  <span v-if="updatedAt" class="update-time">最后修改时间：{{ formatUpdateTime(updatedAt) }}</span>
                  <el-tooltip v-if="showSaveTip" :content="autoSaveTip" effect="dark" placement="bottom">
                    <span class="auto-save-tip" :class="{ 'is-failed': autoSaveFailed }">
                      <i class="save-dot" :class="saveDotClass" />{{ autoSaveText }}
                    </span>
                  </el-tooltip>
                </div>
              </div>
              <!-- 右侧操作区：全屏切换 + 关闭（随整个头部块垂直居中，四周留白一致） -->
              <div class="savebutton">
                <el-tooltip :content="fullscreen ? '退出全屏' : '全屏展示'" effect="dark" placement="top">
                  <span class="fullscreen-btn" @click="handleFullscreen">
                    <svg
                      viewBox="0 0 24 24"
                      width="16"
                      height="16"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path :d="fullscreenPath" />
                    </svg>
                  </span>
                </el-tooltip>
                <el-tooltip content="关闭" effect="dark" placement="top">
                  <span class="el-icon-close" @click="handleClose"></span>
                </el-tooltip>
              </div>
            </div>
            <div class="edit-content">
              <!--记事本专用编辑器：目录大纲 + 富文本，目录支持添加/拖拽/收起并同步正文-->
              <notepad-editor
                :content="content"
                :title="title"
                height="calc(100vh - 226px)"
                title-placeholder="填写记事本标题"
                @input="handleEditorInput"
                @update:title="handleTitleInput"
                @title-change="edit"
              />
            </div>
          </div>
        </el-card>
      </div>
    </div>
    <!-- 移动文件夹弹窗-->
    <move-dialog ref="moveDialog" :move-data="moveData" @handlerMove="handlerMove"></move-dialog>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue'
import { modalSure } from '@/libs/public.ts'
  import { memorialListApi, memorialSaveApi, memorialEditApi, memorialDeleteApi, memorialInfoApi } from '@/api/user.ts'
  import fSpan from '@/views/business/components/formSetting/components/form-create-designer/src/config/rule/span'

  // 本地缓存 key：记录上次关闭时打开的笔记 id，下次进入记事本自动恢复
  const LAST_OPEN_NOTE_KEY = 'memorandum_last_open_note'
  // 本地缓存 key：记录左侧目录菜单拖拽宽度，刷新后保持
  const SIDEBAR_WIDTH_KEY = 'memorandum_sidebar_width'
  const SIDEBAR_WIDTH_MIN = 200
  const SIDEBAR_WIDTH_MAX = 480
  // 读取缓存的菜单宽度：超出可拖范围或未设置时回退默认值
  function initSidebarWidth() {
    const saved = Number(localStorage.getItem(SIDEBAR_WIDTH_KEY))
    if (saved >= SIDEBAR_WIDTH_MIN && saved <= SIDEBAR_WIDTH_MAX) return saved
    return 260
  }

// 自动保存周期（秒）：倒计时归零即自动保存，保存完成后重新计时
const AUTO_SAVE_SECONDS = 60

// 自动保存请求超时（毫秒）：比全局 60s 短，避免请求挂住时长时间占用「保存中」并阻塞后续保存
const AUTO_SAVE_TIMEOUT = 30000

export default {
  emits: ['toggleFullscreen', 'handleClose'],
  name: 'Notepad',
  components: {
    fSpan,
    menuTree: defineAsyncComponent(() => import('./components/menuTree')),
    notepadEditor: defineAsyncComponent(() => import('./components/notepad-editor')),
    moveDialog: defineAsyncComponent(() => import('./components/moveDialog'))
  },
  props: {
    style1: {
      type: String,
      default: ''
    },
    // 抽屉是否处于全屏展示（由 layout 的记事本抽屉传入，仅用于图标状态）
    fullscreen: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    // 全屏图标路径：非全屏为四角外扩（进入全屏），全屏中为四角内收（退出全屏）
    fullscreenPath() {
      return this.fullscreen
        ? 'M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3'
        : 'M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3'
    },
    // 左侧菜单宽度：收起时归零，展开时使用拖拽记忆宽度
    leftBoxStyle() {
      return { width: this.sidebarVisible ? `${this.sidebarWidth}px` : '0' }
    },
    // 自动保存提示文案：保存中 / 失败 / 待保存倒计时 / 已保存（无改动时不显示倒计时）
    autoSaveText() {
      if (this.autoSaving) return '自动保存中'
      if (this.autoSaveFailed) return '自动保存失败，编辑后重试'
      if (!this.hasChanges) return '已保存'
      return `${this.autoSaveCountdown}s后自动保存`
    },
    // 是否展示保存状态：新打开、本次会话还没编辑过时不展示
    showSaveTip() {
      return this.hasEdited || this.autoSaving || this.autoSaveFailed
    },
    // 状态圆点：红=有未保存改动/保存中/失败，绿=已保存
    saveDotClass() {
      return this.autoSaveFailed || this.autoSaving || this.hasChanges ? 'is-dirty' : 'is-saved'
    },
    // 悬停提示：失败时带上具体原因（自动保存是静默的，否则用户不知道为何没存上）
    autoSaveTip() {
      if (this.autoSaveFailed && this.autoSaveErrorMsg) return `自动保存失败：${this.autoSaveErrorMsg}`
      if (!this.hasChanges) return '当前内容已保存；修改后每 1 分钟自动保存一次，也可按 Ctrl+S 手动保存'
      return '每 1 分钟自动保存一次；也可按 Ctrl+S 手动保存；切换笔记或关闭前会提示是否保存'
    }
  },
  data() {
    return {
      loadingPage: false,
      sidebarVisible: true, // 左侧树形菜单显隐
      sidebarWidth: initSidebarWidth(), // 左侧菜单宽度（分隔条可拖拽调整，200~480，本地持久化）
      resizing: false, // 是否正在拖拽调宽
      resizeStartX: 0,
      resizeStartWidth: 0,
      where: {
        pid: ''
      },
      breadcrumbs: [], // 当前笔记的文件夹路径
      updatedAt: '', // 当前笔记最后修改时间（面包屑下方展示）
      baseUpdatedAt: '', // 服务端返回的 updated_at：作为乐观锁基准，期间被其他端改过则拒绝覆盖
      saved: { title: '', content: '' }, // 当前笔记已保存快照，切换前做脏检查用
      id: null, // 文件id
      loadedId: null, // 已完整加载内容的笔记id：仅该笔记允许自动保存
      notePid: null, // 已打开笔记所属文件夹：保存只用它，避免被「新建落点」污染导致笔记被搬家
      loadedAt: 0, // 详情加载完成时刻：屏蔽编辑器回填时的瞬时序列化差异
      calibrateTimer: null, // 回填基线校准定时器：编辑器规范化回写后刷新保存基线，避免「未编辑却提示保存」
      content: '',
      title: '',
      memorandum: [],
      saveLoading: false,
      tabButton: false,
      moveData: {},
      timer: null,
      autoSaveCountdown: AUTO_SAVE_SECONDS, // 距下次自动保存的剩余秒数（由截止时间推导，后台标签页被节流也不会走偏）
      nextSaveAt: 0, // 下次自动保存的截止时间戳
      autoSaving: false, // 是否正在自动保存（提示文案切为「自动保存中」）
      autoSaveFailed: false, // 自动保存失败：提示区转失败态并暂停重试，用户再次编辑后恢复
      autoSaveErrorMsg: '', // 自动保存失败原因：用于悬停提示，静默保存下也能知道为何没存上
      composing: false, // 输入法组词中：组词期间不触发保存，避免存下半成品
      hasChanges: false, // 廉价改动标记：编辑器/标题有输入即置位，保存或加载后复位；用于秒级倒计时与提示文案
      hasEdited: false, // 本次打开是否编辑过：未编辑（刚打开）不展示保存状态
      savingPromise: null, // 在途保存：后续保存串行等待，避免并发请求触发乐观锁误判
      autoSaveTimer: null // 自动保存倒计时定时器
    }
  },
  mounted() {
    this.createdEvent()
    this.getRecentlyList()
    // 注意：这里不启动自动保存倒计时——打开页面/笔记不计时，等用户真正改动后再懒启动
    // 输入法组词期间不允许自动保存，避免把半成品写库
    document.addEventListener('compositionstart', this.onCompositionStart, true)
    document.addEventListener('compositionend', this.onCompositionEnd, true)
  },
  beforeUnmount() {
    this.handleRemove()
    this.stopAutoSave()
    document.removeEventListener('compositionstart', this.onCompositionStart, true)
    document.removeEventListener('compositionend', this.onCompositionEnd, true)
    document.removeEventListener('mousemove', this.onResizing)
    document.removeEventListener('mouseup', this.stopResize)
  },
  methods: {
    onCompositionStart() {
      this.composing = true
    },
    onCompositionEnd() {
      this.composing = false
    },
    // 全屏 / 退出全屏：交给抽屉容器切换宽度
    handleFullscreen() {
      this.$emit('toggleFullscreen')
    },
    // 关闭：有未保存修改时先确认是否保存
    handleClose() {
      if (!this.id || !this.isDirty()) {
        this.$emit('handleClose')
        return
      }
      this.confirmUnsaved('关闭').then((ok) => {
        if (ok) this.$emit('handleClose')
      })
    },
    // 分隔条拖拽调宽：按下后监听全局 mousemove/mouseup
    startResize(event) {
      event.preventDefault()
      this.resizing = true
      this.resizeStartX = event.clientX
      this.resizeStartWidth = this.sidebarWidth
      document.addEventListener('mousemove', this.onResizing)
      document.addEventListener('mouseup', this.stopResize)
    },
    onResizing(event) {
      if (!this.resizing) return
      event.preventDefault()
      const width = this.resizeStartWidth + (event.clientX - this.resizeStartX)
      this.sidebarWidth = Math.min(480, Math.max(200, width))
    },
    stopResize() {
      this.resizing = false
      // 持久化拖拽后的宽度：刷新浏览器后保持
      localStorage.setItem(SIDEBAR_WIDTH_KEY, String(this.sidebarWidth))
      document.removeEventListener('mousemove', this.onResizing)
      document.removeEventListener('mouseup', this.stopResize)
    },
    // 最近使用：打开最近第一条笔记（最近列表已并入左侧树，由 menuTree 维护数据）
    // 进入页面：优先恢复上次关闭时打开的笔记，不存在（已删除）则回退最近第一条
    getRecentlyList() {
      const tree = this.$refs.menuTree
      const recent = (tree && tree.recentlyData) || []
      const lastId = Number(localStorage.getItem(LAST_OPEN_NOTE_KEY)) || null
      const target = (lastId && recent.find((n) => n.id === lastId)) || recent[0]
      if (target) {
        this.openNote(target, this.getBreadcrumbs(target.pid))
        tree.setCurrentId(target.id)
      }
    },
    // 文件夹点击：仅记录新建笔记的落点，笔记列表职责已并入左侧树
    getFrameId(e) {
      this.where.pid = e
    },
    handleRemove() {
      document.removeEventListener('keydown', this.handleEvent) // 在页面销毁的时候记得解除
    },
    createdEvent() {
      document.addEventListener('keydown', this.handleEvent)
    },
    handleEvent(event) {
      if (event.ctrlKey || event.metaKey) {
        if (event.code === 'KeyS') {
          event.preventDefault()
          // 在这里写保存需要执行的逻辑
          this.edit()
        }
      }
    },
    // 左侧树点击打开笔记（带面包屑）
    selectNote(note, breadcrumbs) {
      this.openNote(note, breadcrumbs)
    },
    // 打开笔记：有未保存修改时先确认，避免切换静默丢稿
    openNote(item, breadcrumbs = this.breadcrumbs) {
      if (this.id === item.id) {
        return
      }
      const go = () => this.doSelectItem(item, breadcrumbs)
      if (!this.isDirty()) {
        go()
        return
      }
      this.confirmUnsaved('切换').then((ok) => {
        if (ok) go()
      })
    },
    /*
      未保存修改的统一确认（切换笔记 / 关闭共用）
      resolve(true) 可以继续（已保存或选择放弃修改）；resolve(false) 留在当前笔记
    */
    async confirmUnsaved(action) {
      try {
        await this.$confirm(`当前笔记尚未保存，${action}后将丢失未保存的修改`, '提示', {
          confirmButtonText: `保存并${action}`,
          cancelButtonText: `不保存${action}`,
          distinguishCancelAndClose: true,
          type: 'warning'
        })
      } catch (type) {
        // 「不保存」继续执行；点弹窗右上角关闭则留在当前笔记
        return type === 'cancel'
      }
      try {
        await this.saveCurrent()
        await this.refreshAfterSave()
        return true
      } catch (err) {
        // 业务错误已由 request.js 弹过提示，避免重复
        if (!err || !err.handled) this.$message.error((err && err.message) || '保存失败')
        return false
      }
    },
    // 执行切换：更新选中态并按需请求笔记详情（列表不再携带 content）
    doSelectItem(item, breadcrumbs) {
      this.id = item.id
      // 上一篇笔记的基线校准定时器作废
      if (this.calibrateTimer) clearTimeout(this.calibrateTimer)
      // 记录本次打开的笔记 id：下次进入记事本自动恢复
      localStorage.setItem(LAST_OPEN_NOTE_KEY, String(item.id))
      // pid=0 是根目录（有效值），不能用 || 回退：否则根目录笔记会沿用上一个文件夹的 pid，
      // 后续保存把它当落点提交，导致笔记归属被静默改写
      if (item.pid !== undefined && item.pid !== null) {
        this.where.pid = item.pid
        this.notePid = item.pid
      }
      this.breadcrumbs = breadcrumbs || []
      // 组词状态可能残留（切换时未必触发 compositionend），重置避免新笔记一直不保存
      this.composing = false
      // 同步左侧目录树：选中该笔记并自动展开其所在目录链
      if (this.$refs.menuTree) this.$refs.menuTree.setCurrentId(item.id)
      memorialInfoApi(item.id)
        .then((res) => {
          // 期间用户已切到其他笔记：丢弃本次结果，避免内容串台
          if (this.id !== item.id) return
          const info = res.data || {}
          this.content = info.content || ''
          this.title = info.title || ''
          this.updatedAt = info.updated_at || ''
          // 同上：详情返回的 pid 含 0（根目录），仅在有值时覆盖
          if (info.pid !== undefined && info.pid !== null) {
            this.where.pid = info.pid
            this.notePid = info.pid
          }
          this.saved = { title: this.title, content: this.content }
          this.baseUpdatedAt = info.updated_at || ''
          this.loadedAt = Date.now()
          // 新加载的笔记还没有改动：停掉倒计时，且不展示保存状态（刚打开时提示区留空）
          this.markSaved()
          this.hasEdited = false
          // 内容已就位，标记为可自动保存的笔记（切换过程中该值仍指向上一篇，自动保存会被拦下）
          this.loadedId = item.id
          // 编辑器回填会把后端原文做规范化序列化（v-model 回写 content），
          // 稳定后校准保存基线：有差异（规范化或用户已有输入）先静默保存落库，内容不丢失
          if (this.calibrateTimer) clearTimeout(this.calibrateTimer)
          this.calibrateTimer = setTimeout(() => {
            if (this.id !== item.id || !this.isDirty()) return
            this.saveCurrent(true).catch(() => {})
          }, 1500)
        })
        .catch(() => {
          if (this.id !== item.id) return
          this.$message.error('笔记内容加载失败')
        })
    },
    /*
      是否有未保存修改
      - 正文用「结构化签名」比对：保留标签与格式（加粗/标题级别/对齐/颜色/列表等），
        忽略属性顺序、空白与编辑器回填时补的 data-* 属性，避免纯格式改动被判为无变化而静默丢失
      - 详情刚加载完的 1s 内视为编辑器回填抖动，不计为改动
    */
    isDirty() {
      if (!this.id) return false
      if (Date.now() - this.loadedAt < 1000) return false
      const titleChanged = String(this.title || '').trim() !== String(this.saved.title || '').trim()
      return titleChanged || this.contentSignature(this.content) !== this.contentSignature(this.saved.content)
    },
    // 富文本结构化签名：标签名 + 关键格式属性 + 文本，忽略属性顺序与编辑器自加属性
    contentSignature(html) {
      const doc = new DOMParser().parseFromString(String(html || ''), 'text/html')
      const parts = []
      const walk = (node) => {
        node.childNodes.forEach((child) => {
          if (child.nodeType === 3) {
            const text = child.textContent.replace(/\s+/g, '')
            if (text) parts.push('t:' + text)
            return
          }
          if (child.nodeType !== 1) return
          const attrs = []
          const style = (child.getAttribute('style') || '')
            .split(';')
            .map((item) => item.replace(/\s+/g, ''))
            .filter(Boolean)
            .sort()
            .join(';')
          if (style) attrs.push('style=' + style)
          ;['colspan', 'rowspan', 'src', 'alt'].forEach((name) => {
            const val = child.getAttribute(name)
            if (val) attrs.push(name + '=' + val)
          })
          parts.push('n:' + child.tagName.toLowerCase() + (attrs.length ? '[' + attrs.join('|') + ']' : ''))
          walk(child)
        })
      }
      walk(doc.body)
      return parts.join('>')
    },
    // 业务错误：request.js 已按后端 tips 弹过提示，标记 handled 供调用方避免重复提示
    saveError(res) {
      const err = new Error((res && res.message) || '保存失败')
      err.handled = true
      return err
    },
    // 保存当前笔记，复用 edit 的接口分支，返回 Promise 供切换流程等待
    // silent=true 时不弹「保存成功」提示（自动保存专用，避免每分钟打扰）
    saveCurrent(silent = false) {
      // 先等上一次保存结束再取基准：并发请求会让后到者因基准过期被误判为冲突
      return this.waitPrevSave().then(() => this.doSaveCurrent(silent))
    },
    // 实际发起保存（已被 waitPrevSave 串行化）
    doSaveCurrent(silent) {
      const id = this.id
      // 归属只认已打开笔记的文件夹（notePid）：where.pid 是「新建落点」，点左侧文件夹就会变，
      // 用它提交会把当前笔记静默搬到那个文件夹
      const data = { title: this.title, content: this.content, pid: this.notePid || 0 }
      // 乐观锁基准：带上读取时服务端返回的 updated_at，期间被其他端改过则由后端拒绝覆盖
      if (this.baseUpdatedAt) data.updated_at = this.baseUpdatedAt
      // 快照按发起时的内容记录：自动保存期间可能已切换笔记，避免回写串台
      const snapshot = { title: data.title, content: data.content }
      this.saveLoading = true
      const options = { silent }
      // 自动保存单独设较短超时（未传时用全局配置，避免把全局超时覆盖成 undefined）
      if (silent) options.timeout = AUTO_SAVE_TIMEOUT
      const request = id ? memorialEditApi(id, data, options) : memorialSaveApi(data)
      const promise = request
        .then((res) => {
          this.saveLoading = false
          // request.js 对业务错误（status 400）也会 resolve，这里显式区分，避免把失败当成功
          if (!res || res.status !== 200) throw this.saveError(res)
          // 保存成功即重新开始 60 秒计时（切换/关闭流程保存后同样适用），提示区与真实节奏保持一致
          this.resetAutoSaveCountdown()
          // 保存期间已切到其他笔记：不回写快照与时间，避免新笔记被误判为「无改动」
          if (this.id !== id) return
          this.saved = snapshot
          // 已落库：停掉倒计时，提示区回到「已保存」
          this.markSaved()
          // 同步服务端写入的更新时间：既是展示值，也是下一轮乐观锁基准
          this.baseUpdatedAt = (res.data && res.data.updated_at) || ''
          this.updatedAt = this.formatDateTime(new Date())
        })
        .catch((err) => {
          this.saveLoading = false
          throw err
        })
      return this.trackSave(promise)
    },
    // 保存后仅增量同步树中该笔记标题，不重建整树（切换笔记时左侧目录保持稳定）
    refreshAfterSave() {
      if (this.id) this.$refs.menuTree.updateNoteName(this.id, this.title)
    },
    // 启动自动保存倒计时：全局周期，保存完成或跳过后重新计时
    startAutoSave() {
      this.stopAutoSave()
      this.resetAutoSaveCountdown()
      this.autoSaveTimer = setInterval(() => {
        // 无改动不走倒计时：保持完整 60 秒窗口，等用户真正改动后再从头计时
        if (!this.hasChanges) {
          this.resetAutoSaveCountdown()
          return
        }
        // 剩余秒数由截止时间推导：后台标签页定时器被浏览器节流时，秒数也不会走偏
        const remain = Math.max(0, Math.ceil((this.nextSaveAt - Date.now()) / 1000))
        this.autoSaveCountdown = remain
        if (remain > 0) return
        // 保存中不重复触发；秒数仍按截止时间继续走，请求结束后统一重置
        if (this.autoSaving) return
        this.autoSave()
      }, 1000)
    },
    // 重置倒计时：统一走这里，避免各处直接改秒数导致与截止时间不同步
    resetAutoSaveCountdown() {
      this.nextSaveAt = Date.now() + AUTO_SAVE_SECONDS * 1000
      this.autoSaveCountdown = AUTO_SAVE_SECONDS
    },
    stopAutoSave() {
      if (this.autoSaveTimer) {
        clearInterval(this.autoSaveTimer)
        this.autoSaveTimer = null
      }
    },
    /*
      保存串行化：同一客户端的并发请求会让后到者因乐观锁基准过期被误判为冲突
      - waitPrevSave：本次保存前先等上一次结束（切换/关闭流程用，保证不丢改动）
      - trackSave：登记在途请求，供后续等待
    */
    async waitPrevSave() {
      const prev = this.savingPromise
      if (!prev) return
      try {
        await prev
      } catch (err) {
        // 上一次保存失败不影响本次
      }
    },
    trackSave(promise) {
      this.savingPromise = promise
      const clear = () => {
        if (this.savingPromise === promise) this.savingPromise = null
      }
      promise.then(clear, clear)
      return promise
    },
    // 自动保存：只保存「当前已完整加载」的笔记，且内容确有变化时才提交
    async autoSave() {
      // 未打开笔记、或切换过程中（id 已变、内容未到，loadedId 仍指上一篇）都不写库，
      // 否则会把上一篇的正文存进新笔记
      if (!this.id || this.loadedId !== this.id) {
        this.resetAutoSaveCountdown()
        return
      }
      // 内容与已保存快照一致（例如改动被撤销回原样）：停掉倒计时，提示区回到「已保存」
      if (!this.isDirty()) {
        this.markSaved()
        return
      }
      /*
        本轮不提交、仅重新计时的情形：
        - 输入法组词中：避免把半成品写库
        - 已有保存在途（手动/切换）：等它结束，避免并发请求触发乐观锁误判
        - 上一轮失败未恢复：等用户再次编辑
        - 标题为空：不允许写库
      */
      if (this.composing || this.saveLoading || this.autoSaveFailed || this.title === '') {
        this.resetAutoSaveCountdown()
        return
      }
      this.autoSaving = true
      try {
        // 静默保存：成功不弹提示，仅由倒计时区展示「自动保存中」
        await this.saveCurrent(true)
        this.refreshAfterSave()
        this.autoSaveFailed = false
        this.autoSaveErrorMsg = ''
      } catch (err) {
        // 失败不打断编辑：提示区转失败态并暂停重试，等用户再次编辑后恢复（原因放进悬停提示）
        this.autoSaveFailed = true
        this.autoSaveErrorMsg = (err && err.message) || ''
      } finally {
        this.autoSaving = false
        this.resetAutoSaveCountdown()
      }
    },
    // 创建内容
    addCreate(data, Flag = false) {
      if (Flag) {
        this.saveLoading = true
      }
      memorialSaveApi(data)
        .then(async () => {
          if (Flag) {
            this.saveLoading = false
          }
          await this.$refs.menuTree.menuList()
          // 保存接口不返回新笔记 id，按落点取列表第一条定位（与原逻辑一致）
          const listRes = await memorialListApi({ pid: data.pid, page: 1, limit: 10 })
          const first = (listRes.data.list || [])[0]
          if (first) {
            this.doSelectItem(first, this.getBreadcrumbs(first.pid))
            this.$refs.menuTree.setCurrentId(first.id)
          }
        })
        .catch((err) => {
          if (Flag) {
            this.saveLoading = false
          }
          this.$message.error(err.message)
        })
    },
    // 修改
    edit(val) {
      // 切换加载窗口内（id 已变、正文未到）不允许保存，否则会把上一篇正文写进新笔记
      if (!this.id || this.loadedId !== this.id) return
      // 已有保存在途：跳过本次，避免并发请求因乐观锁基准过期被误判为冲突
      if (this.saveLoading) return
      if (this.title == '') {
        this.$message.error('记事本的标题不能为空')
      } else if (this.content == '') {
        this.$message.error('记事本的内容不能为空')
      } else {
        var data = {
          title: this.title,
          content: this.content,
          // 归属只认已打开笔记的文件夹，见 saveCurrent 说明
          pid: this.notePid || 0
        }
        if (this.baseUpdatedAt) data.updated_at = this.baseUpdatedAt
        // 编辑
        this.memorialEdit(this.id, data, val)
      }
    },
    // 编辑
    memorialEdit(id, data, val) {
      this.saveLoading = true
      const promise = memorialEditApi(id, data)
        .then(async (res) => {
          // request.js 对业务错误（status 400）也会 resolve，这里显式区分，避免把失败当成功
          if (!res || res.status !== 200) throw this.saveError(res)
          // 手动保存成功同样重新开始 60 秒计时，避免提示区还停在旧的倒计时上
          this.resetAutoSaveCountdown()
          // 成功提示由 request.js 统一弹出（后端 tips →「保存成功」），这里不再重复提示
          this.saveLoading = false
          this.saved = { title: this.title, content: this.content }
          // 已落库：停掉倒计时，提示区回到「已保存」
          this.markSaved()
          // 同步服务端写入的更新时间：既是展示值，也是下一轮乐观锁基准
          this.baseUpdatedAt = (res.data && res.data.updated_at) || ''
          this.updatedAt = this.formatDateTime(new Date())
          // 增量同步左侧树中该笔记标题，不重建整树（内容按需加载，无需同步正文）
          this.$refs.menuTree.updateNoteName(id, this.title)
        })
        .catch((err) => {
          this.saveLoading = false
          // 业务错误已由 request.js 弹过提示，避免重复
          if (!err || !err.handled) this.$message.error((err && err.message) || '保存失败')
        })
      // 登记在途请求：切换/关闭流程的保存会等它结束，避免并发触发乐观锁误判
      return this.trackSave(promise)
    },
    // 最后修改时间展示：今天/昨天/前天用相对天，同年显示月日，跨年显示完整日期
    formatUpdateTime(time) {
      if (!time) return ''
      // 兼容 Safari：Y-m-d H:i:s 需转斜杠再解析
      const date = new Date(String(time).replace(/-/g, '/'))
      if (Number.isNaN(date.getTime())) return ''
      const now = new Date()
      const dayStart = (val) => new Date(val.getFullYear(), val.getMonth(), val.getDate()).getTime()
      const diffDay = Math.round((dayStart(now) - dayStart(date)) / 86400000)
      const hms = this.formatHms(date)
      if (diffDay <= 0) return '今天 ' + hms
      if (diffDay === 1) return '昨天 ' + hms
      if (diffDay === 2) return '前天 ' + hms
      const md = this.formatNum(date.getMonth() + 1) + '-' + this.formatNum(date.getDate())
      if (date.getFullYear() === now.getFullYear()) return md + ' ' + hms
      return date.getFullYear() + '-' + md + ' ' + hms
    },
    // Date 格式化为 Y-m-d H:i:s（保存成功后本地即时刷新展示用）
    formatDateTime(date) {
      return (
        date.getFullYear() +
        '-' +
        this.formatNum(date.getMonth() + 1) +
        '-' +
        this.formatNum(date.getDate()) +
        ' ' +
        this.formatHms(date)
      )
    },
    formatHms(date) {
      return this.formatNum(date.getHours()) + ':' + this.formatNum(date.getMinutes()) + ':' + this.formatNum(date.getSeconds())
    },
    formatNum(num) {
      return num < 10 ? '0' + num : '' + num
    },
    // 标记「有改动」：首次改动时才启动倒计时（打开页面/笔记不会自动计时）
    markChanged() {
      this.hasChanges = true
      this.hasEdited = true
      if (!this.autoSaveTimer) this.startAutoSave()
    },
    // 标记「已无改动」：停掉倒计时，提示区回到「已保存」
    markSaved() {
      this.hasChanges = false
      this.stopAutoSave()
    },
    // 编辑器正文输入回调（未打开笔记时忽略，避免空编辑干扰状态）
    handleEditorInput(e) {
      if (!this.id) return false
      this.content = e
      /*
        回声判定：加载/切换笔记时编辑器会把回填的内容再 emit 回来，这不是用户改动。
        - 与已保存快照完全一致：直接判为回声
        - 加载后 1s 内：编辑器可能重新序列化出细微差异，一并按回声处理
      */
      const isEcho = e === this.saved.content || Date.now() - this.loadedAt < 1000
      if (!isEcho) this.markChanged()
      // 上一轮自动保存失败后暂停重试，用户再次编辑即恢复
      if (this.autoSaveFailed) {
        this.autoSaveFailed = false
        this.autoSaveErrorMsg = ''
      }
    },
    // 标题输入：值确实变化才算改动（避免失焦回传同值等噪声置位）
    handleTitleInput(val) {
      this.title = val
      if (this.id && val !== this.saved.title) this.markChanged()
    },
    // 删除笔记（左侧树入口），删的是当前打开的笔记时清空编辑区
    deleteNote(note) {
      modalSure('你确定要删除这条内容吗').then(async () => {
        await memorialDeleteApi(note.id)
        // 刷新左侧树（最近模式下同步刷新最近列表）
        this.$refs.menuTree.menuList()
        if (note.id === this.id) {
          this.id = null
          this.loadedId = null
          this.notePid = null
          this.content = ''
          this.title = ''
          this.breadcrumbs = []
          this.updatedAt = ''
          this.baseUpdatedAt = ''
          // 没有打开的笔记就不必再跑倒计时，打开新笔记并改动后会懒启动
          this.markSaved()
          this.hasEdited = false
        }
      })
    },
    // 左侧树笔记操作入口
    handleNoteAction({ action, note }) {
      if (action === 'move') {
        this.openMoveDialog(note)
      } else if (action === 'delete') {
        this.deleteNote(note)
      }
    },
    /*
      移动弹窗：列表接口不再返回正文，直接用树节点提交会把 content 当空串写库、正文被清空，
      所以先取详情补全；移动的若是当前打开的笔记，用编辑器里的最新内容，避免覆盖未保存的编辑
    */
    async openMoveDialog(note) {
      let detail = {}
      try {
        const res = await memorialInfoApi(note.id)
        detail = res.data || {}
      } catch (err) {
        this.$message.error('笔记内容加载失败，暂不能移动')
        return
      }
      const isCurrent = note.id === this.id
      this.moveData.data = {
        ...note,
        ...detail,
        title: isCurrent ? this.title : detail.title || note.title,
        content: isCurrent ? this.content : detail.content || ''
      }
      this.$refs.moveDialog.handleOpen()
    },
    getBreadcrumbs(pid) {
      const tree = this.$refs.menuTree
      return tree && tree.getBreadcrumbByPid ? tree.getBreadcrumbByPid(pid) : []
    },
    async handlerMove() {
      // 移动后刷新左侧树（笔记归属变化；最近模式下同步刷新最近列表）
      await this.$refs.menuTree.menuList()
      if (!this.id) return
      // 重新取详情同步归属：否则后续保存仍按旧文件夹提交，会把笔记又移回去
      try {
        const res = await memorialInfoApi(this.id)
        const info = res.data || {}
        if (this.id !== info.id) return
        if (info.pid !== undefined && info.pid !== null) {
          this.notePid = info.pid
          this.where.pid = info.pid
        }
        this.baseUpdatedAt = info.updated_at || ''
      } catch (err) {
        // 详情失败时保持原值，不猜测归属
      }
      // 移动的是当前打开的笔记：移动弹窗已把编辑器里的标题/正文一并写库，
      // 这里同步快照并结束倒计时，否则会留下一个「永远存不出东西」的假倒计时
      if (this.moveData.data && this.moveData.data.id === this.id) {
        this.saved = { title: this.title, content: this.content }
        this.markSaved()
      }
      this.breadcrumbs = this.getBreadcrumbs(this.notePid)
    }
  }
}
</script>

<style lang="scss" scoped>
.divBox {
  background-color: #f7f8fa;
  border: none;
}
.el-icon-close {
  font-size: 15px;
  cursor: pointer;
  margin-left: 10px;
  margin-right: 10px;
}

.left-box {
  height: calc(100% - 40px);
  overflow: hidden;
  transition: width 0.28s ease;
  flex-shrink: 0;

  // 拖拽调宽过程中禁用过渡，宽度实时跟随鼠标
  &.is-resizing {
    transition: none;
  }

  :deep(.tree-box) {
    // 最小内容宽度：拖窄到下限时内部元素仍自适应可用；收起动画时内容保持该宽被裁剪滑出
    min-width: 200px;
  }
}

// 左侧菜单与编辑区之间的拖拽调宽分隔条
.sidebar-resizer {
  flex-shrink: 0;
  width: 6px;
  margin: 0 -3px;
  cursor: col-resize;
  position: relative;
  z-index: 5;
  transition: background-color 0.2s ease;

  &:hover,
  &.is-resizing {
    background-color: rgba(24, 144, 255, 0.25);
  }
}
.iconjishiben-01 {
  font-size: 16px;
  color: #1890ff;
}

/* 编辑区：记事本专用编辑器（目录面板 + 富文本在组件内部布局） */
.edit-content {
  position: relative;
  display: flex;
  align-items: stretch;

  // 编辑器组件占满剩余宽度：缺省 flex-basis 为内容宽，长段落会把编辑区撑破视口
  > .notepad-editor {
    flex: 1;
    min-width: 0;
  }
}

.right-box {
  background-color: #fff;
  // 左侧 210px，剩余空间自适应占满，避免右缘留缝
  flex: 1;
  min-width: 0;
  :deep(.el-card__body) {
    padding: 0 !important;
    border: none !important;
  }
  :deep(.el-card) {
    border: none !important;
  }
  // 全屏 / 退出全屏按钮：内容态头部与空状态头部共用同一图标样式
  .fullscreen-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    color: #606266;
    cursor: pointer;
    transition: color 0.2s ease;

    svg {
      display: block;
    }

    &:hover {
      color: #1890ff;
    }
  }
  .content {
    font-size: 12px;
    color: #303133;
    font-weight: normal;

    /* 文章顶部：左侧信息区（路径行 + 时间行）+ 右侧关闭按钮 */
    .article-top {
      display: flex;
      align-items: center;
      // 上下内边距相等，右侧 15px：配合关闭按钮的 24px 盒，使其四周留白一致
      padding: 8px 15px 8px 20px;
      // 与编辑区之间的分隔线（工具栏无边框，仅此一条）
      border-bottom: 1px solid #ebeef5;

      .head-main {
        flex: 1;
        min-width: 0;
      }

      .top-row {
        display: flex;
        align-items: center;
      }

      .fold-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        margin-right: 10px;
        flex-shrink: 0;
        color: #606266;
        cursor: pointer;
        transition: color 0.2s ease;

        .fold-svg {
          display: block;
        }

        &:hover {
          color: #1890ff;
        }
      }

      .crumb {
        // 不抢占剩余空间：提示紧跟面包屑文字，超长时自身收缩截断
        flex: 0 1 auto;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;

        :deep(.el-breadcrumb__inner) {
          font-size: 13px;
          font-weight: 400;
          color: #909399;
        }

        :deep(.el-breadcrumb__item:last-child .el-breadcrumb__inner) {
          color: #303133;
          font-weight: 500;
        }
      }

      // 第二行：最后修改时间 + 自动保存倒计时，左缘与收起图标图形对齐（16px 图形在 24px 盒内居中，偏移 4px）
      .meta-row {
        display: flex;
        align-items: center;
        margin-top: 2px;

        .update-time {
          margin-left: 4px;
          font-size: 12px;
          line-height: 1;
          color: #909399;
        }

        // 自动保存倒计时：弱化灰，悬停说明保存方式
        .auto-save-tip {
          flex-shrink: 0;
          margin-left: 12px;
          font-size: 12px;
          line-height: 1;
          color: #909399;
          white-space: nowrap;
          cursor: default;

          // 保存失败：转红提示，用户再次编辑后恢复
          &.is-failed {
            color: #f56c6c;
          }

          // 状态圆点：绿=已保存，红=有未保存改动/保存中/失败
          .save-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            margin-right: 5px;
            border-radius: 50%;
            vertical-align: middle;
            background: #67c23a;

            &.is-dirty {
              background: #f56c6c;
            }
          }
        }
      }
    }

    .savebutton {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      // 全屏与关闭各占 24px 方盒：盒外靠头部块垂直居中 + 15px 右内边距，四周留白一致
      height: 24px;

      // 全屏 / 退出全屏：与关闭按钮同尺寸方盒
      .fullscreen-btn {
        margin-right: 4px;
      }

      .el-icon-close {
        margin: 0;
      }
    }
  }
  :deep(.w-e-bar-divider) {
    width: 0.5px;
    background: #f7f7f7;
  }
  .noneDefault {
    position: relative;
    width: 100%;
    height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 13px;
    color: #999999;
    div {
      margin-bottom: 140px;
    }
    .img {
      width: 94px;
      height: 94px;
    }
    .btn1 {
      position: absolute;
      top: 20px;
      right: 10px;
    }
    // 全屏切换：置于关闭按钮左侧，与关闭图标垂直居中对齐
    .btn2 {
      position: absolute;
      top: 16px;
      right: 30px;
    }
  }
}
</style>
