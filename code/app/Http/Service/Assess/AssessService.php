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

namespace App\Http\Service\Assess;

use App\Constants\AssessEnum;
use App\Constants\CacheEnum;
use App\Constants\NoticeEnum;
use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use App\Events\SystemMessageEvent;
use App\Http\Contract\Assess\AssessInterface;
use App\Http\Dao\Company\CompanyUserAssessDao;
use App\Http\Service\Admin\AdminInfoService;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\Other\TaskService;
use App\Task\message\StatusChangeTask;
use App\Task\message\UserAssessAppealRemind;
use App\Task\message\UserAssessAppealResRemind;
use App\Task\message\UserAssessEndRemind;
use App\Task\message\UserAssessReleaseRemind;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\options\TaskOptions;
use Tuoluojiang\Open\services\FormService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 绩效考核记录.
 */
class AssessService extends BaseService implements AssessInterface
{
    public function __construct(CompanyUserAssessDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取绩效列表.
     *
     * 筛选约定：
     * - type=1 考核记录：数据权限可见 ∪ 考核人为登录人
     * - type=1 待处理：上级评价状态且考核人为登录人
     * - type=2 待处理：考核审批人为登录人且状态为绩效审核
     * - type=2 考核记录：数据权限可见 ∪ 考核审批人为登录人
     * - filter_test_uid：用户选择的被考核人，始终与上述可见范围取交集
     *
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAssessList(int $uid, array $where = [], int $types = 0)
    {
        $entId        = 1;
        $frameService = app()->get(FrameService::class);
        $isPending    = (int) ($where['handle'] ?? 0) === 1;
        $scopeTestUid = array_values(array_filter(array_map('intval', (array) ($where['test_uid'] ?? []))));
        switch ($types) {
            case 0:// 我的自评
                $where['test_uid'] = [$uid];
                $where['is_show']  = 1;
                break;
            case 1:// 部门考核
                if ($isPending) {
                    // 待处理：上级评价状态 + 考核人为登录人（状态由 handle+type 在 Dao 中限定）
                    unset($where['test_uid']);
                    $where['check_uid'] = $uid;
                } else {
                    // 考核记录：数据权限可见 OR 考核人为登录人
                    $where['test_uid']     = $scopeTestUid;
                    $where['or_check_uid'] = $uid;
                }
                break;
            case 2:// 绩效审核
                // 考核审批人 = 被考核人二级上级，对应登录人二级下级
                $verifyTestUids = array_values(array_filter(array_map('intval', (array) $frameService->getLevelSub($uid, 2))));
                if ($isPending) {
                    // 待处理：考核审批人为登录人 + 状态绩效审核
                    $where['test_uid'] = $verifyTestUids ?: [0];
                } else {
                    // 考核记录：数据权限可见 OR 考核审批人为登录人
                    $where['test_uid'] = array_values(array_unique(array_merge($scopeTestUid, $verifyTestUids))) ?: [0];
                }
                break;
            default:
                $types = '';
        }
        $where['type']   = $types;
        $where['handle'] = $isPending ? 1 : '';
        [$page, $limit]  = $this->getPageValue();
        $scoreList      = app()->get(AssessScoreService::class)->getScoreList($entId);
        $data           = $this->dao->getUserAssessMode($where, ['*'], $page, $limit, 'id', ['test', 'check', 'frame'])->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = $scoreList[$item['grade']] ?? '无';
            } else {
                $item['level'] = '--';
                $item['score'] = '--';
            }
            $item['verify'] = $this->dao->getVerifyInfo($uid);
        }
        unset($item);
        $count = $this->dao->getUserAssessMode($where)->count();

