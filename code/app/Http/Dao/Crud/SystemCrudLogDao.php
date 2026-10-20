<?php

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudLog;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 日志
 */
class SystemCrudLogDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * @return string
     */
    protected function setModel()
    {
        return SystemCrudLog::class;
    }
}
