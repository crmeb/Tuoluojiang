<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Company;

use App\Http\Dao\Company\CompanyUserJobAnalysisDao;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\Frame\FrameService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 企业用户工作分析
 * Class CompanyUserJobAnalysisService.
 */
class CompanyUserJobAnalysisService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(CompanyUserJobAnalysisDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取工作分析内容.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInfo(int $id): array
    {
        $userInfo = app()->get(AdminService::class)->get($id, ['id', 'name', 'avatar', 'uid', 'phone'])?->toArray();
        if (! $userInfo) {
            throw $this->exception('用户信息不存在');
        }

        $info = $this->dao->get(['uid' => $id], ['data', 'created_at', 'updated_at'])?->toArray();
        if ($info) {
            $info['uid']  = $id;
            $info['card'] = $userInfo;
        }
        return $info ?: [];
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @return bool
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        $entId    = 1;
        $userInfo = app()->get(AdminService::class)->get($id);
        if (! $userInfo) {
            throw $this->exception('用户信息不存在');
        }
        $time  = date('Y-m-d H:i:s');
        $where = ['entid' => $entId, 'uid' => $id];
        $info  = $this->dao->get($where);
        if ($info) {
            $info->data       = $data['data'];
            $info->updated_at = $time;
            $res              = $info->save();
        } else {
            $res = $this->create(array_merge($where, ['data' => $data['data'], 'created_at' => $time]));
        }
        return (bool) $res;
    }

    /**
     * 根据uid获取工作分析.
     * @throws BindingResolutionException
     */
    public function getInfoByUid(int $uid, int $entId): array
    {
        return toArray($this->get(['entid' => $entId, 'uid' => $uid], ['uid', 'data', 'created_at', 'updated_at']));
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        return [];
    }

    /**
     * 工作分析表人员.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getJobAnalysisList(array $where, string $uuid): array
    {
        [$page, $limit] = $this->getPageValue();
        $service        = app()->get(AdminService::class);
        if ($where['frame_id']) {
            $where['frame_ids'] = app()->get(FrameService::class)->column(['path' => $where['frame_id'], 'not_id' => $where['frame_id']], 'id');
        } else {
            $where['ids'] = app()->get(FrameService::class)->getLevelSub(uuid_to_uid($uuid));
        }
        $with = [
            'frames' => fn ($query) => $query->select(['frame.id', 'frame.name', 'frame_assist.is_mastart', 'frame_assist.is_admin']),
            'frame'  => fn ($query) => $query->select(['frame.id', 'frame.name', 'frame_assist.is_mastart', 'frame_assist.is_admin']),
            'job'    => fn ($query) => $query->select(['id', 'name']),
        ];
        $sort  = ['is_admin', 'frame_assist.is_admin', 'id' => 'asc'];
        $list  = $service->listSearch($where, $page, $limit, $sort, $with)->get()?->toArray();
        $count = $service->listSearch($where)->count('admin.id');
        return $this->listData($list, $count);
    }
}
