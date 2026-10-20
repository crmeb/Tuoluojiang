#!/bin/bash
set -e

echo "🚀 启动陀螺匠OA系统Docker环境..."

OS_NAME=$(uname -s)
ARCH=$(uname -m)
PHP_EXT_DIR=$(php-config --extension-dir)
INI_DIR=/usr/local/etc/php/conf.d
PHP_THREAD_TYPE=$(php -r 'echo PHP_ZTS ? "ZTS" : "NTS";')
PHP_RUNTIME_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')

# 输出架构信息
echo "🔧 容器系统: $OS_NAME"
echo "🔧 容器架构: $ARCH"
echo "🔧 PHP运行版本: $PHP_RUNTIME_VERSION"
echo "🔧 PHP扩展目录: $PHP_EXT_DIR"
echo "🔧 PHP线程安全: $PHP_THREAD_TYPE"

if [ "$PHP_RUNTIME_VERSION" != "8.0" ]; then
    echo "❌ 当前Docker环境固定要求PHP 8.0，实际版本为 PHP $PHP_RUNTIME_VERSION"
    exit 1
fi

# 开源版核心代码为明文，无需 swoole_loader
echo "ℹ️  开源版跳过 swoole_loader 安装"
rm -f "$INI_DIR/swoole_loader.ini"

write_env_value() {
    local key=$1
    local value=$2

    if grep -q "^${key}=" /var/www/.env; then
        sed -i "s|^${key}=.*|${key}=${value}|" /var/www/.env
    else
        printf '%s=%s\n' "$key" "$value" >> /var/www/.env
    fi
}

read_env_value() {
    local key=$1
    local value

    value=$(awk -F= -v key="$key" '$1 == key {sub(/^[^=]*=/, ""); print; exit}' /var/www/.env 2>/dev/null || true)
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"

    printf '%s' "$value"
}

env_value_or_default() {
    local key=$1
    local default=$2
    local value

    if ! grep -q "^${key}=" /var/www/.env; then
        printf '%s' "$default"
    else
        value=$(read_env_value "$key")
        printf '%s' "$value"
    fi
}

echo "🔧 准备容器环境配置..."
if [ ! -f "/var/www/.env" ]; then
    cp /var/www/.env.example /var/www/.env

    write_env_value "APP_ENV" "local"
    write_env_value "APP_DEBUG" "true"
    write_env_value "DB_CONNECTION" "mysql"
    write_env_value "DB_HOST" "mysql"
    write_env_value "DB_PORT" "3306"
    write_env_value "DB_DATABASE" "tuoluojiang"
    write_env_value "DB_USERNAME" "root"
    write_env_value "DB_PASSWORD" "123456"
    write_env_value "CACHE_DRIVER" "redis"
    write_env_value "QUEUE_CONNECTION" "redis"
    write_env_value "REDIS_HOST" "redis"
    write_env_value "REDIS_PORT" "6379"
    write_env_value "REDIS_PASSWORD" ""
    write_env_value "REDIS_DB" "0"
    write_env_value "REDIS_CACHE_DB" "1"
    write_env_value "REDIS_KNOWLEDGE_DB" "0"
    write_env_value "KNOWLEDGE_VECTOR_DRIVER" "auto"
    write_env_value "LARAVELS_DAEMONIZE" "false"
    write_env_value "LARAVELS_LISTEN_PORT" "20200"
    write_env_value "LARAVELS_WEBSOCKET_ENABLE" "1"
else
    echo "ℹ️  检测到已有 .env，保留现有数据库和Redis配置"
fi

RUNTIME_DB_HOST=$(env_value_or_default "DB_HOST" "mysql")
RUNTIME_DB_PORT=$(env_value_or_default "DB_PORT" "3306")
RUNTIME_DB_USERNAME=$(env_value_or_default "DB_USERNAME" "root")
RUNTIME_DB_PASSWORD=$(env_value_or_default "DB_PASSWORD" "123456")
RUNTIME_REDIS_HOST=$(env_value_or_default "REDIS_HOST" "redis")
RUNTIME_REDIS_PORT=$(env_value_or_default "REDIS_PORT" "6379")

echo "🧹 清理Laravel启动缓存..."
rm -f /var/www/bootstrap/cache/config.php \
    /var/www/bootstrap/cache/events.php \
    /var/www/bootstrap/cache/packages.php \
    /var/www/bootstrap/cache/routes-*.php \
    /var/www/bootstrap/cache/services.php

