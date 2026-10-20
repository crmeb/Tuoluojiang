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

use App\Http\Model\WorkExternalContact\WorkMassMessagingTask;
use App\Http\Dao\BaseDao;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkMassMessagingTaskDao extends BaseDao
{
    protected $table = 'work_mass_messaging_task';

    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            method_exists($this, 'createTable') && $this->createTable();
        }
        return parent::getModel($need);
    }

    protected function setModel()
    {
        return WorkMassMessagingTask::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('mass_id')->default(0)->comment('群发任务ID');
            $table->unsignedInteger('uid')->default(0)->index()->comment('用户ID');
            $table->string('userid', 256)->default('')->comment('员工ID');
            $table->string('msgid', 256)->default('')->comment('群发消息id');
            $table->string('moment_id', 256)->default('')->comment('朋友圈id');
            $table->string('jobid', 256)->default('')->comment('朋友圈任务id');
            $table->unsignedInteger('status')->default(0)->comment('发送状态：0-未发送 2-已发送');
            $table->unsignedInteger('sum_count')->default(0)->comment('发送人数');
            $table->unsignedInteger('not_send_count')->default(0)->comment('未发送人数');
            $table->unsignedInteger('success_count')->default(0)->comment('成功人数');
            $table->unsignedInteger('fail_count')->default(0)->comment('失败人数');
            $table->unsignedTinyInteger('types')->default(0)->comment('类型:1、群聊消息,0、群发消息,2、朋友圈消息;');
            $table->json('fail_list')->nullable()->comment('无效或无法发送的external_userid或chatid列表');
            $table->timestamp('send_time')->nullable()->comment('发送时间');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('企微群发成员发送任务表');
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
