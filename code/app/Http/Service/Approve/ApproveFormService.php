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

namespace App\Http\Service\Approve;

use App\Constants\CommonEnum;
use App\Http\Dao\Approve\ApproveFormDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 审核流程表单.
 */
class ApproveFormService extends BaseService
{
    public function __construct(ApproveFormDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param mixed $data
     * @param mixed $approve_id
     * @return true
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function saveMore($data, $approve_id)
    {
        foreach ($data as $val) {
            $val['approve_id'] = $approve_id;
            if ($this->dao->exists(['approve_id' => $approve_id, 'uniqued' => $val['uniqued']])) {
                $this->dao->update(['approve_id' => $approve_id, 'uniqued' => $val['uniqued']], $val);
            } else {
                $this->dao->create($val);
            }
        }
        return true;
    }

    /**
     * 提交审批表单.
     * @param mixed $origin
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getApplyForm(int $id, int $uid, array $data = [], string $origin = CommonEnum::ORIGIN_WEB)
    {
        $forms = $this->dao->setDefaultSort(['sort' => 'asc'])->column(['approve_id' => $id], 'content');
        $info  = app(ApproveService::class)->get($id, ['types', 'name', 'icon', 'color', 'info', 'examine'])?->toArray();
        foreach ($forms as &$form) {
            if (is_array($form)) {
                $form = $this->enrichApplyFormNode($form, $uid, $data, $origin);
            }
        }
        unset($form);

        return compact('info', 'forms');
    }

    /**
     * 获取审批配置所有唯一值
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUniques(int $approve_id): array
    {
        return collect($this->dao->select(['approve_id' => $approve_id], ['uniqued', 'symbol', 'content', 'title'])?->toArray() ?? [])
            ->flatMap(function ($item) {
                $parentTitle = $item['title'] ?: ($item['content']['title'] ?? ($item['content']['props']['titleIpt'] ?? ''));
                $nodes[]     = [
                    'value'  => $item['uniqued'],
                    'label'  => $parentTitle,
                    'symbol' => $item['symbol'] ?? '',
                    'type'   => $item['content']['type'],
                ];
                if ($item['content']['type'] !== 'approvalBill' && isset($item['content']['children'])) {
                    $children = collect($item['content']['children'])
                        ->map(function ($child) {
                            $childTitle = $child['title'] ?: ($child['props']['titleIpt'] ?? '');
                            $arr        = [
                                'value'  => $child['field'],
                                'label'  => $childTitle,
                                'symbol' => $child['symbol'] ?? '',
                                'type'   => $child['type'],
                            ];
                            if ($child['type'] === 'approvalBill') {
                                $arr['children'] = collect($child['children'] ?? [])->map(function ($val) {
                                    $valTitle = $val['title'] ?: ($val['props']['titleIpt'] ?? '');
                                    return [
                                        'value'  => $val['field'],
                                        'label'  => $valTitle,
                                        'symbol' => $val['symbol'] ?? '',
                                        'type'   => $val['type'],
                                    ];
                                })->all();
                            }
                            return $arr;
                        })->all();
                    $nodes = array_merge($nodes, $children);
                }
                return $nodes;
            })->filter(fn ($item) => $item['label'])->values()->all();
    }

    /**
     * 处理带children的表单子项数据组装.
     * @param array $datum 单条数据
     * @param array $children 子项配置
     * @return array 组装后的子项数组
     */
    public function processFormChildren(array $datum, array $children): array
    {
        return collect($children)
            ->filter(function ($childChild) use ($datum) {
                $childSymbol = Str::snake($childChild['symbol']);
                return isset($datum[$childSymbol]);
            })
            ->mapWithKeys(function ($childChild) use ($datum) {
                $childSymbol = Str::snake($childChild['symbol']);
                return [$childChild['value'] => $datum[$childSymbol]];
            })
            ->all();
    }

    /**
     * 递归 enrichment：系统 symbol 字段填充 options/default.
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function enrichApplyFormNode(array $node, int $uid, array $data, string $origin): array
    {
        if (! empty($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as &$child) {
                if (is_array($child)) {
                    $child = $this->enrichApplyFormNode($child, $uid, $data, $origin);
                }
            }
            unset($child);

            return $node;
        }

        return $this->enrichApplyFormLeaf($node, $uid, $data, $origin);
    }

    /**
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    private function enrichApplyFormLeaf(array $node, int $uid, array $data, string $origin): array
    {
        $symbol = (string) ($node['symbol'] ?? '');
        if ($symbol === '' || ! method_exists(ApproveAssistService::class, $symbol)) {
            return $node;
        }

        $enriched = app(ApproveAssistService::class)->{$symbol}(uid: $uid, data: $data, child: $node, origin: $origin);

        return is_array($enriched) ? $enriched : $node;
    }
}
