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

namespace App\Http\Model\Other;

use App\Constants\AttachEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;

/**
 * 导出记录.
 */
class ExportRecord extends BaseModel
{
    protected $table = 'export_record';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'            => 'integer',
        'uid'           => 'integer',
        'success_count' => 'integer',
        'fail_count'    => 'integer',
        'status'        => 'integer',
        'types'         => 'integer',
        'file_status'   => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public function getFilePathAttribute($value)
    {
        return $value ? link_file($value) : '';
    }

    public function scopeUid($query, $value)
    {
        is_array($value) ? $query->whereIn('uid', $value) : $query->where('uid', $value);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    public function attach()
    {
        return $this->hasOne(Attach::class, 'relation_id', 'id')->where('relation_type', AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_CUSTOMER_IMPORT])->select(['id', 'att_dir as url', 'relation_id', 'name', 'real_name', 'att_type', 'up_type']);
    }
}
