# Cursor 适配

Cursor 以仓库根 `AGENTS.md` / `CLAUDE.md` 为入口。细则与 Skill 索引在 `.tuoluojiang/`。

## 推荐加载

| 文件 | 作用 |
|------|------|
| 根目录 `AGENTS.md` | 仓库级路由 |
| 根目录 `CLAUDE.md` | Claude / Cursor 短入口 |
| `.tuoluojiang/rules/workspace-routing.md` | 工作目录路由 |
| `code/AGENTS.md` 或 `template/AGENTS.md` | 锁定目录后的细则 |

## 使用建议

1. 打开本仓库后先遵循根 `AGENTS.md`。
2. 编辑 `code/` 或 `template/` 时，再读该目录 `AGENTS.md`。
3. 管理端细则在 `template/.agents/rules/`，二开步骤在 `template/.agents/skills/`。按需读取。
4. 包内约定与仓库级摘要冲突时，以该目录 `AGENTS.md` 为准。
