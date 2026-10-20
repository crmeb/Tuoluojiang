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

use App\Constants\CacheEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 分表
 * Class SplitTable.
 */
class SplitTable
{
    /**
     * 前缀
     * @var string
     */
    protected $prefix;

    protected $database;

    /**
     * 数据表.
     * @var \array[][]
     */
    protected $tableField = [
        'enterprise_log' => "CREATE TABLE `[:enterprise_log]` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志自增id',
  `enterprise_id` int(11) NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `enterprise_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员姓名',
  `path` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `method` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '访问方式',
  `event_name` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '行为',
  `type` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '类型',
  `terminal` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '访问端',
  `last_ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL COMMENT '访问ip',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enterprise_id` (`enterprise_id`),
  KEY `enterprise_name` (`enterprise_name`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
    ];

    public function __construct()
    {
        $connection     = app()->config->get('database.default');
        $this->prefix   = app()->config->get('database.connections.' . $connection . '.prefix', 'eb_');
        $this->database = app()->config->get('database.connections.' . $connection . '.database');
    }

    /**
     * 检测数据库是否存在.
     * @return bool
     */
    public function checkTable(string $table)
    {
        if (Cache::tags([CacheEnum::TAG_OTHER])->get('checkTable_' . $table)) {
            return true;
        }
        $res = DB::select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA= :database AND TABLE_NAME=:tablename', ['database' => $this->database, 'tablename' => $table]);
        $res = (bool) count($res);
        if ($res) {
            Cache::add('checkTable_' . $table, true);
        }
        return $res;
    }

    /**
     * 获取数据库表名.
     * @return string
     * @throws ValidationException
     */
    public function getTalbeName(string $table, string $name)
    {
        $talbeName = $this->prefix . $table . '_' . $name;
        if ($this->checkTable($talbeName)) {
            return $talbeName;
        }
        $sql = $this->tableField[$table] ?? null;
        if (! $sql) {
            throw new ValidationException('表不存在');
        }
        $sql = str_replace('[:' . $table . ']', $talbeName, $sql);
        $res = DB::statement($sql);
        if (! $res) {
            throw new ValidationException(__('Failed to create database'));
        }
        return $talbeName;
    }
}
