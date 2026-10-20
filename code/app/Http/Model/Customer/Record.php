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

namespace App\Http\Model\Customer;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 客户记录
 * Class Record.
 */
class Record extends BaseModel
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'             => 'integer',
        'eid'            => 'integer',
        'type'           => 'integer',
        'uid'            => 'integer',
        'creator_uid'    => 'integer',
        'record_version' => 'integer',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * @var string
     */
    protected $table = 'customer_record';

    /**
     * 创建人.
     * @return HasOne
     */
    public function creator()
    {
        return $this->hasOne(Admin::class, 'id', 'creator_uid')->select(['id', 'avatar', 'name']);
    }

    public function follow()
    {
        return $this->hasOne(FollowUp::class, 'id', 'record_version')->with(['attachs']);
    }

    /**
     * type 作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeType($query, $value): void
    {
        is_array($value) ? $query->whereIn('type', $value) : $query->where('type', $value);
    }

    public function scopeEid($query, $value)
    {
        is_array($value) ? $query->whereIn('eid', $value) : $query->where('eid', $value);
    }

    /**
     * reason 模糊搜索（跟进/动态摘要）.
     *
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeReason($query, $value): void
    {
        $keyword = trim((string) $value);
        if ($keyword === '') {
            return;
        }
        $query->where('reason', 'like', '%' . $keyword . '%');
    }

    /**
     * 最新记录.
     * @return HasOne
     */
    public function latest()
    {
        return $this->hasOne(self::class, 'eid', 'eid')->latest();
    }
}
