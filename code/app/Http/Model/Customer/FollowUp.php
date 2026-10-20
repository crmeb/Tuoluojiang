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

use App\Constants\System\ViewSearchEnum;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Customer\File;
use App\Http\Model\System\Attach;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FollowUp.
 */
class FollowUp extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'             => 'integer',
        'value'          => 'integer',
        'eid'            => 'integer',
        'user_id'        => 'integer',
        'types'          => 'integer',
        'time'           => 'datetime:Y-m-d H:i:s',
        'status'         => 'integer',
        'deleted_at'     => 'datetime:Y-m-d H:i:s',
        'created_at'     => 'datetime:Y-m-d H:i:s',
        'updated_at'     => 'datetime:Y-m-d H:i:s',
        'follow_version' => 'integer',
    ];

    /**
     * @var string
     */
    protected $table = 'client_follow';

    public function clue()
    {
        $linkType = $this->link_type;

        return $this->hasOne(Lead::class, 'id', 'eid')
            ->select(['id', 'name'])
            ->when($linkType === ViewSearchEnum::VIEW_CLUE, function ($query) {
                return $query;
            });
    }

    public function odds()
    {
        $linkType = $this->link_type;

        return $this->hasOne(Opportunity::class, 'id', 'eid')
            ->select(['id', 'name', 'eid'])->with(['customer' => fn ($query) => $query->select(['id', 'customer_name as name'])])
            ->when($linkType === ViewSearchEnum::VIEW_ODDS, function ($query) {
                return $query;
            });
    }

    public function customer()
    {
        $linkType = $this->link_type;

        return $this->hasOne(Customer::class, 'id', 'eid')
            ->select(['id', 'customer_name as name'])
            ->when($linkType === ViewSearchEnum::VIEW_CUSTOMER, function ($query) {
                return $query;
            });
    }

    /**
     * 一对一关联客户.
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(Customer::class, 'id', 'eid')->select([
            'customer.id',
            'customer.customer_name as name',
        ]);
    }

    /**
     * 一对一关联最新跟进.
     * @return HasOne
     */
    public function latest()
    {
        return $this->hasOne(self::class, 'eid', 'eid')->latest();
    }

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'user_id')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * @return HasMany
     */
    public function file()
    {
        return $this->hasMany(File::class, 'fid', 'id');
    }

    public function setFilesAttribute($value)
    {
        $this->attributes['files'] = is_array($value) ? json_encode($value) : '';
    }

    public function getFilesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function scopeEid($query, $value)
    {
        is_array($value) ? $query->whereIn('eid', $value) : $query->where('eid', $value);
    }

    public function scopeTypes($query, $value)
    {
        $query->where('types', $value);
    }

    public function scopeUniqued($query, $value)
    {
        $query->where('uniqued', $value);
    }

    public function scopeId($query, $value)
    {
        $query->where('id', $value);
    }

    /**
     * 附件一对多关联.
     *
     * @return HasMany
     */
    public function attachs()
    {
        return $this->hasMany(Attach::class, 'relation_id', 'id')->where('relation_type', 5)->select(['id', 'att_dir as url', 'relation_id', 'name', 'real_name', 'att_type']);
    }

    /**
     * time作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeTimeLt($query, $value)
    {
        $query->where('time', '<', $value);
    }

    /**
     * user_id作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeUserId($query, $value)
    {
        is_array($value) ? $query->whereIn('user_id', $value) : $query->where('user_id', $value);
    }

    /**
     * nameLike作用域
     * @param mixed $query
     * @param mixed $value
     */
    public function scopeNameLike($query, $value)
    {
        $query->where(function ($query) use ($value) {
            $query->where('link_type', ViewSearchEnum::VIEW_CLUE)
                ->whereExists(function ($subQuery) use ($value) {
                    $subQuery->from('customer_clue')
                        ->whereColumn('customer_clue.id', 'client_follow.eid')
                        ->where('customer_clue.name', 'like', '%' . $value . '%');
                });
            $query->orWhere(function ($q) use ($value) {
                $q->where('link_type', ViewSearchEnum::VIEW_CUSTOMER)
                    ->whereExists(function ($subQuery) use ($value) {
                        $subQuery->from('customer')
                            ->whereColumn('customer.id', 'client_follow.eid')
                            ->where('customer.customer_name', 'like', '%' . $value . '%');
                    });
            });
            $query->orWhere(function ($q) use ($value) {
                $q->where('link_type', ViewSearchEnum::VIEW_ODDS)
                    ->whereExists(function ($subQuery) use ($value) {
                        $subQuery->from('customer_odds')
                            ->whereColumn('customer_odds.id', 'client_follow.eid')
                            ->where('customer_odds.name', 'like', '%' . $value . '%');
                    });
            });
            $query->orWhere($this->getTable() . '.content', 'like', '%' . $value . '%');
        });
    }

    /**
     * 关联客户、线索、商机是否存在.
     * @param mixed $value
     */
    public function scopeExist(Builder $query, $value)
    {
        $query->where(function ($query) {
            $query->where('link_type', ViewSearchEnum::VIEW_CLUE)
                ->whereExists(function ($subQuery) {
                    $subQuery->from('customer_clue')
                        ->whereColumn('customer_clue.id', 'client_follow.eid');
                });
            $query->orWhere(function ($q) {
                $q->where('link_type', ViewSearchEnum::VIEW_CUSTOMER)
                    ->whereExists(function ($subQuery) {
                        $subQuery->from('customer')
                            ->whereColumn('customer.id', 'client_follow.eid');
                    });
            });
            $query->orWhere(function ($q) {
                $q->where('link_type', ViewSearchEnum::VIEW_ODDS)
                    ->whereExists(function ($subQuery) {
                        $subQuery->from('customer_odds')
                            ->whereColumn('customer_odds.id', 'client_follow.eid');
                    });
            });
        });
    }
}
