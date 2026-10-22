<template>
  <div class="tree-box">
    <div class="tree-top">
      <div class="tree-search-row">
        <div class="tree-search">
          <el-input v-model="searchKey" placeholder="搜索" size="small" prefix-icon="el-icon-search" clearable />
        </div>
        <el-tooltip content="刷新" effect="dark" placement="bottom">
          <el-button
            size="small"
            class="action-btn"
            icon="el-icon-refresh"
            @click="handleRefresh"
          />
        </el-tooltip>
      </div>
      <div class="tree-actions">
        <el-dropdown placement="bottom" @command="handleCommand">
          <el-button type="primary" size="small" class="create-btn"> <i class="el-icon-plus"></i> 新 建 </el-button>

          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="note" icon="el-icon-circle-plus">新建笔记</el-dropdown-item>
              <el-dropdown-item command="folder" icon="iconfont iconjishiben-xinjianwenjianjia1"
                >新建文件夹</el-dropdown-item
              >
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
    <!--目录 / 最近笔记 切换（分段式）-->
    <div class="tree-seg">
      <span class="seg-item" :class="{ active: !recentlyShow }" @click="handleTabClick({ name: 'tree' })">目录</span>
      <span class="seg-item" :class="{ active: recentlyShow }" @click="handleTabClick({ name: 'recently' })"
        >最近笔记</span
      >
    </div>
    <div class="tree-scroll" v-height v-loading="searching">
      <el-scrollbar style="height: 100%" class="reset-scroll-bar">
        <div v-show="!recentlyShow" class="main-box" tabindex="1" :class="focusShow ? 'focus' : ''" @click="handleClick(1)">
          <span class="main-box-label">全部笔记</span>
          <span class="add-wrap" @click.stop>
            <el-tooltip content="新建" placement="bottom">
              <el-dropdown trigger="click" @command="handleCommand">
                <i class="el-icon-plus add-icon" />
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="note" icon="el-icon-document">新建笔记</el-dropdown-item>
                    <el-dropdown-item command="folder" icon="el-icon-folder-add">新建文件夹</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </el-tooltip>
          </span>
          <span class="sort-wrap" @click.stop>
            <el-tooltip content="排序方式" placement="bottom">
              <el-dropdown trigger="click" placement="bottom-start" @command="handleSortCommand">
                <i class="el-icon-sort sort-icon" />
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="time" class="sort-item">
                      <div class="sort-row">
                        <span :class="{ 'sort-on': sortType === 'time' }">修改时间</span>
                        <span class="sort-dir" @click.stop>
                          <el-tooltip :content="dirTooltip('time', 'asc')" placement="bottom">
                            <i
                              class="el-icon-top"
                              :class="{ 'dir-on': sortType === 'time' && sortOrder === 'asc' }"
                              @click.stop="setSortOrder('time', 'asc')"
                            />
                          </el-tooltip>
                          <el-tooltip :content="dirTooltip('time', 'desc')" placement="bottom">
                            <i
                              class="el-icon-bottom"
                              :class="{ 'dir-on': sortType === 'time' && sortOrder === 'desc' }"
                              @click.stop="setSortOrder('time', 'desc')"
                            />
                          </el-tooltip>
                        </span>
                      </div>
                    </el-dropdown-item>
                    <el-dropdown-item command="create" class="sort-item">
                      <div class="sort-row">
                        <span :class="{ 'sort-on': sortType === 'create' }">创建时间</span>
                        <span class="sort-dir" @click.stop>
                          <el-tooltip :content="dirTooltip('create', 'asc')" placement="bottom">
                            <i
                              class="el-icon-top"
                              :class="{ 'dir-on': sortType === 'create' && sortOrder === 'asc' }"
                              @click.stop="setSortOrder('create', 'asc')"
                            />
                          </el-tooltip>
                          <el-tooltip :content="dirTooltip('create', 'desc')" placement="bottom">
                            <i
                              class="el-icon-bottom"
                              :class="{ 'dir-on': sortType === 'create' && sortOrder === 'desc' }"
                              @click.stop="setSortOrder('create', 'desc')"
                            />
                          </el-tooltip>
                        </span>
                      </div>
                    </el-dropdown-item>
                    <el-dropdown-item command="name" class="sort-item">
                      <div class="sort-row">
                        <span :class="{ 'sort-on': sortType === 'name' }">文件名称</span>
                        <span class="sort-dir" @click.stop>
                          <el-tooltip :content="dirTooltip('name', 'asc')" placement="bottom">
                            <i
                              class="el-icon-top"
                              :class="{ 'dir-on': sortType === 'name' && sortOrder === 'asc' }"
                              @click.stop="setSortOrder('name', 'asc')"
                            />
                          </el-tooltip>
                          <el-tooltip :content="dirTooltip('name', 'desc')" placement="bottom">
                            <i
                              class="el-icon-bottom"
                              :class="{ 'dir-on': sortType === 'name' && sortOrder === 'desc' }"
                              @click.stop="setSortOrder('name', 'desc')"
                            />
                          </el-tooltip>
                        </span>
                      </div>
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </el-tooltip>
          </span>
        </div>
        <el-tree
          ref="tree"
          :data="displayTreeData"
          :props="defaultProps"
          :highlight-current="true"
          :default-expand-all="false"
          :expand-on-click-node="true"
          node-key="key"
          :indent="16"
          :default-expanded-keys="defaultExpandedKeys"
          @node-click="handleClick"
          @node-contextmenu="handleNodeContextmenu"
          @node-expand="handleNodeExpand"
          @node-collapse="handleNodeCollapse"
        >
          <template #default="{ node, data }">
            <div class="custom-tree">
              <div class="custom-tree-node">
                <i v-if="data.type !== 'note'" class="tree-icon iconfont iconwenjianjia1 folder-icon" />
                <el-tooltip v-else :content="data.name" placement="bottom">
                  <i class="tree-icon el-icon-tickets note-icon" />
                </el-tooltip>
                <span class="over-text flex-box-title" :title="data.name">{{ data.name }}</span>
              </div>

              <!-- 文件夹行内快速新建：下拉选择新建笔记 / 新建文件夹 -->
              <el-dropdown
                v-if="data.type !== 'note'"
                class="quick-add"
                trigger="click"
                placement="bottom-start"
                @command="(command) => handleQuickCommand(command, data)"
              >
                <el-tooltip content="新建" placement="bottom">
                  <i class="el-icon-plus" @click.stop />
                </el-tooltip>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="note" icon="el-icon-document">新建笔记</el-dropdown-item>
                    <el-dropdown-item command="folder" icon="el-icon-folder-add">新建文件夹</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </template>
        </el-tree>
      </el-scrollbar>
    </div>
    <!-- 节点右键操作菜单：笔记（移动到/删除）、文件夹（编辑/删除） -->
    <div
      v-show="contextMenu.visible"
      class="node-context-menu"
      :style="{ left: `${contextMenu.x}px`, top: `${contextMenu.y}px` }"
    >
      <template v-if="contextMenu.type === 'note'">
        <div class="context-item" @click="onContextCommand('move')">移动到</div>
        <div class="context-item" @click="onContextCommand('delete')">删除</div>
      </template>
      <template v-else>
        <div class="context-item" @click="onContextCommand('edit')">编辑</div>
        <div class="context-item" @click="onContextCommand('delete')">删除</div>
      </template>
    </div>
    <!-- 通用弹窗表单   -->
    <dialogForm ref="dialogForm" :roles-config="rolesConfig" :form-data="formBoxConfig" @isOk="menuList()" />
  </div>
