# 陀螺匠 · 企业助手

陀螺匠是企业级 OA 管理系统，后端基于 Laravel 9 + LaravelS/Swoole，集成 MySQL、Redis、JWT 认证、Casbin RBAC 权限、WebSocket、企业微信、WPS 在线文档等能力。

## 技术栈

| 类型 | 说明 |
|------|------|
| PHP | PHP 8.0.2+，Docker 环境固定使用 PHP 8.0 |
| 框架 | Laravel 9.52 |
| 常驻服务 | LaravelS 3.7.43 + Swoole 4.8 |
| 数据存储 | MySQL 8.0、Redis 7（Docker 使用 Redis Stack，含 RediSearch） |
| 认证授权 | JWT、Casbin RBAC |
| 路由 | spatie/laravel-route-attributes |
| Composer 源 | 腾讯云 Composer 镜像 |

## 运行要求

基础 PHP 扩展：

- `bcmath`
- `curl`
- `fileinfo`
- `gd`
- `json`
- `mbstring`
- `openssl`
- `pcntl`
- `pdo_mysql`
- `redis`
- `sockets`
- `swoole`
- `tokenizer`
- `xml`
- `zip`

本仓库为开源明文版，核心类已去除加密，**无需**安装 `swoole_loader`。

当前版本为 **2.6**，仅支持**全新安装**；不提供从开源 2.0 等旧版本升级到 2.6 的 SQL / 迁移命令。旧环境请备份后重装。

### 与商业版的结构差异

