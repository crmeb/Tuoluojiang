<!--
  @FileDescription: 记事本专用编辑器（正文大纲目录 + 富文本）
  功能：封装记事本独立编辑器，不再复用共用的 oa-wangeditor。
  - 左侧目录由正文标题（h1~h4 / Markdown 快捷标题）实时解析生成，点击定位、可收起/展开
  - 正文段落左侧悬浮「+」转换块类型（正文/标题/列表）、拖拽手柄排序，操作实时同步目录与保存
  - 目录收起后保留贴左悬浮入口，正文预留左侧距离避免遮挡
-->
<template>
  <div class="notepad-editor">
    <!-- 工具栏横跨全宽，目录面板与正文在其下方并排 -->
    <Toolbar :defaultConfig="toolbarConfig" :editor="editorInstance" mode="default" class="wangeditor-box" />

    <div class="editor-body">
      <!-- 目录收起后的悬浮入口：紧贴左缘展示 -->
      <el-tooltip content="展开目录" effect="dark" placement="right" :open-delay="300">
        <i
          v-if="!catalogVisible"
          class="catalog-float-btn el-icon-s-unfold"
          @click="openCatalogPanel"
        />
      </el-tooltip>

      <!-- 正文目录面板：宽度可拖拽调整（拖拽后本地持久化） -->
      <div
        v-show="catalogVisible"
        class="catalog-panel"
        :style="{ height: panelHeight, width: panelWidth + 'px' }"
      >
      <div class="panel-head">
        <el-tooltip :content="allExpanded ? '全部折叠' : '全部展开'" effect="dark" placement="top" :open-delay="300">
          <span class="panel-title" @click="toggleAllCatalog">
            <!-- 图标随目录状态切换（Lucide list-collapse 及其镜像）：全部展开时显示折叠样式，全部折叠时显示展开样式 -->
            <svg
              v-if="allExpanded"
              class="panel-title-svg"
              viewBox="0 0 24 24"
              width="14"
              height="14"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M10 5h11m-11 7h11m-11 7h11M3 10l3-3l-3-3m0 16l3-3l-3-3" />
            </svg>
            <svg
              v-else
              class="panel-title-svg"
              viewBox="0 0 24 24"
              width="14"
              height="14"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M4.5 5L7.75 8.5L11 5M15 8.5H20.5M4.5 15.5L7.75 19L11 15.5M15 19H20.5" />
            </svg>
            目录
          </span>
        </el-tooltip>
        <el-tooltip content="收起目录" effect="dark" placement="top" :open-delay="300">
          <i class="el-icon-s-fold panel-fold" @click="collapseCatalogPanel" />
        </el-tooltip>
      </div>
      <el-tree
        v-if="catalog.length"
        :data="displayCatalog"
        node-key="key"
        :indent="0"
        :props="{ label: 'text', children: 'children' }"
        :default-expanded-keys="defaultExpandedKeys"
        :expand-on-click-node="false"
        @node-click="handleNodeClick"
      >
        <template #default="{ data }">
          <span
            class="catalog-node"
            :class="`level-${data.level}`"
            :style="{ paddingLeft: (data.level - 1) * 14 + 12 + 'px' }"
          >
            <el-tooltip :content="collapsedKeys.includes(data.key) ? '展开' : '收起'" effect="dark" placement="top" :open-delay="300">
              <i
                v-if="data.childCount"
                class="node-toggle"
                :class="collapsedKeys.includes(data.key) ? 'el-icon-caret-right' : 'el-icon-caret-bottom'"
                @click.stop="handleToggleNode(data)"
              />
            </el-tooltip>
            <span class="node-label" :title="data.text">{{ data.text }}</span>
          </span>
        </template>
      </el-tree>
      <div v-else class="catalog-empty">当前笔记暂无标题层级</div>
    </div>

    <!-- 目录面板与正文之间的拖拽调宽分隔条 -->
    <div v-show="catalogVisible" class="catalog-resizer" @mousedown="startPanelResize"></div>

    <!-- 富文本编辑区：标题（正文第一行）→ 正文 -->
    <div ref="editorMainRef" class="editor-main">
      <div class="edit-title">
        <el-input
          :value="title"
          :placeholder="titlePlaceholder"
          class="edit-input"
          size="small"
          @input="handleTitleInput"
          @change="handleTitleChange"
        />
      </div>
      <Editor
        v-model="contentVal"
        :defaultConfig="editorConfig"
        mode="default"
        :style="{ '--height': editorHeight }"
        class="wangeditor-box"
        @onChange="handleEditorChange"
        @onCreated="handleEditorCreated"
      />

      <!-- 段落左侧控件组：插入 / 拖拽。标题行排在徽标之后，正文行贴近文字 -->
      <div
        v-show="hoverBlock.visible"
        class="block-ctl-group"
        :style="{ top: hoverBlock.top + 5 + 'px', left: hoverBlock.level > 0 ? '18px' : '34px' }"
      >
        <el-tooltip content="点击插入内容" effect="dark" placement="top" :open-delay="100">
          <i
            class="ctl-btn el-icon-plus"
            :class="{ 'is-active': blockMenuVisible }"
            @mousedown.prevent.stop
            @mouseenter="enterCtl"
            @mouseleave="leaveCtl"
            @click.stop="toggleBlockMenu"
          />
        </el-tooltip>
        <el-tooltip content="拖动排序" effect="dark" placement="top" :open-delay="100">
          <span
            class="ctl-btn ctl-drag"
            draggable="true"
            @mousedown.stop
            @dragstart="handleContentDragStart"
            @dragend="handleContentDragEnd"
            @mouseenter="enterCtl"
            @mouseleave="leaveCtl"
          >
            <svg viewBox="0 0 16 16" width="10" height="10" fill="currentColor">
              <circle cx="4.5" cy="3" r="1.7" />
              <circle cx="11.5" cy="3" r="1.7" />
              <circle cx="4.5" cy="8" r="1.7" />
              <circle cx="11.5" cy="8" r="1.7" />
              <circle cx="4.5" cy="13" r="1.7" />
              <circle cx="11.5" cy="13" r="1.7" />
            </svg>
          </span>
        </el-tooltip>
      </div>
      <!-- 标题级别徽标与折叠箭头：位于行内左侧控件区，箭头紧挨标题文字 -->
      <el-tooltip :content="`H${hoverBlock.level} 级标题`" effect="dark" placement="top" :open-delay="100">
        <span
          v-show="hoverBlock.visible && hoverBlock.level > 0"
          class="ctl-badge"
          :style="{ top: hoverBlock.top + 5 + 'px', left: '0px' }"
        >
          H{{ hoverBlock.level }}
        </span>
      </el-tooltip>
      <el-tooltip
        v-if="hoverBlock.level > 0"
        :content="hoverBlock.collapsed ? '展开' : '收起'"
        effect="dark"
        placement="top"
        :open-delay="100"
      >
        <i
          v-show="hoverBlock.visible && hoverBlock.level > 0"
          class="ctl-btn ctl-float"
          :class="hoverBlock.collapsed ? 'el-icon-caret-right' : 'el-icon-caret-bottom'"
          :style="{ top: hoverBlock.top + 5 + 'px', left: '54px' }"
          @mouseenter="enterCtl"
          @mouseleave="leaveCtl"
          @click.stop="toggleHeadingCollapse"
        />
      </el-tooltip>
      <!-- 块类型菜单：点击「+」展开 -->
      <div
        v-show="blockMenuVisible"
        ref="blockMenuRef"
        class="block-menu"
        :style="{
          top: hoverBlock.top + 30 + 'px',
          left: '6px',
          maxHeight: blockMenuBox.maxHeight,
          transform: blockMenuBox.flip ? 'translateY(-100%)' : 'none'
        }"
        @mousedown.prevent.stop
        @click.stop
        @mouseenter="enterCtl"
        @mouseleave="leaveCtl"
      >
        <div v-for="item in blockMenu" :key="item.type" class="block-menu-item-wrap">
          <el-tooltip :content="blockTip(item)" effect="dark" placement="right" :open-delay="200" popper-class="block-menu-tip-popper">
            <div class="block-menu-item" @click="insertBlock(item.type)">
              <!-- icon 为本地常量 SVG（wangEditor 官方菜单同款），非用户输入，v-html 安全；标题项用文字徽标 -->
              <span v-if="item.badge" class="block-menu-badge">{{ item.badge }}</span>
              <span v-else-if="item.icon" class="block-menu-icon" v-html="item.icon" />
              {{ item.label }}
            </div>
          </el-tooltip>
        </div>
      </div>
      <!-- 图片上传入口：「+」菜单选图片时触发（display:none，仅用 click() 唤起） -->
      <input ref="imageInputRef" type="file" accept="image/*" class="image-file-input" @change="onImageFileChosen" />
      <!-- 拖拽落点指示线 -->
      <div v-show="dropIndicator.visible" class="drop-indicator" :style="{ top: dropIndicator.top + 'px' }"></div>
    </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({ name: 'NotepadEditor' })
