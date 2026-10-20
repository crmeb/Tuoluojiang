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
use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\SignEnum;
use App\Constants\System\ConfigEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Dao\Customer\ContractDao;
use App\Http\Dao\Customer\ContractSignatoryDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Approve\ApproveApplyService;
use App\Http\Service\Approve\ApproveFormService;
use App\Http\Service\Approve\ApproveProcessService;
use App\Http\Service\Approve\ApproveUserService;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\SmsService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Webpatser\Uuid\Uuid;

/**
 * 合同Service.
 * @mixin ContractDao
 */
class ContractService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    private ContractSignatoryDao $signatoryDao;

    public function __construct(ContractDao $dao, ContractSignatoryDao $signatoryDao)
    {
        $this->dao          = $dao;
        $this->signatoryDao = $signatoryDao;
    }

    public function getList(array $where, array $field = ['*'], $sort = ['id'], array $with = ['admin', 'signatory', 'customer']): array
    {
        $where          = $this->viewSearchWhere($where, auth('admin')->id());
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with) ?? [];
        $priceMap       = $this->getContractPriceMap($list);
        $list           = collect($list)->map(function ($item) use ($priceMap) {
            $item['fail_days'] = 0;
            if ($item['start_date'] && Carbon::make($item['start_date'])->isAfter(now()->startOfDay())) {
                $item['fail_status'] = 1;
            } elseif ($item['end_date'] && Carbon::make($item['end_date'])->isBefore(now()->startOfDay())) {
                $item['fail_status'] = 2;
            } else {
                if ($item['end_date'] && Carbon::make($item['end_date'])->isAfter(now()->startOfDay())) {
                    $item['fail_days'] = Carbon::make($item['end_date'])->diffInDays(now()->startOfDay());
                }
                $item['fail_status'] = 0;
            }
            $item['contract_price'] = $priceMap[$item['id'] ?? 0] ?? '0.00';
            return $item;
        })->all();
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    public function resourceSave(array $data)
    {
        if ($data['sign_type'] == 2 && ! $data['file_id']) {
            throw $this->exception('请上传合同文件');
        }
        $uid     = auth('admin')->id();
        $process = $data['processInfo'];
        $product = $data['productInfo'];
        unset($data['processInfo'], $data['productInfo']);
        $adminService = app(AdminService::class);
        $formService  = app(ApproveFormService::class);
        $approveId    = (int) sys_config('contract_sign_switch');
        return $this->transaction(function () use ($data, $adminService, $uid, $approveId, $formService, $process, $product) {
            $signatory = $data['signatory'];
            unset($data['signatory']);
            // 根据 link_type 决定存储位置：link_type=2 存入 cid，link_type=5 存入 oid
            $cidValue = $data['cid'] ? array_values(array_map('strval', $data['cid'])) : null;
            if (($data['link_type'] ?? CustomEnum::CONTRACT) == CustomEnum::ODDS) {
                // 商机关联：存入 oid，清空 cid
                $data['oid'] = $cidValue ?: null;
                $data['cid'] = null;
            } else {
                // 订单关联：存入 cid，清空 oid
                $data['cid'] = $cidValue ?: null;
                $data['oid'] = null;
            }
            $data['start_date'] = $data['start_date'] ?: null;
            $data['end_date']   = $data['end_date'] ?: null;
            if (! $data['date_count']) {
                $data['date_count'] = $data['term_type'] == 1 && $data['start_date'] && $data['end_date'] ? Carbon::make($data['start_date'])->diffInDays(Carbon::make($data['end_date'])) : 0;
            }
            $data['uid']         = $uid;
            $data['is_verify']   = $approveId ? 1 : 0;
            $data['status']      = $approveId ? SignEnum::STATUS_WAIT_APPROVE : SignEnum::STATUS_WAIT_SIGN;
            $data['sign_status'] = $data['sign_type'] == 2 ? SignEnum::FLOW_STATUS_INIT : '';
            $data['doc_no']      = 'SN' . (int) round(microtime(true) * 1000);
            $res                 = $this->dao->create($data);
            app(ProductAssistService::class)->saveProducts($product, $res->id, CustomEnum::DOC);
            $signInsert = [];
            foreach ($signatory as $value) {
                if ($value['types'] && empty($value['name'])) {
                    throw $this->exception('请填写合同经办人');
                }
                $admin        = $value['user_id'] ? $adminService->get($value['user_id'], ['name', 'e_userid', 'e_openid'])?->toArray() : [];
                $signInsert[] = [
                    'cid'          => $res->id,
                    'name'         => $value['types'] ? $value['name'] : $admin['name'],
                    'phone'        => $value['phone'],
                    'user_id'      => $value['user_id'],
                    'company_name' => $value['company_name'],
                    'types'        => $value['types'],
                    'e_userid'     => $value['types'] ? '' : $admin['e_userid'],
                    'e_openid'     => $value['types'] ? '' : $admin['e_openid'],
                ];
            }
            $signInsert && $this->signatoryDao->insert($signInsert);
            if ($res && $data['sign_file']) {
                app(AttachService::class)->saveRelation((string) ($data['sign_file']['id'] ?? 0), uid_to_uuid($uid), $res->id, AttachEnum::RELATION_TYPE_SIGN);
            }
            if ($approveId) {
                $data['signatory']    = $signInsert;
                $data['product_info'] = $this->formatApprovalProductInfo($product);
                $data['term_type']    = $data['term_type'] ? ($data['term_type'] > 1 ? '签约日起算' : '固定期限') : '无期限';
                $form                 = collect($formService->getUniques($approveId) ?? [])
                    ->map(function ($unique) use ($data, $formService) {
                        $symbol = Str::snake($unique['symbol']);
                        if (! isset($data[$symbol])) {
                            return [];
                        }
                        if (! empty($unique['children'])) {
                            $childData = collect($data[$symbol])->map(fn ($datum) => $formService->processFormChildren($datum, $unique['children']))->filter()->all();
                            return [$unique['value'] => $childData];
                        }
                        return [$unique['value'] => $data[$symbol]];
                    })->filter()->collapse()->all();
                $res->approve_id = app(ApproveApplyService::class)->saveForm($form, $process, $approveId, 0, $uid);
                $res->save();
            }
            $data['sign_type'] == 2 && $this->addSignProcess((int) $res->id, needSignReview: (bool) $approveId);
            return $res;
        });
    }

    public function resourceUpdate($id, array $data)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('数据不存在');
        }
        $uid = auth('admin')->id();
        switch ($info->status) {
            case 1:
                app(ApproveApplyService::class)->revokeApply($info->approve_id, $uid);
                break;
            case 2:
                if ($info->sign_type == 2) {
                    app(SmsService::class)->cancelSignatureOrder($info->signature_sn, '用户取消合同签约');
                }
                break;
            case 3:
                throw $this->exception('合同已签约,无法撤销');
        }
        if ($info->signature_sn) {
            app(SmsService::class)->cancelSignatureOrder($info->signature_sn, '用户取消合同签约');
        }
        $adminService = app(AdminService::class);
        $formService  = app(ApproveFormService::class);

        $approveId = (int) sys_config('contract_sign_switch');

        $process = $data['processInfo'];
        $product = $data['productInfo'];
        return $this->transaction(function () use ($info, $data, $id, $adminService, $uid, $approveId, $formService, $process, $product) {
            // 根据 link_type 决定存储位置：link_type=2 存入 cid，link_type=5 存入 oid
            $cidValue = $data['cid'] ? array_values(array_map('strval', $data['cid'])) : null;
            if (($data['link_type'] ?? CustomEnum::CONTRACT) == CustomEnum::ODDS) {
                // 商机关联：存入 oid，清空 cid
                $info->oid = $cidValue ?: null;
                $info->cid = null;
            } else {
                // 订单关联：存入 cid，清空 oid
                $info->cid = $cidValue ?: null;
                $info->oid = null;
            }
            $info->start_date   = $data['start_date'] ?: null;
            $info->end_date     = $data['end_date'] ?: null;
            $info->is_verify    = $approveId ? 1 : 0;
            $info->status       = $approveId ? SignEnum::STATUS_WAIT_APPROVE : SignEnum::STATUS_WAIT_SIGN;
            $info->file_id      = $data['file_id'];
            $info->sign_type    = $data['sign_type'];
            $info->term_type    = $data['term_type'];
            $info->date_count   = $data['date_count'];
            $info->mark         = $data['mark'];
            $info->sign_status  = $data['sign_type'] == 2 ? SignEnum::FLOW_STATUS_INIT : '';
            $info->signature_sn = '';
            $info->save();
            app(ProductAssistService::class)->saveProducts($product, (int) $id, CustomEnum::DOC);
            $this->signatoryDao->delete(['cid' => $id]);
            $signInsert = [];
            foreach ($data['signatory'] as $value) {
                $admin        = $value['user_id'] ? $adminService->get($value['user_id'], ['name', 'e_userid', 'e_openid'])?->toArray() : [];
                $signInsert[] = [
                    'cid'          => $id,
                    'name'         => $value['types'] ? $value['name'] : $admin['name'],
                    'phone'        => $value['phone'],
                    'user_id'      => $value['user_id'],
                    'company_name' => $value['company_name'],
                    'types'        => $value['types'],
                    'e_userid'     => $value['types'] ? '' : $admin['e_userid'],
                    'e_openid'     => $value['types'] ? '' : $admin['e_openid'],
                ];
            }
            $signInsert && $this->signatoryDao->insert($signInsert);
            if ($data['sign_file']) {
                app(AttachService::class)->saveRelation($data['sign_file']['id'] ?? 0, uid_to_uuid($uid), (int) $id, AttachEnum::RELATION_TYPE_SIGN);
            }
            if ($approveId) {
                $data['signatory']    = $signInsert;
                $data['product_info'] = $this->formatApprovalProductInfo($product);
                $data['term_type']    = $data['term_type'] ? ($data['term_type'] > 1 ? '签约日起算' : '固定期限') : '无期限';
                $form                 = collect($formService->getUniques($approveId) ?? [])
                    ->map(function ($unique) use ($data, $formService) {
                        $symbol = Str::snake($unique['symbol']);
                        if (! isset($data[$symbol])) {
                            return [];
                        }
                        if (! empty($unique['children'])) {
                            $childData = collect($data[$symbol])->map(fn ($datum) => $formService->processFormChildren($datum, $unique['children']))->filter()->all();
                            return [$unique['value'] => $childData];
                        }
                        return [$unique['value'] => $data[$symbol]];
                    })->filter()->collapse()->all();
                $info->approve_id = app(ApproveApplyService::class)->saveForm($form, $process, $approveId, 0, $uid);
                $info->save();
            }
            $data['sign_type'] == 2 && $this->addSignProcess((int) $id, needSignReview: (bool) $approveId);
            return $info;
        });
    }

    public function resourceCreate(array $other = []): array
    {
        // TODO: Implement resourceCreate() method.
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        $where = match ($other['link_type'] ?? '') {
            ViewSearchEnum::VIEW_ODDS => [
                'oid' => $id,
            ],
            ViewSearchEnum::VIEW_CONTRACT => [
                'cid' => $id,
            ],
            ViewSearchEnum::VIEW_CUSTOMER => [
                'eid' => $id,
            ],
            default => [
                'id' => $id,
            ]
        };
        $info = $this->dao->get($where, with: ['admin', 'signatory', 'customer', 'result', 'attach', 'rules', 'products']);
        if (! $info) {
            return [];
        }
        if ($info->signature_sn) {
            try {
                $this->syncSignStatus($info->id);
                $info->refresh();
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
        return collect($info?->toArray() ?? [])->map(function ($item, $key) use (&$approveData) {
            // 处理客户区域级联字段
            if ($key === 'customer' && is_array($item) && ($item['area_cascade'] ?? null)) {
                $item['area_cascade'] = collect(app(DictDataService::class)->getNamesByValue('area_cascade', $item['area_cascade']))->implode('/');
            }
            // 统一处理附件和结果的URL字段（合并重复逻辑）
            if (in_array($key, ['attach', 'result']) && $item) {
                $item = collect($item ?? [])->map(function ($file) {
                    $file['url'] = $file['url'] ? link_file($file['url']) : '';
                    return $file;
                })->all();
            }
            if ($key === 'approve_id' && $item) {
                $userService = app(ApproveUserService::class);
                $approveData = collect($userService->getUniques($item))
                    ->map(function ($v) use ($userService, $item) {
                        $process  = $userService->value(['node_id' => $v, 'apply_id' => $item], 'process_info');
                        $userList = collect($userService->getUserList(
                            ['node_id' => $v, 'apply_id' => $item],
                            ['status', 'updated_at', 'user_id', 'is_sign', 'is_transfer', 'content', 'parent'],
                            ['level' => 'asc', 'sort' => 'asc', 'id' => 'asc'],
                            ['card'  => fn ($query) => $query->select(['id', 'name', 'avatar', 'uid'])]
                        )?->toArray() ?? [])->all();
                        $updatedAt = $userService->getValue(
                            ['node_id' => $v, 'apply_id' => $item, 'status' => 1],
                            'updated_at',
                            ['updated_at' => 'desc']
                        ) ?: '';

                        return [
                            'uniqued'      => $v,
                            'apply_id'     => $item,
                            'types'        => $process['types'] ?? '',
                            'title'        => $process['name'] ?? '',
                            'examine_mode' => $process['examine_mode'] ?? '',
                            'updated_at'   => $updatedAt,
                            'users'        => $userList,
                        ];
                    })
                    ->all();
                return $item;
            }
            return $item;
        })
            ->when(isset($approveData), function ($collection) use ($approveData) {
                return $collection->put('approve', $approveData);
            })
            ->when($info !== null, function ($collection) use ($info) {
                // 根据 link_type 返回正确的关联ID到 cid 字段，便于前端使用
                $linkType = $info->link_type ?? CustomEnum::CONTRACT;
                $cid      = $linkType == CustomEnum::ODDS ? $info->oid : $info->cid;
                return $collection
                    ->put('cid', $cid)
                    ->put('contract_price', $this->getContractPrice($info->toArray()));
            })
            ->all();
    }

    /**
     * 获取签约人.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSignatory(int $id)
    {
        $info = $this->dao->get($id, with: ['signatory']);
        return collect($info->signatory ?? [])->each(function ($item) use ($info) {
            $item['app_url'] = $info->app_url;
            return $item;
        })->all();
    }

    /**
     * 合同签约审批.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function approved(int $applyId, int $status)
    {
        $info = $this->dao->get(['approve_id' => $applyId], with: ['signatory']);
        if ($info) {
            $status       = $status === -1 ? SignEnum::STATUS_APPROVE_REJECT : $status;
            $info->status = $status;
            $info->save();
            $info->sign_type == 2 && match ($status) {
                SignEnum::STATUS_WAIT_SIGN        => app(SmsService::class)->approveSignatureOrder($info->signature_sn),
                SignEnum::STATUS_APPROVE_REJECT   => app(SmsService::class)->approveSignatureOrder($info->signature_sn, 'REJECT', '合同签约审批被拒'),
                SignEnum::STATUS_WAIT_SIGN_REVOKE => app(SmsService::class)->cancelSignatureOrder($info->signature_sn, '用户取消合同签约'),
                default                           => null,
            };
            if ($info->sign_type == 2 && $status == SignEnum::STATUS_WAIT_SIGN) {
                $userId = '';
                collect($info->signatory ?? [])->each(function ($signatory) use (&$userId) {
                    if ($signatory->types == 0) {
                        $userId = app(AdminService::class)->value($signatory->user_id, 'e_userid');
                    }
                });
                $urls          = app(SmsService::class)->getSignFlowUrl($info->signature_sn, $userId);
                $info->app_url = $urls;
                $info->save();
            }
        }
    }

    /**
     * 获取审批流程.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function process(array $data, int $uid, mixed $signFile = [], string $fileId = ''): array
    {
        $file['file_id'] = $fileId;
        if (! $fileId && $signFile && $data['sign_type'] == 2) {
            $result = app(SmsService::class)->uploadSignFile(
                $signFile['real_name'],
                base64_encode($this->readSignFile((string) ($signFile['url'] ?? ''))),
                (string) Uuid::generate(4)
            );
            if ($result['status'] == 200) {
                $file = [
                    'file_id' => $result['data']['task_id'] ? '' : $result['data']['file_id'],
                    'task_id' => $result['data']['task_id'],
                ];
            } else {
                throw $this->exception($result['msg'] ?? '上传失败');
            }
        }
        $approveId = sys_config('contract_sign_switch');
        if (! $approveId) {
            return compact('file');
        }
        $data = collect(app(ApproveFormService::class)->getUniques((int) $approveId) ?? [])->map(function ($unique) use ($data) {
            $symbol = Str::snake($unique['symbol']);
            if (isset($data[$symbol])) {
                return [$unique['value'] => $data[$symbol]];
            }
            return [];
        })->filter()->collapse()->all();
        $process         = app(ApproveProcessService::class);
        $content         = $process->verifyForm($data, $approveId, $uid);
        $content['file'] = $file;
        return $content;
    }

    /**
     * 获取合同文件上传结果.
     * @return mixed
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function uploadResult(string $taskId)
    {
        $result = app(SmsService::class)->uploadSignFileTask($taskId);
        if ($result['status'] == 200) {
            return $result['data'];
        }
        throw $this->exception($result['message'] ?? '上传失败');
    }

    /**
     * 发起电子签.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function addSignProcess(int $id, bool $needSignReview = true)
    {
        $doc = app(ContractService::class)->get($id, with: ['admin', 'signatory']);
        if (! $doc) {
            throw $this->exception('合同不存在');
        }
        $service           = app(SmsService::class);
        $doc->signature_sn = $service->createSignatureOrder($doc['doc_name'], $doc['mark'] ?: '');
        $doc->save();
        $userId    = '';
        $approvers = collect();
        collect($doc->signatory ?? [])->each(function ($signatory) use (&$userId, &$approvers) {
            switch ($signatory->types) {
                case 0:
                    $userId = $signatory->e_userid;
                    $approvers->push([
                        'approverType'           => 'ORGANIZATION',
                        'organizationName'       => sys_config(ConfigEnum::E_COMPANY_NAME['key']),
                        'name'                   => $signatory->name,
                        'mobile'                 => $signatory->phone,
                        'openId'                 => $signatory->e_openid,
                        'notChannelOrganization' => false,
                    ]);
                    break;
                case 1:
                    $approvers->push([
                        'approverType'           => 'PERSON',
                        'organizationName'       => '',
                        'name'                   => $signatory->name,
                        'mobile'                 => $signatory->phone,
                        'openId'                 => '',
                        'notChannelOrganization' => true,
                    ]);
                    break;
                case 2:
                    $approvers->push([
                        'approverType'           => 'ORGANIZATION',
                        'organizationName'       => $signatory->company_name,
                        'name'                   => $signatory->name,
                        'mobile'                 => $signatory->phone,
                        'openId'                 => '',
                        'notChannelOrganization' => true,
                    ]);
                    break;
            }
        });
        $doc->app_url     = $service->createFlowByFileDirectly($doc->signature_sn, 'WEIXINAPP', $doc->file_id, $userId, $approvers->all(), $needSignReview);
        $doc->status      = $needSignReview ? SignEnum::STATUS_WAIT_APPROVE : SignEnum::STATUS_WAIT_SIGN;
        $doc->sign_status = $needSignReview ? SignEnum::FLOW_STATUS_INIT : SignEnum::FLOW_STATUS_PART;
        $doc->save();
    }

    /**
     * 取消合同.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function cancel(int $id): void
    {
        $doc = $this->dao->get($id);
        switch ($doc->status) {
            case 0:
                throw $this->exception('合同审批已驳回,无需撤销');
            case 1:
                $doc->status = 6;
                app(ApproveApplyService::class)->revokeApply($doc->approve_id, $doc->uid);
                break;
            case 2:
                if ($doc->sign_type == 2) {
                    app(SmsService::class)->cancelSignatureOrder($doc->signature_sn, '用户取消合同签约');
                }
                $doc->status = 6;
                break;
            case 3:
                throw $this->exception('合同已签约,无法撤销');
            case 4:
                throw $this->exception('合同已拒绝签约,无需撤销');
            case 5:
                throw $this->exception('合同签约已过期,无需撤销');
            case 6:
                throw $this->exception('合同签约已撤销,请勿重复操作');
        }
        $doc->save();
    }

    /**
     * 电子签约回调.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function callBack(array $data)
    {
        $info = $this->dao->get(['signature_sn' => $data['signature_sn']]);
        if ($info) {
            $info->sign_status = $data['flow_status'];
            $status            = SignEnum::statusByFlowStatus($data['flow_status']);
            if ($status !== null && $info->status !== SignEnum::STATUS_APPROVE_REJECT) {
                $info->status = $status;
            }
            if ($data['flow_status'] === SignEnum::FLOW_STATUS_ALL) {
                $info->sign_date = now()->toDateString();
                if ($info->term_type === 2) {
                    $info->start_date = now()->toDateString();
                    $info->end_date   = now()->addDays($info->date_count)->toDateString();
                }
                $info->sign_url = app(SmsService::class)->getDescribeFileUrl($data['signature_sn']);
            }
            $info->save();
            $signService = app(ContractSignatoryService::class);
            collect($data['approves'] ?? [])->each(function ($approve) use ($signService, $info) {
                $status = match ($approve['approve_status']) {
                    SignEnum::APPROVE_STATUS_ACCEPT => 1,
                    SignEnum::FLOW_STATUS_REJECT,
                    SignEnum::APPROVE_STATUS_FILLREJECT,
                    SignEnum::FLOW_STATUS_EXCEPTION,
                    SignEnum::FLOW_STATUS_RELIEVED,
                    SignEnum::FLOW_STATUS_DEADLINE,
                    SignEnum::FLOW_STATUS_CANCEL,
                    SignEnum::APPROVE_STATUS_STOP => 2,
                    default                       => 0,
                };
                if ($approve['approverType'] === 'PERSON') {
                    $signService->update(['cid' => $info->id, 'phone' => $approve['mobile'], 'types' => 1], ['sign_status' => $status]);
                } else {
                    $signService->update(['cid' => $info->id, 'phone' => $approve['mobile']], ['sign_status' => $status]);
                }
            });
        }
    }

    /**
     * 获取合同关联订单.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function orders(int $id, int $uid)
    {
        $cid = $this->dao->value($id, 'cid');
        if (! $cid) {
            return $this->listData([]);
        }
        return app(OrderService::class)->getListByType(['types' => ViewSearchEnum::VIEW_CONTRACT, 'id' => $cid], $uid);
    }

    /**
     * 关联订单.
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function linkOrder(int $id, array $cid)
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('合同不存在');
        }
        // 验证传入的订单ID是否存在
        if (! empty($cid)) {
            $exists   = app(OrderService::class)->dao->select(['id' => $cid], ['id'])?->pluck('id')->toArray();
            $notFound = array_diff($cid, $exists ?? []);
            if (! empty($notFound)) {
                throw $this->exception('部分订单不存在');
            }
            $cid = array_map('strval', $cid);
        }
        return $this->dao->update($id, ['cid' => $cid ?: null]);
    }

    /**
     * 保存合同签约结果.
     * @return int
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveSign(int $id, int $fileId, int $uid)
    {
        $info = $this->dao->get($id);
        if ($info->sign_type === 2) {
            throw $this->exception('该合同需要电子签约');
        }
        $info->status    = 3;
        $info->sign_date = now()->toDateString();
        if ($info->term_type === 2) {
            $info->start_date = now()->toDateString();
            $info->end_date   = now()->addDays($info->date_count)->toDateString();
        }
        $info->sign_result = $fileId;
        app(AttachService::class)->saveRelation($fileId, uid_to_uuid($uid), $id, AttachEnum::RELATION_TYPE_SIGN_RESULT);
        $this->signatoryDao->update(['cid' => $id], ['sign_status' => 1]);
        return $info->save();
    }

    /**
     * 同步签约订单状态.
     * 主动查询签约订单信息，更新合同签约状态和签署方签约状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function syncSignStatus(int $id): void
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('合同不存在');
        }
        if (! $info->signature_sn) {
            throw $this->exception('该合同未发起电子签约');
        }

        // 调用 SmsService 获取签约订单信息
        $orderInfo = app(SmsService::class)->getSignOrder($info->signature_sn);
        if (empty($orderInfo)) {
            throw $this->exception('获取签约订单信息失败');
        }

        $flowStatus = $orderInfo['flow_status'] ?? '';
        if (! $flowStatus) {
            throw $this->exception('签约订单状态信息缺失');
        }

        // 更新合同状态
        $info->sign_status = $flowStatus;
        $status            = SignEnum::statusByFlowStatus($flowStatus);
        if ($status !== null && $info->status !== SignEnum::STATUS_APPROVE_REJECT) {
            $info->status = $status;
        }
        if ($flowStatus === SignEnum::FLOW_STATUS_ALL) {
            $info->sign_date = now()->toDateString();
            if ($info->term_type === 2) {
                $info->start_date = now()->toDateString();
                $info->end_date   = now()->addDays($info->date_count)->toDateString();
            }
            $info->sign_url = app(SmsService::class)->getDescribeFileUrl($info->signature_sn);
        }
        $info->save();

        // 更新签署方状态
        $signService = app(ContractSignatoryService::class);
        collect($orderInfo['approver'] ?? [])->each(function ($approve) use ($signService, $info) {
            if (! isset($approve['approve_status'])) {
                return;
            }
            $status = match ($approve['approve_status']) {
                SignEnum::APPROVE_STATUS_ACCEPT => 1,
                SignEnum::FLOW_STATUS_REJECT,
                SignEnum::APPROVE_STATUS_FILLREJECT,
                SignEnum::FLOW_STATUS_EXCEPTION,
                SignEnum::FLOW_STATUS_RELIEVED,
                SignEnum::FLOW_STATUS_DEADLINE,
                SignEnum::FLOW_STATUS_CANCEL,
                SignEnum::APPROVE_STATUS_STOP => 2,
                default                       => 0,
            };
            $sign_time = $approve['approve_time'] ? date('Y-m-d H:i:s', $approve['approve_time']) : null;
            if ($approve['approverType'] === 'PERSON') {
                $signService->update(['cid' => $info->id, 'phone' => $approve['mobile'], 'types' => 1], ['sign_status' => $status, 'sign_time' => $sign_time]);
            } else {
                $signService->update(['cid' => $info->id, 'phone' => $approve['mobile']], ['sign_status' => $status, 'sign_time' => $sign_time]);
            }
        });
    }

    private function formatApprovalProductInfo(array $products): array
    {
        $uniques = array_values(array_filter(array_column($products, 'unique')));
        if (! $uniques) {
            return $products;
        }

        $productMap = array_column(app(ProductAttrValueService::class)
            ->select(['unique' => $uniques], with: ['product'])?->toArray() ?? [], null, 'unique');

        return collect($products)->map(function (array $product) use ($productMap) {
            $info                    = $productMap[$product['unique'] ?? ''] ?? [];
            $product['product_name'] = ($product['product_name'] ?? '') ?: ($info['product']['name'] ?? '');
            $product['sku']          = ($product['sku'] ?? '') ?: ($info['sku'] ?? '');
            return $product;
        })->all();
    }

    /**
     * 批量计算合同文档展示金额，避免列表逐条查询合同订单/产品.
     */
    private function getContractPriceMap(array $list): array
    {
        if (! $list) {
            return [];
        }

        $docOrderIds     = [];
        $allOrderIds     = [];
        $docIds          = [];
        $productPriceMap = [];
        foreach ($list as $item) {
            if (empty($item['id'])) {
                continue;
            }
            $docId    = (int) $item['id'];
            $docIds[] = $docId;
            $orderIds = array_values(array_filter((array) ($item['cid'] ?? [])));
            if (! $orderIds) {
                if (! empty($item['products'])) {
                    $productPriceMap[$docId] = $this->sumProductPrice($item['products']);
                }
                continue;
            }
            $docOrderIds[$docId] = $orderIds;
            $allOrderIds         = array_merge($allOrderIds, $orderIds);
        }

        $orderPriceMap = [];
        $allOrderIds   = array_values(array_unique($allOrderIds));
        if ($allOrderIds) {
            $orderPriceMap = collect(app(OrderService::class)->select(['id' => $allOrderIds], ['id', 'contract_price'])?->toArray() ?? [])
                ->mapWithKeys(fn ($order) => [(string) $order['id'] => (string) ($order['contract_price'] ?? 0)])
                ->all();
        }

        $productDocIds = array_values(array_diff($docIds, array_keys($docOrderIds), array_keys($productPriceMap)));
        if ($productDocIds) {
            $queriedProductPriceMap = collect(app(ProductAssistService::class)->select(['link_id' => $productDocIds, 'link_type' => CustomEnum::DOC], ['link_id', 'total_price'])?->toArray() ?? [])
                ->groupBy('link_id')
                ->map(fn ($products) => $this->sumProductPrice($products->all()))
                ->all();
            $productPriceMap = $productPriceMap + $queriedProductPriceMap;
        }

        $priceMap = [];
        foreach ($docIds as $docId) {
            $total = '0.00';
            foreach ($docOrderIds[$docId] ?? [] as $orderId) {
                $total = bcadd($total, $orderPriceMap[(string) $orderId] ?? '0.00', 2);
            }
            $priceMap[$docId] = ($docOrderIds[$docId] ?? []) ? $total : ($productPriceMap[$docId] ?? '0.00');
        }

        return $priceMap;
    }

    private function getContractPrice(array $info): string
    {
        if (empty($info['id']) && ! empty($info['products'])) {
            return $this->sumProductPrice($info['products']);
        }

        return $this->getContractPriceMap([$info])[$info['id'] ?? 0] ?? '0.00';
    }

    private function sumProductPrice(array $products): string
    {
        $total = '0.00';
        foreach ($products as $product) {
            $total = bcadd($total, (string) ($product['total_price'] ?? 0), 2);
        }
        return $total;
    }

    private function readSignFile(string $url): string
    {
        $path    = $this->resolveSignFilePath($url);
        $content = $path === '' ? false : @file_get_contents($path);
        if ($content === false) {
            throw $this->exception('合同文件读取失败');
        }
        return $content;
    }

    private function resolveSignFilePath(string $url): string
    {
        if ($url !== '' && is_file($url)) {
            return $url;
        }
        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? $url : public_path(ltrim($url, '/'));
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
                $where['uid'] = app(FrameAssistService::class)->getScopeUid($uid, $scopeFrame);
                break;
        }
        unset($where['view_search'], $where['scope_frame']);
        return $where;
    }
}
