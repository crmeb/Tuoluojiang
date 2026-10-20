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

use App\Http\Model\Admin\Admin;
use App\Http\Model\Position\Job;
use App\Http\Model\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 评估表数据.
 */
class HayGroupData extends BaseModel
{
    /**
     * 表名.
     * @var string
     */
    protected $table = 'hay_group_data';

    /**
     * 主键.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $casts = [
        'id'         => 'integer',
        'group_id'   => 'integer',
        'uid'        => 'integer',
        'col1'       => 'integer',
        'col2'       => 'integer',
        'col3'       => 'integer',
        'col4'       => 'integer',
        'col5'       => 'integer',
        'col6'       => 'integer',
        'col7'       => 'integer',
        'col8'       => 'integer',
        'col9'       => 'integer',
        'col10'      => 'integer',
        'col12'      => 'integer',
        'sort'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 一对一关联职位.
     * @return HasOne
     */
    public function position()
    {
        return $this->hasOne(Job::class, 'id', 'col1')->select(['rank_job.id', 'rank_job.name']);
    }

    /**
     * 一对一远程关联用户.
     * @return HasOne
     */
    public function card()
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'uid', 'name', 'avatar', 'phone']);
    }

    /**
     * 格式化col11字段.
     * @param mixed $value
     */
    public function setCol11Attribute($value)
    {
        $this->attributes['col11'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 获取col11数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol11Attribute($value): array
    {
        return $value ? array_map('intval', json_decode($value)) : [];
    }

    /**
     * 获取col2数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol2Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col3数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol3Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col4数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol4Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col5数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol5Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col6数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol6Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col7数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol7Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col8数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol8Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col9数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol9Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col10数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol10Attribute($value): mixed
    {
        return $value ?: null;
    }

    /**
     * 获取col12数据.
     * @param mixed $value
     * @return string[]
     */
    public function getCol12Attribute($value): mixed
    {
        return $value ?: null;
    }
}
