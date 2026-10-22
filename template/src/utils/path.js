export function resolve(...segments) {
  // 从右到左拼接，遇到绝对路径停止
  let path = '';
  for (let i = segments.length - 1; i >= 0; i--) {
    const seg = segments[i];
    path = seg + (path ? '/' + path : '');
    if (seg.startsWith('/')) break;
  }

  // 如果最终还不是绝对路径，加上当前"工作目录"
  // 浏览器没有 cwd，可以用 location.pathname 或固定为 '/'
  if (!path.startsWith('/')) {
    path = '/' + path;
  }

  return normalize(path);
}

function normalize(path) {
  const parts = path.split('/');
  const stack = [];
  for (const part of parts) {
    if (part === '..') {
      stack.pop();
    } else if (part !== '.' && part !== '') {
      stack.push(part);
    }
  }
  return '/' + stack.join('/');
}