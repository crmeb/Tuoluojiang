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

namespace Tuoluojiang\Open\traits;

/**
 * Class BaseError.
 */
trait ErrorTrait
{
    /**
     * 错误信息.
     * @var string
     */
    protected $error;

    /**
     * 获取错误信息.
     * @return string
     */
    public function getError()
    {
        $error       = $this->error;
        $this->error = null;
        return $error;
    }

    /**
     * 设置错误信息.
     * @return bool
     */
    protected function setError(?string $error = null)
    {
        $this->error = $error ?: '未知错误';
        return false;
    }
}
