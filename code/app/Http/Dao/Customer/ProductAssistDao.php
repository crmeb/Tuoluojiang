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

use App\Http\Model\Customer\ProductAssist;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProductAssistDao extends BaseDao
{
    protected $table = 'customer_product_assist';

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

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return ProductAssist::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('product_id')->default(0)->index()->comment('产品ID');
            $table->string('product_name', 256)->default('')->comment('产品名');
            $table->string('sku', 256)->default('')->comment('产品规格');
            $table->string('image', 512)->default('')->comment('产品图片');
            $table->unsignedDecimal('price', 11)->default('0.00')->comment('售价');
            $table->unsignedDecimal('ot_price', 11)->default('0.00')->comment('原价');
            $table->unsignedDecimal('total_price', 11)->default('0.00')->comment('总价');
            $table->unsignedInteger('count')->default(0)->comment('数量');
            $table->unsignedTinyInteger('discount')->default(0)->comment('折扣百分比');
            $table->text('remark')->default('')->comment('备注');
            $table->string('unique', 32)->default('')->comment('商品属性唯一值');
            $table->unsignedInteger('link_id')->default(0)->comment('关联ID');
            $table->unsignedTinyInteger('link_type')->default(1)->comment('关联业务类型:1、 客户；2、合同订单；3、联系人；4、线索；5、商机；6、产品；');
            $table->timestamps();
            $table->comment('产品业务关联表');
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
