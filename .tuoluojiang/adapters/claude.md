# Claude Code 适配

## 加载顺序

1. 根目录 `CLAUDE.md` → 根目录 `AGENTS.md`
2. `.tuoluojiang/rules/workspace-routing.md`
3. 当前目录 `AGENTS.md`（`code/` 或 `template/`）
4. 命中场景时的 `SKILL.md`（见 `.tuoluojiang/skills/README.md`）

## 本仓资产

| 目录 | 路径 |
|------|------|
| code | `code/AGENTS.md`。`.claude` 被 `code/.gitignore` 忽略，不作为入库约定 |
| template | `template/AGENTS.md`、`template/.agents/rules/`、`template/.agents/skills/` |

## 注意

- 用户级 `~/.claude/rules` 不覆盖本仓约定。
- 产品运行时 Agent skills 在 `code/resources/agent/skills/`，与开发 skills 分开。
- `.tuoluojiang/AGENTS.md` 只做重定向，正文在仓库根。
