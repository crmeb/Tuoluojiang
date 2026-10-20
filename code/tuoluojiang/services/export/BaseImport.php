<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services\export;

use App\Task\export\ImportTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;

abstract class BaseImport
{
    public string $filePath = '';

    public string $tableName = '';

    public array $fieldMap = [];

    public function __construct(protected int $recordId = 0)
    {
        $this->tableName = $this->tableName ?: $this->setTable();
        $this->fieldMap  = $this->fieldMap ?: $this->setFieldMap();
        Task::deliver(new ImportTask($this, $recordId));
    }

    abstract public function processCallback(): callable;

    abstract public function setTable(): string;

    abstract public function setFieldMap(): array;
}
