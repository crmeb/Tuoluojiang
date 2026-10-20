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

use App\Http\Dao\Customer\TargetDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 业绩目标服务
 */
class TargetService extends BaseService
{
    public function __construct(TargetDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存数据.
     * @return mixed
     */
    public function saveData(int $uid, array $data)
    {
        return $this->transaction(function () use ($data, $uid) {
            foreach ($data as $item) {
                $this->dao->updateOrCreate([
                    'types'   => $item['link_type'],
                    'link_id' => $item['link_id'],
                    'month'   => $item['month'],
                    'year'    => $item['year'],
                ], [
                    'uid'     => $uid,
                    'link_id' => $item['link_id'],
                    'amount'  => $item['amount'],
                    'month'   => $item['month'],
                    'year'    => $item['year'],
                    'types'   => $item['link_type'],
                ]);
            }
            return true;
        });
    }

    /**
     * 获取数据.
     * @return mixed
     */
    public function getData(array $where)
    {
        $list         = $this->dao->search($where)->get()?->toArray();
        $linkIds      = array_unique(array_column($list, 'link_id'));
        if (! $linkIds) {
            return [];
        }
        $targetMap    = $this->getTargetMap($list);
        $data         = [];
        $frameService = app(FrameService::class);
        $adminService = app(AdminService::class);
        $frames       = collect($frameService->select(['ids' => $linkIds], ['id', 'name'])?->toArray() ?? [])->keyBy('id')->all();
        $admins       = collect($adminService->select(['id' => $linkIds], ['id', 'name'])?->toArray() ?? [])->keyBy('id')->all();
        foreach ($linkIds as $linkId) {
            $targets = $targetMap[$linkId] ?? [];
            $target  = $this->getFirstTarget($targets);
            $type    = (int) ($target['types'] ?? 0);
            $year    = $target['year'] ?? ($where['year'] ?? now()->format('Y'));
            $arr = [
                'link_id' => $linkId,
            ];
            for ($i = 1; $i <= 12; ++$i) {
                $info              = $targets[$i] ?? [];
                $arr['month' . $i] = (float) ($info['amount'] ?? 0);
                $arr['year']       = $info['year'] ?? $year;
                $arr['types']      = $info['types'] ?? $type;
            }
            $arr['user'] = $this->getTargetUser($linkId, (int) $arr['types'], $frames, $admins);
            $data[]      = $arr;
        }
        return $data;
    }

    /**
     * 获取指定月份业绩目标.
     * @return int|string
     * @throws BindingResolutionException
     */
    public function getTargetFormMonth(array $months, int $linkId, int $linkType)
    {
        $amount = 0;
        foreach ($months as $month) {
            [$year, $month] = explode('-', $month);
            $amount         = bcadd((string) $amount, (string) $this->dao->sum(['month' => ltrim($month, '0'), 'year' => $year, 'link_id' => $linkId, 'types' => $linkType], 'amount'), 2);
        }
        return $amount;
    }

    public function deleteData($where)
    {
        return $this->dao->delete($where);
    }

    /**
     * 获取目标完成度.
     * @return mixed
     */
    public function getTargetRate(array $where)
    {
        $list         = $this->dao->search($where)->get()?->toArray();
        $linkIds      = array_unique(array_column($list, 'link_id'));
        if (! $linkIds) {
            return [];
        }
        $targetMap    = $this->getTargetMap($list);
        $frameService = app(FrameService::class);
        $adminService = app(AdminService::class);
        $bill         = app(PaymentService::class)->getBillCensus((int) $where['year'], (int) $where['types']);
        $frames       = collect($frameService->select(['ids' => $linkIds], ['id', 'name'])?->toArray() ?? [])->keyBy('id')->all();
        $admins       = collect($adminService->select(['id' => $linkIds], ['id', 'name'])?->toArray() ?? [])->keyBy('id')->all();
        $data         = [];
        foreach ($linkIds as $linkId) {
            $targets = $targetMap[$linkId] ?? [];
            $target  = $this->getFirstTarget($targets);
            $type    = (int) ($target['types'] ?? 0);
            $year    = $target['year'] ?? ($where['year'] ?? now()->format('Y'));
            $arr = [
                'link_id' => $linkId,
            ];
            $i = 0;
            do {
                $info         = $targets[$i + 1] ?? [];
                $targetAmount = (string) ($info['amount'] ?? 0);
                if (isset($bill[$i][$linkId])) {
                    $arr['month' . ($i + 1)] = [
                        'target' => (float) $targetAmount,
                        'amount' => sprintf('%.2f', $bill[$i][$linkId]['price']),
                        'ratio'  => (float) $targetAmount ? bcmul(bcdiv((string) $bill[$i][$linkId]['price'], $targetAmount, 4), '100', 1) : 100.0,
                    ];
                } else {
                    $arr['month' . ($i + 1)] = [
                        'target' => sprintf('%.2f', (float) $targetAmount),
                        'amount' => (float) 0,
                        'ratio'  => (float) $targetAmount ? 0.0 : 100.0,
                    ];
                }
                $arr['year']  = $info['year'] ?? $year;
                $arr['types'] = $info['types'] ?? $type;
                ++$i;
            } while ($i < 12);
            $arr['user'] = $this->getTargetUser($linkId, (int) $arr['types'], $frames, $admins);
            $data[]      = $arr;
        }
        return $data;
    }

    /**
     * 获取目标完成度.
     * @return mixed
     */
    public function getTargetStatistics(array $where)
    {
        $list    = $this->dao->search($where)->get()?->toArray();
        $linkIds = array_unique(array_column($list, 'link_id'));
        if (! $linkIds) {
            return $this->getEmptyTargetStatistics();
        }
        $targetMap = $this->getTargetMap($list);
        $bill    = app(PaymentService::class)->getBillCensus((int) $where['year'], (int) $where['types']);
        $target  = $amount = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($linkIds as $linkId) {
            $targets = $targetMap[$linkId] ?? [];
            $i = 0;
            do {
                $target[$i] = sprintf('%.2f', bcadd((string) $target[$i], (string) ($targets[$i + 1]['amount'] ?? 0), 2));
                if (isset($bill[$i][$linkId])) {
                    $amount[$i] = sprintf('%.2f', bcadd((string) $amount[$i], (string) $bill[$i][$linkId]['price'], 2));
                } else {
                    $amount[$i] = sprintf('%.2f', bcadd((string) $amount[$i], '0', 2));
                }
                ++$i;
            } while ($i < 12);
        }
        $key = 0;
        do {
            $ratio[] = (is_numeric($target[$key]) ? (float) $target[$key] : 0) ? sprintf('%.2f', bcmul(bcdiv((string) $amount[$key], (string) $target[$key], 4), '100', 1)) : 100.0;
            ++$key;
            $xAxis[] = $key . '月';
        } while ($key < 12);
        $series = [
            ['data' => $target, 'name' => '目标'],
            ['data' => $amount, 'name' => '完成'],
            ['data' => $ratio, 'name' => '完成度'],
        ];
        return compact('xAxis', 'series');
    }

    private function getEmptyTargetStatistics(): array
    {
        $xAxis = $target = $amount = $ratio = [];
        for ($i = 1; $i <= 12; ++$i) {
            $xAxis[]  = $i . '月';
            $target[] = $amount[] = '0.00';
            $ratio[]  = 100.0;
        }

        $series = [
            ['data' => $target, 'name' => '目标'],
            ['data' => $amount, 'name' => '完成'],
            ['data' => $ratio, 'name' => '完成度'],
        ];

        return compact('xAxis', 'series');
    }

    private function getTargetMap(array $list): array
    {
        return collect($list)->groupBy('link_id')->map(function ($items) {
            return collect($items)->keyBy('month')->all();
        })->all();
    }

    private function getFirstTarget(array $targets): array
    {
        $target = reset($targets);
        return is_array($target) ? $target : [];
    }

    private function getTargetUser($linkId, int $type, array $frames, array $admins): array
    {
        if ($type) {
            return $frames[$linkId] ?? ['id' => $linkId, 'name' => '已删除部门'];
        }

        return $admins[$linkId] ?? ['id' => $linkId, 'name' => '已删除人员'];
    }
}
