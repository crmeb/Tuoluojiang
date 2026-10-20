<?php

namespace App\Http\Dao\Work;

use App\Http\Model\Work\WorkMessageSeq;
use App\Http\Dao\BaseDao;

/**
 * 群聊消息序列号
 * Class WorkMessageSeqDao
 */
class WorkMessageSeqDao extends BaseDao
{
    /**
     * @return string
     */
    protected function setModel()
    {
        return WorkMessageSeq::class;
    }
}
