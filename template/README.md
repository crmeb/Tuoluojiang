# 陀螺匠企业管理端

`admin/` 是 PC 企业管理后台，当前使用 Vue 3 + TypeScript。开发约定以 [AGENTS.md](./AGENTS.md) 和 [.agents/rules/](./.agents/rules/) 为准；按业务场景查阅 [.agents/skills/README.md](./.agents/skills/README.md)。

## 技术栈

- Vue 3.5、TypeScript 6、Vite 8、Vue Router 5。
- Element Plus、Pinia、Axios、SCSS、pnpm。
- 新建脚本使用 `.ts`，新建或完全重构的组件使用 `<script setup lang="ts">` + Composables。
- 存量 JavaScript / Options API 组件的小改动就地完成；项目仍允许 JS 混编。

版本范围以 [package.json](./package.json) 为准，锁定版本以 `pnpm-lock.yaml` 为准。

## 本地开发与验证

运行环境要求 Node.js ≥ 22.12、pnpm ≥ 10。在 `admin/` 目录执行：

```sh
pnpm install --frozen-lockfile
pnpm dev
pnpm typecheck
pnpm build
```

开发服务默认地址为 `http://localhost:9527/admin/`，生产构建输出到 `dist/`。其他脚本包括 `pnpm build:demo`、`pnpm analyze` 和 `pnpm preview`。`build:own` 会覆盖后端的管理端静态资源目录，执行前需确认目标路径。

个人环境配置按 `.env.example` 创建 `.env.development`，不要提交真实密钥或 Token。业务代码只从 `@/config/env` 具名导入环境变量；新增变量同步维护 `src/types/env.d.ts` 和 `src/config/env.ts`。

项目使用编辑器 ESLint 提示，不提供 `lint`、`format`、`test` 或 Prettier 脚本。编辑器使用 Vue - Official（Volar）和 ESLint 扩展，关闭保存时自动格式化。具体规则见 [.agents/rules/toolchain.md](./.agents/rules/toolchain.md)。

## 主要目录

```text
admin/
├── .agents/              # AI 规则与二开技能
├── build/plugins/        # Vite 构建插件
├── docs/                 # 二开手册与专项说明
├── patches/              # pnpm 依赖补丁
├── public/               # 公共静态资源
├── src/
│   ├── api/              # TypeScript API 模块，request.ts 统一请求封装
│   ├── bootstrap/        # 应用启动、插件与样式注册
│   ├── components/       # 公共组件、表单设计器与渲染器
│   ├── composables/      # 组合式函数
│   ├── config/           # 环境变量统一出口与业务配置
│   ├── constants/        # 弹窗尺寸等常量
│   ├── directive/        # Vue3 指令与按钮权限
│   ├── layout/           # 布局、菜单与工具栏
│   ├── router/           # Vue Router 静态路由与动态菜单映射
│   ├── stores/           # Pinia setup stores
│   ├── styles/           # SCSS、Element Plus 主题与组件覆盖
│   ├── types/            # 全局类型与接口类型
│   ├── utils/            # 通用工具
│   ├── views/            # 按业务模块组织的页面
│   ├── App.vue
│   ├── main.ts
│   ├── permission.ts
│   └── settings.ts
├── eslint.config.ts
├── tsconfig.json
├── vite.config.ts
└── package.json
```

业务页面主要分布在 `administration`（行政）、`customer`（客户）、`develop`（低代码）、`fd`（财务）、`hr`（人事）、`program`（项目）、`setting`（系统设置）、`user`（办公）及 `chat`（管理端 AI 功能）等目录。

## 二次开发入口

- API：在 `src/api/<scope>.ts` 中通过 `@/api/request` 定义接口，函数使用 `xxxApi` 命名。
- 路由：大多数页面通过后端菜单动态注册；固定业务路由放在 `src/router/modules/<module>.ts`，使用 `roterPre` 前缀。
- 状态：跨页面状态使用 `src/stores/` 下的 Pinia setup store；局部状态留在组件或 Composable 中。
- 样式：使用 SCSS 和 `:deep()`；主题入口为 `src/styles/element-variables.scss`，JS 读取样式变量走 `variables.module.scss`。
- 弹窗：遵守 [弹窗与侧滑规范](./docs/ai-ui-popup-guidelines.md)，使用统一尺寸常量。

当前手册见 [二次开发手册](./docs/二次开发手册/README.md)。两份标有“历史存档”的旧长文仅供理解旧版实现，不作为当前技术栈或开发步骤的依据。
