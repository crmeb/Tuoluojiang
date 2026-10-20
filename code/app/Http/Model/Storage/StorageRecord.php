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

namespace App\Http\Model\Storage;

use App\Constants\CacheEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Frame\Frame;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Storage\StorageService;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * 物资记录.
 *
 * Class StorageRecord.
 */
class StorageRecord extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    protected $primaryKey = 'id';

    protected $casts = [
        'id'           => 'integer',
        'user_id'      => 'integer',
        'operator'     => 'integer',
        'storage_id'   => 'integer',
        'storage_type' => 'integer',
        'entid'        => 'integer',
        'card_id'      => 'integer',
        'frame_id'     => 'integer',
        'price'        => 'decimal:2',
        'total'        => 'decimal:2',
        'num'          => 'integer',
        'types'        => 'integer',
        'status'       => 'integer',
        'created_at'   => 'datetime:Y-m-d H:i:s',
        'updated_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_at'   => 'datetime:Y-m-d H:i:s',
    ];

    protected $table = 'storage_record';

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeStorageId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('storage_id', $value);
        } else {
            $query->where('storage_id', $value);
        }
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeIds($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('storage_id', $value);
        } else {
            $query->where('storage_id', $value);
        }
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeStorageType($query, $value)
    {
        $query->where('storage_type', $value);
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeFrameId($query, $value)
    {
        $query->where('frame_id', $value);
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCardId($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('user_id', $value);
        } else {
            $query->where('user_id', $value);
        }
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeTypes($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('types', $value);
        } else {
            $query->where('types', $value);
        }
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeStatus($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeEntid($query, $value)
    {
        $query->where('entid', $value);
    }

    /**
     * @param Builder $query
     * @param mixed $value
     */
    public function scopeCreater($query, $value)
    {
        $query->where('creater', $value);
    }

    /**
     * 关联查询物资.
     * @return HasOne
     */
    public function storage()
    {
        return $this->hasOne(Storage::class, 'id', 'storage_id')->with([
            'cate' => function ($query) {
                $query->select(['id', 'cate_name']);
            }, ]);
    }

    /**
     * 关联查询物资.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id');
    }

    /**
     * 关联查询物资.
     * @return HasOne
     */
    public function frame()
    {
        return $this->hasOne(Frame::class, 'id', 'frame_id');
    }

    /**
     * 关联查询物资.
     * @return HasOne
     */
    public function creater()
    {
        return $this->hasOne(Admin::class, 'id', 'operator');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($data) {
            $services = app()->get(StorageService::class);
            $storage  = $services->get($data->storage_id);
            switch ($data->types) {
                case 1:
                    if ($storage['types']) {
                        if ($data->user_id) {
                            $name = app()->get(AdminService::class)->value($data->user_id, 'name');
                        }
                        if ($data->frame_id) {
                            $name = app()->get(FrameService::class)->value($data->frame_id, 'name');
                        }
                        $remark = '领用对象：' . $name . '，领用时间：' . now()->toDateString();
                        $services->update($data->storage_id, ['status' => 1, 'remark' => $remark]);
                    } else {
                        if ($storage['stock'] < $data->num) {
                            throw new ApiException('超过可领用最大库存！');
                        }
                        $services->dec($data->storage_id, $data->num, 'stock');
                        $services->inc($data->storage_id, $data->num, 'used');
                    }
                    break;
                case 2:
                    if ($storage['types']) {
                        $remark = '归还时间：' . now()->toDateString();
                        $services->update($data->storage_id, ['status' => 0, 'link_id' => 0, 'remark' => $remark]);
                    }
                    break;
                case 3:
                    if ($storage['types']) {
                        $remark = '维修时间：' . now()->toDateString();
                        $services->update($data->storage_id, ['status' => 3, 'remark' => $remark]);
                    }
                    break;
                case 4:
                    if ($storage['types']) {
                        $remark = '报废时间：' . now()->toDateString();
                        $services->update($data->storage_id, ['status' => 4, 'link_id' => 0, 'remark' => $remark]);
                    }
                    break;
                case 0:
                    if ($storage['types']) {
                        $remark = '入库时间：' . now()->toDateString();
                        $services->update($data->storage_id, ['remark' => $remark]);
                    } else {
                        $services->inc($data->storage_id, $data->num, 'stock');
                    }
                    break;
            }
        });
        static::saved(function ($data) {
            Cache::tags([CacheEnum::TAG_STORAGE])->flush();
        });
    }
}
