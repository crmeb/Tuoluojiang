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

namespace App\Http\Controller\AdminApi\Module;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Crud\SystemCrudTableRequest;
use App\Http\Service\Crud\SystemCrudFieldService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Crud\SystemCrudTableService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * Class CrudController.
 * @email 136327134@qq.com
 * @date 2024/3/6
 */
#[Prefix('ent/crud')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CrudController extends AuthController
{
    /**
     * Crud constructor.
     */
    public function __construct(SystemCrudService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取表列表.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Get('database/list', '数据表列表')]
    public function index()
    {
        $where = $this->request->getMore([
            ['table_name', ''],
            ['cate_id', 0],
        ]);
        $where['crud_id'] = 0;
        return $this->success($this->service->getCrudTableList($where));
    }

    /**
     * 获取实体和应用组合成的数据.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('database/tree', '获取实体和应用组合成的数据')]
    public function getCrudTree()
    {
        return $this->success($this->service->getCrudTree());
    }

    /**
     * 创建表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Post('database/create', '数据表创建')]
    public function createTable()
    {
        $data = $this->request->postMore([
            ['table_name', ''],
            ['table_name_en', ''],
            ['crud_id', 0],
            ['cate_ids', []],
            ['is_update_form', 0],
            ['is_update_table', 0],
            ['info', ''],
            ['show_comment', 0],
            ['comment_title', ''],
            ['show_log', 0],
            ['path', []],
            ['icon', ''],
            ['uni_img', ''],
        ]);
        if (!$data['table_name']) {
            return $this->fail('请输入表名');
        }
        $pattern = '/^[A-Za-z_]{1,100}$/';
        if (!preg_match($pattern, $data['table_name_en'])) {
            return $this->fail('表名不符合规范，应为字母和下划线的组合');
        }
        $this->service->saveCrudTable($data, auth('admin')->id());
        return $this->success('创建成功');
    }

    /**
     * 修改表.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Put('database/update/{id}', '数据表修改')]
    public function updateTable($id)
    {
        $data = $this->request->postMore([
            ['table_name', ''],
            ['is_update_form', 0],
            ['is_update_table', 0],
            ['show_comment', 0],
            ['comment_title', ''],
            ['show_log', 0],
            ['info', ''],
            ['cate_ids', []],
            ['path', []],
            ['icon', ''],
            ['uni_img', ''],
        ]);
        if (!$data['table_name']) {
            return $this->fail('请输入表名');
        }
        $this->service->updateCrudTable((int)$id, $data);
        return $this->success('修改成功');
    }

    /**
     * 复制实体.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/5
     */
    #[Post('database/copy/{id}', '数据表复制')]
    public function copyCrud($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $data = $this->request->postMore([
            ['table_name', ''],
            ['table_name_en', ''],
            ['crud_id', 0],
            ['cate_ids', []],
            ['info', ''],
        ]);
        $this->service->copyCrud($id, $data);
        return $this->success('复制成功');
    }

    /**
     * 删除表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Delete('database/del/{id}', '数据表删除')]
    public function deleteTable($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $this->service->deleteCrudTable((int)$id);
        return $this->success('删除成功');
    }

    /**
     * 导入模板.
     */
    #[Get('import/temp', '获取导入模板')]
    public function importTemplate(): mixed
    {
        $url = sys_config('site_url') . '/static/temp/crud_import_temp.xlsx';
        return $this->success(compact('url'));
    }

    /**
     * 获取实体信息.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/3/8
     */
    #[Get('database/info/{id}', '数据表信息')]
    public function getTableInfo($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $crudInfo = $this->service->get($id, ['*', 'comment_title as comment_name'], [
            'menu' => fn($q) => $q->select([
                'paths',
                'uni_img',
                'icon',
                'crud_id'
            ])
        ]);
        if (!$crudInfo) {
            return $this->fail('没有查询到实体信息');
        }
        $crudInfo = $crudInfo->toArray();
        $crudInfo['path'] = $crudInfo['menu']['paths'] ?? [];
        $crudInfo['uni_img'] = $crudInfo['menu']['uni_img'] ?? '';
        $crudInfo['icon'] = $crudInfo['menu']['icon'] ?? '';
        return $this->success($crudInfo);
    }

    /**
     * 批量添加字段.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    #[Post('batch/field/save', '批量添加字段')]
    public function batchSaveField()
    {
        $fields = $this->request->post('fields', []);
        $crudId = $this->request->post('crud_id', 0);
        $tableName = $this->request->post('table_name', '');
        $tableNameEn = $this->request->post('table_name_en', '');
        $cateIds = $this->request->post('cate_ids', []);
        if (!$fields) {
            return $this->fail('缺少参数');
        }
        if (!$crudId && (!$tableName || !$tableNameEn)) {
            return $this->fail('缺少实体名称参数');
        }
        foreach ($fields as $data) {
            if (!$data['value']) {
                return $this->fail('请输选择数据表字段类型');
            }
            if (!$data['field_name']) {
                return $this->fail('请输入字段名');
            }
            $pattern = '/^[A-Za-z][A-Za-z_0-9]{0,99}$/';
            if (!preg_match($pattern, $data['field_name_en'])) {
                return $this->fail('字段名不符合规范，应为字母、下划线、数字的组合，且不能以数字开头');
            }
        }

        app()->make(SystemCrudFieldService::class)->batchAddField($fields, (int)auth('admin')->id(), (int)$crudId, $tableName, $tableNameEn, $cateIds);

        return $this->success('添加字段成功');
    }


    /**
     * 获取某个实体下的字段信息和数据字典信息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/6
     */
    #[Get('database/fields/{id}', '某实体下字段和数据字典信息')]
    public function getCrudField(SystemCrudFieldService $service, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $approve = $this->request->get('approve', 0);
        return $this->success($service->getCrudField((int)$id, (bool)$approve));
    }

    /**
     * 获取视图数据.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/4/13
     */
    #[Get('view/info/{id}', '视图详情')]
    public function findView(SystemCrudTableService $service, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $viewInfo = $service->get(['crud_id' => $id, 'is_index' => 1])?->toArray();
        if (!$viewInfo) {
            $viewInfo = [
                'options'       => [],
                'view_search'   => [],
                'senior_search' => [],
                'show_field'    => [],
            ];
        }
        return $this->success($viewInfo);
    }

    /**
     * 保存视图.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Post('view/save/{id}', '保存视图')]
    public function saveView(SystemCrudTableRequest $request, SystemCrudTableService $service, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $searchOptions = $request->post('senior_search'); // 高级搜索条件
        $viewSearch = $request->post('view_search'); // 视图搜索条件
        $showField = $request->post('show_field'); // 展示字段
        $options = $request->post('options'); // 其他配置信息

        $version = $service->max(['crud_id' => $id], 'version');

        if ($version) {
            ++$version;
        } else {
            $version = 1;
        }

        $tableInfo = $service->get(['crud_id' => $id, 'is_index' => 1])?->toArray();

        if ($tableInfo) {
            if (is_null($showField)) {
                $showField = $tableInfo['show_field'];
            }
            if (is_null($searchOptions)) {
                $searchOptions = $tableInfo['senior_search'];
            }
            if (is_null($options)) {
                $options['create'] = $tableInfo['create'] ?? [];
                $options['tab'] = $tableInfo['tab'] ?? [];
            } elseif (!isset($options['create']) || is_null($options['create'])) {
                $options['create'] = $tableInfo['options']['create'] ?? [];
            } elseif (!isset($options['tab']) || is_null($options['tab'])) {
                $options['tab'] = $tableInfo['options']['tab'] ?? [];
            }
        }

        $service->transaction(function () use ($viewSearch, $service, $showField, $id, $version, $options, $searchOptions) {
            $service->update(['crud_id' => $id], ['is_index' => 0]);
            $service->create([
                'crud_id'       => $id,
                'version'       => $version,
                'options'       => $options,
                'view_search'   => $viewSearch,
                'senior_search' => $searchOptions,
                'show_field'    => $showField,
                'is_index'      => 1,
            ]);
        });
        event('system.crud');
        return $this->success('保存成功');
    }

    /**
     * 获取字典数据.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('crud_dict/list/{id}')]
    public function getDictDataList($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($this->service->getDictDataList((int)$id));
    }

    /**
     * 批量添加字典数据.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('crud_dict/batch')]
    public function batchDictData()
    {
        $data = $this->request->postMore([
            ['dict_data', []],
            ['dict_type_id', 0],
        ]);
        if (!$data['dict_type_id']) {
            return $this->fail('请选择字典类型');
        }
        if (!$data['dict_data']) {
            return $this->fail('请填写字典数据');
        }
        $this->service->batchDictData($data['dict_type_id'], $data['dict_data']);
        return $this->success('保存成功');
    }
}
