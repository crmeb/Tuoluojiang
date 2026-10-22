# 工具链：Vite / ESLint / 环境变量 / 包管理器

> 适用：改构建配置、加依赖或补丁、写 scss / JSX、读写环境变量、执行 pnpm 脚本。

## Vite

配置见 `vite.config.ts`，构建期插件在 `build/plugins/`。

- **禁止 webpack 专有语法**：

  | 禁止（webpack 写法） | 改用（Vite 写法） |
  |---|---|
  | `require.context(dir, deep, regex)` | `import.meta.glob('./**/*.vue', { eager: true })`；需懒加载则去掉 `eager` |
  | `require('@/assets/x.png')` | 顶部 `import x from '@/assets/x.png'`，或 `import.meta.glob(..., { query: '?url', import: 'default' })` |
  | `!!raw-loader!foo.scss` | `import('foo.scss?raw')` |
  | scss 里 `@import '~pkg/x'` | `@import 'pkg/x'`（`~` 前缀已由 alias 兼容，但新代码别再写） |
  | `process.env.VUE_APP_X` | 从 `@/config/env` 具名导入（详见下方「环境变量」） |

- **SFC 里写 JSX 必须标 `<script lang="jsx">`**，否则 esbuild 以 js 解析而报错。
- **`:export` 只在 `*.module.scss` 中生效**：SCSS 变量给 JS 读，走 `@/styles/variables.module.scss`。
- **不存在的具名导入会让构建直接失败**（"No matching export"），不要保留 IDE 自动补全产生的死导入。
- **给第三方库打补丁用 pnpm patch**：补丁文件放 `patches/`，在 `pnpm-workspace.yaml` 的 `patchedDependencies` 登记（已有 `element-ui`、`qrcodejs2` 两例）。典型场景：CJS 老库在 IIFE 顶层用 `this` 指代 window，打成 ESM 后 `this` 为 `undefined` 抛错。
- **根 `package.json` 是 `"type": "module"`**：根目录新增配置 / 脚本一律 ESM，确需 CJS 的单个文件用 `.cjs` 后缀。
- **`browserslist` 必须与 `build.target` 逐条对齐**（当前 chrome/edge ≥ 107、firefox ≥ 104、safari/ios ≥ 16）：`build.target` 决定 JS 降级程度，`browserslist` 决定 autoprefixer 加哪些前缀，放宽任一侧只会产出永远用不上的死前缀。
- 命令：`pnpm dev`（端口 9527）/ `pnpm build` / `pnpm build:demo` / `pnpm analyze`（产出 `dist/stats.html`）/ `pnpm preview` / `pnpm typecheck`。**没有 lint / format / test script，不要凭空调用。**

## ESLint

- 配置见 `eslint.config.ts`（ESLint 10 扁平配置）。**仅供编辑器实时提示，不接入 dev / build / CI**，`package.json` 里也没有 lint script。
- **ESLint 只管代码质量与 Vue 模板正确性，不管格式**。项目不使用任何格式化工具，按存量风格手写：**单引号、无分号、2 空格缩进**；`formatOnSave` 刻意关闭，避免无统一 formatter 时重排存量文件制造无关 diff。
- 末尾的 `eslint-config-prettier` 只是一份「关掉全部格式类规则」的现成清单，**不代表项目用 prettier，不要删**（删了 `vue/html-indent` 这类会在存量代码上刷出上万条报错）。
- 存量约 3.5k 处告警（`prefer-const` / `eqeqeq` / `no-var` 为主）**不要求批量整改**，只要求新写、改写的文件不新增。
- 编辑器需装 `Vue.volar` + `dbaeumer.vscode-eslint`，并禁用 Vetur。

## 环境变量

- **业务代码禁止出现 `process.env`，也不要直接读 `import.meta.env`**，一律从 `@/config/env` 具名导入：

  ```js
  import { BASE_API, DEMO_SUPPORT, IS_DEV } from '@/config/env'
  ```

  `vite.config.ts` 没有任何 `process.env` 的 define 兼容层，写了会在浏览器直接抛 `ReferenceError`。

- 前缀统一 `VITE_`，只有 `VITE_` 开头的变量会注入客户端。
- 新增变量三步走：`.env.*` 加 `VITE_XXX` → `src/types/env.d.ts` 补类型 → `src/config/env.ts` 导出语义化常量（顺手做默认值兜底与类型收敛，如开关类直接导出 `boolean`，调用方不必各自写 `=== 'true'`）。
- Vite 内置的 `BASE_URL` / `DEV` / `PROD` 同样从 `@/config/env` 出口（`BASE_URL` / `IS_DEV` / `IS_PROD`），保持单一来源。
- 命令行注入的变量（如 `build:demo` 里的 `VITE_DEMO_*`）无需额外配置，Vite 会自动合并 `.env` 文件与 shell 环境变量。
- `.env.development` 已在 `.gitignore` 中（个人本地文件），以 `.env.example` 为模板新建；私有后端地址只写在这里，不要写进会被提交的文件。

## 包管理器

统一 `pnpm`（`engines`：node ≥ 22.12、pnpm ≥ 10），安装、打包一律走 `pnpm`，不要用 npm。
