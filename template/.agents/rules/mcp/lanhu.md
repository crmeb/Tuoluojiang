# 蓝湖 MCP 使用规范

> 适用：蓝湖设计稿还原、取标注尺寸之前。索引见 [`README.md`](./README.md)。
>
> **底线**：只接受 stage 画板链接，`detailDetach` 预览链接不要直接调用

## 链接校验（重要）

调用 `mcp__lanhu__lanhu_design` 前必须先校验用户提供的 URL：

- ✅ **stage 页面**（完整画板）：URL 路径为 `/item/project/stage?tid=...&pid=...`
- ❌ **detailDetach 预览页**：URL 路径包含 `/detailDetach` 且带 `image_id=...&type=image`

如果用户提供的是 `detailDetach` 预览链接，**不要直接调用**，先让用户回到蓝湖 stage 页面复制完整链接。原因：detailDetach 返回的画板是 @0.5x 压缩预览（720×512），所有尺寸被折半甚至更小（例如 390×71 的卡片会变成 216×71 左右），推导出的像素值不可靠，会反复返工。

提示话术示例：

> 你给的是 detailDetach 预览链接，返回的尺寸会被压缩为 @0.5x，导致具体像素值推导不准。请在蓝湖打开 stage 画板视图后复制链接（URL 含 `/item/project/stage`），再发我。

## 调用参数

调用时默认携带完整 `include` 参数，保证拿到原始画板的精准尺寸：

```json
{
  "mode": "analyze",
  "include": ["html", "tokens", "layout", "layers"]
}
```

`layer_tree` 字段包含未经缩放的原始尺寸（来自 Sketch artboard），**优先以它为准**。`sketch_annotations` 里的坐标是 @0.5x 预览值，仅作定位参考。
