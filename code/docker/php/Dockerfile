# 陀螺匠 OA PHP 镜像
# - target=base：核心运行（含 poppler PDF 文本层，不含 OCR）
# - target=ocr ：完整知识库 OCR（默认，含 tesseract + ImageMagick CLI）
# Compose 通过 PHP_IMAGE_TARGET 选择；未指定时默认 ocr，保持现网行为。

ARG PHP_BASE_IMAGE=m.daocloud.io/docker.io/library/php:8.0-cli
ARG COMPOSER_IMAGE=m.daocloud.io/docker.io/library/composer:2

# COPY --from 不支持变量展开，需先声明为命名 stage
FROM ${COMPOSER_IMAGE} AS composer

FROM ${PHP_BASE_IMAGE} AS base

ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# PHP 8.0 基于 Debian 11 (bullseye)，已 EOL。
# 国内镜像站 debian-security 索引仍在但 .deb 大量 404，会导致 apt install exit 100。
# 构建仅用腾讯云 main/updates（不含 security），比阿里云 archive 更快且可装。
RUN rm -f /etc/apt/sources.list.d/* \
    && printf '%s\n' \
    "deb [check-valid-until=no] https://mirrors.cloud.tencent.com/debian bullseye main contrib non-free" \
    "deb [check-valid-until=no] https://mirrors.cloud.tencent.com/debian bullseye-updates main contrib non-free" \
    > /etc/apt/sources.list \
    && printf '%s\n' \
    'Acquire::Check-Valid-Until "false";' \
    'Acquire::Retries "5";' \
    'Acquire::http::Timeout "30";' \
    'Acquire::https::Timeout "30";' \
    > /etc/apt/apt.conf.d/99mirror-tune

# 系统依赖：核心运行 + PDF 文本层（pdftotext）。OCR 重依赖放在 ocr stage。
RUN apt-get -o Acquire::Retries=5 update && apt-get -o Acquire::Retries=5 install -y --no-install-recommends \
    git \
    curl \
    file \
    unzip \
    zip \
    default-mysql-client \
    poppler-utils \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo_mysql \
        mysqli \
        zip \
        bcmath \
        pcntl \
        sockets \
    && rm -rf /var/lib/apt/lists/*

# Swoole（PHP 8.0 兼容版本）+ Redis；一次 pecl 减少编译往返
RUN pecl install swoole-4.8.12 redis \
    && docker-php-ext-enable swoole redis

# Composer（多阶段复制，避免运行时 curl 安装器）
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_PROCESS_TIMEOUT=1200
ENV COMPOSER_HTTP_TIMEOUT=1200

WORKDIR /var/www

COPY ./docker/php/config/php.ini /usr/local/etc/php/conf.d/custom.ini

RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/bootstrap/cache \
    && chmod -R 777 /var/www/storage \
    && chmod -R 777 /var/www/bootstrap/cache

COPY ./docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 20200

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "./bin/laravels", "start", "--ignore"]

# ---------------------------------------------------------------------------
# OCR 完整镜像：知识库扫描件 / 分块图片 OCR（tesseract + ImageMagick convert）
# 未安装时业务会自动跳过 OCR；本 stage 保证 Docker 默认具备完整能力。
# ---------------------------------------------------------------------------
FROM base AS ocr

RUN apt-get -o Acquire::Retries=5 update && apt-get -o Acquire::Retries=5 install -y --no-install-recommends \
    imagemagick \
    tesseract-ocr \
    tesseract-ocr-chi-sim \
    tesseract-ocr-eng \
    && rm -rf /var/lib/apt/lists/*
