# 全仓 AI Skills 目录

管理端二开 skill 的正文在 `template/.agents/skills/`。本文件只做索引。命中意图时先读对应 `SKILL.md` 再动手。

仓库级 Skill：

| Skill | 路径 | 触发 |
|-------|------|------|
| `tuoluojiang-onboard` | [tuoluojiang-onboard/SKILL.md](./tuoluojiang-onboard/SKILL.md) | 首次进入本仓、确认改 `code/` 还是 `template/`、找规范入口 |

---

## code（开源后端）

本仓后端没有单独的 skill 包。新增接口、权限、MCP、定时任务时读 [code/AGENTS.md](../../code/AGENTS.md)，并对照同模块邻近文件（例如 `app/Http/Controller/AdminApi/Customer/LabelController.php`）。

已退役、不要再使用：原 `.trae/skills/tuoluojiang_api/` 的 PHP 模板和 `create_api.php`。那些模板按商业版目录和旧分层生成，和当前 `Tuoluojiang\Open` 不一致。

---

## template（管理端）

权威索引：[template/.agents/skills/README.md](../../template/.agents/skills/README.md)。规则路由见 [template/AGENTS.md](../../template/AGENTS.md)。

| Skill | 触发意图 |
|-------|----------|
| `scaffold-business-module` | 完整新业务模块脚手架 |
| `create-crud-page` | CRUD / 列表管理页 |
| `create-route` | 静态路由 / 页面入口 |
| `create-api-module` | API 模块（现有文件在 `src/api/*.js`） |
| `create-vuex-module` | Vuex 模块（`src/store/`） |
| `register-permission` | 按钮 / 菜单权限 |
| `create-popup` | 弹窗 / 侧滑 |
| `create-echarts-dashboard` | 图表 / 看板 |
| `create-form-validator` | 表单校验 |
| `create-import-export` | Excel 导入导出 |
| `extract-composable` | 抽 composable |
| `integrate-dynamic-menu` | 动态菜单（默认接入方式） |
| `check-dev-env` | 本地环境体检 |
| `verify-feature-in-browser` | 浏览器联调验证 |
| `apifox-cli` | Apifox CLI（先读 `template/.agents/rules/mcp/apifox.md`） |

管理端栈是 Vue 2.7 + Element UI + Vuex + Vite 7。新建或完全重构的组件用 `<script setup lang="ts">`，全局状态用 `create-vuex-module`。原 `.trae/skills/tuoluojiang_admin/` 的生成脚本已删除，不要按那套 webpack 模板再造页面。

---

## 产品内 Agent Skills

`code/resources/agent/skills/`：运行时 Agent 技能。改产品 Agent 能力时再打开。

---

## 维护

- 新增管理端 skill → 更新 `template/.agents/skills/README.md` 和 `template/AGENTS.md`，并在本文件补一行。
- 不把 skill 正文复制进 `.tuoluojiang/`。
