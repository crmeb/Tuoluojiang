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

namespace Tuoluojiang\Open\services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Class BaseManager.
 */
abstract class BaseManager
{
    /**
     * 驱动的命名空间.
     * @var null
     */
    protected $namespace;

    /**
     * 配置.
     * @var null
     */
    protected $configFile;

    /**
     * 配置.
     * @var array
     */
    protected $config = [];

    /**
     * 驱动.
     * @var array
     */
    protected $drivers = [];

    /**
     * 驱动类型.
     * @var null
     */
    protected $name;

    /**
     * 容器的共享实例.
     * @var array
     */
    protected $instances = [];

    /**
     * BaseManager constructor.
     * @param array $config 配置
     * @param null|mixed $name
     */
    public function __construct($name = null, array $config = [])
    {
        $type = null;
        if (is_array($name)) {
            $config = $name;
            $name   = null;
        }

        if (is_int($name)) {
            $type = $name;
            $name = null;
        }

        if ($name) {
            $this->name = $name;
        }
        if ($type && is_null($this->name)) {
            $this->setHandleType((int) $type - 1);
        }
        $this->config = $config;
    }

    /**
     * 动态调用.
     * @param mixed $method
     * @param mixed $arguments
     */
    public function __call($method, $arguments)
    {
        return $this->driver()->{$method}(...$arguments);
    }

    /**
     * 提取配置文件名.
     * @return $this
     */
    protected function getConfigFile()
    {
        if (is_null($this->configFile)) {
            $this->configFile = strtolower((new \ReflectionClass($this))->getShortName());
        }
        return $this;
    }

    /**
     * 设置文件句柄.
     */
    protected function setHandleType(int $type)
    {
        $this->getConfigFile();
        $stores = array_keys(Config::get($this->configFile . '.stores', []));
        $name   = $stores[$type] ?? null;
        if (! $name) {
            throw new \RuntimeException($this->configFile . ' type is not used');
        }
        $this->name = $name;
    }

    /**
     * 设置默认句柄.
     * @return mixed
     */
    abstract protected function getDefaultDriver();

    /**
     * 获取驱动实例.
     * @return mixed
     */
    protected function driver(?string $name = null)
    {
        $name = $name ?: $this->name;
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->drivers[$name] = $this->getDriver($name);
    }

    /**
     * 获取驱动实例.
     * @return mixed
     */
    protected function getDriver(string $name)
    {
        return $this->drivers[$name] ?? $this->createDriver($name);
    }

    /**
     * 获取驱动类型.
     * @return mixed
     */
    protected function resolveType(string $name)
    {
        return $name;
    }

    /**
     * 创建驱动.
     *
     * @return mixed
     */
    protected function createDriver(string $name)
    {
        $type = $this->resolveType($name);

        $method = 'create' . Str::studly($type) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->{$method}($name);
        }

        $class      = $this->resolveClass($type);
        $this->name = $type;
        return $this->invokeClass($class);
    }

    /**
     * 获取驱动类.
     */
    protected function resolveClass(string $type): string
    {
        if ($this->namespace || strpos($type, '\\') !== false) {
            $class = strpos($type, '\\') !== false ? $type : $this->namespace . Str::studly($type);
            if (class_exists($class)) {
                return $class;
            }
        }

        throw new \InvalidArgumentException("Driver [{$type}] not supported.");
    }

    /**
     * 实例化类.
     * @param mixed $class
     * @return mixed
     */
    protected function invokeClass($class)
    {
        if (! class_exists($class)) {
            throw new \RuntimeException('class not exists: ' . $class);
        }
        $this->getConfigFile();
        $handle       = $this->make($class, [$this->name, $this->config, $this->configFile]);
        $this->config = [];
        return $handle;
    }

    /**
     * @param mixed $abstract
     * @return mixed
     */
    protected function make($abstract, array $parameters = [], bool $newInstance = false)
    {
        if (isset($this->instances[$abstract]) && ! $newInstance) {
            return $this->instances[$abstract];
        }
        $instance = new $abstract(...$parameters);

        $this->instances[$abstract] = $instance;

        return $instance;
    }
}
