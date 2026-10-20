<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Model;

use Tuoluojiang\Open\interfaces\TimeDataInterface;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 *  Class BaseModel.
 */
abstract class BaseModel extends Model implements TimeDataInterface
{
    use TimeDataTrait;

    /**
     * 自动写入时间.
     * @var bool
     */
    public $timestamps = true;

    /**
     * 字段黑名单.
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 分批执行 whereIn 查询.
     * @param string $column 字段名
     * @param array $values 待查询的大数组
     * @param int $batchSize 每批次大小（默认 2000，可根据数据库调整）
     * @param null|\Closure $callback 自定义查询逻辑（可选）
     * @return Collection|\Generator
     */
    public function whereInBatch($baseQuery,string $column, array $values, int $batchSize = 1000, ?\Closure $callback = null)
    {
        if (empty($values)) {
            return $baseQuery;
        }
        $uniqueValues = array_unique($values);
        $batches      = array_chunk($uniqueValues, $batchSize);
        $baseQuery->where(function ($query)use ($column, $baseQuery,$batches){
            foreach ($batches as $batch) {
                $query->orWhereIn($column, $batch);
            }
        });
        return $baseQuery;
    }

    /**
     * 分批执行 whereNotIn 查询.
     * @param string $column 字段名
     * @param array $values 待查询的大数组
     * @param int $batchSize 每批次大小（默认 2000，可根据数据库调整）
     * @param null|\Closure $callback 自定义查询逻辑（可选）
     * @return Collection|\Generator
     */
    public function whereNotInBatch(string $column, array $values, int $batchSize = 1000, ?\Closure $callback = null)
    {
        if (empty($values)) {
            return collect();
        }
        $uniqueValues = array_unique($values);
        $batches      = array_chunk($uniqueValues, $batchSize);
        foreach ($batches as $batch) {
            $query = $this->newQuery()->whereNotIn($column, $batch);
            if ($callback instanceof \Closure) {
                $query = $callback($query);
            } else {
                $query->select($this->getKeyName());
            }
            $cursor = $query->cursor();
            foreach ($cursor as $item) {
                yield $item;
            }
            unset($cursor, $query, $batch);
        }
    }

    /**
     * 设置时间.
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
