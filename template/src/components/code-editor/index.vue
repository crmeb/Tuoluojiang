<template>
  <div class="ace-container">
    <!-- 官方文档中使用id，这里禁止使用，在后期打包后容易出现问题，使用 ref 或者 DOM 就行 -->
    <div class="ace-editor" ref="ace"></div>
  </div>
</template>

<script>
import ace from 'ace-builds'
/* 启用此行后webpack打包回生成很多动态加载的js文件，不便于部署，故禁用！！
   特别提示：禁用此行后，需要调用ace.config.set('basePath', 'path...')指定动态js加载URL！！
 */
//import 'ace-builds/webpack-resolver'

//import 'ace-builds/src-min-noconflict/theme-monokai' // 默认设置的主题
import 'ace-builds/src-min-noconflict/theme-sqlserver' // 新设主题
import 'ace-builds/src-min-noconflict/mode-javascript' // 默认设置的语言模式
import 'ace-builds/src-min-noconflict/mode-json' //
import 'ace-builds/src-min-noconflict/mode-css' //
import 'ace-builds/src-min-noconflict/ext-language_tools'
// 语法检查 worker：ace 默认按 basePath 动态加载，Vite 下没有这个路径，需逐个登记产物地址
import jsWorkerUrl from 'ace-builds/src-min-noconflict/worker-javascript.js?url'
import jsonWorkerUrl from 'ace-builds/src-min-noconflict/worker-json.js?url'
import cssWorkerUrl from 'ace-builds/src-min-noconflict/worker-css.js?url'

ace.config.setModuleUrl('ace/mode/javascript_worker', jsWorkerUrl)
ace.config.setModuleUrl('ace/mode/json_worker', jsonWorkerUrl)
ace.config.setModuleUrl('ace/mode/css_worker', cssWorkerUrl)
// import {ACE_BASE_PATH} from "@/utils/config";

// 编辑器配置常量
const EDITOR_CONFIG = {
  themePath: 'ace/theme/sqlserver',
  modePath: 'ace/mode/javascript',
  defaultOptions: {
    maxLines: 20,
    minLines: 5,
    fontSize: 12,
    tabSize: 2,
    highlightActiveLine: true
  },
  autoCompleteOptions: {
    enableBasicAutocompletion: true,
    enableSnippets: true,
    enableLiveAutocompletion: true
  }
}

export default {
  emits: ['update:value', 'update:modelValue'],
  name: 'CodeEditor',
  props: {
    modelValue: {
      type: String,
      required: true
    },
    readonly: {
      type: Boolean,
      default: false
    },
    mode: {
      type: String,
      default: 'javascript'
    },
    userWorker: {  //是否开启语法检查，默认开启
      type: Boolean,
      default: true
    },

  },
  computed: {
    // Vue 3 的 v-model 走 modelValue，保留 value 供组件内部沿用
    value() {
      return this.modelValue
    },
  },
  mounted() {
    //ace.config.set('basePath', 'https://ks3-cn-beijing.ksyun.com/vform2021/ace')
    // ace.config.set('basePath', ACE_BASE_PATH)

    this.addAutoCompletion(ace)  //添加自定义代码提示！！

    this.aceEditor = ace.edit(this.$refs.ace, {
      ...EDITOR_CONFIG.defaultOptions,
      theme: EDITOR_CONFIG.themePath,
      mode: EDITOR_CONFIG.modePath,
      readOnly: this.readonly,
      value: this.codeValue
    })

    this.aceEditor.setOptions(EDITOR_CONFIG.autoCompleteOptions)

    if (this.mode === 'json') {
      this.setJsonMode()
    } else if (this.mode === 'css') {
      this.setCssMode()
    }

    if (!this.userWorker) {
      this.aceEditor.getSession().setUseWorker(false)
    }

    //编辑时同步数据
    this.aceEditor.getSession().on('change',(ev)=>{
      //this.$emit('update:value', this.aceEditor.getValue())  // 触发更新事件, 实现.sync双向绑定！！
      this.$emit('update:modelValue', this.aceEditor.getValue())
    })
  },
  data() {
    return {
      aceEditor: null,
      codeValue: this.modelValue
    }
  },
  watch: {
    value(val) {
      if (this.aceEditor && val !== this.aceEditor.getValue()) {
        this.aceEditor.setValue(val, 1)
      }
    }
  },
  methods: {
    addAutoCompletion(ace) {
      let acData = [
        {meta: '', caption: 'getWidgetRef', value: 'getWidgetRef()', score: 1},
        {meta: '', caption: 'getFormRef', value: 'getFormRef()', score: 1},
        //TODO: 待补充！！
      ]
      let langTools = ace.require('ace/ext/language_tools')
      langTools.addCompleter({
        getCompletions: function(editor, session, pos, prefix, callback) {
          if (prefix.length === 0) {
            return callback(null, []);
          }else {
            return callback(null, acData);
          }
        }
      })
    },

    setJsonMode() {
      this.aceEditor.getSession().setMode('ace/mode/json')
    },

    setCssMode() {
      this.aceEditor.getSession().setMode('ace/mode/css')
    },

    getEditorAnnotations() {
      return this.aceEditor.getSession().getAnnotations()
    },

  }
}
</script>

<style lang="scss" scoped>
  .ace-editor {
    min-height: 300px;
  }
</style>
