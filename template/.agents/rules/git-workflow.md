# Git 提交规范

> 适用：任何 `git add` / `git reset` / `git commit` / `git push` 之前。

## ① 提交范围（用户已确认）

用户于 2026-09-24 明确指定：`admin` 包以后每轮工作的改动合并为一个 commit，跨业务模块也不再询问拆分。提交前确认该轮改动的统一主题，并用一个 message 准确概括。

当前管理端已使用 Vue 3 + TypeScript；提交时遵守下方暂存区约束。

若该轮包含不同类型的改动，在提交说明中清楚概括；不要因跨模块而拆分或重复询问用户。

## ② message 格式与 scope

遵循仓库级 `.tuoluojiang/rules/git-workflow.md`，格式为 `<type>(admin[-<module>]): <中文描述>`，`type` 取 feat / fix / style / refactor / chore / docs / perf / test。

`package` 固定为 `admin`；`module` 可选，取关联的组件 / 业务模块 / 领域名，必须使用英文小写 kebab-case。禁止省略 `admin`，禁止中文、拼音或 `admin/<module>` 写法。

示例：

- `feat(admin-customer): 新增客户批量导入功能`
- `fix(admin-approval): 修复审批流程中状态未更新的问题`
- `style(admin-opportunity): 隐藏右下角悬浮添加按钮`
- `refactor(admin-app): 重构入口文件，拆分启动编排与服务层`

## ③ 提交语法（避免首行被 `@` 污染）

- **Bash 工具**：双引号传 message，换行写在引号内；禁用 here-string。
- **PowerShell 工具**：多行用单引号 here-string，闭合符顶格。
- 优先单行 `-m`，需正文再追加多行；首行若被污染立即 `git commit --amend -m "..."` 修正。

## ④ 暂存区约束

暂存区已有代码文件时，仅提交暂存区内文件，禁止追加工作区文件。
