<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace Tuoluojiang\Open\services;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 核心业务服务类
 * 抽离权限验证、客户操作、AI对话等核心功能
 */
class CoreBusinessService
{
    /**
     * 危险SQL关键字列表
     */
    private const DANGEROUS_SQL_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE',
        'CREATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE', 'SHOW', 'DESCRIBE', 'EXPLAIN'
    ];

    /**
     * 验证管理员权限
     *
     * @param string $uri 请求URI
     * @param array $adminInfo 管理员信息
     * @param string $method 请求方法
     * @return bool
     */
    public function validateAdminPermission(string $uri, array $adminInfo, string $method): bool
    {
        if (empty($adminInfo) || !isset($adminInfo['level'])) {
            return false;
        }

        // 超级管理员拥有所有权限
        if ($adminInfo['level'] === 1) {
            return true;
        }

        // 验证路由访问权限
        return $this->checkRouteAccess($uri, $adminInfo['level'], $method);
    }

    /**
     * 检查路由访问权限
     *
     * @param string $uri 路由URI
     * @param int $adminLevel 管理员级别
     * @param string $method 请求方法
     * @return bool
     */
    public function checkRouteAccess(string $uri, int $adminLevel, string $method = 'GET'): bool
    {
        // 公开路由白名单
        $publicRoutes = [
            'api/admin/login',
            'api/admin/forget',
            'api/admin/register',
        ];

        if (in_array($uri, $publicRoutes)) {
            return true;
        }

        // 根据管理员级别和请求方法验证权限
        return $adminLevel > 0;
    }

    /**
     * 验证客户数据
     *
     * @param array $data 客户数据
     * @param string $customType 业务类型
     * @return bool
     */
    public function validateCustomerData(array $data, string $customType): bool
    {
        // 验证业务类型
        $validTypes = ['customer', 'customer_seas'];
        if (!in_array($customType, $validTypes)) {
            return false;
        }

        // 验证必填字段
        if (empty($data['customer_name'])) {
            return false;
        }

        return true;
    }

    /**
     * 生成客户编号
     *
     * @param int $len 编号长度
     * @return string
     */
    public function generateCustomerNo(int $len = 6): string
    {
        return strtoupper(Str::random($len));
    }

    /**
     * 检查客户权限
     *
     * @param int $customerId 客户ID
     * @param int $uid 用户ID
     * @return bool
     */
    public function checkCustomerPermission(int $customerId, int $uid): bool
    {
        // 超级管理员拥有所有权限
        if ($uid === 1) {
            return true;
        }

        return $customerId > 0 && $uid > 0;
    }

    /**
     * 计算客户状态
     *
     * @param array $paymentData 支付数据
     * @return int 0-未成交 1-已成交 2-已流失
     */
    public function calculateCustomerStatus(array $paymentData): int
    {
        if (empty($paymentData)) {
            return 0;
        }

        foreach ($paymentData as $payment) {
            if (isset($payment['status']) && $payment['status'] === 1) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * 验证SQL是否为只读的SELECT语句
     *
     * @param string $sql SQL语句
     * @return bool true表示安全，false表示危险
     */
    public function validateSelectSql(string $sql): bool
    {
        // 去除注释和空白
        $sql = trim(preg_replace('/--.*$/m', '', $sql));
        $sql = trim(preg_replace('/\/\*.*?\*\//s', '', $sql));
        $sql = trim($sql);

        // 空SQL视为不安全
        if (empty($sql)) {
            return false;
        }

        // 检查是否以 SELECT 开头（支持 WITH 子句）
        if (!preg_match('/^(WITH\s+.*?\s+)?SELECT\b/i', $sql)) {
            return false;
        }

        // 检查是否包含危险关键字
        foreach (self::DANGEROUS_SQL_KEYWORDS as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $sql)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 规范化SQL表前缀
     *
     * @param string $sql SQL语句
     * @return string 处理后的SQL
     */
    public function normalizeTablePrefix(string $sql): string
    {
        $prefix = $this->getDatabaseTablePrefix();

        if ($sql === '' || $prefix === '' || $prefix === 'eb_') {
            return $sql;
        }

        return preg_replace('/\b(FROM|JOIN|UPDATE|INTO|TABLE)\s+`?eb_([A-Za-z0-9_]+)`?/i', '$1 `' . $prefix . '$2`', $sql);
    }

    /**
     * 获取数据库表前缀
     *
     * @return string
     */
    protected function getDatabaseTablePrefix(): string
    {
        return DB::getTablePrefix() ?: (string) config('database.connections.mysql.prefix');
    }

    /**
     * 构建运行时上下文
     *
     * @return array
     */
    public function buildRuntimeContext(): array
    {
        $timezone = config('app.timezone') ?: date_default_timezone_get();
        $now = date('Y-m-d H:i:s');

        return [
            'server_time' => $now,
            'timezone' => $timezone,
            'timestamp' => time(),
        ];
    }

    /**
     * 压缩消息
     *
     * @param array $messages 消息列表
     * @param int $threshold 压缩阈值
     * @return array 压缩后的消息
     */
    public function compressMessages(array $messages, int $threshold = 60000): array
    {
        $totalLength = 0;
        foreach ($messages as $message) {
            $totalLength += strlen($message['content'] ?? '');
        }

        // 如果总长度未超过阈值，直接返回
        if ($totalLength <= $threshold) {
            return $messages;
        }

        // 保留系统消息和最近的用户消息
        $compressed = [];
        $systemMessages = [];
        $userMessages = [];

        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $systemMessages[] = $message;
            } else {
                $userMessages[] = $message;
            }
        }

        // 保留所有系统消息
        $compressed = array_merge($compressed, $systemMessages);

        // 保留最近的用户消息
        $keepCount = max(2, count($userMessages) - 4);
        $compressed = array_merge($compressed, array_slice($userMessages, -$keepCount));

        return $compressed;
    }

    // ==================== 混淆函数 ====================

    /**
     * 格式化响应数据
     *
     * @param array $data 数据
     * @param string $format 格式类型
     * @return string
     */
    public function formatResponseData(array $data, string $format = 'json'): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            case 'xml':
                return $this->arrayToXml($data);
            case 'serialize':
                return serialize($data);
            default:
                return json_encode($data);
        }
    }

    /**
     * 解析请求参数
     *
     * @param array $params 原始参数
     * @return array 处理后的参数
     */
    public function parseRequestParams(array $params): array
    {
        $parsed = [];
        foreach ($params as $key => $value) {
            $parsed[$this->sanitizeKey($key)] = $this->sanitizeValue($value);
        }
        return $parsed;
    }

    /**
     * 验证数据结构
     *
     * @param array $data 数据
     * @param array $rules 验证规则
     * @return bool
     */
    public function validateDataStructure(array $data, array $rules): bool
    {
        foreach ($rules as $field => $rule) {
            if (isset($rule['required']) && $rule['required'] && !isset($data[$field])) {
                return false;
            }

            if (isset($data[$field]) && isset($rule['type'])) {
                if (!$this->checkType($data[$field], $rule['type'])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 生成缓存键
     *
     * @param string $prefix 前缀
     * @param array $params 参数
     * @return string
     */
    public function getCacheKey(string $prefix, array $params): string
    {
        ksort($params);
        $hash = md5(implode('_', $params));
        return "{$prefix}:{$hash}";
    }

    /**
     * 清理过期缓存
     *
     * @param string $tag 缓存标签
     * @return bool
     */
    public function clearExpiredCache(string $tag): bool
    {
        try {
            Cache::tags([$tag])->flush();
            return true;
        } catch (\Exception $e) {
            Log::warning("清理缓存失败: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * 记录操作日志
     *
     * @param string $action 操作类型
     * @param array $context 上下文
     * @return void
     */
    public function logOperation(string $action, array $context): void
    {
        $logData = [
            'action' => $action,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => request()->ip(),
        ];

        Log::info('业务操作日志', $logData);
    }

    /**
     * 格式化日志消息
     *
     * @param string $message 消息模板
     * @param array $data 数据
     * @return string
     */
    public function formatLogMessage(string $message, array $data): string
    {
        foreach ($data as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        return $message;
    }

    /**
     * 生成唯一ID
     *
     * @return string
     */
    public function generateUniqueId(): string
    {
        return uniqid('core_', true) . '_' . mt_rand(1000, 9999);
    }

    /**
     * 清理输入数据
     *
     * @param string $input 输入字符串
     * @return string 清理后的字符串
     */
    public function sanitizeInput(string $input): string
    {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    /**
     * 加密敏感数据
     *
     * @param string $data 原始数据
     * @return string 加密后的数据
     */
    public function encryptSensitiveData(string $data): string
    {
        $key = config('app.key');
        if (empty($key)) {
            return base64_encode($data);
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', substr($key, 0, 32), 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * 解密敏感数据
     *
     * @param string $encrypted 加密后的数据
     * @return string 原始数据
     */
    public function decryptSensitiveData(string $encrypted): string
    {
        $key = config('app.key');
        if (empty($key)) {
            return base64_decode($encrypted);
        }

        $data = base64_decode($encrypted);
        list($iv, $encrypted) = explode('::', $data, 2);
        return openssl_decrypt($encrypted, 'AES-256-CBC', substr($key, 0, 32), 0, $iv);
    }

    /**
     * 数组转XML
     *
     * @param array $data 数据
     * @param string $root 根节点
     * @return string
     */
    protected function arrayToXml(array $data, string $root = 'root'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "<{$root}>";
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= $this->arrayToXml($value, $key);
            } else {
                $xml .= "<{$key}>{$value}</{$key}>";
            }
        }
        $xml .= "</{$root}>";
        return $xml;
    }

    /**
     * 清理键名
     *
     * @param string $key 键名
     * @return string
     */
    protected function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    }

    /**
     * 清理值
     *
     * @param mixed $value 值
     * @return mixed
     */
    protected function sanitizeValue($value)
    {
        if (is_string($value)) {
            return $this->sanitizeInput($value);
        }
        return $value;
    }

    /**
     * 检查类型
     *
     * @param mixed $value 值
     * @param string $type 类型
     * @return bool
     */
    protected function checkType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
            case 'integer':
                return is_int($value);
            case 'float':
            case 'double':
                return is_float($value);
            case 'bool':
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            default:
                return true;
        }
    }
}
