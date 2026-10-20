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

namespace App\Http\Model\Frame;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\PathAttrTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Frame.
 */
class Frame extends BaseModel
{
    use PathAttrTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'frame';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'                => 'integer',
        'value'       => 'integer',
        'user_id'           => 'integer',
        'entid'             => 'integer',
        'pid'               => 'integer',
        'role_id'           => 'integer',
        'sort'              => 'integer',
        'user_count'        => 'integer',
        'user_single_count' => 'integer',
        'is_show'           => 'integer',
        'level'             => 'integer',
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
        'deleted_at'        => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * introduce 修改器.
     * @param mixed $value
     */
    public function setIntroduceAttribute($value)
    {
        $this->attributes['introduce'] = htmlentities($value);
    }

    /**
     * introduce 获取器.
     * @param mixed $value
     * @return string
     */
    public function getIntroduceAttribute($value)
    {
        return htmlspecialchars_decode($value);
    }

    /**
     * 企业ID作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeEntid($query, $value)
    {
        return $query->where('entid', $value);
    }

    /**
     * 分类名称作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeNameLike($query, $value)
    {
        return $value !== '' ? $query->where('name', 'LIKE', "%{$value}%") : null;
    }

    /**
     * 屏蔽ID作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopeNotId($query, $value)
    {
        $query->where('id', '<>', $value);
    }

    /**
     * 父级ID作用域
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    public function scopePid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('pid', $value);
        } else {
            return $value !== '' ? $query->where('pid', $value) : null;
        }
    }

    /**
     * ids作用域
     * @param Builder $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeIds($query, $value)
    {
        if (is_array($value)) {
            return $query->whereIn('id', $value);
        }
        return $query->where('id', $value);
    }

    /**
     * 一对多远程关联用户.
     * @return HasManyThrough
     * @throws BindingResolutionException
     */
    public function user()
    {
        $table = app()->get(Admin::class);
        return $this->hasManyThrough(
            $table,
            FrameAssist::class,
            'frame_id',
            'id',
            'id',
            'user_id'
        )->with([
            'job'      => fn ($q) => $q->select(['id', 'name', 'rank_id', 'card_id']),
            'frameIds' => fn ($query) => $query->select(['user_id', 'frame_id']),
        ])
            ->where(['status' => 1])
            ->groupBy([$table->getTable() . '.id']);
    }

    /**
     * 一对多远程关联正常用户.
     * @return HasManyThrough
     * @throws BindingResolutionException
     */
    public function users()
    {
        $table = app()->get(Admin::class);
        return $this->hasManyThrough(
            $table,
            FrameAssist::class,
            'frame_id',
            'id',
            'id',
            'user_id'
        )->with([
            'job' => fn ($q) => $q->select(['rank_job.id', 'name', 'rank_id']),
        ]);
    }

    /**
     * 一对一关联直属上级.
     * @return HasOne
     */
    public function super()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * pid作用域
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopePids($query, $value)
    {
        is_array($value) ? $query->whereIn('pid', $value) : $query->where('pid', $value);
    }

    public function scopeFrameIds($query, $value)
    {
        $query->whereIn('frame_id', $value);
    }

    public function scopeUserIds($query, $value)
    {
        $query->whereIn('user_id', $value);
    }

    public function scopeNotPid($query, $value)
    {
        if (is_array($value)) {
            $query->whereNotIn('pid', $value);
        } else {
            $query->where('pid', '<>', $value);
        }
    }

    public function scopePath($query, $value)
    {
        if (is_array($value)) {
            $query->where(function ($que) use ($value) {
                foreach ($value as $val) {
                    $que->where(fn ($q) => $q->orWhere('path', 'like', "%/{$val}/%")->orWhere('path', 'like', "%/{$val}/")->orWhere('path', 'like', "/{$val}/%"));
                }
            });
        } else {
            $query->where(fn ($q) => $q->orWhere('path', 'like', "%/{$value}/%")->orWhere('path', 'like', "%/{$value}/")->orWhere('path', 'like', "/{$value}/%"));
        }
    }
}
