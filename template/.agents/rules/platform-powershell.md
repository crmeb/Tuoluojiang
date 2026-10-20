# 平台约束：Windows 11 / pwsh 7

> 适用：执行任何 shell 命令、写正则、跑脚本、做文件搜索之前。

## Windows 约束

当前环境 Windows 11 / pwsh 7，默认使用 PowerShell 语法（除非确认此 shell 处在 Linux 环境）：

- 默认禁止使用 Bash 语法，不要套用 Bash 的引号 / 转义习惯。
- 复杂正则优先用单引号包裹；正则本身同时含单引号和双引号时，拆成多条简单 `rg` 命令。
- 执行多行 Python 时用 PowerShell here-string 管道（`... | python -`），**禁止 Bash heredoc**。
- 语句块表达式（`foreach` / `if`）不能直接作为管道输入，需先用 `$()` / `@()` 包裹，或先赋值给变量；普通命令输出可直接进管道，无需额外包裹。
- 使用 `rg` 时，通配目录必须先用 `Get-ChildItem -Filter` 展开为真实路径，**禁止把含 `*` 的搜索路径直接传给 `rg`**。

## 工具优先级

1. **文件操作优先内置工具**：Read / Write / Edit / Glob / Grep
2. **文本 / 文件搜索优先 `rg`**：搜索文本优先使用 `rg`，搜索文件优先使用 `rg --files`
3. **JSON 处理优先 `jq`**：对 JSON 做解析、提取、筛选、重组、统计时优先使用 `jq`
4. **PowerShell**：仅在执行系统命令、复杂文件系统操作、编码 / 换行控制，或内置工具 / `rg` / `jq` 无法满足需求时使用
