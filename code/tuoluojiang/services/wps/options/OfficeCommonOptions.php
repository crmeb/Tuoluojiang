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

namespace Tuoluojiang\Open\services\wps\options;

use Tuoluojiang\Open\interfaces\OptionsInterface;
use Tuoluojiang\Open\traits\OptionsTrait;
use Illuminate\Support\Str;

/**
 * 公用
 * Class OfficeCommonOptions.
 */
class OfficeCommonOptions implements OptionsInterface
{
    use OptionsTrait;

    /**
     * @var array
     */
    public $common = [];

    /**
     * OfficeCommonOptions constructor.
     */
    public function __construct(array $common = [])
    {
        $this->common = $common;
    }

    /**
     * 设置属性.
     * @param mixed $name
     * @param mixed $value
     * @return $this
     */
    public function __set($name, $value)
    {
        $this->common[$name] = $value;
        return $this;
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->common as $key => $value) {
            $data[Str::snake($key)] = $value;
        }
        return $data;
    }
}
