<?php

namespace App\Http\Controller\AdminApi\Module;

use App\Constants\Crud\CrudFormEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudFieldService;
use App\Http\Service\Crud\SystemCrudFormService;
use App\Http\Service\Crud\SystemCrudService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 表单设置.
 * @package App\Http\Controller\AdminApi\Module
 */
#[Prefix('ent/crud/form')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CrudFormController extends AuthController
{
    /**
     * Crud constructor.
     */
    public function __construct(SystemCrudFormService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 表单设置中获取字段和实体信息组合成的表单配置.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/18
     */
    #[Get('list/{id}', '表单设置中获取字段和实体信息组合成的表单配置')]
    public function fieldForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success(app()->make(SystemCrudService::class)->getMainFieldForm((int)$id));
    }

    /**
     * 修改表单名称.
     * @return mixed
     */
    #[Put('update/{id}', '修改表单名称')]
    public function updateFormName(SystemCrudService $service, $id)
    {
        $name = $this->request->post('name', '');
        if (!$name) {
            return $this->fail('表单名称不能为空');
        }
        if ($id) {
            $this->service->update($id, ['name' => $name]);
        } else {
            $crudId = $this->request->post('crud_id', '');
            if (!$crudId) {
                return $this->fail('缺少参数');
            }
            if (!$service->count(['id' => $crudId])) {
                return $this->fail('实体信息不存在');
            }
            $res = $this->service->create([
                'crud_id' => $crudId,
                'name'    => $name,
            ]);
        }

        return $this->success('修改成功', isset($res) ? ['id' => $res->id] : ['id' => 0]);
    }

    /**
     * @return mixed
     */
    #[Delete('delete/{id}', '删除表单')]
    public function deleteForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $this->service->delete($id);

        return $this->success('删除成功');
    }

    /**
     * 保存表单信息.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/2/27
     */
    #[Post('save/{id}', '保存表单信息')]
    public function saveForm(SystemCrudService $crudService, SystemCrudFieldService $fieldService, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $options = $this->request->post('options', []);
        $fields = $this->request->post('fields', []);
        $globalOptions = $this->request->post('global_options', []);
        if (!$fields) {
            return $this->fail('至少选择一个字段进行保存');
        }
        $mastarTableName = $crudService->value($id, 'table_name_en');
        $chinedTableName = $crudService->value(['crud_id' => $id], 'table_name_en');

        $from = $this->service->get(['crud_id' => $id, 'is_index' => 1]);
        $version = $from['version'] ?? 0;

        if ($version) {
            ++$version;
        } else {
            $version = 1;
        }

        $updateCrud = [];
        foreach ($fields as &$field) {
            $field = str_replace('@', '.', $field);
            [$tableName, $fieldName] = strstr($field, '.') !== false ? explode('.', $field) : [$mastarTableName, $field];
            if ($tableName !== $mastarTableName) {
                $tableId = $crudService->value(['table_name_en' => $tableName], 'id');
                $updateCrud[$tableId][] = $fieldName;
            } else {
                $updateCrud[$id][] = $fieldName;
            }
        }

        $crudService->transaction(function () use ($from, $fieldService, $updateCrud, $crudService, $fields, $id, $version, $options, $globalOptions, $chinedTableName, $mastarTableName) {
            $formType = [];
            foreach ($this->service->formType as $type => $item) {
                $formType[$item['icon']] = $type;
            }
            $formOption = $this->service->getFormOptionList($options);

            $tableField = [];
            foreach ($formOption as $item) {
                if (isset($item['tableDetails']) && $item['tableDetails']) {
                    if (str_contains($item['options']['formFieldUniqid'], '.')) {
                        [, $field] = explode('.', $item['options']['formFieldUniqid']);
                    } else {
                        $field = $item['options']['formFieldUniqid'];
                    }
                    $tableField[] = $field;
                }
                if (!empty($item['options']['fieldId']) && isset($formType[$item['icon']])) {
                    switch ($formType[$item['icon']]) {
                        case CrudFormEnum::FORM_SELECT:
                        case CrudFormEnum::FORM_RADIO:
                        case CrudFormEnum::FORM_CHECKBOX:
                        case CrudFormEnum::FORM_CASCADER:
                        case CrudFormEnum::FORM_CASCADER_RADIO:
                        case CrudFormEnum::FORM_CASCADER_ADDRESS:
                        case CrudFormEnum::FORM_INPUT_SELECT:
                        case CrudFormEnum::FORM_TAG:
                            $fieldService->update($item['options']['fieldId'], [
                                'data_type'                 => $item['options']['data_type'] ?? 0,
                                'customize_items'           => $item['options']['customizeItems'] ?? [],
                                'association_show_type'     => $item['options']['showType'] ?? 1,
                                'data_dict_id'              => $item['options']['dataDictId'] ?? 0,
                                'is_default_value_not_null' => isset($item['options']['required']) ? $item['options']['required'] ? 0 : 1 : 1,
                            ]);
                            break;
                        default:
                            $fieldService->update($item['options']['fieldId'], [
                                'is_default_value_not_null' => isset($item['options']['required']) ? $item['options']['required'] ? 0 : 1 : 1,
                            ]);
                            break;
                    }
                }
            }

            $formFields = [];
            foreach ($fields as $field) {
                if (str_contains($field, '.')) {
                    [, $fieldName] = explode('.', $field);
                    if (!$tableField) {
                        $formFields[] = $field;
                    } else {
                        if (!in_array($fieldName, $tableField)) {
                            $formFields[] = $field;
                        }
                    }
                } else {
                    $formFields[] = $field;
                }
            }

            $update = ['form_fields' => $formFields];
            if ($tableField) {
                $update['is_form_table'] = 1;
                $update['table_field'] = $tableField;

                $crudService->schemaDeleteIndex($chinedTableName, $mastarTableName);
            } else {
                $update['is_form_table'] = 0;
                $update['table_field'] = $tableField;
            }
            if ($from) {
                $from->version = $version;
                $from->options = $options;
                $from->fields = $fields;
                $from->global_options = $globalOptions;
                $from->save();
            } else {
                $this->service->create([
                    'crud_id'        => $id,
                    'version'        => $version,
                    'options'        => $options,
                    'fields'         => $fields,
                    'global_options' => $globalOptions,
                    'is_index'       => 1,
                ]);
            }
            // 把所有数据改为不是表单
            $fieldService->updateForm($updateCrud);
            $fieldService->updateIsForm($updateCrud);
            $crudService->update($id, $update);
        });
        event('system.crud');
        return $this->success('保存成功');
    }

    /**
     * 表单查看.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/19
     */
    #[Get('info/{id}', '表单详情')]
    public function findForm(SystemCrudService $crudService, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $formId = (int)$this->request->get('form_id', 0);
        $crudInfo = $crudService->get($id);
        if (!$crudInfo) {
            return $this->success([]);
        }
        $formInfo = [];
        try {
            $formInfo = app()->get(CrudModuleService::class)->getCreateForm(crud: $crudInfo)['form_info'];
        } catch (\Throwable $e) {
            Log::error('表单详情报错:' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        if ((!isset($formInfo['global_options']) || !$formInfo['global_options']) && $this->service->count(['crud_id' => $id, 'is_index' => 0])) {
            $formInfo['global_options'] = $this->service->getGlobalOptions($id);
        }
        return $this->success($formInfo);
    }

    /**
     * 获取表单列表
     * @param $id
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('page/{id}', '获取表单列表')]
    public function listForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $crudInfo = app()->make(SystemCrudService::class)->get($id);
        if (!$crudInfo) {
            return $this->success([]);
        }

        return $this->success($this->service->getListForm(crud: $crudInfo));
    }
}
