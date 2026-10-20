<?php

namespace App\Http\Service\Crud;


use App\Http\Dao\Crud\SystemCrudOperateDao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SystemCrudOperateService extends BaseService
{
    /**
     * @param SystemCrudOperateDao $dao
     */
    public function __construct(SystemCrudOperateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取操作按钮
     * @param int $crudId
     * @param int|string $status
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getOperateList(int $crudId, int|string $status = '')
    {
        return $this->dao->select(['crud_id' => $crudId, 'status' => $status]);
    }

    /**
     * 获取操作按钮
     * @param int $crudId
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getOperate(int $crudId)
    {
        $list = $this->dao->select(['crud_id' => $crudId, 'status' => 1]);
        $row = [];
        $head = [];
        foreach ($list as $item) {
            if ($item['operate_type']) {
                $head[] = $item;
            } else {
                $row[] = $item;
            }
        }

        return compact('head', 'row');
    }
}
