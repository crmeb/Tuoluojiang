---
name: verify-feature-in-browser
description: 在本地开发环境用 Chrome DevTools 联调验证二开功能（页面渲染、交互、Console 报错、Network 接口）。当用户说"帮我测一下页面"、"浏览器验证"、"联调检查"、"看看有没有报错"且提供了 localhost 地址时触发。
---

# verify-feature-in-browser

在本地 dev server 上对新改/新生成的页面做**浏览器侧验收**，覆盖：能否打开、关键交互是否正常、Console 是否有报错、Network 请求是否 200 且参数正确。

## 前置条件

1. 用户已提供或可推断本地地址（如 `http://localhost:8080`）
2. 开发服务已启动；未启动时先用 [`check-dev-env`](../check-dev-env/SKILL.md) 排查
3. 需要 chrome-devtools MCP 时，**第一个工具调用必须是授权**，不得与其他操作并行

## Tab 复用策略（强制）

1. `list_pages` 查看已打开 Tab
2. 按 URL 精确或同源前缀匹配 → `select_page` 切换；路径不对则 `navigate_page`
3. 无匹配 Tab 才 `new_page`

## 验证清单

按用户任务勾选，完成后输出结构化报告：

```
验证报告
- 页面 URL：
- 渲染：通过 / 失败（说明）
- 交互：通过 / 失败（步骤 + 现象）
- Console：无报错 / 有报错（摘录）
- Network：关键接口状态码 + 请求参数摘要
- 建议修复：（若有）
```

### 1. 页面可达

- 导航到目标路由（含 `roterPre` 前缀，默认 `/admin/...`）
- 确认非白屏、非 404、非无限 loading

### 2. 关键交互

| 页面类型 | 必测项 |
|---|---|
| CRUD 列表 | 搜索、分页、新增弹窗打开、编辑回填、删除确认 |
| 弹窗/抽屉 | 打开/关闭、提交校验、尺寸是否符合 `popupSize` |
| 表单页 | 必填校验、提交成功后的跳转或列表刷新 |

### 3. Console

- 过滤 `error` / `warn`
- Vue 警告（如 prop 类型、重复 key）需记录

### 4. Network

- 列表接口：method、URL、query/body 是否与 `create-api-module` 约定一致
- 401/403：提示检查登录态或 `v-hasPermi` 权限码
- 重复请求：是否正常（`request.js` 默认去重）

## 工作流程

1. 确认 dev server 地址与目标路径
2. 授权并连接 chrome-devtools
3. 导航到页面，截图或描述首屏状态
4. 按清单执行交互
5. 查看 Console + Network
6. 输出验证报告；失败时定位到具体文件/接口并建议修复

## 不要做的事

- 不要代替用户登录生产环境
- 不要在未授权时尝试连接浏览器
- 不要用浏览器验证替代 [`write-unit-test`](../write-unit-test/SKILL.md) 对纯函数的覆盖
- 发现问题后不要跳过根因直接改样式

## 与其他 skill 的配合

| 顺序 | Skill |
|---|---|
| 生成代码 | `scaffold-business-module` / `create-crud-page` |
| 注册入口 | `create-route` 或 `integrate-dynamic-menu` |
| 本 skill | 浏览器联调验收 |
| 修环境 | `check-dev-env` |
