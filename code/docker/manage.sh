#!/bin/bash

# 陀螺匠OA系统 Docker 环境管理脚本
# 启动时自动检测容器名/端口占用，冲突则递增编号分配新实例。

set -e

BASE_ENV_FILE="${BASE_ENV_FILE:-.docker.env}"
INSTANCE_ENV_FILE="${INSTANCE_ENV_FILE:-.docker.instance.env}"
DOCKER_ENV_FILE="${DOCKER_ENV_FILE:-}"
FORCE_NEW_INSTANCE=0
# 指定操作目标实例编号（不传则用当前绑定）；use 命令会改写绑定
TARGET_INSTANCE_INDEX=""
MAX_INSTANCE_INDEX=50
TEMPORARY_ENV_FILE=""

# 颜色
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ---------- 工具 ----------

parse_global_opts() {
    while [ $# -gt 0 ]; do
        case "$1" in
            -e|--env-file)
                [ -n "${2:-}" ] || { echo -e "${RED}❌ -e/--env-file 需要文件路径${NC}"; exit 1; }
                DOCKER_ENV_FILE="$2"
                shift 2
                ;;
            -n|--instance)
                [ -n "${2:-}" ] || { echo -e "${RED}❌ -n/--instance 需要实例编号，如 1、2${NC}"; exit 1; }
                TARGET_INSTANCE_INDEX="$2"
                shift 2
                ;;
            --new)
                FORCE_NEW_INSTANCE=1
                shift
                ;;
            *)
                break
                ;;
        esac
    done
    REMAINING_ARGS=("$@")
}

get_env_value_from() {
    local file="$1"
    local key="$2"
    local default="${3:-}"
    local value=""
    if [ -f "$file" ]; then
        value=$(grep -E "^[[:space:]]*${key}=" "$file" | tail -n 1 | cut -d= -f2- | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
    fi
    if [ -z "$value" ]; then
        printf '%s' "$default"
    else
        printf '%s' "$value"
    fi
}

get_env_value() {
    get_env_value_from "$DOCKER_ENV_FILE" "$1" "${2:-}"
}

port_in_use() {
    local port="$1"
    if command -v lsof >/dev/null 2>&1; then
        lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1
        return $?
    fi
    if command -v nc >/dev/null 2>&1; then
        nc -z 127.0.0.1 "$port" >/dev/null 2>&1
        return $?
    fi
    (echo >/dev/tcp/127.0.0.1/"$port") >/dev/null 2>&1
}

# 带超时执行命令，避免 docker 无响应时 list/start 永久卡住
# 用法: run_with_timeout 8 docker ps ...
# 注意: 不要依赖本函数的 stdout 与调用方重定向叠加时的竞态；调用方应重定向到文件。
run_with_timeout() {
    local secs="${1:-8}"
    shift
    "$@" &
    local pid=$!
    local i=0
    while [ "$i" -lt "$secs" ]; do
        if ! kill -0 "$pid" 2>/dev/null; then
            wait "$pid" 2>/dev/null
            return $?
        fi
        sleep 1
        i=$((i + 1))
    done
    kill -9 "$pid" 2>/dev/null
    wait "$pid" 2>/dev/null
    return 124
}

# 缓存 docker ps，避免 list/分配时反复调用
refresh_container_cache() {
    CONTAINER_CACHE_FAILED=0
    local all_file run_file
    all_file=$(mktemp)
    run_file=$(mktemp)

    if ! run_with_timeout "${DOCKER_TIMEOUT:-8}" docker ps -a --format '{{.Names}}' >"$all_file"; then
        echo -e "${YELLOW}⚠️  docker ps 超时或无响应（${DOCKER_TIMEOUT:-8}s）。Docker Desktop 可能过载，请稍后重试或重启 Docker。${NC}" >&2
        CONTAINER_ALL_CACHE=""
        CONTAINER_RUN_CACHE=""
        CONTAINER_CACHE_FAILED=1
        rm -f "$all_file" "$run_file"
        return 1
    fi

    if ! run_with_timeout "${DOCKER_TIMEOUT:-8}" docker ps --format '{{.Names}}' >"$run_file"; then
        cp "$all_file" "$run_file"
    fi

    CONTAINER_ALL_CACHE=$(cat "$all_file")
    CONTAINER_RUN_CACHE=$(cat "$run_file")
    rm -f "$all_file" "$run_file"
    return 0
}

container_exists() {
    local name="$1"
    if [ -z "${CONTAINER_ALL_CACHE+x}" ]; then
        refresh_container_cache || true
    fi
    printf '%s\n' "$CONTAINER_ALL_CACHE" | grep -qx "$name"
}

container_running() {
    local name="$1"
    if [ -z "${CONTAINER_RUN_CACHE+x}" ]; then
        refresh_container_cache || true
    fi
    printf '%s\n' "$CONTAINER_RUN_CACHE" | grep -qx "$name"
}

# 容器是否由当前 docker 目录拉起（compose working_dir 标签）
container_owned_by_this_dir() {
    local name="$1"
    local label_dir
    label_dir=$(run_with_timeout 5 docker inspect "$name" --format '{{index .Config.Labels "com.docker.compose.project.working_dir"}}' 2>/dev/null || true)
    [ -n "$label_dir" ] || return 1
    local here
    here=$(pwd -P)
    [ "$label_dir" = "$here" ] || [ "$label_dir" = "$(cd .. && pwd -P)" ]
}

# 本代码目录（compose working_dir）已占用的实例编号（含已停止容器）
# 用于禁止「同一套代码拉起多套容器」
get_indices_owned_by_this_codebase() {
    load_base_defaults
    local here parent names name n
    here=$(pwd -P)
    parent=$(cd .. && pwd -P)
    names=$(mktemp)

    run_with_timeout "${DOCKER_TIMEOUT:-8}" docker ps -a \
        --filter "label=com.docker.compose.project.working_dir=${here}" \
        --format '{{.Names}}' >"$names" 2>/dev/null || true
    run_with_timeout "${DOCKER_TIMEOUT:-8}" docker ps -a \
        --filter "label=com.docker.compose.project.working_dir=${parent}" \
        --format '{{.Names}}' >>"$names" 2>/dev/null || true

    while IFS= read -r name; do
        [ -z "$name" ] && continue
        case "$name" in
            "${BASE_INSTANCE_NAME}_php"|"${BASE_INSTANCE_NAME}_mysql"|"${BASE_INSTANCE_NAME}_redis")
                echo 1
                ;;
            ${BASE_INSTANCE_NAME}_[0-9]*_php|${BASE_INSTANCE_NAME}_[0-9]*_mysql|${BASE_INSTANCE_NAME}_[0-9]*_redis)
                n="${name#${BASE_INSTANCE_NAME}_}"
                n="${n%%_*}"
                echo "$n"
                ;;
        esac
    done <"$names" | sort -nu
    rm -f "$names"
}

