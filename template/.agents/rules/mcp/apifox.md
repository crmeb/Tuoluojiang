# Apifox CLI 使用规范

> 适用：查接口、查数据模型、对接后端字段之前。索引见 [`README.md`](./README.md)。
>
> **底线**：只读 · 只查项目 `1530621` · `list` 必须 `jq` 过滤 · 禁止 `head` / `tail` 截断

接口信息查询统一走 Apifox CLI（`apifox` 命令 + `.agents/skills/apifox-cli/SKILL.md`），**不再使用 apifox MCP**。该 skill 是第三方通用能力包，描述了本项目禁止的写入流程，**以本文为准**。

## 只读原则（强制）

本项目**仅用于查询接口 / 数据模型信息**，禁止任何写入或有副作用的操作。

- ✅ 允许的只读命令：`--help`、`--version`、各资源的 `list` / `get` 子命令、`apifox project list`、`apifox cli-schema list|get`
  - 例：`apifox endpoint list --project 1530621`
  - 例：`apifox endpoint get <endpointId> --project 1530621`
  - 例：`apifox schema get <schemaId> --project 1530621`
- ❌ 禁止的写入 / 副作用命令：`create` / `update` / `delete`、`import` / `export`、`run` / `test-*` / `runner` / `scheduled-task`、`branch`（含创建 AI 分支）/ `merge` / `merge-request`、`login` / `logout`
- ❌ 禁止写入本地配置：`~/.apifox/config.toml`、项目内 `.apifox/settings.json`
- apifox-cli skill 中描述的写入流程（AI 分支、`cli-schema validate` 后写入、合并请求等）在本项目**一律不执行**；用户明确要求写入时，先复述本约束并取得二次确认后才执行。

## 项目锁定

- **仅查询 id 为 `1530621` 的 `Tuoluojiang` 项目**，禁止跨项目读取其他项目的接口数据。
- 项目 ID 已记录在 `.apifox/settings.json`；命令一律显式带 `--project 1530621`，不依赖隐式默认值。

## 输出处理纪律（强制）

本项目 Apifox 有 1390+ 接口、300+ 目录，`endpoint list` 全量输出约 280KB（≈ 8 万 token），耗时约 3 秒。CLI **没有服务端搜索能力**，检索只能靠本地过滤，因此以下两条是硬性纪律：

- **必须在 shell 管道内用 `jq` 过滤，禁止把 list 类命令的原始输出直接读入上下文。** 过滤在管道内完成，只有命中结果进上下文（实测：全量 280KB → 过滤后约 300 token）。不加过滤直接读 list 输出会一次性吃掉大半个上下文窗口。
- **检索类查询禁止用 `head` / `tail` 截断输出。** 截断会让「没搜到」与「被砍掉」无法区分，从而误判资源不存在——已实际发生：`head -30` 恰好砍掉真实存在的 `v2.5/自动拉群/群活码` 目录，据此误报该目录不存在。需要控制输出量时，先用 `jq '... | length'` 取计数，再决定全量输出或分批，且**计数必须与实际输出行数对得上**。

## 检索链路

`endpoint list` 只返回 `id/name/method/path/status/folderId`，**不含目录名**；按目录检索必须先经 `folder list` 拿 id 中转：

```bash
# 1. 目录树（带完整 path，如 v2.5/自动拉群/群活码）
apifox folder list --project 1530621 --type endpoint \
  | jq -r '.data[] | select(.path | test("<关键字>")) | "\(.id)\t\(.path)"'

# 2. 按 folderId 过滤接口；也可直接按 name / path 关键字过滤
apifox endpoint list --project 1530621 \
  | jq -r '.data[] | select(.folderId==<folderId>) | "\(.id)\t\(.method)\t\(.path)\t\(.name)"'

# 3. 取单个接口的参数与响应结构（一次只能一个，批量前先与用户确认范围）
apifox endpoint get <endpointId> --project 1530621
```

- 接口 URL 片段（如 `channel_code`）是最可靠的检索关键字，不受中文命名习惯影响。
- 需要分页时用 `--page 1 --page-size 500`；但配合 `jq` 过滤时通常直接全量拉取即可。

## 故障恢复

未登录或 token 失效时**不要自行登录**，提示用户执行 `apifox login --with-token <TOKEN>`；禁止把 token 打印到日志、写入仓库或出现在对话摘要中。