</script>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, shallowRef, watch } from 'vue'
import { Editor, Toolbar } from '@wangeditor-next/editor-for-vue2'
import '@wangeditor-next/editor/dist/css/style.css'
import { uploader } from '@/utils/uploadCloud'

// 「+ 添加子目录」生成的新标题默认文案
const NEW_NODE_TEXT = '新建目录'

interface CatalogNode {
  // 稳定 key：层级-文案-同文案序号，用于保留展开状态
  key: string
  text: string
  level: number
  // 编辑器顶层块索引（editor.children 下标），用于点击目录滚动定位
  blockIndex: number
  // 原始子级数量（展示数据收起时 children 会被裁空，用该字段决定是否显示箭头）
  childCount: number
  children: CatalogNode[]
}

const props = withDefaults(
  defineProps<{
    content?: string
    title?: string
    height?: string
    placeholder?: string
    titlePlaceholder?: string
  }>(),
  {
    content: '',
    title: '',
    height: '400px',
    placeholder: '请输入内容...',
    titlePlaceholder: '请输入标题'
  }
)

const emit = defineEmits<{
  (e: 'input', value: string): void
  (e: 'update:title', value: string): void
  (e: 'title-change', value: string): void
}>()

// 标题输入：input 实时同步（脏检查用），change（失焦/回车）通知父组件保存
function handleTitleInput(value: string) {
  emit('update:title', value)
}

function handleTitleChange(value: string) {
  emit('update:title', value)
  emit('title-change', value)
}

const contentVal = ref(props.content)
const editorInstance = shallowRef<any>(null)
const catalogVisible = ref(false) // 默认不显示目录，出现标题后自动展示
// 用户对目录面板的显式操作：'open' 手动打开 / 'closed' 手动收起 / null 跟随内容自动
// 手动打开后编辑过程中不再自动隐藏，手动收起后有标题也不自动弹出
let catalogUserIntent: 'open' | 'closed' | null = null
const catalog = ref<CatalogNode[]>([])
// 目录节点默认全部展开：只记录用户主动收起的 key，新增标题自动展开
const collapsedKeys = ref<string[]>([])
const editorMainRef = ref<HTMLElement | null>(null)

// 控件组水平位置：紧贴文字左缘（文字起点 = 编辑区左缩进 96 + 首行缩进 28 = 124，再留 4px 间距；
// 标题行多一个级别徽标 20px，组的 left 相应左移）
const ctlGroupLeft = computed(() => (hoverBlock.level > 0 ? 28 : 76) + 'px')

// ---------------- 目录面板宽度拖拽（本地持久化） ----------------

const CATALOG_WIDTH_KEY = 'memorandum_catalog_width'
const PANEL_WIDTH_MIN = 180
const PANEL_WIDTH_MAX = 420

// 读取缓存的目录面板宽度：超出可拖范围或未设置时回退默认值
function initPanelWidth(): number {
  const saved = Number(localStorage.getItem(CATALOG_WIDTH_KEY))
  return saved >= PANEL_WIDTH_MIN && saved <= PANEL_WIDTH_MAX ? saved : 230
}

const panelWidth = ref(initPanelWidth())
let panelResizing = false
let panelResizeStartX = 0
let panelResizeStartWidth = 0

// 目录面板在编辑区左侧：分隔条跟随鼠标，向右拖加宽、向左拖收窄（与左侧菜单拖拽方向一致）
function startPanelResize(e: MouseEvent) {
  e.preventDefault()
  panelResizing = true
  panelResizeStartX = e.clientX
  panelResizeStartWidth = panelWidth.value
  document.addEventListener('mousemove', onPanelResizing)
  document.addEventListener('mouseup', stopPanelResize)
}

function onPanelResizing(e: MouseEvent) {
  if (!panelResizing) return
  e.preventDefault()
  const width = panelResizeStartWidth + (e.clientX - panelResizeStartX)
  panelWidth.value = Math.min(PANEL_WIDTH_MAX, Math.max(PANEL_WIDTH_MIN, width))
}

function stopPanelResize() {
  panelResizing = false
  localStorage.setItem(CATALOG_WIDTH_KEY, String(panelWidth.value))
  document.removeEventListener('mousemove', onPanelResizing)
  document.removeEventListener('mouseup', stopPanelResize)
}

// ---------------- 高度自适应 ----------------
// 工具栏/标题布局调整后写死 calc(100vh - N) 容易算错，改为实测编辑区距视口顶部的位置动态计算

const panelHeight = ref('400px')
const editorHeight = ref('400px')

function updateHeights() {
  const main = editorMainRef.value
  if (!main) return
  const top = main.getBoundingClientRect().top
  const available = window.innerHeight - top - 56 // 底部留白 56px
  if (available < 200) return
  panelHeight.value = `${available}px`
  const titleEl = main.querySelector('.edit-title') as HTMLElement | null
  editorHeight.value = `${available - (titleEl ? titleEl.offsetHeight : 60)}px`
}

// ---------------- 段落左侧悬浮「+」插入菜单 ----------------

// visible 是否显示 / top 相对 editor-main / index 顶层块下标 / level 标题级别(0=非标题) / collapsed 该标题是否已收起
const hoverBlock = reactive({ visible: false, top: 0, index: -1, level: 0, collapsed: false })
// 点击行后固定显示：鼠标移到别的行或移出编辑区、内容变化时才解除
const pinned = ref(false)
const blockMenuVisible = ref(false)
const blockMenuRef = ref<HTMLElement | null>(null)
// 鼠标是否停在悬浮控件/菜单上：期间编辑器 blur 触发的 change 不得隐藏控件，
// 否则会出现"点击 + 控件就消失、菜单来不及出现"
let ctlHovered = false
let hideTimer: ReturnType<typeof setTimeout> | null = null
let boundEditable: HTMLElement | null = null

// 标题收起状态：标题块 DOM -> 被隐藏的子节块列表（CSS 隐藏，文档节点不删，保存内容完整）
const hiddenSections = new Map<HTMLElement, HTMLElement[]>()

