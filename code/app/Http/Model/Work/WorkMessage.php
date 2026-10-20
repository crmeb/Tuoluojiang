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

namespace App\Http\Model\Work;

use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\model\TimeDataTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkMessage extends BaseModel
{
    use TimeDataTrait;

    protected $primaryKey = 'id';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = 'work_message_' . date('Ym');

        if (! Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->id();
                $table->string('corp_id', 50)->default('')->comment('企业ID');
                $table->unsignedInteger('seq')->default(0)->comment('消息的seq值，标识消息的序号');
                $table->string('msg_id', 64)->unique()->default('')->comment('消息唯一标识');
                $table->char('action', 10)->default('')->comment('消息动作，0.send(发送消息) 1.recall(撤回消息) 2.switch(切换企业日志)');
                $table->string('from', 255)->default('')->comment('消息发送方id。同一企业内容为userid，非相同企业/机器人为external_userid');
                $table->json('tolist')->comment('消息接收方列表，可能是多个，同一个企业内容为userid，非相同企业为external_userid');
                $table->json('tolist_id')->comment('接收方ID');
                $table->tinyInteger('tolist_type')->default(0)->comment('接收方类型 0通讯录 1外部联系人 2群');
                $table->char('msg_type', 50)->default('')->comment('文本消息类型：text=文本，image=图片，revoke=撤回消息，agree=同意会话内容，voice=语音，video=视屏，card=名片，location=位置等等');
                $table->json('content')->comment('文本内容：详细见wx文档');
                $table->timestamp('msg_time')->nullable()->comment('消息发送时间戳，utc时间，ms单位');
                $table->string('wx_room_id', 255)->default('')->comment('微信群id。如果是单聊则为空');
                $table->integer('room_id')->default(0)->comment('群id');
                $table->tinyInteger('status')->default(0)->comment('关键词打标签查询状态（0：未查询，1：已查询）');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
}