        return $this->listData($data, $count);
    }

    /**
     * TODO 人事获取考核列表.
     *
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function assessList(array $where)
    {
        $scoreList      = app()->get(AssessScoreService::class)->getScoreList($where['entid']);
        [$page, $limit] = $this->getPageValue();
        $data           = $this->dao->getUserAssessMode($where, ['*'], $page, $limit, ['start_time', 'id'], [
            'frame', 'test', 'check',
        ])->get()->toArray();
        foreach ($data as &$item) {
            if (datetime_timestamp($item['start_time']) > time()) {
                $item['status'] = 5;
            }
            if ($item['status'] > 2) {
                $item['level'] = $scoreList[$item['grade']] ?? '无';
            } else {
                $item['level'] = '--';
                $item['score'] = '--';
            }
            $item['verify'] = $this->dao->getVerifyInfo($item['test_uid']);
        }
        unset($item);
        $count = $this->dao->getUserAssessMode($where)->count();
        return $this->listData($data, $count);
    }

    /**
     * 获取绩效详情.
     * @param mixed $id
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAssessInfo($id)
    {
        $assessInfo = $this->dao->get($id, ['*'], [
            'frame', 'check' => fn ($q) => $q->with('job:id,name'), 'test' => fn ($q) => $q->with('job:id,name'),
        ])?->toArray();
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        if (datetime_timestamp($assessInfo['start_time']) > time()) {
            $assessInfo['status'] = 5;
        }
        if ($assessInfo['status'] > 2) {
            $assessInfo['level'] = app()->get(AssessScoreService::class)->value(['level' => $assessInfo['grade']], 'name') ?? '无';
        } else {
            $assessInfo['level'] = '暂无';
            $assessInfo['score'] = '未评分';
        }
        $assessInfo['verify'] = $this->dao->getVerifyInfo($assessInfo['test_uid']);
        $assessInfo['time']   = $this->strToTime($assessInfo['start_time'], (int) $assessInfo['period']);
        $info                 = app()->get(AssessSpaceService::class)->getAssess($id)?->toArray();
        if (! $info) {
            throw $this->exception('考核内容不存在');
        }
        $explain = sys_config('assess_score_mark', '');
        return compact('assessInfo', 'info', 'explain');
    }

    /**
     * 获取绩效其他信息.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAssessExplain(int $id, int $uid)
    {
        $assessInfo = $this->dao->get($id);
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        $assessInfo   = $assessInfo->toArray();
        $replyService = app()->get(AssessReplyService::class);
        $field        = ['user_id', 'content', 'types', 'status', 'updated_at'];
        $reply        = $replyService->dao->setDefaultSort('id')->select(['assessid' => $assessInfo['id'], 'is_own' => 0], $field, ['user']) ?? [];
        if ($assessInfo['check_uid'] == $uid) {
            $mark = $replyService->get(['assessid' => $assessInfo['id'], 'is_own' => 1], $field, ['user']) ?? [];
        } else {
            $mark = [];
        }
        $explain = sys_config('assess_score_mark', '');
        $appeal  = $replyService->get(['assessid' => $assessInfo['id'], 'is_own' => 0, 'types' => 1, 'status' => 0], $field, ['user'], 'id') ?? [];

        return compact('reply', 'explain', 'appeal', 'mark');
    }

    /**
     * 创建绩效.
     * @return mixed|true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function createAssess(int $uid, array $data, int $entId = 1)
    {
        $isDraft      = (bool) $data['is_draft']; // 草稿
        $isTemp       = $data['is_temp']; // 是否为模板
        $info         = $data['info']; // 模板简介
        $name         = $data['name']; // 模板名称
        $testUid      = $data['test_uid']; // 被考核人
        $period       = $data['period']; // 周期
        $time         = $data['time']; // 考核时间
        $types        = $data['types']; // 记分方式
        $data         = $data['data']; // 考核目标
        $spaceService = app()->get(AssessSpaceService::class);
        if ($isTemp) {
            if (! $name) {
                throw $this->exception('请填写模板名称');
            }
            return $this->transaction(function () use ($uid, $name, $info, $types, $data, $isTemp, $spaceService) {
                $tempInfo = app()->get(AssessTemplateService::class)->create([
                    'entid'   => 1,
                    'user_id' => $uid,
                    'name'    => $name,
                    'info'    => $info,
                    'types'   => $types,
                ]);
                return $spaceService->saveAssess($tempInfo->id, $data, 1, $isTemp, false);
            });
        } else {
            if (! $testUid) {
                throw $this->exception('保存失败，请选择被考核人');
            }
            $assistService = app()->get(FrameAssistService::class);
            $uids          = $assistService->getSubUid($uid);
            if (array_diff($testUid, $uids)) {
                throw $this->exception('保存失败，被考核人必须为下级用户');
            }
            $planInfo    = app()->get(AssessPlanService::class)->get(['entid' => $entId, 'period' => $period]);
            $companyUser = app()->get(AdminService::class);
            foreach ($testUid as $v) {
                $save['test_uid']  = $v;
                $save['check_uid'] = $uid;
                $save['entid']     = $entId;
                $save['period']    = $period;
                $save['types']     = $types;
                $save['frame_id']  = $assistService->value(['user_id' => $v, 'is_mastart' => 1], 'frame_id');
                $timeInfo          = $this->getTimes($period, $time, $planInfo);
                if ($this->dao->exists([
                    'test_uid' => $v,
                    'entid'    => $entId,
                    'period'   => $period,
                    'name'     => $timeInfo['name'],
                ])) {
                    throw $this->exception('【' . $companyUser->value($v, 'name') . '】' . $timeInfo['name'] . '的考核记录已存在，无法重复添加！');
                }
                $save['name']        = $timeInfo['name'];
                $save['start_time']  = $timeInfo['start_time'];
                $save['end_time']    = $timeInfo['end_time'];
                $save['make_time']   = $timeInfo['make_time'];
                $save['check_end']   = $timeInfo['check_end'];
                $save['verify_time'] = $timeInfo['verify_time'];
                $other               = $this->verifyAssess($isDraft, $data);
                $save['intact']      = $other['intact'];
                $save['status']      = $other['status'];
                $save['total']       = $other['total'];
                $res                 = $this->transaction(function () use ($save, $data, $entId, $isDraft, $spaceService) {
                    $assessInfo = $this->dao->create($save);
                    $res        = $spaceService->saveAssess($assessInfo->id, $data, $entId, 0, $isDraft);
                    if (! $res) {
                        throw $this->exception('保存失败');
                    }
                    return $assessInfo && $res;
                });
                // 清除缓存
                $res && Cache::tags([CacheEnum::TAG_FRAME])->flush();
                unset($save, $assessInfo);
            }
            return true;
        }
    }

    /**
     * 修改绩效(未启用时).
     * @return bool|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setAssess(int $id, array $data, int $entId, bool $isSubmit = false, string $time = '')
    {
        $saved = app()->get(AssessSpaceService::class)->updateSuperAssess($id, $data, $entId);
        if (! $saved) {
            throw $this->exception('绩效内容修改失败');
        }
        $info = $this->dao->get($id);
        if ($time) {
            $planInfo = app()->get(AssessPlanService::class)->get(['period' => $info->period]);
            $timeInfo = $this->getTimes($info->period, $time, $planInfo);
            if ($info->name = $timeInfo['name']) {
                $info->name        = $timeInfo['name'];
                $info->start_time  = $timeInfo['start_time'];
                $info->end_time    = $timeInfo['end_time'];
                $info->make_time   = $timeInfo['make_time'];
                $info->check_end   = $timeInfo['check_end'];
                $info->verify_time = $timeInfo['verify_time'];
                $info->save();
            }
        }
        if ($isSubmit) {
            $info->is_show = 1;
            $res1          = $info->save();
            $info          = $info->toArray();
            // 考核目标发布提醒
            Task::deliver(new UserAssessReleaseRemind($entId, $info['test_uid'], $info));
            $res2 = $this->openAssessTask($info);
            Cache::tags([CacheEnum::TAG_FRAME])->flush();
            return $res1 && $res2;
        }
    }

    /**
     * 自评.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setSelfAssess(int $id, array $data, bool $isSubmit = false, string $selfReply = ''): void
    {
        app()->get(AssessSpaceService::class)->updateSelfAssess($data);
        $status               = $this->dao->value($id, 'status');
        $update['self_reply'] = $selfReply;
        if ($isSubmit) {
            if ($status < AssessEnum::ASSESS_EVALUATION) {
                $update['status'] = 2;
            }
            $update['test_status'] = 1;
        } else {
            $update['test_status'] = 2;
        }
        $this->dao->update($id, $update);
        $this->syncAssessTodo($id);
    }

    /**
     * 上级评价.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setSuperiorAssess(int $id, int $uid, array $data, int $entId, bool $isSubmit = false, string $reply = '', string $hideReply = ''): void
    {
        app()->get(AssessSpaceService::class)->updateSuperAssess($id, $data, $entId);
        $info = toArray($this->dao->get($id));
        if (! $info) {
            throw $this->exception('未找到指定考核记录');
        }
        $update = [
            'reply'      => $reply,
            'hide_reply' => $hideReply,
        ];
        if ($isSubmit) {
            $scoreService = app()->get(AssessScoreService::class);
            $score        = $scoreService->getScoreFromId($id);
            $grade        = $scoreService->getScoreLevel($entId, $score);
            if ($info['status'] < AssessEnum::ASSESS_VERIFY) {
                $update = array_merge($update, [
                    'score'        => $score,
                    'grade'        => $grade,
                    'status'       => 3,
                    'check_status' => 1,
                ]);
            } else {
                $update = array_merge($update, [
                    'score'        => $score,
                    'grade'        => $grade,
                    'check_status' => 1,
                ]);
            }
            app()->get(UserAssessScoreService::class)->create([
                'entid'     => $entId,
                'assessid'  => $id,
                'userid'    => $uid,
                'test_uid'  => $info['test_uid'],
                'check_uid' => $info['check_uid'],
                'score'     => $score,
                'grade'     => $grade,
                'total'     => $info['total'],
                'types'     => 0,
            ]);
            event(new SystemMessageEvent(
                type: NoticeEnum::ASSESS_EVALUATE_RESULT_TYPE,
                params: [
                    '考核名称'     => $info['name'],
                    '上级评价得分' => $score,
                ],
                receiverIds: (int) $info['test_uid'],
                other: ['id' => $id],
                linkId: $id,
                linkStatus: $info['status']
            ));
            Task::deliver(new StatusChangeTask(AssessEnum::LINK_NOTICE, AssessEnum::ASSESS_VERIFY, 1, $id));
        } else {
            $update['check_status'] = 2;
        }
        $this->dao->update($id, $update);
        $this->syncAssessTodo($id);
    }

    /**
     * 审核.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setExamineAssess(int $id, int $uid, array $data, int $entId, bool $isSubmit = false): void
    {
        app()->get(AssessSpaceService::class)->updateSuperAssess($id, $data, $entId);
        $info = toArray($this->dao->get($id));
        if (! $info) {
            throw $this->exception('未找到指定考核记录');
        }
        if ($isSubmit) {
            $scoreService = app()->get(AssessScoreService::class);
            $score        = $scoreService->getScoreFromId($id);
            $grade        = $scoreService->getScoreLevel($entId, $score);
            if ($info['status'] < AssessEnum::ASSESS_FINISH) {
                $this->dao->update($id, [
                    'score'         => $score,
                    'grade'         => $grade,
                    'status'        => 4,
                    'verify_status' => 1,
                ]);
            } else {
                $this->dao->update($id, [
                    'score'         => $score,
                    'grade'         => $grade,
                    'verify_status' => 1,
                ]);
            }
            app()->get(UserAssessScoreService::class)->create([
                'entid'     => $entId,
                'assessid'  => $id,
                'userid'    => $uid,
                'test_uid'  => $info['test_uid'],
                'check_uid' => $info['check_uid'],
                'score'     => $score,
                'grade'     => $grade,
                'total'     => $info['total'],
                'types'     => 0,
            ]);
            Task::deliver(new UserAssessEndRemind($info['entid'], $info['test_uid'], $id));
        } else {
            $this->dao->update($id, ['verify_status' => 2]);
        }
        $this->syncAssessTodo($id);
    }

    /**
     * 获取考核分数统计
     * @param mixed $where
     * @param mixed $uid
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAssessCensusLine($where, $uid)
    {
        $uids = [];
        switch ($where['types']) {
            case 2:// 人事
                $uids = array_values(array_filter(array_map('intval', (array) ($where['uid'] ?? []))));
                unset($where['frame_id']);
                break;
            case 1:// 下级
                $uids = array_values(array_filter(array_map('intval', (array) ($where['uid'] ?? []))));
                unset($where['frame_id']);
                break;
            default:// 自己
                $uids = [(int) $uid];
        }
        unset($where['uid']);
        $where['time'] && is_string($where['time']) && [$where['start'], $where['end']] = explode('-', $where['time']);
        if (strlen($where['end']) == 4) {
            $start = $startTime = Carbon::parse($where['start'] . '0101')->timezone(config('app.timezone'))->startOfYear()->timestamp;
            $end   = $endTime = Carbon::parse($where['end'] . '0101')->timezone(config('app.timezone'))->addYear()->startOfYear()->timestamp;
        } else {
            $start = $startTime = Carbon::parse($where['start'])->timezone(config('app.timezone'))->timestamp;
            $end   = $endTime = Carbon::parse($where['end'])->timezone(config('app.timezone'))->timestamp;
        }
        $dayCount = ($endTime - $startTime) / 86400 + 1;
        $timeType = '%H';
        $xAxis = [];
        switch ($where['period']) {
            case 1:// 周考核
                $num = $dayCount / 7;
                if (! Carbon::parse($startTime)->timezone(config('app.timezone'))->isMonday()) {
                    $start = $startTime = Carbon::parse($startTime)->timezone(config('app.timezone'))->addWeek()->timestamp;
                    $end   = $endTime = Carbon::parse($endTime)->timezone(config('app.timezone'))->addWeek()->timestamp;
                }
                while ($startTime < $endTime) {
                    if ($num <= 53) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年') . '第' . Carbon::parse($startTime)->timezone(config('app.timezone'))->weekOfYear . '周';
                        $startTime = strtotime('+1 week', $startTime);
                        $timeType  = '%Y-%u';
                    } else {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年') . '第' . Carbon::parse($startTime)->timezone(config('app.timezone'))->weekOfYear . '周';
                        $startTime = strtotime('+3 week', $startTime);
                        $timeType  = '%Y-%u';
                    }
                }
                $group = 'weeks';
                break;
            case 2:// 月考核
                $num = $dayCount / 30;
                while ($startTime < $endTime) {
                    if ($num <= 53) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年m月');
                        $startTime = strtotime('+1 month', $startTime);
                        $timeType  = '%Y-%m';
                    } else {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年m月');
                        $startTime = strtotime('+2 month', $startTime);
                        $timeType  = '%Y-%m';
                    }
                }
                $group = 'month';
                break;
            case 3:// 年考核
                while ($startTime < $endTime) {
                    $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年');
                    $startTime = strtotime('+1 year', $startTime);
                    $timeType  = '%Y';
                }
                $group = 'year';
                break;
            case 4:// 半年考核
                $num = $dayCount / 90;
                while ($startTime < $endTime) {
                    if ($num <= 24) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y') . '年' . (Carbon::parse($startTime)->timezone(config('app.timezone'))->format('m') > 6 ? '下半年' : '上半年');
                        $startTime = Carbon::parse($startTime)->addMonths(6)->timestamp;
                        $timeType  = '%Y-%m';
                    } else {
                        $xAxis[]   = date('Y-m', $startTime);
                        $startTime = strtotime('+2 month', $startTime);
                        $timeType  = '%Y-%m';
                    }
                }
                $group = 'month';
                break;
            case 5:// 季度考核
                while ($startTime < $endTime) {
                    $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y') . '年第' . Carbon::parse($startTime)->timezone(config('app.timezone'))->quarter . '季度';
                    $startTime = Carbon::parse($startTime)->addQuarter()->timestamp;
                    $timeType  = '%Y-%m';
                }
                $group = 'quarter';
                break;
            default:
                $num = $dayCount / 30;
                while ($startTime < $endTime) {
                    if ($num <= 53) {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年m月');
                        $startTime = strtotime('+1 month', $startTime);
                        $timeType  = '%Y-%m';
                    } else {
                        $xAxis[]   = Carbon::parse($startTime)->timezone(config('app.timezone'))->format('Y年m月');
                        $startTime = strtotime('+2 month', $startTime);
                        $timeType  = '%Y-%m';
                    }
                }
                $group = 'month';
        }
        $data = $series = $income = [];
        if ($uids) {
            foreach ($uids as $v) {
                $income[$v] = [];
                $startDate  = Carbon::parse($start)->timezone(config('app.timezone'))->toDateTimeString();
                $endDate    = Carbon::parse($end)->timezone(config('app.timezone'))->toDateTimeString();
                $info       = $this->dao->getTrend([$startDate, $endDate], $timeType, 'score', ['test_uid' => $v, 'period' => $where['period']], $group);
                switch ($where['period']) {
                    case 5:
                    case 4:
                        foreach ($info as $inf) {
                            $key        = $this->getCensusKey($inf[$group], $where['period']);
                            $income[$v] = [$key => $inf['grade']];
                        }
                        break;
                    default:
                        $income[$v] = array_column($info, 'grade', $group);
                }
            }
        }
        foreach ($xAxis as $item) {
            $item = rtrim(str_replace(['年', '月', '周', '日', '季度', '年下半年', '年上半年', '年第'], '-', $item), '-');
            foreach ($income as $key => $v) {
                $data[$key][] = isset($v[$item]) ? floatval($v[$item]) : 0;
            }
        }
        $userService = app()->get(AdminService::class);
        foreach ($data as $key => $value) {
            $series[] = [
                'name'   => $userService->value($key, 'name'),
                'data'   => $value,
                'type'   => 'line',
                'smooth' => 'true',
            ];
        }
        return compact('series', 'xAxis');
    }

    /**
     * 获取考核分数统计
     *
     * @param mixed $where
     * @param mixed $uid
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getAssessStatistics(array $where, int $uid)
    {
        $testUid = [];
        if ($where['test_uid']) {
            $testUid = array_filter(array_map('intval', $where['test_uid']));
        }

        if ($testUid) {
            $where['test_uid'] = $testUid;
        } else {
            $where['test_uid'] = app()->get(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id');
        }
        $score = toArray(app()->get(AssessScoreService::class)->select(['entid' => 1], ['min', 'max', 'name', 'level']));
        switch ($where['types']) {
            case 2:
                break;
            case 1:
                $where['check_uid'] = $uid;
                break;
            default:
                $where['test_uid'] = $uid;
                unset($where['frame_id']);
        }
        unset($where['types']);
        $count = 0;
        if (is_array($where['time'])) {
            $where['time'] = implode('-', $where['time']);
        }
        foreach ($score as &$v) {
            $v['count'] = $this->dao->setTimeField('start_time')->count($where + ['grade' => $v['level']]);
            $count      = bcadd((string) $count, (string) $v['count']);
        }
        unset($where['frame_id']);
        $frame_ids = $this->dao->column($where, 'frame_id');
        $frame     = app()->get(FrameService::class)->select(['ids' => $frame_ids], ['id', 'name'], ['user']);
        return compact('score', 'frame', 'count');
    }

    /**
     * 获取统计字段名.
     * @param mixed $date
     * @param mixed $period
     * @return string
     */
    public function getCensusKey($date, $period)
    {
        [$year, $month] = explode('-', $date);
        if ($period == 5) {
            return match ((int) $month) {
                1, 2, 3 => $year . '年第1季度',
                4, 5, 6 => $year . '年第2季度',
                7, 8, 9 => $year . '年第3季度',
                default => $year . '年第4季度',
            };
        }
        return match ((int) $month) {
            1, 2, 3, 4, 5, 6 => $year . '年上半年',
            default => $year . '年下半年',
        };
    }

    /**
     * 启用绩效.
     * @param mixed $id
     * @param mixed $status
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function showAssess($id, $status)
    {
        $info = toArray($this->dao->get($id));
        if (! in_array($status, [0, 1])) {
            throw $this->exception('无效的状态！');
        }
        if (! $info) {
            throw $this->exception('未找到考核记录！');
        }
        if ($info['status'] > 1) {
            throw $this->exception('考核已完成自评，无法修改状态！');
        }
        if (! $info['intact'] && $status) {
            throw $this->exception('考核已启失败，请按规范完善内容！');
        }
        $entId = 1;

        return $this->transaction(function () use ($id, $info, $entId, $status) {
            $res1 = $this->dao->update($id, ['is_show' => $status, 'status' => $status, 'make_status' => $status]);
            $res1 && $this->syncAssessTodo((int) $id);
            if ($status) {
                // 考核目标发布提醒
                Task::deliver(new UserAssessReleaseRemind($entId, $info['test_uid'], $info));
                $res2 = $this->openAssessTask($info);
                Cache::tags([CacheEnum::TAG_FRAME])->flush();
                return $res1 && $res2;
            }
            $uniqued = md5($entId . $id . 'start');
            app()->get(TaskService::class)->delete(['uniqued' => $uniqued]);
            Cache::tags([CacheEnum::TAG_FRAME])->flush();
            return true;
        });
    }

    public function getAssessScore($id)
    {
        // TODO: Implement getAssessScore() method.
    }

    /**
     * 获取企业最高分数.
     * @throws BindingResolutionException
     */
    public function getMaxScore(): int
    {
        return (int) app()->get(AssessScoreService::class)->max([], 'max');
    }

    /**
     * 获取维度计算方式.
     */
    public function getComputeMode(): int
    {
        return intval(sys_config('assess_compute_mode', 0));
    }

    /**
     * 删除绩效表单.
     *
     * @param mixed $id
     * @return array
     */
    public function deleteForm($id)
    {
        $form = [
            FormService::textarea('mark', '删除原因', '')->placeholder('请输入删除原因')->required(),
        ];
        return $this->createElementForm('删除绩效考核', $form, '/ent/assess/delete/' . $id, 'DELETE');
    }

    /**
     * 删除绩效考核.
     *
     * @param mixed $id
     * @param mixed $mark
     * @param mixed $uid
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function deleteAssess($id, $mark, $uid)
    {
        if (! $mark) {
            throw $this->exception('删除失败，您填写删除原因！');
        }
        $info         = $this->dao->get($id);
        $spaceService = app()->get(AssessSpaceService::class);
        $info['info'] = $spaceService->getAssess((int) $id);
        $save         = [
            'assessid'  => $id,
            'entid'     => $info['entid'],
            'userid'    => $uid,
            'check_uid' => $info['check_uid'],
            'test_uid'  => $info['test_uid'],
            'score'     => $info['score'],
            'total'     => $info['total'],
            'grade'     => $info['grade'],
            'info'      => json_encode($info),
            'mark'      => $mark,
            'types'     => 1,
        ];
        $spaceService  = app()->get(AssessSpaceService::class);
        $targetService = app()->get(EnterpriseAssessTargetService::class);
        $spaceIds      = $spaceService->column(['assessid' => $id], 'id') ?: [];
        $targetIds     = $targetService->column(['spaceid' => $spaceIds], 'id') ?: [];

        return $this->transaction(function () use ($id, $save, $spaceService, $targetService, $spaceIds, $targetIds) {
            $res1 = app()->get(UserAssessScoreService::class)->create($save);
            if ($spaceIds) {
                $spaceService->delete($spaceIds, 'id');
            }
            if ($targetIds) {
                $targetService->delete($targetIds, 'id');
            }
            $res2 = $this->dao->delete($id, 'id');
            if (! $res1 || ! $res2) {
                throw $this->exception('删除失败！');
            }
            Cache::tags([CacheEnum::TAG_FRAME])->flush();

            return true;
        });
    }

    /**
     * 申诉/驳回.
     *
     * @return Model
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function assessAppeal(int $assessId, string $uid, array $data)
    {
        $types   = $data['types']; // 类型
        $content = $data['content']; // 类型
        if (! $content) {
            throw $this->exception('请填写内容');
        }
        $assessInfo = $this->dao->get($assessId);
        if (! $assessInfo) {
            throw $this->exception('考核记录不存在');
        }
        $replyService = app()->get(AssessReplyService::class);
        if ($types) {
            $replyService->update(['assessid' => $assessId, 'user_id' => $assessInfo['test_uid'], 'types' => 1], ['status' => 1]);
            $res = $replyService->create([
                'assessid' => $assessId,
                'entid'    => 1,
                'user_id'  => $uid,
                'content'  => $content,
                'types'    => 1,
                'status'   => 2,
            ]);
            $this->dao->update($assessId, ['status' => AssessEnum::ASSESS_REJECT]);
            // 绩效申诉结果
            Task::deliver(new UserAssessAppealResRemind(1, $assessInfo->toArray()));
        } else {
            if ($assessInfo['test_uid'] != $uid) {
                throw $this->exception('您暂无权限操作该考核记录');
            }
            if ($assessInfo['check_status'] != 1) {
                throw $this->exception('申诉失败，上级未评价打分');
            }
            if ($assessInfo['status'] == 4) {
                throw $this->exception('申诉失败，考核已结束');
            }
            $res = $replyService->create([
                'assessid' => $assessId,
                'entid'    => 1,
                'user_id'  => $assessInfo['test_uid'],
                'content'  => $content,
                'types'    => 1,
            ]);
            $this->dao->update($assessId, ['status' => AssessEnum::ASSESS_APPEAL]);
            // 绩效申诉提醒
            Task::deliver(new UserAssessAppealRemind(1, $assessInfo->toArray()));
        }
        $this->syncAssessTodo((int) $assessId, $types ? [(int) $uid] : []);
        return $res;
    }

    private function syncAssessTodo(int $assessId, array $userIds = []): void
    {
        if (! $assessId) {
            return;
        }

        try {
            foreach (TodoEnum::ASSESS_TYPES as $type) {
                BusinessDataChangeEvent::dispatch($type, $assessId, 'update', $userIds);
            }
        } catch (\Throwable) {
        }
    }

    /**
     * 绩效未创建列表.
     * @param mixed $period
     * @param mixed $time
     * @param mixed $entId
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function abnormalList($period, $time, $entId)
    {
        $planService = app()->get(AssessPlanService::class);
        $planId      = $planService->value(['period' => $period, 'entid' => $entId], 'id');
        $planUid     = $planService->getPlanUid($planId);
        if (! $time) {
            $time = now()->toDateTimeString();
        }
        switch ($period) {
            case 1:// 周考核
                [$start, $end] = [Carbon::make($time)->startOfWeek()->toDateTimeString(), Carbon::make($time)->endOfWeek()->toDateTimeString()];
                $title         = Carbon::make($time)->year . '年第' . Carbon::make($time)->week . '周考核';
                break;
            case 2:// 月考核
                [$start, $end] = [Carbon::make($time)->startOfMonth()->toDateTimeString(), Carbon::make($time)->endOfMonth()->toDateTimeString()];
                $title         = Carbon::make($time)->year . '年' . Carbon::make($time)->month . '月考核';
                break;
            case 3:// 年考核
                [$start, $end] = [Carbon::make($time)->startOfYear()->toDateTimeString(), Carbon::make($time)->endOfYear()->toDateTimeString()];
                $title         = Carbon::make($time)->year . '年考核';
                break;
            case 4:// 半年考核
                if (Carbon::make($time)->month > 6) {
                    [$start, $end] = [Carbon::make($time)->startOfYear()->addMonths(6)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->toDateTimeString()];
                    $title         = Carbon::make($time)->year . '年下半年考核';
                } else {
                    [$start, $end] = [Carbon::make($time)->startOfYear()->toDateTimeString(), Carbon::make($time)->startOfYear()->addMonths(6)->endOfDay()->toDateTimeString()];
                    $title         = Carbon::make($time)->year . '年上半年考核';
                }
                break;
            case 5:// 季度考核
                $month = Carbon::make($time)->month;
                if ($month > 9) {
                    [$start, $end] = [Carbon::make($time)->endOfYear()->subMonths(3)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->toDateTimeString()];
                    $title         = Carbon::make($time)->year . '第四季度考核';
                } elseif ($month > 6) {
                    [$start, $end] = [Carbon::make($time)->endOfYear()->subMonths(6)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->subMonths(3)->endOfDay()->toDateTimeString()];
                    $title         = Carbon::make($time)->year . '第三季度考核';
                } elseif ($month > 3) {
                    [$start, $end] = [Carbon::make($time)->endOfYear()->subMonths(9)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->subMonths(6)->endOfDay()->toDateTimeString()];
                    $title         = Carbon::make($time)->year . '第二季度考核';
                } else {
                    [$start, $end] = [Carbon::make($time)->startOfYear()->toDateTimeString(), Carbon::make($time)->endOfYear()->startOfYear()->addMonths(3)->endOfDay()->toDateTimeString()];
                    $title         = Carbon::make($time)->year . '第一季度考核';
                }
                break;
            default:
                throw $this->exception('无效的考核周期');
        }
        $assessUid   = $this->dao->column(['period' => $period, 'start_time' => $start, 'end_time' => $end], 'test_uid');
        $abnormalUid = array_diff($planUid, $assessUid);
        $normalUid = app(AdminInfoService::class)->column(['type' => [1, 2, 3]], 'id');
        $abnormalUid = array_intersect($abnormalUid, $normalUid);
        if (! $abnormalUid) {
            return [];
        }
        $service = app(AdminService::class);
        [$page, $limit] = $this->getPageValue();
        $list = $service->select(['id' => $abnormalUid], ['id', 'name', 'avatar', 'job'],[
            'job' => fn ($q) => $q->select(['id', 'name']),
            'frame',
            'super' => fn ($q) => $q->select(['admin.id', 'name']),
        ], $page, $limit)?->toArray();
        $count = $service->count(['id' => $abnormalUid]);
        $frameService = app()->get(FrameService::class);
        foreach ($list as &$value) {
            $value['super'] = $value['super'] ?: ($value['frame'] ? $frameService->getFrameAdmin((int) $value['frame']['id'], $entId, ['id', 'name']) : null);
            $value['title'] = $title;
        }
        return ['list' => $list, 'count' => $count];
    }

    /**
     * 绩效未创建列表.
     * @param mixed $period
     * @param mixed $time
     * @param mixed $entId
     * @return array|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function abnormalCount($period, $time, $entId): int
    {
        if (! $period) {
            $period = [
                AssessEnum::PERIOD_WEEK,
                AssessEnum::PERIOD_MONTH,
                AssessEnum::PERIOD_YEAR,
                AssessEnum::PERIOD_HALF_YEAR,
                AssessEnum::PERIOD_QUARTER,
            ];
        } else {
            $period = [$period];
        }
        $count = 0;
        foreach ($period as $value) {
            $planService = app()->get(AssessPlanService::class);
            $planId      = $planService->value(['period' => $value, 'entid' => $entId], 'id');
            $planUid     = $planService->getPlanUid($planId);
            if (! $time) {
                $time = now()->toDateTimeString();
            }
            switch ($value) {
                case AssessEnum::PERIOD_WEEK:// 周考核
                    [$start, $end] = [Carbon::make($time)->startOfWeek()->toDateTimeString(), Carbon::make($time)->endOfWeek()->toDateTimeString()];
                    break;
                case AssessEnum::PERIOD_MONTH:// 月考核
                    [$start, $end] = [Carbon::make($time)->startOfMonth()->toDateTimeString(), Carbon::make($time)->endOfMonth()->toDateTimeString()];
                    break;
                case AssessEnum::PERIOD_YEAR:// 年考核
                    [$start, $end] = [Carbon::make($time)->startOfYear()->toDateTimeString(), Carbon::make($time)->endOfYear()->toDateTimeString()];
                    break;
                case AssessEnum::PERIOD_HALF_YEAR:// 半年考核
                    if (Carbon::make($time)->month > 6) {
                        [$start, $end] = [Carbon::make($time)->startOfYear()->addMonths(6)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->toDateTimeString()];
                    } else {
                        [$start, $end] = [Carbon::make($time)->startOfYear()->toDateTimeString(), Carbon::make($time)->startOfYear()->addMonths(6)->endOfDay()->toDateTimeString()];
                    }
                    break;
                case AssessEnum::PERIOD_QUARTER:// 季度考核
                    $month = Carbon::make($time)->month;
                    if ($month > 9) {
                        [$start, $end] = [Carbon::make($time)->endOfYear()->subMonths(3)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->toDateTimeString()];
                    } elseif ($month > 6) {
                        [$start, $end] = [Carbon::make($time)->endOfYear()->subMonths(6)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->subMonths(3)->endOfDay()->toDateTimeString()];
                    } elseif ($month > 3) {
                        [$start, $end] = [Carbon::make($time)->endOfYear()->subMonths(9)->startOfDay()->toDateTimeString(), Carbon::make($time)->endOfYear()->subMonths(6)->endOfDay()->toDateTimeString()];
                    } else {
                        [$start, $end] = [Carbon::make($time)->startOfYear()->toDateTimeString(), Carbon::make($time)->endOfYear()->startOfYear()->addMonths(3)->endOfDay()->toDateTimeString()];
                    }
                    break;
                default:
                    throw $this->exception('无效的考核周期');
            }
            $assessUid   = $this->dao->column(['period' => $value, 'start_time' => $start, 'end_time' => $end], 'test_uid');
            $abnormalUid = array_diff($planUid, $assessUid);
            if ($abnormalUid) {
                $count += count($abnormalUid);
            }
        }
        return $count;
    }

    public function toEval(int $id)
    {
        $info              = $this->dao->get($id);
        $info->status      = 2;
        $info->test_status = 1;
        $info->save();
    }

    /**
     * 上级评分保存.
     * @param mixed $isDraft
     * @param mixed $data
     * @return array
     * @throws BindingResolutionException
     */
    final protected function verifyAssess($isDraft, $data)
    {
        $maxScore       = $this->getMaxScore();
        $computeMode    = $this->getComputeMode();
        $save['intact'] = 1;
        $save['status'] = 0;
        $save['total']  = $maxScore;
        if ($isDraft) {
            if ($computeMode) {
                if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                    $save['intact'] = 0;
                } else {
                    foreach ($data as $item) {
                        if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                            $save['intact'] = 0;
                        }
                        break;
                    }
                }
            } else {
                if (array_sum(array_column($data, 'ratio')) != 100) {
                    $save['intact'] = 0;
                } else {
                    foreach ($data as $item) {
                        if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                            $save['intact'] = 0;
                            break;
                        }
                    }
                }
            }
        } else {
            if ($computeMode) {
                if (array_sum(array_column($data, 'ratio')) != $maxScore) {
                    throw $this->exception('维度总分必须为' . $maxScore . '分');
                }
                foreach ($data as $item) {
                    if (array_sum(array_column($item['target'], 'ratio')) != $item['ratio']) {
                        throw $this->exception('维度中指标总分必须为' . $item['ratio'] . '分');
                    }
                }
            } else {
                if (array_sum(array_column($data, 'ratio')) != 100) {
                    throw $this->exception('维度权重总占比必须为100%');
                }
                foreach ($data as $item) {
                    if (array_sum(array_column($item['target'], 'ratio')) != 100) {
                        throw $this->exception('维度中指标权重总占比必须为100%');
                    }
                }
            }
        }
        return $save;
    }

    /**
     * 获取时间.
     *
     * @param mixed $type
     * @param mixed $time
     * @return int
     */
    final protected function getDateTime($type, int $day, $time, bool $toString = true)
    {
        $newDay = match ($type) {
            'before' => $time->subDays($day),
            'after'  => $time->addDays($day),
            default  => $time,
        };
        if ($toString) {
            return $newDay->toDateTimeString();
        }
        return $newDay;
    }

    /**
     * 获取周期时间.
     * @param mixed $period
     * @param mixed $time
     * @param mixed $planInfo
     */
    final protected function getTimes($period, $time, $planInfo): array
    {
        $info = [];
        switch ($period) {
            case 1:// 周考核
                [$year, $week] = explode('-', $time);
                $moreYear      = abs($year - intval(date('Y', time())));
                $info['name']  = $year . '年第' . $week . '周考核';
                if ($year >= intval(date('Y', time()))) {
                    if (intval(date('W', time())) > $week) {
                        $info['start_time']  = now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } elseif (intval(date('W', time())) < $week) {
                        $info['start_time']  = now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } else {
                        $info['start_time']  = now()->addYears($moreYear)->startOfWeek()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->endOfWeek()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfWeek());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfWeek(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    }
                } else {
                    if (intval(date('W', time())) > $week) {
                        $info['start_time']  = now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->startOfWeek());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->subWeeks(intval(date('W', time())) - $week)->endOfWeek(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } elseif (intval(date('W', time())) < $week) {
                        $info['start_time']  = now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->startOfWeek());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->addWeeks($week - intval(date('W', time())))->endOfWeek(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } else {
                        $info['start_time']  = now()->subYears($moreYear)->startOfWeek()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->endOfWeek()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfWeek());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfWeek(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    }
                }
                break;
            case 2:// 月考核
                [$year, $month] = explode('-', $time);
                $moreYear       = abs($year - intval(date('Y', time())));
                $info['name']   = $year . '年' . $month . '月考核';
                if ($year >= intval(date('Y', time()))) {
                    if (intval(date('m', time())) < $month) {
                        $info['start_time']  = now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } elseif (intval(date('m', time())) == $month) {
                        $info['start_time']  = now()->addYears($moreYear)->startOfMonth()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->endOfMonth()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfMonth());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfMonth(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } else {
                        $info['start_time']  = now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    }
                } else {
                    if (intval(date('m', time())) < $month) {
                        $info['start_time']  = now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->startOfMonth());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->addMonths($month - intval(date('m', time())))->endOfMonth(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } elseif (intval(date('m', time())) == $month) {
                        $info['start_time']  = now()->subYears($moreYear)->startOfMonth()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->endOfMonth()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfMonth());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfMonth(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } else {
                        $info['start_time']  = now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->startOfMonth());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->subMonths(intval(date('m', time())) - $month)->endOfMonth(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    }
                }
                break;
            case 3:// 年考核
                $year         = Carbon::make($time)->format('Y');
                $info['name'] = $year . '年考核';
                $moreYear     = abs($year - intval(date('Y', time())));
                if ($year >= intval(date('Y', time()))) {
                    $info['start_time']  = now()->addYears($moreYear)->startOfYear()->toDateTimeString();
                    $info['end_time']    = now()->addYears($moreYear)->endOfYear()->toDateTimeString();
                    $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear());
                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfYear(), false);
                    $info['check_end']   = $check_end->toDateTimeString();
                    $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                } else {
                    $info['start_time']  = now()->subYears($moreYear)->startOfYear()->toDateTimeString();
                    $info['end_time']    = now()->subYears($moreYear)->endOfYear()->toDateTimeString();
                    $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear());
                    $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfYear(), false);
                    $info['check_end']   = $check_end->toDateTimeString();
                    $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                }
                break;
            case 4:// 半年考核
                [$year, $index] = explode('-', $time);
                $moreYear       = abs($year - intval(date('Y', time())));
                if ($year >= intval(date('Y', time()))) {
                    if ($index == 1) {
                        $info['name']        = $year . '上半年考核';
                        $info['start_time']  = now()->addYears($moreYear)->startOfYear()->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(6), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } else {
                        $info['name']        = $year . '下半年考核';
                        $info['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6)->toDateTimeString();
                        $info['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(6)->endOfYear()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6));
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(6)->endOfYear(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    }
                } else {
                    if ($index == 1) {
                        $info['name']        = $year . '上半年考核';
                        $info['start_time']  = now()->subYears($moreYear)->startOfYear()->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear());
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(6), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    } else {
                        $info['name']        = $year . '下半年考核';
                        $info['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6)->toDateTimeString();
                        $info['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(6)->endOfYear()->toDateTimeString();
                        $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear()->addMonths(6)->startOfYear()->addMonths(6));
                        $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(6)->endOfYear(), false);
                        $info['check_end']   = $check_end->toDateTimeString();
                        $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                    }
                }
                break;
            case 5:// 季度考核
                [$year, $index] = explode('-', $time);
                $moreYear       = abs($year - intval(date('Y', time())));
                if ($year >= intval(date('Y', time()))) {
                    switch ($index) {
                        case 1:
                            $info['name']        = $year . '第一季度考核';
                            $info['start_time']  = now()->addYears($moreYear)->startOfYear()->toDateTimeString();
                            $info['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(3)->subSeconds()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear());
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(3), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                        case 2:
                            $info['name']        = $year . '第二季度考核';
                            $info['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(3)->toDateTimeString();
                            $info['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(6)->subSeconds()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addMonths(3)->startOfYear());
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(6), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                        case 3:
                            $info['name']        = $year . '第三季度考核';
                            $info['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                            $info['end_time']    = now()->addYears($moreYear)->startOfYear()->addMonths(9)->subSeconds()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->addMonths(6)->startOfYear());
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->startOfYear()->addMonths(9), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                        case 4:
                            $info['name']        = $year . '第四季度考核';
                            $info['start_time']  = now()->addYears($moreYear)->startOfYear()->addMonths(9)->toDateTimeString();
                            $info['end_time']    = now()->addYears($moreYear)->endOfYear()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->addYears($moreYear)->startOfYear()->addMonths(9));
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->addYears($moreYear)->endOfYear(), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                    }
                } else {
                    switch ($index) {
                        case 1:
                            $info['name']        = $year . '第一季度考核';
                            $info['start_time']  = now()->subYears($moreYear)->startOfYear()->toDateTimeString();
                            $info['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(3)->subSeconds()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear());
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(3), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                        case 2:
                            $info['name']        = $year . '第二季度考核';
                            $info['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(3)->toDateTimeString();
                            $info['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(6)->subSeconds()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addMonths(3)->startOfYear());
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(6), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                        case 3:
                            $info['name']        = $year . '第三季度考核';
                            $info['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(6)->toDateTimeString();
                            $info['end_time']    = now()->subYears($moreYear)->startOfYear()->addMonths(9)->subSeconds()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->addMonths(6)->startOfYear());
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->startOfYear()->addMonths(9), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                        case 4:
                            $info['name']        = $year . '第四季度考核';
                            $info['start_time']  = now()->subYears($moreYear)->startOfYear()->addMonths(9)->toDateTimeString();
                            $info['end_time']    = now()->subYears($moreYear)->endOfYear()->toDateTimeString();
                            $info['make_time']   = $this->getDateTime($planInfo->make_type, (int) $planInfo->make_day, now()->subYears($moreYear)->startOfYear()->addMonths(9));
                            $check_end           = $this->getDateTime($planInfo->eval_type, (int) $planInfo->eval_day, now()->subYears($moreYear)->endOfYear(), false);
                            $info['check_end']   = $check_end->toDateTimeString();
                            $info['verify_time'] = $this->getDateTime($planInfo->verify_type, (int) $planInfo->verify_day, $check_end);
                            break;
                    }
                }
                break;
            default:
                throw $this->exception('保存失败，无效的考核周期');
        }
        return $info;
    }

    /**
     * TODO 绩效考核定时任务
     *
     * @param mixed $assessInfo
     * @return bool
     * @throws BindingResolutionException
     */
    private function openAssessTask($assessInfo)
    {
        if (is_object($assessInfo)) {
            $assessInfo = $assessInfo->toArray();
        }
        $option            = new TaskOptions($assessInfo['name']);
        $option->persist   = 1;
        $option->className = 'user.assess';
        $option->entid     = $assessInfo['entid'];
        // 设置任务参数
        $option->uniqued        = md5($assessInfo['entid'] . $assessInfo['id'] . 'start');
        $option->period         = 'day';
        $option->intervalHour   = (int) Carbon::parse($assessInfo['start_time'])->timezone(config('app.timezone'))->format('H');
        $option->intervalMinute = (int) Carbon::parse($assessInfo['start_time'])->timezone(config('app.timezone'))->format('i');
        $option->intervalSecond = (int) Carbon::parse($assessInfo['start_time'])->timezone(config('app.timezone'))->format('s') + 1;
        $taskService            = app()->get(TaskService::class);
        if ($taskService->exists(['uniqued' => $option->uniqued])) {
            $taskService->delete($option->uniqued, 'uniqued');
        }
        $option->setParameter($assessInfo['id']);

        return $taskService->addTask($option);
    }

    /**
     * 格式化考核时间.
     */
    private function strToTime(string $startTime, int $period): string
    {
        $timeObj = Carbon::parse($startTime)->timezone(config('app.timezone'));
        return match ($period) {
            1 => $timeObj->format('Y') . '-' . $timeObj->weekOfYear,
            3 => $timeObj->format('Y'),
            4 => $timeObj->format('Y') . '-' . ($timeObj->format('m') > 6 ? 2 : 1),
            5 => $timeObj->format('Y') . '-' . match ($timeObj->format('m')) {
                4, 5, 6 => 2,
                7, 8, 9 => 3,
                10, 11, 12 => 4,
                default => 1,
            },
            default => $timeObj->format('Y-m'),
        };
    }
}