# 禁止同一代码目录启动多套；target_index 为空表示 start --new
assert_single_stack_per_codebase() {
    local target_index="${1:-}"
    local owned owned_list
    owned=$(get_indices_owned_by_this_codebase)
    owned_list=$(echo "$owned" | tr '\n' ' ' | sed 's/[[:space:]]*$//')

    if [ -z "$target_index" ]; then
        if [ -n "$owned_list" ]; then
            echo -e "${RED}❌ 禁止：同一套代码不能再 start --new（本目录已有实例: ${owned_list}）${NC}"
            echo -e "${YELLOW}重启现有: ./manage.sh start${NC}"
            echo -e "${YELLOW}停掉现有: ./manage.sh stop${NC}"
            echo -e "${YELLOW}要部署第二套，请用独立代码目录，例如:${NC}"
            echo -e "  git worktree add ../oa-own-2 HEAD"
            echo -e "  cd ../oa-own-2/docker && ./manage.sh start --new"
            exit 1
        fi
        return 0
    fi

    local idx
    for idx in $owned_list; do
        if [ "$idx" != "$target_index" ]; then
            echo -e "${RED}❌ 禁止：同一套代码不能同时运行多套容器（已有 #${idx}，又请求 #${target_index}）${NC}"
            echo -e "${YELLOW}请先: ./manage.sh stop ${idx}${NC}"
            echo -e "${YELLOW}或切换操作已有实例: ./manage.sh start ${idx}${NC}"
            echo -e "${YELLOW}第二套环境请使用独立 worktree/目录后再 start --new${NC}"
            exit 1
        fi
    done
}

init_compose() {
    if [ ! -f "$DOCKER_ENV_FILE" ]; then
        echo -e "${RED}❌ 环境文件不存在: ${DOCKER_ENV_FILE}${NC}"
        exit 1
    fi
    if command -v docker-compose >/dev/null 2>&1; then
        COMPOSE=(docker-compose --env-file "$DOCKER_ENV_FILE")
    else
        COMPOSE=(docker compose --env-file "$DOCKER_ENV_FILE")
    fi
}

load_base_defaults() {
    BASE_INSTANCE_NAME=$(get_env_value_from "$BASE_ENV_FILE" INSTANCE_NAME tuoluojiang)
    BASE_APP_PORT=$(get_env_value_from "$BASE_ENV_FILE" HOST_APP_PORT 20300)
    BASE_MYSQL_PORT=$(get_env_value_from "$BASE_ENV_FILE" HOST_MYSQL_PORT 3376)
    BASE_REDIS_PORT=$(get_env_value_from "$BASE_ENV_FILE" HOST_REDIS_PORT 6389)
    BASE_REDIS_DATA_PATH=$(get_env_value_from "$BASE_ENV_FILE" REDIS_DATA_PATH ./redis/data)
    BASE_MYSQL_LOG_PATH=$(get_env_value_from "$BASE_ENV_FILE" MYSQL_LOG_PATH ./mysql/logs)
    PHP_IMAGE_NAME=$(get_env_value_from "$BASE_ENV_FILE" PHP_IMAGE_NAME tuoluojiang-php)
    # 始终从基线读取，避免旧 .docker.instance.env 锁死 target
    PHP_IMAGE_TARGET=$(get_env_value_from "$BASE_ENV_FILE" PHP_IMAGE_TARGET ocr)
    MYSQL_ROOT_PASSWORD=$(get_env_value_from "$BASE_ENV_FILE" MYSQL_ROOT_PASSWORD 123456)
    MYSQL_DATABASE=$(get_env_value_from "$BASE_ENV_FILE" MYSQL_DATABASE tuoluojiang)
}

