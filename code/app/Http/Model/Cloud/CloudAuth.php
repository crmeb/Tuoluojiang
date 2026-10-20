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

namespace App\Http\Model\Cloud;

use App\Http\Model\BaseModel;

/**
 * 云盘权限.
 */
class CloudAuth extends BaseModel
{
    public $timestamps = false;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'folder_auth';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'user_id'    => 'integer',
        'folder_id'  => 'integer',
        'create'     => 'integer',
        'read'       => 'integer',
        'update'     => 'integer',
        'download'   => 'integer',
        'delete'     => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeFolderId($query, $value)
    {
        is_array($value) ? $query->whereIn('folder_id', $value) : $query->where('folder_id', $value);
    }

    public function scopeUserId($query, $value)
    {
        is_array($value) ? $query->whereIn('user_id', $value) : $query->where('user_id', $value);
    }

    public function scopeNotUid($query, $value)
    {
        $query->where('uid', '<>', $value);
    }
}
