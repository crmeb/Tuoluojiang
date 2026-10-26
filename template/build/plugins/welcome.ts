import chalk from 'chalk'
import type { Plugin } from 'vite'

/**
 * dev server 启动时打印欢迎信息，替代原先 `node src/libs/start.mjs && vite` 的前置进程。
 *
 * 挂在 `configureServer` 上而不是 `configResolved`：
 * - 只有 `vite`（dev server）会触发它，`vite build` / `vite preview` 都不会；
 * - 时机在 server.listen 之前，因此输出排在 Vite 自己的 "ready in xx ms" banner 之前，
 *   与旧脚本「第一时间输出」的效果一致。
 */
export function welcomePlugin(): Plugin {
  let isFirstStart = true;
  return {
    name: 'oa-welcome',
    apply: 'serve',
    configureServer(server) {
      const originalPrintUrls = server.printUrls.bind(server);

      server.printUrls = () => {
        if (isFirstStart) {
          isFirstStart = false;
          [
            chalk.hex('#DEADED').underline('😄 Hello ~ 欢迎使用CRMEB陀螺匠，我们将竭诚为您服务！'),
            chalk.yellow('info - [提示] 点击这里查看更多商品~ ') + chalk.blue.underline('https://www.crmeb.com'),
            chalk.yellow('info - [提示] 点击这里可以查看开发文档喔~ ') + chalk.blue.underline('https://doc.crmeb.com/tuoluojiang'),
            chalk.yellow('info - [提示] 点击这里可以查看我们的论坛社区~ ') + chalk.blue.underline('https://www.crmeb.com/ask'),
            chalk.blue('info - [你知道吗？] 按 Ctrl+C 可以停止服务呢~'),
          ].forEach(c => console.log(c));
        }
        originalPrintUrls();
      }
    },
  }
}
