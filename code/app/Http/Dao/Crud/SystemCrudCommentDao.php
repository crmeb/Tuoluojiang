<?php

namespace App\Http\Dao\Crud;

use App\Http\Model\Crud\SystemCrudComment;
use App\Http\Dao\BaseDao;
use Tuoluojiang\Open\traits\dao\ListSearchTrait;

/**
 * 评论
 */
class SystemCrudCommentDao extends BaseDao
{

    use ListSearchTrait;

    protected function setModel()
    {
        return SystemCrudComment::class;
    }
}
