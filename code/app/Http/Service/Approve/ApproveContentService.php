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

use App\Http\Dao\Approve\ApproveContentDao;
use App\Http\Service\System\SystemCityService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 申请内容表
 * Class ApproveContentService.
 * @method null|array|Model select($where, array $field = [], array $with = [], int $page = 0, int $limit = 0) 获取多条数据
 */
class ApproveContentService extends BaseService
{
    protected array $blackSymbol = [
        'billId',
    ];

    public function __construct(ApproveContentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 批量保存数据.
     * @param mixed $data
     * @param mixed $applyId
     * @return bool
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function saveMore($data, $applyId)
    {
        $uniques = [];
        foreach ($data as $val) {
            unset($val['id']);
            if ($this->dao->exists(['apply_id' => $applyId, 'uniqued' => $val['uniqued']])) {
                $this->dao->update(['apply_id' => $applyId, 'uniqued' => $val['uniqued']], $val);
            } else {
                $val['apply_id'] = $applyId;
            }
            $this->dao->create($val);
            $uniques[] = $val['uniqued'];
        }
        $this->dao->delete(['apply_id' => $applyId, 'not_uniqued' => $uniques]);
        return true;
    }

    /**
     * 获取审批内容.
     * @param mixed $filter
     * @param mixed $applyId
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function getContent($applyId, $filter = [])
    {
        $assistService = app(ApproveAssistService::class);
        return collect($this->dao->select(['apply_id' => $applyId])?->toArray() ?? [])->map(function ($item) use ($filter, $applyId, $assistService) {
            if ($filter && in_array($item['types'], $filter)) {
                return [];
            }
            $content = [
                'uniqued' => $item['uniqued'],
                'type'    => $item['types'],
            ];
            if ($item['symbol'] && method_exists(ApproveAssistService::class, $item['symbol'])) {
                if (in_array($item['symbol'], $this->blackSymbol)) {
                    return [];
                }
                $data = $item['value'];
                if ($item['symbol'] == 'attendanceExceptionRecord') {
                    $data = ['value' => $item['value'], 'apply_id' => $applyId];
                }
                $content['value'] = $assistService->{$item['symbol']}(uid: $item['user_id'], data: ['customer_id' => 0, 'bill_id' => [], 'invoice_id' => 0, 'contract_id' => 0], child: [], value: $data);
            } else {
                if ($item['types'] == 'approvalBill') {
                    $content['value'] = collect($item['value'] ?? [])->map(function ($val) use ($item) {
                        return $this->formatItemValue($item['content']['children'] ?? [], $val);
                    });
                } elseif ($item['types'] == 'uploadFrom') {
                    isset($item['value']['id']) ? $content['value'][] = $item['value'] : $content['value'] = $item['value'];
                } else {
                    $content['value'] = $this->formatValue($item['types'], $item['value']);
                }
            }
            if (isset($item['content']['title']) && $item['content']['title']) {
                $content['label'] = $item['content']['title'];
                $content['value'] = $content['value'] ?: ($item['content']['children'][0] ?? '');
            } elseif (isset($item['content']['props'])) {
                $content['label'] = $item['content']['props']['titleIpt'];
            }
            return $content;
        })->filter();
    }

    /**
     * 格式化时长.
     * @param mixed $value
     * @return array|array[]
     */
    private function formatTimeFrom($value)
    {
        if ($value['timeType'] == 'day') {
            return [
                [
                    'label' => '开始时间',
                    'value' => $value['dateStart'] . ($value['timeStart'] ? ' 上午' : ' 下午'),
                ],
                [
                    'label' => '结束时间',
                    'value' => $value['dateEnd'] . ($value['timeEnd'] ? ' 上午' : ' 下午'),
                ],
                [
                    'label' => '时长',
                    'value' => $value['duration'] . ' 天',
                ],
            ];
        }
        return [
            [
                'label' => '开始时间',
                'value' => $value['dateStart'],
            ],
            [
                'label' => '结束时间',
                'value' => $value['dateEnd'],
            ],
            [
                'label' => '时长',
                'value' => $value['duration'] . ' 小时',
            ],
        ];
    }

    /**
     * 格式化值.
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function formatItemValue(array $data, mixed $value)
    {
        $result = [];
        foreach ($data as $val) {
            $arr = [
                'label'   => $val['title'] ?? '',
                'uniqued' => $val['field'],
                'type'    => $val['type'],
            ];
            $arr['value'] = $value[$val['field']] ?? '';
            if ($val['type'] == 'city') {
                if (is_numeric($value[$val['field']])) {
                    $arr['value'] = app()->get(SystemCityService::class)->get(['city_id' => $value[$val['field']]], with: ['ancestors'])?->hierarchy_name;
                } else {
                    $arr['value'] = implode('/', $value[$val['field']]['city']) ?? '';
                }
            }
            if ($val['type'] == 'timeFrom') {
                $arr['value'] = $this->formatTimeFrom($value[$val['field']]);
            } elseif (is_array($arr['value'])) {
                if ($val['type'] == 'checkbox') {
                    $arr['value'] = implode('，', $arr['value']);
                } else {
                    $arr['value'] = implode('，', array_column($arr['value'], 'name'));
                }
            }
            $result[] = $arr;
        }
        return $result;
    }

    /**
     * 格式化值.
     * @return null|mixed|string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function formatValue(string $types, mixed $value)
    {
        if ($types == 'city') {
            if (is_numeric($value)) {
                return app()->get(SystemCityService::class)->get(['city_id' => $value], with: ['ancestors'])?->hierarchy_name;
            }
            return implode('/', $value['city']) ?? '';
        }
        if (is_array($value)) {
            if ($types == 'checkbox') {
                return implode('，', $value);
            }
            return implode('，', array_column($value, 'name'));
        }
        return $value;
    }
}
