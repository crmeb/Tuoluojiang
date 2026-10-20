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

use App\Http\Model\Customer\Target;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\TogetherSearchTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 业绩目标Dao.
 */
class TargetDao extends BaseDao
{
    use TogetherSearchTrait;

    protected $table = 'customer_target';

    public function search($where, ?bool $authWhere = null)
    {
        if (isset($where['frame_id']) && $where['frame_id']) {
            $where['link_id'] = $where['frame_id'];
            $where['types']   = 1;
        }
        if (isset($where['user_id']) && $where['user_id']) {
            $where['link_id'] = $where['user_id'];
            $where['types']   = 0;
        }
        unset($where['frame_id'],$where['user_id']);
        return parent::search($where, $authWhere);
    }

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

    public function insert(array $data)
    {
        return $this->getModel(false)->insert($data);
    }

    /**
     * 设置模型.
     * @return string
     */
    protected function setModel()
    {
        return Target::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('id');
            $table->unsignedInteger('uid')->default(0)->index()->comment('用户ID');
            $table->unsignedInteger('link_id')->default(0)->index()->comment('用户/部门ID');
            $table->unsignedDecimal('amount', 11)->default(0)->comment('目标额');
            $table->unsignedInteger('year')->nullable()->comment('年份');
            $table->unsignedInteger('month')->nullable()->comment('月份');
            $table->unsignedInteger('types')->default(0)->comment('类型：0、人员；1、部门；');
            $table->timestamps();
            $table->comment('业绩目标表');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
