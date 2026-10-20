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

namespace App\Http\Requests;

use Tuoluojiang\Open\exceptions\ApiRequestException;
use Tuoluojiang\Open\traits\RequestHelpTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request基类
 * Class BaseRequest.
 */
abstract class BaseRequest extends FormRequest
{
    use RequestHelpTrait;

    /**
     * 程序自定义业务错误码
     *
     * @var int
     */
    protected $code = 400;

    /**
     * http状态码
     *
     * @var int
     */
    protected $statusCode = 200;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }

    public function messages()
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ApiRequestException(
            $validator->errors()->first(),
            $this->code,
            null,
            $this->statusCode
        );
    }

    /**
     * 设置request.
     * @return $this
     */
    protected function request()
    {
        return $this;
    }
}
