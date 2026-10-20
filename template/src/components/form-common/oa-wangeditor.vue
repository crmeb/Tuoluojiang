<!-- @FileDescription: 富文本组件wangeditorV5 -->
<template>
  <div :class="{ 'train-mode': training }">
    <Toolbar
      v-if="headers && !readOnly"
      :defaultConfig="toolbarConfig"
      :editor="editor"
      :mode="mode"
      class="wangeditor-box"
    />
    <div :style="{ '--width': mainWidth }" class="main" :class="training ? 'train-spac' : ''">
      <Editor
        ref="wang-editor"
        v-model="contentVal"
        :defaultConfig="editorConfig"
        :mode="mode"
        :style="{ '--height': height }"
        @onChange="onChange"
        @onCreated="onCreated"
        :class="{ 'has-border': editorBorder }"
        class="wangeditor-box"
      />
    </div>

    <!-- 表格右键菜单（仅单元格内触发） -->
    <div
      v-show="tableMenu.visible"
      class="table-context-menu"
      :style="{ left: tableMenu.x + 'px', top: tableMenu.y + 'px' }"
      @contextmenu.prevent
    >
      <div
        v-for="item in tableMenu.items"
        :key="item.key"
        class="table-context-menu__item"
        :class="{ 'is-disabled': item.disabled }"
        @click="onTableMenuClick(item)"
      >
        {{ item.label }}
      </div>
    </div>
  </div>
</template>
<script>
import { defineComponent } from 'vue'
import '@wangeditor-next/editor/dist/css/style.css'
import { uploader } from '@/utils/uploadCloud.ts'
import { Editor, Toolbar } from '@wangeditor-next/editor-for-vue'
import { normalizePastedTableHtml } from '@/utils/wangeditor/tablePaste.ts'
import { TOOLBAR_CONFIG } from './oa-wangeditor-config.ts'