# 按编号计算实例参数：1=基线无后缀，2+= _N 且端口 + (N-1)
compute_slot() {
    local index="$1"
    INSTANCE_INDEX="$index"
    if [ "$index" -eq 1 ]; then
        INSTANCE_NAME="$BASE_INSTANCE_NAME"
        HOST_APP_PORT="$BASE_APP_PORT"
        HOST_MYSQL_PORT="$BASE_MYSQL_PORT"
        HOST_REDIS_PORT="$BASE_REDIS_PORT"
        REDIS_DATA_PATH="$BASE_REDIS_DATA_PATH"
        MYSQL_LOG_PATH="$BASE_MYSQL_LOG_PATH"
    else
        local offset=$((index - 1))
        INSTANCE_NAME="${BASE_INSTANCE_NAME}_${index}"
        HOST_APP_PORT=$((BASE_APP_PORT + offset))
        HOST_MYSQL_PORT=$((BASE_MYSQL_PORT + offset))
        HOST_REDIS_PORT=$((BASE_REDIS_PORT + offset))
        REDIS_DATA_PATH="${BASE_REDIS_DATA_PATH}-${index}"
        MYSQL_LOG_PATH="${BASE_MYSQL_LOG_PATH}-${index}"
    fi
    COMPOSE_PROJECT_NAME="$INSTANCE_NAME"
}

load_instance_vars() {
    INSTANCE_NAME=$(get_env_value INSTANCE_NAME tuoluojiang)
    INSTANCE_INDEX=$(get_env_value INSTANCE_INDEX 1)
    HOST_APP_PORT=$(get_env_value HOST_APP_PORT 20300)
    HOST_MYSQL_PORT=$(get_env_value HOST_MYSQL_PORT 3376)
    HOST_REDIS_PORT=$(get_env_value HOST_REDIS_PORT 6389)
    REDIS_DATA_PATH=$(get_env_value REDIS_DATA_PATH ./redis/data)
    MYSQL_LOG_PATH=$(get_env_value MYSQL_LOG_PATH ./mysql/logs)
    MYSQL_ROOT_PASSWORD=$(get_env_value MYSQL_ROOT_PASSWORD 123456)
    MYSQL_DATABASE=$(get_env_value MYSQL_DATABASE tuoluojiang)
    export COMPOSE_PROJECT_NAME
    COMPOSE_PROJECT_NAME=$(get_env_value COMPOSE_PROJECT_NAME "$INSTANCE_NAME")
}

print_instance_info() {
    HOST_PUBLISH_BIND=$(get_env_value HOST_PUBLISH_BIND 127.0.0.1)
    PHP_IMAGE_TARGET=$(get_env_value PHP_IMAGE_TARGET ocr)
    echo -e "${BLUE}实例: ${INSTANCE_NAME} (#${INSTANCE_INDEX})  env: ${DOCKER_ENV_FILE}${NC}"
    echo -e "${YELLOW}应用: http://localhost:${HOST_APP_PORT}${NC}"
    echo -e "${YELLOW}MySQL: ${HOST_PUBLISH_BIND}:${HOST_MYSQL_PORT} (root/${MYSQL_ROOT_PASSWORD}；远程请用 SSH 隧道)${NC}"
    echo -e "${YELLOW}Redis: ${HOST_PUBLISH_BIND}:${HOST_REDIS_PORT}（远程请用 SSH 隧道）${NC}"
    echo -e "${YELLOW}PHP镜像 target: ${PHP_IMAGE_TARGET}（ocr=完整OCR，base=轻量）${NC}"
}

# 槽位是否有相关容器在跑
slot_is_running() {
    local index="$1"
    compute_slot "$index"
    container_running "${INSTANCE_NAME}_php" \
        || container_running "${INSTANCE_NAME}_mysql" \
        || container_running "${INSTANCE_NAME}_redis"
}

# 槽位是否有相关容器存在（含已停止）
slot_has_containers() {
    local index="$1"
    compute_slot "$index"
    container_exists "${INSTANCE_NAME}_php" \
        || container_exists "${INSTANCE_NAME}_mysql" \
        || container_exists "${INSTANCE_NAME}_redis"
}

