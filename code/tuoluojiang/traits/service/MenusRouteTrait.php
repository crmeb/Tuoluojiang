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

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * 根据系统内置路由提取路由信息
 * Trait MenusRouteTrait.
 */
trait MenusRouteTrait
{
    /**
     * 菜单api字段名.
     * @var string
     */
    protected $menusApiFiled = 'api';

    /**
     * 菜单method字段.
     * @var string
     */
    protected $menusMethodField = 'method';

    /**
     * 过滤参数.
     * @var string[]
     */
    protected $option = [
        'name'   => '',
        'path'   => '',
        'method' => '',
    ];

    /**
     * 设置过滤参数.
     * @return $this
     */
    public function setOption(string $key, string $value)
    {
        if (in_array($key, array_keys($this->option))) {
            $this->option[$key] = $value;
        }
        return $this;
    }

    /**
     * 获取全部路由.
     * @param mixed $filter
     * @return array
     */
    public function getRoutes($filter)
    {
        $routes = collect(Route::getRoutes()->getRoutes())->map(function ($route) {
            return $this->getRouteInformation($route);
        })->filter($filter)->all();
        $this->option = ['name' => '', 'path' => '', 'method' => ''];
        return $routes;
    }

    /**
     * 获取当前菜单不存在的接口路由.
     * @return array
     */
    public function diffAssoc(array $menus, ?callable $filter = null)
    {
        $routes    = collect($this->getRoutes($filter));
        $routeList = $routes->map(function ($route) {
            return $route['uri'] . '|' . $route['method'];
        });

        $menusList = collect($menus)->map(function ($menu) {
            return $menu[$this->menusApiFiled] . '|' . $menu[$this->menusMethodField];
        });

        $diffAssoc = $routeList->diff($menusList->all());
        return $routes->filter(function ($route) use ($diffAssoc) {
            $api = $route['uri'] . '|' . $route['method'];
            $res = $diffAssoc->filter(function ($value) use ($api) {
                if ($value === $api) {
                    return true;
                }
            });
            return $res->isNotEmpty();
        })->all();
    }

    /**
     * 提取过滤参数.
     * @return string
     */
    protected function option(string $key)
    {
        return $this->option[$key];
    }

    /**
     * 获取单个路由数据的组合.
     * @return array|void
     */
    protected function getRouteInformation(\Illuminate\Routing\Route $route)
    {
        return $this->filterRoute([
            'method' => $route->methods()[0] ?? 'GET',
            'uri'    => $route->uri(),
            'name'   => $route->getName(),
            'action' => ltrim($route->getActionName(), '\\'),
        ]);
    }

    /**
     * 提取过滤路由.
     * @return array|void
     */
    protected function filterRoute(array $route)
    {
        if (($this->option('name') && ! Str::contains($route['name'], $this->option('name')))
            || $this->option('path') && ! Str::contains($route['uri'], $this->option('path'))
            || $this->option('method') && ! Str::contains($route['method'], strtoupper($this->option('method')))) {
            return;
        }

        return $route;
    }
}
