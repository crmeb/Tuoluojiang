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

namespace App\Http\Service\Customer;

use App\Constants\AttachEnum;
use App\Constants\CacheEnum;
use App\Constants\CustomerNoticeEnum;
use App\Constants\CommonEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\ScheduleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Constants\TodoEnum;
use App\Events\BusinessDataChangeEvent;
use App\Http\Contract\Customer\CustomerFollowInterface;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Dao\Customer\FollowUpDao;
use App\Http\Dao\Schedule\ScheduleRemindDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Ai\AiModelService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\Work\WorkMemberService;
use App\Http\Service\Work\WorkMessageService;
use App\Task\message\StatusChangeTask;
use Carbon\Carbon;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Tuoluojiang\Open\services\ai\BaseOption;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 跟踪记录Service.
 * @mixin FollowUpDao
 */
class FollowUpService extends BaseService implements CustomerFollowInterface
{
    use ResourceServiceTrait;

    protected array $tags;

    public function __construct(FollowUpDao $dao)
    {
        $this->dao  = $dao;
        $this->tags = [CacheEnum::TAG_SCHEDULE];
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param string $sort
     * @param array|string[] $with
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['client_follow.*'], $sort = 'client_follow.created_at', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $where          = $this->getWhere($where, auth('admin')->id());
        $with           = $with + ['card', 'attachs', 'clue', 'odds', 'customer'];
        $data           = collect($this->dao->getList($where, $field, $page, $limit, $sort, $with) ?: [])->map(function ($item) {
            $item['attachs'] = collect($item['attachs'] ?? [])->map(function ($val) {
                $val['url'] = $val['url'] ? link_file($val['url']) : '';
                return $val;
            })->all();
            $item['title'] = match ($item['link_type']) {
                ViewSearchEnum::VIEW_CLUE => $item['clue'] ?: [],
                ViewSearchEnum::VIEW_ODDS => $item['odds'] ?: [],
                default                   => $item['customer'] ?: [],
            };
            $item['type']      = 0;
            $item['follow_id'] = $item['id'];
            return $item;
        })->all();
        $count = $this->dao->count($where);
        return $this->listData($data, $count);
    }

