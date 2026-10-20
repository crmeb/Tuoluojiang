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

namespace App\Http\Controller\AdminApi\Config;

use App\Constants\CacheEnum;
use App\Http\Requests\system\DIctDataRequest;
use App\Http\Service\Config\DictDataService;
use App\Http\Service\Config\DictTypeService;
use App\Http\Service\Crud\SystemCrudFieldService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Controller\BaseController;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 数据字典-数据.
 */
#[Prefix('ent/config/dict_data')]
#[Resource('/', false, names: [
    'index'   => '获取字典数据列表接口',
    'create'  => '获取添加字典数据接口',
    'store'   => '添加字典保存数据接口',
    'show'    => '显示隐藏字典数据接口',
    'edit'    => '获取修改字典数据接口',
    'update'  => '修改字典数据保存接口',
    'destroy' => '删除字典数据接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class DictDataController extends BaseController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    public function __construct(DictDataService $service)
    {
        $this->service = $service;
        parent::__construct();
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    #[Post('tree', '获取字典数据树形结构')]
    public function tree()
    {
        $where = $this->request->postMore($this->getSearchField());
        return $this->success($this->service->getTreeData($where));
    }

    /**
     * 字典数据保存.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('crud_save', '保存字典数据接口')]
    public function crudSave(SystemCrudService $crudService, SystemCrudFieldService $service, DictTypeService $typeService)
    {
        $crudId  = $this->request->post('crud_id');
        $fieldId = $this->request->post('field_id');
        $id      = $this->request->post('data_id');

        $data = $this->request->postMore($this->getRequestFields());
        if (! $data['name']) {
            return $this->fail('请填写数据值');
        }
        $fieldInfo = $service->get($fieldId, ['field_name', 'field_name_en']);
        if (! $fieldInfo) {
            return $this->fail('字段不存在');
        }
        $crudInfo = $crudService->get($crudId, ['table_name', 'table_name_en']);
        if (! $crudInfo) {
            return $this->fail('数据表不存在');
        }

        $typeInfo = $typeService->getModel()->where(['crud_id' => $crudId, 'field_id' => $fieldId])->first();
        if (! $typeInfo) {
            $typeInfo = $typeService->create([
                'crud_id'  => $crudId,
                'field_id' => $fieldId,
                'name'     => $crudInfo->table_name . '-' . $fieldInfo->field_name,
                'ident'    => $crudInfo->table_name_en . '_' . $fieldInfo->field_name_en,
                'level'    => 4,
                'status'   => 1,
            ]);
        }

        $data['type_id'] = $typeInfo->id;

        if ($id) {
            $this->service->update($id, $data);
        } else {
            $data['type_name'] = $crudInfo->table_name_en . '_' . $fieldInfo->field_name_en;
            $res               = $this->service->create($data);
        }

        Cache::tags([CacheEnum::TAG_DICT])->flush();

        return $this->success($id ? '修改成功' : '保存成功', isset($res) ? ['id' => $res->id] : []);
    }

    /**
     * 保存字典数据排序接口.
     * @return mixed
     */
    #[Post('crud_sort_put', '保存字典数据排序接口')]
    public function crudSortPut()
    {
        $datas = $this->request->post('data', []);

        $sort = count($datas) * 10;
        foreach ($datas as $data) {
            $this->service->update($data['id'], ['sort' => $sort]);
            $sort -= 10;
        }

        Cache::tags([CacheEnum::TAG_DICT])->flush();

        return $this->success('排序成功');
    }

    protected function getRequestClassName(): string
    {
        return DIctDataRequest::class;
    }

    protected function getSearchField(): array
    {
        return [
            ['name', '', 'name_like'],
            ['types', '', 'type_name'],
            ['type_id', ''],
            ['level', ''],
            ['pid', ''],
            ['status', ''],
            ['isCityShow', ''],
        ];
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['value', ''],
            ['type_id', 0],
            ['pid', ''],
            ['sort', 0],
            ['color', ''],
            ['status', 1],
            ['mark', ''],
        ];
    }
}