// wangEditor 官方菜单同款图标（与顶部工具栏视觉统一），仅新加的内容块配置
const ICON_TODO = '<svg viewBox="0 0 1024 1024"><path d="M278.755556 403.911111l-79.644445 79.644445L455.111111 739.555556l568.888889-568.888889-79.644444-79.644445L455.111111 580.266667l-176.355555-176.355556zM910.222222 910.222222H113.777778V113.777778h568.888889V0H113.777778C51.2 0 0 51.2 0 113.777778v796.444444c0 62.577778 51.2 113.777778 113.777778 113.777778h796.444444c62.577778 0 113.777778-51.2 113.777778-113.777778V455.111111h-113.777778v455.111111z"></path></svg>'
const ICON_CODE = '<svg viewBox="0 0 1280 1024"><path d="M832 736l96 96 320-320L928 192l-96 96 224 224zM448 288l-96-96L32 512l320 320 96-96-224-224zM701.312 150.528l69.472 18.944-192 704.032-69.472-18.944 192-704.032z"></path></svg>'
const ICON_TABLE = '<svg viewBox="0 0 1024 1024"><path d="M0 64v896h1024V64H0z m384 576v-192h256v192h-256z m256 64v192h-256v-192h256z m0-512v192h-256V192h256zM320 192v192H64V192h256z m-256 256h256v192H64v-192z m640 0h256v192h-256v-192z m0-64V192h256v192h-256zM64 704h256v192H64v-192z m640 192v-192h256v192h-256z"></path></svg>'
const ICON_IMAGE = '<svg viewBox="0 0 1024 1024"><path d="M959.877 128l0.123 0.123v767.775l-0.123 0.122H64.102l-0.122-0.122V128.123l0.122-0.123h895.775zM960 64H64C28.795 64 0 92.795 0 128v768c0 35.205 28.795 64 64 64h896c35.205 0 64-28.795 64-64V128c0-35.205-28.795-64-64-64zM832 288.01c0 53.023-42.988 96.01-96.01 96.01s-96.01-42.987-96.01-96.01S682.967 192 735.99 192 832 234.988 832 288.01zM896 832H128V704l224.01-384 256 320h64l224.01-192z"></path></svg>'
// 官方列表菜单图标（无序/有序共用同一个，靠 ordered 标记区分）
const ICON_LIST = '<svg viewBox="0 0 1024 1024"><path d="M384 832h640v128H384z m0-384h640v128H384z m0-384h640v128H384zM192 0v256H128V64H64V0zM128 526.016v50.016h128v64H64v-146.016l128-60V384H64v-64h192v146.016zM256 704v320H64v-64h128v-64H64v-64h128v-64H64v-64z"></path></svg>'

// 「+」可插入的内容块（与 wangEditor 节点 type 对应）
// tip：悬浮说明；shortcut：快捷键级别数字（wangEditor 内置绑定：Windows Ctrl+数字 / macOS Alt+数字）
const isMac = /mac/i.test(navigator.platform || navigator.userAgent)
const blockMenu: Array<{ label: string; type: string; icon?: string; badge?: string; tip: string; level?: number }> = [
  { label: '正文', type: 'paragraph', tip: '普通文本段落', level: 0 },
  { label: '一级标题', type: 'header1', badge: 'H1', tip: 'Markdown: #空格', level: 1 },
  { label: '二级标题', type: 'header2', badge: 'H2', tip: 'Markdown: ##空格', level: 2 },
  { label: '三级标题', type: 'header3', badge: 'H3', tip: 'Markdown: ###空格', level: 3 },
  { label: '四级标题', type: 'header4', badge: 'H4', tip: 'Markdown: ####空格', level: 4 },
  { label: '五级标题', type: 'header5', badge: 'H5', tip: 'Markdown: #####空格', level: 5 },
  { label: '六级标题', type: 'header6', badge: 'H6', tip: 'Markdown: ######空格', level: 6 },
  { label: '无序列表', type: 'bulletedList', icon: ICON_LIST, tip: 'Markdown: -空格' },
  { label: '有序列表', type: 'numberedList', icon: ICON_LIST, tip: 'Markdown: 1.空格' },
  { label: '待办', type: 'todo', icon: ICON_TODO, tip: '生成勾选待办项' },
  { label: '代码块', type: 'codeBlock', icon: ICON_CODE, tip: '插入代码块' },
  { label: '表格', type: 'table', icon: ICON_TABLE, tip: '插入 3×3 表格' },
  { label: '图片', type: 'image', icon: ICON_IMAGE, tip: '上传并插入图片' }
]

// 菜单项悬浮提示：快捷键行（有级别数字时展示，Windows Ctrl+数字 / macOS Alt+数字）+ Markdown 用法行
function blockTip(item: { label: string; tip: string; level?: number }): string {
  const lines: string[] = []
  if (item.level !== undefined) {
    const mod = isMac ? 'Alt' : 'Ctrl'
    lines.push(`${item.label}\u2003${mod}+${item.level}`)
  }
  lines.push(item.tip)
  return lines.join('\n')
}

// 图片上传：隐藏文件框 + 插入落点（「+」菜单选图片时先触发选文件，成功后回填）
const imageInputRef = ref<HTMLInputElement | null>(null)
let imageInsertAt = -1

// Slate 编辑器实例：节点树体积大且 sealed，不入响应式
let editor: any = null

// 记事本工具栏（与原 oa-wangeditor 的 notepad 配置一致）
const toolbarConfig = {
  toolbarKeys: [
    'headerSelect',
    'blockquote',
    'bold',
    'underline',
    'italic',
    {
      key: 'group-more-style',
      title: '更多',
      iconSvg:
        '<svg viewBox="0 0 1024 1024"><path d="M204.8 505.6m-76.8 0a76.8 76.8 0 1 0 153.6 0 76.8 76.8 0 1 0-153.6 0Z"></path><path d="M505.6 505.6m-76.8 0a76.8 76.8 0 1 0 153.6 0 76.8 76.8 0 1 0-153.6 0Z"></path><path d="M806.4 505.6m-76.8 0a76.8 76.8 0 1 0 153.6 0 76.8 76.8 0 1 0-153.6 0Z"></path></svg>',
      menuKeys: ['fontSize', 'fontFamily', 'lineHeight', 'code', 'clearStyle', 'through', 'sup', 'sub']
    },
    '|',
    'color',
    'bgColor',
    'bulletedList',
    'numberedList',
    'todo',
    {
      key: 'group-justify',
      title: '对齐',
      iconSvg:
        '<svg viewBox="0 0 1024 1024"><path d="M768 793.6v102.4H51.2v-102.4h716.8z m204.8-230.4v102.4H51.2v-102.4h921.6z m-204.8-230.4v102.4H51.2v-102.4h716.8zM972.8 102.4v102.4H51.2V102.4h921.6z"></path></svg>',
      menuKeys: ['justifyLeft', 'justifyRight', 'justifyCenter', 'justifyJustify']
    },
    '|',
    'emotion',
    {
      key: 'group-image',
      title: '图片',
      iconSvg:
        '<svg viewBox="0 0 1024 1024"><path d="M959.877 128l0.123 0.123v767.775l-0.123 0.122H64.102l-0.122-0.122V128.123l0.122-0.123h895.775zM960 64H64C28.795 64 0 92.795 0 128v768c0 35.205 28.795 64 64 64h896c35.205 0 64-28.795 64-64V128c0-35.205 28.795-64 64-64zM832 288.01c0 53.023-42.988 96.01-96.01 96.01s-96.01-42.987-96.01-96.01S682.967 192 735.99 192 832 234.988 832 288.01zM896 832H128V704l224.01-384 256 320h64l224.01-192z"></path></svg>',
      menuKeys: ['insertImage', 'uploadImage']
    },
    'insertTable',
    'codeBlock',
    'divider',
    'insertLink',
    'undo',
    'redo'
  ]
}

const editorConfig: any = {
  MENU_CONF: {},
  placeholder: props.placeholder
}
editorConfig.MENU_CONF['uploadImage'] = {
  customUpload(file: File, insertFn: (url: string, alt: string, href: string) => void) {
    const options = { way: 2, relation_type: '', relation_id: 0, eid: 0 }
    uploader(file, '1', options).then((res: any) => {
      insertFn(res.data.url, '图片上传', res.data.url)
    })
  }
}

// ---------------- 编辑器生命周期 ----------------

function handleEditorCreated(ed: any) {
  editor = Object.seal(ed) // 必须 seal，否则 wangeditor 报错
  editorInstance.value = editor
  // 段落默认字号由 .w-e-text-container 的 font-size: 15px 继承提供，不写 fontSize 内联——
  // 内联 mark 在段落转标题（工具栏 headerSelect / 「+」菜单）后仍挂在文字上，会盖掉标题字号，
  // 表现为"设置 H1 样式后文字大小不变"。
  // 标题清理全部 fontSize；段落只清理历史上由旧逻辑写入的 15px 默认值（用户手动设置的其他字号保留）
  editor.children.forEach((item: any) => {
    if (!item.children) return
    const isHeading = /^header[1-6]$/.test(item.type || '')
    ;(item.children as any[]).forEach((ele: any) => {
      if (isHeading) {
        if (ele.fontSize) delete ele.fontSize
      } else if (item.type === 'paragraph' && ele.fontSize === '15px') {
        delete ele.fontSize
      }
    })
  })
  rebuildCatalog()
  bindBlockHover()
  updateHeights()
}