write_instance_env() {
    local target_file="${1:-$INSTANCE_ENV_FILE}"
    local tmp
    tmp=$(mktemp)
    if [ -f "$BASE_ENV_FILE" ]; then
        grep -vE '^[[:space:]]*(INSTANCE_NAME|COMPOSE_PROJECT_NAME|INSTANCE_INDEX|HOST_APP_PORT|HOST_MYSQL_PORT|HOST_REDIS_PORT|REDIS_DATA_PATH|MYSQL_LOG_PATH|MYSQL_DATA_VOLUME|MYSQL_LOG_VOLUME|REDIS_DATA_VOLUME|PHP_IMAGE_NAME|PHP_IMAGE_TARGET)=' \
            "$BASE_ENV_FILE" > "$tmp" || true
    else
        : > "$tmp"
    fi

    {
        echo ""
        echo "# ----- 自动分配的实例（由 manage.sh 生成） -----"
        echo "INSTANCE_INDEX=${INSTANCE_INDEX}"
        echo "INSTANCE_NAME=${INSTANCE_NAME}"
        echo "COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME}"
        echo "HOST_APP_PORT=${HOST_APP_PORT}"
        echo "HOST_MYSQL_PORT=${HOST_MYSQL_PORT}"
        echo "HOST_REDIS_PORT=${HOST_REDIS_PORT}"
        echo "REDIS_DATA_PATH=${REDIS_DATA_PATH}"
        echo "MYSQL_LOG_PATH=${MYSQL_LOG_PATH}"
        echo "MYSQL_DATA_VOLUME=./mysql/data"
        echo "MYSQL_LOG_VOLUME=${MYSQL_LOG_PATH}"
        echo "REDIS_DATA_VOLUME=${REDIS_DATA_PATH}"
        echo "PHP_IMAGE_NAME=${PHP_IMAGE_NAME:-tuoluojiang-php}"
        echo "PHP_IMAGE_TARGET=${PHP_IMAGE_TARGET:-ocr}"
    } >> "$tmp"

    mv "$tmp" "$target_file"
    DOCKER_ENV_FILE="$target_file"
}

# 按编号准备 compose 环境：persist=1 写绑定文件；quiet=1 少打印
prepare_instance_by_index() {
    local index="$1"
    local persist="${2:-0}"
    local quiet="${3:-0}"

    if ! [[ "$index" =~ ^[1-9][0-9]*$ ]] || [ "$index" -gt "$MAX_INSTANCE_INDEX" ]; then
        echo -e "${RED}❌ 无效实例编号: ${index}（范围 1..${MAX_INSTANCE_INDEX}）${NC}"
        exit 1
    fi

    load_base_defaults
    compute_slot "$index"
    # PHP_IMAGE_* 来自基线，写入 instance env 时覆盖旧值
    PHP_IMAGE_NAME="${PHP_IMAGE_NAME:-tuoluojiang-php}"
    PHP_IMAGE_TARGET="${PHP_IMAGE_TARGET:-ocr}"

    if [ "$persist" = "1" ]; then
        write_instance_env "$INSTANCE_ENV_FILE"
        if [ "$quiet" != "1" ]; then
            echo -e "${GREEN}✅ 已切换当前绑定为 #${index} (${INSTANCE_NAME})${NC}"
        fi
    else
        TEMPORARY_ENV_FILE="$(mktemp "${TMPDIR:-/tmp}/docker-instance.XXXXXX")"
        write_instance_env "$TEMPORARY_ENV_FILE"
        if [ "$quiet" != "1" ] && [ -f "$INSTANCE_ENV_FILE" ]; then
            local bound
            bound=$(get_env_value_from "$INSTANCE_ENV_FILE" INSTANCE_INDEX "")
            if [ -n "$bound" ] && [ "$bound" != "$index" ]; then
                echo -e "${BLUE}ℹ️  操作实例 #${index}（当前绑定仍为 #${bound}；切换请用: ./manage.sh use ${index}）${NC}"
            fi
        fi
    fi
}

cleanup_temporary_env() {
    if [ -n "${TEMPORARY_ENV_FILE:-}" ] && [ -f "$TEMPORARY_ENV_FILE" ]; then
        rm -f "$TEMPORARY_ENV_FILE"
        TEMPORARY_ENV_FILE=""
    fi
}

# 解析要操作的实例：优先 -n，否则当前绑定，再否则基线 #1
resolve_target_instance() {
    local persist="${1:-0}"
    if [ -n "$TARGET_INSTANCE_INDEX" ]; then
        prepare_instance_by_index "$TARGET_INSTANCE_INDEX" "$persist"
        return 0
    fi
    if [ -f "$INSTANCE_ENV_FILE" ]; then
        DOCKER_ENV_FILE="$INSTANCE_ENV_FILE"
        return 0
    fi
    DOCKER_ENV_FILE="$BASE_ENV_FILE"
}

# 槽位是否空闲可新建（无同名容器且端口未被占用）
slot_is_free_for_new() {
    local index="$1"
    compute_slot "$index"

    container_exists "${INSTANCE_NAME}_php" && return 1
    container_exists "${INSTANCE_NAME}_mysql" && return 1
    container_exists "${INSTANCE_NAME}_redis" && return 1

    port_in_use "$HOST_APP_PORT" && return 1
    port_in_use "$HOST_MYSQL_PORT" && return 1
    port_in_use "$HOST_REDIS_PORT" && return 1
    return 0
}

accept_slot() {
    local index="$1"
    local reason="$2"
    compute_slot "$index"
    echo -e "${GREEN}✅ ${reason}${NC}"
    echo -e "${GREEN}   实例 ${INSTANCE_NAME}  应用=${HOST_APP_PORT} MySQL=${HOST_MYSQL_PORT} Redis=${HOST_REDIS_PORT}${NC}"
    write_instance_env "$INSTANCE_ENV_FILE"
}

