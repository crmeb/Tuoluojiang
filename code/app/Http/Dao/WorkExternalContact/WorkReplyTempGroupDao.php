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

namespace App\Http\Dao\WorkExternalContact;

use App\Http\Model\WorkExternalContact\WorkReplyTempGroup;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkReplyTempGroupDao extends BaseDao
{
    use ListSearchTrait;

    protected $table = 'work_reply_temp_group';

    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            method_exists($this, 'createTable') && $this->createTable();
        }
        return parent::getModel($need);
    }

    protected function setModel()
    {
        return WorkReplyTempGroup::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('pid')->default(0)->index()->comment('父级分组ID');
            $table->unsignedInteger('uid')->default(0)->index()->comment('创建用户ID');
            $table->string('name', 255)->default('')->comment('分组名称');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->comment('快捷回复分组表');
            $table->timestamps();
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