if ! grep -q '^APP_KEY=base64:' /var/www/.env; then
    echo "🔑 生成应用密钥..."
    php artisan key:generate --force
fi

install_composer_dependencies=0
reset_composer_dependencies=0
COMPOSER_INSTALL_FAILED_MARKER=/var/www/storage/framework/docker-composer-install.failed

if [ ! -d "/var/www/vendor" ] || [ ! -f "/var/www/vendor/autoload.php" ]; then
    if [ -f "$COMPOSER_INSTALL_FAILED_MARKER" ]; then
        echo "❌ Composer依赖安装上次已经失败，已停止自动重试。"
        echo "   请检查上方Composer错误日志，处理网络或依赖问题后删除 $COMPOSER_INSTALL_FAILED_MARKER 再重启容器。"
        sleep infinity
    fi
    echo "📦 vendor不存在或autoload缺失，安装Composer依赖..."
    install_composer_dependencies=1
elif ! php -r 'require "/var/www/vendor/autoload.php";' >/tmp/composer-platform-check.log 2>&1; then
    if [ -f "$COMPOSER_INSTALL_FAILED_MARKER" ]; then
        echo "❌ Composer依赖安装上次已经失败，已停止自动重试。"
        echo "   请检查上方Composer错误日志，处理网络或依赖问题后删除 $COMPOSER_INSTALL_FAILED_MARKER 再重启容器。"
        sleep infinity
    fi
    echo "📦 vendor不可用，检查是否与当前PHP版本兼容..."
    cat /tmp/composer-platform-check.log 2>/dev/null || true
    install_composer_dependencies=1

    if grep -Eiq 'platform_check|requires php|requires ext-|your composer dependencies require|does not satisfy' /tmp/composer-platform-check.log; then
        echo "⚠️  检测到vendor与当前PHP平台不兼容，将删除vendor和composer.lock后重新生成"
        reset_composer_dependencies=1
    else
        echo "ℹ️  未检测到平台不兼容，保留composer.lock以便继续补齐未下载依赖"
    fi
fi

if [ "$install_composer_dependencies" = "1" ]; then
    rm -f "$COMPOSER_INSTALL_FAILED_MARKER"
    if [ "$reset_composer_dependencies" = "1" ]; then
        rm -rf /var/www/vendor /var/www/composer.lock
    else
        rm -rf /var/www/vendor
    fi
    composer config --global process-timeout "${COMPOSER_PROCESS_TIMEOUT:-1200}"
    composer config --global repos.packagist composer "${COMPOSER_REPO_PACKAGIST:-https://mirrors.cloud.tencent.com/composer/}"
    composer_install_attempt=1
    composer_install_max_attempts="${COMPOSER_INSTALL_MAX_ATTEMPTS:-3}"
    while ! COMPOSER_MAX_PARALLEL_HTTP="${COMPOSER_MAX_PARALLEL_HTTP:-4}" \
        composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader; do
        if [ "$composer_install_attempt" -ge "$composer_install_max_attempts" ]; then
            echo "❌ Composer使用国内镜像安装失败 ${composer_install_max_attempts} 次，停止自动重试。"
            touch "$COMPOSER_INSTALL_FAILED_MARKER"
            sleep infinity
        fi

        composer_install_attempt=$((composer_install_attempt + 1))
        echo "⚠️  Composer下载失败，3秒后进行第 ${composer_install_attempt}/${composer_install_max_attempts} 次尝试..."
        sleep 3
    done
    rm -f "$COMPOSER_INSTALL_FAILED_MARKER"
fi

# 等待MySQL服务就绪
echo "⏳ 等待MySQL服务启动..."
until mysqladmin ping -h "$RUNTIME_DB_HOST" -P "$RUNTIME_DB_PORT" -u"$RUNTIME_DB_USERNAME" -p"$RUNTIME_DB_PASSWORD" --silent; do
    sleep 2
done
echo "✅ MySQL服务已就绪"

echo "⏳ 等待Redis服务启动..."
until php -r '$h=$argv[1]; $p=(int)$argv[2]; $s=@fsockopen($h, $p, $e, $m, 2); exit($s ? 0 : 1);' "$RUNTIME_REDIS_HOST" "$RUNTIME_REDIS_PORT"; do
    sleep 2
done
echo "✅ Redis服务已就绪"

# 启动LaravelS服务
echo "🚀 启动LaravelS服务..."
exec "$@"
