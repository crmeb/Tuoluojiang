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

namespace App\Http\Service\Crud;

use App\Constants\Crud\CrudFormEnum;
use App\Http\Dao\Crud\SystemCrudQuestionnaireDao;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Crud\SystemCrud;
use App\Http\Service\Admin\AdminService;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 问卷.
 */
class SystemCrudQuestionnaireService extends BaseService
{
    /**
     * 白名单路由.
     * @var array|string[]
     */
    protected array $whiteList = [
        '/^config\/dict_data\/tree$/',
        '/^crud\/module\/association_list\/[0-9]+$/',
        '/^crud\/module\/association_field\/[0-9]+$/',
        '/^common\/upload_key$/',
        '/^system\/attach_cate[a-zA-Z_\/]+$/',
        '/^system\/attach[a-zA-Z_\/]+$/',
        '/^dict\/data\/tree$/',
        '/^attach\/imgs(\/.*)?$/i'
    ];

    public function __construct(SystemCrudQuestionnaireDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建问卷地址
     * @return mixed []
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function createQuestionnaire(SystemCrud $crud, array $data, int $userId)
    {
        $data['unique']  = uniqid('OA');
        $data['crud_id'] = $crud->id;
        $data['user_id'] = $userId;
        $data['status']  = 1;
        $data['url']     = sys_config('site_url') . '/q/' . $data['unique'];

        $service = app(SystemCrudFieldService::class);

        $fieldNameEn = $crud->table_name_en . '_ip';
        $hasColumn   = Schema::hasColumn($crud->table_name_en, $fieldNameEn);
        if (! $hasColumn) {
            $service->addField(crudId: $crud->id, value: CrudFormEnum::FORM_INPUT, fieldName: 'IP地址', fieldNameEn: $fieldNameEn, isTableShowRow: false);
        }

        return $this->dao->create($data)->toArray();
    }

    /**
     * 获取问卷列表.
     * @return array|mixed
     */
    public function listQuestionnaire(SystemCrud $crud)
    {
        $response = $this->getList(where: ['crud_id' => $crud->id], sort: 'id', with: ['user' => fn ($q) => $q->select(['id', 'name'])]);

        foreach ($response[$this->listField['list']] as &$item) {
            if ($item['invalid_time'] < date('Y-m-d H:i:s')) {
                $item['status'] = 0;
                $this->dao->update($item['id'], ['status' => 0]);
                $item['invalid_time'] = '已过期';
            }
        }
        return $response;
    }

    /**
     * 获取问卷信息.
     * @return array
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getQuestionnaire(string $unique)
    {
        $info = $this->dao->get(['unique' => $unique], ['*'], ['crud']);
        if (! $info) {
            throw $this->exception('没有查询到问卷信息');
        }
        if ($info->invalid_time < date('Y-m-d H:i:s')) {
            throw $this->exception('问卷已经过期');
        }
        if (! $info->status) {
            throw $this->exception('问卷没有开启');
        }
        if (empty($info['crud'])) {
            throw $this->exception('问卷关联的实体信息不存在');
        }
        return $info;
    }

    /**
     * 检测是否登录.
     * @return Admin|bool
     * @throws BindingResolutionException
     */
    public function checkUniqueisLogin(Request $request)
    {
        // 获取问卷唯一标识（支持 header 和 Referer 两种方式）
        $unique = $request->header('Curd-Unique');
        if (! $unique) {
            // 从 Referer 解析问卷唯一标识
            $referer = $request->header('Referer');
            if ($referer && preg_match('/[?&]unique=([^&]+)/', $referer, $matches)) {
                $unique = $matches[1];
            }
        }
        if (! $unique) {
            return true;
        }

        // 验证问卷是否过期
        try {
            $questionnaire = $this->getQuestionnaire($unique);
        } catch (\Throwable) {
            return true;
        }

        // 判断是否在白名单内
        $iswhitePath = false;
        $path        = str_replace(['api/ent/', 'api/uni/'], '', $request->path());
        foreach ($this->whiteList as $item) {
            if (preg_match($item, $path)) {
                $iswhitePath = true;
                break;
            }
        }

        // 如果没有在白名单还是需要登陆
        if (! $iswhitePath) {
            return true;
        }

        // 一对一关联的接口权限控制
        if (preg_match('/^crud\/module\/association_list\/[0-9]+$/', $path) || preg_match('/^crud\/module\/association_field\/[0-9]+$/', $path)) {
            $id = $request->route()->parameter('id');
            if (! $id) {
                return true;
            }
            $associationCrudId = app()->get(SystemCrudFieldService::class)->value($id, 'association_crud_id');
            // 获取当前问卷关联的关联实体
            $associationCrudList = app()->get(SystemCrudFieldService::class)->getAssociationCrudId($questionnaire->crud_id, 1);
            $associationCrudIds  = array_column($associationCrudList, 'association_crud_id');
            if (! in_array($associationCrudId, $associationCrudIds)) {
                return true;
            }
        }

        // 没有登陆返回当前问卷的用户信息
        $userInfo = app()->get(AdminService::class)->get($questionnaire->user_id);
        if (! $userInfo) {
            return true;
        }

        return $userInfo;
    }
}
