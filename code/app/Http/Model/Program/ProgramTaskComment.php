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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 项目任务评论
 * Class ProgramTaskComment.
 */
class ProgramTaskComment extends BaseModel
{
    use SoftDeletes;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'program_task_comment';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'task_id'    => 'integer',
        'pid'        => 'integer',
        'reply_uid'  => 'integer',
        'uid'        => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 隐藏字段.
     * @var string[]
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * 关联评论人.
     * @return HasOne
     */
    public function member()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['admin.id', 'admin.name', 'admin.avatar']);
    }

    /**
     * 关联评论回复人.
     * @return HasOne
     */
    public function reply_member()
    {
        return $this->hasOne(Admin::class, 'id', 'reply_uid')
            ->select(['admin.id', 'admin.name', 'admin.avatar']);
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
     * task_id 作用域
     */
    public function scopeTaskId($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('task_id', $value);
        } elseif ($value !== '') {
            $query->where('task_id', $value);
        }
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
     * describe获取器.
     */
    public function getDescribeAttribute($value): string
    {
        return $value ? stripslashes(htmlspecialchars_decode($value)) : '';
    }
}
