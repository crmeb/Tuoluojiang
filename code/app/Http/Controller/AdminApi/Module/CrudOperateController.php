<?php

namespace App\Http\Controller\AdminApi\Module;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\AuthEnterprise;
use App\Http\Middleware\LogEnterprise;
use App\Http\Service\Crud\SystemCrudOperateService;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 操作按钮
 */
#[Prefix('ent/crud')]
#[Middleware([AuthAdmin::class, AuthEnterprise::class, LogEnterprise::class])]
class CrudOperateController  extends AuthController
{
    /**
     * @param SystemCrudOperateService $service
     */
    public function __construct(SystemCrudOperateService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function index()
    {
        $crudId = $this->request->get('crud_id');
        return $this->service->getOperateList($crudId);
    }
}
