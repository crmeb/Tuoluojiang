# 🐳 陀螺匠OA系统Docker集成环境

这是一个专为陀螺匠OA系统设计的完整Docker集成环境，包含PHP、MySQL、Redis三个核心服务。首次启动只初始化运行环境和空数据库，业务表与初始化数据通过浏览器安装页面创建。

## 📁 目录结构

```
docker/
├── docker-compose.yml          # Docker编排（实例名/端口由 env 注入）
├── docker-compose.ha.yml       # 高可用部署示例配置
├── .docker.env                 # 基线配置（#1 的默认名与端口）
├── .docker.instance.env        # 自动生成：当前绑定实例（勿提交）
├── manage.sh                   # 管理脚本（启动时自动分配实例编号）
├── README.md                   # 使用说明
├── php/
│   ├── Dockerfile              # PHP CLI镜像构建文件
│   ├── entrypoint.sh           # PHP容器入口脚本
│   └── config/
│       └── php.ini             # PHP配置文件
├── mysql/
│   ├── config/
│   │   └── my.cnf              # MySQL配置文件
│   ├── data/                   # 历史目录；当前MySQL数据以Docker命名卷为准
│   ├── logs/                   # MySQL日志目录
│   └── init/
│       └── init-db.sh          # 数据库初始化脚本：仅创建数据库和授权用户
└── redis/
    ├── config/
    │   └── redis.conf          # Redis配置文件
    └── data/                   # Redis数据目录
```

## 🚀 核心特性

### PHP环境 (8.0 CLI)
- ✅ 基于PHP 8.0 CLI版本，专为LaravelS优化
- ✅ 集成Swoole扩展，支持高性能异步处理
- ✅ 集成Redis扩展，提供缓存和队列支持
- ✅ 固定PHP 8.0；开源明文版无需 `swoole_loader`
- ✅ 预装Composer依赖管理工具，默认使用腾讯云Composer镜像
- ✅ 优化的PHP配置参数
- ✅ 多阶段镜像：`base`（轻量，含 `poppler-utils`）与 `ocr`（完整，默认；另含 tesseract 中英语言包 + ImageMagick `convert`）
- ✅ 知识库 OCR：完整镜像覆盖多图 PDF 与分块 Markdown 内嵌图片 OCR（`base` 未装 tesseract 时自动跳过，详见根目录 [README.md](../README.md)「知识库 OCR 系统依赖」）

### MySQL数据库 (8.0)
- ✅ UTF8MB4字符集支持，完美支持中文
- ✅ 优化的性能配置参数
- ✅ 自动创建空数据库 `tuoluojiang`
- ✅ 使用Docker命名卷持久化数据
- ✅ 详细的慢查询日志记录

### Redis（Redis Stack）
- ✅ 镜像为 `redis/redis-stack-server`（含 **RediSearch**，供知识库向量 KNN）
- ✅ 完整的配置与 AOF 持久化
- ✅ 与缓存/队列共用同一实例；知识库索引使用 `REDIS_KNOWLEDGE_DB=0` + key 前缀隔离
- ✅ 普通社区版 Redis 无 RediSearch；裸机部署需自行安装 Redis Stack（详见根目录 [README.md](../README.md)「知识库向量检索与 Redis Stack」）

## 📦 初始化安装流程

### 1. 准备环境

确认本机已安装 Docker Engine / Docker Desktop。

**同一套代码目录只允许一套 Docker 容器。** 要并行多套，请用独立目录或 `git worktree`，再在新目录里 `./manage.sh start --new`（自动避开已占用端口/容器名）。

项目 Docker 初始化会自动处理：

- 仅当项目根目录 `.env` 不存在时，复制 `.env.example` 为 `.env`
- 仅首次生成 `.env` 时写入容器内 MySQL / Redis 默认地址；后续启动保留现有 `.env` 配置，便于调试外部数据库
- 固定 PHP 8.0；开源版跳过 `swoole_loader`
- 启动前清理 Laravel 可再生缓存，避免旧 provider 缓存影响启动
- 使用 PHP 8.0 重新安装不兼容或缺失的 `vendor`

