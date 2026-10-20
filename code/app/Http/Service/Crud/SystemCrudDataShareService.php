<?php

namespace App\Http\Service\Crud;

use App\Http\Dao\Crud\SystemCrudDataShareDao;
use App\Http\Service\BaseService;

/**
 * 数据共享记录
 */
class SystemCrudDataShareService extends BaseService
{
    /**
     * @param SystemCrudDataShareDao $dao
     */
    public function __construct(SystemCrudDataShareDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取共享的记录id
     * @param int $crudId
     * @param int $userId
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getUserShareDataIds(int $crudId, int $userId)
    {
        if (!$userId) {
            return [];
        }

        return $this->dao->column(['crud_id' => $crudId, 'user_id' => $userId], 'data_id');
    }

    /**
     * 根据共享id获取数据id
     * @param int $crudId
     * @param array $shareIds
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function shareIdByDataIds(int $crudId, array $shareIds)
    {
        if (!$shareIds) {
            return [];
        }

        return $this->dao->column(['crud_id' => $crudId, 'share_id' => $shareIds], 'data_id');
    }
}
