# Repository Guidelines

> 本文件是 Cursor / Codex / Claude Code 的**唯一 always-on 约定源**。详述参考见 `docs-detailed/agent-context-reference.md`（按需读取，勿再写入 always-on）。

## 项目概览

陀螺匠 · 企业助手 OA/CRM 后端（开源版）：PHP 8.0+、Laravel 9.52、LaravelS/Swoole、MySQL 8、Redis 7、JWT、Casbin、企业微信、MCP。Docker 固定 PHP 8.0。

框架包目录为 `tuoluojiang/`，命名空间 `Tuoluojiang\Open\`（**不是**商业版的 `crmeb/` 加密运行时；**无** `tuoluojiang/basic` 目录）。基类拆分：`BaseDao`/`BaseModel`/`BaseService` → `app/Http/{Dao,Model,Service}`；`BaseController` → `app/Http/Controller`；授权桩 → `App\Support\OpenAuth`。开源明文与商业加密包**不兼容、不可直接替换**。

## 分层与目录

`Middleware → Controller → Service → Dao → Model → Database`。Controller 只收请求、调 Service、返回 `$this->success()`/`$this->fail()`；校验放 Request，事务/编排放 Service，查询放 Dao。**禁止 Controller 直操 Model 或跨层调用**。

| 区域 | 路径 |
|------|------|
| 管理端 API | `app/Http/Controller/AdminApi`（开源版不含移动端 UniApi） |
| 业务 / 数据 / 模型 / 校验 | `app/Http/Service` / `Dao` / `Model` / `Requests` |
| MCP | `app/Mcp` |
| 队列与定时 | `app/Jobs`、`app/Task`、`config/laravels.php` |
| 安装结构 | `database/schema/mysql-schema.sql` + `public/install/*.sql`（仅支持全新安装，无版本升级路径） |

## 开发原则

- **克制新增文件**：简单逻辑优先落在现有同模块同层文件；仅职责清晰、复用面广或文件明显过重时才拆新文件。
- **全局思维与克制重构**：改前梳理调用链；小修优先最小安全改动；确需整理则局部有界，禁止无关大重构。

## 命令（必须在 Docker PHP 容器内验证）

```bash
cd docker && ./manage.sh start
docker compose --env-file .docker.env exec php php artisan test
docker compose --env-file .docker.env exec php php artisan test --filter=DataPermission
docker compose --env-file .docker.env exec php php -l app/Path/File.php
```

本机勿直接跑测试。LaravelS 已热更新，勿再装 fswatch/inotify。`php bin/laravels start|reload|stop|info` 管理常驻进程。

## 路由、响应、命名

- 路由用 Spatie 属性（`#[Prefix]`/`#[Resource]`/`#[Get]`…），业务 API 不写 `routes/web.php`。前缀：`ent/...`、`admin/...`、`mcp/...`（开源版无 `uni/...` 移动端路由）。
- 新增模块优先复用 `tuoluojiang/traits`（`Tuoluojiang\Open\traits`）+ `#[Resource]`。
- `$this->success()`/`fail()` **第一参数按消息理解**；传数据时对照邻近控制器，避免消息/内容写反。
- 类 PascalCase；方法/属性 camelCase；控制器可外调方法与自由函数小写下划线；常量 SNAKE_CASE；表字段小写下划线。保留 `declare(strict_types=1);` 与项目头注释。

## 注释与文档

新增/改动功能须中文 PHPDoc，并与 `docs/swagger/` 同步。类写职责与约束；public/protected 写 `@param`/`@return`/`@throws`；枚举每个常量单独注释。不必为无逻辑 accessor 或纯翻译方法名写注释。

## 测试与提交

- 测试：`*Test.php`，放 `tests/Unit|<Domain>`、`Feature`、`Integration`。共享服务、数据权限、MCP、控制台命令优先补定向测试。
- 提交：遵循仓库级 `.tuoluojiang/rules/git-workflow.md`，使用 `type(server[-module]): 中文描述`；`module` 可选且使用英文 kebab-case，例如 `feat(server-customer): 新增客户批量导入接口`。未经用户要求不 `git commit`/`push`，不用 `--no-verify`。

## 敏感区域与踩坑

- 定位调用链优先 `.codegraph/`，再 grep。
- 改数据权限必跑容器内 `--filter=DataPermission`；列表/详情/导出须挂权限过滤。
- MCP：`app/Mcp/Tools/<Module>/*Tool.php` 实现 `ToolInterface`；写操作 `getAction()` 返回 `write`；更新 `config/mcp.php` 并补测试。
- Swoole 长生命周期：勿在请求处理中持有“每请求私有”静态/单例状态；新 `CronJob` 挂到 `config/laravels.php`。
- 表前缀 `eb_`（`DB_PREFIX`）。密钥/Token 不入库。

按需深读：`docs-detailed/agent-context-reference.md`、`README.md`、`tree.md`。
