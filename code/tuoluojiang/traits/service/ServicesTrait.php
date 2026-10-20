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

namespace Tuoluojiang\Open\traits\service;

use App\Http\Model\BaseModel;
use Illuminate\Support\Facades\DB;

/**
 * Services IDE辅助
 * Trait ServicesTrait.
 * @mixin  BaseModel
 */
trait ServicesTrait
{
    protected $databaseListen = [];

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null|string $sort
     * @return mixed
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        $this->runListen(__FUNCTION__);
        [$page, $limit] = $this->getPageValue();
        if (isset($where['cate_id']) && is_array($where['cate_id'])) {
            $where['cate_id'] = array_map(function ($item) {
                return str_replace(['[', ']'], '', $item);
            }, $where['cate_id']);
        }
        $list = $this->dao->getList($where, $field, $page, $limit, $sort, $with);

        $count = $this->dao->count($where);
        return $this->listData($list, $count);
    }

    /**
     * 显示隐藏修改.
     * @param mixed $id
     * @return mixed
     */
    public function showUpdate($id, array $data)
    {
        $this->runListen(__FUNCTION__);
        return $this->dao->update($id, $data);
    }

    /**
     * SQL调试兼容入口.
     */
    public function dumpSql()
    {
        if (env('APP_DEBUG')) {
            DB::listen(function ($QueryExecuted) {
                $bindings = $QueryExecuted->connection->prepareBindings($QueryExecuted->bindings);
                $sql      = str_replace('%', '$', $QueryExecuted->sql);
                $sql      = str_replace('?', '%s', $sql);
                dump(vsprintf($sql, $bindings));
            });
        }
    }

    /**
     * 投放事件.
     */
    protected function listen(string $name, \Closure $callback)
    {
        $this->databaseListen[$name] = $callback;
    }

    /**
     * 监听sql.
     * @param callable|string $name
     */
    protected function runListen($name)
    {
        if (env('APP_DEBUG')) {
            if (is_string($name) && isset($this->databaseListen[$name])) {
                DB::listen($this->databaseListen[$name]);
            } elseif ($name instanceof \Closure) {
                DB::listen($name);
            }
        }
    }
}