onMounted(() => {
  updateHeights()
  window.addEventListener('resize', updateHeights)
  // Alt+数字快捷键挂 document：鼠标悬浮某行（未点击、编辑器无焦点）时也要能触发
  document.addEventListener('keydown', onGlobalKeydown)
})

function handleEditorChange() {
  handleMarkdownHeader()
  rebuildCatalog()
  // 编辑器 blur/选区变化同样会触发 change。鼠标停在控件/菜单上、或正在拖拽时不能隐藏控件，
  // 否则会出现"点击 + 控件就消失、菜单来不及出现"（这是菜单经常打不开的根因）
  const interacting = ctlHovered || blockMenuVisible.value || dragFromIndex >= 0
  if (!interacting) {
    hoverBlock.visible = false
    pinned.value = false
  }
  dropIndicator.visible = false
}

onBeforeUnmount(() => {
  unbindBlockHover()
  window.removeEventListener('resize', updateHeights)
  document.removeEventListener('keydown', onGlobalKeydown)
  document.removeEventListener('mousedown', onDocumentMouseDown)
  document.removeEventListener('mousemove', onPanelResizing)
  document.removeEventListener('mouseup', stopPanelResize)
  const ed = editorInstance.value
  if (ed) {
    try {
      ed.destroy()
    } catch (e) {
      // ignore
    }
    editorInstance.value = null
    editor = null
  }
})

// ---------------- 正文 <-> 目录同步 ----------------

interface OutlineBlock {
  blockIndex: number
  level: number
  text: string
}

