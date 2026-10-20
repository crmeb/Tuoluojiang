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

use App\Constants\ModuleEnum;
use App\Http\Dao\Program\ProgramVersionDao;
use App\Http\Service\System\ModulePermissionService;
use App\Http\Service\BaseService;
use Tuoluojiang\Open\traits\service\ResourceServiceTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * 项目版本
 * Class ProgramVersionService.
 */
class ProgramVersionService extends BaseService
{
    use ResourceServiceTrait;

    public $dao;

    public function __construct(ProgramVersionDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['id', 'name', 'sort'], $sort = ['sort', 'created_at'], array $with = []): array
    {
        return $this->dao->getList($where, $field, 0, 0, $sort, $with);
    }

    /**
     * 保存版本.
     * @throws BindingResolutionException
     */
    public function saveVersion(array $data, int $programId): mixed
    {
        $program = app()->get(ProgramService::class)->get($programId);
        if (! $program) {
            throw $this->exception('未找到指定项目记录');
        }

        return $this->transaction(function () use ($data, $programId) {
            $creatorUid = uuid_to_uid($this->uuId(false));
            $ids        = $this->dao->column(['program_id' => $programId], 'id', 'id');
            $num        = count($data);
            foreach ($data as $datum) {
                if (! isset($datum['id']) || $datum['id'] < 1) {
                    $this->dao->create(['program_id' => $programId, 'name' => $datum['name'], 'sort' => $num, 'creator_uid' => $creatorUid]);
                } else {
                    unset($ids[$datum['id']]);
                    $this->dao->update(['id' => $datum['id'], 'program_id' => $programId], ['sort' => $num, 'name' => $datum['name']]);
                }
                --$num;
            }

            if ($ids && ! $this->dao->delete(['program_id' => $programId, 'id' => array_keys($ids)])) {
                throw $this->exception('保存失败');
            }
            return true;
        });
    }

    /**
     * 下拉列表.
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getSelectList(string $uuid, int $programId = 0): array
    {
        $where      = [];
        $adminUid   = uuid_to_uid($uuid);
        $uid        = app()->get(ModulePermissionService::class)->getAccessibleUserIds($adminUid, ModuleEnum::PROGRAM);
        $programIds = app()->get(ProgramService::class)->column(['uid' => $uid, 'admin_uid' => $adminUid, 'types' => ''], 'id', 'id');
        if ($programId) {
            if (! in_array($programId, $programIds)) {
                $programId = 0;
            }
            $where['program_id'] = $programId;
        } else {
            $where['program_id'] = $programIds;
        }
        return $this->dao->getList($where, ['id', 'name'], 0, 0, 'sort');
    }
}
