# Codex 适配

## 项目指令

按任务范围加载：

1. 仓库根 `AGENTS.md`
2. `code/AGENTS.md` 或 `template/AGENTS.md`
3. `.tuoluojiang/skills/README.md`，找到路径后再读对应 `SKILL.md`

## 可选：用户级 Skill 软链

若希望 Codex 发现仓库级导览 skill：

```bash
ln -s "<仓库根>/.tuoluojiang/skills/tuoluojiang-onboard" \
  "$HOME/.codex/skills/tuoluojiang-onboard"
```

把 `<仓库根>` 换成 oa-open 的绝对路径。包内开发仍以该目录 `AGENTS.md` 为准。

## 验证习惯

- `code/`：`code/docker` 容器内的 `php -l` / `php artisan test`
- `template/`：`pnpm typecheck`；页面验证走 `verify-feature-in-browser`
