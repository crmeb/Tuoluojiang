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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目任务
 * Class ProgramTask.
 */
class ProgramTask extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'program_task';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'          => 'integer',
        'value'       => 'integer',
        'pid'         => 'integer',
        'top_id'      => 'integer',
        'level'       => 'integer',
        'program_id'  => 'integer',
        'version_id'  => 'integer',
        'creator_uid' => 'integer',
        'uid'         => 'integer',
        'status'      => 'integer',
        'priority'    => 'integer',
        'plan_start'  => 'date:Y-m-d',
        'plan_end'    => 'date:Y-m-d',
        'sort'        => 'integer',
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s',
        'deleted_at'  => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 隐藏字段.
     * @var string[]
     */
    protected $hidden = [
        'deleted_at',
    ];

    public function setPlanStartAttribute($value): void
    {
        $this->attributes['plan_start'] = $value ?: null;
    }

    public function setPlanEndAttribute($value): void
    {
        $this->attributes['plan_end'] = $value ?: null;
    }

    /**
     * 一对多远程关联用户.
     * @return HasManyThrough
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function members()
    {
        $table = app()->get(Admin::class);
        return $this->hasManyThrough(
            $table,
            ProgramTaskMember::class,
            'task_id',
            'id',
            'id',
            'uid'
        )->where([$table->getTable() . '.status' => 1])
            ->select([$table->getTable() . '.id', $table->getTable() . '.name', $table->getTable() . '.avatar']);
    }

    /**
     * 关联父级.
     * @return HasOne
     */
    public function parent()
    {
        return $this->hasOne(ProgramTask::class, 'id', 'pid')->select(['id', 'name']);
    }

    /**
     * 关联负责人.
     * @return HasMany
     */
    public function admins()
    {
        return $this->hasMany(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone']);
    }

    /**
     * 关联项目.
     * @return HasOne
     */
    public function program()
    {
        return $this->hasOne(Program::class, 'id', 'program_id')->select(['id', 'name']);
    }

    /**
     * 关联版本.
     * @return HasOne
     */
    public function version()
    {
        return $this->hasOne(ProgramVersion::class, 'id', 'version_id')->select(['id', 'name']);
    }

    /**
     * 关联创建人.
     * @return HasMany
     */
    public function creator()
    {
        return $this->hasMany(Admin::class, 'id', 'creator_uid')->select(['id', 'name', 'avatar']);
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
     * program_id 作用域
     */
    public function scopeProgramId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('program_id', $value);
        } elseif ($value !== '') {
            $query->where('program_id', $value);
        }
    }

    /**
     * admins 作用域
     */
    public function scopeAdmins($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * status 作用域
     */
    public function scopeStatus($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('status', $value);
        } elseif ($value !== '') {
            $query->where('status', $value);
        }
    }

    /**
     * priority 作用域
     */
    public function scopePriority($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('priority', $value);
        } elseif ($value !== '') {
            $query->where('priority', $value);
        }
    }

    /**
     * name作用域
     */
    public function scopeNameLike($query, $value): void
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * created_at作用域
     */
    public function scopeCreatedAt($query, $value)
    {
        $this->setTimeField('created_at')->scopeTime($query, $value);
    }

    /**
     * plan_start作用域
     */
    public function scopePlanStart($query, $value)
    {
        $this->setTimeField('plan_start')->scopeTime($query, $value);
    }

    /**
     * plan_end作用域
     */
    public function scopePlanEnd($query, $value)
    {
        $this->setTimeField('plan_end')->scopeTime($query, $value);
    }

    /**
     * pid 作用域
     */
    public function scopePid($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('pid', $value);
        } elseif ($value !== '') {
            $query->where('pid', $value);
        }
    }

    /**
     * 格式化path字段.
     */
    public function setPathAttribute($value): void
    {
        $this->attributes['path'] = $value ? '/' . implode('/', $value) . '/' : '';
    }

    /**
     * 格式化path字段.
     * @return string[]
     */
    public function getPathAttribute($value): array
    {
        return $value ? array_map('intval', array_merge(array_filter(explode('/', $value)))) : [];
    }

    /**
     * describe获取器.
     */
    public function getDescribeAttribute($value): string
    {
        return $value ? stripslashes(htmlspecialchars_decode($value)) : '';
    }

    /**
     * path 作用域
     */
    public function scopePath($query, $value): void
    {
        if (is_array($value)) {
            $query->where(function () use ($query, $value) {
                foreach ($value as $item) {
                    $query->orWhere('path', 'like', '%/' . $item . '/%');
                }
            });
        } elseif ($value !== '') {
            $query->where('path', 'like', '%/' . $value . '/%');
        }
    }

    /**
     * uid not 作用域
     */
    public function scopeUidNot($query, $value): void
    {
        if ($value) {
            $query->where('uid', '<>', $value);
        }
    }

    /**
     * status not 作用域
     */
    public function scopeStatusNot($query, $value): void
    {
        if ($value) {
            $query->where('status', '<>', $value);
        }
    }

    /**
     * version_id not 作用域
     */
    public function scopeVersionIdNot($query, $value): void
    {
        if ($value) {
            $query->where('version_id', '<>', $value);
        }
    }

    /**
     * pid not 作用域
     */
    public function scopePidNot($query, $value): void
    {
        if ($value) {
            $query->where('pid', '<>', $value);
        }
    }

    /**
     * program_id not 作用域
     */
    public function scopeProgramIdNot($query, $value): void
    {
        if ($value) {
            $query->where('program_id', '<>', $value);
        }
    }

    /**
     * plan_start not or null 作用域
     */
    public function scopePlanStartNotOrNull($query, $value): void
    {
        if ($value) {
            $query->where(function ($query) use ($value) {
                $query->where('plan_start', '<>', $value)->orWhereNull('plan_start');
            });
        }
    }

    /**
     * plan_end not or null 作用域
     */
    public function scopePlanEndNotOrNull($query, $value): void
    {
        if ($value) {
            $query->where(function ($query) use ($value) {
                $query->where('plan_end', '<>', $value)->orWhereNull('plan_end');
            });
        }
    }

    /**
     * top_id 作用域
     */
    public function scopeTopId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('top_id', $value);
        } elseif ($value !== '') {
            $query->where('top_id', $value);
        }
    }

    /**
     * version_id 作用域
     */
    public function scopeVersionId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('version_id', $value);
        } elseif ($value !== '') {
            $query->where('version_id', $value);
        }
    }

    /**
     * auth_id 作用域
     */
    public function scopeAuthUid($query, $value): void
    {
        if ($value !== '') {
            $query->where(function ($query) use ($value) {
                $query->where('uid', $value)->orWhere('creator_uid', $value);
            });
        }
    }

    /**
     * uid 和 creator_uid 作用域
     */
    public function scopeUidOrCreatorUid($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value)->orWhereIn('creator_uid', $value);
        } elseif ($value !== '') {
            $query->where('uid', $value)->orWhere('creator_uid', $value);
        }
    }
}
