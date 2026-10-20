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

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 过滤参数
 * Class FilterStrings.
 */
class FilterStrings extends TransformsRequest
{
    /**
     * 过滤方法名.
     * @var string[]
     */
    protected $filter = ['addslashes', 'trim', 'e'];

    /**
     * 排除那些值
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
        'password_confirm',
        'confirm_password',
        'introduce',
        'duty',
        'content',
        'standard',
        'firewall_content'
    ];

    /**
     * 过滤form.
     * @param string $key
     * @param mixed $value
     * @return array|mixed
     */
    protected function transform($key, $value)
    {
        $res = array_find($this->except, function ($value) use ($key) {
            return $value === (str_contains($key, '.') ? explode('.', $key)[0] ?? null : $key);
        });

        if (in_array($res, $this->except, true)) {
            return $value;
        }

        if (str_contains(request()->path(), 'crud')) {
            return $value;
        }
        if (is_numeric($value) || empty($value)) {
            return $value;
        }
        if (!is_array($value)) {
            foreach ($this->filter as $closure) {
                if (function_exists($closure) && !is_bool($value) && $value !== '') {
                    $value = $closure((string)$value);
                }
            }
        }

        return $this->filterArrayValues($value);
    }

    /**
     * 过滤数据
     * @param $array
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function filterArrayValues($array)
    {
        $firewallSwitch = (int)sys_config('firewall_switch');
        if (!$firewallSwitch) {
            return $array;
        }

        if (is_array($array)) {
            $result = [];
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    // 如果值是数组，并且不在不过滤变量名里面，递归调用 filterArrayValues，否则直接赋值
                    $result[$key] = in_array($key, $this->except) ? $value : $this->filterArrayValues($value);
                } else {
                    if (in_array($key, $this->except) || is_int($value) || is_null($value)) {
                        $result[$key] = $value;
                    } else {
                        // 如果值是字符串，过滤特殊字符
                        $result[$key] = $this->filterStr($firewallSwitch, $value);
                    }
                }
            }
            return $result;
        } else {
            return $this->filterStr($firewallSwitch, $array);
        }
    }

    /**
     * 过滤字符串
     * @param int $firewallSwitch
     * @param $str
     * @return array|mixed|string|string[]|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function filterStr(int $firewallSwitch, $str)
    {
        try {
            $rules = (array)sys_config('firewall_content');
            if ($firewallSwitch === 1) {
                foreach ($rules as $item) {
                    if (preg_match($item, $str)) {
                        throw new \Exception('接口请求失败：非法操作！');
                    }
                }
            }
            if (filter_var($str, FILTER_VALIDATE_URL)) {
                $url = parse_url($str);
                if (!isset($url['scheme']))
                    return $str;
                $host = $url['scheme'] . '://' . $url['host'];
                $str = $host . preg_replace($rules, '', str_replace($host, '', $str));
            } else {
                $str = preg_replace($rules, '', $str);
            }
        } catch (\Throwable) {
        }
        return $str;
    }

}
