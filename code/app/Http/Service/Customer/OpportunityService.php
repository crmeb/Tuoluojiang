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
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\OddsEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Events\SystemMessageEvent;
use App\Http\Dao\Customer\OpportunityDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\User\UserRemindLogService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\traits\service\ServicesTrait;
use Tuoluojiang\Open\utils\MessageType;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 商机模块.
 */
class OpportunityService extends BaseService
{
    use CustomerTrait;
    use ResourceServiceTrait;
    use ServicesTrait;

    public $dao;

    public function __construct(OpportunityDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 已入账金额.
     */
    public function getAmountRecorded(int $id): string
    {
        $billService = app(PaymentService::class);
        return sprintf('%.2f', $billService->getSum(['eid' => $id, 'types' => [0, 1]]));
    }

    /**
     * 已支出金额.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAmountExpend(int $id): string
    {
        $billService = app(PaymentService::class);
        return sprintf('%.2f', $billService->getSum(['eid' => $id, 'types' => 2]));
    }

    /**
     * 已开票金额.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getInvoicedAmount(int $id): string
    {
        $billService = app(PaymentService::class);
        return sprintf('%.2f', $billService->sum(['eid' => $id, 'status' => [1, 3, 5, 6]], 'num'));
    }

    /**
     * 合同订单数量.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getContractNum(int $id): int
    {
        return app(OrderService::class)->count(['oid' => $id]);
    }

    /**
     * 发票数量.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getInvoiceNum(int $id): int
    {
        return app(InvoiceService::class)->count(['eid' => $id]);
    }

    /**
     * 附件数量.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAttachmentNum(int $id): int
    {
        return app(AttachService::class)->getCountByRelationType(AttachService::RELATION_TYPE_CLIENT, $id);
    }

    /**
     * 最后跟进时间.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getLastFollowTime(int $id): string
    {
        return app(FollowUpService::class)->getLastFollowTime($id, ViewSearchEnum::VIEW_ODDS);
    }

    /**
     * 获取退回原因.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getReturnReason(int $id): string
    {
        return app(RecordService::class)->getLastReasonByEid($id, 1);
    }

    /**
     * 字段过滤.
     * @return string[]
     */
    public function dictFilterField(): array
    {
        return ['followed'];
    }

    public function followUpField(): string
    {
        return 'followed';
    }

    public function followUpService(): string
    {
        return SubscribeService::class;
    }

    /**
     * 关注状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSubscribeStatus(int $uid, array $ids): array
    {
        return app(SubscribeService::class)->getSubscribeStatusWithEid($uid, $ids, CustomEnum::ODDS);
    }

    /**
     * 获取未跟进天数.
     * @param mixed $nowObj
     * @throws BindingResolutionException
     */
    public function getUnFollowedDays(int $id, string $tz, $nowObj, string $followTime = ''): int
    {
        if (! $followTime) {
            $followTime = $this->getLastFollowTime($id);
        }
        return Carbon::parse($followTime, $tz)->startOfDay()->diffInDays($nowObj, false);
    }

    /**
     * 获取搜索字段.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function searchField(): array
    {
        $field[]  = ['statistics_type', ''];
        $field[]  = ['types', ''];
        $field[]  = ['uid', ''];
        $field[]  = ['eid', ''];
        $fieldSet = app(FormService::class)->getCustomDataByTypes(CustomEnum::ODDS, ['key as field', 'input_type']);
        $fieldSet = array_merge($fieldSet, OddsEnum::ODDS_SEARCH_FIELD, OddsEnum::ODDS_CHARGE_SEARCH_FIELD);
        foreach ($fieldSet as $value) {
            $field[] = [$value['field'], ''];
        }
        return $field;
    }

    /**
     * 保存商机.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveOdds(array $data, int $uid, array $products = []): mixed
    {
        unset($data['odds_no']);
        if (array_key_exists('products', $data)) {
            $products = is_array($data['products']) ? $data['products'] : [];
            unset($data['products']);
        }

        $formService = app(FormService::class);
        $list        = $formService->getFormDataList(CustomEnum::ODDS);
        $this->checkRequiredProducts($products, $formService, CustomEnum::ODDS, $list);
        $formService->fieldValueCheck($data, CustomEnum::ODDS, 0, $list);
        $attaches = [];
        foreach ($list as $item) {
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            foreach ($data as $key => &$datum) {
                if ($item['key'] == $key) {
                    if (in_array($inputType, ['file', 'images'])) {
                        $attaches = array_merge($attaches, (array) $datum);
                    }
                    $datum = $formService->getFormValue($type, $inputType, $datum, $key);
                }
            }
        }
        $attaches              = array_filter($attaches);
        $data['odds_customer'] = $data['odds_customer'] ?? '';
        if ($data['odds_customer']) {
            $customerInfo = app(CustomerService::class)->get($data['odds_customer'], ['userid', 'external_userid'])?->toArray();
            if ($customerInfo) {
                $data['userid']          = $customerInfo['userid'];
                $data['external_userid'] = $customerInfo['external_userid'];
            }
        }
        $data['eid'] = $data['odds_customer'];
        unset($data['odds_customer']);
        $data['creator_uid'] = $data['uid'] = $uid;
        $data['odds_no']     = $this->getUniqueNo('SJ');
        return $this->transaction(function () use ($products, $data, $attaches) {
            $res = $this->dao->create($data);
            if (! $res) {
                throw $this->exception('保存失败');
            }
            $products && app(ProductAssistService::class)->saveProducts($products, $res->id);
            app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_ODDS, [
                'eid'            => $res->id,
                'type'           => OddsEnum::OPERATE_CREATE,
                'creator_uid'    => $data['creator_uid'],
                'record_version' => 0,
                'reason'         => '新添加商机“' . ($data['name'] ?? $data['odds_no'] ?? '') . '”',
            ]);
            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $res->id, 'relation_type' => AttachEnum::RELATION_TYPE[ViewSearchEnum::VIEW_ODDS]]);
            }
            if (isset($data['followed'])) {
                $status = $data['followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($data['uid'], $res->id, $status, CustomEnum::ODDS);
            }
            return $res;
        });
    }

    public function getSearchField()
    {
        return [
            ['eid', ''],
            ['name', '', 'name_like'],
            ['salesman_id', '', 'uid'],
            ['time', ''],
            ['signing_status', ''],
            ['abnormal', '', 'contract_status'],
        ];
    }

    /**
     * 修改商机.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateOdds(array $data, int $id, array $products = []): mixed
    {
        unset($data['odds_no']);
        $hasProductsField = array_key_exists('products', $data);
        if ($hasProductsField) {
            $products = is_array($data['products']) ? $data['products'] : [];
            unset($data['products']);
        }

        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $shouldSaveProducts = $hasProductsField || ! empty($products);
        if (! $data) {
            if (! $shouldSaveProducts) {
                return false;
            }

            return $this->transaction(function () use ($products, $id) {
                app(ProductAssistService::class)->saveProducts($products, $id);
                return true;
            });
        }

        $formService = app(FormService::class);
        $list        = $formService->getFormDataList(CustomEnum::ODDS);
        $formService->fieldValueCheck($data, CustomEnum::ODDS, $id, $list);
        if (isset($data['odds_customer'])) {
            $data['eid'] = $data['odds_customer'];
            unset($data['odds_customer']);
        }
        $attaches = [];
        foreach ($list as $item) {
            $type      = strtolower($item['type']);
            $inputType = strtolower($item['input_type']);
            foreach ($data as $key => &$datum) {
                if ($item['key'] == $key) {
                    if (in_array($inputType, ['file', 'images'])) {
                        $attaches = array_merge($attaches, (array) $datum);
                    }
                    $datum = $formService->getFormValue($type, $inputType, $datum, $key);
                }
            }
        }
        $uid      = $info->uid;
        $attaches = array_filter($attaches);
        return $this->transaction(function () use ($products, $id, $uid, $data, $attaches, $shouldSaveProducts) {
            $res = $this->dao->update($id, $data);
            if (! $res) {
                throw $this->exception('更新失败');
            }
            if ($shouldSaveProducts) {
                app(ProductAssistService::class)->saveProducts($products, $id);
            }
            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $id, 'relation_type' => AttachEnum::RELATION_TYPE[ViewSearchEnum::VIEW_ODDS]]);
            }
            if (isset($data['followed'])) {
                $status = $data['followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($uid, $id, $status, CustomEnum::ODDS);
            }
            return $res;
        });
    }

    /**
     * 无需同步字段.
     * @return string[]
     */
    public function getOutOfSyncField(): array
    {
        return ['contract_customer', 'products'];
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelectList(array|int $eid, int $uid): array
    {
        $userIds = app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER);
        return $this->dao->getList(['eid' => $eid, 'uid' => $userIds], ['id', 'eid', 'name as title'], 0, 0, 'id');
    }

    /**
     * 执行定时器任务
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function timer(int $page, int $limit): void
    {
        $key          = 'client_contract_list_' . $page . '_' . $limit;
        $ttl          = (int) sys_config('system_cache_ttl', 3600);
        $contractList = Cache::tags([CacheEnum::TAG_CUSTOMER])->remember($key, $ttl, function () use ($page, $limit) {
            $this->dao->getClientContractList([], $page, $limit);
        });

        if (! $contractList) {
            return;
        }

        $nowTimer = time();
        foreach ($contractList as $item) {
            $dateTimer = datetime_timestamp($item['end_date']);
            $dateNow   = now()->setTimeFromTimeString(date('Y-m-d H:i:s', $dateTimer));
            if ($dateTimer > $nowTimer) {
                // 前30天提醒
                // 转换成天
                $subTime = (int) (($dateTimer - $nowTimer) / 60 / 60 / 24);
                // 30天前
                if ($subTime == 30) {
                    $this->sendMessage($item, MessageType::CONTRACT_SOON_OVERDUE_REMIND);
                }
            } elseif ($dateNow->year == now()->year && $dateNow->day == now()->day && $dateNow->month == now()->month) {
                // 结束当天
                $this->sendMessage($item, MessageType::CONTRACT_OVERDUE_DAY_REMIND);
            }
        }
    }

    /**
     * 发送消息.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function sendMessage(array $item, string $type): void
    {
        $userRemindLogService = app(UserRemindLogService::class);
        // 发送过不再发送
        if ($userRemindLogService->exists([
            'entid'       => 1,
            'user_id'     => $item['card']['user_id'],
            'year'        => now()->year,
            'month'       => now()->month,
            'day'         => now()->day,
            'remind_type' => $type,
            'relation_id' => $item['id'],
        ])) {
            return;
        }
        $message           = app(MessageService::class)->getMessageContent(1, $type);
        $hasTemplateTime   = ! empty($message['template_time']);
        $isRemindTimeMatch = $hasTemplateTime && ! empty($message['remind_time']) && date('H:i') === $message['remind_time'];
        $res               = ! $hasTemplateTime || $isRemindTimeMatch;
        if (! $res) {
            return;
        }
        event(new SystemMessageEvent(
            type: $type,
            params: [
                '商机名称'   => $item['title'] ?? '',
                '商机金额'   => $item['price'] ?? '',
                '商机开始时间' => $item['start_date'] ?? '',
                '商机结束时间' => $item['end_date'] ?? '',
                '客户名称'   => $item['name'] ?? '',
                '业务员'    => $item['card']['name'] ?? '',
            ],
            receiverIds: $item['card']['user_id'],
            other: [
                'id' => $item['id'],
            ],
            linkId: $item['id'],
        ));
        // 写入消息提醒记录数据
        $userRemindLogService->create([
            'remind_type' => $type,
            'user_id'     => $item['uid'],
            'relation_id' => $item['id'],
            'entid'       => 1,
            'year'        => now()->year,
            'day'         => now()->day,
            'month'       => now()->month,
            'week'        => now()->week,
        ]);
    }

    /**
     * 删除商机.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteOdds(int $id): int
    {
        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }
            app(ProductAssistService::class)->delete(['link_id' => $id, 'link_type' => CustomEnum::ODDS]);
            app(SubscribeService::class)->delete(['eid' => $id, 'types' => CustomEnum::ODDS]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return $res;
        });
    }

    /**
     * 处理分类ID.
     */
    public function handleCategoryIds(array $categoryIds): array
    {
        return array_map(function ($item) {
            return json_encode(array_map('strval', $item), JSON_UNESCAPED_UNICODE);
        }, $categoryIds);
    }

    /**
     * 转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function shift(array $ids, int $uid, int $toUid, int $contract = 0, int $invoice = 0): mixed
    {
        if ($toUid < 1) {
            throw $this->exception(__('common.empty.attr', ['attr' => '转移人ID']));
        }
        $service = app(OrderService::class);
        if ($contract) {
            $service->shift($service->column(['oid' => $ids], 'id'), $toUid, $invoice);
        }
        $list = $this->dao->select(['id' => $ids], ['id', 'uid'])?->toArray();
        if (! $list) {
            return true;
        }
        $adminService  = app(AdminService::class);
        $recordService = app(RecordService::class);
        $salesman      = $adminService->get($toUid, ['id', 'name'])?->toArray();
        if (! $salesman) {
            throw $this->exception('交接人员不存在');
        }
        foreach ($list as $customer) {
            $beforeSalesman = $adminService->get($customer['uid'], ['id', 'name']);
            $reason         = '此商机从“' . $beforeSalesman?->name . '”负责移交给“' . $salesman['name'] . '”负责';
            $recordService->saveRecord(ViewSearchEnum::VIEW_ODDS, [
                'eid'            => $customer['id'],
                'type'           => OddsEnum::OPERATE_SHIFT,
                'reason'         => $reason,
                'record_version' => 0,
                'uid'            => $toUid,
                'creator_uid'    => $uid,
            ]);
        }
        return $this->dao->search(['id' => $ids])->update(['uid' => $toUid]);
    }

    /**
     * 本人所属商机下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCurrentSelect(int $id, int $uid, array $where = []): array
    {
        $field = ['id as value', 'odds_no as label', 'odds_no as text', 'name', 'eid'];
        if (! $where) {
            $where = [
                'uid' => $uid,
                'eid' => $id ?: '',
            ];
        }
        $info = $id ? $this->dao->get(['id' => $id], ['id', 'uid', 'name', 'odds_no', 'eid'])?->toArray() : [];
        if ($info) {
            $list[] = ['value' => $info['id'], 'label' => $info['odds_no'], 'text' => $info['odds_no'], 'eid' => $info['eid'], 'disabled' => $info['uid'] < 1];
        } else {
            $list = $this->dao->getList($where, $field, 0, 0, 'id', callable: function ($list) use (&$id) {
                foreach ($list as $item) {
                    $item->disabled = false;
                    if ($item->value == $id) {
                        $id = 0;
                    }
                    $item['value'] = (int) $item['value'];
                }
            });
        }
        return $list;
    }

    /**
     * 修改商机状态.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateStatus(int $uid, int $id, int $status)
    {
        $info = $this->dao->get(['id' => $id], ['id', 'status']);
        if (! in_array($info->status, [1, 4]) || ! in_array($status, [1, 4])) {
            throw $this->exception(__('无效的商机状态,仅允许修改为：进行中、失效!'));
        }
        return $this->transaction(function () use ($id, $info, $uid, $status) {
            $dict = app(DictDataService::class)->column(['type_name' => 'odds_status'], 'name', 'value');
            app(RecordService::class)->saveRecord(
                ViewSearchEnum::VIEW_ODDS,
                [
                    'eid'            => $id,
                    'type'           => CustomEnum::OPERATE_CHANGE,
                    'uid'            => (int) ($info->uid ?? $uid),
                    'creator_uid'    => $uid,
                    'record_version' => 0,
                    'reason'         => '商机状态：由【' . ($dict[$info->status] ?: '空') . '】修改为【' . ($dict[$status] ?: '空') . '】',
                ]
            );
            $info->status = $status;
            return $info->save();
        });
    }

    private function checkRequiredProducts(array $products, FormService $formService, int $type, array $list): void
    {
        if (! $products && $formService->isFormFieldRequired($type, 'products', $list)) {
            throw $this->exception('请选择产品列表');
        }
    }

    /**
     * 获取列表搜索条件.
     */
    private function viewSearchWhere(array $where, int $uid): array
    {
        if (! isset($where['view_search'])) {
            unset($where['scope_frame']);
            return $where;
        }
        $scopeFrame = isset($where['scope_frame']) && $where['scope_frame'] ? (is_array($where['scope_frame']) ? end($where['scope_frame']) : $where['scope_frame']) : 'all';
        switch ((int) $where['view_search']) {
            case 1:// 我负责的
                $where['uid'] = $uid;
                break;
            case 2:// 我查看的
                $where = $this->applyScopeWhere($where, $uid, $scopeFrame);
                break;
            case 3:// 我关注的
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'concern';
                break;
            case 4:// 急需跟进
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'urgent_follow_up';
                break;
            case 10:// 我创建的
                $where['creator_uid'] = $uid;
                break;
        }
        unset($where['view_search'], $where['scope_frame']);
        return $where;
    }
}
