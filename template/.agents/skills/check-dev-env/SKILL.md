---
name: check-dev-env
description: 拉起本项目后体检本地开发环境——Node/pnpm 版本、依赖是否安装、锁文件与 patch 是否生效、Vite 预构建缓存是否需清理、后端地址（.env.development 与 vite.config.ts 代理）指向是否正确、dev 端口与访问路径。输出体检清单 + 修复建议，改动前必须征得用户同意。当用户说"项目跑不起来/启动报错/装好依赖了吗/配一下代理/检查开发环境/换后端地址"时触发。
---

# check-dev-env

新接手或拉新分支后，对本地开发环境做一次体检，定位「装没装、后端指哪、缓存要不要清」这类高频卡点。

## 原则（重要）

- **默认只读诊断**：先逐项检查、输出体检报告，**不要**擅自 `install` / 删缓存 / 改配置。
- **改动需明确同意**：安装依赖、删除预构建缓存、修改后端地址等动作，逐项征得用户确认后再执行。
- **后端地址绝不替用户提交**：`.env.development` 已在 `.gitignore` 中（个人本地文件），`vite.config.ts` 的代理 target 则是团队共享值，**不要 commit 对它的改动**。

## 体检清单（逐项跑）

### 1. 运行时版本
- 要求（`package.json > engines`）：**Node ≥ 22.12，pnpm ≥ 10**。
- 跑 `node -v` / `pnpm -v` 比对，低于要求提示用户升级。

### 2. 包管理器
- 本项目**只用 pnpm**，锁文件只有 `pnpm-lock.yaml`（已无 `package-lock.json`）。
- 发现 `package-lock.json` 或 `node_modules/.package-lock.json` 之类 npm 残留 → 提示用户删除并重跑 `pnpm install`，**禁止 npm / pnpm 混用**。

### 3. 依赖是否安装
- 检查 `node_modules` 是否存在、是否与 `pnpm-lock.yaml` 一致（刚切分支、锁文件变更时尤其要查）。
- 缺失或不一致 → 征得同意后 `pnpm install`。

### 4. 补丁与构建脚本许可
- 补丁走 **pnpm 原生 patch**，登记在 `pnpm-workspace.yaml > patchedDependencies`（当前为 `qrcodejs2@0.0.2`），`pnpm install` 时自动应用。**没有 patch-package，也没有 postinstall 脚本**。
- 若补丁未生效（运行时行为与未打补丁一致），提示重跑 `pnpm install`，必要时先删 `node_modules`。
- `pnpm-workspace.yaml > allowBuilds` 白名单控制哪些依赖允许跑安装脚本（esbuild、core-js 等）。安装时若 pnpm 提示某个包的 build 被忽略且确实影响运行，提示用户按需补进白名单，**不要擅自加**。

### 5. 缓存（构建异常时才查）
- Vite 预构建缓存在 `node_modules/.vite`。出现页面白屏 / 依赖改了不生效 / HMR 异常且代码本身无误时，多为预构建缓存陈旧。
- 现象匹配时提示：删除 `node_modules/.vite` 后重启，或用 `pnpm dev --force` 强制重新预构建。删除前先确认。

### 6. 后端地址（两个入口，先问清用哪个）
- **接口基址走 env**：`.env.development`（**已 gitignore，本地文件**）里的 `VITE_BASE_API` / `VITE_WS_URL` / `VITE_AI_BASE_URL`。文件缺失时以 `.env.example` 为模板新建。业务代码统一从 `@/config/env` 读，不要直接读 `import.meta.env`。
- **同源代理走 vite.config.ts**：`DEV_PROXY` 三条 `^/api`、`^/uploads`、`^/ws`（`ws: true`），默认 `target: http://dev.oa.crmeb.net`，`changeOrigin: true`，dev server 与 preview server 共用同一份。
- 询问用户后端要指向哪：
  - 用团队共享 dev → 保持默认。
  - 指向本地 / 自有后端 → **优先改 `.env.development`**（不会被提交）；确需改代理 target 时提示这是共享配置、改动不要 commit，且 `^/ws` 必须保留 `ws: true`。

### 7. dev 端口与访问路径
- `vite.config.ts > server`：`port: 9527`、`host: true`（局域网可访问）。端口被占用时提示释放，或用 `pnpm dev --port <n>`。
- 站点 base 为 `roterPre + '/'`（`src/settings.ts` 中 `roterPre = '/admin'`），根路径由 `build/plugins/redirect-root` 自动重定向，无需手动拼。

### 8. 启动
- 体检通过后：`pnpm dev`。
- 其他命令：`pnpm build` / `pnpm build:demo` / `pnpm analyze` / `pnpm preview` / `pnpm typecheck`。**项目没有 lint / format / test script**，不要建议用户去跑。

## 输出格式

给用户一张逐项的 ✅/⚠️/❌ 体检表 + 每个 ⚠️/❌ 的「建议动作」，把需要执行命令或改文件的项标出来，等用户点头再动手。

## 不要做的事

- 不要用 npm 安装，更不要 npm 和 pnpm 都跑一遍。
- 不要替用户修改并提交 `vite.config.ts` 的代理 target，也不要把私有后端地址写进会被提交的文件。
- 不要未经同意删除 `node_modules` 或 `node_modules/.vite`。
- 不要凭「应该装好了」下结论，逐项实际检查后再报告。
