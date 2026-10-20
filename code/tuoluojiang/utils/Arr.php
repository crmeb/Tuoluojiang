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

use Tuoluojiang\Open\utils\Str as UtilsStr;
use Illuminate\Support\Str;

class Arr
{
    /**
     * @param string $mentoh
     * @param mixed $instance
     * @return array
     */
    public static function more($instance, array $params, ?bool $suffix = null, $mentoh = 'post')
    {
        $p = [];
        $i = 0;
        foreach ($params as $param) {
            if (! is_array($param)) {
                $p[$suffix == true ? $i++ : $param] = $instance->{$mentoh}($param);
            } else {
                if (! isset($param[1])) {
                    $param[1] = null;
                }
                if (! isset($param[2])) {
                    $param[2] = '';
                }
                $value                                                                               = $instance->{$mentoh}($param[0], $param[1]);
                $p[$suffix == true ? $i++ : (isset($param[2]) && $param[2] ? $param[2] : $param[0])] = is_null($value) ? $param[1] : $value;
            }
        }
        return $p;
    }

    /**
     * 设置.
     * @return array
     */
    public static function setDefaultField(array $data, array $field)
    {
        foreach ($field as $k => $v) {
            if (! isset($data[$k])) {
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * 过滤字段.
     * @return array
     */
    public static function filterValue(array $data)
    {
        foreach ($data as &$item) {
            if (is_array($item)) {
                $item = self::filterValue($item);
            } else {
                $item = UtilsStr::filterValue($item);
            }
        }
        return $data;
    }

    /**
     * 合并数组并去重复，去空值
     * @param mixed ...$data
     * @return array
     */
    public static function merge(...$data)
    {
        $dataArr = func_get_args();
        $merge   = [];
        foreach ($dataArr as $item) {
            $merge = array_merge($merge, array_filter($item, function ($val) {
                return (bool) $val;
            }));
        }
        return array_merge(array_unique($merge));
    }

    /**
     * 将键名由驼峰转下划线
     */
    public static function snake(mixed $input): array|string
    {
        if (is_array($input)) {
            $result = [];
            foreach ($input as $key => $item) {
                $result[Str::snake($key)] = is_array($item) ? self::snake($item) : $item;
            }
            return $result;
        }
        if (is_string($input)) {
            return Str::snake($input);
        }
        return $input;
    }
}