> Docker 容器内始终是 Linux 环境。开源版核心代码为明文，无需加密扩展。

### 2. 启动服务
```bash
cd docker
./manage.sh start
# 或者直接使用docker compose
docker compose --env-file .docker.env up -d
```

### 3. 验证服务状态
```bash
./manage.sh status
# 或者
docker compose --env-file .docker.env ps
```

三个服务均为 `healthy` 后继续安装。

### 4. 确认数据库为空

首次安装时，MySQL 容器只应创建 `tuoluojiang` 数据库，不应创建业务表：

```bash
docker compose --env-file .docker.env exec -T mysql \
  mysql -uroot -p123456 -e "SELECT COUNT(*) AS tables_count FROM information_schema.TABLES WHERE TABLE_SCHEMA='tuoluojiang';"
```

期望结果：

```text
tables_count
0
```

### 5. 访问安装页面

- **安装地址**: http://localhost:20300/install
- **应用地址**: http://localhost:20300
- **MySQL管理**: `127.0.0.1:3376`（仅本机；用户 root / 密码见 `.docker.env`）
- **Redis Stack 服务**: `127.0.0.1:6389`（仅本机；含 RediSearch）

MySQL / Redis 默认发布到 `HOST_PUBLISH_BIND=127.0.0.1`，**不对公网开放**。容器内 PHP 仍通过服务名 `mysql` / `redis` 互访，不受影响。远程用 Navicat 等客户端时，请走 **SSH 隧道**（SSH 主机填服务器，数据库主机填 `127.0.0.1`、端口填上表映射口）。确需局域网直连时，可在 `.docker.env` 将 `HOST_PUBLISH_BIND` 改为 `0.0.0.0` 后重建容器（不推荐对公网）。

按安装页面提示完成系统安装。安装流程会创建业务数据表并写入初始化数据。

## 🛠️ 管理命令

### 使用管理脚本 (推荐)
```bash
./manage.sh start       # 启动本目录当前绑定
./manage.sh start --new # 本目录尚无实例时新建（端口冲突则自动换编号）
./manage.sh stop        # 停止
./manage.sh restart     # 重启
./manage.sh status      # 状态
./manage.sh logs        # 日志
./manage.sh info        # 当前绑定
./manage.sh list        # 列出实例
./manage.sh help
```

### 多实例（必须独立代码目录）

同一仓库工作区会共用 `.env` / `install.lock` / `storage`，**禁止**在同一目录 `start --new` 再拉一套。

```bash
# 目录 A
cd /path/to/oa-own/docker && ./manage.sh start

# 目录 B（独立代码）
git -C /path/to/oa-own worktree add ../oa-own-2 HEAD
cd ../oa-own-2/docker && ./manage.sh start --new
```

**常用操作：**

```bash
./manage.sh list
./manage.sh stop              # 停当前绑定
./manage.sh stop all          # 停全部（慎用）
./manage.sh start             # 启当前绑定
```

### 直接使用Docker Compose
```bash
# 推荐仍走 manage.sh；若手动 compose，请使用已生成的实例文件：
docker compose --env-file .docker.instance.env up -d
docker compose --env-file .docker.instance.env down
docker compose --env-file .docker.instance.env logs -f
docker compose --env-file .docker.instance.env exec php bash
```

## 🔧 环境配置

### 基线变量（`.docker.env`，一般不用改）

```
INSTANCE_NAME=tuoluojiang   # #1 名称；#N 自动变为 tuoluojiang_N
HOST_APP_PORT=20300         # #N 端口 = 基线 + (N-1)
HOST_PUBLISH_BIND=127.0.0.1 # MySQL/Redis 宿主机绑定；默认仅本机
HOST_MYSQL_PORT=3376
HOST_REDIS_PORT=6389
PHP_IMAGE_TARGET=ocr        # ocr=完整知识库 OCR（默认，镜像标签 :ocr）；base=轻量（:base，构建更快）
```

