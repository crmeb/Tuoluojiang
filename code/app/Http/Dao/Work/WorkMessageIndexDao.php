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

namespace App\Http\Dao\Work;

use App\Http\Model\Work\WorkMessageIndex;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @mixin WorkMessageIndex
 */
class WorkMessageIndexDao extends BaseDao
{
    use BatchSearchTrait;

    protected string $tableName = '';

    /**
     * 设置表名.
     * @return $this
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return BaseModel
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getModel(bool $need = true)
    {
        $model = parent::getModel($need);
        if ($this->tableName) {
            if (! Schema::hasTable($this->tableName)) {
                Schema::create($this->tableName, function (Blueprint $table) {
                    $table->id();
                    $table->string('corp_id', 20)->default('')->comment('企业ID');
                    $table->unsignedInteger('index_id')->default(0)->comment('对应type的 ID');
                    $table->tinyInteger('index_type')->default(0)->comment('0=员工，1=客户，2=群聊');
                    $table->timestamps();

                    $table->index(['corp_id', 'index_id', 'index_type'], 'corp_id_index_id_index_type');
                });
            }

            $model = $model->setTable($this->tableName);
        }
        return $model;
    }

    /**
     * 获取今日数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getTodayIndexData()
    {
        // 获取今日数据
        $startTime = date('Y-m-d 00:00:00');
        $endTime   = date('Y-m-d 23:59:59');
        return $this->getModel()->whereBetween('created_at', [$startTime, $endTime])->with(['client' => fn ($q) => $q->select(['id', 'external_userid', 'userid'])])->where('index_type', 1)->get()->toArray();
    }

    protected function setModel()
    {
        return WorkMessageIndex::class;
    }
}
