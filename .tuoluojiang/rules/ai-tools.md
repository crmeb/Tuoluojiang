# AI 工具加载约定

## 单一事实源

| 层级 | 位置 | 何时读 |
|------|------|--------|
| 仓库级 | 仓库根 `AGENTS.md` | 根目录任务、跨目录任务 |
| 目录级 | `code/AGENTS.md`、`template/AGENTS.md` | 改该目录代码时 |
| 细则 | `template/.agents/rules/` | 按 `template/AGENTS.md` 的路由表按需读 |
| Skill | `template/.agents/skills/`、`.tuoluojiang/skills/` | 命中触发意图时先读再做 |

不把细则全文再抄进 always-on 文件。`.tuoluojiang/AGENTS.md` 只做重定向，正文在仓库根。

## Cursor

- 根目录 `AGENTS.md`、`CLAUDE.md` 为入口。
- 编辑 `code/` 或 `template/` 时再读该目录 `AGENTS.md`。
- 细则和 Skill 按需打开，不要一次预读全部 `.agents/`。

## Claude Code

- 读取顺序：根 `CLAUDE.md` → 根 `AGENTS.md` → `.tuoluojiang/rules/workspace-routing.md` → 当前目录 `AGENTS.md`。
- `code/.gitignore` 忽略了 `.claude`。后端约定以已入库的 `code/AGENTS.md` 为准。
- 用户级 `~/.claude/rules` 不覆盖本仓约定。

## Codex / 其它

- 项目指令按根 `AGENTS.md` 与当前目录 `AGENTS.md` 加载。
- Skill 路径见 `.tuoluojiang/skills/README.md`。

## MCP

管理端外部工具的只读和输出纪律在 `template/.agents/rules/mcp/`。调用 Apifox、蓝湖、MasterGo、Chrome DevTools 之前先读该目录的 `README.md`。
