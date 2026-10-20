<?php

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudDataShare;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * Class SystemCrudDataShareDao
 */
class SystemCrudDataShareDao extends BaseDao
{
    use ListSearchTrait;

    protected function setModel()
    {
        return SystemCrudDataShare::class;
    }
}
