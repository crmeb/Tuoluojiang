# Chrome DevTools MCP 使用规范

> 适用：浏览器调试、页面验证、Console / Network 排查之前。索引见 [`README.md`](./README.md)。
>
> **底线**：首次调用作为任务第一个动作单独发出 · 先 `list_pages` 复用 Tab

- 如果用户在提示词中明确提供了本地开发服务器地址，例如 `http://localhost:3000`、`http://127.0.0.1:5173`、`http://192.168.x.x:8080`，可使用 `chrome-devtools` 连接页面进行调试。
- 可用于页面查看、交互验证、DOM 检查、Console 报错排查、Network 请求分析、性能初步排查等浏览器侧调试工作。
- 若提供的地址不是本地开发地址，或页面无法访问，再根据实际情况补充确认。

## 授权最高优先级（强制）

只要判断本次任务**后续会用到** `chrome-devtools`（浏览器调试、页面验证、Network/Console 排查等），就必须在做任何其他事情之前**第一时间**发起一次授权请求：

1. **最高优先级**：把首次 `chrome-devtools` 调用（如 `mcp__chrome-devtools__list_pages`）作为整个任务的第一个动作，先于读代码、改代码、分析、规划等一切工作。
2. **禁止并行**：发起授权请求时不得在同一轮内夹带其他工具调用，必须单独发出，等授权结果返回后再继续。
3. **禁止延后**：不允许「先写代码、等到要调试时再授权」。务必趁用户在场时尽早完成授权，避免调试阶段因用户离场而阻塞。
4. 授权完成后无需就「是否可以打开浏览器」重复确认，直接按下方 Tab 复用策略连接并排查。

## Tab 复用策略（重要）

收到用户提供的 URL 后，**禁止**直接调用 `mcp__chrome-devtools__new_page` 新开页面，必须先复用已有 Tab：

1. 调用 `mcp__chrome-devtools__list_pages` 查看当前所有已打开的 Tab。
2. 在返回结果中**按 URL 精确匹配或同源 + 路径前缀匹配**已有的 Tab：
   - 命中：调用 `mcp__chrome-devtools__select_page` 切换到该 Tab 后直接进行后续调试操作；如目标路径与当前 URL 不一致，可在该 Tab 上调用 `mcp__chrome-devtools__navigate_page` 跳转到目标 URL，避免新开。
   - 未命中：才调用 `mcp__chrome-devtools__new_page` 在新 Tab 中打开目标 URL。
3. 避免重复开启同一站点的多个 Tab，减少端口 / 会话残留对调试结果的干扰。
