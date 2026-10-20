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

namespace App\Http\Controller\AdminApi\Ai;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Ai\AiMcpServiceRequest;
use App\Http\Service\Ai\AiMcpService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;
use Spatie\RouteAttributes\Attributes\WhereNumber;

/**
 * AI MCP 服务配置管理控制器.
 *
 * 路由前缀 ent/ai/mcp，对应表 chat_mcp_services.
 * 鉴权：auth.admin + ent.auth + ent.log（JWT 管理端登录）.
 * config_json 为单条 server 扁平 JSON；内置服务含 module 字段；应用侧 mcp_json 合并为 mcpServers.
 */
#[Prefix('ent/ai/mcp')]
#[Resource('/', false, except: ['create', 'show'], names: [
    'index'   => 'AI MCP服务列表',
    'store'   => '保存AI MCP服务',
    'edit'    => '获取AI MCP服务',
    'update'  => '修改AI MCP服务',
    'destroy' => '删除AI MCP服务',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AiMcpController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait {
        show as protected resourceShow;
    }

    public function __construct(AiMcpService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 修改 AI MCP 服务状态.
     *
     * @param int|string $id MCP 服务 ID
     */
    #[Get('{id}', '修改AI MCP服务状态')]
    #[WhereNumber('id')]
    public function show($id)
    {
        return $this->resourceShow($id);
    }

    /**
     * 测试 MCP 服务连接是否可用.
     */
    #[Post('test-connection', '测试AI MCP连接')]
    public function testConnection()
    {
        $result = $this->service->testConnection($this->request->postMore($this->getRequestFields()));

        return ($result['success'] ?? false) ? $this->success($result) : $this->fail((string) ($result['message'] ?? '连接失败'));
    }

    /**
     * 获取指定 MCP 服务暴露的工具列表.
     *
     * @param int|string $id MCP 服务 ID
     */
    #[Get('{id}/tools', '获取AI MCP工具列表')]
    #[WhereNumber('id')]
    public function tools($id)
    {
        return $this->success($this->service->tools((int) $id));
    }

    protected function getSearchField(): array
    {
        return [
            ['name', ''],
            ['status', ''],
            // 外部应用关联插件时传 is_default=0 排除内置 MCP；内部应用可不传.
            ['is_default', ''],
        ];
    }

    protected function getRequestClassName(): string
    {
        return AiMcpServiceRequest::class;
    }

    protected function getRequestFields(): array
    {
        return [
            ['name', ''],
            ['info', ''],
            ['config_json', []],
            ['status', 1],
            ['sort', 0],
            ['is_default', 0],
        ];
    }
}