    /**
     * 保存.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $uuid            = (string) $this->uuId(false);
        $data['user_id'] = auth('admin')->id();
        $attachIds       = $data['attach_ids'];
        $followId        = $data['follow_id'] ?? 0;
        unset($data['attach_ids'], $data['follow_id']);
        switch ($data['link_type']) {
            case ViewSearchEnum::VIEW_CLUE:
            case ViewSearchEnum::VIEW_CLUE_SEAS:
                $clue = app(LeadService::class)->get((int) $data['eid'], ['id', 'return_num', 'uid']);
                if (! $clue) {
                    throw $this->exception('线索数据异常');
                }
                $data['follow_version'] = $clue['return_num'];
                break;
            case ViewSearchEnum::VIEW_CUSTOMER:
                $customer = app(CustomerService::class)->get((int) $data['eid'], ['id', 'return_num', 'uid']);
                if (! $customer) {
                    throw $this->exception('客户数据异常');
                }
                $data['follow_version'] = $customer['return_num'];
                break;
            case ViewSearchEnum::VIEW_ODDS:
                break;
            default:
                throw $this->exception('该业务暂不支持填写跟进');
        }
        return $this->transaction(function () use ($data, $attachIds, $uuid, $followId) {
            if ($data['types']) {
                if (! $data['time']) {
                    throw $this->exception('common.empty.attrs');
                }
                $data['uniqued'] = md5(json_encode($data) . time());
                $res1            = $this->dao->create($data);

                $timeZone  = config('app.timezone');
                $startTime = Carbon::parse($data['time'], $timeZone)->startOfDay()->toDateTimeString();
                $endTime   = Carbon::parse($data['time'], $timeZone)->endOfDay()->toDateTimeString();
                $res2      = app(ScheduleInterface::class)->saveSchedule($data['user_id'], 1, [
                    'title'       => $data['content'],
                    'content'     => $data['content'],
                    'remind'      => 1,
                    'remind_time' => $data['time'],
                    'all_day'     => 1,
                    'cid'         => match ($data['link_type']) {
                        ViewSearchEnum::VIEW_CLUE     => ScheduleEnum::TYPE_CLUE_TRACK,
                        ViewSearchEnum::VIEW_CUSTOMER => ScheduleEnum::TYPE_CLIENT_TRACK,
                        ViewSearchEnum::VIEW_ODDS     => ScheduleEnum::TYPE_ODDS_TRACK,
                        default                       => throw $this->exception('该业务暂不支持填写跟进'),
                    },
                    'period'     => 0,
                    'rate'       => 1,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                    'fail_time'  => $endTime,
                    'link_id'    => $data['eid'],
                    'uniqued'    => $data['uniqued'],
                    'member'     => [$data['user_id']],
                ]);
                return $res1 && $res2 ? $res1 : [];
            }

            // 更新日程状态
            ! $data['types'] && $this->updateSchedule((int) $data['eid']);

            // 完成日程关联跟进提醒
            if ($followId) {
                $this->dao->update(['id' => $followId, 'types' => 1], ['status' => 2]);
            }
            unset($data['time']);
            $res = $this->dao->create($data);
            if ($attachIds) {
                app(AttachService::class)->saveRelation($attachIds, $uuid, $res->id, AttachEnum::RELATION_TYPE_FOLLOW);
            }
            app(RecordService::class)->saveRecord(
                $data['link_type'],
                [
                    'eid'            => $data['eid'],
                    'type'           => CustomEnum::LINK_FOLLOW,
                    'creator_uid'    => $data['user_id'],
                    'record_version' => $res->id,
                    'reason'         => '新增跟进记录',
                ]
            );
            $this->syncCustomerTodo((string) $data['link_type'], (int) $data['eid']);
            return $res;
        });
    }

    /**
     * 修改.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        $info = $this->dao->get($id, ['*'], ['file' => fn ($q) => $q->select(['id', 'fid'])]);
        if (! $info) {
            throw $this->exception('common.operation.noExists');
        }
        $uuid            = (string) $this->uuId(false);
        $attachIds       = $data['attach_ids'];
        $data['user_id'] = auth('admin')->id();
        unset($data['attach_ids'], $data['follow_id'], $data['eid']);
        $schedule = app(ScheduleInterface::class);
        $res = $this->transaction(function () use ($data, $info, $id, $schedule, $attachIds, $uuid) {
            if ($info->types) {
                if (! $data['time']) {
                    $data['time'] = $info['time'];
                }
                $data['uniqued'] = md5(json_encode($data) . time());
                unset($data['files']);
                $res1 = $this->dao->update($id, $data);
                if ($info->uniqued && $info->uniqued != $data['uniqued']) {
                    $schedule->deleteRemind($data['user_id'], $info->uniqued);
                    $schedule->saveSchedule($data['user_id'], 1, [
                        'title'       => $data['content'],
                        'content'     => $data['content'],
                        'remind'      => 1,
                        'remind_time' => $data['time'],
                        'all_day'     => 1,
                        'cid'         => match ($data['link_type']) {
                            ViewSearchEnum::VIEW_CLUE     => ScheduleEnum::TYPE_CLUE_TRACK,
                            ViewSearchEnum::VIEW_CUSTOMER => ScheduleEnum::TYPE_CLIENT_TRACK,
                            ViewSearchEnum::VIEW_ODDS     => ScheduleEnum::TYPE_ODDS_TRACK,
                            default                       => throw $this->exception('该业务暂不支持填写跟进'),
                        },
                        'period'     => 0,
                        'rate'       => 1,
                        'start_time' => Carbon::parse($data['time'], config('app.timezone'))->startOfDay()->toDateTimeString(),
                        'end_time'   => Carbon::parse($data['time'], config('app.timezone'))->endOfDay()->toDateTimeString(),
                        'fail_time'  => Carbon::parse($data['time'], config('app.timezone'))->endOfDay()->toDateTimeString(),
                        'link_id'    => $info->eid,
                        'uniqued'    => $data['uniqued'],
                        'member'     => [$data['user_id']],
                    ]);
                }
                return $res1;
            }
            app(AttachService::class)->saveRelation($attachIds, $uuid, (int) $id, AttachService::RELATION_TYPE_FOLLOW);
            unset($data['time']);
            return $this->dao->update($id, $data);
        });
        if ($res && ! $info->types) {
            $this->syncCustomerTodo((string) $info->link_type, (int) $info->eid);
        }
        return $res;
    }

    /**
     * 删除.
     * @param mixed $id
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('common.operation.noExists');
        }
        if ($info->types && $info->uniqued) {
            app(ScheduleInterface::class)->deleteRemind(auth('admin')->id(), $info->uniqued);
        }
        $res = $this->transaction(function () use ($info, $id) {
            app(RecordService::class)->delete(['link_type' => ViewSearchEnum::VIEW_CUSTOMER, 'record_version' => $id]);
            return $info->delete();
        });
        if ($res) {
            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CUSTOMER_FOLLOW_NOTICE, CommonEnum::STATUS_DELETE, 1, $info->eid));
            ! $info->types && $this->syncCustomerTodo((string) $info->link_type, (int) $info->eid);
        }
        return $res;
    }

    private function syncCustomerTodo(string $linkType, int $customerId): void
    {
        if ($linkType !== ViewSearchEnum::VIEW_CUSTOMER || ! $customerId) {
            return;
        }

        try {
            BusinessDataChangeEvent::dispatch(TodoEnum::TYPE_CUSTOMER, $customerId);
        } catch (\Throwable) {
        }
    }

    /**
     * 未完成待办提醒客户ID.
     */
    public function getEidBySchedule(array $where, array $field = ['client_follow.eid']): array
    {
        return $this->dao->scheduleSearch($where)->select($field)->distinct()->pluck('client_follow.eid')->filter()->all();
    }

