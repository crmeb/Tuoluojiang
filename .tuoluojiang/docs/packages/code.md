# code · 开源后端

**工作目录**：`code/`（说法：server / 后端）  
**提交 scope**：`server`  
**权威约定**：[code/AGENTS.md](../../../code/AGENTS.md)

## 技术栈

PHP 8.0.2+（Docker 固定 8.0）、Laravel 9.52、LaravelS 3.7.43 + Swoole 4.8、MySQL 8、Redis 7 / Redis Stack、JWT、Casbin、Spatie Route Attributes、MCP。版本以 `code/composer.json` 为准。

## 分层

`Middleware → Controller → Service → Dao → Model → Database`

| 区域 | 路径 |
|------|------|
| 管理 / 企业 API | `app/Http/Controller/AdminApi` |
| 业务 / 数据 / 模型 / 校验 | `app/Http/Service`、`Dao`、`Model`、`Requests` |
| 框架包 | `tuoluojiang/`（`Tuoluojiang\Open\`） |
| MCP | `app/Mcp` |
| 队列与定时 | `app/Jobs`、`app/Task`、`config/laravels.php` |
| 安装结构 | `database/schema/mysql-schema.sql`、`public/install` |

## 硬约束

- 框架是明文 `tuoluojiang/`，没有 `crmeb/`，也没有 `tuoluojiang/basic/`。
- 验证与测试在 `code/docker` 的 PHP 容器内。本机不直接跑测试。
- 路由用属性注解。前缀 `ent/`、`admin/`、`open/`、`mcp/`。
- `$this->success()` / `fail()` 第一参数是消息。
- 新增或改动功能补中文 PHPDoc。类、public / protected 方法写清职责；枚举常量逐条注释。
- `code/.gitignore` 忽略了 `tests/`、`docs`、`docs-detailed`、`.claude`。这些路径即使本地存在，也不要当成已入库的权威文档。

## 常用命令

```bash
cd code/docker && ./manage.sh start
docker compose --env-file .docker.env exec php php artisan test
docker compose --env-file .docker.env exec php php artisan test --filter=DataPermission
docker compose --env-file .docker.env exec php php -l app/Path/File.php
```

LaravelS：在容器内用 `php bin/laravels start|reload|stop|info`。热更新已由 LaravelS 提供。

安装地址：`http://localhost:20300/install`。首次启动只建空库 `tuoluojiang`，业务表由安装页创建。