| 项 | 开源版 | 商业版 |
|----|--------|--------|
| 框架目录 | `tuoluojiang/` | `crmeb/`（含加密运行时） |
| 框架命名空间 | `Tuoluojiang\Open\` | `crmeb\` |
| 基类 Dao/Model/Service | `App\Http\{Dao,Model,Service}` | `crmeb\basic\*` |
| 控制器/授权基类 | `App\Http\Controller\BaseController` + `App\Support\OpenAuth` | `crmeb\basic\BaseController` / `BaseAuth` |
| 无 `basic/` 聚合目录 | 已拆到 App / services / traits | 商业版集中在 `crmeb/basic/` |
| 常用 Artisan | `open:init` / `open:http` | 商业版自有命令 |

开源包**不能**直接覆盖商业版加密文件或 `crmeb/` 目录使用。

开源版**不含移动端**：已移除 `UniApi` 控制器与 `uni/...` 路由；仅提供 PC/管理端与 MCP 等后端能力。

### 知识库 OCR 系统依赖（可选增强）

知识库支持 PDF 多图解析与分块 Markdown 内嵌图片 OCR。依赖系统级命令行工具；**未安装时会自动跳过 OCR**，不影响文档入库与纯文本向量化。

| 软件 | 用途 |
|------|------|
| `poppler-utils` | `pdftotext` 抽取 PDF 文本层；`pdftoppm` 将 PDF 页转为图片供 OCR |
| `tesseract-ocr` | OCR 引擎 |
| `tesseract-ocr-chi-sim` | 简体中文语言包 |
| `tesseract-ocr-eng` | 英文语言包（中英混排文档推荐一并安装） |

**Docker 环境**：默认构建 `ocr` 完整镜像（含 tesseract 中英语言包与 ImageMagick）。仅需更快构建、不测 OCR 时可在 `docker/.docker.env` 设 `PHP_IMAGE_TARGET=base` 后重建：

```shell
cd docker
# 默认完整 OCR
docker compose --env-file .docker.env up -d --build php
docker compose --env-file .docker.env exec php tesseract --list-langs
# 应能看到 chi_sim、eng
```

**裸机 Linux（Debian / Ubuntu 示例）**：

```shell
sudo apt-get update
sudo apt-get install -y poppler-utils tesseract-ocr tesseract-ocr-chi-sim tesseract-ocr-eng
pdftotext -v
tesseract --list-langs
```

**CentOS / RHEL（需可用 EPEL 等源）示例**：

```shell
sudo yum install -y poppler-utils tesseract tesseract-langpack-chi_sim tesseract-langpack-eng
```

相关环境变量（见 `.env.example` / `config/knowledge.php`）：

```env
# PDF / 分块图片 OCR；未装 tesseract 时自动跳过
KNOWLEDGE_PDF_OCR=true
KNOWLEDGE_PDF_OCR_LANG=chi_sim+eng
KNOWLEDGE_PDF_OCR_DPI=150
KNOWLEDGE_PDF_OCR_MAX_PAGES=100
# 单次 tesseract 超时（秒）；单图 Job 默认 = 该值 + 5（KNOWLEDGE_PDF_OCR_JOB_TIMEOUT=0）
KNOWLEDGE_PDF_OCR_COMMAND_TIMEOUT=30
KNOWLEDGE_PDF_OCR_JOB_TIMEOUT=0
# 单分块最多挂图 / OCR 张数
KNOWLEDGE_CHUNK_OCR_MAX_IMAGES=10
# OCR 入向量策略：auto（默认，截图/演示表不入 content）| caption_only | full
KNOWLEDGE_OCR_EMBED_MODE=auto
# 截图/演示表命中分数折减
KNOWLEDGE_OCR_SCREENSHOT_SCORE_FACTOR=0.85
# OCR 专用队列名（空=默认队列）。若填写，LaravelS/worker 须同时监听该队列，建议 OCR 并发 1～2
# KNOWLEDGE_OCR_QUEUE=knowledge_ocr
```

说明：

1. PHP 进程需能执行 `exec` / `command -v`（勿在 `disable_functions` 中禁用）。
2. 新增/编辑分块接口**仅接收 `content_html`（Markdown）**；服务端先解析纯文本落库并向量化，含图分片再按图异步 OCR 回填。客户端不要再传 `content`。
3. 文档处理关键路径：解析正文 → 持久化 `image_urls` → 文本分片 → 入队向量化 → 文档 `DONE`（**不跑 tesseract**）。随后对每张图投递一个 OCR 队列任务；成功后按 `KNOWLEDGE_OCR_EMBED_MODE` 决定是否合并进 `content`：截图/演示表 OCR 默认只写入 `metadata.ocr_text`，`content` 保留 caption，避免演示数据污染向量检索；扫描正文型 OCR 在 `auto` 下仍会合并。超时/失败保留原文与 `image_urls`，**不会**把文档标失败。
4. 存量已污染分片可执行 `php artisan knowledge:sanitize-ocr-embed`（可加 `--kb=` / `--dry-run`）剥离 image_only 中已合并的演示 OCR 并重向量化。
5. 部署/发版后请执行 `php bin/laravels reload`；日志应出现 `knowledge_pipeline_version=image_ocr_embed_policy_v1`。

### 知识库向量检索与 Redis Stack

知识库向量召回可走 Redis Stack 内置的 **RediSearch**（KNN）；MySQL 中的 `embedding` 仍是向量真源。

| 侧 | 是否需要额外安装 |
|----|------------------|
| **PHP** | **不需要**。沿用现有 `phpredis` / Laravel Redis 连接即可，通过原生命令（如 `MODULE LIST`、`FT.*`）访问 RediSearch，无需再装 PHP 扩展。 |
| **Redis 服务端** | **需要 Redis Stack**（或已加载 RediSearch 模块的 Redis）。普通 `redis:7` / `redis-server` **不含**向量索引能力。 |

**Docker 环境**：`docker-compose.yml` 已使用 `redis/redis-stack-server`，一般无需额外操作。验证：

```shell
cd docker
docker compose --env-file .docker.env exec redis redis-cli MODULE LIST
# 输出中应能看到 search / RediSearch 相关模块
```

**裸机 / 自建 Redis**：请部署 [Redis Stack](https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/)（或等价镜像 `redis/redis-stack-server`），不要用社区版纯 Redis 代替。将应用的 `REDIS_HOST` / `REDIS_PORT` 指向该实例。

相关配置（见 `.env.example` / `config/knowledge.php`）：

```env
# auto：优先 Redis Stack，不可用时回退 MySQL 线性扫描
# redis：仅用 Redis Stack（不可用则检索降级）
# mysql：仅用 MySQL，不访问向量索引
KNOWLEDGE_VECTOR_DRIVER=auto
# RediSearch 只能在 DB 0 建索引，请保持 0；与业务缓存用 key 前缀隔离
REDIS_KNOWLEDGE_DB=0
```

说明：

1. `auto` 为默认：未部署 Redis Stack 时知识库仍可用，只是向量检索回退 MySQL 扫描，数据量大时更慢。
2. 从普通 Redis 升级到 Redis Stack 后，可执行 `php artisan knowledge:vector-reindex` 将已有向量回填到 RediSearch。
3. 缓存、队列仍可共用同一 Redis 主机；知识库索引落在 `REDIS_KNOWLEDGE_DB`（建议 `0`）。

## Docker 初始化安装

推荐使用 Docker 完成首次安装。Docker 环境包含 PHP、MySQL、Redis 三个服务；首次启动只创建 `tuoluojiang` 空数据库，不创建业务表。业务表和初始化数据由浏览器安装页面创建。

### 1. 启动服务

```shell
cd docker
docker compose --env-file .docker.env up -d --build
```

也可以使用管理脚本：

```shell
cd docker
./manage.sh start
```

启动过程中 PHP 容器会自动完成：

1. 项目根目录不存在 `.env` 时，复制 `.env.example` 为 `.env`。
2. 将 `.env` 中的 MySQL / Redis 配置写为容器内服务地址。
3. `vendor` 不存在或与 PHP 8.0 不兼容时，重新执行 `composer install`。

容器内连接配置如下：

```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=tuoluojiang
DB_USERNAME=root
DB_PASSWORD=123456

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### 2. 检查服务状态