# start --new：仅当本代码目录尚无任何实例时，分配全局空闲编号
allocate_new_instance() {
    assert_single_stack_per_codebase ""
    load_base_defaults
    refresh_container_cache
    local index
    for index in $(seq 1 "$MAX_INSTANCE_INDEX"); do
        if slot_is_running "$index"; then
            echo -e "${YELLOW}⚠️  #${index} 已被其它目录占用，跳过${NC}"
            continue
        fi
        if slot_is_free_for_new "$index"; then
            accept_slot "$index" "为本代码目录分配实例 #${index}"
            return 0
        fi
        echo -e "${YELLOW}⚠️  #${index} 端口或容器名占用，尝试下一个${NC}"
    done
    echo -e "${RED}❌ 在 1..${MAX_INSTANCE_INDEX} 内未找到可用实例槽位${NC}"
    exit 1
}

# start：只启动当前绑定；无绑定时默认 #1
start_bound_instance() {
    if [ -f "$INSTANCE_ENV_FILE" ]; then
        local saved_index
        saved_index=$(get_env_value_from "$INSTANCE_ENV_FILE" INSTANCE_INDEX 1)
        assert_single_stack_per_codebase "$saved_index"
        prepare_instance_by_index "$saved_index" 1 1
        echo -e "${BLUE}ℹ️  启动当前绑定实例 #${saved_index}${NC}"
        return 0
    fi

    local owned
    owned=$(get_indices_owned_by_this_codebase | head -n 1)
    if [ -n "$owned" ]; then
        assert_single_stack_per_codebase "$owned"
        prepare_instance_by_index "$owned" 1 1
        echo -e "${BLUE}ℹ️  本目录已有实例，启动 #${owned}${NC}"
        return 0
    fi

    load_base_defaults
    refresh_container_cache || true
    if slot_is_free_for_new 1 || slot_has_containers 1 || slot_is_running 1; then
        if ! slot_is_free_for_new 1 && ! slot_has_containers 1; then
            echo -e "${RED}❌ 默认 #1 已被其它环境占用${NC}"
            echo -e "${YELLOW}请执行: ./manage.sh start --new${NC}"
            exit 1
        fi
        accept_slot 1 "绑定并启动默认实例 #1"
        return 0
    fi

    echo -e "${RED}❌ 无当前绑定，且默认 #1 不可用${NC}"
    echo -e "${YELLOW}请执行: ./manage.sh start --new${NC}"
    exit 1
}

# start N：同一代码目录不得与其它编号并存
start_instance_by_number() {
    local index="$1"
    assert_single_stack_per_codebase "$index"
    load_base_defaults
    refresh_container_cache || true
    compute_slot "$index"

    if ! slot_is_running "$index" && ! slot_has_containers "$index"; then
        if port_in_use "$HOST_APP_PORT" || port_in_use "$HOST_MYSQL_PORT" || port_in_use "$HOST_REDIS_PORT"; then
            echo -e "${RED}❌ #${index} 对应端口已被占用（${HOST_APP_PORT}/${HOST_MYSQL_PORT}/${HOST_REDIS_PORT}）${NC}"
            echo -e "${YELLOW}其它目录已占用时，在本目录执行: ./manage.sh start --new${NC}"
            exit 1
        fi
        echo -e "${YELLOW}⚠️  #${index} 尚无容器，将按该编号创建并启动${NC}"
    fi

    prepare_instance_by_index "$index" 1 1
    echo -e "${BLUE}ℹ️  启动实例 #${index} (${INSTANCE_NAME})${NC}"
}

resolve_env_for_command() {
    # 显式 -e 优先
    if [ -n "$DOCKER_ENV_FILE" ] && [ "$DOCKER_ENV_FILE" != "$INSTANCE_ENV_FILE" ] && [ "$DOCKER_ENV_FILE" != "$BASE_ENV_FILE" ]; then
        return 0
    fi
    if [ -n "${DOCKER_ENV_FILE:-}" ] && [ -f "$DOCKER_ENV_FILE" ]; then
        return 0
    fi
    if [ -f "$INSTANCE_ENV_FILE" ]; then
        DOCKER_ENV_FILE="$INSTANCE_ENV_FILE"
        return 0
    fi
    DOCKER_ENV_FILE="$BASE_ENV_FILE"
}

ensure_instance_dirs() {
    mkdir -p "$REDIS_DATA_PATH" "$MYSQL_LOG_PATH"
}

detect_php_platform() {
    local host_os host_arch
    host_os=$(uname -s)
    host_arch=$(uname -m)
    PHP_PLATFORM=${PHP_PLATFORM:-linux/amd64}
    PHP_PLATFORM_REASON="开源版默认 linux/amd64（无需 swoole_loader）"
    export PHP_PLATFORM
    unset DOCKER_DEFAULT_PLATFORM
    echo -e "${BLUE}Docker PHP平台: ${PHP_PLATFORM} (host: ${host_os}/${host_arch})${NC}"
    echo -e "${BLUE}PHP镜像 target: ${PHP_IMAGE_TARGET:-$(get_env_value_from "$BASE_ENV_FILE" PHP_IMAGE_TARGET ocr)}（ocr=完整OCR / base=轻量）${NC}"
}

