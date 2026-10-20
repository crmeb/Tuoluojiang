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
use App\Constants\System\ViewSearchEnum;
use App\Http\Contract\Customer\CustomerSubscribeInterface;
use App\Http\Dao\Customer\SubscribeDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 客户关注.
 * @mixin SubscribeDao
 */
class SubscribeService extends BaseService implements CustomerSubscribeInterface
{
    use ResourceServiceTrait;

    public function __construct(SubscribeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 关注.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function subscribe(int $uid, int $eid, int $status, int $type = 0): bool
    {
        $status = $status == 1 ? 1 : 0;
        $where  = ['entid' => 1, 'uid' => $uid, 'eid' => $eid, 'types' => $type];
        $info   = $this->dao->get($where);
        if ($info) {
            $info->subscribe_status = $status;
            $res                    = $info->save();
        } else {
            $res = $this->dao->create(array_merge($where, ['subscribe_status' => $status]));
        }
        if (! $res) {
            throw $this->exception(__('common.operation.fail'));
        }
        return true;
    }

    /**
     * 关注.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function setSubscribe(int $uid, int $eid, int $status, string $customType): bool
    {
        $customType = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => CustomEnum::CUSTOMER,
            ViewSearchEnum::VIEW_CONTRACT => CustomEnum::CONTRACT,
            ViewSearchEnum::VIEW_LIAISON  => CustomEnum::LIAISON,
            ViewSearchEnum::VIEW_CLUE     => CustomEnum::CLUE,
            ViewSearchEnum::VIEW_PRODUCT  => CustomEnum::PRODUCT,
            ViewSearchEnum::VIEW_ODDS     => CustomEnum::ODDS,
            default                       => 0
        };
        $status = $status == 1 ? 1 : 0;
        $where  = ['entid' => 1, 'uid' => $uid, 'eid' => $eid, 'types' => $customType];
        $info   = $this->dao->get($where);
        if ($info) {
            $info->subscribe_status = $status;
            $res                    = $info->save();
        } else {
            $res = $this->dao->create(array_merge($where, ['subscribe_status' => $status]));
        }
        if (! $res) {
            throw $this->exception(__('common.operation.fail'));
        }
        return true;
    }

    /**
     * 获取关注.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSubscribe(int $uid, int $eid, string $customType): int
    {
        $customType = match ($customType) {
            ViewSearchEnum::VIEW_CUSTOMER => CustomEnum::CUSTOMER,
            ViewSearchEnum::VIEW_CONTRACT => CustomEnum::CONTRACT,
            ViewSearchEnum::VIEW_LIAISON  => CustomEnum::LIAISON,
            ViewSearchEnum::VIEW_CLUE     => CustomEnum::CLUE,
            ViewSearchEnum::VIEW_PRODUCT  => CustomEnum::PRODUCT,
            ViewSearchEnum::VIEW_ODDS     => CustomEnum::ODDS,
            default                       => 0
        };
        return (int) $this->dao->value(['uid' => $uid, 'eid' => $eid, 'subscribe_status' => 1, 'types' => $customType], 'subscribe_status');
    }

    /**
     * 客户关注状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSubscribeStatusWithEid(int $uid, array $eids, int $type = 0): array
    {
        return $this->dao->column(['uid' => $uid, 'eid' => $eids, 'subscribe_status' => 1, 'types' => $type], 'subscribe_status', 'eid');
    }

    /**
     * 关注状态
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSubscribeStatus(int $uid, int $eid, int $type = 1): int
    {
        return (int) $this->dao->value(['uid' => $uid, 'eid' => $eid, 'subscribe_status' => 1, 'types' => $type], 'subscribe_status');
    }

    /**
     * 合同订单关注数量.
     */
    public function contractCount(int|array $uid, int $subscribeUid): int
    {
        if (is_array($uid) && ! $uid) {
            return 0;
        }

        return (int) $this->dao->getModel()
            ->where('uid', $subscribeUid)
            ->where('subscribe_status', 1)
            ->where('types', CustomEnum::CONTRACT)
            ->whereExists(function ($query) use ($uid) {
                $query->selectRaw('1')
                    ->from('contract')
                    ->whereColumn('contract.id', 'client_subscribe.eid')
                    ->whereNull('contract.deleted_at')
                    ->when(is_array($uid), fn ($query) => $query->whereIn('contract.uid', $uid), fn ($query) => $query->where('contract.uid', $uid));
            })
            ->count();
    }
}
