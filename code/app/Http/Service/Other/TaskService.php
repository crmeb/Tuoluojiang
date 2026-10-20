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

use App\Http\Dao\Other\TaskDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\options\TaskOptions;
use Tuoluojiang\Open\utils\Arr;
use Tuoluojiang\Open\utils\Task;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class TaskService.
 */
class TaskService extends BaseService
{
    /**
     * 任务
     * @var Task
     */
    protected $task;

    /**
     * 时间规则.
     * @var int[]
     */
    protected $interval = ['month' => 0, 'week' => 0, 'day' => 0, 'hour' => 0, 'minute' => 0, 'second' => 0, 'time' => 0];

    /**
     * TaskService constructor.
     */
    public function __construct(TaskDao $dao, Task $task)
    {
        $this->dao  = $dao;
        $this->task = $task;
    }

    /**
     * 设置任务
     */
    public function setTask(Task $task)
    {
        $this->task = $task;
    }

    /**
     * 初始化任务
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function initializeTask()
    {
        $list = $this->dao->getList([], ['*'], 0, 0, 'id');
        foreach ($list as $item) {
            $interval = Arr::setDefaultField($item['interval'], $this->interval);
            $this->task->index($item['id'])
                ->name($item['name'])
                ->parameter($item['parameter'])
                ->monthInterval((int) $interval['month'])
                ->dayInterval((int) $interval['day'])
                ->dayWeekInterval((int) $interval['week'])
                ->hourInterval((int) $interval['hour'])
                ->minuteInterval((int) $interval['minute'])
                ->secondInterval((int) $interval['second'])
                ->timeInterval((string) $interval['time'])
                ->runCount((int) $item['run_count'])
                ->event($item['class_name'] && $item['action'] ? [$item['class_name'], $item['action']] : $item['class_name'])
                ->period($item['period'])
                ->persist((bool) $item['persist'])
                ->updateAt($item['updated_at'])
                ->rate((string) $item['rate'])
                ->uniqued($item['uniqued'])
                ->end_time($item['end_time'])
                ->push();
        }
    }

    /**
     * 删除任务
     * @return bool
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteTask(int $id)
    {
        return $this->dao->delete($id) && $this->task->deleteTask($id);
    }

    /**
     * 获取任务
     * @return array
     */
    public function getTask()
    {
        return $this->task->getTask();
    }

    /**
     * 更新 执行次数.
     */
    public function updateExcCount(int $id, int $inc = 1)
    {
        $this->dao->inc($id, $inc, 'exe_count');
        return $this->task->incExcCount($id, $inc);
    }

    /**
     * 获取任务对象
     * @return Task
     */
    public function task()
    {
        return $this->task;
    }

    /**
     * 新增任务并放入内存.
     * @return bool
     * @throws BindingResolutionException
     */
    public function addTask(TaskOptions $options)
    {
        if (! $options->className) {
            throw $this->exception('执行任务类缺少');
        }
        if (! in_array($options->period, Task::PERIOD)) {
            throw $this->exception('周期格式错误');
        }
        if (! $options->intervalDay
            && ! $options->intervalHour
            && ! $options->intervalMinute
            && ! $options->intervalMonth
            && ! $options->intervalSecond
            && ! $options->intervalTime
            && ! $options->intervalWeek) {
            throw $this->exception('触发时间必须填写一项');
        }
        $task = $this->dao->create($options->toArray());

        return $this->task->name($options->name)
            ->parameter($options->parameter)
            ->dayWeekInterval($options->intervalWeek)
            ->hourInterval($options->intervalHour)
            ->secondInterval($options->intervalSecond)
            ->minuteInterval($options->intervalMinute)
            ->monthInterval($options->intervalMonth)
            ->timeInterval((string) $options->intervalTime)
            ->index($task->id)
            ->persist((bool) $options->persist)
            ->period($options->period)
            ->rate((string) $options->rate)
            ->uniqued($options->uniqued)
            ->end_time($options->end_time)
            ->updateAt(now()->timezone(config('app.timezone'))->toDateString())
            ->event($options->className && $options->action ?
                [$options->className, $options->action] : $options->className)
            ->push();
    }
}
