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

use App\Http\Dao\Customer\InvoiceCategoryDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;

/**
 * 客户订单发票类目
 * Class InvoiceCategoryService.
 * @method search($where, ?bool $authWhere = null)
 */
class InvoiceCategoryService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(InvoiceCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param string $sort
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'name', 'sort', 'created_at', 'updated_at'], $sort = ['sort', 'created_at'], array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 保持数据.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($this->dao->exists(['name' => $data['name']])) {
            throw $this->exception('当前类目已存在, 请勿重复添加');
        }
        return $this->dao->create($data);
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data): int
    {
        if ($this->dao->exists(['notid' => $id, 'name' => $data['name']])) {
            throw $this->exception('当前类目已存在，请确认后重试');
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 删除数据.
     * @param mixed $id
     */
    public function resourceDelete($id, ?string $key = null): int
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception(__('common.operation.noExists'));
        }

        if (app()->get(InvoiceService::class)->count(['category_id' => $id])) {
            throw $this->exception('当前类目已被使用, 无法删除');
        }
        return $this->dao->delete($id);
    }
}
