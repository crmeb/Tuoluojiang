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
use App\Http\Requests\Ai\AiApplicationRequest;
use App\Http\Service\Ai\AiApplicationService;
use App\Http\Service\Ai\AiStreamService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Illuminate\Support\Facades\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Resource;
use Spatie\RouteAttributes\Attributes\WhereNumber;

/**
 * AI 应用管理控制器.
 *
 * 路由前缀 ent/ai/applications，对应表 chat_applications.
 * 鉴权：auth.admin + ent.auth + ent.log（JWT 管理端登录）.
 * 支持 CRUD、快速新增、保存草稿、发布、人设/提示词生成、知识库绑定、数据库插件、外部分享配置等.
 * 详情（edit）额外返回 mcp_services、knowledge 基本信息，便于编辑页展示已关联插件.
 */
#[Prefix('ent/ai/applications')]
#[Resource('/', false, except: ['create', 'show'], names: [
    'index'   => '获取AI应用列表',
    'store'   => '保存AI应用接口',
    'edit'    => '获取AI应用接口',
    'update'  => '修改AI应用接口',
    'destroy' => '删除AI应用接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AiApplicationController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait {
        show as protected resourceShow;
    }

    public function __construct(AiApplicationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 修改AI应用状态.
     * 单独注册并限制 id 为数字，避免 Resource 的 {id} 抢先匹配静态路径.
     * @param mixed $id
     */
    #[Get('{id}', '修改AI应用状态')]
    #[WhereNumber('id')]
    public function show($id)
    {
        return $this->resourceShow($id);
    }

    /**
     * 获取 AI 应用已绑定的知识库列表.
     *
     * @param int|string $id 应用 ID
     */
    #[Get('{id}/knowledge', '获取AI应用绑定知识库')]
    #[WhereNumber('id')]
    public function knowledge($id)
    {
        return $this->success($this->service->knowledge((int) $id));
    }

    /**
     * 批量绑定知识库到 AI 应用.
     *
     * @param int|string $id 应用 ID
     */
    #[Post('{id}/knowledge', '绑定AI应用知识库')]
    #[WhereNumber('id')]
    public function bindKnowledge($id)
    {
        $request = app(AiApplicationRequest::class)->scene('knowledge');
        $request->check();
        $this->service->bindKnowledge((int) $id, (array) $this->request->post('knowledge_ids', []));

        return $this->success('绑定成功');
    }

    /**
     * 解绑 AI 应用与指定知识库的关联.
     *
     * @param int|string $id 应用 ID
     * @param int|string $kbId 知识库 ID
     */
    #[Delete('{id}/knowledge/{kbId}', '解绑AI应用知识库')]
    #[WhereNumber('id')]
    public function unbindKnowledge($id, $kbId)
    {
        $this->service->unbindKnowledge((int) $id, (int) $kbId);

        return $this->success('解绑成功');
    }

    /**
     * 通过基本信息快速新增 AI 应用（未发布）.
     *
     * 仅需应用名称；简介与模型配置 ID 选填，app_type=0 为内部应用、1 为智能客服，省略默认 0。
     * 新建应用 status=0，当前登录用户自动写入 edit_ids；仅内部应用写入 auth_ids.
     */
    #[Post('quick', '快速新增AI应用')]
    public function quick()
    {
        $request = app(AiApplicationRequest::class)->scene('quick');
        $request->check();

        $app = $this->service->quickCreate([
            'name'      => (string) $this->request->post('name', ''),
            'info'      => (string) $this->request->post('info', ''),
            'models_id' => (int) $this->request->post('models_id', 0),
            'app_type'  => (int) $this->request->post('app_type', 0),
        ]);

        return $this->success('common.insert.succ', ['id' => $app->id]);
    }

    /**
     * 按类型流式生成 AI 应用提示内容（人设与回复、开场白、开场白问题等）.
     *
     * 入参：models_id、content（上下文）、type；SSE 调模型推送正文分片，不落库.
     */
    #[Post('generate-prompt', '生成AI应用提示内容')]
    public function generatePrompt()
    {
        $request = app(AiApplicationRequest::class)->scene('generate_prompt');
        $request->check();

        return $this->streamResponse(fn () => $this->service->generatePrompt(
            (int) $this->request->post('models_id', 0),
            (string) $this->request->post('content', ''),
            (string) $this->request->post('type', ''),
        ));
    }

    /**
     * 封装 SSE 响应头与异常兜底输出.
     */
    private function streamResponse(callable $callback)
    {
        return Response::stream(function () use ($callback) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            try {
                $callback();
            } catch (\Throwable $e) {
                app(AiStreamService::class)->error($e->getMessage());
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=utf-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Encoding'  => 'identity',
        ]);
    }

    /**
     * 获取企业可绑定的数据库表列表（SQL 插件数据源，当前产品已关闭）.
     */
    #[Get('databes/list', '数据库列表')]
    public function databes()
    {
        return $this->success($this->service->databaseTables((string) $this->request->get('keyword', '')));
    }

    /**
     * 根据选定表生成数据库插件提示词文本（当前产品已关闭）.
     */
    #[Post('database/tooltip', '数据库提示文本')]
    public function databaseTooltip()
    {
        return $this->success([
            'tooltip_text' => $this->service->databaseTooltip((array) $this->request->post('tables', [])),
        ]);
    }

    /**
     * 获取外部应用分享配置（仅 app_type=1）.
     *
     * @param int|string $id 应用 ID
     */
    #[Get('{id}/share', '获取AI应用分享配置')]
    #[WhereNumber('id')]
    public function shareSettings($id)
    {
        return $this->success($this->service->getShareSettings((int) $id));
    }

    /**
     * 更新分享过期时间、访客限额与 guest_json（含 Widget/白名单/敏感词）.
     *
     * share_enabled 与发布状态绑定，由服务端按 status 同步，不接受独立开关.
     *
     * @param int|string $id 应用 ID
     */
    #[Put('{id}/share', '更新AI应用分享配置')]
    #[WhereNumber('id')]
    public function updateShareSettings($id)
    {
        return $this->success($this->service->updateShareSettings((int) $id, $this->request->postMore([
            ['share_expire_at', ''],
            ['guest_use_limit', 0],
            ['guest_json', null],
        ])));
    }

    /**
     * 发布 AI 应用.
     *
     * 提交当前表单写入已发布配置，并把状态改为已发布。不重置已有 share_key。
     *
     * @param int|string $id 应用 ID
     */
    #[Put('{id}/publish', '发布AI应用')]
    #[WhereNumber('id')]
    public function publish($id)
    {
        $data = $this->request()->postMore($this->getRequestFields());
        $this->service->publish((int) $id, $data);

        return $this->success('发布成功');
    }

    /**
     * 重新生成 share_key，旧分享链接与 Widget 嵌入代码失效.
     *
     * @param int|string $id 应用 ID
     */
    #[Post('{id}/share/regenerate', '重新生成AI应用分享密钥')]
    #[WhereNumber('id')]
    public function regenerateShareKey($id)
    {
        return $this->success($this->service->regenerateShareKey((int) $id));
    }

    /**
     * 列表筛选字段：名称、发布状态、应用类型（内/外部）、插件类型、创建人、绑定知识库.
     *
     * @return array<int, array{0: string, 1: mixed}>
     */
    protected function getSearchField(): array
    {
        return [
            ['id', ''],
            ['name', ''],
            ['status', ''],
            ['app_type', ''],
            ['source_type', ''],
            ['uids', ''],
            ['knowledge_id', ''],
        ];
    }

    protected function getRequestClassName(): string
    {
        return AiApplicationRequest::class;
    }

    protected function getRequestFields(): array
    {
        return [
            ['uid', auth('admin')->id()],
            ['name', ''],
            ['info', ''],
            ['pic', ''],
            ['models_id', 0],
            ['enable_thinking', 1],
            ['tooltip_text', ''],
            ['prologue_text', ''],
            ['prologue_list', null],
            ['use_limit', 0],
            ['count_number', 6],
            ['source_types', null],
            ['tables', null],
            ['content', ''],
            ['keyword', null],
            ['data_arrange_text', ''],
            ['mcp_service_ids', null],
            ['agent_json', null],
            ['json', null],
            ['app_type', 0],
            ['auth_ids', []],
            ['edit_ids', []],
            ['knowledge_ids', null],
            ['status', 1],
            ['sort', 0],
        ];
    }
}
