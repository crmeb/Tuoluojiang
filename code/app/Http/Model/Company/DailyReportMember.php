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

namespace App\Http\Model\Company;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;

/**
 * 日报汇报人
 * Class DailyReportMember.
 */
class DailyReportMember extends BaseModel
{
    use TimeDataTrait;

    /**
     * 表名.
     *
     * @var string
     */
    protected $table = 'daily_report_member';

    protected $casts = [
        'id'         => 'integer',
        'daily_id'   => 'integer',
        'member'     => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * member作用域
     */
    public function scopeMember($query, $value): void
    {
        if (is_array($value)) {
            $query->whereIn('member', $value);
        } elseif ($value !== '') {
            $query->where('member', $value);
        }
    }
}
