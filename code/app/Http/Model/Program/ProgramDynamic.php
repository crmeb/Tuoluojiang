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

namespace App\Http\Model\Program;

use App\Http\Service\Program\ProgramTaskService;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目动态
 * Class ProgramDynamic.
 */
class ProgramDynamic extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'program_dynamic';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'types'       => 'integer',
        'uid'         => 'integer',
        'relation_id' => 'integer',
        'action_type' => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联任务.
     * @return HasOne
     */
    public function task()
    {
        return $this->hasOne(ProgramTask::class, 'id', 'relation_id')
            ->select(['program_task.id', 'program_task.name', 'program_task.ident', 'program_task.program_id']);
    }

    /**
     * id 作用域
     */
    public function scopeId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } elseif ($value !== '') {
            $query->where('id', $value);
        }
    }

    /**
     * uid 作用域
     */
    public function scopeUid($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * 格式化describe字段.
     */
    public function setDescribeAttribute($value): void
    {
        $this->attributes['describe'] = json_encode($value);
    }

    /**
     * 格式化describe字段.
     * @return string[]
     */
    public function getDescribeAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * program_id 作用域
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeProgramId($query, $value): void
    {
        if ($value != '') {
            $query->whereIn('relation_id', app()->get(ProgramTaskService::class)->setTrashed()->column(['program_id' => $value], 'id'));
        }
    }
}
