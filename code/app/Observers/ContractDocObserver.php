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

namespace App\Observers;

use App\Constants\CustomEnum\CustomEnum;
use App\Constants\CustomEnum\OddsEnum;
use App\Constants\CustomEnum\SignEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Model\Customer\Contract;
use App\Http\Service\Customer\OpportunityService;
use App\Http\Service\Customer\OrderService;
use App\Http\Service\Customer\RecordService;
use App\Jobs\Customer\ContractDocToLocalJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractDocObserver
{
    public function created(Contract $model)
    {
        app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CONTRACT_DOC, [
            'eid'            => $model->id,
            'type'           => SignEnum::OPERATE_CREATE,
            'creator_uid'    => $model->uid,
            'record_version' => 0,
            'reason'         => '新增合同签约"' . $model->doc_name . '"',
        ]);
    }

    public function updated(Contract $model)
    {
        if ($model->isDirty('status')) {
            $record = [
                'eid'            => $model->id,
                'creator_uid'    => auth('admin')->id() ?: 0,
                'record_version' => 0,
                'type'           => 0,
            ];
            switch ($model->status) {
                case SignEnum::STATUS_APPROVE_REJECT:
                    $record['reason'] = '合同签约"' . $model->doc_name . '"审核未通过';
                    $record['type']   = SignEnum::OPERATE_APPROVE_REJECT;
                    break;
                case SignEnum::STATUS_WAIT_SIGN:
                    $record['reason'] = '合同签约"' . $model->doc_name . '"审核已通过';
                    $record['type']   = SignEnum::OPERATE_APPROVED;
                    break;
                case SignEnum::STATUS_WAIT_SIGN_COMPLETE:
                    $record['reason'] = '合同签约"' . $model->doc_name . '"已完成';
                    $record['type']   = SignEnum::OPERATE_COMPLETE;
                    // // 处理商机关联：更新商机状态为赢单，并创建订单
                    $this->handleOddsAssociation($model);
                    break;
                case SignEnum::STATUS_WAIT_SIGN_COMPLETE_REJECT:
                    $record['reason'] = '合同签约"' . $model->doc_name . '"已被拒绝';
                    $record['type']   = SignEnum::OPERATE_REJECT;
                    break;
                case SignEnum::STATUS_WAIT_SIGN_EXPIRED:
                    $record['reason'] = '合同签约"' . $model->doc_name . '"已过期';
                    $record['type']   = SignEnum::OPERATE_EXPIRED;
                    break;
                case SignEnum::STATUS_WAIT_SIGN_REVOKE:
                    $record['reason'] = '合同签约"' . $model->doc_name . '"已撤销';
                    $record['type']   = SignEnum::OPERATE_REVOKE;
                    break;
            }
            $record['type'] && app(RecordService::class)->saveRecord(ViewSearchEnum::VIEW_CONTRACT_DOC, $record);
        }
        if ($model->isDirty('sign_url') && $model->sign_url) {
            ContractDocToLocalJob::dispatch($model->sign_url, $model->id, $model->uid);
        }
    }

    /**
     * 处理商机关联：更新商机状态为赢单，并创建订单.
     */
    protected function handleOddsAssociation(Contract $model): void
    {
        // 判断是否为商机关联的合同签约
        if ($model->link_type != CustomEnum::ODDS || empty($model->oid)) {
            return;
        }

        // 如果订单已创建，跳过重复创建（防止并发或重复调用导致重复创建）
        if (! empty($model->cid)) {
            return;
        }

        // 从 oid JSON 数组中提取商机ID
        $oddsIds = $model->oid;
        if (! is_array($oddsIds)) {
            return;
        }

        try {
            DB::beginTransaction();

            $oddsService     = app(OpportunityService::class);
            $contractService = app(OrderService::class);
            $recordService   = app(RecordService::class);

            // 获取合同签约的产品信息
            $products      = $model->products()->get()->toArray();
            $contractPrice = '0.00';
            $productsData  = array_map(function ($product) use (&$contractPrice) {
                $contractPrice = bcadd((string) $contractPrice, (string) ($product['total_price'] ?? 0), 2);
                return [
                    'unique'      => $product['unique'],
                    'price'       => $product['price'],
                    'count'       => $product['count'],
                    'discount'    => $product['discount'],
                    'total_price' => $product['total_price'],
                    'ot_price'    => $product['ot_price'],
                    'sku'         => $product['sku'] ?? '',
                    'remark'      => $product['remark'] ?? '',
                ];
            }, $products);
            $contractId = 0;
            foreach ($oddsIds as $oddsId) {
                $oddsId = (int) $oddsId;
                if ($oddsId <= 0) {
                    continue;
                }
                if (! $contractId) {
                    // 创建订单
                    $contractData = [
                        'contract_customer' => $model->eid,
                        'contract_name'     => $model->doc_name,
                        'contract_price'    => $contractPrice,
                        'start_date'        => $model->start_date,
                        'end_date'          => $model->end_date,
                        'oid'               => $oddsId, // 关联商机ID
                    ];
                    $contract   = $contractService->saveContract($contractData, $model->uid, $productsData, '合同签约"' . $model->doc_name . '"已完成，自动创建订单');
                    $contractId = $contract->id;
                    Contract::withoutEvents(function () use ($model, $contractId) {
                        $model->cid = [(string) $contractId];
                        $model->save();
                    });
                }
                // 更新商机状态为赢单（status='2'）
                $oddsService->updateOdds(['status' => 2, 'cid' => $contractId], $oddsId);
                // 记录商机状态变更日志
                $recordService->saveRecord(ViewSearchEnum::VIEW_ODDS, [
                    'eid'            => $oddsId,
                    'type'           => OddsEnum::OPERATE_UPDATE,
                    'creator_uid'    => $model->uid,
                    'record_version' => 0,
                    'reason'         => '合同签约"' . $model->doc_name . '"已完成，商机状态变更为赢单',
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('合同签约完成处理商机关联失败', [
                'contract_doc_id' => $model->id,
                'odds_ids'        => $oddsIds,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);
        }
    }
}
