# 十、Git 提交规范

## 10.1 分支命名

| 分支类型 | 命名格式 | 示例 |
|----------|----------|------|
| 功能分支 | feature/功能名 | feature/user-management |
| 修复分支 | fix/问题描述 | fix/login-error |
| 发布分支 | release/版本号 | release/v2.3.0 |
| 热修复分支 | hotfix/问题描述 | hotfix/security-patch |

## 10.2 Commit Message 格式

```
<type>(admin[-<module>]): <subject>

<body>

<footer>
```

## 10.3 Type 类型

| 类型 | 说明 | 示例 |
|------|------|------|
| feat | 新功能 | feat(admin-user): 新增用户导入功能 |
| fix | 修复 Bug | fix(admin-login): 修复登录验证码失效问题 |
| docs | 文档更新 | docs(admin): 更新开发规范文档 |
| style | 代码格式（不影响功能） | style(admin): 格式化代码缩进 |
| refactor | 重构代码 | refactor(admin-api): 重构请求封装 |
| perf | 性能优化 | perf(admin-list): 优化列表渲染性能 |
| test | 测试相关 | test(admin-user): 添加用户模块单元测试 |
| chore | 构建/工具变动 | chore(admin): 更新依赖版本 |
| revert | 回滚提交 | revert(admin-user): 回滚用户模块修改 |

## 10.4 Commit 示例

```bash
# 新功能
git commit -m "feat(admin-asset): 新增资产管理模块

- 新增资产列表页面
- 新增资产新增/编辑弹窗
- 新增资产删除功能"

# 修复 Bug
git commit -m "fix(admin-table): 修复表格分页重置问题"

# 文档更新
git commit -m "docs(admin-api): 更新 API 接口文档"

# 代码重构
git commit -m "refactor(admin-store): 重构用户状态管理模块"
```

## 10.5 提交前检查

```bash
# 运行 lint 检查
npm run lint

# 运行格式化
npm run prettier

# 确保构建成功
npm run build
```

## 10.6 提交规范检查清单

- [ ] 提交信息是否符合规范格式
- [ ] 是否只提交相关文件
- [ ] 是否通过 lint 检查
- [ ] 是否测试通过
- [ ] 是否有 console.log（生产环境应移除）

---

[上一章：样式编写规范](./09-样式编写规范.md) | [返回目录](./README.md) | [下一章：快速检查清单](./附录-快速检查清单.md)
