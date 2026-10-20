# 外部工具与 MCP 使用规范（索引）

> 适用：查接口（Apifox）、还原设计稿（蓝湖 / MasterGo）、浏览器调试（Chrome DevTools）之前。
>
> **本目录优先级高于任何第三方 skill**：由 CLI 安装、现已并入 `.agents/skills/` 的通用能力包（如 `apifox-cli`）可能描述了本项目不允许的操作，冲突时以本目录为准。

## 不可绕过的底线（速查）

- **Apifox CLI**：只读 · 只查项目 `1530621` · `list` 必须 `jq` 过滤 · 禁止 `head` / `tail` 截断
- **蓝湖 MCP**：只接受 stage 画板链接，`detailDetach` 预览链接不要直接调用
- **MasterGo MCP**：只读，严禁任何写入设计稿的操作
- **Chrome DevTools MCP**：首次调用作为任务第一个动作单独发出 · 先 `list_pages` 复用 Tab

## 明细路由（用到哪个读哪个，不要全读）

| 触发场景 | 读这份 |
|---|---|
| 查接口 / 数据模型、对接后端字段 | [`apifox.md`](./apifox.md)（走 CLI，**不用 apifox MCP**） |
| 浏览器调试、页面验证、Console / Network 排查 | [`chrome-devtools.md`](./chrome-devtools.md) |
| 蓝湖设计稿还原、取标注尺寸 | [`lanhu.md`](./lanhu.md) |
| MasterGo 设计稿还原、取 DSL | [`mastergo.md`](./mastergo.md) |