    /**
     * 提醒删除.
     * @param mixed $uniqued
     * @throws BindingResolutionException
     */
    public function delScheduleAfter($uniqued): void
    {
        $this->dao->update(['uniqued' => $uniqued], ['types' => 0, 'uniqued' => '']);
    }

    /**
     * 获取客户最后跟进时间.
     * @throws BindingResolutionException
     */
    public function getLastFollowTime(int $eid, string $linkType = ViewSearchEnum::VIEW_CUSTOMER): string
    {
        return (string) $this->dao->setDefaultSort('created_at')->value(['eid' => $eid, 'types' => 0, 'link_type' => $linkType], 'created_at');
    }

    /**
     * 保存.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function authSave(int $clientId, string $externalUserid, string $userid)
    {
        $customerService     = app()->make(CustomerService::class);
        $customerClueService = app()->make(LeadService::class);

        // 查询出当前客户和用户的对话回复超过三轮，并切当前的员工今天没有给客户生成过跟进记录
        $messageList = app()->make(WorkMessageService::class)->getClientMessage($externalUserid, $userid);
        if (! $messageList) {
            return;
        }
        // 查不出成员不再记录
        $work_member_id = app()->make(WorkMemberService::class)->value(['userid' => $userid], 'id');
        if (! $work_member_id) {
            return;
        }
        // 成员没有绑定员工
        $uid = app()->make(AdminService::class)->value(['work_member_id' => $work_member_id], 'id');
        if (! $uid) {
            return;
        }
        $linkType = '';
        if (($customerInfo = $customerService->get(['external_userid' => $externalUserid, 'userid' => $userid])) && ! $this->dao->getClientFollow($customerInfo->id)) {
            $linkType = 'customer';
        } elseif (($customerClue = $customerClueService->get(['external_userid' => $externalUserid, 'userid' => $userid])) && ! $this->dao->getClientFollow($customerClue->id, 'clue')) {
            $linkType = 'clue';
        }
        // 记录了跟进不在生成
        if (! $linkType) {
            return;
        }

        $messageAttr = $client = $user = [];
        foreach ($messageList as $message) {
            if (empty($message['content']['content'])) {
                continue;
            }
            if (str_contains($message['from'], 'wmu')) {
                // 客户发送的消息
                $client[]      = $message['content']['content'];
                $messageAttr[] = '客户消息内容：' . $message['content']['content'];
            } else {
                // 员工发送的消息
                $messageAttr[] = '员工消息内容：' . $message['content']['content'];
                $user[]        = $message['content']['content'];
            }
        }
        if (count($client) < 3 && count($user) < 3) {
            return;
        }
        $messageStr    = implode("\n", $messageAttr);
        $followContent = <<<'EOF'
一、角色定位
你是一名专业的客户跟进记录 AI 助手，擅长从客户与员工的聊天记录中，精准提取关键信息并转化为标准化、高可读性的跟进记录。你需要具备极强的信息筛选能力，忽略无关寒暄，只聚焦核心业务内容。

二、核心任务
信息提取：从聊天记录中提取并分类以下关键信息，确保无遗漏、无错误。
客户基础信息：姓名、联系方式（若提及）、所属公司（若提及）。
核心需求 / 问题：明确客户本次沟通中提出的主要需求、待解决的问题或关注点。
已确认信息：双方已达成共识、确认无误的内容，如产品型号、价格、服务范围等。
待办事项：需要员工后续跟进的任务，需明确责任人（默认员工）、具体行动和时间节点（若提及）。
格式输出：将提取的信息按照固定模板整理，使用简洁、专业的商务语言，避免口语化表达。

三、执行要求
仅基于提供的聊天记录内容生成，不添加主观推测或额外信息。
若聊天记录中某类信息未提及，需标注 “未提及” 或 “未明确”，不可空白。

**返回格式必须为json格式**:
```json
{
    "content": "具体的跟进内容"
}
EOF;
        $content  = '根据聊天记录，请生成客户跟进记录。聊天内容：' . $messageStr;
        $messages = [
            ['content' => $followContent, 'role' => BaseOption::RULE_SYSTEM],
            ['content' => $content, 'role' => BaseOption::RULE_USER],
        ];

        try {
            $res = $this->chat($messages);

            $content = '';
            if (isset($res['choices'][0]['message']['content'])) {
                $content = $res['choices'][0]['message']['content'];
                $content = json_decode($content, true);
                $content = $content['content'] ?? '';
            }
            if ($content) {
                $this->dao->create([
                    'eid'        => $linkType === 'customer' ? $customerInfo->id : $customerClue->id,
                    'link_type'  => $linkType,
                    'content'    => $content,
                    'user_id'    => $uid,
                    'status'     => 0,
                    'types'      => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return;
        }
    }

    /**
     * 聊天.
     * @return array|string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function chat(array $message, array $body = [])
    {
        $modelsId   = (int) sys_config('customer_models_id');
        $modelsInfo = app(AiModelService::class)->get($modelsId);
        if (! $modelsInfo) {
            throw $this->exception('没有查询到模型信息');
        }
        if (! $modelsInfo->api_key) {
            throw $this->exception('模型配置有问题，请检查模型配置');
        }

        $option = app(AiModelService::class)->makeChatOption($modelsInfo);
        foreach ($message as $item) {
            $option->setMessage($item['content'], $item['role'] ?? BaseOption::RULE_USER);
        }

        $option->stream = false;

        $option->streamOptions = [
            'include_usage' => false,
        ];

        $curl = new BaseCurl((string) $modelsInfo->api_key);

        return $curl->setBody($option)->send(url: $option->url, body: $body);
    }

    /**
     * 获取跟进记录分组列表.
     * @param mixed $where
     * @return array|Collection[]
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getFollowGroup($where)
    {
        switch ($where['link_type']) {
            case ViewSearchEnum::VIEW_ODDS:
                $where['eid'] = app(OpportunityService::class)->column(['eid' => $where['eid']], 'id');
                $where['eid'] = $where['oid'] ? array_push($where['eid'], $where['oid']) : $where['eid'];
                break;
            case ViewSearchEnum::VIEW_CONTRACT:
                $where['eid'] = app(ContractService::class)->column(['eid' => $where['eid']], 'id');
                $where['eid'] = $where['cid'] ? array_push($where['eid'], $where['cid']) : $where['eid'];
                break;
        }
        unset($where['cid'],$where['oid']);
        return collect($this->dao->setDefaultSort('id')->select($where, with: ['card', 'attachs'])?->toArray())->map(function ($item) {
            $item['attachs'] = collect($item['attachs'] ?? [])->map(function ($val) {
                $val['url'] = $val['url'] ? link_file($val['url']) : '';
                return $val;
            })->all();
            $item['type']      = 0;
            $item['follow_id'] = $item['id'];
            return $item;
        })->groupBy('eid')->all();
    }

    /**
     * 更新日程状态
     * @param mixed $eid
     * @return bool|void
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function updateSchedule(int $eid): void
    {
        $list = $this->dao->column(['eid' => $eid, 'types' => 1, 'time_lt' => date('Y-m-d H:i:s'), 'status' => 0], 'id,uniqued');
        if (! $list) {
            return;
        }
        $scheduleRemindService = app(ScheduleRemindDao::class);
        $scheduleService       = app(ScheduleInterface::class);
        try {
            foreach ($list as $item) {
                $this->dao->update($item['id'], ['status' => 2]);
                $scheduleRemindInfo = $scheduleRemindService->get(['uniqued' => $item['uniqued']], ['id', 'sid', 'uid', 'entid'], ['schedule']);
                if (! $scheduleRemindInfo || ! $scheduleRemindInfo->schedule) {
                    continue;
                }

                if ($scheduleRemindInfo->schedule->status !== 0) {
                    continue;
                }

                $scheduleService->updateStatus(
                    $scheduleRemindInfo->sid,
                    (int) $scheduleRemindInfo->uid,
                    $scheduleRemindInfo->entid,
                    3,
                    [$scheduleRemindInfo->schedule->start_time, $scheduleRemindInfo->schedule->end_time]
                );
            }
        } catch (\Exception) {
        }
    }

    private function getWhere(array $where, int $uid)
    {
        if (isset($where['user_id']) && $where['user_id']) {
            $userId = is_array($where['user_id']) ? $where['user_id'] : [$where['user_id']];
        }
        switch ((int) $where['view_search']) {
            case 1:// 我负责的
                $where['user_id'] = $uid;
                break;
            case 2:// 我查看的
                $where['user_id'] = app(FrameAssistService::class)->getScopeUid($uid, 'all');
                break;
        }
        $where['user_id'] = isset($userId) ? array_intersect($userId, $where['user_id']) : $where['user_id'];
        unset($where['view_search']);
        $where['exist'] = 1;
        return $where;
    }
}