show_platform() {
    echo -e "${BLUE}PHP容器平台判断:${NC}"
    echo "  宿主系统: $(uname -s)"
    echo "  宿主架构: $(uname -m)"
    echo "  PHP平台: ${PHP_PLATFORM:-linux/amd64}"
    echo "  开源版: 无需 swoole_loader"
}

show_help() {
    echo -e "${BLUE}🐳 陀螺匠OA Docker 管理工具${NC}"
    echo ""
    echo "用法: $0 {start|stop|restart|status|logs|info|list|use|...} [编号|--new]"
    echo ""
    echo "重要: 同一套代码目录只允许一套容器。要多套请用独立目录/worktree。"
    echo ""
    echo "启动:"
    echo "  start              启动本目录当前绑定实例"
    echo "  start 2            启动本目录已有 #2（若本目录已有其它编号会拒绝）"
    echo "  start --new        仅当本目录还没有任何实例时，分配空闲编号新建"
    echo ""
    echo "其它:"
    echo "  stop / restart / status / logs / info / list / use"
    echo "  stop 2 / stop all"
    echo ""
    echo "多套示例:"
    echo "  git worktree add ../oa-own-2 HEAD"
    echo "  cd ../oa-own-2/docker && ./manage.sh start --new"
    echo ""
}

check_directory() {
    if [ ! -f "docker-compose.yml" ]; then
        echo -e "${RED}❌ 请在 docker 目录下执行${NC}"
        exit 1
    fi
    if [ ! -f "$BASE_ENV_FILE" ]; then
        echo -e "${RED}❌ 缺少基线配置 ${BASE_ENV_FILE}${NC}"
        exit 1
    fi
    detect_php_platform
}

start_services() {
    echo -e "${BLUE}🚀 启动 Docker 环境...${NC}"

    if [ "$FORCE_NEW_INSTANCE" -eq 1 ] && [ -n "$TARGET_INSTANCE_INDEX" ]; then
        echo -e "${RED}❌ 不要同时使用 --new 与编号。新建用 start --new，指定已有用 start <编号>${NC}"
        exit 1
    fi

    if [ "$FORCE_NEW_INSTANCE" -eq 1 ]; then
        allocate_new_instance
    elif [ -n "$TARGET_INSTANCE_INDEX" ]; then
        start_instance_by_number "$TARGET_INSTANCE_INDEX"
    else
        start_bound_instance
    fi

    init_compose
    load_instance_vars
    ensure_instance_dirs
    "${COMPOSE[@]}" up -d --build
    echo -e "${GREEN}✅ 启动完成${NC}"
    print_instance_info
}

stop_services() {
    local mode="${1:-}"
    if [ "$mode" = "all" ]; then
        stop_all_instances
        return 0
    fi
    echo -e "${BLUE}🛑 停止实例 ${INSTANCE_NAME} (#${INSTANCE_INDEX:-?})...${NC}"
    "${COMPOSE[@]}" down
    echo -e "${GREEN}✅ 已停止${NC}"
}

stop_all_instances() {
    load_base_defaults
    echo -e "${BLUE}🛑 停止全部可识别实例...${NC}"
    local index stopped=0
    local bound_index=""
    [ -f "$INSTANCE_ENV_FILE" ] && bound_index=$(get_env_value_from "$INSTANCE_ENV_FILE" INSTANCE_INDEX "")

    for index in $(seq 1 "$MAX_INSTANCE_INDEX"); do
        compute_slot "$index"
        if container_exists "${INSTANCE_NAME}_php" \
            || container_exists "${INSTANCE_NAME}_mysql" \
            || container_exists "${INSTANCE_NAME}_redis"; then
            echo -e "${YELLOW}→ 停止 #${index} ${INSTANCE_NAME}${NC}"
            TEMPORARY_ENV_FILE="$(mktemp "${TMPDIR:-/tmp}/docker-instance.XXXXXX")"
            write_instance_env "$TEMPORARY_ENV_FILE"
            init_compose
            "${COMPOSE[@]}" down || true
            cleanup_temporary_env
            stopped=$((stopped + 1))
        fi
    done

    # 恢复绑定文件指向（若仍存在）
    if [ -n "$bound_index" ]; then
        prepare_instance_by_index "$bound_index" 1 1
    fi

    if [ "$stopped" -eq 0 ]; then
        echo -e "${YELLOW}未发现可停止的实例容器${NC}"
    else
        echo -e "${GREEN}✅ 已停止 ${stopped} 套实例${NC}"
    fi
}

restart_services() {
    echo -e "${BLUE}🔄 重启实例 ${INSTANCE_NAME} (#${INSTANCE_INDEX:-?})...${NC}"
    ensure_instance_dirs
    "${COMPOSE[@]}" down
    "${COMPOSE[@]}" up -d --build
    echo -e "${GREEN}✅ 重启完成${NC}"
    print_instance_info
}

