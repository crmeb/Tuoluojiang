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

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\services\wps\WebOffice;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 云端文件.
 */
class CloudFile extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 表名.
     * @var string
     */
    protected $table = 'folder';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'             => 'integer',
        'user_id'        => 'integer',
        'type'           => 'integer',
        'pid'            => 'integer',
        'entid'          => 'integer',
        'download_count' => 'integer',
        'version'        => 'integer',
        'is_temp'        => 'integer',
        'is_share'       => 'integer',
        'is_collect'     => 'integer',
        'is_shortcut'    => 'integer',
        'is_del'         => 'integer',
        'del_uid'        => 'integer',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
    ];

    public function user()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    public function del_user()
    {
        return $this->hasOne(Admin::class, 'id', 'del_uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    public function scopeId($query, $value)
    {
        is_array($value) ? $query->whereIn('id', $value) : $query->where('id', $value);
    }

    public function scopePath($query, $value)
    {
        if (is_array($value)) {
            $query->where(function ($q) use ($value) {
                $q->whereIn('id', $value);
                foreach ($value as $val) {
                    $q->orWhere('path', 'LIKE', "/{$val}/%");
                }
            });
        } else {
            $query->where('path', 'LIKE', "%/{$value}/%");
        }
    }

    public function scopeAllId($query, $value)
    {
        $query->where(function ($q) use ($value) {
            $q->where('id', $value)->orWhere('path', 'LIKE', "/{$value}/%")->orWhere('path', 'LIKE', "%/{$value}/");
        });
    }

    public function scopeKeyword($query, $val)
    {
        return $query->where('name', 'LIKE', "%{$val}%");
    }

    public function scopePid($query, $pid)
    {
        return $query->where('pid', (int) $pid);
    }

    public function scopeNotPid($query, $pid)
    {
        return $query->where('pid', '<>', (int) $pid);
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid);
    }

    public function scopeType($query, $type)
    {
        $query->where('type', (int) $type);
    }

    public function scopeFid($query, $fid)
    {
        return $query->where('path', 'LIKE', "/{$fid}/%");
    }

    public function share()
    {
        return $this->hasMany(CloudShare::class, 'folder_id', 'id');
    }

    public function getDownloadUrl()
    {
        return $this->upload_type === 1 ? (sys_config('site_url', config('app.url')) . $this->file_url) : $this->file_url;
    }

    public function getFileUrlAttribute($value)
    {
        return $value && ! str_contains($value, 'http') ? (sys_config('site_url', config('app.url')) . $value) : $value;
    }

    public function scopeFileType($query, $val)
    {
        return match ($val) {
            'word'  => $query->whereIn('file_ext', WebOffice::WPS_OFFICE_WORD_TYPE),
            'ppt'   => $query->whereIn('file_ext', WebOffice::WPS_OFFICE_PPT_TYPE),
            'excel' => $query->whereIn('file_ext', WebOffice::WPS_OFFICE_SHEET_TYPE),
            'image' => $query->whereIn('file_ext', ['jpg', 'jpeg', 'png', 'gif', 'pem']),
            default => $query->where('file_ext', $val),
        };
    }
}
