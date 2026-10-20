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
use App\Http\Middleware\AuthCrud;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Requests\ApiRequest;
use App\Http\Service\Crud\CrudModuleService;
use App\Http\Service\Crud\SystemCrudCommentService;
use App\Http\Service\Crud\SystemCrudFieldService;
use App\Http\Service\Crud\SystemCrudLogService;
use App\Http\Service\Crud\SystemCrudQuestionnaireService;
use App\Http\Service\Crud\SystemCrudSeniorSearchService;
use App\Http\Service\Crud\SystemCrudService;
use App\Http\Service\Crud\SystemCrudShareService;
use App\Http\Service\Crud\SystemCrudTableUserService;
use App\Http\Service\Frame\FrameService;
use Tuoluojiang\Open\traits\SearchTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
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
 * Class ModuleController.
 * @email 136327134@qq.com
 * @date 2024/3/1
 */
#[Prefix('ent/crud/module')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log', 'ent.crud'])]
class ModuleController extends AuthController
{
    use SearchTrait;

    public function __construct(CrudModuleService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->middleware([AuthCrud::class])->except([
            'getAssociationField', 'getAssociationList',
            'saveUserTable', 'getCrudInfo',
            'delSeniorSearch', 'getSeniorSearchList',
            'saveSeniorSearch', 'sortSeniorSearch',
            'getTreeFrame', 'deleteQuestionnaire', 'listQuestionnaire',
            'updateQuestionnaire', 'createQuestionnaire',
        ]);
    }

    /**
     * 获取一对一关联展示字段.
     * @return mixed
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    #[Get('association_field/{id}', '一对一关联展示字段')]
    public function getAssociationField($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->service->getAssociationField((int)$id));
    }

    /**
     * 获取一对一关联展示列表.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    #[Get('association_list/{id}', '一对一关联展示列表')]
    public function getAssociationList($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $keyword = $this->request->get('keyword', '');
        return $this->success($this->service->getAssociationList((int)$id, (string)$keyword));
    }

    /**
     * 保存用户相关视图信息和表格展示信息.
     * @return mixed
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/3/9
     */
    #[Post('{name}/crud', '保存用户相关视图信息和表格展示信息')]
    public function saveUserTable(SystemCrudTableUserService $service)
    {
        [$seniorSearch, $showField, $options] = $this->request->postMore([
            ['senior_search', []],
            ['show_field', []],
            ['options', []],
        ], true);

        $service->saveUserTable($this->request->crudInfo->id, auth('admin')->id(), (array)$seniorSearch, (array)$showField, (array)$options);

        return $this->success('保存成功');
    }

    /**
     * 获取视图搜索列表.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Get('{name}/senior/list', '获取视图搜索列表')]
    public function getSeniorSearchList(SystemCrudSeniorSearchService $service)
    {
        $title = $this->request->get('title', '');
        $system = $this->request->get('system', 0);
        return $this->success($service->getSeniorSearchList($this->request->crudInfo, $title, $system ? 0 : auth('admin')->id()));
    }

    /**
     * 保存视图信息.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BindingResolutionException
     */
    #[Post('{name}/senior/save', '保存视图信息')]
    public function saveSeniorSearch(ApiRequest $request, SystemCrudSeniorSearchService $service)
    {
        $id = $this->request->get('id', '');
        $system = $this->request->get('system', 0);
        $data = $request->postMore([
            ['sort', 0],
            ['senior_title', ''],
            ['senior_search', []],
            ['senior_type', 0],
            ['search_boolean', 0],
        ]);

        $data['crud_id'] = $this->request->crudInfo->id;

        if (!$data['senior_title']) {
            return $this->fail('缺少视图标题');
        }

        if ($id) {
            $service->update($id, $data);
        } else {
            if (!$system) {
                $data['user_id'] = auth('admin')->id();
            }
            if ($service->count() >= 100) {
                return $this->fail('视图搜索条件最多添加100个');
            }
            $service->create($data);
        }

        event('system.crud');

        return $this->success($id ? '修改成功' : '添加成功');
    }

