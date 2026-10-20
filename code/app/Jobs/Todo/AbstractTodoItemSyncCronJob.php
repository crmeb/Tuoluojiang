<?php

declare(strict_types=1);

namespace App\Jobs\Todo;

use App\Task\todo\TodoItemSyncTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;

abstract class AbstractTodoItemSyncCronJob extends CronJob
{
    /**
     * 频率：每5分钟运行一次。实时更新由业务变更事件负责，这里仅做兜底校准.
     */
    public function interval(): int
    {
        return 1000 * 60 * 5;
    }

    public function run(): void
    {
        Task::deliver(new TodoItemSyncTask($this->types()));
    }

    /**
     * @return array<int, string>
     */
    abstract protected function types(): array;
}