```shell
cd docker
docker compose --env-file .docker.env ps
```

确认 `php`、`mysql`、`redis` 三个服务均为 `healthy`。

宿主机访问端口：

| 服务 | 宿主机地址 | 容器内地址 |
|------|------------|------------|
| 应用 | `http://127.0.0.1:20300` | `php:20200` |
| MySQL | `127.0.0.1:3376` | `mysql:3306` |
| Redis | `127.0.0.1:6389` | `redis:6379` |

### 3. 确认数据库为空

首次安装前，`tuoluojiang` 数据库应存在，但业务表数量应为 `0`：

```shell
cd docker
docker compose --env-file .docker.env exec -T mysql \
  mysql -uroot -p123456 -e "SELECT COUNT(*) AS tables_count FROM information_schema.TABLES WHERE TABLE_SCHEMA='tuoluojiang';"
```

期望结果：

```text
tables_count
0
```

### 4. 访问安装页面

```text
http://127.0.0.1:20300/install
```

按页面提示完成系统安装。安装流程会创建业务数据表并写入初始化数据。

### 5. 清空 MySQL 重新安装

如果本机已存在旧 Docker 数据卷，MySQL 会复用旧数据。需要重新走安装流程时，先删除并重建 MySQL 数据卷：

```shell
cd docker
docker compose --env-file .docker.env down
docker volume rm tuoluojiang_mysql_data
docker compose --env-file .docker.env up -d --build
```

注意：删除 `tuoluojiang_mysql_data` 会清除 Docker MySQL 中已有数据。生产或已有业务数据环境请先备份。

## 手动安装流程

手动安装适用于已准备好 `.env`、Composer 依赖、应用密钥、PHP、MySQL、Redis、Swoole 的服务器（开源版无需 `swoole_loader`）。此处只说明服务启动和页面安装步骤。

若需知识库 PDF / 分块图片 OCR，请先按上文「知识库 OCR 系统依赖」安装 `poppler-utils` 与 `tesseract-ocr`（含中英文语言包）；未安装亦可正常使用，仅跳过 OCR。

1. 可选：创建本地文件软链：

   ```shell
   php artisan storage:link
   ```

2. 启动 LaravelS：

   ```shell
   php bin/laravels start
   ```

   生产环境可使用守护进程模式：

   ```shell
   php bin/laravels start -d
   ```

3. 通过浏览器访问 `/install` 完成系统安装。

## LaravelS 常用命令

```shell
php bin/laravels start
php bin/laravels start -d
php bin/laravels stop
php bin/laravels restart
php bin/laravels reload
php bin/laravels info
```

| 命令 | 说明 |
|------|------|
| `start` | 启动 LaravelS |
| `start -d` | 以守护进程方式启动 LaravelS |
| `stop` | 停止 LaravelS |
| `restart` | 重启 LaravelS |
| `reload` | 平滑重载 Worker / Task / Timer 进程 |
| `info` | 查看组件版本和服务信息 |

