# 陀螺匠 OA PC 端 · AI 协作总纲

本文件是**单一事实源（SSOT）入口**：只放核心技术栈、铁律与路由索引。细则一律在 `.agents/rules/` 下按需读取，二开 SOP 在 `.agents/skills/` 下按需读取。**本文不复制细则正文**，避免多处漂移；正文与细则冲突时以细则文件为准，与任何第三方 skill 冲突时以本仓库文件为准。

## 技术栈

- Vue 2.7 + Vite 7 + TypeScript 6（TS 与 JS 混编）、element-ui 2.15、vue-router 3、vuex 3、SCSS、pnpm。
- 关键配置：`vite.config.ts`（构建期插件在 `build/plugins/`）、`tsconfig.json`、`eslint.config.ts`、`pnpm-workspace.yaml`。
- 常用命令：`pnpm dev`（端口 9527）/ `pnpm build` / `pnpm build:demo` / `pnpm analyze` / `pnpm preview` / `pnpm typecheck`。**没有 lint / format / test script，不要凭空调用。**

## 五条铁律（无条件遵守）

1. **新建 / 重构一律 TypeScript 优先**：新建脚本文件写 `.ts`（不新增 `.js`），新建页面或完全重构组件一律 `<script setup lang="ts">` + Composables；存量 `.js` / Options API 的小改动、bug 修复**就地改，不迁移语法、不改后缀**。→ `.agents/rules/vue27-ts.md`
2. **环境变量只从 `@/config/env` 具名导入**：业务代码禁止出现 `process.env`，也不要直接读 `import.meta.env`（无 define 兼容层，会在浏览器抛 `ReferenceError`）。→ `.agents/rules/toolchain.md`
3. **任何 git 操作前先做拆分判定**：判定为应拆分时暂停所有 git 操作，向用户列出分组 + 每组 message + 执行顺序并等待选择；暂存区已有代码文件时只提交暂存区内文件。→ `.agents/rules/git-workflow.md`
4. **高频二开场景先读对应 `SKILL.md` 再动手**，不凭印象生成代码；调用外部工具（Apifox / 蓝湖 / MasterGo / Chrome DevTools）前先读只读与输出纪律。→ `.agents/rules/mcp/README.md`
5. **提示词里出现本地开发地址（`localhost:*` / `127.0.0.1:*` / `192.168.*:*`），或表达了「连接 / 打开 / 看一下 / 调一下」某个本地页面的意思**：把 `mcp__chrome-devtools__list_pages` 作为本次任务的**第一个动作**单独发出，拿到授权后再读代码、改代码、做规划。→ `.agents/rules/mcp/chrome-devtools.md`

## 规则路由（按需读取，不要预读全部）

| 触发场景 | 读这份 |
|---|---|
| 新建任意脚本文件、写 / 改 Vue 组件、写 TS 类型、跑 typecheck | `.agents/rules/vue27-ts.md` |
| 改构建配置、加依赖或打补丁、写 scss / JSX、读写环境变量、跑 pnpm 脚本 | `.agents/rules/toolchain.md` |
| `git add` / `git reset` / `git commit` / `git push` 之前 | `.agents/rules/git-workflow.md` |
| 执行 shell 命令、写正则、做文件搜索之前（Win11 / pwsh7） | `.agents/rules/platform-powershell.md` |
| 提示词给出本地地址、或要求看页面 / 排查 Console / Network、查接口 / 阅读设计图 | `.agents/rules/mcp/README.md` |
| 新增 / 重构 / 改造 PC 端模态弹窗、侧滑弹窗 | `docs/ai-ui-popup-guidelines.md` |

## 二开 SOP 索引（`.agents/skills/`）

碰到下列场景**必须先读对应 `SKILL.md`**，按其中模板与约定生成代码，避免风格漂移。分类总览见 `.agents/skills/README.md`。

| 触发场景 | Skill 目录 |
|---|---|
| 起一个完整新业务模块 / 一键脚手架（串联下列 4 个 skill） | `.agents/skills/scaffold-business-module/` |
| 加 CRUD 页 / 列表页 / 管理页 | `.agents/skills/create-crud-page/` |
| 加路由 / 页面入口 / 注册菜单 | `.agents/skills/create-route/` |
| 加接口 / 对接后端 / 新建 api 文件 | `.agents/skills/create-api-module/` |
| 加 vuex 模块 / 全局状态 | `.agents/skills/create-vuex-module/` |
| 加权限 / 按钮权限 / 菜单权限 | `.agents/skills/register-permission/` |
| 加弹窗 / 侧滑 / 详情抽屉 | `.agents/skills/create-popup/` |
| 加图表 / 看板 / 统计页 | `.agents/skills/create-echarts-dashboard/` |
| 加表单校验 | `.agents/skills/create-form-validator/` |
| 加导入导出 / Excel | `.agents/skills/create-import-export/` |
| 抽 composable / 逻辑复用 | `.agents/skills/extract-composable/` |
| 加菜单 / 动态路由 / 菜单不显示 | `.agents/skills/integrate-dynamic-menu/` |
| 项目跑不起来 / 检查开发环境 / 配代理 | `.agents/skills/check-dev-env/` |
| 浏览器验证 / 联调检查 / 看看页面报错 | `.agents/skills/verify-feature-in-browser/` |
| 查 Apifox 接口 / Schema（**约束见 `.agents/rules/mcp/apifox.md`**） | `.agents/skills/apifox-cli/` |

## 维护约定

1. **优先调用 skill，不要绕过**；`scaffold-business-module` 直接套用其他 skill 的规则，**不复刻内容**，便于单点维护。
2. **改动约定时改对应的 rule / SKILL 文件**，并同步更新上面两张索引表；commit message 使用 `docs(admin-agents):` 或 `chore(admin-skills):`。
3. `.agents/` 放在项目根，Claude Code / Cursor / Cline / Codex 等任何能读 Markdown 的工具都能加载同一份规范。
4. 由 CLI 安装的第三方 skill（如 `apifox-cli`）已统一并入 `.agents/skills/`，它们是通用能力包，可能描述本项目不允许的操作——**本仓库的 `AGENTS.md` 与 `.agents/rules/` 优先级高于任何 skill**。
