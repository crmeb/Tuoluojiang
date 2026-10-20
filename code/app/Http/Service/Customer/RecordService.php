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

use App\Constants\CustomEnum\CustomEnum;
use App\Http\Contract\Customer\CustomerRecordInterface;
use App\Http\Dao\Customer\RecordDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 动态记录Service.
 * @mixin RecordDao
 */
class RecordService extends BaseService implements CustomerRecordInterface
{
    public function __construct(RecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'id', array $with = ['creator', 'follow']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = collect($this->dao->setDefaultSort($sort)->select($where, $field, $with, $page, $limit)?->toArray() ?? [])
            ->map(function ($item) {
                $item['attachs']   = [];
                $item['follow_id'] = 0;
                if ($item['type'] == CustomEnum::LINK_FOLLOW) {
                    $item['reason']    = $item['follow'] ? $item['follow']['content'] : '';
                    $item['follow_id'] = $item['follow'] ? $item['follow']['id'] : 0;
                    $item['attachs']   = collect($item['follow']['attachs'] ?? [])->map(function ($val) {
                        $val['url'] = $val['url'] ? link_file($val['url']) : '';
                        return $val;
                    })->all();
                }
                unset($item['follow']);
                return $item;
            })->all();
        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取最新客户退回原因.
     * @return null|mixed
     * @throws BindingResolutionException
     */
    public function getLastReasonByEid(int $eid, int $type): ?string
    {
        return $this->dao->value(['eid' => $eid, 'type' => $type], 'reason') ?: '';
    }

    public function saveRecord(string $linkType, array $params)
    {
        $params['link_type'] = $linkType;
        return $this->dao->create($params);
    }
}