常用启动选项：

| 选项 | 说明 |
|------|------|
| `-d, --daemonize` | 守护进程模式 |
| `-e, --env` | 指定运行环境，例如 `--env=testing` |
| `-i, --ignore` | 忽略 Master 进程 PID 文件检查 |
| `-x, --x-version` | 写入当前工程版本号到运行环境 |

## 热更新开发

项目已内置热更新能力。开发环境启动后会自动监听代码变更并 reload LaravelS Worker，无需额外安装 `fswatch`、`inotify` 或执行额外监听命令。

## Docker 常用命令

以下命令均在 `docker` 目录执行：

```shell
cd docker

# 首次启动或 Dockerfile / entrypoint.sh 变更后启动
docker compose --env-file .docker.env up -d --build

# 日常启动
docker compose --env-file .docker.env up -d

# 查看状态
docker compose --env-file .docker.env ps

# 停止
docker compose --env-file .docker.env down

# 重启 PHP 服务
docker compose --env-file .docker.env restart php

# 查看日志
docker compose --env-file .docker.env logs -f
docker compose --env-file .docker.env logs -f php
docker compose --env-file .docker.env logs -f mysql
docker compose --env-file .docker.env logs -f redis

# 进入容器
docker compose --env-file .docker.env exec php bash
docker compose --env-file .docker.env exec mysql mysql -u root -p123456
docker compose --env-file .docker.env exec redis redis-cli
```

更多 Docker 说明见 [docker/README.md](docker/README.md)。

## 登录 Token 运维排查

管理端（`ent/`）使用 JWT access token + DB `refresh_token` 轮换。业务鉴权中间件过期返回 `410003`，由前端调用 `POST .../user/token/refresh` 续期。

| 检查项 | 建议 | 说明 |
|--------|------|------|
| `CACHE_DRIVER` | 生产务必 `redis` | JWT 黑名单与 refresh 回放缓存都走 Cache；`file` + Swoole 多 worker 会不一致 |
| `JWT_TTL` | 默认示例 `120`（分钟） | access token 有效期；过期依赖前端 refresh，不是中间件静默续期 |
| `single_login_switch` | 系统配置，默认开启 | 同账号同 client（web / Form-type）再次登录会踢掉旧会话 |
| refresh 回放窗口 | 服务端 300 秒 | 多 Tab 短时间内用旧 refresh 可拿到同一次轮换结果 |

掉线时优先区分：自然过期（约 `JWT_TTL`）、同端重登互踢、还是 refresh 失败（缺 `refresh_token` / 超过滑动窗 / 回放窗外的旧票）。

## 开发规范

### 命名规范

| 类型 | 规范 | 示例 |
|------|------|------|
| 类名 | PascalCase | `UserController` |
| 方法名 | camelCase | `getUserInfo` |
| 控制器方法 | 小写加下划线 | `get_client_ip` |
| 普通函数 | 小写加下划线 | `get_client_ip` |
| 属性 / 变量 | camelCase | `$userId` |
| 常量 | SNAKE_CASE | `MAX_UPLOAD_SIZE` |
| 数据表 | 小写加下划线 | `system_user` |
| 字段名 | 小写加下划线 | `user_name` |

### 分层约定

```text
HTTP Request
  -> Middleware
  -> Controller
  -> Service
  -> Dao
  -> Model
  -> Database
```

业务逻辑应放在 Service 层，数据访问应通过 Dao 层封装，避免 Controller 直接操作 Model 或跨层调用。

## 重要目录

```text
app/Http/Controller/   控制器
app/Http/Service/      业务服务
app/Http/Dao/          数据访问
app/Http/Model/        Eloquent 模型
app/Jobs/              队列任务
config/laravels.php    LaravelS / Swoole 配置
routes/install.php     安装路由
routes/mcp.php         MCP 路由
routes/websocket.php   WebSocket 路由
crmeb/                 基础能力、工具类和第三方服务封装
docker/                Docker 运行环境
```

## 帮助文档

- 官方文档：https://doc.crmeb.com/tuoluojiang

