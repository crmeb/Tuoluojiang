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

namespace App\Http\Dao\Customer;

use App\Http\Model\Customer\Product;
use App\Http\Model\Customer\ProductAttrValue;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\JoinSearchTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductAttrValueDao extends BaseDao
{
    use JoinSearchTrait;

    protected $table = 'customer_product_attr_value';

    /**
     * 设置模型.
     * @return BaseModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            $this->createTable();
        }
        return parent::getModel();
    }

    /**
     * 插入数据.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function insert(array $data): bool
    {
        return $this->getModel(false)->insert($data);
    }

    public function joinSearch(array $where, int $page = 0, int $limit = 0, array $with = [], mixed $sort = null)
    {
        return $this->getJoinModel('product_id', 'id', '=', 'left')
            ->when($where['pid'], function ($query) use ($where) {
                $query->where($this->getFiled('pid', $this->aliasB), $where['pid']);
            })
            ->when(isset($where['name']) && $where['name'], function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->orWhere($this->getFiled('name', $this->aliasB), 'like', '%' . $where['name'] . '%')
                        ->orWhere($this->getFiled('number', $this->aliasB), 'like', '%' . $where['name'] . '%')
                        ->orWhere($this->getFiled('sku', $this->aliasA), 'like', '%' . $where['name'] . '%')
                        ->orWhere($this->getFiled('bar_code', $this->aliasA), 'like', '%' . $where['name'] . '%');
                });
            })
            ->when(isset($where['types']) && $where['types'] !== '', function ($query) use ($where) {
                $query->where($this->getFiled('types', $this->aliasB), $where['types']);
            })
            ->when(isset($where['name_like']) && $where['name_like'], function ($query) use ($where) {
                $query->where($this->getFiled('name', $this->aliasB), 'like', '%' . $where['name_like'] . '%');
            })
            ->when(isset($where['attr_like']) && $where['attr_like'], function ($query) use ($where) {
                $query->where(function ($q) use ($where) {
                    $q->where($this->getFiled('sku', $this->aliasA), 'like', '%' . $where['attr_like'] . '%')
                        ->orWhere($this->getFiled('bar_code', $this->aliasA), 'like', '%' . $where['attr_like'] . '%');
                });
            })
            ->whereNull($this->getFiled('deleted_at', $this->aliasB))
            ->where($this->getFiled('is_show', $this->aliasB), 1)
            ->select([$this->getFiled('*', $this->aliasA), $this->getFiled('id', $this->aliasB), $this->getFiled('name', $this->aliasB)])
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->forPage($page, $limit);
            })->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->when($sort, function ($query) use ($sort) {
                foreach ($sort as $item) {
                    $query->orderByDesc($item);
                }
            })->with($with);
    }

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return ProductAttrValue::class;
    }

    protected function setModelB(): string
    {
        return Product::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('product_id')->default(0)->index()->comment('商品ID');
            $table->text('detail')->default('')->comment('商品属性详情');
            $table->string('sku', 36)->default('')->index()->comment('商品属性索引值');
            $table->unsignedDecimal('ot_price', 11)->default('0.00')->comment('原价');
            $table->unsignedDecimal('price', 11)->default('0.00')->comment('售价');
            $table->unsignedDecimal('cost', 11)->default('0.00')->comment('成本价');
            $table->string('image', 512)->default('')->comment('图片');
            $table->string('bar_code', 32)->default('')->comment('产品规格编码');
            $table->string('product_bar_code', 64)->default('')->comment('产品条码');
            $table->string('unique', 36)->default('')->index()->comment('唯一值');
            $table->comment('产品属性值表');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
