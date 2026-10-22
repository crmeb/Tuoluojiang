# 工作目录路由

仓库根是陀螺匠开源仓。写代码、跑命令、读约定之前，先锁定一个工作目录。跨目录任务按目录拆开，每次只在一个目录里操作。

## 目录别名

| 别名 / 用户说法 | 工作目录 | 提交 scope | 角色 |
|-----------------|----------|------------|------|
| **server**、后端、API、Laravel、PHP、MCP、Docker 后端 | `code/` | `server` | 开源后端 |
| **admin**、管理端、PC 端、管理后台、Vue 管理端 | `template/` | `admin` | 管理端前端 |
| 规范中心、全仓 Skill 索引、AI 工具适配 | `.tuoluojiang/` | `repo` | 仓库级文档 |

路径相对于仓库根。后端入口是 `code/AGENTS.md`，管理端入口是 `template/AGENTS.md`。

目录名和提交 scope 不一致：后端目录叫 `code/`，提交写成 `server`；前端目录叫 `template/`，提交写成 `admin`。`template/README.md` 里出现的 `admin/` 是角色名，本仓物理目录是 `template/`。

## 意图 → 目录

| 用户意图 | 锁定目录 |
|----------|----------|
| 接口、Controller、Service、Dao、Model、安装 SQL、权限过滤、Casbin、JWT、定时任务、队列、LaravelS、Swoole、`ent/` / `open/` / `mcp/` API | `code/` |
| PC 列表页、后台菜单、Element UI、Vuex、管理端路由、弹窗侧滑、`pnpm dev`、端口 9527 | `template/` |
| 规范中心、跨目录约定、全仓 Skill 索引 | `.tuoluojiang/`（业务代码仍落在上表目录） |

本仓没有 `uniapp/`、`chat/`、`server/`、`admin/` 源码目录。`code/build-frontends.sh` 仍按一体化仓库去找这些兄弟目录，在本仓不能当作前端入口。管理端开发在 `template/`。

## 操作规则

1. 先定目录，再读该目录的 `AGENTS.md`。
2. shell 的 cwd 必须在已锁定目录。后端 Docker 命令在 `code/docker`，前端命令在 `template/`。
3. 新建和修改的业务文件落在已锁定目录下。跨目录功能先改 `code/`，再改 `template/`。
4. 回复里可以用 `server` / `admin` 称呼角色，并写明物理目录 `code/` / `template/`。
5. 只说「前端」时，本仓只有管理端 `template/`。
