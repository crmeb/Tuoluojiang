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

namespace App\Http\Controller\AdminApi\System;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Frame\FrameAssistService;
use App\Http\Service\ImportExport\RecordService;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 数据导入导出.
 */
#[Prefix('ent/system/data')]
#[Resource('/', false, except: ['create', 'show', 'store', 'destroy'], names: [
    'index'  => '用户协议列表',
    'edit'   => '用户协议详情',
    'update' => '用户协议修改',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ImportExportController extends AuthController
{
    public function __construct(RecordService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取列表.
     * @return mixed
     */
    #[Get('record')]
    public function index(FrameAssistService $assistService)
    {
        $where = $this->request->getMore([
            ['types', '', 'module'],
        ]);
        $where['uid'] = $assistService->getScopeUid(auth('admin')->id());
        return $this->success($this->service->getList($where));
    }

    /**
     * 获取导入模板
     * @return mixed
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Delete('delete/{id}')]
    public function delete(int $id)
    {
        $this->service->deleteRecord($id, auth('admin')->id());
        return $this->success('删除成功');
    }

    /**
     * 获取导入模板
     * @return mixed
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    #[Get('template/{types}')]
    public function template(string $types)
    {
        $url = $this->service->importTemp($types);
        return $this->success(compact('url'));
    }

    #[Post('export/{types}')]
    public function export(string $types)
    {
        $this->service->exportData($types, auth('admin')->id());
        return $this->success('数据导出中，请稍后在记录中查看...');
    }

    #[Post('import/{types}')]
    public function import(string $types)
    {
        $fileId = (int) $this->request->post('file_id', '');
        $this->service->importData($types, $fileId, auth('admin')->id());
        return $this->success('数据导入中，请稍后在记录中查看结果...');
    }
}
