<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Controller\AdminApi\Module;

use Illuminate\Database\Eloquent\Model;
use App\Http\Contract\System\MenusInterface;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Crud\SystemCrudCateService;
use App\Http\Service\Crud\SystemCrudService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 实体应用.
 */
#[Prefix('ent/crud/cate')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CrudCateController extends AuthController
{
    /**
     * Crud constructor.
     */
    public function __construct(SystemCrudCateService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取分类.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Get('/list', '实体分类列表')]
    public function getCate()
    {
        $name = $this->request->get('name', '');
        return $this->success($this->service->getCateList(
            ['name' => $name],
            ['name', 'id', 'info'],
            [
                'sort' => 'desc',
                'id'   => 'desc',
            ],
            ['menu' => fn($q) => $q->select(['icon', 'paths', 'crud_app_id'])]
        ));
    }

    /**
     * 获取应用.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Get('/find/{id}', '实体分类列表')]
    public function getCateFind($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $cate = $this->service->get(where: $id, with: [
            'menu' => fn($q) => $q->select([
                '*'
            ]),
        ]);
        if (!$cate) {
            return $this->fail('数据不存在');
        }
        $cate = $cate->toArray();
        $cate['menu']['path'] = $cate['menu']['paths'] ?? [];
        $cate['menu']['icon'] = $cate['menu']['icon'] ?? '';
        return $this->success($cate);
    }

    /**
     * 保存单个应用.
     * @return mixed
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @email 136327134@qq.com
     * @date 2026/1/13
     */
    #[Post('/one_save/{id?}', '保存单个应用')]
    public function saveOneCate($id = 0)
    {
        $data = $this->request->postMore([
            ['icon', ''],
            ['info', ''],
            ['name', ''],
            ['path', []],
            ['sort', ''],
        ]);

        if (!$data['name']) {
            return $this->fail('请填写应用名称');
        }
        if (!is_array($data['path'])) {
            $data['path'] = [];
        }
        /** @var Model $menusModel */
        $menusModel = app()->get(MenusInterface::class)->getModel();
        if ($id) {
            $this->service->update($id, [
                'name' => $data['name'],
                'info' => $data['info'],
                'sort' => $data['sort'],
            ]);

            $menuData = [
                'menu_name'   => $data['name'],
                'icon'        => $data['icon'],
                'crud_id'     => 0,
                'menu_type'   => 1,
                'type'        => 'M',
                'unique_auth' => uniqid('module'),
                'menu_path'   => '/crud/module',
                'sort'        => $data['sort'],
            ];
            $menuData['uniqued'] = md5($menuData['unique_auth']);
            $path = $menuData['paths'] = array_filter($data['path']);
            $menuData['paths'] = implode('/', $path);
            if ($path) {
                $menuData['pid'] = end($path);
                $menuData['level'] = count($path);
                if ($menuData['pid']) {
                    $menuData['parent_uniqued'] = app()->get(MenusInterface::class)->getModel()->where('id', $menuData['pid'])->value('unique_auth');
                }
            }

            if ($menusModel->where('crud_app_id', $id)->value('id')) {
                unset($menuData['uniqued'], $menuData['unique_auth']);
                $menusModel->where('crud_app_id', $id)->update($menuData);
            } else {
                $menuData['crud_app_id'] = $id;
                $menusModel->create($menuData);
            }
        } else {
            $res = $this->service->create([
                'name' => $data['name'],
                'info' => $data['info'],
                'sort' => $data['sort'],
            ]);
            $menuData = [
                'menu_name'   => $data['name'],
                'icon'        => $data['icon'],
                'crud_id'     => 0,
                'menu_type'   => 1,
                'type'        => 'M',
                'unique_auth' => uniqid('module'),
                'menu_path'   => '/crud/module',
                'sort'        => $data['sort'],
                'crud_app_id' => $res->id,
            ];
            $menuData['uniqued'] = md5($menuData['unique_auth']);
            $path = $menuData['paths'] = array_filter($data['path']);
            $menuData['paths'] = implode('/', $path);
            if ($path) {
                $menuData['pid'] = end($path);
                $menuData['level'] = count($path);
                if ($menuData['pid']) {
                    $menuData['parent_uniqued'] = app()->get(MenusInterface::class)->getModel()->where('id', $menuData['pid'])->value('unique_auth');
                }
            }
            if ($menusModel->where('crud_app_id', $res->id)->value('id')) {
                unset($menuData['uniqued'], $menuData['unique_auth']);
                $menusModel->where('crud_app_id', $res->id)->update($menuData);
            } else {
                $menusModel->create($menuData);
            }
        }

        event('system.crud');

        return $this->success('添加成功');
    }

    /**
     * 保存应用.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Post('/save', '实体分类保存')]
    public function saveCate()
    {
        $data = $this->request->postMore([
            ['cate', []],
        ]);

        foreach ($data['cate'] as $item) {
            if (!$item['name']) {
                return $this->fail('请填写应用名称');
            }
            if ($item['id']) {
                $this->service->update($item['id'], [
                    'name' => $item['name'],
                    'sort' => $item['sort'],
                ]);
            } else {
                $this->service->create([
                    'name' => $item['name'],
                    'sort' => $item['sort'],
                ]);
            }
        }

        event('system.crud');

        return $this->success('添加成功');
    }

    /**
     * 删除分类.
     * @return mixed
     * @email 136327134@qq.com
     * @date 2024/2/29
     */
    #[Delete('/del/{id}', '实体分类删除')]
    public function deleteCate($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $crudNumber = app()->make(SystemCrudService::class)->getCateCrudNum((int)$id);
        if ($crudNumber) {
            return $this->fail('请先删除该应用下的实体');
        }

        $this->service->delete($id);

        event('system.crud');

        return $this->success('删除成功');
    }
}
