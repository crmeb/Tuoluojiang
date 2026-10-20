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

namespace App\Http\Controller\AdminApi\Config;

use App\Constants\System\CategoryEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\category\CategoryRequest;
use App\Http\Service\Config\SystemConfigCateService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\CateControllerTrait;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

/**
 * 系统配置分类
 * Class ConfigCateController.
 */
#[Prefix('ent/config')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ConfigCateController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;
    use CateControllerTrait;

    protected array $baseKeys = [
        CategoryEnum::SYSTEM_CONFIG['key'],
        CategoryEnum::STORAGE_CONFIG['key'],
        CategoryEnum::PUSH_CONFIG['key'],
        CategoryEnum::UPLOAD_CONFIG['key'],
    ];

    public function __construct(SystemConfigCateService $services)
    {
        parent::__construct();
        $this->service = $services;
        $this->setShowField('is_show');
    }

    /**
     * @return mixed
     */
    #[Get('cate', '配置分类列表')]
    public function index()
    {
        $cates = [];
        foreach (CategoryEnum::values() as $key => $value) {
            if (in_array(strtolower($key), $this->baseKeys)) {
                $cates[] = $value->getValue();
            }
        }
        return $this->success($cates);
    }

    protected function getRequestClassName(): string
    {
        return CategoryRequest::class;
    }
}
