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

use App\Http\Model\WorkExternalContact\WorkMassMessagingTempAttach;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 群发素材附件.
 */
class WorkMassMessagingTempAttachDao extends BaseDao
{
    use ListSearchTrait;

    protected $table = 'work_mass_messaging_temp_attach';

    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            method_exists($this, 'createTable') && $this->createTable();
        }
        return parent::getModel($need);
    }

    protected function setModel()
    {
        return WorkMassMessagingTempAttach::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('uid')->default(0)->index()->comment('创建用户ID');
            $table->unsignedInteger('temp_id')->default(0)->index()->comment('素材ID');
            $table->string('types', 255)->default('')->comment('内容类型');
            $table->string('title', 255)->default('')->comment('标题');
            $table->string('info', 512)->default('')->comment('摘要');
            $table->string('link', 512)->default('')->comment('链接');
            $table->string('app_id', 255)->default('')->comment('小程序AppID');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
            $table->comment('素材附件表');
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
