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

use App\Constants\DataPermissionLevelEnum;
use App\Constants\ModuleEnum;
use App\Constants\System\ViewSearchEnum;
use App\Http\Model\Customer\Contract;
use App\Http\Model\Customer\Customer;
use App\Http\Model\Customer\FollowUp;
use App\Http\Model\Customer\Invoice;
use App\Http\Model\Customer\Lead;
use App\Http\Model\Customer\Liaison;
use App\Http\Model\Customer\Opportunity;
use App\Http\Model\Customer\Order;
use App\Http\Model\Customer\Payment;
use App\Http\Service\System\ModulePermissionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * CRM 活动汇总：按日/周/月聚合客户模块全业务动态（客户/线索/商机/订单/合同/发票/账目/联系人/跟进），供 AI 汇报组装.
 */
class CrmActivitySummaryService
{
    private const LIMIT = 50;

    /** 账目 types：回款. */
    private const BILL_PAYMENT = 0;

    /** 账目 types：续费. */
    private const BILL_RENEWAL = 1;

    /** 账目 types：支出. */
    private const BILL_EXPENSE = 2;

    /**
     * 汇总指定用户在周期内的 CRM 全模块动态.
     *
     * @param int         $userId 用户数据库 ID
     * @param string      $period day|week|month
     * @param string|null $date   锚点日期 Y-m-d
     *
     * @return array<string, mixed>
     */
    public function summarize(int $userId, string $period, ?string $date = null): array
    {
        [$start, $end] = $this->resolveRange($period, $date);
        $buckets       = [
            'customers_created'     => [],
            'leads_created'         => [],
            'opportunities_created' => [],
            'orders_created'        => [],
            'contracts_created'     => [],
            'invoices_created'      => [],
            'payments_created'      => [],
            'renewals_created'      => [],
            'expenses_created'      => [],
            'contacts_created'      => [],
            'follow_ups'            => [],
        ];
        $empty = array_merge([
            'period'          => $period,
            'start'           => $start,
            'end'             => $end,
            'stats'           => array_map(static fn () => 0, $buckets),
            'skipped_modules' => [],
        ], $buckets);

        if ($userId <= 0) {
            return $empty;
        }

        $permission = app(ModulePermissionService::class)->getUserModulePermission($userId, ModuleEnum::CUSTOMER);
        if (($permission['data_level'] ?? null) === DataPermissionLevelEnum::NONE) {
            $empty['skipped_modules'][] = ModuleEnum::CUSTOMER;

            return $empty;
        }

        $customers = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Customer::query()
                ->where(fn (Builder $q) => $q->where('creator_uid', $userId)->orWhere('uid', $userId))
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'customer_name', 'uid', 'creator_uid', 'created_at'])
                ->map(static fn ($row) => [
                    'id'         => (int) $row->id,
                    'name'       => (string) ($row->customer_name ?? ''),
                    'uid'        => (int) $row->uid,
                    'created_at' => (string) $row->created_at,
                ])
                ->all();
        });

        $leads = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Lead::query()
                ->where(fn (Builder $q) => $q->where('creator_uid', $userId)->orWhere('uid', $userId))
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'name', 'uid', 'creator_uid', 'created_at'])
                ->map(static fn ($row) => [
                    'id'         => (int) $row->id,
                    'name'       => (string) ($row->name ?? ''),
                    'uid'        => (int) $row->uid,
                    'created_at' => (string) $row->created_at,
                ])
                ->all();
        });

        $opportunities = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Opportunity::query()
                ->where(fn (Builder $q) => $q->where('creator_uid', $userId)->orWhere('uid', $userId))
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'name', 'uid', 'creator_uid', 'created_at', 'eid'])
                ->map(static fn ($row) => [
                    'id'          => (int) $row->id,
                    'name'        => (string) ($row->name ?? ''),
                    'customer_id' => (int) ($row->eid ?? 0),
                    'uid'         => (int) $row->uid,
                    'created_at'  => (string) $row->created_at,
                ])
                ->all();
        });

        $orders = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Order::query()
                ->where(fn (Builder $q) => $q->where('creator_uid', $userId)->orWhere('uid', $userId))
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'contract_name', 'uid', 'creator_uid', 'eid', 'contract_price', 'created_at'])
                ->map(static fn ($row) => [
                    'id'          => (int) $row->id,
                    'name'        => (string) ($row->contract_name ?? ''),
                    'customer_id' => (int) ($row->eid ?? 0),
                    'amount'      => (string) ($row->contract_price ?? '0'),
                    'uid'         => (int) $row->uid,
                    'created_at'  => (string) $row->created_at,
                ])
                ->all();
        });

        $contracts = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Contract::query()
                ->where('uid', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'doc_name', 'uid', 'eid', 'sign_status', 'created_at'])
                ->map(static fn ($row) => [
                    'id'          => (int) $row->id,
                    'name'        => (string) ($row->doc_name ?? ''),
                    'customer_id' => (int) ($row->eid ?? 0),
                    'sign_status' => (string) ($row->sign_status ?? ''),
                    'uid'         => (int) $row->uid,
                    'created_at'  => (string) $row->created_at,
                ])
                ->all();
        });

        $invoices = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Invoice::query()
                ->where('uid', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'title', 'uid', 'eid', 'price', 'amount', 'status', 'created_at'])
                ->map(static fn ($row) => [
                    'id'          => (int) $row->id,
                    'name'        => (string) ($row->title ?? ''),
                    'customer_id' => (int) ($row->eid ?? 0),
                    'price'       => (string) ($row->price ?? '0'),
                    'amount'      => (string) ($row->amount ?? '0'),
                    'status'      => (int) ($row->status ?? 0),
                    'uid'         => (int) $row->uid,
                    'created_at'  => (string) $row->created_at,
                ])
                ->all();
        });

        $payments = $this->collectBills($userId, $start, $end, self::BILL_PAYMENT);
        $renewals = $this->collectBills($userId, $start, $end, self::BILL_RENEWAL);
        $expenses = $this->collectBills($userId, $start, $end, self::BILL_EXPENSE);

        $contacts = $this->safeCollect(static function () use ($userId, $start, $end) {
            return Liaison::query()
                ->where(fn (Builder $q) => $q->where('creator_uid', $userId)->orWhere('uid', $userId))
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'liaison_name', 'uid', 'creator_uid', 'eid', 'created_at'])
                ->map(static fn ($row) => [
                    'id'          => (int) $row->id,
                    'name'        => (string) ($row->liaison_name ?? ''),
                    'customer_id' => (int) ($row->eid ?? 0),
                    'uid'         => (int) $row->uid,
                    'created_at'  => (string) $row->created_at,
                ])
                ->all();
        });

        $followUps = $this->safeCollect(static function () use ($userId, $start, $end) {
            return FollowUp::query()
                ->where('user_id', $userId)
                ->whereIn('link_type', [
                    ViewSearchEnum::VIEW_CUSTOMER,
                    ViewSearchEnum::VIEW_CLUE,
                    ViewSearchEnum::VIEW_ODDS,
                ])
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'eid', 'link_type', 'content', 'user_id', 'created_at'])
                ->map(static fn ($row) => [
                    'id'         => (int) $row->id,
                    'link_id'    => (int) $row->eid,
                    'link_type'  => (string) $row->link_type,
                    'content'    => (string) ($row->content ?? ''),
                    'user_id'    => (int) $row->user_id,
                    'created_at' => (string) $row->created_at,
                ])
                ->all();
        });

        $data = [
            'customers_created'     => $customers,
            'leads_created'         => $leads,
            'opportunities_created' => $opportunities,
            'orders_created'        => $orders,
            'contracts_created'     => $contracts,
            'invoices_created'      => $invoices,
            'payments_created'      => $payments,
            'renewals_created'      => $renewals,
            'expenses_created'      => $expenses,
            'contacts_created'      => $contacts,
            'follow_ups'            => $followUps,
        ];

        return array_merge([
            'period'          => $period,
            'start'           => $start,
            'end'             => $end,
            'stats'           => array_map('count', $data),
            'skipped_modules' => [],
        ], $data);
    }

    /**
     * 汇总指定账目类型（回款/续费/支出）.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectBills(int $userId, string $start, string $end, int $types): array
    {
        return $this->safeCollect(static function () use ($userId, $start, $end, $types) {
            return Payment::query()
                ->where('uid', $userId)
                ->where('types', $types)
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get(['id', 'uid', 'eid', 'cid', 'num', 'types', 'status', 'date', 'created_at'])
                ->map(static fn ($row) => [
                    'id'          => (int) $row->id,
                    'customer_id' => (int) ($row->eid ?? 0),
                    'order_id'    => (int) ($row->cid ?? 0),
                    'amount'      => (string) ($row->num ?? '0'),
                    'types'       => (int) $row->types,
                    'status'      => (int) ($row->status ?? 0),
                    'bill_date'   => (string) ($row->date ?? ''),
                    'uid'         => (int) $row->uid,
                    'created_at'  => (string) $row->created_at,
                ])
                ->all();
        });
    }

    /**
     * 安全采集：单模块异常不影响整体汇总.
     *
     * @param callable(): array<int, array<string, mixed>> $callback
     *
     * @return array<int, array<string, mixed>>
     */
    private function safeCollect(callable $callback): array
    {
        try {
            $rows = $callback();

            return is_array($rows) ? $rows : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveRange(string $period, ?string $date = null): array
    {
        $anchor = $date ? Carbon::parse($date) : Carbon::now();

        return match ($period) {
            'week' => [
                $anchor->copy()->startOfWeek()->toDateTimeString(),
                $anchor->copy()->endOfWeek()->endOfDay()->toDateTimeString(),
            ],
            'month' => [
                $anchor->copy()->startOfMonth()->toDateTimeString(),
                $anchor->copy()->endOfMonth()->endOfDay()->toDateTimeString(),
            ],
            default => [
                $anchor->copy()->startOfDay()->toDateTimeString(),
                $anchor->copy()->endOfDay()->toDateTimeString(),
            ],
        };
    }
}
