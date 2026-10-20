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

namespace App\Http\Service\Ai\Agent\Intent;

/**
 * 写续跑窄信号：仅服务会话写失败重试 / 历史回溯，不参与工具编排决策.
 */
final class NarrowIntentSignals
{
    /**
     * 写失败后再办口语（可无业务对象，供会话回溯）.
     */
    public static function isWriteRetry(string $question): bool
    {
        $question = trim($question);
        if ($question === '') {
            return false;
        }

        if (preg_match(
            '/(没有成功|未成功|没成功|不成功|失败了?|没(有)?生成|没(有)?创建).{0,16}(重新|再次|再)?(生成|创建|提交|办理|试)/u',
            $question
        )) {
            return true;
        }

        return (bool) preg_match(
            '/^(重新生成|再生成|再次生成|再创建|重新创建|再提交|重试一下|再试一次|再办一次)[\s!！。.?？]*$/u',
            $question
        ) || (bool) preg_match(
            '/(重新|再次|再)(生成|创建|提交|办理).{0,8}(客户|联系人|线索|商机|订单|合同|回款|续费|支出|发票|开票|跟进|日报|周报|月报|汇报|日程|会议|绩效|自评)/u',
            $question
        );
    }

    /**
     * 写操作口语（供写缺参/重试时回溯历史上轮原题，不做编排）.
     */
    public static function isWriteLike(string $question): bool
    {
        return self::isWriteFloor($question)
            || self::isWriteRetry($question)
            || self::isMutate($question);
    }

    /**
     * 高精确写操作底线：强办理动词 + 明确业务对象.
     */
    public static function isWriteFloor(string $question): bool
    {
        $question = trim($question);
        if ($question === '') {
            return false;
        }

        if (self::isWriteRetry($question) && preg_match(
            '/(客户|联系人|线索|商机|订单|合同|回款|续费|支出|发票|开票|跟进|日报|周报|月报|汇报|日程|会议|绩效|自评|请假|审批)/u',
            $question
        )) {
            return true;
        }

        if (preg_match(
            '/(创建|新建|新增|添加|录入|登记|提交|发起|申请|写入|保存).{0,10}(客户|联系人|线索|商机|订单|合同|回款|续费|支出|发票|开票|跟进|日报|周报|月报|汇报|日程|会议|绩效|自评)/u',
            $question
        )) {
            return true;
        }

        if (preg_match('/(?<!已经)(?<!已)生成.{0,4}(客户|联系人|线索|商机|订单|合同)/u', $question)
            && ! preg_match('/生成(图表|内容|报告|总结|摘要|文案)|列表|查询|没有找到|找不到|未找到/u', $question)
        ) {
            return true;
        }

        if (preg_match('/(帮我|我要|请帮).{0,8}(请假|调休|加班|出差|补卡)/u', $question)) {
            return true;
        }

        return (bool) preg_match(
            '/(提醒我|别忘了|记一下|帮我记|设个提醒).{0,20}\d/u',
            $question
        );
    }

    /** 明确变更既有数据（修改/删除），区别于新建. */
    private static function isMutate(string $question): bool
    {
        $question = trim($question);
        if ($question === '') {
            return false;
        }

        if (preg_match('/怎么|如何|怎样|在哪|是否支持|支持吗|是什么|什么是/u', $question)
            && ! preg_match('/帮我|请帮|替我|为我/u', $question)
        ) {
            return false;
        }

        return (bool) preg_match(
            '/(改成|改为|改名|改掉|重命名|删除|删掉|移除|作废|撤销|撤回).{0,12}(客户|联系人|线索|商机|订单|合同|日程|会议|审批|日报|周报|月报|汇报|跟进)/u',
            $question
        ) || (bool) preg_match(
            '/(帮我|请帮|替我).{0,8}(修改|更改|更新|编辑|删除|删掉).{0,12}(客户|联系人|线索|商机|订单|合同|日程|会议)/u',
            $question
        ) || (bool) preg_match(
            '/(日程|会议|日报|周报|月报|汇报|跟进|备注|标题).{0,16}(改成|改为|改掉|修改)/u',
            $question
        ) || (bool) preg_match(
            '/(把|将).{0,24}(改成|改为)/u',
            $question
        );
    }
}
