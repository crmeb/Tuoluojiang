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
use App\Http\Service\Customer\CustomerTrait;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 产品dao.
 */
class ProductDao extends BaseDao
{
    use CustomerTrait;

    protected $table = 'customer_product';

    private $otherSearch = [
        'types',
        'scope_frame',
    ];

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
     * 列表筛选数据.
     * @param mixed $where
     * @param mixed $page
     * @param mixed $limit
     * @param mixed $with
     * @param mixed $sort
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function listSearch(array $where, int $page = 0, int $limit = 0, array $with = [], int $uid = 0, array|string $sort = ['sort', 'id'])
    {
        $dao   = $this->getModel();
        $where = $this->getWhere($where);
        foreach ($where as $field => $value) {
            if ($value === '') {
                continue;
            }
            if (in_array($field, $this->otherSearch)) {
                unset($where[$field]);
            } elseif ($field == 'contract_category') {
                $value['value'] && $dao = $dao->whereIn('contract_category', $value['value']);
            } elseif ($field == 'product_type') {
                $value !== '' && $dao = $dao->where('types', $value);
            } elseif ($field == 'created_at') {
                $value !== '' && $dao = $dao->time($value);
            } elseif (is_array($value)) {
                if (isset($value['input_type'])) {
                    $dao = match ($value['input_type']) {
                        'select' => $this->getMoreSelectSearch($dao, $field, $value['value'], $value['type']),
                        'radio' => $this->getSelectSearch($dao, $field, $value['value']),
                        'checked' => $this->getMemberSearch($dao, $field, $value['value']),
                        'input' => $this->getInputSearch($dao, $field, $value['value']),
                        'date', 'datetime' => $this->getDateSearch($dao, $field, $value['value']),
                        'personnel' => $this->getPersonnelSearch($dao, $field, $value['value']),
                        'member'    => $this->getMemberSearch($dao, $field, $value['value']),
                        default     => $dao->where($field, $value['value']),
                    };
                } else {
                    $dao = $dao->whereIn($field, $value);
                }
            } else {
                $dao = $dao->where($field, $value);
            }
        }
        return $dao->when($page && $limit, function ($query) use ($page, $limit) {
            $query->forPage($page, $limit);
        })->when($sort = sort_mode($sort), function ($query) use ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($sort);
            }
        })->with($with);
    }

    public function searchCreator($dao, $value)
    {
        return $value ? (is_array($value) ? $dao->whereIn('uid', $value) : $dao->where('uid', $value)) : $dao;
    }

    public function searchPath($dao, $value)
    {
        if (! is_array($value)) {
            return $dao->where('pid', $value);
        }

        $value = collect($value)->map(function ($item) {
            while (is_array($item)) {
                $item = end($item);
            }
            return $item;
        })->filter(fn ($item) => $item !== '' && $item !== null)->unique()->values()->all();

        return $value ? $dao->whereIntegerInRaw('pid', $value) : $dao->whereRaw('0 = 1');
    }

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return Product::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('id');
            $table->unsignedInteger('uid')->default(0)->index()->comment('用户ID');
            $table->string('name', 255)->default('')->index()->comment('产品名称');
            $table->unsignedInteger('pid')->default(0)->comment('产品分类');
            $table->json('path')->nullable()->comment('产品分类路径');
            $table->string('unit_name', 32)->default('')->comment('单位名');
            $table->unsignedInteger('types')->default(2)->comment('产品类型');
            $table->string('number')->index()->default('')->comment('产品编号');
            $table->text('description')->default('')->comment('产品描述');
            $table->unsignedTinyInteger('spec_type')->default(0)->comment('产品规格：0、单规格；1、多规格；');
            $table->unsignedTinyInteger('is_show')->default(1)->comment('产品状态：0、下架；1、上架；');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('产品表');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
