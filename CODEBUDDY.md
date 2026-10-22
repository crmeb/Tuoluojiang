# CODEBUDDY.md

CodeBuddy 在本仓库的约定入口与 Cursor / Claude 相同。

1. 读仓库根 [AGENTS.md](./AGENTS.md)，按 [`.tuoluojiang/rules/workspace-routing.md`](./.tuoluojiang/rules/workspace-routing.md) 锁定 `code/` 或 `template/`。
2. 再读该目录的 `AGENTS.md`。后端是 [code/AGENTS.md](./code/AGENTS.md)，管理端是 [template/AGENTS.md](./template/AGENTS.md)。
3. 二开步骤见 [`.tuoluojiang/skills/README.md`](./.tuoluojiang/skills/README.md)。管理端 skill 正文在 `template/.agents/skills/`。

架构、端口和文档列表见 [`.tuoluojiang/docs/architecture.md`](./.tuoluojiang/docs/architecture.md) 与 [`.tuoluojiang/docs/catalog.md`](./.tuoluojiang/docs/catalog.md)。

## Docker

只使用 `code/docker`。在该目录执行 `./manage.sh start`。应用 http://localhost:20300 ，安装页 http://localhost:20300/install 。说明见 [code/docker/README.md](./code/docker/README.md)，验证命令见 [code/AGENTS.md](./code/AGENTS.md)。

管理端：`cd template && pnpm dev`，地址 `http://localhost:9527/admin/`。
