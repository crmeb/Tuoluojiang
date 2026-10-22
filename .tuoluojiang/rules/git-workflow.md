# Git 工作流（仓库级）

各目录有更细的提交规则。在某个目录里开发时，以该目录细则为准。本文件汇总共同约束。

管理端细则：`template/.agents/rules/git-workflow.md`  
后端细则：`code/AGENTS.md` 的提交约定

## 格式

```text
<type>(<package>[-<module>]): <中文描述>
```

- `type`：`feat` | `fix` | `refactor` | `docs` | `style` | `chore` | `test` | `perf`
- `package`：`server` | `admin` | `repo`
  - `server`：`code/` 下的后端改动
  - `admin`：`template/` 下的管理端改动
  - `repo`：仓库根、`.tuoluojiang/`，以及必须同一时刻生效的跨目录改动
- `module`：可选，英文小写 kebab-case，例如 `customer`、`data-permission`
- scope 写成 `<package>` 或 `<package>-<module>`。描述用简体中文，说明意图。

示例：

- `feat(server-customer): 新增客户批量导入接口`
- `fix(admin-approval): 修复审批详情状态未刷新`
- `docs(repo-tuoluojiang): 整理开源仓规范中心`

## 拆分

- `code/` 与 `template/` 可以分开验证时，拆成 `server` 与 `admin` 两次提交。
- 必须原子生效的跨目录改动使用 `repo-<module>`。
- 管理端提交前按 `template/.agents/rules/git-workflow.md` 做拆分判定。跨业务模块、混用不同 `type`、或把修复和新功能放在一起时，先列出分组和建议 message，等用户选择后再提交。

## 强制行为

1. 未经用户明确要求，不执行 `git commit` / `git push`。
2. 不使用 `--no-verify` 跳过钩子，除非用户明确要求。
3. 暂存区已有代码文件时，只提交暂存区内的文件。
4. 暂存区混有多个互不相关的主题时，先列出分组和建议 message，等用户选择后再提交。
