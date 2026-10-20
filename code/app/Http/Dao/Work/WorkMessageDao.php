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

use App\Http\Model\Work\WorkMessage;
use App\Http\Dao\BaseDao;
use App\Http\Model\BaseModel;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @mixin WorkMessage
 */
class WorkMessageDao extends BaseDao
{
    use BatchSearchTrait;

    /**
     * @var null|string
     */
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

            $model = $model->setTable($this->tableName);
        }
        return $model;
    }

    /**
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getClientMessage(string $externalUserid, string $userid)
    {
        $startTime = date('Y-m-d 00:00:00');
        $endTime   = date('Y-m-d 23:59:59');
        return $this->getModel()->where(function ($query) use ($externalUserid, $userid) {
            $query->where(function ($query) use ($externalUserid, $userid) {
                $query->whereJsonContains('tolist', $userid)
                    ->where('from', $externalUserid);
            })->orWhere(function ($query) use ($externalUserid, $userid) {
                $query->whereJsonContains('tolist', $externalUserid)
                    ->where('from', $userid);
            });
        })
            ->where('msg_type', 'text')
            ->whereBetween('msg_time', [$startTime, $endTime])
            ->limit(10)
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    /**
     * @return string
     */
    protected function setModel()
    {
        return WorkMessage::class;
    }
}
