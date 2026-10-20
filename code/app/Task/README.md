# app/Task 说明

本目录存放 **Swoole Task**（`Hhxsv5\LaravelS\Swoole\Task\Task`），通过 `Task::deliver()` 在 Worker 内异步执行。

适用：HTTP 触发的通知/状态同步、CronJob 触发的轻量批处理。

完整选型规范见 [`docs-detailed/13-jobs-and-tasks.md`](../docs-detailed/13-jobs-and-tasks.md)。

## 域目录（小写，历史约定）

| 目录 | 对应 Jobs/Service 域 |
|------|----------------------|
| `customer/` | `Customer` — CRM 客户 |
| `approve/` | 审批 |
| `message/` | 消息通知 |
| `crud/` | 低代码触发器 |
| `export/` | 导入导出 |

> CRM 相关 Task 位于 `customer/`，与 `app/Jobs/Customer/`、`app/Http/Service/Customer/` 对应同一业务域。

## 禁止

- 在 Task 中执行超长阻塞 IO（应改用 Queue Job）
- 与同名 CronJob/Job 重复实现相同业务而不注明分工
