<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Approve;

use App\Constants\AttachEnum;
use App\Constants\CacheEnum;
use App\Http\Dao\Approve\ApproveReplyDao;
use App\Http\Service\Attach\AttachService;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 审核留言表.
 */
class ApproveReplyService extends BaseService
{
    use ResourceServiceTrait;

    public function __construct(ApproveReplyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 创建评价.
     * @return BaseModel|Model
     * @throws BindingResolutionException
     */
    public function resourceSave(array $data)
    {
        $data['card_id'] = $data['user_id'] = auth('admin')->id();
        $fileId          = $data['files'];
        unset($data['files']);
        $saved = $this->dao->create($data);
        if ($saved && $fileId) {
            app()->get(AttachService::class)->saveRelation($fileId, uid_to_uuid($data['user_id']), $saved->id, AttachEnum::RELATION_TYPE_APPROVE_REPLY);
        }
        Cache::tags([CacheEnum::TAG_APPROVE])->flush();
        return $saved;
    }

    /**
     * 删除评价.
     * @param mixed $id
     * @return int
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function resourceDelete($id, ?string $key = null)
    {
        if (auth('admin')->id() != $this->dao->value(['id' => $id], 'user_id')) {
            throw $this->exception('仅可删除自己的评价！');
        }
        return $this->dao->delete($id) && app()->get(AttachService::class)->delRelationFiles((int) $id, AttachEnum::RELATION_TYPE[AttachEnum::RELATION_TYPE_APPROVE_REPLY]) && Cache::tags([CacheEnum::TAG_APPROVE])->flush();
    }
}
