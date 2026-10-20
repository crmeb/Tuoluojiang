<?php

namespace App\Http\Dao\Work;

use App\Http\Model\Work\WorkMemberOther;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;

/**
 * 其他成员信息
 * Class WorkMemberOtherDao
 */
class WorkMemberOtherDao extends BaseDao
{
    use BatchSearchTrait;

    protected function setModel()
    {
        return WorkMemberOther::class;
    }
}
