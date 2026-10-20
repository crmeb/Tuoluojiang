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

namespace Tuoluojiang\Open\services\ai;

use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Tuoluojiang\Open\services\HttpService;

class BaseCurl
{
    protected string $baeUrl = '';

    protected string $key = '';

    protected int $timeout = 120;

    protected array $body = [];

    protected array $query = [];

    protected string $method = 'POST';

    /**
     * @var array|string[]
     */
    protected array $header = [
        'Content-Type: application/json',
    ];

    public function __construct(string $key = '', string $baeUrl = '', string $auth = 'bearer')
    {
        $this->baeUrl   = $baeUrl;
        $this->key      = $key;
        if ($auth === 'api_key') {
            $this->header[] = 'api-key: ' . $this->key;
        } else {
            $this->header[] = 'Authorization: Bearer ' . $this->key;
        }
    }

    /**
     * @return $this
     */
    public function setBody(BaseOption $option)
    {
        if ($option->baseUrl) {
            $this->baeUrl = $option->baseUrl;
        }
        $this->body = $option->toArray();
        return $this;
    }

    /**
     * @return $this
     */
    public function setBaeUrl(string $baeUrl)
    {
        $this->baeUrl = $baeUrl;
        return $this;
    }

    /**
     * @return array|string
     */
    public function send(string $url = '', string $method = 'POST', array $body = [], array $query = [], array $header = [], int $timeout = 120)
    {
        $url = $this->baeUrl . $url . ($query ? '?' . http_build_query($query) : '');

        $header = array_merge($this->header, $header);

        [$httpCode, $content] = (new HttpService())->setHeader($header)->requests($url, $method, json_encode($body ?: $this->body), true, $timeout);

        if (! $httpCode) {
            throw new HttpServiceExceptions(__('request was aborted'));
        }

        $respones = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpServiceExceptions(__('Failed to parse JSON: :error', ['error' => json_last_error_msg()]));
        }

        return $respones;
    }

    /**
     * @return string
     */
    public function stream(string $url = '', string $method = 'POST', array $body = [], array $query = [], ?callable $stream = null, array $header = [], ?string $uuid = null, ?string $tagName = null, int $timeout = 120)
    {
        $url = $this->baeUrl . $url . ($query ? '?' . http_build_query($query) : '');

        $header = array_merge($this->header, $header);

        return (new HttpService())->setHeader($header)->stream($url, $method, json_encode($body ?: $this->body), $stream, true, $uuid, $tagName, $timeout);
    }
}
