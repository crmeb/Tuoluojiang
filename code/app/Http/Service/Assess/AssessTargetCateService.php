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

namespace App\Http\Service\Assess;

use App\Http\Dao\Access\AssessTargetCategoryDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\services\synchro\Company;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 模板分类service
 * Class TargetCateService.
 */
class AssessTargetCateService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    /**
     * TargetCateService constructor.
     */
    public function __construct(AssessTargetCategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null $sort
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        return Cache::tags(['assess_category_' . $where['types']])->remember(md5(json_encode($where)), 60, function () use ($where) {
            return app()->get(Company::class)->assessCate($where['types'], ['*', 'id as value', 'name as label']);
        });
    }

    /**
     * 获取模板分类tree型数据.
     * @param mixed $where
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getTargetCateTree($where)
    {
        return get_tree_children($this->dao->getList($where, ['name as label', 'id as value', 'pid'], 0, 0, 'id'), 'children', 'value');
    }

    /**
     * 获取修改模板分类表单.
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $rankCateInfo = $this->dao->get($id);
        if (! $rankCateInfo) {
            throw $this->exception('修改的模板分类不存在');
        }
        return $this->createElementForm('修改模板分类', $this->getBillCateFormRule(collect($rankCateInfo->toArray())), '/admin/enterprise/assess/category/' . $id, 'PUT');
    }

    /**
     * 获取创建模板分类表单.
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加模板分类', $this->getBillCateFormRule(collect($other)), '/admin/enterprise/assess/category');
    }

    /**
     * 保存数据.
     * @return BaseModel|mixed|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $path = $data['path'] ?? [];
        if ($path) {
            $data['pid'] = $path[count($path) - 1];
        }
        if (! $data['pid']) {
            $data['pid'] = 0;
        }
        if ($this->dao->exists($data)) {
            throw $this->exception('分类已存在，请勿重复添加！');
        }
        return $this->dao->create($data);
    }

    /**
     * 修改数据.
     * @param int $id
     * @return int|mixed
     * @throws BindingResolutionException
     */
    public function resourceUpdate($id, array $data)
    {
        $path = $data['path'] ?? [];
        if ($path) {
            $data['pid'] = $path[count($path) - 1];
        }
        if (! $data['pid']) {
            $data['pid'] = 0;
        }
        $this->dao->update($id, $data);
        return true;
    }

    /**
     * 删除模板分类.
     * @return int|mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        /** @var EnterpriseTargetServices $service */
        $service = app()->get(EnterpriseTargetServices::class);
        if ($service->count(['cate_id' => $id])) {
            throw $this->exception('请先删除关联指标,再尝试删除');
        }
        return $this->dao->delete($id, $key);
    }

    /**
     * 获取模板分类表单规则.
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getBillCateFormRule(Collection $collection)
    {
        return [
            Form::input('name', '分类名称', $collection->get('name'))->required(),
            //            Form::switches('types', '分类类型', $collection->get('types', 0))->inactiveValue(0)->activeValue(1)->inactiveText('指标模板')->activeText('分类模板'),
            Form::hidden('types', $collection->get('types', 0)),
        ];
    }
}
