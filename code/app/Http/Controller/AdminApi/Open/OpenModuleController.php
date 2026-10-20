<?php

namespace App\Http\Controller\AdminApi\Open;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthOpenApi;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudService;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 对外接口
 */
#[Prefix('open/module')]
#[Middleware([AuthOpenApi::class, 'ent.crud'])]
class OpenModuleController extends AuthController
{
    use SearchTrait;

    /**
     * @param CrudModuleService $service
     */
    public function __construct(CrudModuleService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 列表展示.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    #[Post('{name}/list', '低代码列表')]
    public function index(Request $request)
    {
        $crudInfo = $this->request->crudInfo;

        $postOrderBy = $request->post('order_by', []);
        $viewSearch = $request->post('view_search', []);
        $viewSearchBoolean = $request->post('view_search_boolean', 0);
        $keywordDefault = $request->post('keyword_default');

        $orderBy = [];
        if ($postOrderBy) {
            $orderByField = $this->service->getOrderByField($crudInfo->id);
            foreach ($orderByField as $item) {
                if (isset($postOrderBy[$item['field_name_en']])) {
                    $orderBy[$item['field_name_en']] = $postOrderBy[$item['field_name_en']] ? 'desc' : 'asc';
                }
            }
        }

        if (isset($postOrderBy['default_field_name_en'])) {
            $orderBy = [];
        }

        $viewNewSearch = [];
        foreach ($viewSearch as $search) {
            if ($search['form_field_uniqid']) {
                $viewNewSearch[] = [
                    'field_name' => $search['form_field_uniqid'],
                    'operator'   => $search['operator'],
                    'value'      => $search['value'] ?? '',
                ];
            }
        }
        $viewSearch = $viewNewSearch;

        return $this->success($this->service->getModuleList($request, $crudInfo, [], $orderBy, (array)$viewSearch, (string)$keywordDefault, (int)$viewSearchBoolean));
    }

    /**
     * 获取新建页面的表单信息.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    #[Get('{name}/create', '创建表单')]
    public function create(Request $request)
    {
        $crudId = $request->get('crud_id', 0);
        $crudValue = $request->get('crud_value', 0);
        $id = $request->get('id', 0);
        return $this->success($this->service->getCreateForm($this->request->crudInfo, (int)$crudId, (int)$crudValue, (int)$id));
    }

    /**
     * 保存数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/13
     */
    #[Post('{name}/save', '保存数据')]
    public function save(Request $request)
    {
        $crudInfo = $this->request->crudInfo;
        $crudList = $this->service->getFormField($crudInfo->id);

        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许创建');
        }

        $data = $validatorData = $rule = $message = [];

        $crudId = $request->post('crud_id', 0);
        $crudValue = $request->post('crud_value', 0);

        foreach ($crudList as $item) {
            $fieldName = $item['crud']['id'] === $crudInfo->id ?
                $item['field_name_en'] :
                $item['crud']['table_name_en'] . '.' . $item['field_name_en'];

            $default = '';
            $association = false;
            if ($item['association_crud_id']) {
                $default = [];
                $association = true;
            } elseif (in_array($item['field_name_en'], ['user_id', 'update_user_id', 'frame_id', 'owner_user_id'])) {
                $default = [];
                $association = true;
            }

            $postValue = $request->post(str_replace('.', '@', $fieldName), $default);
            if ($association) {
                $value = $postValue['id'] ?? 0;
            } else {
                $value = $postValue;
            }

            [$itemMsg, $ruleData] = $this->service->getValidationRule($item, $fieldName);

            if ($ruleData) {
                $validatorFieldName = str_replace('.', '_', $fieldName);
                $validatorData[$validatorFieldName] = $value;
                $rule[$validatorFieldName] = implode('|', $ruleData);
                $message = array_merge($message, $itemMsg);
            }

            $data[$fieldName] = [
                'value' => $value,
                'post'  => $postValue,
                'item'  => $item,
            ];
        }

        if ($validatorData && $rule && $message) {
            $validator = Validator::make($validatorData, $rule, $message);

            if ($validator->fails()) {
                // 验证失败的处理逻辑
                return $this->fail($validator->errors()->first());
            }
        }

        $this->service->saveModule($crudInfo, $data, [
            'uid' => $request->authSkInfo['uid'],
        ], (int)$crudId, (int)$crudValue);

        return $this->success('添加成功');
    }

    /**
     * 更新数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/13
     */
    #[Put('{name}/update/{id}', '更新数据')]
    public function update(Request $request)
    {
        $id = $this->request->route()->parameter('id', 0);
        $crudInfo = $this->request->crudInfo;
        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许更新');
        }
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $crudList = $this->service->getFormField($crudInfo->id);
        $data = $validatorData = $rule = $message = [];
        foreach ($crudList as $item) {
            $fieldName = $item['crud']['id'] === $crudInfo->id ?
                $item['field_name_en'] :
                $item['crud']['table_name_en'] . '.' . $item['field_name_en'];
            $default = '';
            $association = false;
            if ($item['association_crud_id']) {
                $default = [];
                $association = true;
            } elseif (in_array($item['field_name_en'], ['user_id', 'update_user_id', 'frame_id', 'owner_user_id'])) {
                $default = [];
                $association = true;
            }
            $postValue = $request->post(str_replace('.', '@', $fieldName), $default);
            if ($association) {
                $value = $postValue['id'] ?? 0;
            } else {
                $value = $postValue;
            }
            [$itemMsg, $ruleData] = $this->service->getValidationRule($item, $fieldName);
            if ($ruleData) {
                $validatorFieldName = str_replace('.', '_', $fieldName);
                $validatorData[$validatorFieldName] = $value;
                $rule[$validatorFieldName] = implode('|', $ruleData);
                $message = array_merge($message, $itemMsg);
            }
            $data[$fieldName] = [
                'value' => $value,
                'post'  => $postValue,
                'item'  => $item,
            ];
        }
        if ($validatorData && $rule && $message) {
            $validator = Validator::make($validatorData, $rule, $message);

            if ($validator->fails()) {
                // 验证失败的处理逻辑
                return $this->fail($validator->errors()->first());
            }
        }
        $this->service->updateModule($crudInfo, (int)$id, $data, [
            'uid' => $request->authSkInfo['uid'],
        ]);
        return $this->success('修改成功');
    }

    /**
     * 获取实体数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    #[Get('{name}/find/{id}', '获取实体数据')]
    public function find()
    {
        $id = $this->request->route()->parameter('id', 0);
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->getFindModule($this->request->crudInfo, (int)$id));
    }

    /**
     * 删除数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/14
     */
    #[Delete('{name}/delete/{id}', '删除数据')]
    public function delete()
    {
        $id = $this->request->route()->parameter('id', 0);
        $crudInfo = $this->request->crudInfo;

        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许删除');
        }

        if (!$id) {
            return $this->fail('缺少参数');
        }

        $this->service->deleteModule($crudInfo, (int)$id);

        return $this->success('删除成功');
    }
}
