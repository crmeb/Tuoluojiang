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

namespace Tuoluojiang\Open\exceptions;

use Tuoluojiang\Open\services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ApiRequestException.
 */
class ApiRequestException extends \RuntimeException
{
    /**
     * @var int
     */
    protected $statusCode;

    public function __construct($message = '', $code = 0, ?\Throwable $previous = null, int $statusCode = 200)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
    }

    /**
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function render()
    {
        /** @var ApiResponseService $response */
        $response = app()->get(ApiResponseService::class);
        return $response->httpStatus($this->code)->make($this->code, $this->getMessage(), $this->getTrace());
    }
}
