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

namespace App\Http\Service\Program;

use App\Http\Dao\Program\ProgramTaskMemberDao;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * 项目任务成员
 * Class ProgramTaskMemberService.
 */
class ProgramTaskMemberService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(ProgramTaskMemberDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 处理成员.
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function handleMember(array $members, int $taskId): void
    {
        $data = [];
        if ($taskId) {
            foreach ($this->dao->column(['task_id' => $taskId], 'uid', 'id') as $key => $item) {
                $data[$item] = $key;
            }
        }

        foreach ($members as $member) {
            if (isset($data[$member])) {
                unset($data[$member]);
                continue;
            }
            $this->dao->create(['task_id' => $taskId, 'uid' => $member]);
        }

        if ($data) {
            $this->dao->delete(['task_id' => $taskId, 'id' => $data]);
        }
    }
}
