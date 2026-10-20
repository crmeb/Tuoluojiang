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

namespace App\Exceptions;

use Tuoluojiang\Open\exceptions\AdminException;
use Tuoluojiang\Open\exceptions\ApiException;
use Tuoluojiang\Open\exceptions\ApiRequestException;
use Tuoluojiang\Open\exceptions\AuthException;
use Tuoluojiang\Open\exceptions\EntException;
use Tuoluojiang\Open\exceptions\HttpServiceExceptions;
use Tuoluojiang\Open\exceptions\ServicesException;
use Tuoluojiang\Open\exceptions\UploadException;
use Tuoluojiang\Open\exceptions\WebOfficeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Predis\Connection\ConnectionException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        ValidationException::class,
        WebOfficeException::class,
        HttpServiceExceptions::class,
        ApiException::class,
        EntException::class,
        AdminException::class,
        ApiRequestException::class,
        ServicesException::class,
        UploadException::class,
        JWTException::class,
        AuthException::class,
        ConnectionException::class,
        AuthenticationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (\Throwable $e) {});
    }

    /**
     * @return \Illuminate\Http\Response|JsonResponse|mixed|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     */
    public function render($request, \Throwable $e)
    {
        $debug            = config('app.debug', false);
        $defaultMessage   = '系统开小差了';
        $defaultErrorCode = 400;
        $authExpiredCode  = 410003;
        $webOfficeCode    = 40007;
        $systemErrorMsg   = 'I\'m sorry, the system is out of order';
        $debugData        = $debug ? [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => mb_substr($e->getTraceAsString(), 0, 8000),
            // 'previous' => $e->getPrevious(),
        ] : [];
        $exceptionHandlers = [
            // 基础业务异常 - 统一400码
            'basicBusiness' => [
                'exceptions' => [
                    QueryException::class,
                    ModelNotFoundException::class,
                    MethodNotAllowedHttpException::class,
                    RouteNotFoundException::class,
                    ValidationException::class,
                    \ReflectionException::class,
                    \BadMethodCallException::class,
                    UnauthorizedHttpException::class,
                    ApiException::class,
                    EntryNotFoundException::class,
                ],
                'handler' => fn ($e) => $this->response(
                    $defaultErrorCode,
                    $e->getMessage() ?: $defaultMessage,
                    $debugData
                ),
            ],
            // 服务/上传类异常 - 使用自定义码或400
            'serviceUpload' => [
                'exceptions' => [
                    HttpServiceExceptions::class,
                    UploadException::class,
                    ApiRequestException::class,
                    AdminException::class,
                    ServicesException::class,
                    ConnectionException::class,
                    AuthException::class,
                    EntException::class,
                ],
                'handler' => fn ($e) => $this->response(
                    $e->getCode() ?: $defaultErrorCode,
                    $e->getMessage(),
                    $debugData
                ),
            ],
            // 权限/认证异常 - 403码
            'authorization' => [
                'exceptions' => [
                    AuthorizationException::class,
                    JWTException::class,
                ],
                'handler' => fn ($e) => $this->response(
                    $e->getCode() ?: 403,
                    $e->getMessage()
                ),
            ],
            // 登录状态失效 - 固定410003码
            'authentication' => [
                'exceptions' => [AuthenticationException::class],
                'handler'    => fn ($e) => $this->response(
                    $authExpiredCode,
                    $e->getMessage()
                ),
            ],
            // 在线办公异常 - 特殊返回格式
            'webOffice' => [
                'exceptions' => [WebOfficeException::class],
                'handler'    => fn ($e) => Response::json([
                    'code'    => $webOfficeCode,
                    'details' => $e->getMessage(),
                    'message' => 'CustomMsg',
                ]),
            ],

            // 传输异常
            'transport' => [
                'exceptions' => [TransportException::class],
                'handler'    => fn ($e) => $this->response(
                    $e->getCode(),
                    $e->getMessage() ?: $defaultMessage,
                    $debugData
                ),
            ],
        ];
        foreach ($exceptionHandlers as $handlerConfig) {
            foreach ($handlerConfig['exceptions'] as $exceptionClass) {
                if ($e instanceof $exceptionClass) {
                    return $handlerConfig['handler']($e);
                }
            }
        }
        if ($request->ajax()) {
            return $this->response(
                $e->getCode() ?: $defaultErrorCode,
                $e->getMessage(),
                $debugData
            );
        }
        // 通用Exception处理
        if ($e instanceof \Exception) {
            return $this->response(
                $e->getCode(),
                $e->getMessage() ?: $defaultMessage,
                $debugData
            );
        }
        // 未匹配的异常
        $this->report($e);
        // 调试模式下使用父类渲染，非调试模式返回统一错误
        return $debug
            ? parent::render($request, $e)
            : app('json')->httpStatus($defaultErrorCode)->fail($systemErrorMsg);
    }

    /**
     * 创建 response.
     * @param array $data
     * @param mixed $code
     * @param mixed $msg
     * @return mixed
     */
    protected function response($code, $msg, $data = [])
    {
        return app('json')->create(collect(['status' => $code, 'message' => $msg, 'data' => $data]));
    }
}
