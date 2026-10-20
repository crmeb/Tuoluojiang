# app/Jobs 说明

本目录存放 **LaravelS 定时任务（CronJob）** 与 **Laravel 队列任务（ShouldQueue Job）**。

- **CronJob**：在 `config/laravels.php` 的 `timer` 中注册，只负责按周期触发下游任务
- **Queue Job**：通过 `::dispatch()` 投递，处理耗时、需重试或去重的业务

完整选型规范见 [`docs-detailed/13-jobs-and-tasks.md`](../docs-detailed/13-jobs-and-tasks.md)。

## 域目录（PascalCase）

| 目录 | 说明 |
|------|------|
| `Customer/` | CRM 客户域（合同、线索、回款、跟进等） |
| `Work/` | 企业微信 |
| `Assess/` | 绩效考核 |
| `Attend/` | 考勤 |
| `Crud/` | 低代码 |
| `Knowledge/` | 知识库 |

## 禁止

- 在 `*CronJob` 中编写复杂业务逻辑（应下沉到 `*Job` 或 `app/Task/`）
- 使用已废弃的 `Jobs/Client/` 路径（已统一为 `Jobs/Customer/`）