// 顶层块的标题信息：header1~6 直接识别，段落行首 "# ~ #####" 视为 Markdown 标题
function getOutlineBlocks(): OutlineBlock[] {
  if (!editor || !Array.isArray(editor.children)) return []
  return (editor.children as any[]).map((node, blockIndex): OutlineBlock => {
    const type = node.type || ''
    let level = 0
    let text = ''
    if (/^header[1-6]$/.test(type)) {
      level = Number(type.slice(6))
      text = plainText(node).trim()
    } else if (type === 'paragraph') {
      const m = plainText(node).trim().match(/^(#{1,5})\s+(.+)$/)
      if (m) {
        level = m[1].length
        text = m[2].trim()
      }
    }
    return { blockIndex, level, text }
  })
}

function plainText(node: any) {
  return (node.children || []).map((c: any) => c.text || '').join('')
}

// 由正文块重建目录树
function rebuildCatalog() {
  const blocks = getOutlineBlocks()
  const flat = blocks.filter((b) => b.level > 0)
  const counter: Record<string, number> = {}
  const nodes: CatalogNode[] = flat.map((b) => {
    const dup = (counter[`${b.level}-${b.text}`] = (counter[`${b.level}-${b.text}`] || 0) + 1)
    return {
      key: `${b.level}-${b.text}-${dup}`,
      text: b.text,
      level: b.level,
      blockIndex: b.blockIndex,
      childCount: 0,
      children: []
    }
  })

  const tree: CatalogNode[] = []
  const stack: CatalogNode[] = []
  nodes.forEach((node) => {
    while (stack.length && stack[stack.length - 1].level >= node.level) stack.pop()
    if (stack.length) stack[stack.length - 1].children.push(node)
    else tree.push(node)
    stack.push(node)
  })
  catalog.value = tree

  // 目录面板显隐：用户显式打开/收起过就尊重其选择（编辑中也不自动改），否则按有无标题自动展示
  if (catalogUserIntent === 'open') {
    catalogVisible.value = true
  } else if (catalogUserIntent === 'closed') {
    catalogVisible.value = false
  } else {
    catalogVisible.value = tree.length > 0
  }
}

// 展示数据：被用户收起的节点把 children 裁空，展开状态完全由 collapsedKeys 驱动（与箭头同源，不会脱节）
const displayCatalog = computed(() => stripCollapsed(catalog.value, collapsedKeys.value))

// el-tree 建节点时的默认展开集合：全部 key 减去用户主动收起的
const defaultExpandedKeys = computed(() =>
  collectKeys(catalog.value).filter((key) => !collapsedKeys.value.includes(key))
)

function stripCollapsed(nodes: CatalogNode[], collapsed: string[]): CatalogNode[] {
  return nodes.map((node) => ({
    ...node,
    childCount: node.children.length,
    children: collapsed.includes(node.key) ? [] : stripCollapsed(node.children, collapsed)
  }))
}

function collectKeys(nodes: CatalogNode[]): string[] {
  return nodes.flatMap((node) => [node.key, ...collectKeys(node.children)])
}

// 点击目录滚动到正文对应标题
// 注意：不能用 scrollIntoView——它会把抽屉/页面等外层容器一并滚动（含横向），
// 导致整个目录面板偏移、盖住左侧笔记本列表；这里只滚动编辑器自身的滚动容器
function handleNodeClick(data: CatalogNode) {
  const container = getEditableEl()
  const el = container && container.children[data.blockIndex]
  if (!el) return
  const scroller = el.closest('.w-e-scroll')
  if (!scroller) return
  const top = el.getBoundingClientRect().top - scroller.getBoundingClientRect().top + scroller.scrollTop - 16
  scroller.scrollTo({ top, behavior: 'smooth' })
}

// ---------------- 段落左侧悬浮「+」事件 ----------------

function scheduleHide() {
  if (hideTimer) clearTimeout(hideTimer)
  hideTimer = setTimeout(() => {
    // 点击固定（pinned）的行不因鼠标移开而隐藏，直到移到别的行/移出编辑区/内容变化
    if (pinned.value) return
    hoverBlock.visible = false
    blockMenuVisible.value = false
  }, 200)
}

function cancelHide() {
  if (hideTimer) clearTimeout(hideTimer)
}

// 进入/离开悬浮控件或菜单：进入时锁定不自动隐藏，并记录 ctlHovered 供 change 判断
function enterCtl() {
  ctlHovered = true
  cancelHide()
}

function leaveCtl() {
  ctlHovered = false
  scheduleHide()
}

// 悬浮经过段落时，在其左侧展示「+」（top 相对 editor-main）
function onEditableMouseOver(e: MouseEvent) {
  // 必须先取消待执行的隐藏定时器：从按钮移回正文时会触发按钮 mouseleave 的延迟隐藏，
  // 否则 200ms 后会把刚显示的悬浮控件再次隐藏
  cancelHide()
  const target = e.target as HTMLElement | null
  const editable = getEditableEl()
  const mainEl = editorMainRef.value
  if (!target || !editable || !mainEl || !(target instanceof Element)) return

  // 鼠标落在块之间的 margin 间隙时 closest 会落空，改按垂直位置匹配顶层块
  const block = findTopBlockEl(target, editable) || findBlockByY(editable, e.clientY)
  if (!block) {
    hoverBlock.visible = false
    return
  }

  if (locateBlock(block, editable) < 0) {
    hoverBlock.visible = false
    return
  }
  hoverBlock.visible = true
  // 注意：这里不能关块类型菜单——菜单打开后鼠标在正文微动会触发 mouseover，
  // 菜单由「点击外部 / 选择菜单项 / 再点+」关闭
}

// 定位块并写入 hoverBlock（index/top/level/collapsed），返回块下标（-1 失效）
function locateBlock(block: HTMLElement, editable: HTMLElement): number {
  const index = Array.prototype.indexOf.call(editable.children, block)
  if (index < 0) return -1

  // 鼠标/点击移到别的行：解除上一行的点击固定，并收起已打开的块菜单
  if (pinned.value && hoverBlock.index !== index) pinned.value = false
  if (blockMenuVisible.value && hoverBlock.index !== index) blockMenuVisible.value = false
  hoverBlock.index = index
  // 与段落垂直居中：控件高 24px，取段落中线再上移半高
  const rect = block.getBoundingClientRect()
  const mainEl = editorMainRef.value
  if (mainEl) {
    hoverBlock.top = rect.top - mainEl.getBoundingClientRect().top + rect.height / 2 - 12
  }
  // 标题块：记录级别与收起状态，供折叠箭头显示
  const slateNode = editor && editor.children ? editor.children[index] : null
  hoverBlock.level = slateNode ? headingLevelOf(slateNode) : 0
  hoverBlock.collapsed = hoverBlock.level > 0 && hiddenSections.has(block)
  return index
}

// 点击正文行：固定该行控件显示（鼠标移到别的行/移出编辑区/内容变化时解除）。
// 注意：mousedown 更新光标就会触发 onChange 隐藏控件，因此点击时必须主动重新定位
function onEditableClick(e: MouseEvent) {
  const editable = getEditableEl()
  if (!editable || !(e.target instanceof Element)) return
  const block = findTopBlockEl(e.target, editable) || findBlockByY(editable, e.clientY)
  if (!block) return
  if (locateBlock(block, editable) < 0) return
  hoverBlock.visible = true
  pinned.value = true
}

// 粘贴后全量扫描：段落行首 "# ~ #####" 的 Markdown 标记自动转为对应级别标题
// （handleMarkdownHeader 只处理光标所在块，覆盖不了粘贴进来的批量内容）
function onEditablePaste() {
  setTimeout(() => {
    if (!editor) return
    let changed = false
    // 原位替换不改变块数量，index 在循环中保持稳定
    ;(editor.children as any[]).forEach((node, index) => {
      if (node.type !== 'paragraph') return
      const text = plainText(node)
      const match = text.match(/^(#{1,5})\s+(.*)$/)
      if (!match) return
      changed = true
      try {
        editor.withoutNormalizing(() => {
          editor.insertNodes({ type: `header${match[1].length}`, children: [{ text: match[2] }] }, { at: [index] })
          editor.removeNodes({ at: [index + 1] })
        })
      } catch (e) {
        // 单块转换失败静默跳过，不影响其余块
      }
    })
    if (changed) {
      rebuildCatalog()
      // 触发保存链路与目录同步
      emit('input', editor.getHtml())
    }
  }, 0)
}

// Alt + 数字（1~5）：设置 H 标签。优先作用于「鼠标悬浮的行」（hover 即生效，无需点击聚焦）；
// 鼠标不在编辑器内时退回光标所在行。挂 document 才能收到无焦点状态的按键
function onGlobalKeydown(e: KeyboardEvent) {
  // 修饰键：Windows 用 Ctrl+数字，mac 上 Ctrl/Option(Alt) 都支持
  // （Cmd+数字被浏览器「切换标签页」占用，按键到不了页面，不做）
  const mod = e.ctrlKey || e.metaKey || e.altKey
  // TODO 临时诊断：定位 Alt+数字 快捷键失效环节（事件是否到达/哪个守卫拦截），确认后移除
  if (mod || /^Digit|^Numpad/.test(e.code)) {
    console.log(
      '[notepad-hotkey]',
      `alt=${e.altKey} ctrl=${e.ctrlKey} meta=${e.metaKey} code=${e.code}`,
      `hover=${hoverBlock.visible}#${hoverBlock.index}`,
      `sel=${editor && editor.selection ? 'y' : 'n'}`,
      `disabled=${editor ? editor.isDisabled() : '-'}`
    )
  }
  if (!mod || e.shiftKey) return
  const m = /^(?:Digit|Numpad)([1-5])$/.exec(e.code)
  if (!m) return
  if (!editor || editor.isDisabled()) return

  // setNodes 只改块类型；正文残留的 fontSize 内联不会顶掉标题字号（CSS 已强制标题内文字继承级别字号）
  const hoverValid = hoverBlock.visible && hoverBlock.index >= 0
  const selection = editor.selection
  if (!hoverValid && !selection) return
  e.preventDefault() // 拦截 macOS Option+数字输出特殊字符（¡™£…）
  const index = hoverValid ? hoverBlock.index : selection.anchor.path[0]
  try {
    editor.setNodes({ type: `header${m[1]}` }, { at: [index] })
  } catch (err) {
    // 转换失败静默降级
  }
}

function bindBlockHover() {
  const editable = getEditableEl()
  if (!editable || editable === boundEditable) return
  unbindBlockHover()
  boundEditable = editable
  editable.addEventListener('mouseover', onEditableMouseOver)
  editable.addEventListener('mouseleave', scheduleHide)
  editable.addEventListener('click', onEditableClick)
  editable.addEventListener('paste', onEditablePaste)
  editable.addEventListener('scroll', scheduleHide, true)
  editable.addEventListener('dragover', onEditableDragOver)
  editable.addEventListener('drop', onEditableDrop)
  editable.addEventListener('dragleave', onEditableDragLeave)
}

function unbindBlockHover() {
  if (!boundEditable) return
  boundEditable.removeEventListener('mouseover', onEditableMouseOver)
  boundEditable.removeEventListener('mouseleave', scheduleHide)
  boundEditable.removeEventListener('click', onEditableClick)
  boundEditable.removeEventListener('paste', onEditablePaste)
  boundEditable.removeEventListener('scroll', scheduleHide, true)
  boundEditable.removeEventListener('dragover', onEditableDragOver)
  boundEditable.removeEventListener('drop', onEditableDrop)
  boundEditable.removeEventListener('dragleave', onEditableDragLeave)
  boundEditable = null
}

// 菜单展开方向与限高：打开时按视口剩余空间计算，底部空间不足时向上翻，避免菜单被裁
const blockMenuBox = reactive({ flip: false, maxHeight: '60vh' })

// 点击「+」：展开/收起块类型菜单（只切换菜单，不改动正文）
function toggleBlockMenu() {
  cancelHide()
  if (!blockMenuVisible.value) {
    const main = editorMainRef.value
    if (main) {
      const mainRect = main.getBoundingClientRect()
      const anchorY = mainRect.top + hoverBlock.top + 30 // 与模板 top 定位一致
      const below = window.innerHeight - anchorY - 8 // 锚点到视口底部
      const above = anchorY - mainRect.top - 8 // 锚点到编辑区顶部
      const need = 430 // 菜单自然高度估算（全量菜单项）
      if (below >= need || below >= above) {
        blockMenuBox.flip = false
        blockMenuBox.maxHeight = `${Math.max(Math.min(below, need), 180)}px`
      } else {
        blockMenuBox.flip = true
        blockMenuBox.maxHeight = `${Math.max(Math.min(above, need), 180)}px`
      }
    }
  }
  blockMenuVisible.value = !blockMenuVisible.value
}

// 菜单打开期间点击页面任意位置收起（mousedown 冒泡阶段监听）
function onDocumentMouseDown(e: MouseEvent) {
  const menuEl = blockMenuRef.value
  if (menuEl && e.target instanceof Node && menuEl.contains(e.target)) return
  blockMenuVisible.value = false
}

watch(blockMenuVisible, (open) => {
  if (open) {
    // 延迟到下一个宏任务挂载，避免打开菜单的那次点击冒泡立即触发关闭
    setTimeout(() => document.addEventListener('mousedown', onDocumentMouseDown), 0)
  } else {
    document.removeEventListener('mousedown', onDocumentMouseDown)
  }
})

// 选中菜单项：「+」是添加，不是转换——在当前行下方插入所选内容块，
// 光标自动进入新块；当前行内容与级别不动
function insertBlock(type: string) {
  blockMenuVisible.value = false
  const index = hoverBlock.index
  if (!editor || index < 0) return
  const at = index + 1

  try {
    if (type === 'image') {
      // 图片先选文件上传，成功后在 onImageFileChosen 里插入
      imageInsertAt = at
      imageInputRef.value?.click()
      return
    }
    if (type === 'codeBlock') {
      // 代码块是 pre > code 两层结构（单层 pre 会被 normalize 破坏，探针实测）
      editor.insertNodes({ type: 'pre', children: [{ type: 'code', language: '', children: [{ text: '' }] }] }, { at: [at] })
      focusInserted(at, { path: [at, 0, 0], offset: 0 })
      return
    }
    if (type === 'table') {
      editor.insertNodes(buildTableNode(), { at: [at] })
      // 光标进第一个单元格（table > row > cell > paragraph > text）
      focusInserted(at, { path: [at, 0, 0, 0, 0], offset: 0 })
      return
    }
    editor.insertNodes({ type, children: [{ text: '' }] }, { at: [at] })
    focusInserted(at, { path: [at, 0], offset: 0 })
  } catch (e) {
    // 插入失败静默降级
  }
}

// 构造 3×3 表格节点
function buildTableNode() {
  const cell = () => ({ type: 'table-cell', children: [{ type: 'paragraph', children: [{ text: '' }] }] })
  const row = () => ({ type: 'table-row', children: [cell(), cell(), cell()] })
  return { type: 'table', children: [row(), row(), row()] }
}

// 「+」菜单选择图片后：上传并插入「含图段落」，光标落在图片后
function onImageFileChosen(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files && input.files[0]
  input.value = '' // 清空以便重复选择同一文件
  if (!file || imageInsertAt < 0 || !editor) return
  const at = imageInsertAt
  imageInsertAt = -1
  const options = { way: 2, relation_type: '', relation_id: 0, eid: 0 }
  uploader(file, '1', options)
    .then((res: any) => {
      const url = res && res.data && res.data.url
      if (!url || !editor) return
      // 图片是行内 void 节点，必须作为段落的 inline 子节点插入（顶层插入会被 normalize 删除，探针实测）
      editor.insertNodes(
        {
          type: 'paragraph',
          children: [
            { text: '' },
            { type: 'image', src: url, alt: file.name || '图片', href: '', style: {}, children: [{ text: '' }] },
            { text: '' }
          ]
        },
        { at: [at] }
      )
      // 光标定位到图片后的文本（children[2]）
      focusInserted(at, { path: [at, 2], offset: 0 }, 'last')
    })
    .catch(() => {
      // 上传失败静默
    })
}

// 插入后同步 slate 选区并手动定位 DOM 光标。
// wangEditor-next 5.7.16 在外部代码设置选区时 slate→DOM 选区同步失效：
// select/focus 后 slate 选区已在新块，但浏览器光标仍钉在原位置（独立探针页实测），
// 等 vdom 渲染出新块后手动把 DOM 选区落到目标文本，光标才能真正进入新行
function focusInserted(domIndex: number, point: { path: number[]; offset: number }, textPos: 'first' | 'last' = 'first') {
  if (!editor) return
  try {
    editor.select(point)
    editor.focus()
  } catch (e) {
    // 选区设置失败不影响已插入的内容
  }
  setTimeout(() => {
    try {
      if (!editor) return
      const editable = getEditableEl()
      const el = editable && (editable.children[domIndex] as HTMLElement | undefined)
      if (!el) return
      const texts: Node[] = []
      const walk = (n: Node) => {
        if (n.nodeType === 3) texts.push(n)
        if (n.childNodes) Array.prototype.forEach.call(n.childNodes, walk)
      }
      walk(el)
      const target = textPos === 'last' ? texts[texts.length - 1] : texts[0]
      if (!target) return
      const range = document.createRange()
      range.setStart(target, 0)
      range.collapse(true)
      const domSel = window.getSelection()
      if (!domSel) return
      domSel.removeAllRanges()
      domSel.addRange(range)
    } catch (e) {
      // DOM 定位失败不影响已插入的内容
    }
  }, 30)
}

// ---------------- 标题折叠/展开 ----------------

// 收起/展开当前 hover 的标题：CSS 隐藏子节块，文档节点不删（保存内容完整；刷新/切笔记后重置）
function toggleHeadingCollapse() {
  const editable = getEditableEl()
  if (!editable || !editor) return
  const index = hoverBlock.index
  if (index < 0) return
  const headingEl = editable.children[index] as HTMLElement | undefined
  const slateNode: any = editor.children[index]
  const level = headingLevelOf(slateNode)
  if (!headingEl || !slateNode || !level) return

  if (hiddenSections.has(headingEl)) {
    // 展开：恢复该标题下被隐藏的块
    const hidden = hiddenSections.get(headingEl) || []
    hidden.forEach((el) => {
      el.style.display = ''
    })
    hiddenSections.delete(headingEl)
    hoverBlock.collapsed = false
    return
  }

  // 收起：隐藏 (index, sectionEnd) 区间的块；内层标题已收起的部分保持其自身状态
  const end = sectionEndOfBlock(index, level)
  if (end <= index + 1) return // 该标题下没有内容
  const hidden: HTMLElement[] = []
  for (let i = index + 1; i < end; i++) {
    const el = editable.children[i] as HTMLElement
    if (el && el.style.display !== 'none') {
      el.style.display = 'none'
      hidden.push(el)
    }
  }
  if (!hidden.length) return
  hiddenSections.set(headingEl, hidden)
  hoverBlock.collapsed = true
}

// ---------------- 正文拖拽排序 ----------------

const dropIndicator = reactive({ visible: false, top: 0 })
let dragFromIndex = -1
let dropTargetIndex = -1
let dropBefore = true

// 块的标题层级：header1~6 直接识别，段落行首 "# ~ #####" 视为 Markdown 标题；非标题返回 0
function headingLevelOf(node: any): number {
  if (!node) return 0
  const type = node.type || ''
  if (/^header[1-6]$/.test(type)) return Number(type.slice(6))
  if (type === 'paragraph') {
    const m = plainText(node).trim().match(/^(#{1,5})\s+/)
    return m ? m[1].length : 0
  }
  return 0
}

// 标题块的章节结束位置（下一个层级 <= 自身的块索引）；非标题块只移动自身
function sectionEndOfBlock(start: number, level: number): number {
  if (!editor) return start + 1
  const children = editor.children as any[]
  for (let i = start + 1; i < children.length; i++) {
    const lv = headingLevelOf(children[i])
    if (lv && lv <= level) return i
  }
  return children.length
}

// 从事件目标向上回溯到 editable 的直接子块，不依赖 tag 名：
// 标题/段落/列表/引用/代码块只要挂在根节点上都能命中（标题常被外层节点包裹）
function findTopBlockEl(target: Element, editable: HTMLElement): HTMLElement | null {
  let el: Element | null = target
  while (el && el.parentElement && el.parentElement !== editable) {
    el = el.parentElement
  }
  return el && el.parentElement === editable ? (el as HTMLElement) : null
}

// 兜底：鼠标落在块之间的 margin 间隙时事件目标是根节点，按垂直位置匹配顶层块
function findBlockByY(editable: HTMLElement, clientY: number): HTMLElement | null {
  const children = Array.prototype.slice.call(editable.children) as HTMLElement[]
  for (const el of children) {
    const rect = el.getBoundingClientRect()
    if (clientY >= rect.top && clientY <= rect.bottom) return el
  }
  return null
}

function handleContentDragStart(e: DragEvent) {
  dragFromIndex = hoverBlock.index
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(dragFromIndex))
  }
  // 注意：不能在这里隐藏悬浮控件——dragstart 后立即 display:none 源元素会取消整个拖动
}

function handleContentDragEnd() {
  // 拖动结束后再隐藏悬浮控件与指示线
  hoverBlock.visible = false
  dragFromIndex = -1
  dropTargetIndex = -1
  dropIndicator.visible = false
}

function onEditableDragOver(e: DragEvent) {
  if (dragFromIndex < 0) return
  const editable = getEditableEl()
  const mainEl = editorMainRef.value
  if (!editable || !mainEl || !(e.target instanceof Element)) return

  const block = findTopBlockEl(e.target, editable)
  if (!block) {
    dropIndicator.visible = false
    return
  }
  const index = Array.prototype.indexOf.call(editable.children, block)
  if (index < 0) {
    dropIndicator.visible = false
    return
  }

  const rect = block.getBoundingClientRect()
  const mainRect = mainEl.getBoundingClientRect()
  dropBefore = e.clientY < rect.top + rect.height / 2
  dropTargetIndex = index
  dropIndicator.top = (dropBefore ? rect.top : rect.bottom) - mainRect.top
  dropIndicator.visible = true
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
  e.preventDefault()
}

function onEditableDragLeave() {
  dropTargetIndex = -1
  dropIndicator.visible = false
}

function onEditableDrop(e: DragEvent) {
  if (dragFromIndex < 0 || !editor) return
  e.preventDefault()

  const from = dragFromIndex
  const target = dropTargetIndex
  const before = dropBefore
  dragFromIndex = -1
  dropTargetIndex = -1
  dropIndicator.visible = false
  if (target < 0) return

  const level = headingLevelOf(editor.children[from])
  const end = level ? sectionEndOfBlock(from, level) : from + 1
  const count = end - from
  const p = before ? target : target + 1
  if (p >= from && p <= end) return

  editor.withoutNormalizing(() => {
    if (p > end) {
      // 向后移：逐块把首块插到目标点；to 为移除后的下标，随插入位置逐次后移
      const to = p - count
      for (let i = 0; i < count; i++) {
        editor.moveNodes({ at: [from], to: [to + i] })
      }
    } else {
      // 向前移：块依次前移，落点随已移入的块数顺延
      for (let i = 0; i < count; i++) {
        editor.moveNodes({ at: [from + i], to: [p + i] })
      }
    }
  })
}

/*
  getEditableContainer() 返回的是 .w-e-text-container 外层容器（其子节点只有 .w-e-scroll），
  真正的可编辑区是内部的 [data-slate-editor]，各块级节点才是它的直接子元素。
  取错节点会导致块下标恒为 0、top 取整容器高度，悬浮控件无法跟随段落。
*/
function getEditableEl(): HTMLElement | null {
  if (!editor || !editor.getEditableContainer) return null
  try {
    const container = editor.getEditableContainer()
    const root = ((container && container[0] ? container[0] : container) || null) as HTMLElement | null
    if (!root) return null
    if (root.matches && root.matches('[data-slate-editor]')) return root
    return (root.querySelector && root.querySelector('[data-slate-editor]')) || root
  } catch (e) {
    return null
  }
}

// ---------------- 目录节点功能：收起/展开 ----------------

// 收起/展开（带「收起/展开」提示）：翻转 collapsedKeys 中的 key，
// 展示数据与箭头方向都由它派生，状态永远一致
function handleToggleNode(data: CatalogNode) {
  const key = data.key
  collapsedKeys.value = collapsedKeys.value.includes(key)
    ? collapsedKeys.value.filter((item) => item !== key)
    : [...collapsedKeys.value, key]
}

// 目录整体展开/折叠切换：无收起节点（全部展开）时显示「全部折叠」，反之「全部展开」
const allExpanded = computed(() => collapsedKeys.value.length === 0)

// 面板手动收起/打开：记录用户意图，编辑过程中不再被自动显隐规则覆盖
function collapseCatalogPanel() {
  catalogUserIntent = 'closed'
  catalogVisible.value = false
}

function openCatalogPanel() {
  catalogUserIntent = 'open'
  catalogVisible.value = true
}

function toggleAllCatalog() {
  collapsedKeys.value = allExpanded.value ? collectKeys(catalog.value) : []
}

// ---------------- Markdown 快捷标题 ----------------

// 输入 "# ~ #####" 加空格自动转为对应级别标题（与原记事本行为一致），目录随之实时更新
let mdConverting = false
function handleMarkdownHeader() {
  if (mdConverting) return
  if (!editor || editor.isDisabled() || !editor.selection) return

  const blockIndex = editor.selection.anchor.path[0]
  const node = editor.children[blockIndex]
  if (!node || node.type !== 'paragraph') return

  const text = plainText(node)
  const match = text.match(/^(#{1,5})\s(.*)$/)
  if (!match) return

  const newNode = { type: `header${match[1].length}`, children: [{ text: match[2] }] }
  mdConverting = true
  try {
    editor.withoutNormalizing(() => {
      // 原位替换：新标题插到当前段落之前，再删掉顺延到下一位的原段落
      editor.insertNodes(newNode, { at: [blockIndex] })
      editor.removeNodes({ at: [blockIndex + 1] })
      editor.select({ path: [blockIndex, 0], offset: 0 })
    })
  } catch (e) {
    // 转换失败静默降级，不影响正常输入
  }
  mdConverting = false
}

// ---------------- v-model 透传 ----------------

watch(
  () => props.content,
  (val) => {
    if (val === contentVal.value) return
    // 外部内容变化（切换笔记）：重置目录收起状态、标题折叠状态与面板手动操作标记
    collapsedKeys.value = []
    catalogUserIntent = null
    hiddenSections.clear()
    contentVal.value = val
  }
)

watch(contentVal, (val) => {
  emit('input', val)
})
</script>

<style lang="scss" scoped>
.notepad-editor {
  position: relative;
}

/* 工具栏下方：目录面板 + 正文并排 */
.editor-body {
  position: relative;
  display: flex;
  align-items: stretch;
}

/* 目录收起后的悬浮展开入口：贴左、与标题同行（参考笔记类产品样式） */
.catalog-float-btn {
  position: absolute;
  top: 14px;
  left: 0;
  z-index: 10;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0 6px 6px 0;
  background: #fff;
  box-shadow: 0 2px 8px rgba(31, 45, 61, 0.12);
  font-size: 16px;
  color: #606266;
  cursor: pointer;
  transition: color 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    color: #1890ff;
    box-shadow: 0 2px 12px rgba(24, 144, 255, 0.25);
  }
}

/* 内嵌目录面板：宽度由内联样式控制（可拖拽调整，localStorage 持久化） */
.catalog-panel {
  flex-shrink: 0;
  padding: 16px 12px 24px 20px; /* 底部 24px：滚动到底时与容器底边保持距离 */
  overflow-y: auto;
  border-right: 1px solid #f0f2f5;
  scrollbar-width: none;

  &::-webkit-scrollbar {
    display: none;
  }

  .panel-head {
    display: flex;
    align-items: center;
    // 左右内边距与下方节点行内边距同值，保证图标与节点箭头对齐
    padding: 0 12px 8px;
    margin-bottom: 4px;
    // 参考样式：标题行下加分隔线，与树区隔开
    border-bottom: 1px solid #f0f2f5;
    font-weight: 500;
    font-size: 13px;
    color: #303133;
  }

  .panel-title {
    display: flex;
    align-items: center;
    cursor: pointer;

    &:hover {
      color: #1890ff;
    }

    i {
      margin-right: 6px;
      font-size: 14px;
    }

    .panel-title-svg {
      margin-right: 6px;
      flex-shrink: 0;
    }
  }

  .panel-fold {
    margin-left: auto;
    font-size: 14px;
    color: #909399;
    cursor: pointer;

    &:hover {
      color: #1890ff;
    }
  }

  :deep(.el-tree) {
    background: transparent;
  }

  :deep(.el-tree-node__content) {
    height: 32px;
    margin-bottom: 2px;
    // 左内边距会被 el-tree 的行内缩进样式覆盖，节点对齐量在 .catalog-node 行内样式中统一计算
    padding-right: 10px;
    border-radius: 4px;
  }

  :deep(.el-tree-node__content:hover) {
    background: #f2f3f5;
  }

  // 隐藏 el-tree 默认展开箭头，改用带提示的自定义收起/展开控件
  :deep(.el-tree-node__expand-icon) {
    display: none;
  }

  .catalog-empty {
    padding: 24px 0;
    text-align: center;
    font-size: 12px;
    color: #909399;
  }
}

/* 目录面板与正文之间的拖拽调宽分隔条 */
.catalog-resizer {
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

/* 目录节点：收起展开 / 标题 */
.catalog-node {
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 0;

  .node-toggle {
    flex-shrink: 0;
    margin-right: 4px;
    font-size: 13px;
    color: #606266;
    cursor: pointer;

    &:hover {
      color: #1890ff;
    }
  }

  .node-label {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    font-size: 14px;
    color: #606266;
  }

  // 层级视觉：# 一级最突出，逐级弱化
  &.level-1 .node-label {
    font-weight: 600;
    color: #303133;
  }

  &.level-2 .node-label {
    color: #303133;
  }

  &.level-3 .node-label,
  &.level-4 .node-label {
    font-size: 13px;
    color: #606266;
  }
}

/* 编辑区：左侧 72px 为固定控件区（hover 时标题行「徽标 + 拖拽 折叠」、正文行「+ 拖拽」出现在这里），标题与正文文字同起点 */
.editor-main {
  position: relative;
  flex: 1;
  min-width: 0;
  padding-left: 72px;
}

/* 段落左侧控件组：徽标 / 插入 / 拖拽 / 折叠，水平一行紧挨文字前面 */
.block-ctl-group {
  position: absolute;
  left: 28px;
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 2px;
}

/* 级别徽标（H1~H6，仅标题块显示）：行内左侧悬浮定位 */
.ctl-badge {
  position: absolute;
  z-index: 20;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  background: #f2f3f5;
  font-size: 11px;
  font-weight: 600;
  color: #909399;
  user-select: none;
}

/* 通用控件按钮 */
.ctl-btn {
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  background: #f2f3f5;
  font-size: 13px;
  color: #606266;
  cursor: pointer;

  &:hover {
    background: #e8eaee;
    color: #1890ff;
  }

  // 菜单展开中：「+」保持高亮
  &.is-active {
    background: #e6f4ff;
    color: #1890ff;
  }
}

/* 独立悬浮的控件（折叠箭头）：与控件组同层，按行内坐标定位 */
.ctl-float {
  position: absolute;
  z-index: 20;
}

/* 拖拽手柄：六点 SVG 图标 */
.ctl-drag {
  cursor: grab;

  svg {
    width: 12px;
    height: 12px;
  }
}

/* 块类型菜单：点击「+」展开；max-height 由打开时按视口剩余空间内联计算（见 toggleBlockMenu） */
.block-menu {
  position: absolute;
  z-index: 30;
  min-width: 132px;
  overflow-y: auto;
  padding: 4px 0;
  background: #fff;
  border-radius: 6px;
  box-shadow: 0 4px 16px rgba(31, 45, 61, 0.15);

  .block-menu-item {
    display: flex;
    align-items: center;
    padding: 8px 14px;
    font-size: 13px;
    color: #303133;
    cursor: pointer;

    &:hover {
      background: #f5f7fa;
      color: #1890ff;
    }

    /* 菜单项图标（wangEditor 官方 SVG，fill 继承文字色，hover 同步变蓝） */
    .block-menu-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 16px;
      height: 16px;
      margin-right: 8px;
      flex-shrink: 0;

      svg {
        width: 14px;
        height: 14px;
        fill: currentColor;
      }
    }

    /* 标题级别文字徽标（H1~H4），与正文悬浮 H 徽标同风格 */
    .block-menu-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 24px;
      height: 16px;
      margin-right: 8px;
      padding: 0 3px;
      flex-shrink: 0;
      border-radius: 3px;
      background: #f2f3f5;
      font-size: 10px;
      font-weight: 600;
      color: #909399;
    }
  }
}

/* 拖拽手柄：六点 SVG 图标 */
.ctl-drag {
  cursor: grab;
}

/* 图片上传文件框：仅用于唤起系统选图，不占布局 */
.image-file-input {
  display: none;
}

/* 拖拽落点指示线：与正文左缘对齐 */
.drop-indicator {
  position: absolute;
  left: 72px;
  right: 14px;
  height: 2px;
  z-index: 25;
  background: #1890ff;
  border-radius: 1px;
  pointer-events: none;
}

/* 标题位于工具栏下方、正文上方：左缘与正文文字对齐（编辑区 padding-left 72px，不加额外缩进） */
.edit-title {
  padding: 20px 28px 8px 0;

  .edit-input {
    :deep(input) {
      height: 44px;
      line-height: 44px; /* 容纳 30px 字号，避免聚焦时文字垂直滚动 */
      font-family: PingFang SC, PingFang SC;
      font-weight: 600;
      font-size: 30px;
      color: #303133;
      border: none;
      padding-left: 0;
    }
  }
}

:deep(.w-e-toolbar) {
  border: none;
  padding-left: 16px;
}

:deep(.w-e-text-container) {
  height: var(--height);
  border-top: none;
  margin: 0;
  padding: 0;
  font-size: 15px;
  background: #fff;
}

:deep(.w-e-scroll) {
  overflow-y: auto;
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
}

/*
  可编辑区：必须用 .w-e-text-container [data-slate-editor] 两级选择器，
  否则特异性与 wangEditor 默认样式（padding: 0 10px）相同，会被其覆盖导致左留白失效
*/
:deep(.w-e-text-container [data-slate-editor]) {
  padding: 16px 14px 100px 0;
}

:deep(.w-e-text-container [data-slate-editor] p) {
  /* 段落默认 15px：此前由内联 fontSize mark 提供，改为 CSS 显式声明（内联会残留到转标题后盖掉字号） */
  font-size: 15px;
  line-height: 2;
}

/*
  各级标题字号显式声明（不依赖浏览器 UA 样式，规避项目全局 reset 把标题字号抹平）；
  配合 handleEditorCreated 清理历史 fontSize 内联，保证标题层级清晰且不会"点击后变小"
*/
:deep(.w-e-text-container [data-slate-editor] h1) {
  font-size: 28px;
  font-weight: 600;
}
:deep(.w-e-text-container [data-slate-editor] h2) {
  font-size: 22px;
  font-weight: 600;
}
:deep(.w-e-text-container [data-slate-editor] h3) {
  font-size: 19px;
  font-weight: 600;
}
:deep(.w-e-text-container [data-slate-editor] h4) {
  font-size: 16px;
  font-weight: 600;
}
:deep(.w-e-text-container [data-slate-editor] h5) {
  font-size: 15px;
  font-weight: 600;
}
:deep(.w-e-text-container [data-slate-editor] h6) {
  font-size: 14px;
  font-weight: 600;
}

/*
  标题字号锁定：历史/异步加载的数据里文字可能残留 fontSize 内联（如旧逻辑写入的 15px），
  会盖掉标题字号（表现为"设成 H1 后文字大小不变"）。inherit !important 强制标题内文字
  跟随标题级别字号（标题内不支持手动设字号，属预期行为）
*/
:deep(.w-e-text-container [data-slate-editor] h1 *),
:deep(.w-e-text-container [data-slate-editor] h2 *),
:deep(.w-e-text-container [data-slate-editor] h3 *),
:deep(.w-e-text-container [data-slate-editor] h4 *),
:deep(.w-e-text-container [data-slate-editor] h5 *),
:deep(.w-e-text-container [data-slate-editor] h6 *) {
  font-size: inherit !important;
}

/*
  标题不再额外缩进：与正文同起点（编辑区 padding-left 72px），悬浮控件区「徽标 + 拖拽 折叠」
  都在左侧 0~72px 内，标题文字与内容区左缘对齐
*/

/*
  空态占位：与首行文字/光标严格对齐
  - 水平：编辑区固定控件区 72px + 4px，与正文文字同起点
  - 垂直：slate 上内边距 16px + 段落默认外边距 15px = 31px；行高与字号取段落同值，保证行框完全重合
  */

:deep(.w-e-text-placeholder) {
  left: 76px;
  top: 31px;
  font-size: 15px;
  line-height: 2;
}

/* 编辑器聚焦（出现光标）即隐藏占位，不必等到输入内容；!important 用于覆盖 wangEditor 用 jQuery 写入的行内 display */
:deep(.w-e-text-container:focus-within .w-e-text-placeholder) {
  display: none !important;
}
</style>

<style lang="scss">
/* 块类型菜单的悬浮提示：popper 挂在 body 下，需用非 scoped 样式 */
.block-menu-tip-popper {
  white-space: pre-line;
  line-height: 1.7;
}
</style>