    /**
     * 排序视图.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Post('{name}/senior/sort', '排序视图')]
    public function sortSeniorSearch(SystemCrudSeniorSearchService $service)
    {
        $ids = $this->request->post('id', []);
        if (!$ids) {
            return $this->fail('缺少参数');
        }

        $sort = $service->max(['crud_id' => $this->request->crudInfo->id], 'sort');
        $sort = is_null($sort) ? count($ids) + 1 : $sort;
        $sort = $sort < 0 ? count($ids) : $sort;
        foreach ($ids as $id) {
            $service->update($id, ['sort' => $sort]);
            --$sort;
        }

        event('system.crud');

        return $this->success('修改成功');
    }

    /**
     * 删除视图.
     * @return mixed
     */
    #[Delete('{name}/senior/del/{id}', '删除视图')]
    public function delSeniorSearch(SystemCrudSeniorSearchService $service)
    {
        $id = $this->request->route()->parameter('id');

        if (!$id) {
            return $this->fail('缺少参数');
        }

        $service->delete($id);

        event('system.crud');

        return $this->success('删除成功');
    }

    /**
     * 权限范围.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('{name}/frame/tree', '权限范围')]
    public function getTreeFrame(FrameService $service)
    {
        $withRole = (bool)$this->request->get('role', 0);
        $isScope = (bool)$this->request->get('scope', 0);
        return $this->success($service->getTree(auth('admin')->id(), $withRole, $isScope, $this->request->crudInfo->id));
    }

    /**
     * 获取实体列表字段展示和搜索字段展示.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     * @email 136327134@qq.com
     * @date 2024/3/12
     */
    #[Get('{name}/crud/info/{id?}', '获取实体列表字段展示和搜索字段展示')]
    public function getCrudInfo(Request $request)
    {
        $id = $this->request->route()->parameter('id', 0);
        $isFieldAll = $request->get('is_field_all', 0);
        return $this->success($this->service->getCrudInfo($this->request->crudInfo, auth('admin')->id(), (int)$id, false, (bool)$isFieldAll));
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
    public function index()
    {
        $systemUserId = $this->request->post('system_user_id', []);
        $defaultWhere = $this->request->postMore([
            ['show_search_type', ''],
            ['is_system', 0],
        ]);

        //兼容前端的搜索，前端的系统搜索
        $scopeFrame = 'all';
        switch ($defaultWhere['show_search_type']) {
            case 0:
                $scopeFrame = 'all';
                break;
            case 1:
                $scopeFrame = 'dep';
                break;
            case 2:
                $scopeFrame = 'self';
                break;
        }

        $this->withScopeFrame(key: 'user_id', crudId: $this->request->crudInfo->id, scopeFrame: $scopeFrame);

        $defaultWhere['user_id'] = $this->request->post('user_id', []);
        $defaultWhere['uid'] = auth('admin')->id();
        $postOrderBy = $this->request->post('order_by', []);
        $viewSearch = $this->request->post('view_search', []);
        if (!is_array($viewSearch)) {
            $viewSearch = [];
        }
        $viewSearchBoolean = $this->request->post('view_search_boolean', 0);
        $keywordDefault = $this->request->post('keyword_default');
        $crudValue = $this->request->post('crud_value', 0);
        $crudId = $this->request->post('crud_id', 0);
        $group = $this->request->post('group', '');
        $childrenLimit = $this->request->post('children_limit', 5);
        $childrenPage = $this->request->post('children_page', 1);
        $childrenId = $this->request->post('children_id', '');
        $isFieldAll = $defaultWhere['is_system'] ? true : $this->request->post('is_field_all', 0);
        if ($systemUserId && $this->request->post('scope_frame') === 'all') {
            $defaultWhere['user_id'] = array_merge($defaultWhere['user_id'], $systemUserId);
        }
        if (!$defaultWhere['user_id']) {
            $defaultWhere['user_id'] = [0];
        }
        $orderBy = [];
        if ($postOrderBy) {
            $orderByField = $this->service->getOrderByField($this->request->crudInfo->id);
            foreach ($orderByField as $item) {
                if (isset($postOrderBy[$item['field_name_en']])) {
                    $orderBy[$item['field_name_en']] = $postOrderBy[$item['field_name_en']] ? 'asc' : 'desc';
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

        return $this->success($this->service->getModuleList(
            request: $this->request,
            crud: $this->request->crudInfo,
            defaultWhere: $defaultWhere,
            orderBy: $orderBy,
            viewSearch: (array)$viewSearch,
            keywordDefault: (string)$keywordDefault,
            viewSearchBoolean: (int)$viewSearchBoolean,
            crudId: (int)$crudId,
            crudValue: (int)$crudValue,
            isFieldAll: (bool)$isFieldAll,
            options: [
                'group'          => $group,
                'children_limit' => (int)$childrenLimit,
                'children_page'  => (int)$childrenPage,
                'children_id'    => $childrenId,
            ]
        ));
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
    #[Get('{name}/create', '低代码创建表单')]
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
    #[Post('{name}/save', '低代码保存数据')]
    public function save(Request $request)
    {
        $crudInfo = $this->request->crudInfo;

        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许创建');
        }

        $crudId = $request->post('crud_id', 0);
        $crudValue = $request->post('crud_value', 0);

        $data = $this->service->checkData($crudInfo, $request);

        $this->service->saveModule($crudInfo, $data, [
            'uid' => auth('admin')->id(),
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
    #[Put('{name}/update/{id}', '低代码更新数据')]
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

        $data = $this->service->checkData($crudInfo, $request);

        $this->service->updateModule($crudInfo, (int)$id, $data, [
            'uid' => auth('admin')->id(),
        ]);
        return $this->success('修改成功');
    }

    /**
     * 单个字段进行更新.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Put('{name}/update_field/{id}', '低代码单个字段修改')]
    public function updateField(Request $request)
    {
        $id = $this->request->route()->parameter('id', 0);
        $crudInfo = $this->request->crudInfo;
        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许更新');
        }
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $fieldNameEn = $request->post('field_name', '');
        $fieldNameEn = str_replace('.', '@', $fieldNameEn);
        $fieldValue = $request->post('value', '');

        $addendumInfo = [];
        if ($crudInfo->is_form_table) {
            $addendumInfo = app()->make(SystemCrudService::class)->get(['crud_id' => $crudInfo->id])?->toArray();
        }

        $crudList = $this->service->getFormField($crudInfo->id, $fieldNameEn, $addendumInfo, $crudInfo);
        if (!$crudList) {
            return $this->fail('字段不存在');
        }
        if (count($crudList) > 1 && !$crudInfo->is_form_table) {
            return $this->fail('字段存在重复，请联系管理员');
        }
        $data = $validatorData = $rule = $message = [];
        foreach ($crudList as $item) {
            $fieldName = $item['crud']['id'] === $crudInfo->id
                ? $item['field_name_en']
                : $item['crud']['table_name_en'] . '.' . $item['field_name_en'];

            $association = false;
            if ($item['association_crud_id']) {
                $association = true;
            } elseif (in_array($item['field_name_en'], [SystemCrud::USER_ID_KEY, SystemCrud::UPDATE_USER_ID_KEY, SystemCrud::FRAME_ID_KEY, SystemCrud::OWNER_USER_ID_KEY])) {
                $association = true;
            }

            if ($association && !$addendumInfo) {
                $value = is_array($fieldValue) ? $fieldValue['id'] ?? 0 : $fieldValue;
            } else {
                $value = $fieldValue;
            }
            [$itemMsg, $ruleData] = $this->service->getValidationRule($item, $fieldName);
            if ($ruleData) {
                $validatorFieldName = str_replace('.', '_', $fieldName);
                $validatorData[$validatorFieldName] = $value;
                $rule[$validatorFieldName] = implode('|', $ruleData);
                $message = array_merge($message, $itemMsg);
            }

            $subordinate = [];
            if ($crudInfo->is_form_table && $fieldNameEn === ($crudInfo->table_name_en . '_' . $addendumInfo['table_name_en'])) {
                if (!is_array($fieldValue)) {
                    return $this->fail('表单明细字段数据类型必须位数组');
                }
                $item = $addendumInfo;
                $subordinateKeys = array_keys($fieldValue[0]);
                $keys = [];
                foreach ($subordinateKeys as $subordinateKey) {
                    $keys[] = str_replace($addendumInfo['table_name_en'] . '@', '', $subordinateKey);
                }
                $newValue = [];
                foreach ($value as $key => $valueItem) {
                    foreach ($valueItem as $k => $i) {
                        $k = str_replace($addendumInfo['table_name_en'] . '@', '', $k);
                        if (in_array($k, [SystemCrud::USER_ID_KEY, SystemCrud::UPDATE_USER_ID_KEY, SystemCrud::FRAME_ID_KEY, SystemCrud::OWNER_USER_ID_KEY])) {
                            $newValue[$key][$k] = is_array($i) ? $i['id'] ?? 0 : $i;
                        } else {
                            $newValue[$key][$k] = $i;
                        }
                    }
                }
                $value = $newValue;

                if ($keys) {
                    $res = app()->get(SystemCrudFieldService::class)->fieldByListCache((int)$addendumInfo['id'], $keys);
                    foreach ($res as $resItem) {
                        $subordinate[$resItem['field_name_en']] = $resItem;
                    }
                }
            }

            $data[$fieldName] = [
                'value'       => $value,
                'post'        => $fieldValue,
                'item'        => $item,
                'subordinate' => $subordinate,
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
            'uid'                => auth('admin')->id(),
            'single_field_name'  => $fieldNameEn,
            'single_field_value' => $fieldValue,
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
    #[Get('{name}/find/{id}', '低代码获取实体数据')]
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
    #[Delete('{name}/delete/{id}', '低代码删除数据')]
    public function delete(Request $request)
    {
        $id = $this->request->route()->parameter('id', 0);
        $crudInfo = $this->request->crudInfo;
        $systemUserIds = $request->post('system_user_id', []);

        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许删除');
        }

        if (!$id) {
            return $this->fail('缺少参数');
        }

        $this->service->deleteModule($crudInfo, (int)$id, (array)$systemUserIds);

        return $this->success('删除成功');
    }

    /**
     * 批量删除.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws BindingResolutionException
     * @email 136327134@qq.com
     * @date 2024/4/7
     */
    #[Delete('{name}/batchdelete', '低代码批量删除')]
    public function batchDelete(Request $request)
    {
        $crudInfo = $this->request->crudInfo;

        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许删除');
        }

        $ids = $request->post('ids', []);
        if (!$ids) {
            return $this->fail('缺少参数');
        }

        $this->service->batchDeleteModule($crudInfo, $ids);

        return $this->success('删除成功');
    }

    /**
     * 低代码批量导入.
     * @return mixed|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('{name}/import', '低代码批量导入')]
    public function import(Request $request)
    {
        $crudInfo = $this->request->crudInfo;

        if (in_array($crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许导入');
        }

        $datas = $request->post('import', []);
        if (!$datas) {
            return $this->fail('缺少导入的数据');
        }

        return $this->success($this->service->runImportData($crudInfo, $datas, auth('admin')->id()));
    }

    /**
     * 评论.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('{name}/comment/{id}', '低代码评论')]
    public function saveComment(Request $request, SystemCrudCommentService $commentService)
    {
        $id = $this->request->route()->parameter('id', 0);
        $crudInfo = $this->request->crudInfo;
        $data = $request->postMore([
            ['pid', 0],
            ['comment', ''],
        ]);

        if (!$id) {
            return $this->fail('缺少参数');
        }
        if (!$data['comment']) {
            return $this->fail('请输入评论内容');
        }

        $commentService->createComment($crudInfo, $data, (int)$id, auth('admin')->id());

        return $this->success('评论成功');
    }

    /**
     * 修改评论.
     * @return mixed
     */
    #[Put('{name}/comment/{comment_id}', '低代码修改评论')]
    public function updateComment(Request $request, SystemCrudCommentService $commentService)
    {
        $id = $this->request->route()->parameter('comment_id', 0);
        $data = $request->postMore([
            ['comment', ''],
        ]);

        if (!$id) {
            return $this->fail('缺少参数');
        }
        if (!$data['comment']) {
            return $this->fail('请输入评论内容');
        }

        $comment = $commentService->get($id);
        if (!$comment) {
            return $this->fail('评论不存在');
        }

        if ($comment->uid != auth('admin')->id()) {
            return $this->fail('只能修改自己的评论');
        }

        $comment->comment = $data['comment'];
        $comment->save();

        return $this->success('修改成功');
    }

    /**
     * 删除评论.
     * @return mixed
     */
    #[Delete('{name}/comment/{comment_id}', '低代码删除评论')]
    public function deleteComment(SystemCrudCommentService $commentService)
    {
        $id = $this->request->route()->parameter('comment_id', 0);

        if (!$id) {
            return $this->fail('缺少参数');
        }

        $comment = $commentService->get($id);
        if (!$comment) {
            return $this->fail('评论不存在');
        }

        if ($comment->uid != auth('admin')->id()) {
            return $this->fail('只能删除自己的评论');
        }

        // 删除下级的所有评论
        if ($commentService->exists(['pid' => $comment->id])) {
            $commentService->delete(['pid' => $comment->id]);
        }

        $comment->delete();

        return $this->success('删除成功');
    }

    /**
     * 获取评论列表.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Get('{name}/comment/{id}', '低代码获取评论列表')]
    public function getCommentList(SystemCrudCommentService $commentService)
    {
        $id = $this->request->route()->parameter('id', 0);
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($commentService->getCommentList($this->request->crudInfo, (int)$id));
    }

    /**
     * 获取日志列表.
     */
    #[Get('{name}/log/{id}', '低代码获取日志列表')]
    public function getLogList(SystemCrudLogService $service)
    {
        $id = $this->request->route()->parameter('id', 0);
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($service->getLogList($this->request->crudInfo, (int)$id));
    }

    /**
     * 低代码移交数据.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('{name}/transfer', '低代码移交数据')]
    public function transfer()
    {
        if (in_array($this->request->crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允进行移交');
        }

        $systemUserId = $this->request->post('system_user_id', []);
        $ids = $this->request->post('ids', []);
        $userId = $this->request->post('user_id', 0);
        if (!$ids) {
            return $this->fail('请选择需要移交的数据');
        }
        if (!$userId) {
            return $this->fail('请选择需要移交给谁的用户');
        }
        $ids = array_filter($ids, function ($v) {
            return (int)$v;
        });
        $this->service->transferData($this->request->crudInfo, $ids, (int)$userId, $systemUserId, auth('admin')->id());

        return $this->success('移交成功');
    }

    /**
     * 共享数据.
     * @return mixed|void
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('{name}/share', '低代码共享数据')]
    public function createShare()
    {
        if (in_array($this->request->crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允进行共享');
        }

        $systemUserId = $this->request->post('system_user_id', []);
        $ids = $this->request->post('ids', []);
        $userIds = $this->request->post('user_ids', []);
        $roleType = $this->request->post('role_type', 0);
        if (!$ids) {
            return $this->fail('请选择需要移交的数据');
        }
        if (!$userIds) {
            return $this->fail('请选择需要移交给谁的用户');
        }
        if (!in_array($roleType, [0, 1, 2])) {
            return $this->fail('请选择正确的共享类型');
        }
        $ids = array_filter($ids, function ($v) {
            return (int)$v;
        });
        $this->service->shareData($this->request->crudInfo, $ids, $userIds, (int)$roleType, $systemUserId, auth('admin')->id());

        return $this->success('共享成功');
    }

    /**
     * 修改共享权限.
     * @return mixed
     */
    #[Put('{name}/share/{id}', '低代码共享数据')]
    public function updateShare()
    {
        $id = $this->request->route()->parameter('id', 0);
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $roleType = $this->request->post('role_type', 0);

        $this->service->shareUpdate($this->request->crudInfo, (int)$id, (int)$roleType, auth('admin')->id());

        return $this->success('修改成功');
    }

    /**
     * 删除共享.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Delete('{name}/share/{id}', '低代码共享数据')]
    public function deleteShare()
    {
        $id = $this->request->route()->parameter('id', 0);
        $dataId = $this->request->post('data_id', 0);
        if (!$id || !$dataId) {
            return $this->fail('缺少参数');
        }
        $this->service->shareDelete($this->request->crudInfo, (int)$id, auth('admin')->id(), (int)$dataId);

        return $this->success('修改成功');
    }

    /**
     * 共享的被用户取消共享.
     * @return mixed
     * @throws BindingResolutionException
     */
    #[Delete('{name}/cancel_share/{data_id}', '低代码共享数据')]
    public function cancelShare()
    {
        $dataId = $this->request->route()->parameter('data_id', 0);
        if (!$dataId) {
            return $this->fail('缺少参数');
        }
        $this->service->cancelShare($this->request->crudInfo, (int)$dataId, auth('admin')->id());

        return $this->success('修改成功');
    }

    /**
     * 共享数据列表.
     * @return mixed
     */
    #[Get('{name}/share/{data_id}', '低代码共享数据列表')]
    public function shareList(SystemCrudShareService $service)
    {
        $dataId = $this->request->route()->parameter('data_id', 0);
        return $this->success($service->shareList($this->request->crudInfo->id, (int)$dataId));
    }

    /**
     * 创建问卷调查.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Post('{name}/questionnaire', '低代码问卷调查')]
    public function createQuestionnaire(SystemCrudQuestionnaireService $service)
    {
        if (in_array($this->request->crudInfo->table_name_en, app()->get(SystemCrudService::class)->notAllowOperateTable())) {
            return $this->fail('系统默认数据不允许创建问卷');
        }

        $data = $this->request->postMore([
            ['role_type', 0],
            ['invalid_type', 0],
        ]);

        switch ((int)$data['invalid_type']) {
            case 1:// 1天
                $data['invalid_time'] = date('Y-m-d H:i:s', time() + 86400);
                break;
            case 7:// 7天
                $data['invalid_time'] = date('Y-m-d H:i:s', time() + 604800);
                break;
            case 30:// 30天
                $data['invalid_time'] = date('Y-m-d H:i:s', time() + 2592000);
                break;
            default:// 永久
                $data['invalid_time'] = date('Y-m-d H:i:s', datetime_timestamp('2999-12-01 23:59:59'));
                break;
        }

        unset($data['invalid_type']);
        return $this->success($service->createQuestionnaire($this->request->crudInfo, $data, auth('admin')->id()));
    }

    /**
     * 修改问卷调查.
     * @return mixed
     */
    #[Put('{name}/questionnaire/{id}', '修改问卷调查')]
    public function updateQuestionnaire(SystemCrudQuestionnaireService $service)
    {
        $id = $this->request->route()->parameter('id', 0);
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $status = (int)$this->request->post('status', 0);

        $questionnaire = $service->get($id, ['id', 'status', 'invalid_time']);
        if (!$questionnaire) {
            return $this->fail('没有找到问卷表单信息');
        }
        if ($questionnaire->invalid_time < date('Y-m-d H:i:s') && $status) {
            return $this->fail('问卷已经过期,无法打开状态');
        }
        $questionnaire->status = $status;
        $questionnaire->save();

        return $this->success('修改成功');
    }

    /**
     * 获取问卷调查.
     */
    #[Get('{name}/questionnaire', '获取问卷调查')]
    public function listQuestionnaire(SystemCrudQuestionnaireService $service)
    {
        return $this->success($service->listQuestionnaire($this->request->crudInfo));
    }

    /**
     * 删除问卷调查.
     * @return mixed
     */
    #[Delete('{name}/questionnaire/{id}', '删除问卷调查')]
    public function deleteQuestionnaire(SystemCrudQuestionnaireService $service)
    {
        $id = $this->request->route()->parameter('id', 0);
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $service->delete($id);

        return $this->success('删除成功');
    }
}
