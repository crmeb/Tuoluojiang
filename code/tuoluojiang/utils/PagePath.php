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

namespace Tuoluojiang\Open\utils;

/**
 * 页面路径
 * Class PagePath.
 * @method string assess(array $data = []) 获取我的绩效路径
 * @method string daily(array $data = []) 获取我的日标路径
 */
class PagePath
{
    // 我的日报
    public const DAILY_PATH = '/user/work/daily?event={$event}';

    // 我的绩效
    public const ASSESS_PATH = '/user/work/assessment?id={$id}&type={$type}';

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * PagePath constructor.
     */
    public function __construct()
    {
        $this->prefix = get_roule_mobu();
    }

    /**
     * @param mixed $name
     * @param mixed $arguments
     * @return array|string|string[]
     */
    public function __call($name, $arguments)
    {
        $path = $this->resolvAction($name);
        if (! $path) {
            throw new \RuntimeException('方法不存在');
        }
        return $this->resolvPath($path, ...$arguments);
    }

    /**
     * @return $this
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 处理路径.
     * @return array|string|string[]
     */
    public function resolvPath(string $path, array $data = [])
    {
        $replace     = [];
        $replaceData = [];
        foreach ($data as $k => $v) {
            $replace[]     = '{$' . $k . '}';
            $replaceData[] = $v;
        }
        return $this->prefix . ($replace && $replaceData ? str_replace($replace, $replaceData, $path) : $path);
    }

    /**
     * @return string
     */
    public function resolvAction(string $action)
    {
        $objClass = new \ReflectionClass(__CLASS__);
        $arrConst = $objClass->getConstants();
        foreach ($arrConst as $k => $v) {
            if (strtoupper($action) . '_PATH' === $k) {
                return $v;
            }
        }
    }
}
