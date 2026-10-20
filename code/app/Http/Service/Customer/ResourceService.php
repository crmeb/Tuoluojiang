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

use App\Http\Dao\Customer\ResourceDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 订单附件.
 */
class ResourceService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(ResourceDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取合同订单附件列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'created_at', array $with = ['attachs', 'user']): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    /**
     * 保存附件.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function save(array $data = []): mixed
    {
        $attachIds = $data['attach_ids'];
        unset($data['attach_ids']);

        $uuid        = $this->uuId(false);
        $data['uid'] = uuid_to_uid((string) $uuid);
        $eid         = app()->get(OrderService::class)->value(['id' => $data['cid']], 'eid');
        if (! $eid) {
            throw $this->exception('客户信息获取异常');
        }

        $data['eid'] = $eid;
        $res         = $this->dao->create($data);
        if (! $res) {
            throw $this->exception('保存失败');
        }
        app()->get(AttachService::class)->saveRelation($attachIds, (string) $uuid, $res->id, AttachService::RELATION_TYPE_CONTRACT);
        return $res;
    }

    /**
     * 修改附件.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update(int $id, array $data): mixed
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('附件不存在');
        }

        $info->content = $data['content'];
        $res           = $info->save();
        if (! $res) {
            throw $this->exception('修改失败');
        }
        app()->get(AttachService::class)->saveRelation($data['attach_ids'], (string) $this->uuId(false), $info->id, AttachService::RELATION_TYPE_CONTRACT);
        return $res;
    }

    /**
     * 删除合同订单.
     * @param mixed $id
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function delete($id): int
    {
        $entId = 1;
        $info  = $this->dao->get(['id' => $id, 'entid' => $entId]);
        if (! $info) {
            throw $this->exception('附件不存在');
        }

        return $this->dao->delete(['id' => $id, 'entid' => $entId]);
    }

    /**
     * 详情.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(array $where, array $field = ['*'], array $with = ['attachs', 'user']): array
    {
        $info = $this->dao->get($where, $field, $with);
        if (! $info) {
            throw $this->exception('附件不存在');
        }
        return $info->toArray();
    }
}