use_instance() {
    local index="${1:-}"
    [ -n "$index" ] || { echo -e "${RED}用法: $0 use <编号>${NC}"; exit 1; }
    prepare_instance_by_index "$index" 1
    init_compose
    load_instance_vars
    print_instance_info
    if container_running "${INSTANCE_NAME}_php" \
        || container_running "${INSTANCE_NAME}_mysql" \
        || container_running "${INSTANCE_NAME}_redis"; then
        echo -e "${GREEN}该实例容器已在运行${NC}"
    else
        echo -e "${YELLOW}该实例当前未运行，可执行: ./manage.sh start ${index}  或  ./manage.sh restart ${index}${NC}"
    fi
}

show_status() {
    echo -e "${BLUE}📋 实例 ${INSTANCE_NAME} (#${INSTANCE_INDEX:-?}) 状态:${NC}"
    "${COMPOSE[@]}" ps
    local ids
    ids=$("${COMPOSE[@]}" ps -q)
    if [ -n "$ids" ]; then
        echo ""
        echo "$ids" | xargs docker stats --no-stream
    fi
}

show_logs() {
    if [ $# -eq 0 ]; then
        "${COMPOSE[@]}" logs -f
    else
        "${COMPOSE[@]}" logs -f "$1"
    fi
}

list_instances() {
    load_base_defaults
    if ! refresh_container_cache; then
        echo -e "${RED}❌ 无法列出实例：Docker 无响应。可尝试重启 Docker Desktop 后重试。${NC}"
        echo -e "${YELLOW}也可直接查看: docker ps -a | grep tuoluojiang${NC}"
        return 1
    fi
    local bound_index=""
    [ -f "$INSTANCE_ENV_FILE" ] && bound_index=$(get_env_value_from "$INSTANCE_ENV_FILE" INSTANCE_INDEX "")

    echo -e "${BLUE}实例一览（基线名 ${BASE_INSTANCE_NAME}）:${NC}"
    printf "%-4s %-22s %-7s %-7s %-7s %-12s %s\n" "#" "NAME" "APP" "MYSQL" "REDIS" "状态" "绑定"
    printf "%-4s %-22s %-7s %-7s %-7s %-12s %s\n" "----" "----------------------" "-------" "-------" "-------" "------------" "----"

    # 从容器名反推编号，避免盲目扫 1..50
    local indices=""
    local name
    while IFS= read -r name; do
        [ -z "$name" ] && continue
        case "$name" in
            "${BASE_INSTANCE_NAME}_php"|"${BASE_INSTANCE_NAME}_mysql"|"${BASE_INSTANCE_NAME}_redis")
                indices="$indices 1"
                ;;
            ${BASE_INSTANCE_NAME}_[0-9]*_php|${BASE_INSTANCE_NAME}_[0-9]*_mysql|${BASE_INSTANCE_NAME}_[0-9]*_redis)
                local n="${name#${BASE_INSTANCE_NAME}_}"
                n="${n%%_*}"
                indices="$indices $n"
                ;;
        esac
    done <<EOF
$CONTAINER_ALL_CACHE
EOF
    [ -n "$bound_index" ] && indices="$indices $bound_index"

    local index found=0
    for index in $(printf '%s\n' $indices | awk 'NF' | sort -n | uniq); do
        compute_slot "$index"
        local php_c="${INSTANCE_NAME}_php"
        local mysql_c="${INSTANCE_NAME}_mysql"
        local redis_c="${INSTANCE_NAME}_redis"
        if ! container_exists "$php_c" && ! container_exists "$mysql_c" && ! container_exists "$redis_c"; then
            if [ "$bound_index" != "$index" ]; then
                continue
            fi
        fi
        found=1
        local st="未创建"
        if container_running "$php_c" || container_running "$mysql_c" || container_running "$redis_c"; then
            st="运行中"
        elif container_exists "$php_c" || container_exists "$mysql_c" || container_exists "$redis_c"; then
            st="已停止"
        fi
        local mark=""
        [ "$bound_index" = "$index" ] && mark="← 当前"
        printf "%-4s %-22s %-7s %-7s %-7s %-12s %s\n" \
            "$index" "$INSTANCE_NAME" "$HOST_APP_PORT" "$HOST_MYSQL_PORT" "$HOST_REDIS_PORT" "$st" "$mark"
    done

    if [ "$found" -eq 0 ]; then
        echo -e "${YELLOW}暂无实例。执行 ./manage.sh start 或 ./manage.sh start --new${NC}"
    else
        echo ""
        echo -e "新建: ${GREEN}./manage.sh start --new${NC}  指定已有: ${GREEN}./manage.sh start 2${NC}  当前绑定: ${GREEN}./manage.sh start${NC}"
    fi
}

build_services() {
    "${COMPOSE[@]}" build --no-cache
    echo -e "${GREEN}✅ 构建完成${NC}"
}

clean_resources() {
    echo -e "${BLUE}🧹 清理当前实例资源...${NC}"
    "${COMPOSE[@]}" down --remove-orphans
    docker image prune -f
    docker builder prune -f
    echo -e "${GREEN}✅ 清理完成${NC}"
}

