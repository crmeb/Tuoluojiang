<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\swoole\queue;

use Tuoluojiang\Open\swoole\server\WithContainer;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Arr;
use Swoole\Constant;
use Swoole\Process;
use Swoole\Process\Pool;
use Swoole\Timer;
use SwooleTW\Http\Server\Manager as ServerManager;

use function Swoole\Coroutine\run;

class Manager
{
    use WithContainer;

    /**
     * Container.
     *
     * @var Container
     */
    protected $container;

    /**
     * @var OutputStyle
     */
    protected $output;

    /**
     * @var Closure[]
     */
    protected $workers = [];

    /**
     * Manager constructor.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function attachToServer(ServerManager $server, OutputStyle $output)
    {
        $this->output = $output;
        $this->listenForEvents();
        $this->createWorkers();
        foreach ($this->workers as $worker) {
            $server->addProcess(new Process($worker, false, 0, true));
        }
    }

    /**
     * 运行消息队列命令.
     */
    public function run(): void
    {
        @cli_set_process_title('swoole queue: manager process');

        $this->listenForEvents();
        $this->createWorkers();

        $pool = new Pool(count($this->workers));

        $pool->on(Constant::EVENT_WORKER_START, function (Pool $pool, int $workerId) {
            $process = $pool->getProcess($workerId);
            run($this->workers[$workerId], $process);
        });

        $pool->start();
    }

    /**
     * 创建执行任务
     */
    protected function createWorkers()
    {
        $workers = $this->getConfig('queue.workers', []);

        foreach ($workers as $queue => $options) {
            if (strpos($queue, '@') !== false) {
                [$queue, $connection] = explode('@', $queue);
            } else {
                $connection = null;
            }

            $this->workers[] = function (Process $process) use ($options, $connection, $queue) {
                @cli_set_process_title('swoole queue: worker process');

                /** @var Worker $worker */
                $worker = $this->container->make('queue.worker');
                /** @var WorkerOptions $option */
                $option = $this->container->make(WorkerOptions::class);

                $option->sleep    = Arr::get($options, 'sleep', 3);
                $option->maxTries = Arr::get($options, 'tries', 0);
                $option->timeout  = Arr::get($options, 'timeout', 60);

                while (true) {
                    $timer = Timer::after($option->timeout * 1000, function () use ($process) {
                        $process->exit();
                    });

                    $worker->runNextJob($connection, $queue, $option);

                    Timer::clear($timer);

                    gc_collect_cycles();
                    if ($worker->memoryExceeded($option->memory)) {
                        $process->exit(Worker::EXIT_MEMORY_LIMIT);
                    }
                }
            };
        }
    }

    /**
     * 注册事件.
     */
    protected function listenForEvents()
    {
        $this->container->make('events')->listen(JobFailed::class, function (JobFailed $event) {
            $this->writeOutput($event->job);

            $this->logFailedJob($event);
        });
    }

    /**
     * 记录失败任务
     */
    protected function logFailedJob(JobFailed $event)
    {
        $this->container['queue.failer']->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }

    /**
     * Write the status output for the queue worker.
     */
    protected function writeOutput(Job $job, string $status = 'success')
    {
        switch ($status) {
            case 'starting':
                $this->writeStatus($job, 'Processing', 'comment');
                break;
            case 'success':
                $this->writeStatus($job, 'Processed', 'info');
                break;
            case 'failed':
                $this->writeStatus($job, 'Failed', 'error');
                break;
        }
    }

    /**
     * Format the status output for the queue worker.
     *
     * @param string $status
     * @param string $type
     */
    protected function writeStatus(Job $job, $status, $type)
    {
        $this->output->writeln(sprintf(
            "<{$type}>[%s][%s] %s</{$type}> %s",
            date('Y-m-d H:i:s'),
            $job->getJobId(),
            str_pad("{$status}:", 11),
            $job->getName()
        ));
    }
}
