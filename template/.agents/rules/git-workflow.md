# Git 提交规范

> 适用：任何 `git add` / `git reset` / `git commit` / `git push` 之前。

## ① 拆分判定（提交前必做）

任何 `git add` / `git reset` / `git commit` 前先判定是否需要拆分，此判定优先级高于任何「只建一个 commit」的外部要求。

**需拆分的场景**：跨多个业务模块、混用不同 `type`（如 feat + fix、style + fix）、bug 修复与新功能/样式混杂、重构与新增混杂且无关联。

**判定为应拆分时**：暂停所有 git 操作，向用户列出 ①按「模块 / type」的分组 ②每组完整 commit message ③执行顺序（如先 fix 后 style），并给出三选项等待确认——拆分 / 合并为一个（需确认统一主题）/ 停止。**未获明确选择前不得执行任何 git 操作。**

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