</template>

<script>
import { modalSure } from '@/libs/public'
import {
  memorialCateCreateApi,
  memorialCateDeleteApi,
  memorialCateEditApi,
  memorialCateListApi,
  memorialGroupApi,
  memorialListApi
} from '@/api/user'
export default {
  name: 'MenuTree',
  components: {
    dialogForm: () => import('./dialog-form')
  },
  props: {},
  data() {
    return {
      defaultProps: {
        children: 'children',
        label: 'name'
      },
      focusShow: false,
      recentlyShow: false, // 默认显示目录
      searchKey: '', // 侧栏搜索关键字：目录模式搜标题+正文（接口），最近模式搜标题+内容摘要（本地）
      searchNoteList: [], // 内容搜索命中的笔记列表（目录模式，接口返回）
      searching: false, // 内容搜索请求进行中
      lastSearchKw: '', // 已完成接口搜索的关键词（结果与关键词对齐，避免过期结果串显）
      searchTimer: null,
      treeData: [],
      recentlyData: [], // 最近使用笔记（平铺节点，替代中间列表直接在树中展示）
      cateTree: [], // 文件夹树（不含笔记）
      noteList: [], // 全量笔记，挂树时按 pid 归位
      sortType: 'time', // 笔记排序维度：time 修改时间 / create 创建时间 / name 文件名称
      sortOrder: 'desc', // 排序方向：desc 降序 / asc 升序（默认修改时间倒序）
      defaultExpandedKeys: [], // 默认展开的目录 key 集：随打开笔记/手动展开维护，树重建后自动恢复
      pendingLocateKey: '', // 树数据未就绪时挂起的定位 key（menuList 完成后补展开与高亮）
      selectedKey: '', // 当前高亮节点 key（cate-x / note-x）
      contextMenu: { visible: false, x: 0, y: 0, type: '', node: null }, // 节点右键菜单
      formBoxConfig: {},
      id: 0,
      nodeIndex: null,
      rolesConfig: [],
      memoricalCount: 0
    }
  },
  computed: {
    // 搜索过滤：
    // - 最近模式：本地匹配标题与内容摘要
    // - 目录模式：本地匹配标题；正文搜索走接口（防抖），命中笔记挂目录骨架展示
    displayTreeData() {
      const source = this.recentlyShow ? this.recentlyData : this.treeData
      const kw = this.searchKey.trim()
      if (!kw) return source

      if (this.recentlyShow) {
        const lkw = kw.toLowerCase()
        return this.filterTree(source, (node) => {
          return (
            String(node.name || '').toLowerCase().includes(lkw) ||
            String(node.content || '').toLowerCase().includes(lkw)
          )
        })
      }

      // 接口结果未返回或关键词已变化：先按标题本地过滤过渡
      if (this.lastSearchKw !== kw) return this.filterTree(source, (node) => this.titleHit(node, kw))
      return this.buildSearchTree(this.searchNoteList)
    }
  },
  watch: {
    // 搜索结果更新后展开全部命中层级，避免结果藏在折叠目录里
    displayTreeData() {
      this.$nextTick(() => {
        const nodesMap = this.$refs.tree && this.$refs.tree.store.nodesMap
        nodesMap &&
          Object.values(nodesMap).forEach((node) => {
            node.expanded = true
          })
      })
    },
    searchKey(val) {
      // 目录模式：防抖后走接口按「标题 + 正文」搜索；最近模式与清空场景用本地数据
      clearTimeout(this.searchTimer)
      const kw = val.trim()
      if (this.recentlyShow || !kw) {
        this.searching = false
        this.lastSearchKw = kw
        this.searchNoteList = []
        return
      }
      this.searching = true
      this.searchTimer = setTimeout(async () => {
        try {
          const res = await memorialListApi({ page: 1, limit: 9999, title: kw })
          // 关键词已变化：丢弃过期结果
          if (this.searchKey.trim() !== kw) return
          this.searchNoteList = (res.data && res.data.list) || []
          this.lastSearchKw = kw
        } catch (e) {
          this.searchNoteList = []
        } finally {
          if (this.searchKey.trim() === kw) this.searching = false
        }
      }, 300)
    },
    treeData: {
      handler() {
        this.$nextTick(() => {
          // 仅恢复选中高亮；展开链由 defaultExpandedKeys 机制在节点创建时自动展开
          this.$refs.tree.setCurrentKey(this.selectedKey || null)
        })
      },
      deep: true
    }
  },
  mounted() {
    this.menuList()
    // 预载最近列表数据；完成后通知父组件默认打开最近一条笔记（左侧分段默认停在「目录」）
    this.loadRecently().then(() => this.$emit('onRecently'))
    // 点击任意处 / 右键其他位置时关闭右键菜单
    document.addEventListener('click', this.closeContextMenu)
    document.addEventListener('contextmenu', this.closeContextMenu)
  },
  beforeDestroy() {
    document.removeEventListener('click', this.closeContextMenu)
    document.removeEventListener('contextmenu', this.closeContextMenu)
  },
  methods: {
    // 刷新树列表
    async handleRefresh() {
      await this.menuList()
      this.$message.success('刷新成功')
    },
    // 新建按钮
    handleCommand(command) {
      switch (command) {
        case 'note':
          var data = {
            // 新建笔记默认标题（产品要求固定文案，不走 i18n）
            title: '无标题笔记',
            content: this.$t('calendar.placeholder04'),
            pid: this.id == '' ? 0 : this.id
          }

          if (this.recentlyShow) {
            this.$emit('createNote', data)
            this.recentlyShow = false
            this.focusShow = true
            this.handleClick(1)
          } else {
            this.$emit('createNote', data)
          }

          break
        case 'folder':
          this.addCate(2)
          break
      }
    },

    // 侧栏 tab 切换：目录 ↔ 最近笔记
    handleTabClick(tab) {
      if (tab.name === 'recently') {
        if (!this.recentlyShow) this.onRecently()
      } else if (this.recentlyShow) {
        this.recentlyShow = false
        this.id = ''
        this.$emit('frameId', this.id)
        // 切回目录：保持当前打开笔记的定位（打开笔记时已登记其目录链为展开）
        if (this.selectedKey) {
          this.focusShow = false
          this.$nextTick(() => this.$refs.tree.setCurrentKey(this.selectedKey))
        } else {
          this.focusShow = true
          this.$refs.tree.setCurrentKey()
        }
      }
    },
    // 最近使用：左侧树切换为最近笔记平铺列表
    async onRecently() {
      this.focusShow = false
      this.recentlyShow = true
      this.searchKey = ''
      this.id = '' // 最近模式无文件夹落点，新建笔记落根目录
      this.selectedKey = ''
      this.$refs.tree.setCurrentKey()
      await this.loadRecently()
      this.$emit('onRecently')
    },
    // 拉取最近使用并平铺成笔记节点
    async loadRecently() {
      const res = await memorialGroupApi({ title: '' })
      const nodes = []
      ;((res.data && res.data.list) || []).forEach((group) => {
        ;(group.data || []).forEach((n) => {
          const node = { ...n, key: `note-${n.id}`, name: n.title, type: 'note' }
          delete node.children
          nodes.push(node)
        })
      })
      this.recentlyData = nodes
    },
    // tree节点点击：笔记→打开笔记；文件夹/根→切换选中
    handleClick(data, node) {
      if (data && data.type === 'note') {
        this.focusShow = false
        this.id = data.pid
        this.selectedKey = data.key
        this.$refs.tree.setCurrentKey(data.key)
        this.$emit('selectNote', data, this.getBreadcrumbByPid(data.pid))
        return
      }
      // 仅文件夹/根节点点击才退出最近模式，最近列表内点笔记保持当前视图
      this.recentlyShow = false
      if (data == 1) {
        this.focusShow = true
        this.id = ''
        this.selectedKey = ''
        this.$refs.tree.setCurrentKey()
      } else {
        this.focusShow = false
        this.id = data.id
        this.selectedKey = data.key
      }

      this.$emit('frameId', this.id)
    },
    // 依据 pid 在文件夹树中回溯面包屑链（跳过笔记节点，避免与文件夹 id 撞号）
    getBreadcrumbByPid(pid) {
      const map = {}
      const walk = (nodes) => {
        ;(nodes || []).forEach((n) => {
          // 笔记是叶子且 id 与文件夹分属两表，直接跳过
          if (n.type === 'note') return
          map[n.id] = { name: n.name, pid: n.pid }
          walk(n.children || [])
        })
      }
      walk(this.treeData)
      const chain = []
      let cur = map[pid]
      while (cur) {
        chain.unshift(cur.name)
        cur = map[cur.pid]
      }
      return chain
    },
    // 外部（如新建笔记后）同步树高亮，并展开其所在层级链
    // 定位笔记：选中并自动展开其所在目录链；树数据未就绪时挂起，待 menuList 完成后补定位
    setCurrentId(id) {
      const key = id ? `note-${id}` : ''
      this.selectedKey = key
      // 无论当前处于最近模式还是目录模式，都提前登记目录链展开，切换视图后定位不丢
      this.collectParentChainKeys(id).forEach((k) => this.expandNodeKey(k))
      this.$nextTick(() => {
        const node = key && this.$refs.tree.getNode(key)
        if (!node) {
          // 首次加载竞态：目录树数据尚未构建，挂起待 menuList 完成后补展开与高亮
          if (key) this.pendingLocateKey = key
          this.$refs.tree.setCurrentKey(null)
          return
        }
        this.expandParentChain(node)
        this.$refs.tree.setCurrentKey(key)
      })
    },
    // 沿父级收集目录链并登记为默认展开，保证树数据重建后仍保持展开
    expandParentChain(node) {
      let parent = node && node.parent
      while (parent) {
        this.expandNodeKey(parent.key)
        parent = parent.parent
      }
    },
    // 在目录树数据中查找笔记节点，返回其父级目录 key 链（不依赖树当前视图模式）
    collectParentChainKeys(id) {
      const key = `note-${id}`
      const result = []
      const walk = (nodes, chain) => {
        for (const n of nodes || []) {
          if (n.key === key) {
            result.push(...chain)
            return true
          }
          if (n.children && n.children.length && walk(n.children, [...chain, n.key])) return true
        }
        return false
      }
      walk(this.treeData, [])
      return result
    },
    expandNodeKey(key) {
      if (!key) return
      if (!this.defaultExpandedKeys.includes(key)) this.defaultExpandedKeys.push(key)
      // 已存在的节点立即展开（default-expanded-keys 仅对新建节点生效）
      const node = this.$refs.tree && this.$refs.tree.store && this.$refs.tree.store.nodesMap[key]
      if (node) node.expanded = true
    },
    // 手动展开/折叠时同步默认展开集，树重建后与手动操作保持一致
    handleNodeExpand(data, node) {
      this.expandNodeKey(node.key)
    },
    handleNodeCollapse(data, node) {
      const index = this.defaultExpandedKeys.indexOf(node.key)
      if (index > -1) this.defaultExpandedKeys.splice(index, 1)
    },
    // 增量同步单篇笔记标题（保存后调用），避免整树重建导致展开/滚动状态丢失
    updateNoteName(id, name) {
      if (!id) return
      const key = `note-${id}`
      const patch = (nodes) => {
        ;(nodes || []).forEach((n) => {
          if (n.key === key && n.name !== name) n.name = name
          patch(n.children)
        })
      }
      patch(this.treeData)
      patch(this.recentlyData)
    },
    // 节点右键：笔记展示（移动到/删除），文件夹展示（编辑/删除）
    handleNodeContextmenu(event, data) {
      if (data.type !== 'note' && data.id == '') return
      event.preventDefault()
      // 阻止冒泡到 document 的关闭监听，避免刚打开即被关闭
      event.stopPropagation()
      const type = data.type === 'note' ? 'note' : 'folder'
      this.contextMenu = { visible: true, x: event.clientX, y: event.clientY, type, node: data }
    },
    closeContextMenu() {
      this.contextMenu.visible = false
    },
    // 右键菜单命令：按节点类型分发
    onContextCommand(command) {
      const node = this.contextMenu.node
      this.closeContextMenu()
      if (!node) return
      if (node.type === 'note') {
        this.$emit('noteAction', { action: command, note: node })
        return
      }
      if (command === 'edit') {
        this.addEdit(node)
      } else if (command === 'delete') {
        this.handleDelete(node)
      }
    },
    // 文件夹行内快速新建：下拉选择新建笔记 / 新建文件夹（子文件夹）
    handleQuickCommand(command, data) {
      if (command === 'folder') {
        this.addCate(1, data)
        return
      }
      this.quickCreate(data)
    },
    // 行内快速新建笔记：文件夹落其下，笔记落其同级（所在文件夹）
    quickCreate(data) {
      this.$emit('createNote', {
        title: '无标题笔记',
        content: this.$t('calendar.placeholder04'),
        pid: data.type === 'note' ? data.pid : data.id
      })
    },
    async menuList() {
      const [cateRes, noteRes] = await Promise.all([
        memorialCateListApi(),
        // pid 不传返回全部笔记，前端按文件夹挂到树上
        memorialListApi({ page: 1, limit: 9999 })
      ])
      this.cateTree = (cateRes.data && cateRes.data.tree) || []
      this.memoricalCount = (cateRes.data && cateRes.data.total) || 0
      this.noteList = (noteRes.data && noteRes.data.list) || []
      this.buildTree()
      // 最近模式下同步刷新最近列表视图（保存/删除/移动后由父组件统一走这里）
      if (this.recentlyShow) await this.loadRecently()
      // 树数据就绪：处理挂起的定位（首次加载竞态），补展开目录链与高亮
      if (this.pendingLocateKey) {
        const key = this.pendingLocateKey
        this.pendingLocateKey = ''
        this.$nextTick(() => {
          const node = this.$refs.tree.getNode(key)
          if (!node) return
          this.expandParentChain(node)
          this.$refs.tree.setCurrentKey(key)
        })
      }
    },
    // 文件夹树 + 笔记合并：笔记作为叶子挂在所属文件夹下
    buildTree() {
      const group = {}
      this.noteList.forEach((n) => {
        const key = n.pid || 0
        ;(group[key] = group[key] || []).push(n)
      })
      const toNoteNode = (n) => {
        // 笔记是叶子：清掉后端可能下发的 children，避免出现无意义的展开箭头
        const node = { ...n, key: `note-${n.id}`, name: n.title, type: 'note' }
        delete node.children
        return node
      }
      const attach = (nodes) => {
        ;(nodes || []).forEach((node) => {
          node.key = `cate-${node.id}`
          const children = node.children || []
          attach(children)
          const notes = ((group[node.id] || [])).map(toNoteNode).sort((a, b) => this.noteSorter(a, b))
          this.$set(node, 'children', [...children, ...notes])
        })
      }
      const tree = JSON.parse(JSON.stringify(this.cateTree))
      attach(tree)
      const rootNotes = (group[0] || []).map(toNoteNode).sort((a, b) => this.noteSorter(a, b))
      this.treeData = [...tree, ...rootNotes]
    },
    // 笔记排序：按维度比较，方向由 sortOrder 控制（desc = 默认结果，asc 取反）
    noteSorter(a, b) {
      let result
      if (this.sortType === 'name') {
        result = String(a.name).localeCompare(String(b.name), 'zh-Hans-CN')
      } else if (this.sortType === 'create') {
        result = new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      } else {
        result = new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()
      }
      return this.sortOrder === 'asc' ? -result : result
    },
    // 递归过滤：命中判定返回 true 的笔记保留，文件夹保留命中的子链
    filterTree(nodes, hit) {
      const result = []
      ;(nodes || []).forEach((node) => {
        if (node.type === 'note') {
          hit(node) && result.push(node)
        } else {
          const children = this.filterTree(node.children, hit)
          children.length && result.push({ ...node, children })
        }
      })
      return result
    },
    // 标题命中（目录模式接口搜索返回前的本地过渡）
    titleHit(node, kw) {
      return String(node.name || '').toLowerCase().includes(String(kw).toLowerCase())
    },
    // 内容搜索：把接口命中笔记挂到目录骨架（保留命中目录链与名称命中的目录），根目录笔记挂顶层
    buildSearchTree(notes) {
      const kw = this.searchKey.trim().toLowerCase()
      const hitNotes = notes.map((n) => ({ ...n, key: `note-${n.id}`, name: n.title, type: 'note' }))
      const pidGroups = {}
      hitNotes.forEach((n) => {
        const pid = String(n.pid ?? 0)
        ;(pidGroups[pid] = pidGroups[pid] || []).push(n)
      })
      const walk = (nodes) => {
        const result = []
        ;(nodes || []).forEach((node) => {
          const children = walk(node.children || [])
          const hits = pidGroups[String(node.id)] || []
          const nameHit = kw && String(node.name || '').toLowerCase().includes(kw)
          if (hits.length) {
            result.push({ ...node, key: `cate-${node.id}`, children: [...children, ...hits] })
          } else if (children.length || nameHit) {
            result.push({ ...node, key: `cate-${node.id}`, children })
          }
        })
        return result
      }
      return [...walk(this.cateTree), ...(pidGroups['0'] || [])]
    },
    handleSortCommand(command) {
      if (this.sortType === command) return
      this.sortType = command
      this.resortTree()
    },
    // 切换排序方向（菜单内常驻升降序按钮，点击即选中该维度并应用方向）
    setSortOrder(command, order) {
      if (this.sortType === command && this.sortOrder === order) return
      this.sortType = command
      this.sortOrder = order
      this.resortTree()
    },
    // 排序切换：重建排序数据，但恢复展开状态 / 选中 / 滚动位置，树不跳变
    resortTree() {
      const tree = this.$refs.tree
      if (!tree || !tree.store) {
        this.buildTree()
        return
      }
      // 记录各节点展开状态与滚动位置
      const expandedKeys = {}
      Object.values(tree.store.nodesMap).forEach((node) => {
        if (node.expanded && node.key) expandedKeys[node.key] = true
      })
      const wrap = this.$el.querySelector('.el-scrollbar__wrap')
      const scrollTop = wrap ? wrap.scrollTop : 0
      this.buildTree()
      this.$nextTick(() => {
        Object.values(tree.store.nodesMap).forEach((node) => {
          node.expanded = !!expandedKeys[node.key]
        })
        tree.setCurrentKey(this.selectedKey || null)
        if (wrap) wrap.scrollTop = scrollTop
      })
    },
    // 升降序按钮的悬浮提示文案
    dirTooltip(command, order) {
      if (command === 'name') return order === 'asc' ? '首字母 A-Z' : '首字母 Z-A'
      return order === 'asc' ? '由远到近' : '由近到远'
    },
    // 添加分类
    addCate(type, data) {
      const id = type === 1 ? data.id : 0
      memorialCateCreateApi(id).then((res) => {
        this.formBoxConfig = {
          title: res.data.title,
          width: '500px',
          method: res.data.method,
          action: res.data.action.substr(4)
        }
        this.rolesConfig = res.data.rule
        this.$refs.dialogForm.open()
      })
    },
    // 修改分类
    addEdit(data) {
      memorialCateEditApi(data.id).then((res) => {
        this.formBoxConfig = {
          title: res.data.title,
          width: '500px',
          method: res.data.method,
          action: res.data.action.substr(4)
        }
        this.rolesConfig = res.data.rule
        this.$refs.dialogForm.open()
      })
    },
    // 删除
    handleDelete(item) {
      modalSure(this.$t('calendar.placeholder08')).then(() => {
        memorialCateDeleteApi(item.id).then((res) => {
          this.menuList()
        })
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.tree-top {
  padding-top: 16px;
}
/* 搜索框 + 刷新 同行 */
.tree-search-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 10px;

  .tree-search {
    flex: 1;
    ::v-deep .el-input__inner {
      background-color: #f2f3f5;
      border: none;
      border-radius: 6px;
    }
  }
}
::v-deep .action-btn {
  width: 34px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: #606266;

  &:hover {
    color: #1890ff;
    border-color: #b8d4f8;
    background: #f1f9ff;
  }
  i {
    font-size: 14px;
  }
}
/* 新建按钮独占一行 */
.tree-actions {
  display: flex;
  align-items: center;
  padding: 8px 10px 0;

  .el-dropdown {
    flex: 1;
  }
  .create-btn {
    width: 100%;
    height: 32px;
    border-radius: 6px;
    font-weight: 500;
  }
}
.main-box {
  cursor: pointer;
  height: 44px;
  margin: 0; // scrollbar view 已有 10px 内边距（全局 element-ui.scss），与「目录/最近笔记」分段条对齐
  display: flex;
  align-items: center;
  padding-left: 10px;
  border-radius: 6px;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;
  // 顶部标题风格：加粗深色
  font-size: 15px;
  font-family: PingFang SC-Regular, PingFang SC;
  font-weight: 600;
  color: #303133;
}
.main-box-label {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
/* 目录 / 最近笔记 分段切换 */
.tree-seg {
  display: flex;
  margin: 12px 10px 8px;
  padding: 3px;
  border-radius: 8px;
  background: #eceef1;

  .seg-item {
    flex: 1;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 13px;
    color: #606266;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;

    &.active {
      background: #fff;
      font-weight: 600;
      color: #303133;
      box-shadow: 0 1px 4px rgba(31, 45, 61, 0.1);
    }
  }
}
.sort-wrap {
  display: inline-flex;
  align-items: center;
  margin-right: 8px;
}
.add-wrap {
  display: inline-flex;
  align-items: center;
  margin-right: 8px;
}
.add-icon {
  font-size: 14px;
  color: #909399;
  cursor: pointer;

  &:hover {
    color: #1890ff;
  }
}
.sort-icon {
  font-size: 14px;
  color: #909399;
  cursor: pointer;

  &:hover {
    color: #1890ff;
  }
}
.sort-on {
  color: #1890ff;
  font-weight: 600;
}
// 排序菜单：行 hover 通栏浅灰；选中维度文字蓝色
.sort-item {
  &:hover {
    background-color: #f2f3f5;
    color: #303133;
  }
}
// 排序菜单行：维度名称 + 选中项的升降序切换
.sort-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-width: 120px;

  .sort-dir {
    display: inline-flex;
    align-items: center;
    gap: 2px;

    i {
      padding: 3px 5px;
      font-size: 12px;
      color: #606266;
      background: #eef0f2;
      border-radius: 4px;
      cursor: pointer;

      // 选中方向：蓝色图标
      &.dir-on {
        color: #1890ff;
      }

      // 悬浮：深灰底白图标
      &:hover {
        color: #fff;
        background: #4e5259;
      }
    }
  }
}
.note-icon,
.folder-icon {
  color: #606266;
}
.quick-add {
  display: none;
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  font-size: 14px;
  color: #909399;
  cursor: pointer;

  &:hover {
    color: #1890ff;
  }
}
::v-deep .el-tree-node__content:hover .quick-add {
  display: inline-block;
}
// 层级引导竖线：浅灰，与父级展开箭头中心对齐（left = (父层级-1)×24 + 12），从箭头正中下垂
::v-deep .el-tree-node__children {
  position: relative;

  &::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 12px; // 兜底：一级父节点 0×16+12
    width: 1px;
    background: #e5e7eb;
    // 置于节点行背景之上：hover/选中行的竖线段不被遮挡
    z-index: 1;
  }
}

::v-deep .el-tree > .el-tree-node > .el-tree-node__children::before {
  left: 12px; // 一级父节点：0×16+12
}

::v-deep
  .el-tree
  > .el-tree-node
  > .el-tree-node__children
  > .el-tree-node
  > .el-tree-node__children::before {
  left: 28px; // 二级父节点：1×16+12
}

::v-deep
  .el-tree
  > .el-tree-node
  > .el-tree-node__children
  > .el-tree-node
  > .el-tree-node__children
  > .el-tree-node
  > .el-tree-node__children::before {
  left: 44px; // 三级父节点：2×16+12
}

::v-deep
  .el-tree
  > .el-tree-node
  > .el-tree-node__children
  > .el-tree-node
  > .el-tree-node__children
  > .el-tree-node
  > .el-tree-node__children
  > .el-tree-node
  > .el-tree-node__children::before {
  left: 60px; // 四级父节点：3×16+12
}
// 选中路径高亮：当前选中节点的父级连接线显示为蓝色
::v-deep .el-tree-node:has(> .el-tree-node__children > .el-tree-node.is-current) > .el-tree-node__children::before {
  background: rgba(24, 144, 255, 0.45);
}
// 展开箭头：浅灰小三角；叶子节点（笔记）不显示，保持透明占位以对齐文件夹图标
::v-deep .el-tree-node__expand-icon {
  color: #8a919f;
  font-size: 12px;
}
::v-deep .el-tree-node__expand-icon.is-leaf {
  color: transparent;
  // 收窄叶子占位：笔记比文件夹更靠左一点
  display: inline-block;
  width: 16px;
  padding: 6px 2px;
}
// 滑过高亮：浅灰背景（与树底色区分明显）
::v-deep .el-tree-node__content:hover {
  background-color: #e9ebee;
}
.main-box:hover {
  background-color: #e9ebee;
}

.focus {
  // 选中态：浅蓝底 + 主色文字加粗
  background: #e8f4ff !important;
  font-weight: 600;
  font-size: 14px;
  color: #1890ff;
}
.recently {
  margin: 6px 10px 0;
  padding-left: 10px;
  text-align: left;
  cursor: pointer;
  height: 44px;
  border-radius: 6px;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;
  font-size: 14px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  color: #303133;
  display: flex;
  align-items: center;
  .iconshijian {
    font-size: 13px;
    margin-right: 9px;
  }
}
.recently:hover {
  background-color: #e9ebee;
}
.activeColor {
  // 选中态：浅蓝底 + 主色文字加粗
  font-weight: 600;
  font-size: 14px;
  color: #1890ff;
  background: #e8f4ff !important;
}
.el-dropdown-menu__item {
  font-size: 14px;
  color: #303133; /* 菜单文字黑色，图标继承同色 */
  line-height: 40px;
  min-width: 170px;
}

.tree-icon {
  margin-right: 4px; // 图标与文字保持适中间距
  margin-left: -4px;
  font-size: 14px;
}
.iconjishiben-xinjianwenjianjia1 {
  display: inline-block;
  font-size: 17px !important;
}
::v-deep .el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content {
  background: #e8f4ff;
}
::v-deep .el-tree-node__content {
  position: relative;
  height: 44px;
  padding-right: 15px;
  border-radius: 4px;
  font-family: PingFang SC, PingFang SC;
  font-weight: 400;
  font-size: 14px;
  color: #303133;
}

::v-deep .el-tree--highlight-current .el-tree-node.is-current > .el-tree-node__content .custom-tree-node {
  font-family: PingFang SC, PingFang SC;
  // 选中态：蓝底 + 主色文字加粗
  font-weight: 600;
  font-size: 14px;
  color: #1890ff;
}

::v-deep .el-tree-node > .el-tree-node__children {
  overflow: inherit;
}
.custom-tree {
  width: 100%;
  .custom-tree-node {
    width: calc(100% - 20px);
    // flex 化：标题成为受约束的 flex 项；不在容器上裁剪，避免负外边距图标左缘被切
    display: flex;
    align-items: center;
    min-width: 0;

    .over-text {
      flex: 1;
      min-width: 0;
    }
  }
}
.item-list {
  display: flex !important;
  align-items: center;
}
.tree-box {
  height: 100vh;
  background-color: #f7f8fa;
  ::v-deep .el-tree {
    // 水平不留白：节点行背景与「全部笔记」行、分段条对齐（容器统一控制缩进）
    // 底部 24px：滚动到底时内容与容器底边保持距离
    background: transparent;
    padding: 4px 0 24px;
  }
  ::v-deep .el-scrollbar__wrap {
    overflow-x: hidden;
  }
  .flex-box {
    display: flex;
    align-items: center;
    width: 100%;

    .flex-box-title {
      font-size: 14px;
    }
    span:first-of-type {
      display: inline-block;
      max-width: 66%;
    }
  }
}
// 节点右键操作菜单
.node-context-menu {
  position: fixed;
  z-index: 3000;
  min-width: 120px;
  padding: 4px 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(31, 45, 61, 0.14);

  .context-item {
    padding: 0 16px;
    line-height: 34px;
    font-size: 13px;
    color: #303133;
    cursor: pointer;

    &:hover {
      background-color: #f5f7fa;
      color: #1890ff;
    }
  }
}

::v-deep .scroll-bar .el-scrollbar__view {
  padding-inline: 10px;
}
</style>
