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

namespace Tuoluojiang\Open\traits\service;

use App\Http\Service\BaseService;

/**
 * path service
 * Trait PathServiceTrait.
 * @mixin BaseService
 */
trait PathServiceTrait
{
    /**
     * 更新path字段.
     * @param array|string[] $field
     * @param mixed $path
     */
    public function updatePathStr(int $id, $path, array $newPath, array $field = ['path'])
    {
        $strPath    = $this->getPathValue($path);
        $strNewPath = $this->getPathValue($newPath);
        if ($strPath != $strNewPath) {
            $this->dao->setFields($field)->updatePath($id, $strPath, $strNewPath);
        }
    }

    /**
     * 获取path值
     * @param mixed $path
     * @return array|string
     */
    public function getPathValue($path, bool $str = true)
    {
        if ($str) {
            return is_string($path) ? $path : (is_array($path) ? '/' . implode('/', $path) : '');
        }
        return is_string($path) ? array_merge(array_filter(explode('/', $path))) : (is_array($path) ? $path : []);
    }
}
