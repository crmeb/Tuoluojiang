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

namespace Tuoluojiang\Open\options;

use Tuoluojiang\Open\interfaces\OptionsInterface;
use Tuoluojiang\Open\traits\OptionsTrait;
use Illuminate\Support\Str;

/**
 * 任务参数
 * Class TaskOptions.
 */
class TaskOptions implements OptionsInterface
{
    use OptionsTrait;

    /**
     * 任务名称.
     * @var string
     */
    public $name = '';

    /**
     * 运行周期
     * @var string
     */
    public $period = '';

    /**
     * 是否永久执行.
     * @var int
     */
    public $persist;

    /**
     * 执行次数.
     * @var int
     */
    public $runCount = 1;

    /**
     * 已运行次数.
     * @var int
     */
    public $exeCount;

    /**
     * 类或事件名.
     * @var string
     */
    public $className = '';

    /**
     * 执行方法可为空.
     * @var string
     */
    public $action = '';

    /**
     * 任务唯一值
     * @var string
     */
    public $uniqued = '';

    /**
     * 企业ID.
     * @var int
     */
    public $entid = 0;

    /**
     * 参数.
     * @var array
     */
    public $parameter = [];

    /**
     * 时间间隔.
     * @var array
     */
    public $rate = 0;

    /**
     * 结束时间.
     * @var array
     */
    public $end_time;

    /**
     * 单次时间.
     * @var int
     */
    public $intervalTime = 0;

    /**
     * 月.
     * @var int
     */
    public $intervalMonth = 0;

    /**
     * 周.
     * @var int
     */
    public $intervalWeek = 0;

    /**
     * 天.
     * @var int
     */
    public $intervalDay = 0;

    /**
     * 小时.
     * @var int
     */
    public $intervalHour = 0;

    /**
     * 分.
     * @var int
     */
    public $intervalMinute = 0;

    /**
     * 秒.
     * @var int
     */
    public $intervalSecond = 0;

    /**
     * TaskOptions constructor.
     * @param null $end_time
     */
    public function __construct(
        string $name = '',
        string $period = '',
        int $persist = 0,
        string $className = '',
        string $action = '',
        int $entid = 1,
        int $runCount = 0,
        int $exeCount = 0,
        int $intervalMonth = 0,
        int $intervalWeek = 0,
        int $intervalDay = 0,
        int $intervalHour = 0,
        int $intervalMinute = 0,
        int $intervalSecond = 0,
        string $uniqued = '',
        int $rate = 0,
        $end_time = null
    ) {
        $this->name           = $name;
        $this->period         = $period;
        $this->persist        = $persist;
        $this->className      = $className;
        $this->action         = $action;
        $this->entid          = $entid;
        $this->runCount       = $runCount;
        $this->exeCount       = $exeCount;
        $this->intervalMonth  = $intervalMonth;
        $this->intervalWeek   = $intervalWeek;
        $this->intervalDay    = $intervalDay;
        $this->intervalHour   = $intervalHour;
        $this->intervalMinute = $intervalMinute;
        $this->intervalSecond = $intervalSecond;
        $this->rate           = $rate;
        $this->uniqued        = $uniqued;
        $this->end_time       = $end_time;
    }

    /**
     * 设置参数.
     * @param mixed $vars
     */
    public function setParameter($vars)
    {
        $this->parameter = is_array($vars) ? $vars : func_get_args();
        return $this;
    }

    /**
     * 转换数组.
     */
    public function toArray(): array
    {
        $publicData = get_object_vars($this);
        $data       = [];
        foreach ($publicData as $key => $value) {
            $data[Str::snake($key)] = $value;
        }
        $data['interval'] = [
            'month'  => $data['interval_month'] ?? 0,
            'week'   => $data['interval_week'] ?? 0,
            'day'    => $data['interval_day'] ?? 0,
            'hour'   => $data['interval_hour'] ?? 0,
            'minute' => $data['interval_minute'] ?? 0,
            'second' => $data['interval_second'] ?? 0,
            'time'   => $data['interval_time'] ?? 0,
        ];
        unset($data['interval_second'], $data['interval_minute'], $data['interval_month'], $data['interval_week'], $data['interval_day'], $data['interval_hour'], $data['interval_time']);
        return $data;
    }
}
