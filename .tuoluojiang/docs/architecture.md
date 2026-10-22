# 系统架构

## 逻辑关系

```text
┌──────────────┐
│   template   │  PC 管理端  Vue 2.7 + Vite
└──────┬───────┘
       │ HTTP / JWT
┌──────▼───────┐
│     code     │  Laravel 9 + LaravelS / Swoole
└──────────────┘
```

两个目录独立构建。管理端通过配置和 Vite 代理访问后端，不直连数据库。

## 后端主链路

```text
HTTP → Middleware → Controller → Service → Dao → Model → Database
```

| 区域 | 路径 |
|------|------|
| 管理 / 企业 API | `code/app/Http/Controller/AdminApi` |
| 开放接口 | `code/app/Http/Controller/AdminApi/Open` |
| 业务 / 数据 / 模型 / 校验 | `code/app/Http/Service`、`Dao`、`Model`、`Requests` |
| 框架包 | `code/tuoluojiang/`，命名空间 `Tuoluojiang\Open\` |
| MCP | `code/app/Mcp`、`code/routes/mcp.php`、`code/config/mcp.php` |
| 队列与定时 | `code/app/Jobs`、`code/app/Task`、`code/config/laravels.php` |

路由使用 Spatie Route Attributes（`#[Prefix]`、`#[Resource]`、`#[Get]` 等）。业务 API 不写进 `routes/web.php`。前缀习惯：`ent/`、`admin/`、`open/`、`mcp/`。

资源控制器常见写法：继承 `AuthController`，实现 `Tuoluojiang\Open\interfaces\ResourceControllerInterface`，使用 `ResourceControllerTrait`。邻近例子：`code/app/Http/Controller/AdminApi/Customer/LabelController.php`。

响应经 `Tuoluojiang\Open\services\ApiResponseService`：`success($message, $content)` / `fail($message, $content)` 的第一参数是消息。

安装只支持全新安装：`code/database/schema/mysql-schema.sql` 与 `code/public/install`。当前版本说明见 `code/README.md`（2.6，无旧版升级 SQL）。

## 管理端

Vue 2.7、Vite 7、TypeScript 6（存量 JS 混编）、Element UI 2.15、Vue Router 3、Vuex 3、SCSS、pnpm。版本以 `template/package.json` 为准。  
路由前缀常量 `roterPre = '/admin'`（`template/src/settings.js`）。状态在 `template/src/store/`。  
新建脚本用 `.ts`，新建或完全重构的组件用 `<script setup lang="ts">`。存量 `.js` / Options API 的小改动就地完成。细则在 `template/AGENTS.md` 与 `.agents/rules/vue27-ts.md`。

## 运行时

- LaravelS / Swoole 长生命周期：请求处理里不存放「每请求私有」的静态状态。
- Docker 开发环境固定 PHP 8.0。开源明文版不需要 `swoole_loader`。
- 知识库向量检索依赖 Redis Stack（RediSearch）。Docker 只使用 `code/docker`，其中 Redis 为 Redis Stack。

## 延伸阅读

- [packages.md](./packages.md)
- [../rules/repo.md](../rules/repo.md)
- `code/README.md`、`code/AGENTS.md`、`template/AGENTS.md`
