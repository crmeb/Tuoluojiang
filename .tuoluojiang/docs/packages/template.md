# template · 管理端前端

**工作目录**：`template/`（说法：admin / 管理端 / PC 后台）  
**提交 scope**：`admin`  
**权威约定**：[template/AGENTS.md](../../../template/AGENTS.md)

本仓物理目录是 `template/`。`template/README.md` 仍按 webpack 和 `http://localhost:9527/admin/` 描述，现行开发服务是 Vite，地址以本节命令为准。

## 技术栈

Vue 2.7、Vite 7、TypeScript 6（存量 JS 混编）、Element UI 2.15、Vue Router 3、Vuex 3、SCSS、pnpm。版本以 `template/package.json` 为准。Node.js ≥ 22.12，pnpm ≥ 10。

## 铁律摘要

1. 新建 / 重构使用 TypeScript 与 `<script setup lang="ts">`。存量 `.js` / Options API 的小改动就地完成。Vue 2.7 的 `<script setup>` 约束见 `.agents/rules/vue27-ts.md`。
2. 环境变量只从 `@/config/env` 具名导入。
3. 提交前按 `template/.agents/rules/git-workflow.md` 做拆分判定。跨模块或混用不同 `type` 时，先列出分组，等用户选择后再提交。
4. 高频二开先读 `template/.agents/skills/` 里对应的 `SKILL.md`。全局状态用 Vuex（`src/store/`），走 `create-vuex-module`。
5. 提示词里出现本地页面地址，或要求看页面时，先按 `template/.agents/rules/mcp/chrome-devtools.md` 处理。

## 规则与 Skills

- 规则：`template/.agents/rules/`（`vue27-ts`、`toolchain`、`git-workflow`、`mcp`）
- Skills：`template/.agents/skills/`（脚手架、CRUD、路由、API、Vuex、权限、弹窗、图表、导入导出、环境体检、浏览器验证）
- 弹窗：`template/docs/ai-ui-popup-guidelines.md`
- 人类手册：`template/docs/二次开发手册/`。手册头仍写 Vue 2.6 / vue-cli；步骤与技术栈以 `template/AGENTS.md` 为准。

`package.json` 没有 `lint`、`format`、`test` script。

## 常用命令

```bash
cd template
pnpm install --frozen-lockfile
pnpm dev          # http://localhost:9527/admin/
pnpm typecheck
pnpm build
```

另有 `pnpm build:demo`、`pnpm analyze`、`pnpm preview`。  
`pnpm build:own` 把产物复制到 `../server/public/admin`。本仓静态目录是 `code/public/admin`，这个脚本路径对不上，不要直接执行。

开发代理写在 `vite.config.ts`，默认目标是 `http://dev.oa.crmeb.net`。改本地后端前读 `check-dev-env` skill。
