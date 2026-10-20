<?php

namespace App\Http\Controller\AdminApi\Module;

use App\Constants\Crud\CrudFormEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Crud\SystemCrudFieldService;
use App\Http\Service\Crud\SystemCrudService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 实体字段控制器.
 * @package App\Http\Controller\AdminApi\Module
 */
#[Prefix('ent/crud/field')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CrudFieldController extends AuthController
{
    /**
     * Crud constructor.
     */
    public function __construct(SystemCrudFieldService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 设置主展示字段.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/1
     */
    #[Put('main/{id}', '数据表字段设置主展示字段')]
    public function mainField($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $fieldInfo = $this->service->get($id);
        if (!$fieldInfo) {
            return $this->fail('修改的字段信息不存在');
        }
        if ($fieldInfo->form_value !== 'input') {
            return $this->fail('只有文本框才可以设置为主展示字段');
        }
        $this->service->updateMain($fieldInfo->crud_id, $id);
        $fieldInfo->is_main = 1;
        $fieldInfo->save();
        event('system.crud');
        return $this->success('设置成功');
    }

    /**
     * 添加字段.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Post('save', '数据表字段添加')]
    public function saveField()
    {
        $data = $this->request->postMore([
            ['crud_id', 0],
            ['value', ''],
            ['field_name', ''],
            ['field_name_en', ''],
            ['is_default_value_not_null', 0],
            ['comment', ''],
            ['data_dict_id', 0],
            ['association_crud_id', 0],
            ['association_field_names', []],
            ['options', []],
            ['create_modify', 1],
            ['update_modify', 1],
            ['is_uniqid', 0],
            ['data_type', 0],
        ]);
        if (!$data['crud_id']) {
            return $this->fail('缺少参数');
        }
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
        if (in_array($data['value'], [
                CrudFormEnum::FORM_RADIO,
                CrudFormEnum::FORM_CASCADER_RADIO,
                CrudFormEnum::FORM_CHECKBOX,
                CrudFormEnum::FORM_CASCADER,
                CrudFormEnum::FORM_SELECT,
            ]) && !$data['data_dict_id'] && $data['data_type'] == 0) {
            return $this->fail('请选择数据字典');
        }
        if (in_array($data['field_name_en'], ['count', 'children', 'is_share'])) {
            return $this->fail('字段名不能为children或者count');
        }
        $this->service->addField(
            (int)$data['crud_id'],
            $data['value'],
            $data['field_name'],
            $data['field_name_en'],
            (bool)$data['is_default_value_not_null'],
            true,
            $data['comment'] ?: $data['field_name'],
            (int)$data['data_dict_id'],
            (int)$data['association_crud_id'],
            (array)$data['association_field_names'],
            (array)$data['options'],
            (int)$data['create_modify'],
            (int)$data['update_modify'],
            (bool)$data['is_uniqid'],
            (int)$data['data_type']
        );
        return $this->success('添加字段成功');
    }

    /**
     * 修改字段.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Put('update/{id}', '数据表字段修改')]
    public function updateField($id)
    {
        $data = $this->request->postMore([
            ['value', ''],
            ['field_name', ''],
            ['is_default_value_not_null', 0],
            ['comment', ''],
            ['data_dict_id', 0],
            ['association_crud_id', 0],
            ['association_field_names', []],
            ['options', []],
            ['create_modify', 1],
            ['update_modify', 1],
            ['is_uniqid', 0],
            ['data_type', 0],
        ]);
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $fieldInfo = $this->service->get($id, ['crud_id', 'form_value']);
        if ($fieldInfo['form_value'] !== $data['value']) {
            $crudInfo = app()->get(SystemCrudService::class)->get($fieldInfo['crud_id']);
            $count = DB::table($crudInfo['table_name_en'])->whereNull('deleted_at')->count();
            if ($count > 0) {
                return $this->fail('请先删除数据表数据,再进行修改字段类型');
            }
        } else {
            $data['value'] = '';
        }
        if (in_array($data['value'], [
                CrudFormEnum::FORM_RADIO,
                CrudFormEnum::FORM_CASCADER_RADIO,
                CrudFormEnum::FORM_CHECKBOX,
                CrudFormEnum::FORM_CASCADER,
                CrudFormEnum::FORM_SELECT,
            ]) && !$data['data_dict_id'] && $data['data_type'] == 0) {
            return $this->fail('请选择数据字典');
        }
        $this->service->updateField(
            (int)$id,
            $data['value'],
            $data['field_name'],
            (bool)$data['is_default_value_not_null'],
            true,
            (int)$data['data_dict_id'],
            (array)$data['association_field_names'],
            (array)$data['options'],
            (int)$data['create_modify'],
            (int)$data['update_modify'],
            (bool)$data['is_uniqid'],
            (int)$data['data_type']
        );
        return $this->success('修改字段成功');
    }

    /**
     * 删除字段.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Delete('del/{id}', '数据表字段删除')]
    public function deleteField($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $this->service->deleteField((int)$id);
        return $this->success('删除字段成功');
    }

    /**
     * 获取表字段.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Get('list/{id}', '数据表字段')]
    public function getFieldList($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $name = $this->request->get('name', '');
        return $this->success($this->service->getTableFieldList((int)$id, $name));
    }

    /**
     * 获取字段详情.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    #[Get('info/{id}', '数据表字段详情')]
    public function getFieldInfo($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $fieldInfo = $this->service->get($id, ['*'], [
            'association' => fn($q) => $q->select(['id', 'table_name', 'table_name_en']),
        ]);
        if (!$fieldInfo) {
            return $this->fail('没有查询到字段信息');
        }

        $fieldInfo = $fieldInfo->toArray();

        if ($fieldInfo['association_field_names']) {
            $fieldInfo['association_field_names_list'] = $this->service->getFieldSelect((int)$fieldInfo['association_crud_id'], $fieldInfo['association_field_names']);
        } else {
            $fieldInfo['association_field_names_list'] = [];
        }

        return $this->success($fieldInfo);
    }

    /**
     * 批量添加字段.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    #[Post('batch/save', '批量添加字段')]
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

        $this->service->batchAddField($fields, (int)auth('admin')->id(), (int)$crudId, $tableName, $tableNameEn, $cateIds);

        return $this->success('添加字段成功');
    }

    /**
     * 获取一对一关联字段展示.
     * @return mixed
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Get('association/{id}', '一对一关联字段展示')]
    public function getAssociationCrudField($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->getAssociationCrudField((int)$id));
    }

    /**
     * 获取表单类型.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Get('type', '表单类型')]
    public function getFieldType()
    {
        return $this->success(SystemCrudService::FORM_TYPE);
    }

    /**
     * 条件搜索类型.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Get('operator', '条件搜索类型')]
    public function getOperatorType()
    {
        return $this->success([
            'operator_string' => SystemCrudService::OPERATOR_TYPE,
            'operator_number' => SystemCrudService::OPERATOR_NUMBER_TYPE,
            'operator_timer'  => SystemCrudService::OPERATOR_TIMER_TYPE,
        ]);
    }

    /**
     * AI 对话生成字段.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Post('ai_dialog', 'AI 对话生成字段')]
    public function aiDialog()
    {
        $crudId  = $this->request->post('crud_id', '');
        $fields  = $this->request->post('fields', []);
        $message = $this->request->post('message', '');
        if (! $message) {
            return $this->fail('请输入内容');
        }

        return $this->success($this->service->aiDialog($message, (int) $crudId, (array) $fields));
    }
}
