<?php

namespace App\Http\Dao\Open;

use App\Http\Model\Open\OpenapiRule;
use App\Http\Dao\BaseDao;

class OpenapiRuleDao extends BaseDao
{

    protected function setModel()
    {
        return OpenapiRule::class;
    }
}
