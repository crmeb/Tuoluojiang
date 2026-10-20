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

namespace Tuoluojiang\Open\services\wechat;

use Illuminate\Support\Collection;

/**
 * 微信错误
 * Class WechatResponse.
 */
class WechatResponse extends Collection
{
    protected \Throwable $e;

    /**
     * 是否抛出默认错误.
     */
    protected bool $error = true;

    /**
     * 数据转换.
     * @var array
     */
    protected $items = [];

    /**
     * WechatResponse constructor.
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = is_object($items) && method_exists($items, 'toArray') ? $items->toArray() : $items;
        $this->wechatError();
    }

    /**
     * 错误统一处理.
     */
    public function wechatError()
    {
        if (! $this->error) {
            return;
        }
        if (isset($this->items['errcode']) && $this->items['errcode'] !== 0) {
            throw new WechatException(
                ErrorMessage::getWorkMessage(
                    $this->items['errcode'] ?? 0,
                    $this->items['errmsg'] ?? null
                )
            );
        }
    }

    /**
     * @return $this
     */
    public function serError(bool $boole)
    {
        $this->error = $boole;
        return $this;
    }

    /**
     * 正确处理.
     * @return $this
     */
    public function then(callable $then, ?bool $error = null)
    {
        $error = $error || $this->error;
        if ($this->items['errcode'] !== 0 && $error) {
            throw new WechatException($this->items['errmsg']);
        }
        try {
            $this->response = $then($this->items);
        } catch (\Throwable $e) {
            $this->e = $e;
        }
        return $this;
    }

    /**
     * 异常处理.
     * @return $this
     */
    public function catch(callable $catch)
    {
        if (! $this->e) {
            $this->e = new WechatException('success');
        }

        $catch($this->e, $this->items);

        return $this;
    }

    /**
     * 获取返回值
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }
}
