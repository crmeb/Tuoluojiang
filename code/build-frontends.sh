#!/usr/bin/env bash
set -Eeuo pipefail

SERVER_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd -- "$SERVER_DIR/.." && pwd)"
PUBLIC_DIR="$SERVER_DIR/public"

# 管理后台体积较大，Node 默认堆上限不足；可在执行前通过 NODE_OPTIONS 覆盖。
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"

usage() {
  cat <<'EOF'
用法: ./build-frontends.sh [端...]

不传参数时打包全部前端；传参时仅打包指定端。

可选端（可多选，顺序无关）:
  admin   管理后台 → public/admin
  chat    会话前端 → public/chat
  uniapp  移动端 H5 → public/work（别名: work）

示例:
  ./build-frontends.sh
  ./build-frontends.sh admin
  ./build-frontends.sh admin chat
  ./build-frontends.sh uniapp
EOF
}

command -v pnpm >/dev/null 2>&1 || {
  echo "错误：未找到 pnpm，请先安装 Node.js 和 pnpm。" >&2
  exit 1
}

# 解析目标端：默认全部；支持 admin / chat / uniapp（别名 work）
# 使用普通变量以兼容 macOS 自带 Bash 3.2（无关联数组）。
BUILD_ADMIN=0
BUILD_CHAT=0
BUILD_UNIAPP=0

if [[ $# -eq 0 ]]; then
  BUILD_ADMIN=1
  BUILD_CHAT=1
  BUILD_UNIAPP=1
else
  for arg in "$@"; do
    case "$arg" in
      -h|--help)
        usage
        exit 0
        ;;
      admin)
        BUILD_ADMIN=1
        ;;
      chat)
        BUILD_CHAT=1
        ;;
      uniapp|work)
        BUILD_UNIAPP=1
        ;;
      *)
        printf '错误：未知端「%s」。\n' "$arg" >&2
        usage >&2
        exit 1
        ;;
    esac
  done
fi

SELECTED=""
[[ $BUILD_ADMIN -eq 1 ]] && SELECTED="${SELECTED}${SELECTED:+ }admin"
[[ $BUILD_CHAT -eq 1 ]] && SELECTED="${SELECTED}${SELECTED:+ }chat"
[[ $BUILD_UNIAPP -eq 1 ]] && SELECTED="${SELECTED}${SELECTED:+ }uniapp"

echo "==> 将打包: ${SELECTED}"

for app in $SELECTED; do
  if [[ ! -f "${ROOT_DIR}/${app}/package.json" ]]; then
    printf '错误：缺少前端目录 %s。\n' "${ROOT_DIR}/${app}" >&2
    exit 1
  fi
done

echo "==> 安装前端依赖"
for app in $SELECTED; do
  pnpm --dir "${ROOT_DIR}/${app}" install --frozen-lockfile
done

if [[ $BUILD_ADMIN -eq 1 ]]; then
  echo "==> 构建管理后台"
  pnpm --dir "$ROOT_DIR/admin" run build
fi

if [[ $BUILD_CHAT -eq 1 ]]; then
  echo "==> 构建会话前端"
  pnpm --dir "$ROOT_DIR/chat" run build
fi

if [[ $BUILD_UNIAPP -eq 1 ]]; then
  echo "==> 构建移动端 H5"
  pnpm --dir "$ROOT_DIR/uniapp" run build:h5
fi

ADMIN_DIST="$ROOT_DIR/admin/dist"
CHAT_DIST="$ROOT_DIR/chat/dist"
WORK_DIST=""
if [[ $BUILD_UNIAPP -eq 1 ]]; then
  if [[ -d "$ROOT_DIR/uniapp/dist/build/h5" ]]; then
    WORK_DIST="$ROOT_DIR/uniapp/dist/build/h5"
  else
    WORK_DIST="$ROOT_DIR/uniapp/unpackage/dist/build/h5"
  fi
fi

if [[ $BUILD_ADMIN -eq 1 && ! -f "${ADMIN_DIST}/index.html" ]]; then
  printf '错误：构建产物不完整，缺少 %s。\n' "${ADMIN_DIST}/index.html" >&2
  exit 1
fi
if [[ $BUILD_CHAT -eq 1 && ! -f "${CHAT_DIST}/index.html" ]]; then
  printf '错误：构建产物不完整，缺少 %s。\n' "${CHAT_DIST}/index.html" >&2
  exit 1
fi
if [[ $BUILD_UNIAPP -eq 1 && ! -f "${WORK_DIST}/index.html" ]]; then
  printf '错误：构建产物不完整，缺少 %s。\n' "${WORK_DIST}/index.html" >&2
  exit 1
fi

mkdir -p "$PUBLIC_DIR"
RELEASE_DIR="$(mktemp -d "$PUBLIC_DIR/.frontends.XXXXXX")"
cleanup() { rm -rf "$RELEASE_DIR"; }
trap cleanup EXIT

PUBLIC_APPS=""

if [[ $BUILD_ADMIN -eq 1 ]]; then
  cp -a "$ADMIN_DIST/." "$RELEASE_DIR/admin/"
  PUBLIC_APPS="${PUBLIC_APPS}${PUBLIC_APPS:+ }admin"
fi
if [[ $BUILD_CHAT -eq 1 ]]; then
  cp -a "$CHAT_DIST/." "$RELEASE_DIR/chat/"
  PUBLIC_APPS="${PUBLIC_APPS}${PUBLIC_APPS:+ }chat"
fi
if [[ $BUILD_UNIAPP -eq 1 ]]; then
  cp -a "$WORK_DIST/." "$RELEASE_DIR/work/"
  PUBLIC_APPS="${PUBLIC_APPS}${PUBLIC_APPS:+ }work"
fi

for app in $PUBLIC_APPS; do
  rm -rf "${PUBLIC_DIR}/${app}"
  mv "${RELEASE_DIR}/${app}" "${PUBLIC_DIR}/${app}"
done

echo "==> 前端打包完成"
[[ $BUILD_ADMIN -eq 1 ]] && echo "管理后台：${PUBLIC_DIR}/admin"
[[ $BUILD_CHAT -eq 1 ]] && echo "会话前端：${PUBLIC_DIR}/chat"
[[ $BUILD_UNIAPP -eq 1 ]] && echo "移动端 H5：${PUBLIC_DIR}/work"
