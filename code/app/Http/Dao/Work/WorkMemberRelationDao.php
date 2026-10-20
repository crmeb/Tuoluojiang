<?php

namespace App\Http\Dao\Work;

use App\Http\Model\Work\WorkMemberRelation;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\BatchSearchTrait;

/**
 * 成员关系
 * Class WorkMemberRelationDao
 */
class WorkMemberRelationDao extends BaseDao
{
    use BatchSearchTrait;


    protected function setModel()
    {
        return WorkMemberRelation::class;
    }
}
