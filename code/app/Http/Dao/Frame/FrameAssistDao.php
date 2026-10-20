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

namespace App\Http\Dao\Frame;

use App\Http\Model\Frame\FrameAssist;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 组织架构关联.
 */
class FrameAssistDao extends BaseDao
{
    use BatchSearchTrait;

    /**
     * 根据条件查询出相关组织架构用户信息.
     * @param array|string[] $field
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getFrameUser(array $where, array $field = ['*'])
    {
        return $this->search($where)->groupBy(['frame_id'])->select($field)->get()->toArray();
    }

    /**
     * 获取单个部门下的人数.
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getUserSingleCount(int $frameId, int $entid)
    {
        return $this->search([
            'entid'    => $entid,
            'frame_id' => $frameId,
        ])->count();
    }

    /**
     * 获取单个部门下的userids.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getFrameUserIds(int $frameId, int $entid)
    {
        return $this->search([], false)->when($frameId, function ($query) use ($frameId, $entid) {
            $query->whereIn('frame_id', function ($query) use ($frameId, $entid) {
                $query->from('frame')
                    ->where('entid', $entid)
                    ->where('path', 'LIKE', '%/' . $frameId . '/%')
                    ->select(['id']);
            })->orWhere('frame_id', $frameId);
        })->select(['user_id'])->get()->map(function ($item) {
            return $item['user_id'];
        })->all();
    }

    /**
     * 修改部门主管
     * @return bool
     * @throws BindingResolutionException
     */
    public function updateFrameAdmin(int $frame_id, int $userId)
    {
        $res1 = $this->getModel()->updateOrCreate(['user_id' => $userId, 'frame_id' => $frame_id], [
            'user_id'  => $userId,
            'frame_id' => $frame_id,
            'is_admin' => 1,
        ]);
        $res2 = $this->getModel()->where('user_id', '<>', $userId)->where('frame_id', $frame_id)->update([
            'is_admin'     => 0,
            'superior_uid' => 0,
        ]);
        return $res1 && $res2;
    }

    /**
     * 删除部门主管
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function deleteFrameAdmin(int $frame_id)
    {
        return $this->getModel()->where('frame_id', $frame_id)->update(['is_admin' => 0]);
    }

    /**
     * 设置模型.
     * @return mixed|string
     */
    public function setModel()
    {
        return FrameAssist::class;
    }
}
