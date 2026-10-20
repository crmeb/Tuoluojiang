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

namespace App\Http\Service\User;

use App\Http\Dao\User\UserResumeDao;
use App\Http\Model\BaseModel;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model;

/**
 * 用户简历.
 */
class UserResumeService extends BaseService
{
    public $dao;

    /**
     * 个人敏感字段.
     * @var array|string[]
     */
    public array $hiddenField = ['id', 'bank_num', 'bank_name', 'spare_name', 'spare_tel', 'social_num', 'fund_num', 'card_front', 'card_both', 'education_image', 'acad_image'];

    public function __construct(UserResumeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户个人简历.
     * @return null|BaseModel|BuildsQueries|mixed|Model|object
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getInfo(string $uuid)
    {
        if (! $this->dao->exists(['uid' => $uuid])) {
            $this->dao->create(['uid' => $uuid]);
        }
        return $this->dao->get(['uid' => $uuid], ['*'], [
            'works', 'educations',
        ])->toArray();
    }

    /**
     * 保存简历信息.
     * @throws BindingResolutionException
     */
    public function saveInfo($data): int
    {
        $id = $this->dao->value(['uid' => $this->uuId(false)], 'id');
        if (! $id) {
            throw $this->exception('保存失败,未找到简历信息');
        }

        return $this->dao->update($id, $data);
    }
}
