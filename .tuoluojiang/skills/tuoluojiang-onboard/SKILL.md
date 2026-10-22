---
name: tuoluojiang-onboard
description: >-
  Orient AI or developers inside the 陀螺匠 oa-open repo. Lock the working
  directory first: code=开源后端 (commit scope server), template=管理端前端
  (commit scope admin). Use when first opening this repo, locating which
  directory to edit, finding AGENTS/skills/docs, or answering where the
  project rules are.
---

# 陀螺匠开源仓导览

## 何时使用

- 首次进入 oa-open
- 需要确认改 `code/` 还是 `template/`
- 用户问规范、文档、Skill 在哪
- 改动同时涉及接口和页面

## 步骤

1. **锁定工作目录**（详见 `.tuoluojiang/rules/workspace-routing.md`）：

   | 别名 | 目录 | 提交 scope | 角色 |
   |------|------|------------|------|
   | server | `code/` | `server` | 开源后端 |
   | admin | `template/` | `admin` | 管理端前端 |

   - API / 权限 / MCP / Docker / SQL → `code/`
   - PC 管理后台页面 → `template/`（Vue 2.7 + Element UI + Vuex + Vite 7）
   - 本仓没有移动端源码，也没有独立 `chat/` 工程

2. 读仓库根 `AGENTS.md`，再读已锁定目录的 `AGENTS.md`。
3. 命中二开场景再读对应 `SKILL.md`。总索引：`.tuoluojiang/skills/README.md`。
   - 管理端 skill 正文在 `template/.agents/skills/`
   - 后端没有独立 skill 包，按 `code/AGENTS.md` 与邻近控制器实现
4. 需要架构或文档列表时读：
   - `.tuoluojiang/docs/architecture.md`
   - `.tuoluojiang/docs/packages/code.md` 或 `packages/template.md`
   - `.tuoluojiang/docs/catalog.md`
5. shell 的 cwd 放在已锁定目录。后端 Docker 命令在 `code/docker`。验证见 `.tuoluojiang/docs/development-workflow.md`。

## 边界

- 未锁定工作目录不改业务代码
- 框架代码在 `code/tuoluojiang/`（`Tuoluojiang\Open\`）。不按商业版 `crmeb/` 路径改文件
- 后端测试只在 `code/docker` 的 PHP 容器内执行
- 未经用户要求不 commit / push
- 不把 `template/.agents/skills/` 的正文抄进回复；指出要读的 `SKILL.md` 路径
