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

use App\Constants\CacheEnum;
use App\Constants\CustomerNoticeEnum;
use App\Constants\CustomEnum\ContractEnum;
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ConfigEnum;
use App\Constants\ScheduleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Constants\Warehouse\WarehouseEnum;
use App\Events\SystemMessageEvent;
use App\Http\Contract\Schedule\ScheduleInterface;
use App\Http\Dao\Customer\OrderDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\FormService;
use App\Http\Service\Message\MessageService;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\User\UserRemindLogService;
use App\Http\Service\Warehouse\WarehouseOutboundService;
use App\Task\message\StatusChangeTask;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Tuoluojiang\Open\utils\MessageType;
use Tuoluojiang\Open\utils\Statistics;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 订单Service.
 * @mixin OrderDao
 */
class OrderService extends BaseService
{
    use ResourceServiceTrait;
    use CustomerTrait;

    public $dao;

    public function __construct(OrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 字段过滤.
     * @return string[]
     */
    public function dictFilterField(): array
    {
        return [];
    }

    public function followUpField(): string
    {
        return 'contract_followed';
    }

    /**
     * 获取用户设置的搜索列表.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    public function searchField()
    {
        $field[] = ['statistics_type', ''];
        $field[] = ['types', ''];
        $field[] = ['uid', ''];
        $field[] = ['eid', ''];
        $field[] = ['oid', ''];

        $fieldSet = app(FormService::class)->getCustomDataByTypes(CustomEnum::CONTRACT, ['key as field', 'input_type']);
        $fieldSet = array_merge($fieldSet, ContractEnum::CONTRACT_SEARCH_FIELD, ContractEnum::CONTRACT_VIEWER_SEARCH_FIELD);
        foreach ($fieldSet as $value) {
            $field[] = [$value['field'], ''];
        }
        return $field;
    }

    /**
     * 保存合同订单.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveContract(array $data, int $uid = 0, array $products = [], ?string $recordReason = null): mixed
    {
        unset($data['contract_no']);
        $outboundWarehouseId = (int) ($data['outbound_warehouse_id'] ?? 0);
        if (array_key_exists('products', $data)) {
            $products = is_array($data['products']) ? $data['products'] : [];
            unset($data['products']);
        }

        $formService = app(FormService::class);
        $list        = $formService->getFormDataList(CustomEnum::CONTRACT);
        $this->checkRequiredProducts($products, $formService, CustomEnum::CONTRACT, $list);
        $this->checkRequiredOutboundWarehouse($outboundWarehouseId, $formService, CustomEnum::CONTRACT, $list);
        $formService->fieldValueCheck($data, CustomEnum::CONTRACT, 0, $list);

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
        $data['contract_customer'] = $this->normalizeRelationId($data['contract_customer'] ?? 0);
        $data['oid']               = $this->normalizeRelationId($data['oid'] ?? 0);
        if ($data['contract_customer']) {
            $data['eid'] = $data['contract_customer'];
            unset($data['contract_customer']);
        } else {
            throw $this->exception('请选择客户');
        }
        if (isset($data['contract_category'])) {
            $data['contract_category']                           = $data['contract_category'] ?: null;
            $data['contract_category'] && $data['contract_cate'] = is_array($data['contract_category']) ? '/' . implode('/', $data['contract_category']) . '/' : '/' . $data['contract_category'] . '/';
        }

        $attaches                                                 = array_filter($attaches);
        // contract_price 为非必填字段，MCP 等来源可能不传；管理端表单总会带上故此前未暴露.
        $data['contract_price']                                   = $data['contract_price'] ?? 0;
        $data['surplus']                                          = $data['contract_price'];
        isset($data['signing_status']) && $data['signing_status'] = (int) $data['signing_status'];
        $data['creator_uid']                                      = $data['uid'] = $uid;
        $data['contract_status']                                  = $this->getStatus($data);
        $data['contract_no']                                      = $this->getUniqueNo('DD');
        return $this->transaction(function () use ($products, $data, $attaches, $recordReason, $outboundWarehouseId) {
            $res = $this->dao->create($data);
            if (! $res) {
                throw $this->exception('保存失败');
            }
            if (! empty($data['oid'])) {
                app(OpportunityService::class)->updateOdds(['status' => 2], $data['oid']);
            }
            $products && app(ProductAssistService::class)->saveProducts($products, $res->id, CustomEnum::CONTRACT);

            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $res->id, 'relation_type' => 3]);
            }
            app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CONTRACT, [
                'eid'            => $res->id,
                'type'           => ContractEnum::OPERATE_CREATE,
                'uid'            => $data['uid'],
                'creator_uid'    => $data['creator_uid'],
                'record_version' => 0,
                'reason'         => $recordReason ?: '新添加订单“' . ($data['contract_name'] ?? '') . '”',
            ]);
            $this->createOutboundByRule((int) $res->id, $outboundWarehouseId, (int) $data['uid']);

            return $res;
        });
    }

    public function getSearchField()
    {
        return [
            ['eid', ''],
            ['name', '', 'name_like'],
            ['salesman_id', '', 'salesman'],
            ['time', '', 'created_at'],
            ['signing_status', ''],
            ['abnormal', '', 'contract_status'],
            ['view_search', 1],
        ];
    }

    /**
     * 修改合同订单.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function updateContract(array $data, int $id, array $products = [], int $creatorUid = 0): mixed
    {
        unset($data['contract_no']);
        unset($data['outbound_warehouse_id']);
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

            return $this->transaction(function () use ($products, $id, $info, $hasProductsField) {
                $totalPrice = app(ProductAssistService::class)->saveProducts($products, $id, CustomEnum::CONTRACT);
                if ($hasProductsField) {
                    $info->contract_price = $totalPrice;
                    $info->save();
                }
                return true;
            });
        }

        $formService = app(FormService::class);
        $list        = $formService->getFormDataList(ContractEnum::CONTRACT);
        $formService->fieldValueCheck($data, ContractEnum::CONTRACT, $id, $list);

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
        $recordData = $data;

        if (isset($data['contract_customer'])) {
            $data['eid'] = $data['contract_customer'];
            unset($data['contract_customer']);
        }
        if (isset($data['contract_category'])) {
            $data['contract_category']                           = $data['contract_category'] ?: null;
            $data['contract_category'] && $data['contract_cate'] = is_array($data['contract_category']) ? '/' . implode('/', $data['contract_category']) . '/' : '/' . $data['contract_category'] . '/';
        }
        if (isset($data['contract_price'])) {
            $data['contract_price'] = $data['contract_price'] ?: 0;
            if ($info->received >= $data['contract_price']) {
                $data['surplus'] = 0;
            } else {
                $data['surplus'] = bcsub((string) $data['contract_price'], $info->received, 2);
            }
        }
        $uid                                                      = $info->uid;
        $creatorUid                                               = $creatorUid ?: $uid;
        $attaches                                                 = array_filter($attaches);
        $data['contract_status']                                  = $info->is_abnormal ? '3' : $this->getStatus($data);
        isset($data['signing_status']) && $data['signing_status'] = (int) $data['signing_status'];
        $record                                                   = $this->getContractChangeRecord($info, $recordData, $list);
        return $this->transaction(function () use ($products, $id, $uid, $creatorUid, $data, $attaches, $record, $info, $hasProductsField, $shouldSaveProducts) {
            $res = $this->dao->update($id, $data);
            if (! $res) {
                throw $this->exception('更新失败');
            }
            if ($shouldSaveProducts) {
                $totalPrice = app(ProductAssistService::class)->saveProducts($products, $id, CustomEnum::CONTRACT);
                if ($hasProductsField) {
                    $info->contract_price = $totalPrice;
                    $info->save();
                }
            }

            // save relation attach
            if ($attaches) {
                app(AttachService::class)->update(['id' => $attaches], ['relation_id' => $id, 'relation_type' => 3]);
            }

            if (isset($data['contract_followed'])) {
                $status = $data['contract_followed'] < 1 ? 0 : 1;
                app(SubscribeService::class)->subscribe($uid, $id, $status);
            }
            $record && app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CONTRACT, [
                'eid'            => $id,
                'type'           => CustomEnum::OPERATE_CHANGE,
                'uid'            => $uid,
                'creator_uid'    => $creatorUid,
                'record_version' => 0,
                'reason'         => $record,
            ]);

            return $res;
        });
    }

    /**
     * 获取订单字段变更记录.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function getContractChangeRecord(mixed $info, array $data, array $list): string
    {
        return collect($list)->keyBy('key')->intersectByKeys($data)->filter(function ($fieldInfo, $fieldKey) use ($data, $info) {
            $inputType = strtolower($fieldInfo['input_type']);
            $type      = strtolower($fieldInfo['type']);
            if ($fieldKey === 'contract_followed') {
                return false;
            }
            $oldValue  = $fieldKey === 'contract_customer' ? $info->eid : $info->{$fieldKey};
            return $this->shouldRecordFieldChange($inputType) && ! $this->compareValues($oldValue, $data[$fieldKey], $type);
        })->map(function ($fieldInfo, $fieldKey) use ($data, $info) {
            $type      = strtolower($fieldInfo['type']);
            $inputType = strtolower($fieldInfo['input_type']);
            $oldValue  = $fieldKey === 'contract_customer' ? $info->eid : $info->{$fieldKey};
            $newValue  = $data[$fieldKey];
            if ($fieldInfo['dict_ident']) {
                $oldValue = $this->handleDictValue($fieldInfo['dict_ident'], $oldValue, $type, $inputType);
                $newValue = $this->handleDictValue($fieldInfo['dict_ident'], $newValue, $type, $inputType);
                if ($type === 'radio') {
                    $oldValue = $oldValue ? reset($oldValue) : '空';
                    $newValue = $newValue ? reset($newValue) : '空';
                } else {
                    $oldValue = $oldValue instanceof BaseModel ? $oldValue->name : ($oldValue ?: '空');
                    $newValue = $newValue instanceof BaseModel ? $newValue->name : ($newValue ?: '空');
                }
            }
            if ($type === 'singlemember') {
                $adminService = app(AdminService::class);
                $oldValue     = $oldValue ? $adminService->value(['id' => $oldValue], 'name') : '空';
                $newValue     = $newValue ? $adminService->value(['id' => $newValue], 'name') : '空';
            }
            if ($type === 'multiplemember') {
                $adminService = app(AdminService::class);
                $oldValue     = $oldValue ? collect($adminService->column(['id' => $oldValue], 'name'))->implode('、') : '空';
                $newValue     = $newValue ? collect($adminService->column(['id' => $newValue], 'name'))->implode('、') : '空';
            }
            if ($fieldKey === 'contract_customer') {
                $customerService = app(CustomerService::class);
                $oldValue        = $oldValue ? $customerService->dao->setTrashed()->value($oldValue, 'customer_name') : '空';
                $newValue        = $newValue ? $customerService->dao->setTrashed()->value($newValue, 'customer_name') : '空';
            }
            $oldValue = is_array($oldValue) ? implode(',', $oldValue) : $oldValue;
            $newValue = is_array($newValue) ? implode(',', $newValue) : $newValue;
            return ($fieldInfo['key_name'] ?? $fieldKey) . '：由【' . ($oldValue ?: '空') . '】修改为【' . ($newValue ?: '空') . '】';
        })->implode('; ');
    }

    /**
     * 合同订单详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo(int $id, int $uid): mixed
    {
        $info = $this->dao->get($id, with: ['product'])?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $attachField               = $this->getAttachField();
        $customerService           = app(CustomerService::class);
        $attachService             = app(AttachService::class);
        $dictDataService           = app(DictDataService::class);
        $adminService              = app(AdminService::class);
        $info['contract_followed'] = (string) app(SubscribeService::class)->getSubscribeStatus($uid, $id);

        $list = app(FormService::class)->getFormDataWithType(CustomEnum::CONTRACT, false);
        $oid  = $this->normalizeRelationId($info['oid'] ?? 0);
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], CustomEnum::SCENE_INFO);
                    if ($datum['dict_ident']) {
                        if (is_dimensional_data($datum['value'])) {
                            $datum['value'] = $this->handleDictValue($datum['dict_ident'], $datum['value'], $type);
                        } else {
                            $datum['value'] = $dictDataService->getNamesByValue($datum['dict_ident'], $datum['value']);
                        }
                    }
                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? [] : $attachService->select(['id' => $datum['value']], $attachField)?->toArray();
                    }
                }
                if ($datum['key'] == 'contract_customer') {
                    $datum['value'] = $info['eid'] ? $customerService->column(['id' => $info['eid']], 'customer_name') : '';
                }
                if ($datum['key'] == 'oid') {
                    $datum['value'] = $oid ? app(OpportunityService::class)->value(['id' => $oid], 'odds_no') : '';
                }
            }
        }

        return [
            'contract_price'    => $info['contract_price'],
            'contract_status'   => $info['contract_status'],
            'contract_customer' => $customerService->get($info['eid'], ['id', 'customer_name']),
            'salesman'          => $adminService->get(['id' => $info['uid']], ['id', 'avatar', 'name']),
            'list'              => $list,
            'product'           => $info['product'],
        ];
    }

    /**
     * 合同订单编辑表单数据.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getEditInfo(int $id, int $uid): mixed
    {
        $info = $this->dao->get($id, with: ['product'])?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $attachField               = $this->getAttachField();
        $attachService             = app(AttachService::class);
        $info['contract_followed'] = (string) app(SubscribeService::class)->getSubscribeStatus($uid, $id);
        $product                   = $info['product'];
        $oid                       = $this->normalizeRelationId($info['oid'] ?? 0);
        $list                      = app(FormService::class)->getFormDataWithType(CustomEnum::CONTRACT, platform: $this->getPlatform(), associationId: (int) $info['eid'], oddsId: (int) $oid);
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']], CustomEnum::SCENE_EDIT);
                    if ($inputType == 'member') {
                        $datum['options'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']]);
                    }
                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? [] : $attachService->select(['id' => $datum['value']], $attachField)?->toArray();
                    }
                }

                if ($datum['key'] == 'contract_customer') {
                    $datum['value'] = $info['eid'];
                }
                if ($datum['key'] == 'outbound_warehouse_id') {
                    $datum['disabled'] = true;
                }
            }
        }
        return compact('list', 'product');
    }

    /**
     * 列表统计
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getListStatistics(int $customType, string $uuid): array
    {
        $subscribeUid = $uid = uuid_to_uid($uuid);

        $uid = match ($customType) {
            ContractEnum::CONTRACT_VIEWER => app(ModulePermissionService::class)->getAccessibleUserIds($uid, ModuleEnum::CUSTOMER),
            default                       => $uid,
        };

        // 全部
        $total = $this->dao->count(['uid' => $uid]);
        // 未签约
        $notSigned = $this->dao->count(['uid' => $uid, 'signing_status' => 0]);
        // 已签约
        $signed = $this->dao->count(['uid' => $uid, 'signing_status' => 1]);
        // 签约作废
        $voidSigned = $this->dao->count(['uid' => $uid, 'signing_status' => 2]);
        // 我关注的
        $concern = app(SubscribeService::class)->contractCount($uid, $subscribeUid);
        // 过期合同订单
        $expired = $this->dao->count(['uid' => $uid, 'abnormal' => 2, 'signing_status_lt' => 2]);

        // 急需续费
        $urgentRenewal = $this->dao->getRenewalRemindCount(['uid' => $uid]);
        // 费用过期
        $costExpired = $this->dao->getRenewalRemindCount(['uid' => $uid], true);
        return [
            'total'          => $total,
            'concern'        => $concern,
            'not_signed'     => $notSigned,
            'signed'         => $signed,
            'void_signed'    => $voidSigned,
            'expired'        => $expired,
            'urgent_renewal' => $urgentRenewal,
            'cost_expired'   => $costExpired,
        ];
    }

    /**
     * 无需同步字段.
     * @return string[]
     */
    public function getOutOfSyncField(): array
    {
        return ['contract_customer', 'products'];
    }

    private function checkRequiredProducts(array $products, FormService $formService, int $type, array $list): void
    {
        if (! $products && $formService->isFormFieldRequired($type, 'products', $list)) {
            throw $this->exception('请选择产品列表');
        }
    }

    private function checkRequiredOutboundWarehouse(int $warehouseId, FormService $formService, int $type, array $list): void
    {
        if ($warehouseId <= 0 && $formService->isFormFieldRequired($type, 'outbound_warehouse_id', $list)) {
            throw $this->exception('请选择出库仓库');
        }
    }

    private function createOutboundByRule(int $orderId, int $warehouseId, int $uid): void
    {
        if ((int) sys_config(ConfigEnum::ORDER_AUTO_OUTBOUND_RULE['key'], 0) !== 1) {
            return;
        }
        if ($warehouseId <= 0) {
            return;
        }
        // 订单没有产品明细时无从生成出库单：createBill 会回查明细并抛「关联订单未找到商品信息」，
        // 在建单事务里连带订单一起回滚。产品明细在订单表单中是非必填项，这里跳过即可，
        // 出库单留给用户后续手动补开。判断口径与 WarehouseOutboundService::sourceProducts 一致。
        $hasProducts = (int) app(ProductAssistService::class)->count([
            'link_type' => CustomEnum::CONTRACT,
            'link_id'   => $orderId,
        ]);
        if ($hasProducts <= 0) {
            return;
        }
        app(WarehouseOutboundService::class)->createBill([
            'warehouse_id'  => $warehouseId,
            'outbound_type' => WarehouseEnum::OUTBOUND_TYPE_SALES,
            'source_type'   => WarehouseEnum::SOURCE_ORDER,
            'source_id'     => $orderId,
        ], $uid, false);
    }

    public function outbound(int $id, int $uid): int
    {
        $order = $this->dao->get($id, ['id', 'outbound_warehouse_id', 'received', 'surplus']);
        if (! $order) {
            throw $this->exception('订单不存在');
        }

        $outboundService = app(WarehouseOutboundService::class);
        $outboundService->assertOrderCanOutbound($order);
        $bill = $outboundService->get([
            'source_type' => WarehouseEnum::SOURCE_ORDER,
            'source_id'   => $id,
            'status'      => [WarehouseEnum::OUTBOUND_STATUS_PENDING, WarehouseEnum::OUTBOUND_STATUS_COMPLETED],
        ], ['id', 'status']);

        $billId = (int) ($bill->id ?? 0);
        if (! $billId) {
            $warehouseId = (int) $order->outbound_warehouse_id;
            if ($warehouseId <= 0) {
                throw $this->exception('请选择出库仓库');
            }
            $billId = $outboundService->createBill([
                'warehouse_id'  => $warehouseId,
                'outbound_type' => WarehouseEnum::OUTBOUND_TYPE_SALES,
                'source_type'   => WarehouseEnum::SOURCE_ORDER,
                'source_id'     => $id,
            ], $uid);
        }

        if ((int) ($bill->status ?? WarehouseEnum::OUTBOUND_STATUS_PENDING) !== WarehouseEnum::OUTBOUND_STATUS_COMPLETED) {
            $outboundService->pick($billId, $uid);
        }

        return $billId;
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelectList(array|int $eid, string $uuid): array
    {
        $userIds = app(ModulePermissionService::class)->getAccessibleUserIds(uuid_to_uid($uuid), ModuleEnum::CUSTOMER);
        return $this->dao->getList(['eid' => $eid, 'uid' => $userIds], ['id', 'eid', 'contract_name as title', 'contract_no'], 0, 0, 'id');
    }

    /**
     * 移动端详情.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUniInfo(int $id, int $uid): array
    {
        $info = $this->dao->get($id)?->toArray();
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        $attachField               = $this->getAttachField();
        $attachService             = app(AttachService::class);
        $subscribeService          = app(SubscribeService::class);
        $info['contract_followed'] = (string) $subscribeService->getSubscribeStatus($uid, $id);
        $dictValue                 = collect();
        $list                      = app(FormService::class)->getFormDataWithType(CustomEnum::CONTRACT, false);
        $oid                       = $this->normalizeRelationId($info['oid'] ?? 0);
        foreach ($list as &$item) {
            foreach ($item['data'] as &$datum) {
                if (array_key_exists($datum['key'], $info)) {
                    $type           = strtolower($datum['type']);
                    $inputType      = strtolower($datum['input_type']);
                    $datum['value'] = $this->handleFieldValue($inputType, $type, $info[$datum['key']]);
                    if ($datum['dict_ident']) {
                        $datum['value'] = $this->handleDictValue($datum['dict_ident'], $datum['value'], $type, $inputType);
                    }
                    if (in_array($inputType, ['file', 'images'])) {
                        $datum['files'] = empty($datum['value']) ? [] : $attachService->select(['id' => $datum['value']], $attachField)?->toArray();
                    }
                }
                if ($datum['key'] == 'contract_customer') {
                    $datum['value'] = app(CustomerService::class)->value(['id' => $info['eid']], 'customer_name');
                }
                if ($datum['key'] == 'oid') {
                    $datum['value'] = $oid ? app(OpportunityService::class)->value(['id' => $oid], 'odds_no') : '';
                }
                $dictValue->put($datum['key'], $datum['value']);
            }
        }
        $field = ['id', 'uid', 'contract_name', 'contract_status', 'contract_customer', 'start_date', 'end_date', 'created_at'];
        $data  = collect();
        collect($field)->map(function ($item) use ($info, $dictValue, &$data) {
            if ($dictValue->has($item)) {
                $data->put($item, match ($item) {
                    'contract_customer' => app(CustomerService::class)->get(['id' => $info['eid']], ['id', 'customer_name']),
                    default             => $dictValue->get($item),
                });
            } else {
                $data->put($item, match ($item) {
                    'contract_customer' => app(CustomerService::class)->get(['id' => $info['eid']], ['id', 'customer_name']),
                    default             => $info[$item],
                });
            }
            return $item;
        })->all();
        $data->put('list', $list);
        return $data->all();
    }

    /**
     * 获取合同订单缓存个数.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getClientContractCountCache(): int
    {
        $ttl = (int) sys_config('system_cache_ttl', 3600);
        return (int) Cache::tags([CacheEnum::TAG_CUSTOMER])->remember('client_contract_count', $ttl, function () {
            $this->dao->getClientContractCount();
        });
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
        $exists = $userRemindLogService->exists([
            'entid'       => 1,
            'user_id'     => $item['card']['user_id'],
            'year'        => now()->year,
            'month'       => now()->month,
            'day'         => now()->day,
            'remind_type' => $type,
            'relation_id' => $item['id'],
        ]);

        if ($exists) {
            return;
        }

        $message = app(MessageService::class)->getMessageContent(1, $type);
        if ($message['template_time']) {
            if ($message['remind_time'] && date('H:i') == $message['remind_time']) {
                $res = true;
            } else {
                $res = false;
            }
        } else {
            $res = true;
        }

        if (! $res) {
            return;
        }
        event(new SystemMessageEvent(
            type: $type,
            params: [
                '合同订单名称'     => $item['title'] ?? '',
                '合同订单金额'     => $item['price'] ?? '',
                '合同订单开始时间' => $item['start_date'] ?? '',
                '合同订单结束时间' => $item['end_date'] ?? '',
                '客户名称'         => $item['name'] ?? '',
                '业务员'           => $item['card']['name'] ?? '',
            ],
            receiverIds: (int) $item['card']['user_id'],
            other: ['id' => $item['id']],
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
     * 异常状态.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function abnormal(int $id, int $status, string $uuid): bool
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据获取异常');
        }

        if ($status !== 0) {
            $info->is_abnormal     = 1;
            $info->contract_status = 3;
            $info->save();
        } else {
            $info->is_abnormal = 0;
            // reload contract status
            $this->reloadStatus($id, $info);
        }
        return true;
    }

    /**
     * 未回款金额.
     * @throws BindingResolutionException
     */
    public function getRingRatioUncollected(array $userIds, array $ids = []): array
    {
        $ratio = 0;
        $where = array_merge(['uid' => $userIds, 'pay_status' => 0, 'signing_status_lt' => 2], $ids ? ['id' => $ids] : []);
        $price = sprintf('%.2f', $this->dao->sum($where, 'surplus'));
        return compact('price', 'ratio');
    }

    /**
     * 删除合同订单.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function deleteContract(int $id): int
    {
        if (app(PaymentService::class)->count(['cid' => $id, 'entid' => 1, 'status' => 1])) {
            throw $this->exception('当前合同订单存在审核通过的付款/支出数据, 不能删除');
        }

        if (app(InvoiceService::class)->count(['cid' => $id, 'entid' => 1])) {
            throw $this->exception('当前合同订单存在发票, 不能删除');
        }

        return $this->transaction(function () use ($id) {
            $res = $this->dao->delete($id);
            if (! $res) {
                throw $this->exception(__('common.delete.fail'));
            }

            $billService = app(PaymentService::class);
            $linkIds     = array_merge([$id], $billService->column(['cid' => $id, 'entid' => 1], 'id'));
            $billService->delete($id, 'cid');
            Task::deliver(new StatusChangeTask(CustomerNoticeEnum::CONTRACT_DELETE_NOTICE, CustomerNoticeEnum::CONTRACT_DELETE, 1, $linkIds));
            app(ScheduleInterface::class)->delScheduleByLinkId($id, [ScheduleEnum::TYPE_CLIENT_RENEW, ScheduleEnum::TYPE_CLIENT_RETURN]);
            app(SubscribeService::class)->delete(['eid' => $id, 'types' => CustomEnum::CONTRACT]);
            Cache::tags([CacheEnum::TAG_CUSTOMER])->flush();
            return $res;
        });
    }

    /**
     * 新增合同订单统计（按创建时间统计）
     * @throws BindingResolutionException
     */
    public function getRingRatioCount(string $searchTime, array $userIds, string $ratioTime = '', array $ids = []): array
    {
        $ratio   = 0;
        $idWhere = $ids ? ['id' => $ids] : [];
        $count   = $this->dao->count(array_merge(['uid' => $userIds, 'created_at' => $searchTime], $idWhere));
        if (! $ratioTime) {
            return compact('count', 'ratio');
        }
        $ratioCount = $this->dao->count(array_merge(['uid' => $userIds, 'created_at' => $ratioTime], $idWhere));
        $ratio      = Statistics::ringRatio($count, $ratioCount);
        return compact('count', 'ratio');
    }

    /**
     * 新增合同订单金额统计（按创建时间统计）
     * @throws BindingResolutionException
     */
    public function getNewAddRingRatio(string $searchTime, array $userIds, string $ratioTime, array $ids = []): array
    {
        $field   = 'contract_price';
        $idWhere = $ids ? ['id' => $ids] : [];
        $price   = sprintf('%.2f', $this->dao->sum(array_merge($idWhere, ['uid' => $userIds, 'created_at' => $searchTime]), $field));
        $ratio   = Statistics::ringRatio($price, $this->dao->sum(array_merge($idWhere, ['uid' => $userIds, 'created_at' => $ratioTime]), $field));
        return compact('price', 'ratio');
    }

    /**
     * 合同订单类型分析统计.
     *
     * @param string $time 时间区间
     * @param array $userIds 用户ID列表
     * @param array $categoryIds 分类ID列表
     * @param int $categoryId 指定分类ID
     * @return array 饼状图数据
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getCategoryRank(string $time, array $userIds, array $categoryIds = [], int $categoryId = 0): array
    {
        if (! empty($categoryIds)) {
            $categoryIds = $this->handleCategoryIds($categoryIds);
        }
        if (! $categoryIds) {
            $categoryIds = app(DictDataService::class)->getDefaultContractCategory();
        }
        if (empty($categoryIds)) {
            return [];
        }

        // 按层级分组：categoryId=0 按第一层，categoryId>0 按该分类的子分类
        // 返回 [[parentId, [jsonStr1, jsonStr2, ...]], ...]
        $groupedIds = $this->getLevelIdByCategoryIds($categoryIds, $categoryId);
        if (empty($groupedIds)) {
            return [];
        }

        // 提取分组键列表（用于 DAO 筛选），确保为字符串
        $groupKeys = array_map(fn ($item) => (string) $item[0], $groupedIds);

        // 一次性获取所有分类的统计数据
        $allStats = $this->dao->getCategoryStatistics($time, $userIds, $groupKeys);
        if (empty($allStats)) {
            return [];
        }

        // 将 $allStats 的键归一化（解码再编码确保格式一致）
        $allStats = array_combine(
            array_map(function ($key) {
                $decoded = json_decode($key, true);
                return $decoded !== null
                    ? json_encode(array_map('strval', $decoded), JSON_UNESCAPED_UNICODE)
                    : (string) $key;
            }, array_keys($allStats)),
            array_values($allStats)
        );

        // 获取字典表中的分类名称
        $dictField = ['name as category_name', 'value as category_id'];
        $dictWhere = ['dict_value' => $groupKeys, 'type_name' => 'contract_type'];
        $dictList  = app(DictDataService::class)->select($dictWhere, $dictField)->keyBy('category_id');

        // 按分组聚合统计数据
        $result = [];
        foreach ($groupedIds as [$parentId, $categoryJsonList]) {
            $categoryName = $dictList[$parentId]['category_name'] ?? (string) $parentId;
            $totalPrice   = '0.00';
            $totalCount   = 0;
            $totalExpend  = '0.00';

            // 遍历 JSON 字符串列表，找到对应的统计数据
            foreach ($categoryJsonList as $categoryJson) {
                if (isset($allStats[$categoryJson])) {
                    $stats      = $allStats[$categoryJson];
                    $totalPrice = bcadd($totalPrice, $stats['price'], 2);
                    $totalCount += $stats['count'];
                    $totalExpend = bcadd($totalExpend, $stats['expend'], 2);
                }
            }

            // 过滤掉金额为0的分类
            if (bccomp($totalPrice, '0.01', 2) >= 0) {
                $result[] = [
                    'category_name' => $categoryName,
                    'category_id'   => $parentId,
                    'price'         => $totalPrice,
                    'count'         => $totalCount,
                    'expend'        => $totalExpend,
                ];
            }
        }

        // 按金额降序排序
        $price = array_column($result, 'price');
        array_multisort($price, SORT_DESC, $result);

        return $result;
    }

    /**
     * 产品分类业绩统计.
     *
     * @param string $time 时间区间
     * @param array $userIds 用户ID列表
     * @param array $categoryIds 分类ID列表
     * @param int $categoryId 指定分类ID
     * @return array 饼状图数据
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getProductCategoryRank(string $time, array $userIds, array $categoryIds = [], int $categoryId = 0): array
    {
        $categoryIds = $categoryId ? [$categoryId] : $this->normalizeProductCategoryIds($categoryIds);
        // 获取产品分类列表
        $categoryList = app(ProductCategoryService::class)->select(
            $categoryIds ? ['id' => $categoryIds] : [],
            ['id', 'pid', 'name']
        )->keyBy('id');

        if ($categoryList->isEmpty()) {
            return [];
        }

        // 按 pid 分组：parentId => [categoryId1, categoryId2, ...]
        $groupedIds = [];
        foreach ($categoryList as $cat) {
            $catId = $cat['id'];
            $pid   = $cat['pid'] ?? 0;
            // pid=0 表示顶级分类，按自身分组
            $parentId = $pid == 0 ? $catId : $pid;
            if (! isset($groupedIds[$parentId])) {
                $groupedIds[$parentId] = [];
            }
            $groupedIds[$parentId][] = $catId;
        }
        if (empty($groupedIds)) {
            return [];
        }
        // 一次性获取所有分类的统计数据（按分类ID统计）
        $allStats = $this->dao->getProductCategoryStatistics($time, $userIds, $categoryIds);
        if (empty($allStats)) {
            return [];
        }

        // 按分组聚合统计数据
        $result = [];
        foreach ($groupedIds as $parentId => $catIds) {
            $categoryName = $categoryList[$parentId]['name'] ?? (string) $parentId;
            $totalPrice   = '0.00';
            $totalCount   = 0;
            $totalExpend  = '0.00';

            // 聚合该分组下所有分类的统计数据
            foreach ($catIds as $catId) {
                if (isset($allStats[$catId])) {
                    $stats      = $allStats[$catId];
                    $totalPrice = bcadd($totalPrice, $stats['price'], 2);
                    $totalCount += $stats['count'];
                    $totalExpend = bcadd($totalExpend, $stats['expend'], 2);
                }
            }

            // 过滤掉金额为0的分类
            if (bccomp($totalPrice, '0.01', 2) >= 0) {
                $result[] = [
                    'category_name' => $categoryName,
                    'category_id'   => $parentId,
                    'price'         => $totalPrice,
                    'count'         => $totalCount,
                    'expend'        => $totalExpend,
                ];
            }
        }

        // 按金额降序排序
        $price = array_column($result, 'price');
        array_multisort($price, SORT_DESC, $result);

        return $result;
    }

    /**
     * 获取产品业绩排行列表（按规格维度，带分页）.
     *
     * @param string $time 时间区间
     * @param array $userIds 用户ID列表
     * @param array $categoryIds 分类ID列表
     * @return array{list: array, count: int}
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getProductRankList(string $time, array $userIds, array $categoryIds = []): array
    {
        [$page,$limit] = $this->getPageValue();
        $categoryIds   = $this->normalizeProductCategoryIds($categoryIds);
        return $this->dao->getProductRankList($time, $userIds, $categoryIds, $page, $limit);
    }

    /**
     * 获取分类等级.
     * 返回 [[parentId字符串, [jsonStr1, jsonStr2, ...]], ...]
     * 不使用 PHP 关系数组键存储 parentId，避免数字字符串键被强制转为整数。
     */
    public function getLevelIdByCategoryIds(array $categoryIds, int $categoryId = 0): array
    {
        // 用前缀键绕过 PHP 数组键自动转整数：'cat_110' 而非 '110'
        $map = [];
        foreach ($categoryIds as $item) {
            $tmp = json_decode($item, true);
            if ($categoryId == 0) {
                $groupKey = (string) $tmp[0];
            } else {
                $index = array_search((string) $categoryId, $tmp);
                if ($index === false || ! isset($tmp[$index + 1])) {
                    continue;
                }
                $groupKey = (string) $tmp[$index + 1];
            }
            $prefixedKey = 'cat_' . $groupKey;
            if (! isset($map[$prefixedKey])) {
                $map[$prefixedKey] = [];
            }
            $map[$prefixedKey][] = $item;
        }
        // 转为索引数组，去掉前缀
        $result = [];
        foreach ($map as $prefixedKey => $jsonList) {
            $groupKey = substr($prefixedKey, 4); // 去掉 'cat_' 前缀
            $result[] = [$groupKey, $jsonList];
        }
        return $result;
    }

    /**
     * 业绩统计
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function performanceStatistics(string $searchTime, array $userIds, string $ratioTime = '', array $categoryIds = []): array
    {
        $ids         = [];
        $categoryIds = $this->getStatisticsCategoryIds($categoryIds);
        if ($categoryIds) {
            $ids = array_unique($this->dao->column(['contract_category' => $categoryIds], 'id'));
        }

        return [
            'new_contract'       => $this->getRingRatioCount($searchTime, $userIds, $ratioTime, $ids),
            'new_contract_price' => $this->getNewAddRingRatio($searchTime, $userIds, $ratioTime, $ids),
            'uncollected_price'  => $this->getRingRatioUncollected($userIds, $ids),
        ];
    }

    /**
     * 处理分类ID.
     */
    public function handleCategoryIds(array $categoryIds): array
    {
        return array_map(function ($item) {
            $item = is_array($item) ? $item : [$item];
            return json_encode(array_map('strval', $item), JSON_UNESCAPED_UNICODE);
        }, $categoryIds);
    }

    /**
     * 处理产品分类ID.
     */
    protected function normalizeProductCategoryIds(array $categoryIds): array
    {
        return collect($categoryIds)
            ->map(fn ($item) => is_array($item) ? collect($item)->flatten()->last() : $item)
            ->filter(fn ($item) => $item !== '' && $item !== null)
            ->map(fn ($item) => (int) $item)
            ->filter(fn ($item) => $item > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 合同订单状态定时任务.
     * @throws BindingResolutionException
     */
    public function statusTimer(): void
    {
        $now = Carbon::now(config('app.timezone'))->toDateString();
        $this->dao->update(['start_date_gt' => $now, 'contract_status_lt' => '2'], ['contract_status' => '1']);
        $this->dao->update(['end_date_lt' => $now, 'contract_status_lt' => '3'], ['contract_status' => '2']);
    }

    /**
     * 重载合同订单状态
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function reloadStatus(int $id, mixed $contract = null): void
    {
        $contract = $contract ?: $this->dao->get($id);
        if ($contract->is_abnormal) {
            $contract->contract_status = '3';
        } else {
            $status = '1';
            $tz     = config('app.timezone');
            $now    = Carbon::now($tz)->toDateString();
            if ($contract->start_date && Carbon::parse($contract->start_date, $tz)->gt($now)) {
                $status = '0';
            }

            if ($contract->end_date && Carbon::parse($contract->end_date, $tz)->lt($now)) {
                $status = '2';
            }
            $contract->contract_status = $status;
        }

        $contract->save();
    }

    /**
     * 获取分类筛选数据.
     */
    public function getStatisticsCategoryIds(array $categoryIds = []): array
    {
        return collect($categoryIds)->map(function ($item) {
            return collect($item)->last();
        })->filter()->unique()->all();
    }

    /**
     * 转移.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function shift(array $ids, int $toUid, int $invoice = 0): mixed
    {
        if ($toUid < 1) {
            throw $this->exception(__('common.empty.attr', ['attr' => '转移人ID']));
        }
        return $this->transaction(function () use ($ids, $toUid, $invoice) {
            if ($invoice) {// 转移发票
                app(InvoiceService::class)->search(['cid' => $ids])->update(['uid' => $toUid]);
            }

            app(PaymentService::class)->search(['cid' => $ids])->update(['uid' => $toUid]);
            return $this->dao->search(['id' => $ids])->update(['uid' => $toUid]);
        });
    }

    /**
     * 导入
     * TODO:待优化.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function batchImport(array $data, array $uids): mixed
    {
        $uid      = auth('admin')->id();
        $required = $fieldMap = $fieldNameKeyMap = [];
        $fields   = app(FormService::class)->getExportField(CustomEnum::CONTRACT);
        foreach ($fields as $field) {
            $fieldMap[$field['key']]             = $field;
            $fieldNameKeyMap[$field['key_name']] = $field['key'];

            if ($field['required']) {
                $required[] = $field['key_name'];
            }
        }

        $adminService    = app(AdminService::class);
        $customerService = app(CustomerService::class);

        // 业务员
        $salesmanMap = $adminService->column(['id' => $uids, 'name_eq' => array_column($data, '业务员')], 'id', 'name');
        // 客户
        $customerMap = $customerService->column(['uid' => $uids, 'name_eq' => array_column($data, '客户名称')], 'id', 'customer_name');
        return $this->transaction(function () use ($data, $fieldNameKeyMap, $fieldMap, $customerMap, $salesmanMap, $uid, $uids) {
            foreach ($data as $index => $customer) {
                $insert   = [];
                $isCreate = false;
                foreach ($customer as $key => $item) {
                    $key = trim($key, '"');
                    if (! isset($fieldNameKeyMap[$key])) {
                        if ($key == '业务员') {
                            $insert['uid'] = $salesmanMap[$item] ?? $uid;
                        } elseif ($key == 'ID') {
                            $insert['id'] = max((int) $item, 0);
                            if ($insert['id'] > 0 && ! $this->dao->exists(['id' => $insert['id'], 'uid' => $uids])) {
                                $insert['id'] = 0;
                            }
                        }

                        continue;
                    }

                    $value     = $item;
                    $field     = $fieldNameKeyMap[$key];
                    $formField = $fieldMap[$field] ?? [];

                    if ($key == '客户名称') {
                        if (! isset($customerMap[$value])) {
                            break;
                        }
                        $insert['eid'] = $customerMap[$value];
                        continue;
                    }

                    if ($field == 'start_date' || $field == 'end_date') {
                        try {
                            Carbon::parse($value);
                        } catch (\Throwable $e) {
                            throw $this->exception('第' . ($index + 1) . '行数据' . ['start_date' => '开始', 'end_date' => '结束'][$field] . '时间格式无法解析');
                        }
                    }

                    // 字典
                    if ($formField && $formField['dict_ident']) {
                        $value = app(DictDataService::class)->getValuesByType($item, $formField['dict_ident'], $formField['input_type'], $formField['type']);
                        if ($formField['input_type'] == 'select' && $formField['type'] == 'single') {
                            if ($formField['dict_ident'] !== 'area_cascade') {
                                $value = $value[0] ?? '';
                            }
                        }
                    }

                    if ($field == 'contract_status' && is_array($value)) {
                        $value                                = intval($value[0] ?? 0);
                        $value == 3 && $insert['is_abnormal'] = 1;
                    }
                    $insert[$field] = $value;
                }

                if (! isset($insert['eid']) || $insert['eid'] < 1) {
                    continue;
                }

                if (isset($insert['contract_status'])
                    && $insert['contract_status'] < 3
                    && isset($insert['start_date'], $insert['end_date'])
                ) {
                    $insert['contract_status'] = $this->getStatus([
                        'contract_status' => $insert['contract_status'],
                        'start_date'      => $insert['start_date'],
                        'end_date'        => $insert['end_date'],
                    ]);
                }

                // 找不到则新增
                if (! isset($insert['id']) || $insert['id'] < 1) {
                    $isCreate              = true;
                    $insert['creator_uid'] = $uid;
                    $insert['contract_no'] = $this->getUniqueNo('DD');
                }

                if (! isset($insert['uid'])) {
                    $insert['uid'] = $uid;
                }

                $followed = $insert['contract_followed'] ?? false;
                if ($isCreate) {
                    foreach ($insert as $field => $value) {
                        if (is_array($value)) {
                            $insert[$field] = json_encode($value);
                        }
                    }

                    $res = $this->dao->create($insert);
                    if (! $res) {
                        throw $this->exception(__('common.insert.fail'));
                    }

                    if ($followed !== false) {
                        app(SubscribeService::class)->subscribe($uid, $res->id, $followed < 1 ? 0 : 1);
                    }
                } else {
                    if (isset($insert['contract_status'])) {
                        unset($insert['contract_status']);
                    }

                    $id = $insert['id'];
                    unset($insert['id']);
                    $res = $this->dao->update($id, $insert);
                    if (! $res) {
                        throw $this->exception(__('common.operation.fail'));
                    }

                    if ($followed !== false) {
                        app(SubscribeService::class)->subscribe($uid, $id, $followed < 1 ? 0 : 1);
                    }
                }
            }
            return true;
        });
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
            case 4:// 已签约
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'signed';
                break;
            case 5:// 未签约
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'not_signed';
                break;
            case 6:// 签约作废
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'void_signed';
                break;
            case 7:// 签约过期
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'expired';
                break;
            case 8:// 急需续费
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'urgent_renewal';
                break;
            case 9:// 费用过期
                $where                    = $this->applyScopeWhere($where, $uid, $scopeFrame);
                $where['statistics_type'] = 'cost_expired';
                break;
            case 10:// 我创建的
                $where['creator_uid'] = $uid;
                break;
        }
        unset($where['view_search'], $where['scope_frame']);
        return $where;
    }

    /**
     * 获取表单合同订单状态
     */
    private function getStatus(array $data)
    {
        $now       = now();
        $startTime = $endTime = null;
        $status    = $data['contract_status'] ?? '1';
        if (isset($data['start_date']) && $data['start_date']) {
            $startTime = Carbon::parse($data['start_date']);
            if ($startTime->gt($now)) {
                $status = '0';
            }

            if ($startTime->lt($now)) {
                $status = '1';
            }
        }

        if (isset($data['end_date']) && $data['end_date']) {
            $endTime = Carbon::parse($data['end_date']);
            if ($endTime->lt($now)) {
                $status = '2';
            }
        }

        if ($startTime && $endTime && $startTime->gt($endTime)) {
            throw $this->exception('结束时间不能早于开始时间');
        }
        return $status;
    }

    /**
     * 获取自定义表单是否启用订单分类.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getCategoryEnabled(): bool
    {
        $formList = app(FormService::class)->getFormDataList(CustomEnum::CONTRACT, field: ['id', 'key', 'status']);

        return collect($formList)->contains('key', 'contract_category');
    }
}
