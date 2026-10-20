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

namespace App\Http\Service\Config;

use App\Http\Dao\Config\QuickDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\services\FormService as Form;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use FormBuilder\Exception\FormBuilderException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class QuickService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public function __construct(QuickDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @param mixed $sort
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], $sort = 'sort', array $with = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 获取创建表单.
     * @throws FormBuilderException
     */
    public function resourceCreate(array $other = []): array
    {
        return $this->createElementForm('添加数据', $this->createFormRule(collect($other)), '/ent/config/quick');
    }

    /**
     * 保存快捷入口.
     * @throws \ReflectionException
     */
    public function resourceSave(array $data): mixed
    {
        if ($this->dao->exists(['name' => $data['name']])) {
            throw $this->exception('快捷入口已存在，请勿重复添加！');
        }
        return $this->dao->create($data);
    }

    /**
     * 修改表单.
     * @throws FormBuilderException
     * @throws \ReflectionException
     */
    public function resourceEdit(int $id, array $other = []): array
    {
        $info = $this->dao->get($id, ['*'], ['cate']);
        if (! $info) {
            throw $this->exception('修改的快捷入口数据不存在');
        }
        return $this->createElementForm('修改数据', $this->createFormRule(collect($info->toArray())), '/ent/config/quick/' . $id, 'PUT');
    }

    /**
     * 保存修改.
     * @param mixed $id
     * @throws \ReflectionException
     */
    public function resourceUpdate($id, array $data)
    {
        if ($this->dao->exists(['name' => $data['name'], 'not_id' => $id])) {
            throw $this->exception('快捷入口已存在，请勿重复添加！');
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 创建表单.
     * @throws \ReflectionException
     */
    public function createFormRule(Collection $collect): array
    {
        $cateList = app()->get(QuickCateService::class)->getTierList([
            'type'    => 'quickConfig',
            'is_show' => 1,
        ], ['pid', 'id as value', 'cate_name as label']);
        return [
            Form::select('cid', '分类')->options($cateList)->value((int) $collect->get('cid', 0))->validate([Form::validateNum()->required()->message('请选择分类')]),
            Form::input('name', '标题名称', $collect->get('name', ''))->required()->maxlength(50),
            Form::input('pc_url', 'PC端地址', $collect->get('pc_url', ''))->required()->maxlength(120)->placeholder('非正确路径不会显示'),
            Form::input('uni_url', '移动端地址', $collect->get('uni_url', ''))->maxlength(120)->placeholder('非正确路径不会显示'),
            Form::frameImage('image', '图标', get_image_frame_url(['field' => 'image', 'type' => 1]), $collect->get('image') ?? '')->icon('el-icon-plus')->width('1250px')->height('590px')->modal(['modal' => true, 'showCancelButton' => false, 'showConfirmButton' => false])->props(['footer' => false]),
            //            Form::radio('types', '菜单类型', intval($collect->get('types', 1)))->options([['value' => 0, 'label' => '个人菜单'], ['value' => 1, 'label' => '企业菜单']]),
            Form::radio('pc_show', 'PC端显示', intval($collect->get('pc_show', 1)))->options([['value' => 0, 'label' => '隐藏'], ['value' => 1, 'label' => '显示']]),
            Form::radio('uni_show', '移动端显示', intval($collect->get('uni_show', 1)))->options([['value' => 0, 'label' => '隐藏'], ['value' => 1, 'label' => '显示']]),
            Form::number('sort', '排序', (int) $collect->get('sort', 1)),
            Form::radio('status', '状态', (int) $collect->get('status', 1))->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]),
        ];
    }

    /**
     * 修改状态
     * @param mixed $id
     */
    public function resourceShowUpdate($id, array $data): mixed
    {
        return $this->showUpdate($id, $data);
    }
}
