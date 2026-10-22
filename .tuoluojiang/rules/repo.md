# 仓库级约定

改动跨 `code/` 与 `template/`，或在仓库根操作时遵守本节。单目录细则以该目录 `AGENTS.md` 为准。目录对照见 `workspace-routing.md`。

## 边界

- **code（提交 scope `server`）**：业务、权限、安装数据和 MCP 的实现。路由前缀使用 `ent/`、`admin/`、`open/`、`mcp/`。
- **template（提交 scope `admin`）**：PC 管理端，通过 HTTP / JWT 调用上述 API。
- 权限过滤、审批状态、计费等业务规则放在后端。前端调用 API，并按权限码控制展示。

## 一次功能通常改到

1. `code/`：Controller / Request / Service / Dao / Model，需要时加 MCP Tool
2. `template/`：`src/api/`、页面、路由或动态菜单、权限码
3. 对外契约有文档时，同步该目录已有的说明

提交拆分见 `git-workflow.md`。

## 环境

| 用途 | 入口 | 说明 |
|------|------|------|
| 后端开发与测试 | `code/docker` | `./manage.sh`。PHP 8.0、MySQL 8、Redis Stack。应用 `http://localhost:20300`，MySQL `127.0.0.1:3376`，Redis `127.0.0.1:6389`。详见 `code/docker/README.md` |
| 管理端开发 | `template/` | `pnpm dev`，`http://localhost:9527/admin/`。`vite.config.ts` 的开发代理默认指向 `http://dev.oa.crmeb.net` |

后端验证命令的 cwd 是 `code/docker`，见 `code/AGENTS.md`。管理端环境变量与代理见 `template/.agents/skills/check-dev-env/SKILL.md`。

`template` 的 `build:own` 把产物复制到 `../server/public/admin`。本仓静态目录是 `code/public/admin`，这个脚本路径对不上，不要直接执行。

## 语言

用户可见文案、校验消息用中文，与邻近代码一致。提交说明用简体中文。