backup_database() {
    local timestamp backup_file
    timestamp=$(date +"%Y%m%d_%H%M%S")
    backup_file="backup_${INSTANCE_NAME}_${timestamp}.sql"
    "${COMPOSE[@]}" exec mysql mysqldump -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" > "$backup_file"
    echo -e "${GREEN}✅ 备份: $backup_file${NC}"
}

restore_database() {
    [ $# -ge 1 ] || { echo -e "${RED}用法: $0 restore <file.sql>${NC}"; exit 1; }
    [ -f "$1" ] || { echo -e "${RED}文件不存在: $1${NC}"; exit 1; }
    "${COMPOSE[@]}" exec -T mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < "$1"
    echo -e "${GREEN}✅ 恢复完成${NC}"
}

enter_container() {
    case "${1:-}" in
        php) "${COMPOSE[@]}" exec php bash ;;
        mysql) "${COMPOSE[@]}" exec mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}" ;;
        redis) "${COMPOSE[@]}" exec redis redis-cli ;;
        *) echo -e "${RED}可用: php|mysql|redis${NC}"; exit 1 ;;
    esac
}

run_artisan() {
    if [ $# -eq 0 ]; then
        "${COMPOSE[@]}" exec php php artisan
    else
        "${COMPOSE[@]}" exec php php artisan "$@"
    fi
}

run_composer() {
    if [ $# -eq 0 ]; then
        "${COMPOSE[@]}" exec php composer
    else
        "${COMPOSE[@]}" exec php composer "$@"
    fi
}

# 从命令后参数取出实例编号：stop 2 / logs 2 php
consume_trailing_instance() {
    if [ -n "$TARGET_INSTANCE_INDEX" ]; then
        return 1
    fi
    if [[ "${1:-}" =~ ^[1-9][0-9]*$ ]]; then
        TARGET_INSTANCE_INDEX="$1"
        return 0
    fi
    return 1
}

main() {
    parse_global_opts "$@"
    set -- "${REMAINING_ARGS[@]}"
    trap cleanup_temporary_env EXIT

    check_directory
    load_base_defaults

    local cmd="${1:-help}"
    shift || true

    case "$cmd" in
        help|"")
            show_help
            exit 0
            ;;
        list)
            list_instances
            exit 0
            ;;
        platform)
            show_platform
            exit 0
            ;;
        use)
            use_instance "$@"
            exit 0
            ;;
        start)
            # 支持: start --new / start 2 / start --new 2
            while [ $# -gt 0 ]; do
                case "$1" in
                    --new)
                        FORCE_NEW_INSTANCE=1
                        shift
                        ;;
                    *)
                        break
                        ;;
                esac
            done
            if consume_trailing_instance "${1:-}"; then
                shift
            fi
            # 编号后再跟 --new 也认
            while [ $# -gt 0 ]; do
                case "$1" in
                    --new)
                        FORCE_NEW_INSTANCE=1
                        shift
                        ;;
                    *)
                        break
                        ;;
                esac
            done
            start_services
            exit 0
            ;;
        stop)
            if [ "${1:-}" = "all" ]; then
                stop_services all
                exit 0
            fi
            if consume_trailing_instance "${1:-}"; then
                shift
            fi
            resolve_target_instance 0
            init_compose
            load_instance_vars
            stop_services
            exit 0
            ;;
        restart|status|info|clean|backup|build)
            if consume_trailing_instance "${1:-}"; then
                shift
            fi
            resolve_target_instance 0
            init_compose
            load_instance_vars
            case "$cmd" in
                restart) restart_services ;;
                status) show_status ;;
                info)
                    print_instance_info
                    echo -e "${BLUE}Redis数据: ${REDIS_DATA_PATH}${NC}"
                    echo -e "${BLUE}MySQL日志: ${MYSQL_LOG_PATH}${NC}"
                    echo -e "${BLUE}MySQL卷: ${INSTANCE_NAME}_mysql_data${NC}"
                    ;;
                clean) clean_resources ;;
                backup) backup_database ;;
                build) build_services ;;
            esac
            exit 0
            ;;
        logs)
            if consume_trailing_instance "${1:-}"; then
                shift
            fi
            resolve_target_instance 0
            init_compose
            load_instance_vars
            show_logs "$@"
            exit 0
            ;;
        exec)
            # exec 2 php  或  exec php
            if consume_trailing_instance "${1:-}"; then
                shift
            fi
            resolve_target_instance 0
            init_compose
            load_instance_vars
            enter_container "$@"
            exit 0
            ;;
        restore)
            resolve_target_instance 0
            init_compose
            load_instance_vars
            restore_database "$@"
            exit 0
            ;;
        artisan)
            resolve_target_instance 0
            init_compose
            load_instance_vars
            run_artisan "$@"
            exit 0
            ;;
        composer)
            resolve_target_instance 0
            init_compose
            load_instance_vars
            run_composer "$@"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ 未知命令: $cmd${NC}"
            show_help
            exit 1
            ;;
    esac
}

main "$@"
