<?php

declare(strict_types=1);

namespace App\Jobs\Todo;

use App\Constants\TodoEnum;

class TodoApprovePendingSyncCronJob extends AbstractTodoItemSyncCronJob
{
    protected function types(): array
    {
        return [TodoEnum::TYPE_APPROVE_PENDING];
    }
}
