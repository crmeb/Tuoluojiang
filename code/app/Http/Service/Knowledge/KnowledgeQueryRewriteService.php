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

namespace App\Http\Service\Knowledge;

use App\Constants\Ai\AiModelEnum;
use App\Constants\Ai\AiModelPresetEnum;
use App\Http\Dao\Ai\AiModelDao;
use App\Http\Model\Ai\AiModel;
use App\Http\Service\Ai\AiModelService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\services\ai\BaseCurl;
use Tuoluojiang\Open\services\ai\BaseOption;
use Illuminate\Support\Facades\Log;

/**
 * 知识库查询改写服务.
 *
 * 在 features.query_rewrite 开启且配置了 rewrite.model_id 时，调用 AiModel chat 接口
 * 将用户原问改写为更利于检索的问句；失败、超时或关闭时一律返回 trim 后的原问，不中断会话.
 */
class KnowledgeQueryRewriteService extends BaseService
{
    private const SYSTEM_PROMPT = '只输出改写后的检索问句';

    /**
     * @param AiModelDao $modelDao AI 模型配置 DAO
     */
    public function __construct(protected AiModelDao $modelDao) {}

    /**
     * 改写用户问题；失败或关闭时返回 trim 后的原问.
     *
     * @param string $question 用户原始问题
     *
     * @return string 改写后的检索问句或原问
     */
    public function rewrite(string $question): string
    {
        $original = trim($question);
        if ($original === '') {
            return '';
        }

        if (! (bool) config('knowledge.features.query_rewrite')) {
            return $original;
        }

        $modelId = (int) config('knowledge.rewrite.model_id', 0);
        if ($modelId <= 0) {
            return $original;
        }

        try {
            $model = $this->resolveChatModel($modelId);
            if (! $model) {
                return $original;
            }

            $rewritten = trim($this->requestRewrite($model, $original));

            return $rewritten !== '' ? $rewritten : $original;
        } catch (\Throwable $e) {
            Log::warning('知识库查询改写失败，回退原问', [
                'model_id' => $modelId,
                'error'    => mb_substr($e->getMessage(), 0, 500),
            ]);

            return $original;
        }
    }

    /**
     * 调用 chat 补全接口完成改写.
     *
     * @param AiModel $model    聊天模型配置
     * @param string  $question 原问
     *
     * @return string 模型返回的改写内容（可能为空）
     */
    protected function requestRewrite(AiModel $model, string $question): string
    {
        $option         = app(AiModelService::class)->makeChatOption($model);
        $option->stream = false;
        $option->tools  = [];
        $option->setMessage(self::SYSTEM_PROMPT, BaseOption::RULE_SYSTEM);
        $option->setMessage($question, BaseOption::RULE_USER);

        $preset  = AiModelPresetEnum::get((string) $model->provider);
        $auth    = (string) ($preset['auth'] ?? AiModelEnum::AUTH_BEARER);
        $timeout = $this->timeoutSeconds((int) config('knowledge.rewrite.timeout_ms', 3000));

        $response = (new BaseCurl(trim((string) $model->api_key), $option->baseUrl, $auth))
            ->setBody($option)
            ->send(url: $option->url, timeout: $timeout);

        if (! empty($response['error'])) {
            $error   = $response['error'];
            $message = is_array($error)
                ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE))
                : (string) $error;
            throw new \RuntimeException($message !== '' ? $message : '查询改写接口返回错误');
        }

        return (string) ($response['choices'][0]['message']['content'] ?? '');
    }

    /**
     * 解析可用的聊天模型.
     *
     * @param int $modelId 模型配置 ID
     */
    private function resolveChatModel(int $modelId): ?AiModel
    {
        $model = $this->modelDao->get([
            'id'     => $modelId,
            'status' => AiModelEnum::STATUS_ENABLED,
        ]);
        if (! $model || trim((string) $model->model_id) === '') {
            return null;
        }

        return $model;
    }

    /**
     * 将毫秒超时转为 BaseCurl 秒级超时（至少 1 秒）.
     *
     * @param int $timeoutMs 超时毫秒
     */
    private function timeoutSeconds(int $timeoutMs): int
    {
        return max(1, (int) ceil($timeoutMs / 1000));
    }
}
