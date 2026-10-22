# 安全与敏感区域

## 开源框架边界

- 框架目录是 `code/tuoluojiang/`，命名空间 `Tuoluojiang\Open\`。
- 基类在 `App\Http\{Dao,Model,Service}`、`App\Http\Controller\BaseController`、`App\Support\OpenAuth`。
- 这套明文结构和商业版 `crmeb/` 加密运行时不能互相整包替换。说明见 `code/tuoluojiang/README.md` 与 `code/AGENTS.md`。
- 开源版不含 `UniApi` 与 `uni/...` 路由。

## 认证与权限

- JWT、Casbin RBAC、企业上下文属于安全敏感区。
- 列表、详情、导出走既有数据权限过滤。改数据权限后，在 `code/docker` 的 PHP 容器内执行 `php artisan test --filter=DataPermission`（工作区若还没有对应测试，先补测试再改过滤逻辑）。
- MCP 写操作在 `app/Mcp/Tools/<Module>/*Tool.php` 里把 `getAction()` 标为 `write`，并更新 `config/mcp.php`。
- 开放接口走 `AuthOpenApi` 等既有中间件，见 `app/Http/Controller/AdminApi/Open/`。

## 密钥与配置

- 不把真实密钥、Token、生产连接串写入仓库或规范文档。
- `code/.env`、`template/.env.development` 留在本地。文档只用占位符。
- 表前缀默认 `eb_`（`DB_PREFIX`）。
- `import.meta.env.VITE_*` 会进浏览器包。业务代码从 `@/config/env` 具名导入，见 `template/.agents/rules/toolchain.md`。

## 前端权限

按钮和菜单显隐以后端菜单 / 权限接口为准。`v-hasPermi` 等指令只控制展示。新增权限码时读 `template/.agents/skills/register-permission/SKILL.md`。