切换轻量镜像（不测 OCR 时）：在 `.docker.env` 设 `PHP_IMAGE_TARGET=base` 后重建：

```bash
# 改 .docker.env 后（manage.sh 会把 target 写入 .docker.instance.env）
./manage.sh stop
./manage.sh start
# 或
docker compose --env-file .docker.env build --no-cache php
docker compose --env-file .docker.env up -d php
```

验证 OCR 完整镜像（默认 `tuoluojiang-php:ocr`）：

```bash
docker compose --env-file .docker.env exec php tesseract --list-langs
# 应能看到 chi_sim、eng
docker compose --env-file .docker.env exec php convert -version
```

### 数据库配置
```
主机: mysql (容器内) / 127.0.0.1 (宿主机本机或 SSH 隧道对端)
端口: 3306 (容器内) / 见 ./manage.sh info
数据库: tuoluojiang
用户名: root
密码: 123456
字符集: utf8mb4
```

### Redis配置
```
主机: redis (容器内) / 127.0.0.1 (宿主机本机或 SSH 隧道对端)
端口: 6379 (容器内) / 见 ./manage.sh info
密码: 无
数据库: 0-15
```

### PHP配置
```
版本: 8.0 CLI
内存限制: 512M
最大执行时间: 300秒
上传文件大小: 100M
扩展: Swoole, Redis, PDO MySQL, GD, zip, bcmath, pcntl, sockets, mysqli
构建目标: PHP_IMAGE_TARGET=ocr|base（默认 ocr）
```

### Composer配置
```
镜像源: https://mirrors.cloud.tencent.com/composer/
安装命令: composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
```

## 💾 数据持久化

所有重要数据均已配置持久化存储：
- **MySQL数据**: Docker命名卷 `{INSTANCE_NAME}_mysql_data`（默认 `tuoluojiang_mysql_data`）
- **MySQL日志**: `MYSQL_LOG_PATH`（默认 `./mysql/logs/`）
- **Redis数据**: `REDIS_DATA_PATH`（默认 `./redis/data/`）
- **应用代码**: 通过volume挂载实现实时同步


### 清空MySQL重新安装

如果本机已经存在旧数据卷，MySQL 会复用旧数据。重新走安装流程前需要删除并重建 MySQL 数据卷：

```bash
cd docker
docker compose --env-file .docker.env down
docker volume rm tuoluojiang_mysql_data
docker compose --env-file .docker.env up -d --build
```

注意：删除 `tuoluojiang_mysql_data` 会清除 Docker MySQL 中的全部数据。已有业务数据时请先备份。

## 🔧 开发工具

### 应用管理
```bash
# 执行Artisan命令
docker compose --env-file .docker.env exec php php artisan cache:clear

# Composer操作
docker compose --env-file .docker.env exec php composer install
docker compose --env-file .docker.env exec php composer update
```

### 版本说明（无升级路径）

开源版 **2.6 仅支持全新安装**（由安装向导导入 `database/schema/mysql-schema.sql` 与 `public/install/*.sql`）。

不提供从 2.0 等旧版本升级到 2.6 的 SQL / `tl:up` / `database/migrations/v*` / `database/seeders/v*`。已有旧库请自行备份后重装，或自行编写迁移。

### 数据库管理
```bash
# 数据库备份
docker compose --env-file .docker.env exec mysql mysqldump -u root -p123456 tuoluojiang > backup.sql

# 数据库恢复
docker compose --env-file .docker.env exec -T mysql mysql -u root -p123456 tuoluojiang < backup.sql

# 进入MySQL命令行
docker compose --env-file .docker.env exec mysql mysql -u root -p123456
```

## ⚠️ 注意事项

### 首次使用
1. **构建时间**: 首次启动需要下载基础镜像和构建，耗时较长
2. **磁盘空间**: 确保有足够的磁盘空间（建议至少5GB）
3. **内存要求**: 建议系统内存至少4GB

