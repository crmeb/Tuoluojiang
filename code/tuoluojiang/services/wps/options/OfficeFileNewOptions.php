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

/**
 * Class OfficeFileNewOptions.
 */
class OfficeFileNewOptions implements OptionsInterface
{
    use OptionsTrait;

    /**
     * 访问地址
     * @var string
     */
    public $redirectUrl;

    /**
     * 用户ID.
     * @var string
     */
    public $userId;

    /**
     * OfficeFileNewOptions constructor.
     */
    public function __construct(?string $redirectUrl = null, ?string $userId = null)
    {
        $this->redirectUrl = $redirectUrl;
        $this->userId      = $userId;
    }
}
