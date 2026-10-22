import { defineConfig, globalIgnores } from 'eslint/config'
import js from '@eslint/js'
import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import vueParser from 'vue-eslint-parser'
import tseslint from 'typescript-eslint'
// 名字带 prettier，作用其实是「一次性关掉 ESLint 全部格式类规则」的现成清单
// （358 条，含 39 条 vue/）。项目并不使用 prettier，纯粹借用这份清单。
import disableFormattingRules from 'eslint-config-prettier/flat'

/**
 * 项目里以全局变量形式出现的第三方 SDK / 运行时注入变量。
 * 它们没有 import 语句，不声明的话会被 no-undef 全部误报。
 */
const projectGlobals = {
  // 高德地图、jQuery、luckysheet（在线表格）、@vue-office 预览组件
  AMap: 'readonly',
  $: 'readonly',
  jQuery: 'readonly',
  luckysheet: 'readonly',
  VueOfficeExcel: 'readonly',
  // form-create 在字符串模板里注入的作用域变量
  fApi: 'readonly',
  form_create_helper: 'readonly',
  // 企业微信 JSSDK
  WWOpenData: 'readonly',
  wx: 'readonly',
}

/**
 * ESLint 扁平配置（ESLint 10）。
 *
 * 职责边界：
 * - 本配置只管「代码质量 + Vue 模板正确性」，不管代码格式。
 *   项目没有引入任何格式化工具，缩进 / 引号 / 分号由人工把控；末尾那份
 *   清单负责把 ESLint 自带的格式规则全部关掉，避免存量代码刷屏。
 *   （存量实际风格是单引号、无分号、2 空格，新代码照此书写即可。）
 * - 存量 .js / .vue(js) 与新写的 .ts / .tsx / <script lang="ts"> 共用同一套
 *   质量规则；TS 专属规则只挂到 ts / SFC 上，不会波及存量 .js。
 *
 * 仅供编辑器实时提示，不接入 dev / build 流程。
 */
