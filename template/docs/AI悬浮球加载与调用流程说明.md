# AI SDK 加载与调用说明

## 分工

OA 只通过 `src/libs/ai/index.js` 使用 AI SDK，不直接创建 iframe，也不直接调用
`plugin-loader.js`。AI 服务只提供一个 `/chat/sdk/index.js`，加载后暴露 `window.GyroChat`。

```text
业务页面
  → src/libs/ai/index.js          OA 场景适配与实例回收
  → src/libs/ai/plugin-loader.js  单例加载 SDK 脚本
  → window.GyroChat               AI SDK
```

SDK 样式已经内联到脚本，不需要独立加载 CSS。

## 全局悬浮入口

`permission.js` 在登录态路由切换时调用：

```js
aiFloatEntryController.ensure(token, route.path)
```

控制器读取企业配置中的 `ai_status` 与 `ai_image`，全局只维护一个 workspace chat 实例。
进入 AI、图标或鉴权设置页时调用实例 `hide()`，离开后 `show()`；企业配置改变时 `refresh()`
销毁并重建全局实例。

退出登录和 token 重置会调用 `AiEmbeddedManager.destroyAll()`，避免旧凭证实例残留。

## 应用列表“使用”

内部应用复用一个 workspace 实例：

```js
const instance = await AiEmbeddedManager.createTemporary({ token })
await instance.open(appId)
```

workspace 的 appId 切换发生在 AI 子应用内部，不重建 iframe。连续点击只执行尚未开始的最后一次意图。

外部应用的 shareKey 与访客启动上下文绑定。每次点击外部应用都执行：

```text
destroy 旧 visitor 实例
→ createTemporary({ identity: "visitor", shareKey })
→ instance.open()
```

OA 用本地 revision 丢弃脚本加载期间已经过时的创建结果。SDK 不提供动态 visitor 切换，也不在
实例内部替换 iframe。

## AI 整页

`views/chat/chat.vue` 把对话填满指定容器：

```js
const instance = await AiEmbeddedManager.createInline({ mount, token, appId })
await instance.whenReady()
```

传 mount 即表示 inline。页面用 15 秒兜底结束 loading，并在组件销毁或重试时销毁旧实例。

## 设置页实时预览

设置页通过 `createAdminPreview` 创建固定目标的 preview 实例：

```js
const preview = await AiEmbeddedManager.createAdminPreview({
  mount,
  identity,
  appId,
  shareKey,
  token,
})

await preview.update({ prologueText, prologueList })
```

目标只在创建时传入。表单侧对逐字更新做 100ms 尾部防抖，SDK 和父子协议不维护 target、revision
或 last-write-wins 状态机。

## 分享链接

`views/chat/utils/share.ts` 通过 SDK 生成地址：

```js
await getAiUrl({ identity: "visitor", shareKey })
```

路由前缀和路径由 AI SDK 负责，OA 不手拼 AI 项目内部路由。

## OA 适配层出口

```text
aiFloatEntryController.ensure(token, path)
aiFloatEntryController.refresh(token, path)

AiEmbeddedManager.createTemporary(options)
AiEmbeddedManager.createAdminPreview(options)
AiEmbeddedManager.createInline(options)
AiEmbeddedManager.refreshFloatEntryAppList()
AiEmbeddedManager.destroyAll()

getAiUrl(options)
```

业务页面只使用这些入口。实例必须在页面卸载时销毁；异步创建完成后还要检查页面是否已经卸载，
过期实例应立即 destroy。
