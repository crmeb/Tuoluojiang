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

use App\Constants\Ai\AiModelEnum;
use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\Ai\AiModelRequest;
use App\Http\Service\Ai\AiModelService;
use Tuoluojiang\Open\interfaces\ResourceControllerInterface;
use Tuoluojiang\Open\traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Resource;
use Spatie\RouteAttributes\Attributes\WhereNumber;

/**
 * AI 模型配置管理控制器.
 *
 * 路由前缀 ent/ai/models，对应表 chat_models.
 * 鉴权：auth.admin + ent.auth + ent.log（JWT 管理端登录）.
 * 支持 CRUD、供应商预设、远程拉取模型列表与连接测试.
 */
#[Prefix('ent/ai/models')]
#[Resource('/', false, except: ['create', 'show'], names: [
    'index'   => '获取AI模型配置列表',
    'store'   => '保存AI模型配置接口',
    'edit'    => '获取AI模型配置接口',
    'update'  => '修改AI模型配置接口',
    'destroy' => '删除AI模型配置接口',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class AiModelController extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait {
        show as protected resourceShow;
    }

    /**
     * AiModelController constructor.
     */
    public function __construct(AiModelService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取启用模型下拉.
     */
    #[Get('list', '获取AI模型下拉')]
    public function list()
    {
        return $this->success($this->service->list());
    }

    /**
     * 修改AI模型配置状态.
     * 单独注册并限制 id 为数字，避免 Resource 的 {id} 抢先匹配 list/options/select/fetch.
     * @param mixed $id
     */
    #[Get('{id}', '修改AI模型配置状态')]
    #[WhereNumber('id')]
    public function show($id)
    {
        return $this->resourceShow($id);
    }

    /**
     * 获取模型供应商选项.
     */
    #[Get('options', '获取AI模型供应商')]
    public function options()
    {
        return $this->success($this->service->presetOptions());
    }

    /**
     * 获取供应商模型类型.
     */
    #[Get('select', '获取AI模型类型')]
    public function select()
    {
        return $this->success($this->service->select((string) $this->request->get('provider', 'deepseek')));
    }

    /**
     * 从预设供应商获取模型列表.
     */
    #[Post('fetch', '从预设供应商获取模型列表')]
    public function fetch()
    {
        $request = app(AiModelRequest::class)->scene('fetch');
        $request->check();
        $data = $this->request->postMore([
            ['id', 0],
            ['provider', 'deepseek'],
            ['config_type', 0],
            ['api_key', ''],
            ['base_url', ''],
        ]);

        return $this->success($this->service->fetchModels($data));
    }

    /**
     * 测试模型连接.
     *
     * 入参：config_type（1 预设 / 2 自定义 / 3 一号通）、provider、api_key、
     * model_id、model_type；预设与一号通可不传 base_url.
     */
    #[Post('test', '测试AI模型连接')]
    public function test()
    {
        $request = app(AiModelRequest::class)->scene('test');
        $request->check();
        $data = $this->request->postMore($this->getRequestFields());

        return $this->success($this->service->testConnection($data));
    }

    /**
     * 搜索字段.
     */
    protected function getSearchField(): array
    {
        return [
            ['name', ''],
            ['provider', ''],
            ['config_type', ''],
            ['status', ''],
            ['model_type', ''],
        ];
    }

    /**
     * 设置验证类.
     */
    protected function getRequestClassName(): string
    {
        return AiModelRequest::class;
    }

    /**
     * 提取请求字段.
     */
    protected function getRequestFields(): array
    {
        return [
            ['uid', auth('admin')->id()],
            // 编辑态 test 可带 id，留空 api_key 时由服务端复用库内密钥
            ['id', 0],
            ['name', ''],
            ['provider', 'deepseek'],
            ['config_type', 1],
            ['base_url', ''],
            ['is_full_url', 0],
            ['chat_path', ''],
            ['embedding_path', ''],
            ['model_id', ''],
            ['model_type', AiModelEnum::MODEL_TYPE_CHAT],
            ['api_key', ''],
            ['status', 1],
            ['enable_thinking', AiModelEnum::THINKING_DEFAULT],
            ['json', []],
        ];
    }
}
