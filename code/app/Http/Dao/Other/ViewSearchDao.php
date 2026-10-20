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

namespace App\Http\Dao\Other;

use App\Http\Model\Other\ViewSearch;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 辅助Dao
 * Class AssistDao.
 */
class ViewSearchDao extends BaseDao
{
    use ListSearchTrait;

    protected $table = 'view_search';

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

    public function search($where, ?bool $authWhere = null)
    {
        $uid = 0;
        if (isset($where['uid'])) {
            $uid = $where['uid'];
            unset($where['uid']);
        }
        return parent::search($where, $authWhere)->when($uid, function (Builder $query) use ($uid) {
            $query->where(function ($q) use ($uid) {
                $q->where('uid', $uid)->orWhere('is_public', 1)->orWhere('types', 0);
            });
        });
    }

    /**
     * 设置模型.
     */
    protected function setModel(): string
    {
        return ViewSearch::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('id');
            $table->unsignedInteger('uid')->default(0)->index()->comment('关联用户ID');
            $table->string('title', 255)->default('')->comment('视图名称');
            $table->text('content')->default('')->comment('视图内容');
            $table->string('category', 32)->default('')->comment('视图分类(参考枚举类目)');
            $table->unsignedInteger('types')->default(0)->index()->comment('视图类型：0-系统 1-个人');
            $table->unsignedTinyInteger('is_public')->default(0)->comment('是否公开：0-否 1-是');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();

            $table->comment('视图搜索表');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
