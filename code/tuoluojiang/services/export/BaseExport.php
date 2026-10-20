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

use App\Task\export\ExportTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;

abstract class BaseExport
{
    public string $filePath = '';

    public string $format = 'xlsx';

    public string $fileName = '';

    public array $headings = [];

    public string $moduleName;

    public function __construct(protected int $recordId)
    {
        $this->fileName = $this->setFileName();
        $this->filePath = '/exports/' . $this->fileName . '.' . $this->format;
        $this->headings = $this->setHeadings();
        Task::deliver(new ExportTask($this, $this->recordId));
    }

    abstract public function setHeadings(): array;

    abstract public function setDataCallback(): callable;

    public function setFileName(): string
    {
        return $this->moduleName . '数据导出(' . date('YmdHis') . ')';
    }
}