export default defineComponent({
  components: {
    Editor,
    Toolbar
  },
  // 声明后 @input 不会再透传到根节点、被编辑区的原生 input 事件误触发
  emits: ['input'],
  props: {
    content: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: '请输入内容...'
    },
    height: {
      type: String,
      default: '400px'
    },
    type: {
      // simple 简约版  notepad 记事本版
      type: String,
      default: 'all'
    },
    headers: {
      type: Boolean,
      default: true
    },
    readOnly: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    },
    mainWidth: {
      type: String,
      default: '100%'
    },
    editorBorder: {
      type: Boolean,
      default: true
    },
    // 员工培训样式需要单独处理
    training: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      editor: null,
      contentVal: this.content,
      toolbarConfig: {},
      editorConfig: { MENU_CONF: {}, placeholder: this.placeholder, readOnly: this.readOnly },
      mode: 'default', // or 'simple'
      editableEl: null,
      globalContextMenuBound: false,
      boundHandlers: {
        docClickOrScroll: null,
        docContextMenu: null
      },
      tableMenu: {
        visible: false,
        x: 0,
        y: 0,
        items: []
      }
    }
  },
  watch: {
    content: function (val) {
      this.contentVal = val
    },
    disabled(val) {
      if (val) {
        this.editor.disable()
      } else {
        this.editor.enable()
      }
    },
    readOnly(val) {
      if (val) {
        this.editor.disable()
      } else {
        this.editor.enable()
      }
    }
  },
  mounted() {
    if (this.content) {
      setTimeout(() => {
        if (!this.editor) return
        this.editor.setHtml(this.content)
        this.contentVal = this.content
        if (this.disabled) {
          this.editor.disable()
        } else {
          this.editor.enable()
        }
      }, 1000)
    }

    // addEventListener 回调里 this 默认不是 Vue 实例，需要手动 bind
    this.boundHandlers.docClickOrScroll = this.hideTableMenu.bind(this)
    this.boundHandlers.docContextMenu = this.onEditorContextMenu.bind(this)

    document.addEventListener('click', this.boundHandlers.docClickOrScroll, true)
    document.addEventListener('scroll', this.boundHandlers.docClickOrScroll, true)

    // 右键菜单：用 document capture 兜底，避免编辑器内部 stopPropagation 导致失效
    document.addEventListener('contextmenu', this.boundHandlers.docContextMenu, true)
    this.globalContextMenuBound = true
  },
  created() {
    let that = this
    that.editorConfig.MENU_CONF['uploadImage'] = {
      customUpload(file, insertFn) {
        let options = {
          way: 2,
          relation_type: '',
          relation_id: 0,
          eid: 0
        }
        uploader(file, 1, options).then((res) => {
          insertFn(res.data.url, '图片上传', res.data.url)
        })
      }
    }

    // 自定义富文本菜单栏 type='simple'简约版本  type='notepad'记事本专用菜单
    if (that.type == 'simple') {
      that.toolbarConfig.toolbarKeys = [
        'headerSelect',
        'bold',
        'italic',
        'insertLink',
        'uploadImage',
        'bulletedList',
        'numberedList',
        'codeBlock',
        'blockquote'
      ]
    } else if (that.type == 'notepad') {
      that.toolbarConfig.toolbarKeys = [
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
            '<svg viewBox="0 0 1024 1024"><path d="M959.877 128l0.123 0.123v767.775l-0.123 0.122H64.102l-0.122-0.122V128.123l0.122-0.123h895.775zM960 64H64C28.795 64 0 92.795 0 128v768c0 35.205 28.795 64 64 64h896c35.205 0 64-28.795 64-64V128c0-35.205-28.795-64-64-64zM832 288.01c0 53.023-42.988 96.01-96.01 96.01s-96.01-42.987-96.01-96.01S682.967 192 735.99 192 832 234.988 832 288.01zM896 832H128V704l224.01-384 256 320h64l224.01-192z"></path></svg>',
          menuKeys: ['insertImage', 'uploadImage']
        },
        'insertTable',
        'codeBlock',
        'divider',
        'insertLink',
        'undo',
        'redo'
      ]
    } else {
      that.toolbarConfig.toolbarKeys = TOOLBAR_CONFIG;
    }
  },
  methods: {
    getValue() {
      return this.editor.getHtml()
    },

    onCreated(editor) {
      this.editor = Object.seal(editor) // 一定要用 Object.seal() ，否则会报错
      this.editor.children.forEach((item) => {
        item.children.forEach((ele) => {
          ele.fontSize = '15px' // 设置默认字号
        })
      })

      // 右键菜单必须绑定到 editable 容器（capture），否则事件可能被编辑器内部拦截
      try {
        const editable = this.editor.getEditableContainer && this.editor.getEditableContainer()
        const realEl = editable && editable[0] ? editable[0] : editable
        if (realEl && realEl.addEventListener) {
          this.editableEl = realEl
        }
      } catch (e) {
        // ignore
      }
    },
    onChange(edit) {
      if (this.disabled) {
        this.editor.disable()
      } else {
        this.editor.enable()
      }
      this.$emit('input', this.contentVal)
    },

    clear() {
      this.contentVal = ''
    },

    hideTableMenu() {
      if (!this.tableMenu.visible) return
      this.tableMenu.visible = false
    },

    // 只在表格单元格内右键展示菜单（capture 绑定在 editable 上）
    onEditorContextMenu(event) {
      if (!this.editor || this.readOnly) return

      const target = event.target
      // 仅处理发生在本组件内的右键（避免影响全站右键）
      if (!this.$el || !this.$el.contains(target)) return

      const cell = target && target.closest ? target.closest('td,th') : null
      if (!cell) return

      event.preventDefault()
      // 尽量避免右键导致编辑器内部清空表格多选
      if (typeof event.stopPropagation === 'function') event.stopPropagation()

      const items = [{ key: '__repairTableHtml', label: '修复表格结构', disabled: false }]

      if (!items.length) return

      this.tableMenu.items = items
      this.tableMenu.x = event.clientX
      this.tableMenu.y = event.clientY
      this.tableMenu.visible = true
    },

    onTableMenuClick(item) {
      if (!this.editor) return
      if (item.disabled) return

      try {
        if (item.key === '__repairTableHtml') {
          const currentHtml = this.editor.getHtml()
          const { html: fixedHtml } = normalizePastedTableHtml(currentHtml)
          this.editor.setHtml(fixedHtml)
          return
        }
      } finally {
        this.hideTableMenu()
      }
    }
  },

  beforeUnmount() {
    const editor = this.editor
    if (editor == null) return
    // 组件销毁时，及时销毁编辑器
    if (this.training) {
      // 由于员工培训具有单独的css样式，销毁组件后容器尚未销毁，会造成页面上下跳动，所以需要延迟销毁
      setTimeout(() => {
        editor.destroy()
      }, 500)
    } else {
      editor.destroy()
    }

    if (this.boundHandlers.docClickOrScroll) {
      document.removeEventListener('click', this.boundHandlers.docClickOrScroll, true)
      document.removeEventListener('scroll', this.boundHandlers.docClickOrScroll, true)
    }
    if (this.globalContextMenuBound) {
      if (this.boundHandlers.docContextMenu) {
        document.removeEventListener('contextmenu', this.boundHandlers.docContextMenu, true)
      }
      this.globalContextMenuBound = false
    }

    if (this.editableEl && this.editableEl.removeEventListener) {
      // 目前不再依赖 editableEl 绑定，仅保留字段以兼容历史逻辑
    }
  }
})
</script>
<style lang="scss" scoped>
:deep(.w-e-toolbar) {
  border: 1px solid #eee;
  border-bottom: none;
}
:deep(.w-e-text-container) {
  /* 使用最小高度避免裁剪底部表格操作条 */
  height: var(--height);
  border-top: none;
  padding: 12px;
  font-size: 15px;

  img {
    width: 60%;
  }
}

:deep(.w-e-text-placeholder) {
  padding: 12px;
}
:deep(.w-e-scroll) {
  overflow-y: auto;
  scrollbar-width: none; /* firefox */
  -ms-overflow-style: none; /* IE 10+ */
}
.main {
  background: #fff;
  width: var(--width);
  margin: 0 auto;
}
.train-spac {
  padding: 56px 155px;
}

.table-context-menu {
  position: fixed;
  z-index: 3000;
  min-width: 160px;
  background: #fff;
  border: 1px solid #ebeef5;
  border-radius: 6px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  padding: 6px 0;
  user-select: none;
}
.table-context-menu__item {
  padding: 8px 14px;
  font-size: 13px;
  line-height: 1;
  color: #303133;
  cursor: pointer;
}
.table-context-menu__item:hover {
  background: #f5f7fa;
}
.table-context-menu__item.is-disabled {
  color: #c0c4cc;
  cursor: not-allowed;
}
.table-context-menu__item.is-disabled:hover {
  background: transparent;
}
</style>
<style lang="scss">
.train-mode {
  .w-e-bar-item {
    height: 65px;
  }
  .w-e-bar-divider {
    margin-top: 12px;
  }

  .w-e-toolbar {
    border: none;
    border-bottom: 1px solid #eee;
  }
}

.wangeditor-box.has-border {
  border: 1px solid #eee;
}
</style>