export default defineConfig([
  globalIgnores([
    'dist/**',
    'node_modules/**',
    'public/**',
    'static/**',
    'src/assets/**',
    // 表单设计器的第三方产物，非手写代码
    'src/views/business/components/formSetting/**',
    '**/*.min.js',
  ]),

  // ---- 基础：所有手写源码 ----
  {
    files: ['**/*.{js,mjs,cjs,jsx,ts,tsx,vue}'],
    extends: [js.configs.recommended],
    linterOptions: {
      // 存量里有些 eslint-disable 因规则调整而失效，提示即可，不当错误
      reportUnusedDisableDirectives: 'warn',
    },
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      parserOptions: {
        // 存量有 .js 文件直接写 JSX（如 form-designer 的 property-editor-factory.js）
        ecmaFeatures: { jsx: true },
      },
      globals: {
        ...globals.browser,
        ...globals.es2024,
        ...globals.node,
        ...projectGlobals,
      },
    },
    rules: {
      // ---- 变量 / 逻辑：沿用原 .eslintrc.js 里「非格式化」的那部分约定 ----
      'no-var': 'error',
      'prefer-const': ['error', { destructuring: 'all' }],
      'no-unused-vars': ['warn', { vars: 'all', args: 'none', ignoreRestSiblings: true }],
      eqeqeq: ['error', 'always', { null: 'ignore' }],
      'no-eval': 'error',
      // 业务侧有依赖日志排查的习惯，不拦 console
      'no-console': 'off',
      'no-debugger': 'warn',

      // ---- 存量里量大、但确实是隐患，降为 warn 而不是直接放行 ----
      // `this.$refs.x && this.$refs.x.reset()` 这类惯用法会命中
      'no-unused-expressions': 'warn',
      'no-extra-boolean-cast': 'warn',
      'no-empty': ['warn', { allowEmptyCatch: true }],
      'no-prototype-builtins': 'warn',
      // ESLint 10 新增，对「先给初值再按分支覆盖」的写法误报较多
      'no-useless-assignment': 'warn',

      // ---- 明确的 bug 信号，保持 error ----
      'no-dupe-keys': 'error',
      'no-dupe-else-if': 'error',
      'no-self-assign': 'error',
      'no-async-promise-executor': 'error',
      'no-constant-binary-expression': 'error',
    },
  },

  // ---- TypeScript：只挂在 ts / tsx / SFC 上，存量 .js 完全不受影响 ----
  {
    files: ['**/*.{ts,tsx}', '**/*.vue'],
    extends: [tseslint.configs.recommended],
    rules: {
      // 这两条与上面的 core 规则同义，只保留 TS 版本，避免同一处报两遍
      'no-unused-vars': 'off',
      'no-unused-expressions': 'off',
      '@typescript-eslint/no-unused-vars': ['warn', { args: 'none', ignoreRestSiblings: true }],
      '@typescript-eslint/no-unused-expressions': 'warn',

      // 渐进式接入 TS，先不禁 any
      '@typescript-eslint/no-explicit-any': 'off',
      // `const that = this` 是本项目 Options API 里的主流写法
      '@typescript-eslint/no-this-alias': 'off',
      // 与存量 js 互操作时难免出现
      '@typescript-eslint/no-empty-object-type': 'off',
      '@typescript-eslint/no-require-imports': 'off',
    },
  },

  // ---- Vue 2：模板正确性校验 ----
  {
    files: ['**/*.vue'],
    extends: [pluginVue.configs['flat/vue2-recommended']],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        // <script lang="ts"> 使用 TypeScript 解析器，普通 <script> 走默认 espree
        parser: {
          ts: tseslint.parser,
        },
        ecmaVersion: 'latest',
        sourceType: 'module',
        extraFileExtensions: ['.vue'],
      },
    },
    rules: {
      // ---- 格式类，未被上面那份清单覆盖，这里单独关掉 ----
      'vue/first-attribute-linebreak': 'off',

      // ---- 存量组件量大，以下降级为提示，不做硬约束 ----
      'vue/multi-word-component-names': 'off',
      'vue/require-default-prop': 'off',
      'vue/no-v-html': 'off',
      'vue/no-unused-components': 'warn',
      'vue/no-unused-vars': 'warn',
      'vue/no-mutating-props': 'warn',
      'vue/no-template-shadow': 'warn',
      'vue/component-definition-name-casing': 'warn',
      'vue/order-in-components': 'off',
      'vue/attributes-order': 'off',
      // element-ui 组件在模板里既有 <el-button> 也有 <ElButton>，不强制统一
      'vue/component-name-in-template-casing': 'off',
      'vue/attribute-hyphenation': 'off',
      // 自闭合写法一律放行：<div /> 与 <div></div> 均可
      'vue/html-self-closing': 'off',

      // ---- 真正能抓 bug 的，保持 error ----
      'vue/require-v-for-key': 'error',
      'vue/no-use-v-if-with-v-for': 'error',
      'vue/no-dupe-keys': 'error',
      'vue/no-side-effects-in-computed-properties': 'error',
      'vue/require-render-return': 'error',
      'vue/valid-v-slot': 'error',
      'vue/no-parsing-error': 'error',

      // ---- 插槽语法迁移：slot-scope / slot="xxx" → #default / #xxx ----
      // 这两条在 vue2 preset 里默认关闭，必须显式开启。
      // Vue 3 编译器完全不识别旧语法（compiler-core 里 'slot-scope' 出现 0 次），
      // 且 @vue/compat 的 8 项 COMPILER_* 兼容清单中没有任何插槽相关项 ——
      // 旧写法在 Vue 3 下编译不报错，但 <template> 退化成原生 inert 元素、
      // slot="xxx" 静默错位到默认插槽，属于升级前必须清空的硬前置。
      // 存量清理期间设为 warn（与项目既有的「不批量整改、只拦增量」策略一致），
      // 全部改完后升级为 error。
      'vue/no-deprecated-slot-attribute': 'warn',
      'vue/no-deprecated-slot-scope-attribute': 'warn',
      'vue/no-deprecated-scope-attribute': 'warn',
    },
  },

  // ---- 构建期脚本跑在 Node 里 ----
  {
    files: ['*.config.{ts,js,mjs}', 'build/**/*.ts'],
    languageOptions: {
      globals: { ...globals.node },
    },
  },

  // ---- 必须放最后：关掉 ESLint 全部格式类规则 ----
  disableFormattingRules,
])
