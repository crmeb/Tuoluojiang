# 开发工作流

## 1. 锁定工作目录

对照 [../rules/workspace-routing.md](../rules/workspace-routing.md)：

| 别名 | 目录 | 提交 scope |
|------|------|------------|
| server | `code/` | `server` |
| admin | `template/` | `admin` |

跨目录任务按目录拆开。

## 2. 读约定

1. 仓库根 `AGENTS.md`（跨目录或从根目录开工时）
2. 已锁定目录的 `AGENTS.md`
3. 命中场景再读 Skill，索引见 [../skills/README.md](../skills/README.md)

## 3. 对齐邻近实现

同模块同层已有代码是风格模板：命名、响应包装、校验、权限、组件结构与邻近文件一致。

## 4. 后端落地（`code/`）

Controller 收请求并返回 `success` / `fail`。校验放 Request。事务和编排放 Service。查询放 Dao。  
新模块优先复用 `Tuoluojiang\Open\traits\ResourceControllerTrait` 与 `#[Resource]`，对照 `AdminApi/Customer/LabelController.php`。  
禁止 Controller 直接操作 Model。

## 5. 管理端落地（`template/`）

API → 路由或动态菜单 → 页面 / 弹窗 → 权限码。跨页状态用 Vuex（`create-vuex-module`，目录 `src/store/`）。按 `template/.agents/skills/README.md` 的顺序选用 skill。  
新建或完全重构的页面用 `<script setup lang="ts">`，约束在 `.agents/rules/vue27-ts.md`。动态菜单是默认接入方式（`integrate-dynamic-menu`）。静态路由用 `create-route`。

## 6. 验证

| 目录 | 最小验证 |
|------|----------|
| `code/` | 容器内 `php -l`；相关 `php artisan test --filter=...` |
| `template/` | `pnpm typecheck`；有本地 URL 时按浏览器验证 skill 看页面 |

## 7. 提交

见 [../rules/git-workflow.md](../rules/git-workflow.md)。
