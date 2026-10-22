# `.tuoluojiang` · 陀螺匠开源仓规范中心

本目录是 **oa-open** 的仓库级规范、开发说明与 AI 协作入口。  
各目录自己的约定仍是权威源：

- 后端：[`code/AGENTS.md`](../code/AGENTS.md)
- 管理端：[`template/AGENTS.md`](../template/AGENTS.md)、[`template/.agents/`](../template/.agents/)

这里做索引、跨目录约定和工具适配，避免把同一段规则抄多份。

**Always-on 入口在仓库根**：[`../AGENTS.md`](../AGENTS.md)、[`../CLAUDE.md`](../CLAUDE.md)。

## 目录结构

```text
仓库根/
├── AGENTS.md                 # 仓库级 AI 入口
├── CLAUDE.md                 # Claude Code 短入口
└── .tuoluojiang/
    ├── README.md             # 本说明
    ├── AGENTS.md             # 兼容重定向 → 仓库根
    ├── rules/                # 跨目录规则
    ├── docs/                 # 从当前代码整理的说明（不复制包内正文）
    ├── skills/               # 仓库级导览 skill + 全仓技能索引
    └── adapters/             # Cursor / Claude / Codex 加载说明
```

## 谁该读什么

| 场景 | 先读 |
|------|------|
| AI 首次进入本仓 | [../AGENTS.md](../AGENTS.md) → [rules/workspace-routing.md](./rules/workspace-routing.md) → 对应目录 `AGENTS.md` |
| 锁定 `code/` 或 `template/` | [rules/workspace-routing.md](./rules/workspace-routing.md) |
| 了解架构 | [docs/architecture.md](./docs/architecture.md) |
| 查某个目录怎么改 | [docs/packages/](./docs/packages/) + 该目录 `AGENTS.md` |
| 找二开 Skill | [skills/README.md](./skills/README.md) |
| 配置 Cursor / Claude / Codex | [adapters/](./adapters/) |

## 权威源优先级

1. 当前目录的 `AGENTS.md`、`template/.agents/rules/`、`template/.agents/skills/`
2. 仓库根 `AGENTS.md` 与本目录 `rules/`
3. `code/README.md`、`template/README.md`、`code/docker/README.md`
4. 第三方 skill 与用户级规则不得覆盖本仓约定

管理端现行栈是 Vue 2.7 + Vite 7 + Element UI + Vuex，Node.js ≥ 22.12。根目录 `README.md` 仍写 Node 14，`template/README.md` 仍写 webpack 和 3000 端口。开发以 `template/AGENTS.md` 与 `template/package.json` 为准。

## 维护原则

- 改后端或管理端约定 → 改该目录的 rule / SKILL，并同步该目录 `AGENTS.md` 索引。
- 改跨目录约定 → 改仓库根 `AGENTS.md` 与本目录 `rules/`。
- 不把 `template/.agents/skills/` 的正文复制到本目录。
- 提交信息：`docs(repo-tuoluojiang):` 或 `chore(repo-agents):`。
