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

namespace Tuoluojiang\Open\utils;

use Swoole\Table;

/**
 * 指定时间执行任务
 * Class Task.
 */
class Task
{
    /**
     * 周期类型.
     */
    public const PERIOD = ['year', 'month', 'week', 'day', 'second', 'once'];

    /**
     * 任务列表.
     * @var array
     */
    protected $task = [];

    /**
     * 增物规则.
     * @var array
     */
    protected $taskField = [
        'name'      => '',
        'func'      => [],
        'period'    => self::PERIOD[3],
        'persist'   => false,
        'run_count' => 1,
        'exe_count' => 0,
        'rate'      => 0, // 频率
        'uniqued'   => '', // 标记
        'end_time'  => '', // 结束时间
        'interval'  => [],
        'parameter' => [],
    ];

    /**
     * 主键.
     * @var int
     */
    protected $index;

    /**
     * @var array
     */
    protected $defaultTaskField;

    /**
     * @var Task
     */
    protected static $instance;

    /**
     * Task constructor.
     */
    public function __construct()
    {
        $this->defaultTaskField = $this->taskField;
    }

    /**
     * 获取表实例.
     * @return Table
     */
    public function getTable()
    {
        return app('swoole')->taskTable;
    }

    /**
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 重置进程内 Task 单例（Swoole Table 仍为任务权威数据源）.
     *
     * 请求间隙清理，避免 $task 内存 map 与单例跨请求膨胀；下次 instance() 会按需重建。
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * 获取任务
     * @return array
     */
    public function getTask()
    {
        $contTask = count($this->task);
        if (! $contTask || $contTask != $this->getTable()->count()) {
            foreach ($this->getTable() as $key => $item) {
                $this->task[$key] = [
                    'id'         => $item['id'],
                    'name'       => $item['name'],
                    'func'       => json_decode($item['func'], true),
                    'period'     => $item['period'],
                    'persist'    => (bool) $item['persist'],
                    'run_count'  => $item['run_count'],
                    'exe_count'  => $item['exe_count'],
                    'interval'   => json_decode($item['interval'], true),
                    'parameter'  => json_decode($item['parameter'], true),
                    'end_time'   => $item['end_time'],
                    'rate'       => $item['rate'],
                    'uniqued'    => $item['uniqued'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
            }
        }
        return $this->task;
    }

    /**
     * 设置运行次数.
     * @return int|mixed
     */
    public function incExcCount(int $index, int $inc)
    {
        $value = $this->getTable()->get((string) $index);
        if ($value) {
            $value['exe_count'] += $inc;
            $this->getTable()->set((string) $index, $value);
            return $value['exe_count'];
        }
        return 0;
    }

    /**
     * 设置.
     * @param mixed $value
     * @return $this
     */
    public function setTask(int $index, string $field, $value)
    {
        $this->task[$index][$field] = $value;
        return $this;
    }

    /**
     * 设置名称.
     * @return $this
     */
    public function name(string $name)
    {
        $this->taskField['name'] = $name;
        return $this;
    }

    /**
     * 是否永久执行.
     * @return $this
     */
    public function persist(bool $persist)
    {
        $this->taskField['persist'] = $persist;
        return $this;
    }

    /**
     * 运行次数.
     * @return $this
     */
    public function runCount(int $count)
    {
        $this->taskField['run_count'] = $count;
        return $this;
    }

    /**
     * 已运行次数.
     * @return $this
     */
    public function excCount(int $count)
    {
        $this->taskField['exe_count'] = $count;
        return $this;
    }

    /**
     * 设置主键.
     * @return $this
     */
    public function index(int $index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * 设置主键.
     * @param mixed $update_time
     * @return $this
     */
    public function updateAt($update_time)
    {
        $this->taskField['updated_at'] = $update_time;
        return $this;
    }

    /**
     * 设置触发事件.
     * @param array|string $event
     * @return $this
     */
    public function event($event)
    {
        $this->taskField['func'] = $event;
        return $this;
    }

    /**
     * 设置触发周期
     * @return $this
     */
    public function period(string $period)
    {
        if (! in_array($period, self::PERIOD)) {
            throw new \RuntimeException('周期格式错误');
        }
        $this->taskField['period'] = $period;
        return $this;
    }

    /**
     * 设置间隔时间.
     * @param array|int|string $interval
     * @return $this
     */
    public function interval($interval)
    {
        $this->taskField['interval'] = $interval;
        return $this;
    }

    /**
     * 设置月.
     * @return $this
     */
    public function monthInterval(int $month)
    {
        $this->taskField['interval']['month'] = $month;
        return $this;
    }

    /**
     * 设置天.
     * @return $this
     */
    public function dayInterval(int $day)
    {
        $this->taskField['interval']['day'] = $day;
        return $this;
    }

    /**
     * 设置星期几.
     * @return $this
     */
    public function dayWeekInterval(int $week)
    {
        $this->taskField['interval']['dayOfWeek'] = $week;
        return $this;
    }

    /**
     * 设置小时.
     * @param string $hour
     * @return $this
     */
    public function hourInterval(int $hour)
    {
        $this->taskField['interval']['hour'] = $hour;
        return $this;
    }

    /**
     * 设置分.
     * @return $this
     */
    public function minuteInterval(int $minute)
    {
        $this->taskField['interval']['minute'] = $minute;
        return $this;
    }

    /**
     * 设置秒.
     * @return $this
     */
    public function secondInterval(int $second)
    {
        $this->taskField['interval']['second'] = $second;
        return $this;
    }

    /**
     * 设置时间.
     * @return $this
     */
    public function timeInterval(string $time)
    {
        $this->taskField['interval']['time'] = $time;
        return $this;
    }

    /**
     * 设置参数.
     * @return $this
     */
    public function parameter(array $parameter)
    {
        $this->taskField['parameter'] = $parameter;
        return $this;
    }

    /**
     * 设置参数.
     * @return $this
     */
    public function rate(string $rate)
    {
        $this->taskField['rate'] = $rate;
        return $this;
    }

    /**
     * 设置参数.
     * @return $this
     */
    public function uniqued(string $uniqued)
    {
        $this->taskField['uniqued'] = $uniqued;
        return $this;
    }

    /**
     * 设置参数.
     * @param mixed $end_time
     * @return $this
     */
    public function end_time($end_time)
    {
        $this->taskField['end_time'] = $end_time;
        return $this;
    }

    /**
     * 放入任务
     * @return bool
     */
    public function push()
    {
        if (! $this->index) {
            throw new \RuntimeException('请设置任务主键');
        }
        $taskField                = $this->taskField;
        $this->task[$this->index] = $taskField;
        $this->taskField          = $this->defaultTaskField;
        if ($this->getTable()->exists((string) $this->index)) {
            return true;
        }
        return $this->getTable()->set((string) $this->index, [
            'id'         => $this->index,
            'name'       => $taskField['name'],
            'func'       => json_encode($taskField['func']),
            'period'     => $taskField['period'],
            'persist'    => $taskField['persist'] ? 1 : 0,
            'run_count'  => $taskField['run_count'],
            'exe_count'  => $taskField['exe_count'],
            'interval'   => json_encode($taskField['interval']),
            'parameter'  => json_encode($taskField['parameter']),
            'rate'       => $taskField['rate'],
            'uniqued'    => $taskField['uniqued'],
            'end_time'   => $taskField['end_time'],
            'updated_at' => $taskField['updated_at'],
        ]);
    }

    /**
     * 删除任务
     */
    public function deleteTask(int $index)
    {
        $this->getTable()->delete((string) $index);
        if (isset($this->task[$index])) {
            unset($this->task[$index]);
        }
        return true;
    }
}
