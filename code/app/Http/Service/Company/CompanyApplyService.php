<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\Company;

use App\Constants\CommonEnum;
use App\Http\Dao\User\UserEnterpriseApplyDao;
use App\Http\Service\Admin\AdminService;
use App\Task\message\EnterprisePersonnelJoinRemind;
use App\Task\message\EnterprisePersonnelRefuseRemind;
use App\Task\message\StatusChangeTask;
use App\Http\Service\BaseService;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class CompanyApplyService.
 * @method bool insert(array $data) 插入数据
 */
class CompanyApplyService extends BaseService
{
    /**
     * CompanyApplyService constructor.
     */
    public function __construct(UserEnterpriseApplyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 处理企业邀请用户.
     * @return mixed
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function setApply(int $id, int $status, string $uid)
    {
        $info = $this->dao->get(['id' => $id, 'uid' => $uid]);
        if (! $info) {
            throw $this->exception('邀请信息不存在');
        }
        if ($info->status != -1) {
            throw $this->exception('已同意,无法操作');
        }
        $info->status = $status;
        $info->verify = 1;
        // 曾经在企业中
        if (app()->get(AdminService::class)->setTrashed()->exists(['uid' => $uid])) {
            $restore = true;
        } else {
            $restore = false;
        }
        $res = $this->transaction(function () use ($info, $restore) {
            if (! $info->save()) {
                throw $this->exception('保存处理信息失败');
            }
            return true;
        });

        // 更新消息状态
        Task::deliver(new StatusChangeTask(CommonEnum::ENTERPRISE_INVITATION_NOTICE, CommonEnum::STATUS_DELETE, 0, (int) $info['id']));
        if ($status) {
            // 人员加入提醒
            Task::deliver(new EnterprisePersonnelJoinRemind($info));
        } else {
            // 人员拒绝加入提醒
            Task::deliver(new EnterprisePersonnelRefuseRemind($info));
        }
        return $res;
    }

    /**
     * 获取列表.
     * @param array|string[] $field
     * @param null|string $sort
     * @return mixed
     */
    public function getList(array $where, array $field = ['*'], $sort = null, array $with = ['user']): array
    {
        [$page, $limit] = $this->getPageValue();
        $list           = $this->dao->getList($where, $field, $page, $limit, $sort, $with);
        $count          = $this->dao->count($where);
        return $this->listData($list, $count);
    }
}
