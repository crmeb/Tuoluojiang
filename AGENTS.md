# 陀螺匠开源仓 · AI 入口

本文件只做仓库级路由。进入某个目录后，以该目录的 `AGENTS.md` 为细则。跨目录约定在 [`.tuoluojiang/`](./.tuoluojiang/README.md)。

## 工作目录

| 说法 | 目录 | 提交 scope | 角色 |
|------|------|------------|------|
| server、后端、API、Laravel、PHP、MCP、Docker 后端 | `code/` | `server` | 开源后端 |
| admin、管理端、PC 后台、Vue 管理端 | `template/` | `admin` | 管理端前端 |

本仓源码是这两个目录。移动端和独立会话前端不在本仓；`code/public/chat`、`code/public/work` 只是已构建静态资源。

## 先读

1. [`.tuoluojiang/rules/workspace-routing.md`](./.tuoluojiang/rules/workspace-routing.md) 锁定目录。
2. 读已锁定目录的 `AGENTS.md`。
3. 命中二开场景再读对应 `SKILL.md`。索引：[`.tuoluojiang/skills/README.md`](./.tuoluojiang/skills/README.md)。

## 提交

格式与拆分见 [`.tuoluojiang/rules/git-workflow.md`](./.tuoluojiang/rules/git-workflow.md)。未经用户要求不执行 `git commit` / `git push`。
