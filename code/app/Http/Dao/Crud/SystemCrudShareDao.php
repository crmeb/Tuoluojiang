<?php

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudShare;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 分享数据共享记录
 */
class SystemCrudShareDao extends BaseDao
{
    use ListSearchTrait;

    /**
     * @return string
     */
    protected function setModel()
    {
        return SystemCrudShare::class;
    }
}
