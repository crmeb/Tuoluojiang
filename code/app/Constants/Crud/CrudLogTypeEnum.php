<?php

namespace App\Constants\Crud;

use MyCLabs\Enum\Enum;

class CrudLogTypeEnum extends Enum
{

    /**
     * 新增
     */
    const CREATE_TYPE = 'create';

    /**
     * 修改
     */
    const UPDATE_TYPE = 'update';

    /**
     * 删除
     */
    const DELETE_TYPE = 'delete';

    /**
     * 创建共享
     */
    const SHARE_CREATE = 'share_create';

    /**
     * 删除共享
     */
    const SHARE_DELETE_TYPE = 'share_delete';

    /**
     * 分享修改
     */
    const SHARE_UPDATE_TYPE = 'share_update';

    /**
     * 转移
     */
    const TRANSFER_TYPE = 'transfer';

    /**
     * 审批
     */
    const APPROVE_TYPE = 'approve';



}
