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

use App\Http\Model\WorkExternalContact\WorkMassMessagingResult;
use App\Http\Dao\BaseDao;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkMassMessagingResultDao extends BaseDao
{
    protected $table = 'work_mass_messaging_result';

    public function getModel(bool $need = true)
    {
        if (! Schema::hasTable($this->table)) {
            method_exists($this, 'createTable') && $this->createTable();
        }
        return parent::getModel($need);
    }

    protected function setModel()
    {
        return WorkMassMessagingResult::class;
    }

    /**
     * 创建表.
     */
    protected function createTable()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->unsignedInteger('task_id')->default(0)->comment('群发任务ID');
            $table->string('msgid', 255)->nullable()->comment('群发消息ID');
            $table->unsignedInteger('uid')->default(0)->index()->comment('用户ID');
            $table->string('chat_id', 32)->default('')->comment('客户群ID');
            $table->string('external_userid', 32)->default('')->comment('客户ID');
            $table->string('userid', 32)->default('')->comment('员工ID');
            $table->unsignedInteger('is_comment')->default(0)->comment('是否评论');
            $table->unsignedInteger('is_like')->default(0)->comment('是否点赞');
            $table->unsignedInteger('status')->default(1)->comment('发送状态：0-未发送 1-已发送 2-因客户不是好友导致发送失败 3-因客户已经收到其他群发消息导致发送失败');
            $table->timestamp('send_time')->nullable()->comment('发送时间');
            $table->timestamps();
            $table->comment('企微消息群发结果表');
            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8';
            $table->collation = 'utf8_unicode_ci';
        });
    }
}