### 运行环境
1. **端口占用**: 确保20300、3376、6389端口未被占用
2. **权限设置**: 确保docker目录具有适当的读写权限
3. **Docker版本**: 建议使用Docker Engine 20.10+版本

### 生产部署
1. **安全配置**: 生产环境请修改默认密码
2. **备份策略**: 建立定期备份机制
3. **监控告警**: 配置系统监控和异常告警
4. **资源限制**: 根据实际需求调整容器资源限制

## 🆘 故障排除

### 常见问题

**1. 端口冲突**

无需手工改端口。新建实例请执行 `./manage.sh start --new`；`./manage.sh info` / `list` 查看实际端口。

**2. 权限问题**
```bash
# 修复目录权限
sudo chown -R $(id -u):$(id -g) .
sudo chmod -R 755 .
```

**3. 构建失败**
```bash
# 清理构建缓存
docker builder prune -f
docker compose --env-file .docker.env build --no-cache
```

**4. 服务无法启动**
```bash
# 查看详细日志
docker compose --env-file .docker.env logs -f --tail=100
docker compose --env-file .docker.env logs php
docker compose --env-file .docker.env logs mysql
docker compose --env-file .docker.env logs redis
```

**5. Composer安装失败**
```bash
# 查看PHP容器日志
docker compose --env-file .docker.env logs php

# 如果日志提示已停止自动重试，处理网络后删除失败标记并重启
rm -f ../storage/framework/docker-composer-install.failed
docker compose --env-file .docker.env restart php
```

**6. 数据库不是空库**
```bash
# 确认表数量
docker compose --env-file .docker.env exec -T mysql \
  mysql -uroot -p123456 -e "SELECT COUNT(*) AS tables_count FROM information_schema.TABLES WHERE TABLE_SCHEMA='tuoluojiang';"

# 需要重新安装时清空数据卷（按实例名替换）
docker compose --env-file .docker.env down
docker volume rm tuoluojiang_mysql_data
# 第二套示例: docker volume rm tuoluojiang_dev2_mysql_data
docker compose --env-file .docker.env up -d --build
```

### 日志查看
```bash
# 实时查看所有服务日志
./manage.sh logs

# 查看特定服务日志
docker compose --env-file .docker.env logs -f php
docker compose --env-file .docker.env logs -f mysql
docker compose --env-file .docker.env logs -f redis

# 查看最近的日志
docker compose --env-file .docker.env logs --tail=50
```

## 🧱 高可用配置

当前默认使用 `docker-compose.yml` 作为本地开发和单机初始化配置。已额外保留可独立启动的 `docker-compose.ha.yml`，包含 Nginx 上游转发、两个 PHP/LaravelS 节点、持久化卷和 Redis AOF 策略。HA 同样读取 `.docker.env` 中的 `INSTANCE_NAME` / `HOST_*_PORT`（项目名会自动加 `_ha` 后缀，卷名为 `{INSTANCE_NAME}_ha_*`）：

```bash
cd docker
docker compose --env-file .docker.env -f docker-compose.ha.yml up -d --build
```

注意：这份配置保留为高可用部署模板。与默认单机栈不要共用同一组 `HOST_*_PORT`；生产使用时建议放在 Swarm、Kubernetes 或外部 SLB 后面，并将 MySQL/Redis 替换为托管集群或主从哨兵方案。

## 📊 性能优化建议

### PHP优化
- 调整OPcache配置参数
- 根据应用负载调整进程数
- 启用适当的预加载机制

### MySQL优化
- 根据数据量调整缓冲池大小
- 优化慢查询SQL语句
- 定期分析表和索引

### Redis优化
- 根据使用场景选择合适的持久化策略
- 配置适当的内存淘汰策略
- 监控内存使用情况

---
**提示**: 本环境已移除原有的integrated目录结构，采用扁平化设计，更便于日常使用和维护。
