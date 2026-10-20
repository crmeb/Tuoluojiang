<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Finance;

use App\Http\Dao\Finance\PaytypeDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\FormService as Form;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;

/**
 * 企业支付方式管理
 * Class PaytypeService.
 */
class PaytypeService extends BaseService
{
    /**
     * PaytypeService constructor.
     */
    public function __construct(PaytypeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['id', 'name', 'ident', 'info', 'status', 'sort', 'created_at', 'updated_at'], $sort = ['sort', 'created_at'], array $with = [], bool $withImportTemp = true): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where, $with);

        if (! $withImportTemp) {
            return compact('list', 'count');
        }

        $import_temp = '/static/temp/bill_import_temp.xlsx';
        return compact('list', 'count', 'import_temp');
    }

    /**
     * 创建支付方式获取表单.
     */
    public function resourceCreate(): array
    {
        return $this->createElementForm('支付方式', $this->getRankRuleForm(collect()), '/ent/pay_type');
    }

    /**
     * 修改支付方式获取表单信息.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id): array
    {
        $rankInfo = $this->dao->get($id);
        if (! $rankInfo) {
            throw $this->exception('修改的支付方式不存在');
        }
        return $this->createElementForm('支付方式', $this->getRankRuleForm(collect($rankInfo->toArray())), '/ent/pay_type/' . $id, 'put');
    }

    /**
     * 修改支付方式.
     * @param mixed $id
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data): bool
    {
        $info = $this->dao->get((int) $id);
        if (! $info) {
            throw $this->exception('修改的支付方式不存在');
        }
        return $info->update($data);
    }

    /**
     * 保存支付方式.
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data): bool
    {
        if ($this->dao->exists(['name' => $data['name'], 'ident' => $data['ident']])) {
            throw $this->exception('支付方式已存在，请勿重复添加');
        }
        $this->dao->create($data);
        return true;
    }

    /**
     * 修改支付方式状态
     * @param mixed $id
     * @param mixed $data
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceShowUpdate($id, $data): int
    {
        $info = $this->dao->get($id);
        if (! $info) {
            throw $this->exception('操作失败，记录不存在');
        }
        return $this->dao->update(['id' => $id], ['status' => $data['status']]);
    }

    /**
     * @param mixed $entid
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getBillPayType($entid)
    {
        return $this->dao->getList(['entid' => $entid, 'status' => 1], ['id as value', 'name as label'], 0, 0, ['sort', 'created_at']);
    }

    /**
     * 删除支付方式.
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (app()->get(BillService::class)->count(['entid' => 1, 'type_id' => $id])) {
            throw $this->exception('请先删除关联财务流水,再尝试删除');
        }
        return $this->dao->delete($id, $key);
    }

    /**
     * 获取支付方式名称.
     * @throws BindingResolutionException
     */
    public function getTypeName(int $id, int $entId = 1): string
    {
        if (! $id) {
            return '';
        }
        $entId = $entId ?: 1;
        $info  = $this->dao->get(['id' => $id, 'entid' => $entId], ['id', 'name']);
        if (! $info) {
            throw $this->exception('支付方式异常');
        }
        return $info->name ?? '';
    }

    /**
     * 获取支付方式表单规则.
     */
    protected function getRankRuleForm(Collection $collection): array
    {
        return [
            Form::input('name', '支付方式', $collection->get('name'))->required(),
            // Form::input('ident', '支付方式标识', $collection->get('ident'))->required(),
            Form::textarea('info', '简介', $collection->get('info'))->placeholder('简介内容')->rows(3),
            Form::radio('status', '是否开启', (int) ($collection->get('status') ?? 1))->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]),
            Form::number('sort', '排序', (int) ($collection['sort'] ?? 0))->min(0)->max(999999),
        ];
    }
}
