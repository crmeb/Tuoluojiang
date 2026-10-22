# 文档与规范目录

本表索引仓库内已有文档。`.tuoluojiang` 做汇总，不复制正文。

## 仓库根

| 文档 | 说明 |
|------|------|
| [README.md](../../README.md) | 产品介绍、演示与安装入口。技术栈表里的 Node 14 与当前前端不一致 |
| [AGENTS.md](../../AGENTS.md) | 仓库级 AI 路由 |
| [CLAUDE.md](../../CLAUDE.md) | Claude Code 短入口 |
| [CODEBUDDY.md](../../CODEBUDDY.md) | CodeBuddy 入口，指向本目录 |

## code

| 文档 | 说明 |
|------|------|
| [code/README.md](../../code/README.md) | 技术栈、与商业版差异、Docker、知识库 |
| [code/AGENTS.md](../../code/AGENTS.md) | 后端 AI / 分层约定 |
| [code/docker/README.md](../../code/docker/README.md) | 后端 Docker（Redis Stack、安装页） |
| [code/tuoluojiang/README.md](../../code/tuoluojiang/README.md) | `Tuoluojiang\Open` 框架包 |
| [code/tree.md](../../code/tree.md) | 目录树参考 |

`code/.gitignore` 忽略 `docs`、`docs-detailed`、`tests/`、`.claude`。未入库内容不作为约定源。

## template

| 文档 | 说明 |
|------|------|
| [template/README.md](../../template/README.md) | 旧版说明（webpack、端口 3000）。现行命令见 `AGENTS.md` |
| [template/AGENTS.md](../../template/AGENTS.md) | 管理端 AI 总纲（Vue 2.7 + Vite 7） |
| [template/.agents/rules/](../../template/.agents/rules/) | 细则，组件写法见 `vue27-ts.md` |
| [template/.agents/skills/](../../template/.agents/skills/) | 二开 Skills（含 Vuex） |
| [template/docs/二次开发手册/](../../template/docs/二次开发手册/README.md) | 分章手册。文首版本仍是 Vue 2.6 / vue-cli |
| [template/docs/前端二次开发文档.md](../../template/docs/前端二次开发文档.md) | 长文。文首仍是 vue-cli / Vue 2.6 |
| [template/docs/开发规范.md](../../template/docs/开发规范.md) | 长文规范，版本标注与手册相同 |
| [template/docs/ai-ui-popup-guidelines.md](../../template/docs/ai-ui-popup-guidelines.md) | 弹窗 / 侧滑 |
| [template/docs/AI悬浮球加载与调用流程说明.md](../../template/docs/AI悬浮球加载与调用流程说明.md) | AI 悬浮球 |

现行开发步骤以 `template/AGENTS.md` 和 `.agents/skills/` 为准。上面三份长文保留作背景，其中的 vue-cli、Node 版本和 Vue 2.6 不要当作当前构建方式。

## 产品内 Agent Skills

`code/resources/agent/skills/` 是运行时 Agent 技能（当前为 `gpt-vis`），由 `config/ai.php` 扫描后注入提示词。改产品 Agent 能力时再打开，与上面的开发 skill 分开。
