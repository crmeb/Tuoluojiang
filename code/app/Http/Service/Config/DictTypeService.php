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

namespace App\Http\Service\Config;

use App\Constants\CacheEnum;
use App\Http\Dao\Config\DictTypeDao;
use App\Http\Service\Crud\SystemCrudFieldService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class DictTypeService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * 可编辑的字典.
     * @var array|string[]
     */
    protected array $canDeleteData = [
        'customer_way',
        'customer_type',
        'client_renew',
        'contract_type',
        'area_cascade',
    ];

    /**
     * 可编辑的字典.
     * @var array|string[]
     */
    protected array $canEditData = [
        'area_cascade',
    ];

    public function __construct(DictTypeDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, ['*'], $page, $limit, 'id');
        $count          = $this->dao->count($where);

        $ids = array_column($list, 'id');
        if ($ids) {
            $crudList = app()->get(SystemCrudFieldService::class)
                ->getModel()
                ->with([
                    'crud' => fn ($q) => $q->select('id', 'table_name'),
                ])
                ->whereIn('data_dict_id', $ids)
                ->groupBy('data_dict_id')
                ->select(['crud_id', 'data_dict_id', 'id'])->get()->toArray();
            foreach ($list as &$item) {
                $item['crud_name'] = [];
                foreach ($crudList as $value) {
                    if ($item['id'] === $value['data_dict_id'] && ! empty($value['crud']['table_name'])) {
                        $item['crud_name'][] = $value['crud']['table_name'];
                    }
                }
            }
        }

        return $this->listData($list, $count);
    }

    /**
     * 字典信息.
     * @param mixed $id
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function info($id)
    {
        $info = toArray($this->dao->get($id));
        if (in_array($info['ident'], $this->canDeleteData)) {
            $info['is_default'] = 0;
        } elseif (in_array($info['ident'], $this->canEditData) && $this->isBinding($info['ident'])) {
            $info['is_default'] = 0;
        }
        return $info;
    }

    /**
     * 新增表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('新增字典', $this->getFormRule(collect($other)), '/ent/config/dict_type');
    }

    /**
     * 保存数据.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        if ($this->dao->exists(['ident' => $data['ident']]) || $this->dao->exists(['name' => $data['name']])) {
            throw $this->exception('字典已存在, 请勿重复添加');
        }
        $res = $this->dao->create($data);
        Cache::tags([CacheEnum::TAG_DICT])->flush();
        return $res;
    }

    /**
     * 修改表单.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $info = toArray($this->dao->get($id));
        if (! $info) {
            throw $this->exception('修改的字典不存在');
        }
        return $this->createElementForm('修改字典', $this->getFormRule(collect($info), true), '/ent/config/dict_type/' . $id, 'PUT');
    }

    /**
     * 修改数据.
     * @param mixed $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($this->dao->exists(['not_id' => $id, 'name' => $data['name']])) {
            throw $this->exception('字典名称已存在, 请勿重复添加');
        }
        if ($this->dao->exists(['not_id' => $id, 'ident' => $data['ident']])) {
            throw $this->exception('字典标识已存在, 请勿重复添加');
        }

        $res = $this->dao->update($id, $data);
        Cache::tags([CacheEnum::TAG_DICT])->flush();
        return $res;
    }

    public function resourceShowUpdate($id, array $data)
    {
        Cache::pull(md5('dict_data_' . $id));
        $res = $this->dao->update($id, $data);
        Cache::tags([CacheEnum::TAG_DICT])->flush();
        return $res;
    }

    /**
     * 删除字典数据.
     * @param mixed $id
     * @return int|mixed
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (str_contains($id, ',')) {
            $id = explode(',', $id);
        }
        if (app()->get(DictDataService::class)->count(['type_id' => $id])) {
            throw $this->exception('字典存在关联数据，无法删除');
        }
        if ($this->dao->exists(['id' => $id, 'is_default' => 1])) {
            throw $this->exception('默认字典无法删除');
        }
        $res = $this->dao->delete($id, 'id');
        Cache::tags([CacheEnum::TAG_DICT])->flush();
        return $res;
    }

    private function isBinding($ident)
    {
        return ! app()->get(\App\Http\Service\Config\FormService::class)->dataDao->exists(['dict_ident' => $ident]);
    }

    /**
     * 获取表单规则.
     * @return array
     */
    private function getFormRule(Collection $collection, bool $edit = false)
    {
        return [
            FormService::input('name', '字典名称', $collection->get('name', ''))->required(),
            FormService::input('ident', '字典标识', $collection->get('ident', ''))->required(),
            $edit ? FormService::radio('level', '字典类型', (int) $collection->get('level', 1))->options([['value' => 1, 'label' => '单级'], ['value' => 2, 'label' => '标签'], ['value' => 4, 'label' => '多级']])->disabled(true)
            : FormService::radio('level', '字典类型', (int) $collection->get('level', 1))->options([['value' => 1, 'label' => '单级'], ['value' => 2, 'label' => '标签'], ['value' => 4, 'label' => '多级']]),
            FormService::radio('status', '状态', (int) $collection->get('status', 1))->options([['value' => 1, 'label' => '启用'], ['value' => 0, 'label' => '停用']]),
            FormService::textarea('mark', '备注信息', $collection->get('mark', ''))->placeholder('请输入备注信息，最多可输入200字')->maxlength(200),
        ];
    }
}
