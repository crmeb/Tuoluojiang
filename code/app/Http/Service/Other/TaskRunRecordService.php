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

namespace App\Http\Service\Other;

use App\Http\Dao\Other\TaskRunRecordDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 任务执行记录
 * Class TaskRunRecordService.
 */
class TaskRunRecordService extends BaseService
{
    /**
     * TaskRunRecordServices constructor.
     */
    public function __construct(TaskRunRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存任务运行日志.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function saveRunLog(int $taskId, string $message, string $files = '', int $line = 0, int $status = 1)
    {
        return $this->dao->create([
            'task_id' => $taskId,
            'message' => $message,
            'line'    => $line,
            'files'   => $files,
            'status'  => $status,
        ]);
    }
}
