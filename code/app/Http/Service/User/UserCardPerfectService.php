<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

namespace App\Http\Service\User;

use App\Http\Dao\User\UserCardPerfectDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\interfaces\ResourceServicesInterface;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class UserCardPerfectService extends BaseService implements ResourceServicesInterface
{
    use ResourceServiceTrait;

    public $dao;

    protected $hidden = ['bank_num', 'bank_name', 'social_num', 'fund_num', 'card_front', 'card_both', 'education_image', 'acad_image'];

    public function __construct(UserCardPerfectDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, array $field = ['*'], $sort = null, array $with = []): array
    {
        return parent::getList($where, $field, $sort, $with);
    }

    public function resourceCreate(array $other = []): array
    {
        return [];
    }

    public function resourceEdit(int $id, array $other = []): array
    {
        return [];
    }

    /**
     * 拒绝发送/完善个人信息.
     * @param mixed $id
     * @throws BindingResolutionException
     */
    public function refusePerfect($id, string $uuid): int
    {
        $info = toArray($this->dao->get($id));
        if ($info['uid'] !== $uuid) {
            throw $this->exception('人员信息不符，禁止操作');
        }
        return $this->dao->update($id, ['status' => 2]);
    }
}
