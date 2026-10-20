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

namespace App\Http\Controller\AdminApi\Forum;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\System\MenusService;
use App\Http\Service\System\RolesService;
use Tuoluojiang\Open\services\synchro\Article;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 知识社区.
 * Class ArticleController.
 */
class ArticleController extends AuthController
{
    public function __construct(Article $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 文章用户修改密码
     * @return mixed
     * @throws BindingResolutionException
     */
    public function editPwd()
    {
        $data = $this->request->postMore([
            ['phone', ''],
            ['password', ''],
            ['captcha', ''],
        ]);
        $this->service->setFromType($this->uuid)->saveUser($data);
        return $this->success('密码修改成功');
    }

    /**
     * 文章用户修改密码
     * @return mixed
     * @throws BindingResolutionException
     */
    public function login()
    {
        $data = $this->request->postMore([
            ['phone', ''],
            ['captcha', ''],
        ]);
        return $this->success($this->service->setFromType($this->uuid)->articleLogin($data));
    }

    /**
     * 修改文章推荐标签.
     * @param mixed $types
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function label(MenusService $service, $types = 0)
    {
        $menus = $service->getMenusForUser($this->uuid, $this->entId, false, 'menu_path');
        return $this->success($this->service->setFromType($this->uuid)->articleCate($types, $menus));
    }

    /**
     * 保存用户标签.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function saveLabel()
    {
        $data = $this->request->postMore([
            ['label', []],
        ]);
        if (! $data['label']) {
            return $this->fail('请选择标签');
        }
        return $this->success($this->service->setFromType($this->uuid)->articleLabelSave($data));
    }

    /**
     * 获取用户标签.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getLabel()
    {
        return $this->success($this->service->setFromType($this->uuid)->articleLabelGet());
    }

    /**
     * 获取文章列表.
     * @return mixed
     */
    public function list()
    {
        $data = $this->request->postMore([
            ['sort', ''],
            ['page', 1],
            ['limit', 20],
            ['name', ''],
            ['label_id', ''],
            ['status', 0],
            ['draft', 0],
            ['menus', app()->get(MenusService::class)->getMenusForUser($this->uuid, $this->entId, false, 'menu_path')],
        ]);
        return $this->success($this->service->setFromType($this->uuid)->articleList($data));
    }

    /**
     * 获取文章详情.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function info($id)
    {
        if (! $id) {
            return $this->fail('缺少文章ID');
        }
        return $this->success($this->service->setFromType($this->uuid)->articleInfo($id));
    }

    /**
     * 保存文章信息.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['cid', 0],
            ['label', []],
            ['title', ''],
            ['info', ''],
            ['author', ''],
            ['image', ''],
            ['url', ''],
            ['types', ''],
            ['content', ''],
            ['draft', 0],
        ]);
        return $this->success($this->service->setFromType($this->uuid)->articleSave($data));
    }

    /**
     * 收藏文章.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function collect()
    {
        [$id, $status] = $this->request->postMore([
            ['id', 0],
            ['status', 1],
        ], true);
        if (! $id) {
            return $this->fail('缺少文章ID');
        }
        return $this->success($this->service->setFromType($this->uuid)->articleCollect((int) $id, $status));
    }

    /**
     * 点赞文章.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function support()
    {
        [$id, $status] = $this->request->postMore([
            ['id', 0],
            ['status', 1],
        ], true);
        if (! $id) {
            return $this->fail('缺少文章ID');
        }
        return $this->success($this->service->setFromType($this->uuid)->articleSupport((int) $id, $status));
    }

    /**
     * 文章.
     * @param mixed $id
     * @return mixed
     * @throws BindingResolutionException
     */
    public function delete($id)
    {
        return $this->success($this->service->setFromType($this->uuid)->articleDelete((int) $id));
    }

    /**
     * 文章图片上传.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function image()
    {
        [$image] = $this->request->postMore([
            ['image', ''],
        ], true);
        return $this->success($this->service->setFromType($this->uuid)->articleImage($image));
    }

    /**
     * 用户文章数量.
     * @return mixed
     * @throws BindingResolutionException
     */
    public function count()
    {
        return $this->success($this->service->setFromType($this->uuid)->articleCount());
    }

    /**
     * 文章推荐列表.
     * @throws BindingResolutionException
     */
    public function recList()
    {
        $where = $this->request->postMore([
            ['filter_id', [], 'id'],
            ['menus', app()->get(RolesService::class)->getRolesForUser($this->uuid, $this->entId, withApi: false)],
        ]);
        $where['id'] = array_filter(array_map('intval', (array) $where['id']));
        return $this->success($this->service->setFromType($this->uuid)->articleRecList($where));
    }

    /**
     * 个人统计
     * @throws InvalidArgumentException
     */
    public function suspension(): mixed
    {
        return $this->success($this->service->setFromType($this->uuid)->articleSuspension());
    }

    /**
     * 成就统计
     * @param mixed $uid
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function authorSuspension($uid)
    {
        if (! $uid) {
            return $this->fail('common.empty.attrs');
        }
        return $this->success($this->service->setFromType($this->uuid)->articleAuthorAchievement(['uid' => $uid]));
    }

    /**
     * 获取热门文章.
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function hotIds()
    {
        return $this->success($this->service->setFromType($this->uuid)->hotIds());
    }
}
